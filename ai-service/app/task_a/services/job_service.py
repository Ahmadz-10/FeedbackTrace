"""Layanan job in-memory untuk endpoint Task A.

FastAPI menyimpan status job sementara; Laravel/MySQL menjadi catatan permanen.
Job berjalan melalui BackgroundTasks dan harus dijalankan dengan satu proses Uvicorn.
"""
from __future__ import annotations

import logging
import threading
import uuid
from dataclasses import dataclass, field
from datetime import datetime, timezone
from typing import Callable

from app.task_a.config.labels import JobStatus
from app.task_a.schemas.responses import (
    AnalyzeResponse, AspectCount, AspectMention, BatchStats, BatchSummary, ConsolidatedFinding,
    FindingDetail, SentimentDistribution, TopFinding, Warning as ResponseWarning,
)
from app.task_a.services.analysis_service import AnalysisService
from app.task_a.services.consolidation_service import attach_to_analysis, consolidate
from app.task_a.services.feedback_validator import PreparedBatch
from app.task_a.services.llm_gateway import GroqGateway, LLMGateway
from app.task_a.services.retry_handler import ValidatedPipeline

logger = logging.getLogger("feedbacktrace.task_a.jobs")
_ACTIVE = {JobStatus.PENDING, JobStatus.PROCESSING}


def _now() -> datetime:
    return datetime.now(timezone.utc)


class BatchBusyError(Exception):
    def __init__(self, job_id: str) -> None:
        super().__init__(f"Batch sedang diproses oleh job {job_id}.")
        self.job_id = job_id


@dataclass
class Job:
    job_id: str
    batch_id: str
    request_id: str | None = None
    status: JobStatus = JobStatus.PENDING
    created_at: datetime = field(default_factory=_now)
    updated_at: datetime = field(default_factory=_now)
    stats: BatchStats | None = None
    error: str | None = None
    response: AnalyzeResponse | None = None
    mention_ids_by_finding: dict[str, list[str]] = field(default_factory=dict)

    @property
    def finished(self) -> bool:
        return self.status not in _ACTIVE


class JobStore:
    def __init__(self, max_jobs: int = 200) -> None:
        self._jobs: dict[str, Job] = {}
        self._lock = threading.RLock()
        self.max_jobs = max_jobs

    def add(self, job: Job) -> None:
        with self._lock:
            self._jobs[job.job_id] = job
            self._evict()

    def get(self, job_id: str) -> Job | None:
        with self._lock:
            return self._jobs.get(job_id)

    def active_for_batch(self, batch_id: str) -> Job | None:
        with self._lock:
            return next((j for j in self._jobs.values() if j.batch_id == batch_id and not j.finished), None)

    def latest_for_batch(self, batch_id: str) -> Job | None:
        with self._lock:
            jobs = [j for j in self._jobs.values() if j.batch_id == batch_id]
            return max(jobs, key=lambda j: j.created_at) if jobs else None

    def find_finding(self, finding_id: str) -> tuple[Job, ConsolidatedFinding] | None:
        with self._lock:
            for job in sorted(self._jobs.values(), key=lambda j: j.created_at, reverse=True):
                if job.response:
                    for finding in job.response.findings:
                        if finding.finding_id == finding_id:
                            return job, finding
        return None

    def touch(self, job: Job, **fields) -> None:
        with self._lock:
            for key, value in fields.items():
                setattr(job, key, value)
            job.updated_at = _now()

    def _evict(self) -> None:
        finished = sorted((j for j in self._jobs.values() if j.finished), key=lambda j: j.created_at)
        while len(self._jobs) > self.max_jobs and finished:
            del self._jobs[finished.pop(0).job_id]


@dataclass
class RunOutput:
    response: AnalyzeResponse
    mention_ids_by_finding: dict[str, list[str]]


class AnalysisRunner:
    """Jalankan analisis, validasi/retry, dan konsolidasi sebagai satu pipeline."""

    def __init__(self, gateway: LLMGateway | None = None, *, use_llm_summaries: bool = True) -> None:
        self._gateway = gateway
        self._use_llm_summaries = use_llm_summaries

    def __call__(self, prepared: PreparedBatch, job: Job) -> RunOutput:
        gateway = self._gateway or GroqGateway()
        result = ValidatedPipeline(AnalysisService(gateway)).run_batch(prepared)
        analysis = result.analysis
        findings: list[ConsolidatedFinding] = []
        mention_map: dict[str, list[str]] = {}
        try:
            consolidated = consolidate(
                analysis, prepared, gateway=gateway, use_llm_summaries=self._use_llm_summaries
            )
            attach_to_analysis(analysis, consolidated)
            findings = consolidated.findings
            mention_map = consolidated.mention_ids_by_finding
        except Exception:
            logger.exception("Konsolidasi gagal untuk batch %s", job.batch_id)
            analysis.warnings.append(ResponseWarning(
                code="consolidation_failed",
                message="Konsolidasi temuan gagal; hasil per komentar tetap tersedia.",
            ))
        response = AnalyzeResponse(
            request_id=job.request_id,
            job_id=job.job_id,
            batch_id=job.batch_id,
            status=result.job_status,
            metadata=analysis.metadata,
            stats=analysis.stats,
            results=analysis.results,
            findings=findings,
            warnings=analysis.warnings,
        )
        return RunOutput(response, mention_map)


