"""Orkestrasi satu panggilan LLM per komentar dan penguraian mention."""
from __future__ import annotations
import time
from dataclasses import dataclass
from app.task_a.config.labels import FeedbackAnalysisStatus as Status
from app.task_a.config.taxonomy import TAXONOMY_VERSION
from app.task_a.config.versions import LLM_MAX_COMPLETION_TOKENS, LLM_TEMPERATURE, PIPELINE_VERSION, PROMPT_VERSION
from app.task_a.schemas.requests import AnalysisOptions
from app.task_a.schemas.responses import AspectMention, BatchStats, EvidenceItem, ModelMetadata, Warning as ResponseWarning
from app.task_a.services.analysis_types import BatchAnalysis, ItemAnalysis, LLMOutputError, ParseIssue
from app.task_a.services.aspect_extractor import build_messages, parse_llm_json, prompt_fingerprint, resolve_aspect
from app.task_a.services.feedback_validator import PreparedBatch, PreparedFeedback
from app.task_a.services.llm_gateway import PROVIDER, GroqGateway, LLMError, LLMGateway
from app.task_a.services.quality_flags import QualityFlag
from app.task_a.services.reason_extractor import extract_reason
from app.task_a.services.sentiment_classifier import classify_sentiment

@dataclass
class _Draft:
    aspect_code: str
    is_proposed: bool
    label: str | None
    sentiment: object
    strength: object
    reason_type: object
    reason_category: str
    description: str | None
    quotes: list[str]
    @property
    def key(self):
        return (self.aspect_code, (self.label or "").lower(), self.sentiment, self.reason_category)

