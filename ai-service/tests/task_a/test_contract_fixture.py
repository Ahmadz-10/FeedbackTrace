"""Menjaga kontrak Python <-> Laravel: fixture JSON harus cocok dengan keluaran pipeline saat ini.

Regenerasi fixture dengan:
    python tests/task_a/test_contract_fixture.py --write
Lalu jalankan tes Pest untuk memastikan persister Laravel masih cocok.
"""
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.task_a.schemas.responses import AnalyzeResponse
from app.task_a.services.feedback_validator import validate_payload
from app.task_a.services.job_service import AnalysisRunner, Job
from task_a_helpers import RouterGateway, m

FIXTURE = Path(__file__).resolve().parents[3] / "tests" / "Feature" / "TaskA" / "fixtures" / "analyze_response.json"
TEXTS = ["Antreannya lama banget", "Nunggu berjam-jam baru dilayani", "Loketnya cepat", ""]


def generate() -> dict:
    gateway = RouterGateway(
        {"mentions": [m(quotes=["lama banget"])]},
        {"mentions": [m(quotes=["berjam-jam"])]},
        {"mentions": [m(quotes=["cepat"], sentiment="positive", rtype="satisfaction_driver", cat="fast_service")]},
    )
    prepared = validate_payload({
        "batch": {"batch_id": "B1", "title": "Fixture"},
        "feedbacks": [
            {"feedback_id": f"f{i+1}", "question_id": "q1", "comment_text": text}
            for i, text in enumerate(TEXTS)
        ],
    })
    output = AnalysisRunner(gateway=gateway)(
        prepared,
        Job(job_id="JOB-FIXTURE", batch_id="B1", request_id="REQ-FIXTURE"),
    )
    data = output.response.model_dump(mode="json")
    data["stats"]["processing_time_ms"] = 0
    return data


def test_laravel_fixture_matches_the_current_pipeline_output_and_the_contract():
    committed = json.loads(FIXTURE.read_text(encoding="utf-8"))
    assert committed == generate()
    AnalyzeResponse.model_validate(committed)


if __name__ == "__main__":
    import sys
    if "--write" in sys.argv:
        FIXTURE.write_text(json.dumps(generate(), indent=2, ensure_ascii=False), encoding="utf-8")
        print("fixture ditulis:", FIXTURE)
