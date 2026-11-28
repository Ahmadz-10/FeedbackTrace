"""Uji Segment 07: endpoint FastAPI Task A (auth, validasi, siklus job, hasil, ringkasan, detail temuan)."""
import threading

import pytest
from fastapi.testclient import TestClient
from pydantic import SecretStr

from app.core.config import settings
from app.main import app
from app.task_a.config.labels import JobStatus
from app.task_a.schemas.responses import AnalyzeResponse, BatchSummary, FindingDetail
from app.task_a.api.routes import get_job_service
from app.task_a.services import job_service
from app.task_a.services.job_service import AnalysisRunner, BatchBusyError, Job, JobService, JobStore
from app.task_a.services.feedback_validator import validate_payload
from task_a_helpers import RouterGateway, m

BASE = "/api/v1/task-a"
POS = dict(sentiment="positive", rtype="satisfaction_driver", cat="fast_service")


def payload(*texts, batch_id="B1", **extra):
    return {"batch": {"batch_id": batch_id}, "feedbacks": [
        {"feedback_id": f"f{i+1}", "question_id": "q1", "comment_text": t} for i, t in enumerate(texts)], **extra}


SCENARIO = ["Antreannya lama banget", "Nunggu berjam-jam baru dilayani", "Loketnya cepat", ""]


def scenario_gateway():
    return RouterGateway(
        {"mentions": [m(quotes=["lama banget"])]},
        {"mentions": [m(quotes=["berjam-jam"])]},
        {"mentions": [m(quotes=["cepat"], **POS)]},
    )


@pytest.fixture
def api():
    gw = scenario_gateway()
    service = JobService(runner=AnalysisRunner(gateway=gw))
    app.dependency_overrides[get_job_service] = lambda: service
    yield TestClient(app), service, gw
    app.dependency_overrides.clear()


def run_scenario(client):
    r = client.post(f"{BASE}/analyze", json=payload(*SCENARIO, request_id="REQ-9"))
    assert r.status_code == 202, r.text
    return r.json()


def test_analyze_returns_202_with_job_id_and_the_job_completes(api):
    client, service, _ = api
    accepted = run_scenario(client)
    assert accepted["status"] == "pending" and accepted["batch_id"] == "B1" and accepted["request_id"] == "REQ-9"
    assert "Antreannya" not in str(accepted)
    job = client.get(f"{BASE}/jobs/{accepted['job_id']}").json()
    assert job["status"] == "completed" and job["stats"]["analyzed_feedbacks"] == 3 and job["stats"]["skipped_feedbacks"] == 1
    assert job["created_at"] and job["updated_at"] and job["error"] is None


def test_results_endpoint_returns_a_contract_valid_response(api):
    client, _, _ = api
    accepted = run_scenario(client)
    r = client.get(f"{BASE}/batches/B1/results")
    assert r.status_code == 200
    resp = AnalyzeResponse.model_validate(r.json())
    assert resp.job_id == accepted["job_id"] and resp.request_id == "REQ-9" and resp.status == JobStatus.COMPLETED
    assert [x.feedback_id for x in resp.results] == ["f1", "f2", "f3", "f4"]
    assert resp.stats.total_findings == 2 and resp.metadata.model_id == "model-aktual-v1"
    assert resp.metadata.decoding["summary_prompt_version"] == "finding_summary_v1"
    ev = resp.results[0].mentions[0].evidence[0]
    assert ev.status.value == "valid" and ev.start_char is not None
    assert resp.results[3].status.value == "skipped" and resp.results[3].quality_flags == ["empty_comment"]


def test_finding_detail_returns_the_finding_with_its_mentions_and_evidence(api):
    client, _, _ = api
    run_scenario(client)
    findings = client.get(f"{BASE}/batches/B1/results").json()["findings"]
    weak = next(f for f in findings if f["finding_type"] == "service_weakness")
    r = client.get(f"{BASE}/findings/{weak['finding_id']}")
    assert r.status_code == 200
    detail = FindingDetail.model_validate(r.json())
    assert detail.finding.unique_feedback_count == 2 and [x.mention_id for x in detail.mentions] == ["f1-m1", "f2-m1"]
    assert all(e.status.value == "valid" for x in detail.mentions for e in x.evidence)
    assert [v.feedback_id for v in detail.finding.minority_views] == ["f3"]


def test_batch_summary_aggregates_for_the_dashboard(api):
    client, _, _ = api
    run_scenario(client)
    r = client.get(f"{BASE}/batches/B1/summary")
    assert r.status_code == 200
    s = BatchSummary.model_validate(r.json())
    assert s.findings_by_type == {"service_weakness": 1, "service_strength": 1}
    assert s.single_feedback_findings == 1
    assert s.sentiment_distribution.model_dump() == {"positive": 1, "negative": 2, "neutral": 0, "mixed": 0}
    assert (s.aspects[0].aspect_code, s.aspects[0].mention_count, s.aspects[0].unique_feedback_count) == ("service_speed", 3, 3)
    assert s.top_findings[0].unique_feedback_count == 2 and s.top_findings[0].review_status.value == "pending_review"
    assert s.not_analyzed_by_flag == {"empty_comment": 1}
    assert s.stats.total_findings == 2 and s.status == JobStatus.COMPLETED