class AnalysisService:
    def __init__(self, gateway: LLMGateway | None = None, *, pipeline_version: str = PIPELINE_VERSION,
                 prompt_version: str = PROMPT_VERSION):
        self._gateway = gateway or GroqGateway()
        self.pipeline_version = pipeline_version
        self.prompt_version = prompt_version

    def analyze_item(self, item: PreparedFeedback, options: AnalysisOptions) -> ItemAnalysis:
        flags = list(item.quality_flags)
        if not item.analyzable:
            return ItemAnalysis(item.feedback_id, Status.SKIPPED.value, quality_flags=flags)
        messages = build_messages(item.analysis_text, max_aspects=options.max_aspects_per_feedback,
                                  allow_proposed=options.allow_proposed_aspects, language_hint=item.language_hint)
        try:
            response = self._gateway.complete(messages, temperature=LLM_TEMPERATURE,
                max_completion_tokens=LLM_MAX_COMPLETION_TOKENS, json_mode=True)
        except LLMError as exc:
            return ItemAnalysis(item.feedback_id, Status.FAILED.value,
                quality_flags=_add(flags, QualityFlag.LLM_ERROR), error=str(exc)[:200])
        meta = dict(model_id=response.model_id, latency_ms=response.latency_ms, usage=response.usage)
        try:
            raw_mentions = parse_llm_json(response.content)
        except LLMOutputError as exc:
            return ItemAnalysis(item.feedback_id, Status.FAILED.value,
                quality_flags=_add(flags, QualityFlag.INVALID_LLM_OUTPUT), error=str(exc), **meta)
        mentions, issues = self._build_mentions(item.feedback_id, raw_mentions, options)
        if raw_mentions and not mentions:
            return ItemAnalysis(item.feedback_id, Status.FAILED.value, issues=issues,
                quality_flags=_add(flags, QualityFlag.INVALID_LLM_OUTPUT), error="Semua mention ditolak.", **meta)
        if not raw_mentions:
            flags = _add(flags, QualityFlag.NO_ASPECT_FOUND)
        return ItemAnalysis(item.feedback_id, Status.ANALYZED.value, mentions=mentions,
            quality_flags=flags, issues=issues, **meta)

    def _build_mentions(self, feedback_id: str, raw_mentions: list, options: AnalysisOptions):
        issues: list[ParseIssue] = []
        drafts: dict[tuple, _Draft] = {}
        for index, raw in enumerate(raw_mentions):
            if not isinstance(raw, dict):
                issues.append(ParseIssue("invalid_mention", "Mention bukan objek.", index))
                continue
            aspect = resolve_aspect(raw, allow_proposed=options.allow_proposed_aspects, index=index)
            sent = classify_sentiment(raw, index=index)
            reason = extract_reason(raw, index=index)
            issues.extend(aspect.issues + sent.issues + reason.issues)
            if aspect.aspect_code is None or sent.sentiment is None or reason.reason_type is None or reason.reason_category is None:
                issues.append(ParseIssue("mention_rejected", "Mention ditolak karena field wajib tidak valid.", index))
                continue
            draft = _Draft(aspect.aspect_code, aspect.is_proposed, aspect.proposed_label,
                sent.sentiment, sent.strength, reason.reason_type, reason.reason_category, reason.description, reason.quotes)
            existing = drafts.get(draft.key)
            if existing is None:
                drafts[draft.key] = draft
            else:
                existing.quotes = list(dict.fromkeys(existing.quotes + draft.quotes))[:3]
                issues.append(ParseIssue("duplicate_mention_merged", "Mention duplikat digabung.", index))
        kept = list(drafts.values())
        if len(kept) > options.max_aspects_per_feedback:
            issues.append(ParseIssue("mentions_truncated", "Jumlah mention melebihi batas opsi.", None))
            kept = kept[:options.max_aspects_per_feedback]
        mentions = [AspectMention(mention_id=f"{feedback_id}-m{n}", feedback_id=feedback_id,
            aspect_code=d.aspect_code, is_proposed_aspect=d.is_proposed, proposed_aspect_label=d.label,
            sentiment=d.sentiment, sentiment_strength=d.strength, reason_type=d.reason_type,
            reason_category=d.reason_category, reason_description=d.description,
            evidence=[EvidenceItem(quote=q, feedback_id=feedback_id) for q in d.quotes])
            for n, d in enumerate(kept, 1)]
        return mentions, issues

    def analyze_batch(self, prepared: PreparedBatch) -> BatchAnalysis:
        if not prepared.ok:
            raise ValueError("Batch tidak valid; tangani prepared.errors sebelum analisis.")
        options = prepared.options or AnalysisOptions()
        started = time.perf_counter()
        items = [self.analyze_item(item, options) for item in prepared.items]
        stats = BatchStats(total_feedbacks=len(items),
            analyzed_feedbacks=sum(i.status == Status.ANALYZED.value for i in items),
            skipped_feedbacks=sum(i.status == Status.SKIPPED.value for i in items),
            failed_feedbacks=sum(i.status == Status.FAILED.value for i in items),
            total_mentions=sum(len(i.mentions) for i in items),
            processing_time_ms=int((time.perf_counter() - started) * 1000))
        seen = sorted({i.model_id for i in items if i.model_id})
        metadata = ModelMetadata(provider=PROVIDER, model_id=",".join(seen),
            prompt_version=self.prompt_version, pipeline_version=self.pipeline_version,
            schema_version=prepared.batch.schema_version if prepared.batch else "1.0",
            taxonomy_version=TAXONOMY_VERSION,
            decoding={"temperature": LLM_TEMPERATURE, "json_mode": True,
                "max_completion_tokens": LLM_MAX_COMPLETION_TOKENS,
                "prompt_fingerprint": prompt_fingerprint(self.prompt_version)}) if seen else None
        warnings = [ResponseWarning(code=w.code, message=w.message, feedback_id=w.feedback_id) for w in prepared.warnings]
        for item in items:
            warnings.extend(ResponseWarning(code=p.code, message=p.message, feedback_id=item.feedback_id) for p in item.issues)
        return BatchAnalysis(items=items, stats=stats, metadata=metadata, warnings=warnings)

def _add(flags: list[str], flag: QualityFlag) -> list[str]:
    return flags if flag.value in flags else [*flags, flag.value]
