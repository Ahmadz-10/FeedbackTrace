"""Schema response Task A (kontrak keluar ke Laravel dan, via finding approved, ke Task B).

aspect_code dan reason_category bertipe str di sini; keanggotaannya pada taxonomy /
reason_categories serta konsistensi reason_type diperiksa di schema_validator (Segment 05).
"""
from datetime import datetime

from pydantic import BaseModel, Field

from app.task_a.config.labels import (
    EvidenceStatus,
    FeedbackAnalysisStatus,
    FindingType,
    JobStatus,
    ReasonType,
    ReviewStatus,
    Sentiment,
    SentimentStrength,
)
from app.task_a.schemas.requests import SCHEMA_VERSION


class EvidenceItem(BaseModel):
    quote: str
    feedback_id: str
    start_char: int | None = Field(default=None, ge=0)
    end_char: int | None = Field(default=None, ge=0)
    status: EvidenceStatus = EvidenceStatus.UNVERIFIED


class AspectMention(BaseModel):
    mention_id: str
    feedback_id: str
    aspect_code: str
    is_proposed_aspect: bool = False
    proposed_aspect_label: str | None = None
    sentiment: Sentiment
    sentiment_strength: SentimentStrength | None = None
    reason_type: ReasonType
    reason_category: str
    reason_description: str | None = None
    evidence: list[EvidenceItem] = Field(default_factory=list)


class FeedbackResult(BaseModel):
    feedback_id: str
    status: FeedbackAnalysisStatus
    quality_flags: list[str] = Field(default_factory=list)
    mentions: list[AspectMention] = Field(default_factory=list)
    error: str | None = None


class SentimentDistribution(BaseModel):
    positive: int = 0
    negative: int = 0
    neutral: int = 0
    mixed: int = 0


class MinorityView(BaseModel):
    feedback_id: str
    sentiment: Sentiment
    summary: str


class ConsolidatedFinding(BaseModel):
    finding_id: str
    aspect_code: str
    proposed_aspect_label: str | None = None
    reason_category: str
    finding_type: FindingType
    issue_title: str
    issue_summary: str
    unique_feedback_count: int = Field(ge=0)
    mention_count: int = Field(ge=0)
    supporting_feedback_ids: list[str]
    sentiment_distribution: SentimentDistribution
    minority_views: list[MinorityView] = Field(default_factory=list)
    single_feedback_support: bool = False
    quality_flags: list[str] = Field(default_factory=list)
    review_status: ReviewStatus = ReviewStatus.PENDING_REVIEW


class ModelMetadata(BaseModel):
    provider: str
    model_id: str
    prompt_version: str
    pipeline_version: str
    schema_version: str = SCHEMA_VERSION
    taxonomy_version: str | None = None
    decoding: dict = Field(default_factory=dict)


class BatchStats(BaseModel):
    total_feedbacks: int = 0
    analyzed_feedbacks: int = 0
    skipped_feedbacks: int = 0
    failed_feedbacks: int = 0
    total_mentions: int = 0
    valid_evidence_count: int = 0
    invalid_evidence_count: int = 0
    total_findings: int = 0
    processing_time_ms: int = 0


class Warning(BaseModel):
    code: str
    message: str
    feedback_id: str | None = None


class AnalyzeResponse(BaseModel):
    request_id: str | None = None
    job_id: str
    batch_id: str
    status: JobStatus
    metadata: ModelMetadata | None = None
    stats: BatchStats = Field(default_factory=BatchStats)
    results: list[FeedbackResult] = Field(default_factory=list)
    findings: list[ConsolidatedFinding] = Field(default_factory=list)
    warnings: list[Warning] = Field(default_factory=list)


class JobStatusResponse(BaseModel):
    job_id: str
    batch_id: str
    status: JobStatus
    stats: BatchStats | None = None
    error: str | None = None
    created_at: datetime | None = None
    updated_at: datetime | None = None


class ApprovedFinding(BaseModel):
    """Hanya temuan berstatus approved yang boleh diekspor sebagai input Task B."""

    schema_version: str = SCHEMA_VERSION
    finding_id: str
    batch_id: str
    aspect_code: str
    proposed_aspect_label: str | None = None
    reason_category: str
    finding_type: FindingType
    issue_title: str
    issue_summary: str
    unique_feedback_count: int = Field(ge=0)
    mention_count: int = Field(ge=0)
    supporting_feedback_ids: list[str]
    sentiment_distribution: SentimentDistribution
    minority_views: list[MinorityView] = Field(default_factory=list)
    review_status: ReviewStatus = ReviewStatus.APPROVED
    reviewed_at: datetime | None = None


class AnalyzeAccepted(BaseModel):
    """Balasan POST /analyze: pekerjaan diterima dan berjalan di latar belakang."""
    job_id: str
    batch_id: str
    request_id: str | None = None
    status: JobStatus = JobStatus.PENDING
    warnings: list[Warning] = Field(default_factory=list)


class FindingDetail(BaseModel):
    job_id: str
    batch_id: str
    finding: ConsolidatedFinding
    mentions: list[AspectMention] = Field(default_factory=list)


class AspectCount(BaseModel):
    aspect_code: str
    proposed_aspect_label: str | None = None
    mention_count: int
    unique_feedback_count: int
    sentiment_distribution: SentimentDistribution


class TopFinding(BaseModel):
    finding_id: str
    issue_title: str
    finding_type: FindingType
    unique_feedback_count: int
    mention_count: int
    single_feedback_support: bool
    review_status: ReviewStatus


class BatchSummary(BaseModel):
    batch_id: str
    job_id: str
    status: JobStatus
    stats: BatchStats
    metadata: ModelMetadata | None = None
    findings_by_type: dict[str, int] = Field(default_factory=dict)
    single_feedback_findings: int = 0
    sentiment_distribution: SentimentDistribution = Field(default_factory=SentimentDistribution)
    aspects: list[AspectCount] = Field(default_factory=list)
    top_findings: list[TopFinding] = Field(default_factory=list)
    not_analyzed_by_flag: dict[str, int] = Field(default_factory=dict)
    warnings_count: int = 0
