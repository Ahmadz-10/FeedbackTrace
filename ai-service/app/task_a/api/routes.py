"""Router Task A. Prefix API: /api/v1/task-a.

POST /analyze menerima batch dan menjadwalkan job; Laravel mem-poll status, mengambil hasil,
dan menyimpannya secara permanen. Hanya /health yang sengaja tidak dilindungi API key.
"""
from dataclasses import asdict
from functools import lru_cache
from typing import Any

from fastapi import APIRouter, BackgroundTasks, Body, Depends
from fastapi.responses import JSONResponse

from app.api.dependencies import require_service_key
from app.task_a.config.versions import PIPELINE_VERSION
from app.task_a.schemas.responses import (
    AnalyzeAccepted,
    AnalyzeResponse,
    BatchSummary,
    FindingDetail,
    JobStatusResponse,
    Warning as ResponseWarning,
)
from app.task_a.services.feedback_validator import validate_payload
from app.task_a.services.job_service import BatchBusyError, Job, JobService, build_summary

router = APIRouter(prefix="/api/v1/task-a", tags=["task-a"])
secured = [Depends(require_service_key)]


@lru_cache
def get_job_service() -> JobService:
    return JobService()


def _error(http_status: int, code: str, detail: str, **extra) -> JSONResponse:
    return JSONResponse(status_code=http_status, content={"error": code, "detail": detail, **extra})


@router.get("/health")
async def task_a_health():
    return {"status": "ok", "module": "task_a", "pipeline_version": PIPELINE_VERSION}


@router.post(
    "/analyze",
    status_code=202,
    response_model=AnalyzeAccepted,
    dependencies=secured,
    responses={409: {"description": "Batch sedang diproses"}, 422: {"description": "Batch tidak valid"}},
)
async def analyze(
    background: BackgroundTasks,
    payload: Any = Body(...),
    service: JobService = Depends(get_job_service),
):
    prepared = validate_payload(payload)
    if not prepared.ok:
        return JSONResponse(
            status_code=422,
            content={
                "error": "invalid_batch",
                "errors": [asdict(item) for item in prepared.errors],
                "warnings": [asdict(item) for item in prepared.warnings],
            },
        )

    request_id = payload.get("request_id") if isinstance(payload, dict) else None
    try:
        job = service.submit(prepared, request_id if isinstance(request_id, str) else None)
    except BatchBusyError as busy:
        return _error(409, "batch_busy", str(busy), job_id=busy.job_id)

    background.add_task(service.run, job.job_id, prepared)
    return AnalyzeAccepted(
        job_id=job.job_id,
        batch_id=job.batch_id,
        request_id=job.request_id,
        status=job.status,
        warnings=[
            ResponseWarning(code=warning.code, message=warning.message, feedback_id=warning.feedback_id)
            for warning in prepared.warnings
        ],
    )


@router.get("/jobs/{job_id}", response_model=JobStatusResponse, dependencies=secured)
async def get_job(job_id: str, service: JobService = Depends(get_job_service)):
    job = service.get_job(job_id)
    if job is None:
        return _error(404, "job_not_found", "Job tidak ditemukan (mungkin hilang karena service restart).")
    return JobStatusResponse(
        job_id=job.job_id,
        batch_id=job.batch_id,
        status=job.status,
        stats=job.stats,
        error=job.error,
        created_at=job.created_at,
        updated_at=job.updated_at,
    )


def _finished_job(service: JobService, batch_id: str) -> Job | JSONResponse:
    job = service.latest_job(batch_id)
    if job is None:
        return _error(404, "batch_not_found", "Belum ada job untuk batch ini.")
    if not job.finished:
        return _error(
            409,
            "job_not_finished",
            "Job masih berjalan.",
            job_id=job.job_id,
            status=job.status.value,
        )
    if job.response is None:
        return _error(
            409,
            "job_failed_without_result",
            job.error or "Job gagal tanpa hasil.",
            job_id=job.job_id,
        )
    return job


@router.get("/batches/{batch_id}/results", response_model=AnalyzeResponse, dependencies=secured)
async def batch_results(batch_id: str, service: JobService = Depends(get_job_service)):
    job = _finished_job(service, batch_id)
    return job if isinstance(job, JSONResponse) else job.response


@router.get("/batches/{batch_id}/summary", response_model=BatchSummary, dependencies=secured)
async def batch_summary(batch_id: str, service: JobService = Depends(get_job_service)):
    job = _finished_job(service, batch_id)
    return job if isinstance(job, JSONResponse) else build_summary(job)


@router.get("/findings/{finding_id}", response_model=FindingDetail, dependencies=secured)
async def finding_detail(finding_id: str, service: JobService = Depends(get_job_service)):
    detail = service.finding_detail(finding_id)
    if detail is None:
        return _error(404, "finding_not_found", "Temuan tidak ditemukan pada job yang tersimpan.")
    return detail
