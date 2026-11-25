"""Schema request Task A (kontrak masuk dari Laravel).

Catatan pembagian validasi:
- Di sini: bentuk dan tipe field (Pydantic). comment_text boleh kosong agar komentar
  kosong dapat DITANDAI (quality flag) dan tidak hilang diam-diam.
- Segment 03 (feedback_validator): feedback_id unik dalam batch, versi schema didukung,
  komentar kosong/tidak valid ditandai.
"""
from datetime import datetime

from pydantic import BaseModel, ConfigDict, Field

SCHEMA_VERSION = "1.0"
SUPPORTED_SCHEMA_VERSIONS = frozenset({SCHEMA_VERSION})
MAX_FEEDBACKS_PER_BATCH = 500
LANGUAGE_HINT_PATTERN = r"^[a-z]{2,3}(-[A-Za-z]{2,4})?$"


class _Strict(BaseModel):
    model_config = ConfigDict(extra="forbid")


class FeedbackItem(_Strict):
    feedback_id: str = Field(min_length=1, max_length=100)
    question_id: str = Field(min_length=1, max_length=100)
    comment_text: str
    submitted_at: datetime | None = None
    language_hint: str | None = Field(default=None, pattern=LANGUAGE_HINT_PATTERN)


class AnalysisOptions(_Strict):
    max_aspects_per_feedback: int = Field(default=5, ge=1, le=10)
    allow_proposed_aspects: bool = True
    enable_consolidation: bool = True
    use_embeddings: bool = False
    max_retries: int = Field(default=2, ge=0, le=3)


class BatchMetadata(_Strict):
    batch_id: str = Field(min_length=1, max_length=100)
    title: str | None = Field(default=None, max_length=255)
    source: str | None = Field(default=None, max_length=100)
    schema_version: str = SCHEMA_VERSION


class AnalyzeRequest(_Strict):
    request_id: str | None = Field(default=None, max_length=100)
    batch: BatchMetadata
    options: AnalysisOptions = Field(default_factory=AnalysisOptions)
    feedbacks: list[FeedbackItem] = Field(min_length=1, max_length=MAX_FEEDBACKS_PER_BATCH)
