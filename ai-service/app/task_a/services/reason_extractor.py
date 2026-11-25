"""Ekstrak alasan dan kandidat kutipan; verifikasi kutipan dilakukan di Segment 05."""
from dataclasses import dataclass, field
from typing import Any
from app.task_a.config import reason_categories as rc
from app.task_a.config.labels import ReasonType
from app.task_a.services.analysis_types import ParseIssue
from app.task_a.services.aspect_extractor import normalize_code
MAX_DESCRIPTION_LENGTH = 300
MAX_QUOTE_LENGTH = 500
MAX_QUOTES = 3
FALLBACK_CATEGORY = "other_reason"
_TYPE_ALIASES = {"satisfaction":"satisfaction_driver","dissatisfaction":"dissatisfaction_driver","neutral":"neutral_observation"}
@dataclass(frozen=True)
class ReasonResult:
    reason_type: ReasonType | None
    reason_category: str | None
    description: str | None
    quotes: list[str] = field(default_factory=list)
    issues: list[ParseIssue] = field(default_factory=list)
def extract_reason(raw: dict, *, index: int) -> ReasonResult:
    issues = []
    kind = _TYPE_ALIASES.get(normalize_code(raw.get("reason_type")), normalize_code(raw.get("reason_type")))
    try:
        reason_type = ReasonType(kind)
    except ValueError:
        reason_type = None
        issues.append(ParseIssue("invalid_reason_type", "reason_type tidak dikenal.", index))
    category = normalize_code(raw.get("reason_category")) or None
    if category is None:
        issues.append(ParseIssue("missing_reason_category", "reason_category hilang.", index))
    elif not rc.is_valid_reason_category(category):
        issues.append(ParseIssue("unknown_reason_category", f"reason_category '{category[:40]}' tidak dikenal; fallback ke other_reason.", index))
        category = FALLBACK_CATEGORY
    description = raw.get("reason_description")
    description = description.strip() if isinstance(description, str) and description.strip() else None
    if description and len(description) > MAX_DESCRIPTION_LENGTH:
        description = description[:MAX_DESCRIPTION_LENGTH].rstrip()
        issues.append(ParseIssue("description_truncated", "reason_description dipotong.", index))
    raw_quotes = raw.get("evidence_quotes") or raw.get("evidence_quote")
    if isinstance(raw_quotes, str):
        raw_quotes = [raw_quotes]
    quotes = []
    if isinstance(raw_quotes, list):
        for quote in raw_quotes:
            if not isinstance(quote, str) or not quote.strip():
                continue
            quote = quote.strip()
            if len(quote) > MAX_QUOTE_LENGTH:
                issues.append(ParseIssue("quote_too_long", "Kutipan terlalu panjang; dibuang.", index))
            elif quote not in quotes:
                quotes.append(quote)
    if not quotes:
        issues.append(ParseIssue("no_evidence_quote", "Tidak ada kandidat kutipan bukti.", index))
    return ReasonResult(reason_type, category, description, quotes[:MAX_QUOTES], issues)
