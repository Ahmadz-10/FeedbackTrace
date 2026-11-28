from fastapi.testclient import TestClient

from app.main import app
from app.task_b.ipa.calculator import IPACalculator
from app.task_b.ipa.quadrant_classifier import QuadrantClassifier
from app.task_b.services.survey_validator import SurveyValidator

client = TestClient(app)


def row(response_id, attribute, importance, performance):
    return {
        "response_id": response_id,
        "service_attribute": attribute,
        "importance_score": importance,
        "performance_score": performance,
        "period": "2026-S1",
        "source": "survey",
    }


def approved_finding(review_status="approved"):
    return {
        "schema_version": "1.0",
        "finding_id": "F-001",
        "batch_id": "B-001",
        "aspect_code": "service_speed",
        "proposed_aspect_label": None,
        "reason_category": "long_waiting_time",
        "finding_type": "service_weakness",
        "issue_title": "Long waiting time",
        "issue_summary": "Students report long waiting times.",
        "unique_feedback_count": 2,
        "mention_count": 2,
        "supporting_feedback_ids": ["fb-1", "fb-2"],
        "sentiment_distribution": {"positive": 0, "negative": 2, "neutral": 0, "mixed": 0},
        "minority_views": [],
        "review_status": review_status,
        "reviewed_at": "2026-11-28T08:00:00Z",
    }


def test_survey_validator_reports_invalid_records():
    result = SurveyValidator.validate_rows([row("r1", "service_speed", 5, 3), row("", "service_access", 6, 2)])
    assert result["total_checked"] == 2
    assert result["valid_count"] == 1
    assert result["error_count"] == 1
    assert result["errors"][0]["row_number"] == 2


def test_ipa_calculator_computes_mean_gap_and_response_count():
    result = IPACalculator.calculate_ipa([row("r1", "service_speed", 4, 2), row("r2", "service_speed", 5, 3)])
    assert result == [{
        "service_attribute": "service_speed",
        "mean_importance": 4.5,
        "mean_performance": 2.5,
        "gap": -2.0,
        "response_count": 2,
    }]


def test_quadrant_classifier_assigns_all_four_quadrants():
    results = [
        {"service_attribute": "A", "mean_importance": 5, "mean_performance": 5},
        {"service_attribute": "B", "mean_importance": 5, "mean_performance": 1},
        {"service_attribute": "C", "mean_importance": 1, "mean_performance": 1},
        {"service_attribute": "D", "mean_importance": 1, "mean_performance": 5},
    ]
    classified = QuadrantClassifier.classify_quadrants(results)
    assert [item["quadrant"] for item in classified] == [
        "I - Keep Up the Good Work",
        "II - Concentrate Here",
        "III - Low Priority",
        "IV - Possible Overkill",
    ]


def test_task_b_health_endpoint_is_versioned():
    response = client.get("/api/v1/task-b/health")
    assert response.status_code == 200
    assert response.json() == {"status": "ok", "module": "task_b"}


def test_task_b_ipa_endpoint_returns_results_and_validation_summary():
    response = client.post("/api/v1/task-b/calculate-ipa", json=[
        row("r1", "service_speed", 5, 2), row("r2", "service_speed", 4, 3),
        row("r3", "service_access", 3, 4), row("", "service_access", 9, 4),
    ])
    assert response.status_code == 200
    body = response.json()
    assert body["summary"] == {"total_submitted": 4, "valid_records": 3, "invalid_records": 1}
    speed = next(x for x in body["ipa_analysis_results"] if x["service_attribute"] == "service_speed")
    assert (speed["mean_importance"], speed["mean_performance"], speed["gap"]) == (4.5, 2.5, -2.0)
    assert speed["quadrant"] == "II - Concentrate Here"
    assert len(body["validation_errors"]) == 1


def test_task_b_ipa_endpoint_rejects_batches_without_valid_rows():
    response = client.post("/api/v1/task-b/calculate-ipa", json=[row("", "service_speed", 7, 0)])
    assert response.status_code == 400


def test_task_b_accepts_approved_findings_matching_task_a_export_contract():
    response = client.post("/api/v1/task-b/validate-findings", json=[approved_finding()])
    assert response.status_code == 200
    assert response.json()["status"] == "success"


def test_task_b_rejects_findings_not_approved():
    response = client.post("/api/v1/task-b/validate-findings", json=[approved_finding("pending_review")])
    assert response.status_code == 400
