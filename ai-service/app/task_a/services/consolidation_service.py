"""Orkestrasi konsolidasi: mention terverifikasi -> kelompok -> temuan (+ minoritas + ringkasan).

Alur: finding_grouper -> finding_summarizer (teks) -> finding_aggregator (angka) -> minority_preserver
-> validate_finding_references. Angka, ID pendukung, dan distribusi sentimen selalu dihitung kode.
"""
from __future__ import annotations

from dataclasses import dataclass, field

from app.task_a.config.versions import SUMMARY_MAX_LLM_CALLS, SUMMARY_PROMPT_VERSION
from app.task_a.schemas.responses import ConsolidatedFinding, Warning as ResponseWarning
from app.task_a.services.analysis_types import BatchAnalysis
from app.task_a.services.feedback_validator import PreparedBatch
from app.task_a.services.finding_aggregator import build_finding, finding_type_for, sort_findings
from app.task_a.services.finding_grouper import collect_records, group_mentions
from app.task_a.services.finding_summarizer import FindingSummarizer, summary_prompt_fingerprint
from app.task_a.services.llm_gateway import GroqGateway, LLMGateway
from app.task_a.services.minority_preserver import attach_minority_views
from app.task_a.services.reference_validator import validate_finding_references


@dataclass
class ConsolidationResult:
    findings: list[ConsolidatedFinding] = field(default_factory=list)
    warnings: list[ResponseWarning] = field(default_factory=list)
    summary_model_ids: list[str] = field(default_factory=list)
    mention_ids_by_finding: dict[str, list[str]] = field(default_factory=dict)


def consolidate(
    analysis: BatchAnalysis, prepared: PreparedBatch, *,
    gateway: LLMGateway | None = None, use_llm_summaries: bool = True,
    max_summary_calls: int = SUMMARY_MAX_LLM_CALLS,
) -> ConsolidationResult:
    result = ConsolidationResult()
    options = prepared.options
    if options is not None and not options.enable_consolidation:
        return result
    if options is not None and options.use_embeddings:
        result.warnings.append(ResponseWarning(
            code="embeddings_not_enabled",
            message="use_embeddings diminta, tetapi penggabungan semantik belum lolos evaluasi; "
                    "pengelompokan deterministik dipakai."))

    batch_id = prepared.batch.batch_id if prepared.batch else "batch"
    order = {it.feedback_id: i for i, it in enumerate(prepared.items)}
    records, excluded = collect_records(analysis.items, order)
    for mention_id in excluded:
        result.warnings.append(ResponseWarning(
            code="unverified_mention_excluded",
            message=f"Mention '{mention_id}' tanpa bukti valid tidak dimasukkan ke temuan."))

    groups = group_mentions(records)
    summarizer_gateway = (gateway or GroqGateway()) if (use_llm_summaries and groups) else None
    summarizer = FindingSummarizer(summarizer_gateway, max_calls=max_summary_calls)

    pairs = []
    models: set[str] = set()
    for group in groups:
        summary = summarizer.summarize(group, finding_type_for(group))
        if summary.model_id:
            models.add(summary.model_id)
        if summary.source == "template" and summarizer_gateway is not None:
            result.warnings.append(ResponseWarning(
                code="summary_fallback",
                message=f"Ringkasan templat dipakai untuk temuan {group.aspect_code}/{group.reason_category}"
                        f" ({summary.reject_reason or 'tanpa alasan'})."))
        flags = summary.flags if summarizer_gateway is not None else []
        finding = build_finding(batch_id, group, title=summary.title, summary=summary.summary,
                                quality_flags=flags)
        pairs.append((finding, group))

    findings = sort_findings(attach_minority_views(pairs))
    for issue in validate_finding_references(findings, set(order)):
        result.warnings.append(ResponseWarning(code=issue.code, message=issue.message))
    result.findings = findings
    for finding, group in pairs:
        result.mention_ids_by_finding[finding.finding_id] = [r.mention.mention_id for r in group.records]
    result.summary_model_ids = sorted(models)
    return result


def attach_to_analysis(analysis: BatchAnalysis, result: ConsolidationResult) -> None:
    """Perbarui statistik, peringatan, dan metadata batch dengan hasil konsolidasi."""
    analysis.stats.total_findings = len(result.findings)
    analysis.warnings.extend(result.warnings)
    if analysis.metadata is not None and (result.summary_model_ids or result.findings):
        analysis.metadata.decoding.update({
            "summary_prompt_version": SUMMARY_PROMPT_VERSION,
            "summary_prompt_fingerprint": summary_prompt_fingerprint(),
            "summary_model_ids": result.summary_model_ids,
        })
