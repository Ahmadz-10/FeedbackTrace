"""Flag kualitas komentar dan hasil analisis."""
from dataclasses import dataclass, field
from enum import StrEnum

class QualityFlag(StrEnum):
    EMPTY_COMMENT = "empty_comment"
    NO_TEXT_CONTENT = "no_text_content"
    VERY_SHORT = "very_short"
    TOO_LONG = "too_long"
    INVALID_ITEM = "invalid_item"
    MISSING_FEEDBACK_ID = "missing_feedback_id"
    MISSING_QUESTION_ID = "missing_question_id"
    MISSING_COMMENT_TEXT = "missing_comment_text"
    INVALID_COMMENT_TYPE = "invalid_comment_type"
    INVALID_SUBMITTED_AT = "invalid_submitted_at"
    INVALID_LANGUAGE_HINT = "invalid_language_hint"
    PII_REDACTED = "pii_redacted"
    NO_ASPECT_FOUND = "no_aspect_found"
    LLM_ERROR = "llm_error"
    INVALID_LLM_OUTPUT = "invalid_llm_output"
    PRIVACY_BLOCKED = "privacy_blocked"

BLOCKING_FLAGS = frozenset({
    QualityFlag.EMPTY_COMMENT, QualityFlag.NO_TEXT_CONTENT, QualityFlag.VERY_SHORT,
    QualityFlag.TOO_LONG, QualityFlag.INVALID_ITEM, QualityFlag.MISSING_FEEDBACK_ID,
    QualityFlag.MISSING_QUESTION_ID, QualityFlag.MISSING_COMMENT_TEXT,
    QualityFlag.INVALID_COMMENT_TYPE, QualityFlag.PRIVACY_BLOCKED,
})
MIN_LETTERS = 4
MAX_ANALYZABLE_CHARS = 5000

@dataclass
class QualityAssessment:
    flags: list[QualityFlag] = field(default_factory=list)
    @property
    def analyzable(self) -> bool:
        return not any(flag in BLOCKING_FLAGS for flag in self.flags)
    def add(self, flag: QualityFlag) -> None:
        if flag not in self.flags:
            self.flags.append(flag)

def assess_text(original: str, normalized: str) -> QualityAssessment:
    result = QualityAssessment()
    if not original.strip() or not normalized:
        result.add(QualityFlag.EMPTY_COMMENT)
        return result
    letters = sum(ch.isalpha() for ch in normalized)
    if letters == 0:
        result.add(QualityFlag.NO_TEXT_CONTENT)
    elif letters < MIN_LETTERS:
        result.add(QualityFlag.VERY_SHORT)
    if len(original) > MAX_ANALYZABLE_CHARS:
        result.add(QualityFlag.TOO_LONG)
    return result