def test_every_endpoint_except_health_requires_the_service_key_when_configured(api, monkeypatch):
    client, _, _ = api
    monkeypatch.setattr(settings, "service_api_key", SecretStr("rahasia"))
    calls = [("post", "/analyze", payload("Antreannya lama banget")), ("get", "/jobs/x", None),
             ("get", "/batches/B1/results", None), ("get", "/batches/B1/summary", None), ("get", "/findings/F-x", None)]
    for method, path, body in calls:
        assert getattr(client, method)(BASE + path, **({"json": body} if body else {})).status_code == 401, path
        bad = getattr(client, method)(BASE + path, headers={"X-Service-Key": "salah"}, **({"json": body} if body else {}))
        assert bad.status_code == 401, path
    assert client.get(f"{BASE}/health").status_code == 200
    ok = client.post(f"{BASE}/analyze", json=payload("Antreannya lama banget"), headers={"X-Service-Key": "rahasia"})
    assert ok.status_code == 202


def test_invalid_batch_is_rejected_with_structured_errors_and_creates_no_job(api):
    client, service, _ = api
    body = payload("Antre lama", "Petugas ramah")
    body["feedbacks"][1]["feedback_id"] = "f1"
    r = client.post(f"{BASE}/analyze", json=body)
    assert r.status_code == 422
    j = r.json()
    assert j["error"] == "invalid_batch" and j["errors"][0]["code"] == "duplicate_feedback_id" and j["errors"][0]["feedback_id"] == "f1"
    assert service.store._jobs == {}


@pytest.mark.parametrize("body, code", [
    ({"feedbacks": [{"feedback_id": "a", "question_id": "q", "comment_text": "x"}]}, "missing_batch"),
    ({"batch": {"batch_id": "B"}, "feedbacks": []}, "empty_batch"),
    ({"batch": {"batch_id": "B", "schema_version": "9.9"}, "feedbacks": [{"feedback_id": "a", "question_id": "q", "comment_text": "x"}]}, "unsupported_schema_version"),
    ({"batch": {}, "feedbacks": [{"feedback_id": "a", "question_id": "q", "comment_text": "x"}]}, "schema_error"),
    (["bukan", "objek"], "invalid_payload"),
])
def test_batch_level_problems_return_422_with_the_error_code(api, body, code):
    client, _, _ = api
    r = client.post(f"{BASE}/analyze", json=body)
    assert r.status_code == 422 and code in [e["code"] for e in r.json()["errors"]]


def test_malformed_json_body_is_a_422(api):
    client, _, _ = api
    r = client.post(f"{BASE}/analyze", content=b"{tidak json", headers={"Content-Type": "application/json"})
    assert r.status_code == 422


def test_comment_level_problems_are_returned_as_warnings_but_the_batch_runs(api):
    client, _, _ = api
    r = client.post(f"{BASE}/analyze", json=payload("", "Antreannya lama banget"))
    assert r.status_code == 202 and [w["code"] for w in r.json()["warnings"]] == ["empty_comment"]


def test_a_batch_where_everything_fails_ends_failed_but_results_are_still_readable():
    gw = RouterGateway("x", "x", "x")
    service = JobService(runner=AnalysisRunner(gateway=gw))
    app.dependency_overrides[get_job_service] = lambda: service
    try:
        client = TestClient(app)
        jid = client.post(f"{BASE}/analyze", json=payload("Antreannya lama banget")).json()["job_id"]
        assert client.get(f"{BASE}/jobs/{jid}").json()["status"] == "failed"
        res = client.get(f"{BASE}/batches/B1/results")
        assert res.status_code == 200 and res.json()["status"] == "failed"
        assert res.json()["results"][0]["quality_flags"][-1] == "invalid_llm_output"
    finally:
        app.dependency_overrides.clear()


def test_partially_completed_status_is_reported():
    gw = RouterGateway({"mentions": [m(quotes=["lama banget"])]}, "x", "x", "x")
    service = JobService(runner=AnalysisRunner(gateway=gw))
    app.dependency_overrides[get_job_service] = lambda: service
    try:
        client = TestClient(app)
        jid = client.post(f"{BASE}/analyze", json=payload("Antreannya lama banget", "Prosedurnya jelas")).json()["job_id"]
        job = client.get(f"{BASE}/jobs/{jid}").json()
        assert job["status"] == "partially_completed" and job["stats"]["failed_feedbacks"] == 1
    finally:
        app.dependency_overrides.clear()


def test_unexpected_runner_exception_fails_the_job_without_leaking_details():
    def boom(prepared, job):
        raise RuntimeError("detail internal: komentar mahasiswa rahasia")

    service = JobService(runner=boom)
    app.dependency_overrides[get_job_service] = lambda: service
    try:
        client = TestClient(app)
        jid = client.post(f"{BASE}/analyze", json=payload("Antreannya lama banget")).json()["job_id"]
        job = client.get(f"{BASE}/jobs/{jid}").json()
        assert job["status"] == "failed" and job["error"] == "Analisis gagal: RuntimeError"
        assert "rahasia" not in str(job)
        r = client.get(f"{BASE}/batches/B1/results")
        assert r.status_code == 409 and r.json()["error"] == "job_failed_without_result"
    finally:
        app.dependency_overrides.clear()


