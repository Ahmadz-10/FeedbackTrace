"""Router Task A. Prefix API: /api/v1/task-a.

/health sengaja terbuka (seperti /health utama). Endpoint analisis (Segment 07) WAJIB memakai
`dependencies=[Depends(require_service_key)]` dari app.api.dependencies, seperti router shared.
"""
from fastapi import APIRouter

from app.task_a.config.versions import PIPELINE_VERSION

router = APIRouter(prefix="/api/v1/task-a", tags=["task-a"])


@router.get("/health")
async def task_a_health():
    return {"status": "ok", "module": "task_a", "pipeline_version": PIPELINE_VERSION}
