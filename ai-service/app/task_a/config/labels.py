"""Label terpusat Task A. Semua modul memakai enum ini; jangan menulis string label manual."""
from enum import StrEnum


class Sentiment(StrEnum):
    POSITIVE = "positive"
    NEGATIVE = "negative"
    NEUTRAL = "neutral"
    MIXED = "mixed"


class SentimentStrength(StrEnum):
    LOW = "low"
    MEDIUM = "medium"
    HIGH = "high"


class ReasonType(StrEnum):
    SATISFACTION_DRIVER = "satisfaction_driver"
    DISSATISFACTION_DRIVER = "dissatisfaction_driver"
    NEUTRAL_OBSERVATION = "neutral_observation"


class FindingType(StrEnum):
    SERVICE_STRENGTH = "service_strength"
    SERVICE_WEAKNESS = "service_weakness"
    MIXED = "mixed"
    OBSERVATION = "observation"


# ---- Status (dipakai bersama Laravel; nilai string harus identik) ----

class JobStatus(StrEnum):
    PENDING = "pending"
    PROCESSING = "processing"
    COMPLETED = "completed"
    PARTIALLY_COMPLETED = "partially_completed"
    FAILED = "failed"


class FeedbackAnalysisStatus(StrEnum):
    ANALYZED = "analyzed"
    SKIPPED = "skipped"  # mis. komentar kosong / tidak dapat dianalisis
    FAILED = "failed"    # gagal validasi permanen setelah retry


class EvidenceStatus(StrEnum):
    UNVERIFIED = "unverified"
    VALID = "valid"
    INVALID = "invalid"


class ReviewStatus(StrEnum):
    PENDING_REVIEW = "pending_review"
    APPROVED = "approved"
    REJECTED = "rejected"


class ReviewAction(StrEnum):
    APPROVE = "approve"
    EDIT = "edit"
    REJECT = "reject"


def values(enum_cls: type[StrEnum]) -> tuple[str, ...]:
    return tuple(m.value for m in enum_cls)
