"""Smoke test Task A: router terdaftar dan memakai komponen shared (bukan duplikat)."""
from fastapi.testclient import TestClient

from app.main import app
from app.task_a.config.versions import PIPELINE_VERSION

client = TestClient(app)


def test_task_a_router_registered():
    assert "/api/v1/task-a/health" in app.openapi()["paths"]
    r = client.get("/api/v1/task-a/health")
    assert r.status_code == 200
    assert r.json() == {"status": "ok", "module": "task_a", "pipeline_version": PIPELINE_VERSION}


def test_shared_routes_still_registered_after_task_a_router():
    paths = app.openapi()["paths"]
    assert "/api/v1/shared/chat" in paths and "/api/v1/shared/embeddings" in paths
    assert client.get("/health").status_code == 200


def test_task_a_does_not_duplicate_shared_clients():
    from pathlib import Path
    task_a = Path(__file__).resolve().parents[2] / "app" / "task_a"
    offenders = [str(p.name) for p in task_a.rglob("*.py")
                 if "Groq(" in p.read_text(encoding="utf-8") or "SentenceTransformer(" in p.read_text(encoding="utf-8")]
    assert offenders == []
