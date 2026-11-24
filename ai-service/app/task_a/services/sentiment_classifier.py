"""Normalisasi dan validasi sentimen per aspect mention."""
from dataclasses import dataclass
from typing import Any
from app.task_a.config.labels import Sentiment, SentimentStrength
from app.task_a.services.analysis_types import ParseIssue

_SENTIMENT_ALIASES = {"positif":"positive","pos":"positive","negatif":"negative","neg":"negative","netral":"neutral","campuran":"mixed","mix":"mixed"}
_STRENGTH_ALIASES = {"rendah":"low","lemah":"low","weak":"low","sedang":"medium","moderate":"medium","tinggi":"high","kuat":"high","strong":"high"}
@dataclass(frozen=True)
class SentimentResult:
    sentiment: Sentiment | None
    strength: SentimentStrength | None
    issues: list[ParseIssue]
def _norm(value: Any) -> str:
    return value.strip().lower() if isinstance(value, str) else ""
def classify_sentiment(raw: dict, *, index: int) -> SentimentResult:
    issues = []
    value = _SENTIMENT_ALIASES.get(_norm(raw.get("sentiment")), _norm(raw.get("sentiment")))
    try:
        sentiment = Sentiment(value)
    except ValueError:
        sentiment = None
        issues.append(ParseIssue("invalid_sentiment", "sentiment bukan positive/negative/neutral/mixed.", index))
    strength = None
    value = _norm(raw.get("sentiment_strength"))
    if value:
        value = _STRENGTH_ALIASES.get(value, value)
        try:
            strength = SentimentStrength(value)
        except ValueError:
            issues.append(ParseIssue("invalid_sentiment_strength", "sentiment_strength tidak dikenal; diabaikan.", index))
    return SentimentResult(sentiment, strength, issues)
