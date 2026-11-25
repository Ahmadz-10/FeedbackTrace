"""Validasi batch feedback dan penyiapan teks aman sebelum pemanggilan model."""
from __future__ import annotations
import re
from collections import Counter
from dataclasses import dataclass, field
from datetime import datetime
from typing import Any, Iterable, Literal, Mapping
from pydantic import TypeAdapter, ValidationError
from app.task_a.schemas.requests import (
    LANGUAGE_HINT_PATTERN, MAX_FEEDBACKS_PER_BATCH, SUPPORTED_SCHEMA_VERSIONS,
    AnalysisOptions, AnalyzeRequest, BatchMetadata, FeedbackItem,
)
from app.task_a.services.preprocessor import PreprocessResult, preprocess
from app.task_a.services.privacy_filter import PrivacyFilter, RedactionResult, contains_pii
from app.task_a.services.quality_flags import QualityAssessment, QualityFlag, assess_text

_MISSING = object()
_KNOWN_ITEM_FIELDS = frozenset(FeedbackItem.model_fields)
_DATETIME = TypeAdapter(datetime)
_ITEM_MESSAGES = {
    QualityFlag.EMPTY_COMMENT: "Komentar kosong; tidak dianalisis.",
    QualityFlag.NO_TEXT_CONTENT: "Komentar tidak berisi huruf; tidak dianalisis.",
    QualityFlag.VERY_SHORT: "Komentar terlalu pendek untuk dianalisis.",
    QualityFlag.TOO_LONG: "Komentar melebihi batas panjang analisis; tidak dianalisis.",
    QualityFlag.INVALID_ITEM: "Item feedback bukan objek yang valid.",
    QualityFlag.MISSING_FEEDBACK_ID: "feedback_id hilang atau kosong.",
    QualityFlag.MISSING_QUESTION_ID: "question_id hilang atau kosong.",
    QualityFlag.MISSING_COMMENT_TEXT: "comment_text hilang.",
    QualityFlag.INVALID_COMMENT_TYPE: "comment_text bukan string.",
    QualityFlag.INVALID_SUBMITTED_AT: "submitted_at bukan tanggal/waktu yang valid.",
    QualityFlag.INVALID_LANGUAGE_HINT: "language_hint tidak valid.",
    QualityFlag.PRIVACY_BLOCKED: "Data pribadi tersisa setelah redaksi; tidak dikirim ke LLM.",
}

@dataclass(frozen=True)
class ValidationIssue:
    code: str
    message: str
    severity: Literal["error", "warning"]
    feedback_id: str | None = None
    path: str | None = None

@dataclass
class PreparedFeedback:
    index: int
    feedback_id: str
    question_id: str | None
    original_text: str
    normalized_text: str
    analysis_text: str
    quality_flags: list[str]
    analyzable: bool
    language_hint: str | None = None
    redaction_kinds: list[str] = field(default_factory=list)
    preprocessing: PreprocessResult | None = None
    redaction: RedactionResult | None = None
    def analysis_span_to_original(self, start: int, end: int) -> tuple[int, int]:
        if self.preprocessing is None:
            raise ValueError("Item tidak memiliki hasil preprocessing.")
        if self.redaction is not None:
            start, end = self.redaction.to_source_span(start, end)
        return self.preprocessing.to_original_span(start, end)

@dataclass
class PreparedBatch:
    batch: BatchMetadata | None
    options: AnalysisOptions | None
    items: list[PreparedFeedback] = field(default_factory=list)
    issues: list[ValidationIssue] = field(default_factory=list)
    @property
    def errors(self) -> list[ValidationIssue]:
        return [i for i in self.issues if i.severity == "error"]
    @property
    def warnings(self) -> list[ValidationIssue]:
        return [i for i in self.issues if i.severity == "warning"]
    @property
    def ok(self) -> bool:
        return not self.errors
    @property
    def analyzable_items(self) -> list[PreparedFeedback]:
        return [i for i in self.items if i.analyzable]
    @property
    def skipped_items(self) -> list[PreparedFeedback]:
        return [i for i in self.items if not i.analyzable]

def validate_and_prepare(request: AnalyzeRequest, privacy: PrivacyFilter | None = None) -> PreparedBatch:
    return _prepare(request.batch, request.options, [i.model_dump() for i in request.feedbacks], privacy)

def validate_payload(payload: Any, privacy: PrivacyFilter | None = None) -> PreparedBatch:
    if not isinstance(payload, Mapping):
        return _fatal(None, None, "invalid_payload", "Payload harus berupa objek JSON.")
    issues: list[ValidationIssue] = []
    batch = _parse_model(BatchMetadata, payload.get("batch"), "batch", issues, missing_code="missing_batch")
    options = _parse_model(AnalysisOptions, payload.get("options", {}), "options", issues)
    for key in payload:
        if key not in {"request_id", "batch", "options", "feedbacks"}:
            issues.append(ValidationIssue("unknown_field", f"Field tidak dikenal diabaikan: {key}", "warning", path=str(key)))
    feedbacks = payload.get("feedbacks")
    if not isinstance(feedbacks, list):
        issues.append(ValidationIssue("invalid_feedbacks", "feedbacks harus berupa daftar.", "error", path="feedbacks"))
        return PreparedBatch(batch, options, [], issues)
    if any(i.severity == "error" for i in issues):
        return PreparedBatch(batch, options, [], issues)
    return _prepare(batch, options, feedbacks, privacy, extra_issues=issues)