def test_consolidation_failure_keeps_the_per_comment_results(api, monkeypatch):
    client, _, _ = api

    def broken(*a, **k):
        raise ValueError("bug konsolidasi")

    monkeypatch.setattr(job_service, "consolidate", broken)
    run_scenario(client)
    res = client.get(f"{BASE}/batches/B1/results").json()
    assert res["status"] == "completed" and res["findings"] == [] and len(res["results"]) == 4
    assert "consolidation_failed" in [w["code"] for w in res["warnings"]]


def test_unknown_ids_return_404_with_error_codes(api):
    client, _, _ = api
    for path, code in [("/jobs/nope", "job_not_found"), ("/batches/ghost/results", "batch_not_found"),
                       ("/batches/ghost/summary", "batch_not_found"), ("/findings/F-ghost", "finding_not_found")]:
        r = client.get(BASE + path)
        assert r.status_code == 404 and r.json()["error"] == code, path


def test_results_are_not_available_while_the_job_is_still_running(api):
    client, service, _ = api
    job = service.submit(validate_payload(payload("Antreannya lama banget")))
    for path in ("/batches/B1/results", "/batches/B1/summary"):
        r = client.get(BASE + path)
        assert r.status_code == 409 and r.json() == {"error": "job_not_finished", "detail": "Job masih berjalan.",
                                                      "job_id": job.job_id, "status": "pending"}
    assert client.get(f"{BASE}/jobs/{job.job_id}").json()["status"] == "pending"


def test_the_same_batch_cannot_be_analyzed_twice_at_once_but_can_be_rerun_after_it_finishes(api):
    client, service, _ = api
    busy = service.submit(validate_payload(payload("Antreannya lama banget")))
    r = client.post(f"{BASE}/analyze", json=payload("Antreannya lama banget"))
    assert r.status_code == 409 and r.json()["error"] == "batch_busy" and r.json()["job_id"] == busy.job_id
    service.store.touch(busy, status=JobStatus.COMPLETED)
    again = client.post(f"{BASE}/analyze", json=payload("Antreannya lama banget"))
    assert again.status_code == 202 and again.json()["job_id"] != busy.job_id
    assert service.latest_job("B1").job_id == again.json()["job_id"]


def test_concurrent_submissions_for_one_batch_allow_exactly_one():
    service = JobService(runner=lambda p, j: None)
    outcomes, barrier = [], threading.Barrier(8)

    def go():
        barrier.wait()
        try:
            service.submit(validate_payload(payload("Antreannya lama banget")))
            outcomes.append("ok")
        except BatchBusyError:
            outcomes.append("busy")

    threads = [threading.Thread(target=go) for _ in range(8)]
    [t.start() for t in threads]
    [t.join() for t in threads]
    assert sorted(outcomes) == ["busy"] * 7 + ["ok"]


def test_different_batches_run_independently(api):
    client, _, _ = api
    assert client.post(f"{BASE}/analyze", json=payload("Antreannya lama banget", batch_id="B1")).status_code == 202
    assert client.post(f"{BASE}/analyze", json=payload("Nunggu berjam-jam", batch_id="B2")).status_code == 202


def test_stored_results_contain_no_original_personal_data(api):
    client, service, gw = api
    gw.extraction[:] = [{"mentions": [m(quotes=["antreannya lama banget"])]}]
    original_text = "Pak Joko lambat, hubungi budi@kampus.ac.id atau 081234567890 (NIM 2110511001), antreannya lama banget"
    client.post(f"{BASE}/analyze", json=payload(original_text))
    stored = client.get(f"{BASE}/batches/B1/results").text + client.get(f"{BASE}/batches/B1/summary").text
    for secret in ("Joko", "budi@kampus.ac.id", "081234567890", "2110511001"):
        assert secret not in stored
    assert original_text not in repr(service.store._jobs)
    ev = client.get(f"{BASE}/batches/B1/results").json()["results"][0]["mentions"][0]["evidence"][0]
    assert original_text[ev["start_char"]:ev["end_char"]] == "antreannya lama banget"


def test_job_store_evicts_oldest_finished_jobs_but_never_active_ones():
    store = JobStore(max_jobs=2)
    jobs = [Job(job_id=f"j{i}", batch_id=f"B{i}") for i in range(4)]
    jobs[0].status = jobs[1].status = JobStatus.COMPLETED
    for job in jobs:
        store.add(job)
    assert store.get("j0") is None and store.get("j1") is None
    assert store.get("j2") and store.get("j3")
    store.add(Job(job_id="j4", batch_id="B4"))
    assert len(store._jobs) == 3


def test_openapi_lists_the_task_a_endpoints():
    paths = app.openapi()["paths"]
    for path in ("/analyze", "/jobs/{job_id}", "/batches/{batch_id}/results", "/batches/{batch_id}/summary", "/findings/{finding_id}"):
        assert BASE + path in paths