class JobService:
    def __init__(
        self,
        store: JobStore | None = None,
        runner: Callable[[PreparedBatch, Job], RunOutput] | None = None,
    ) -> None:
        self.store = store or JobStore()
        self._runner = runner or AnalysisRunner()
        self._submit_lock = threading.Lock()

    def submit(self, prepared: PreparedBatch, request_id: str | None = None) -> Job:
        batch_id = prepared.batch.batch_id
        with self._submit_lock:
            busy = self.store.active_for_batch(batch_id)
            if busy:
                raise BatchBusyError(busy.job_id)
            job = Job(job_id=uuid.uuid4().hex, batch_id=batch_id, request_id=request_id)
            self.store.add(job)
            return job

    def run(self, job_id: str, prepared: PreparedBatch) -> None:
        job = self.store.get(job_id)
        if job is None:
            return
        self.store.touch(job, status=JobStatus.PROCESSING)
        try:
            output = self._runner(prepared, job)
        except Exception as exc:
            logger.exception("Job %s gagal", job_id)
            self.store.touch(job, status=JobStatus.FAILED, error=f"Analisis gagal: {type(exc).__name__}")
            return
        self.store.touch(
            job,
            status=output.response.status,
            stats=output.response.stats,
            response=output.response,
            mention_ids_by_finding=output.mention_ids_by_finding,
        )

    def get_job(self, job_id: str) -> Job | None:
        return self.store.get(job_id)

    def latest_job(self, batch_id: str) -> Job | None:
        return self.store.latest_for_batch(batch_id)

    def finding_detail(self, finding_id: str) -> FindingDetail | None:
        found = self.store.find_finding(finding_id)
        if not found:
            return None
        job, finding = found
        wanted = set(job.mention_ids_by_finding.get(finding_id, []))
        assert job.response is not None
        mentions = [
            mention
            for result in job.response.results
            for mention in result.mentions
            if mention.mention_id in wanted
        ]
        return FindingDetail(job_id=job.job_id, batch_id=job.batch_id, finding=finding, mentions=mentions)


def build_summary(job: Job) -> BatchSummary:
    response = job.response
    if response is None:
        raise ValueError("Ringkasan hanya tersedia setelah job memiliki hasil.")

    findings_by_type: dict[str, int] = {}
    for finding in response.findings:
        kind = finding.finding_type.value
        findings_by_type[kind] = findings_by_type.get(kind, 0) + 1

    total_sentiment = SentimentDistribution()
    aspects: dict[tuple[str, str | None], dict] = {}
    for result in response.results:
        for mention in result.mentions:
            sentiment = mention.sentiment.value
            setattr(total_sentiment, sentiment, getattr(total_sentiment, sentiment) + 1)
            key = (
                mention.aspect_code,
                mention.proposed_aspect_label if mention.is_proposed_aspect else None,
            )
            slot = aspects.setdefault(
                key,
                {"mentions": 0, "feedback": set(), "distribution": SentimentDistribution()},
            )
            slot["mentions"] += 1
            slot["feedback"].add(mention.feedback_id)
            setattr(
                slot["distribution"],
                sentiment,
                getattr(slot["distribution"], sentiment) + 1,
            )

    aspect_rows = [
        AspectCount(
            aspect_code=code,
            proposed_aspect_label=label,
            mention_count=slot["mentions"],
            unique_feedback_count=len(slot["feedback"]),
            sentiment_distribution=slot["distribution"],
        )
        for (code, label), slot in aspects.items()
    ]
    aspect_rows.sort(
        key=lambda row: (
            -row.unique_feedback_count,
            -row.mention_count,
            row.aspect_code,
            row.proposed_aspect_label or "",
        )
    )

    not_analyzed: dict[str, int] = {}
    for result in response.results:
        if result.status.value in {"skipped", "failed"}:
            for flag in result.quality_flags or [result.status.value]:
                not_analyzed[flag] = not_analyzed.get(flag, 0) + 1

    top = sorted(
        response.findings,
        key=lambda finding: (
            -finding.unique_feedback_count,
            -finding.mention_count,
            finding.finding_id,
        ),
    )[:5]
    return BatchSummary(
        batch_id=job.batch_id,
        job_id=job.job_id,
        status=response.status,
        stats=response.stats,
        metadata=response.metadata,
        findings_by_type=findings_by_type,
        single_feedback_findings=sum(f.single_feedback_support for f in response.findings),
        sentiment_distribution=total_sentiment,
        aspects=aspect_rows,
        top_findings=[
            TopFinding(
                finding_id=f.finding_id,
                issue_title=f.issue_title,
                finding_type=f.finding_type,
                unique_feedback_count=f.unique_feedback_count,
                mention_count=f.mention_count,
                single_feedback_support=f.single_feedback_support,
                review_status=f.review_status,
            )
            for f in top
        ],
        not_analyzed_by_flag=dict(sorted(not_analyzed.items())),
        warnings_count=len(response.warnings),
    )
