from __future__ import annotations
from dataclasses import dataclass, field
from app.task_a.schemas.responses import AspectMention, BatchStats, FeedbackResult, ModelMetadata, Warning as ResponseWarning

class LLMOutputError(Exception): pass

@dataclass(frozen=True)
class ParseIssue:
    code: str
    message: str
    mention_index: int | None = None

@dataclass
class ItemAnalysis:
    feedback_id: str
    status: str
    mentions: list[AspectMention] = field(default_factory=list)
    quality_flags: list[str] = field(default_factory=list)
    issues: list[ParseIssue] = field(default_factory=list)
    error: str | None = None
    model_id: str | None = None
    latency_ms: int = 0
    usage: dict = field(default_factory=dict)
    retryable: bool = True
    attempts: int = 1
    def to_result(self) -> FeedbackResult:
        return FeedbackResult(feedback_id=self.feedback_id, status=self.status,
            quality_flags=list(self.quality_flags), mentions=list(self.mentions), error=self.error)

@dataclass
class BatchAnalysis:
    items: list[ItemAnalysis]
    stats: BatchStats
    metadata: ModelMetadata | None
    warnings: list[ResponseWarning] = field(default_factory=list)
    @property
    def results(self) -> list[FeedbackResult]:
        return [i.to_result() for i in self.items]

@dataclass(frozen=True)
class MentionIssue:
    code: str
    message: str
    mention_id: str | None = None
    severity: str = "error"
    @property
    def is_error(self) -> bool:
        return self.severity == "error"