def _fatal(batch, options, code: str, message: str) -> PreparedBatch:
    return PreparedBatch(batch, options, [], [ValidationIssue(code, message, "error")])

def _parse_model(model, value, path: str, issues: list[ValidationIssue], missing_code: str | None = None):
    if value is None and missing_code:
        issues.append(ValidationIssue(missing_code, f"{path} wajib ada.", "error", path=path))
        return None
    try:
        return model.model_validate(value)
    except ValidationError as exc:
        for error in exc.errors():
            loc = ".".join([path, *(str(p) for p in error["loc"])])
            issues.append(ValidationIssue("schema_error", error["msg"], "error", path=loc))
        return None

def _prepare(batch, options, raw_items: list[Any], privacy, extra_issues: Iterable[ValidationIssue] = ()) -> PreparedBatch:
    issues = list(extra_issues)
    if not batch.batch_id.strip():
        issues.append(ValidationIssue("blank_batch_id", "batch_id tidak boleh kosong.", "error", path="batch.batch_id"))
    if batch.schema_version not in SUPPORTED_SCHEMA_VERSIONS:
        issues.append(ValidationIssue("unsupported_schema_version", "Versi schema tidak didukung.", "error", path="batch.schema_version"))
    if not raw_items:
        issues.append(ValidationIssue("empty_batch", "Batch tidak berisi feedback.", "error", path="feedbacks"))
    elif len(raw_items) > MAX_FEEDBACKS_PER_BATCH:
        issues.append(ValidationIssue("batch_too_large", f"Batch melebihi {MAX_FEEDBACKS_PER_BATCH} feedback.", "error", path="feedbacks"))
    ids = [r["feedback_id"].strip() for r in raw_items if isinstance(r, Mapping) and isinstance(r.get("feedback_id"), str) and r["feedback_id"].strip()]
    for fid, count in Counter(ids).items():
        if count > 1:
            issues.append(ValidationIssue("duplicate_feedback_id", f"feedback_id '{fid}' muncul {count} kali; harus unik.", "error", feedback_id=fid, path="feedbacks"))
    if any(i.severity == "error" for i in issues):
        return PreparedBatch(batch, options, [], issues)
    redactor = privacy or PrivacyFilter()
    items = []
    for index, raw in enumerate(raw_items):
        item, item_issues = _prepare_item(raw, index, redactor)
        items.append(item)
        issues.extend(item_issues)
    return PreparedBatch(batch, options, items, issues)

def _prepare_item(raw: Any, index: int, redactor: PrivacyFilter):
    quality = QualityAssessment()
    feedback_id = f"__item_{index}"
    question_id = None
    text = ""
    language_hint = None
    unknown: list[str] = []
    if not isinstance(raw, Mapping):
        quality.add(QualityFlag.INVALID_ITEM)
    else:
        fid = raw.get("feedback_id")
        if isinstance(fid, str) and fid.strip(): feedback_id = fid.strip()
        else: quality.add(QualityFlag.MISSING_FEEDBACK_ID)
        qid = raw.get("question_id")
        if isinstance(qid, str) and qid.strip(): question_id = qid.strip()
        else: quality.add(QualityFlag.MISSING_QUESTION_ID)
        comment = raw.get("comment_text", _MISSING)
        if comment is _MISSING or comment is None: quality.add(QualityFlag.MISSING_COMMENT_TEXT)
        elif not isinstance(comment, str): quality.add(QualityFlag.INVALID_COMMENT_TYPE)
        else: text = comment
        if raw.get("submitted_at") is not None:
            try: _DATETIME.validate_python(raw["submitted_at"])
            except ValidationError: quality.add(QualityFlag.INVALID_SUBMITTED_AT)
        hint = raw.get("language_hint")
        if hint is not None:
            if isinstance(hint, str) and re.fullmatch(LANGUAGE_HINT_PATTERN, hint): language_hint = hint
            else: quality.add(QualityFlag.INVALID_LANGUAGE_HINT)
        unknown = [key for key in raw if key not in _KNOWN_ITEM_FIELDS]
    pre = preprocess(text)
    if QualityFlag.INVALID_ITEM not in quality.flags and not any(f in quality.flags for f in (QualityFlag.MISSING_COMMENT_TEXT, QualityFlag.INVALID_COMMENT_TYPE)):
        for flag in assess_text(text, pre.normalized).flags: quality.add(flag)
    analysis_text = ""
    redaction = None
    if quality.analyzable:
        redaction = redactor.redact(pre.normalized)
        if redaction.redacted: quality.add(QualityFlag.PII_REDACTED)
        if contains_pii(redaction.text): quality.add(QualityFlag.PRIVACY_BLOCKED)
        else: analysis_text = redaction.text
    analyzable = quality.analyzable
    item = PreparedFeedback(
        index=index, feedback_id=feedback_id, question_id=question_id,
        original_text=text, normalized_text=pre.normalized,
        analysis_text=analysis_text if analyzable else "",
        quality_flags=[f.value for f in quality.flags], analyzable=analyzable,
        language_hint=language_hint, redaction_kinds=redaction.kinds if redaction else [],
        preprocessing=pre, redaction=redaction,
    )
    issues = [ValidationIssue(flag.value, _ITEM_MESSAGES[flag], "warning", feedback_id=feedback_id)
              for flag in quality.flags if flag in _ITEM_MESSAGES]
    issues.extend(ValidationIssue("unknown_field", f"Field tidak dikenal diabaikan: {key}", "warning",
                                  feedback_id=feedback_id, path=str(key)) for key in unknown)
    return item, issues
