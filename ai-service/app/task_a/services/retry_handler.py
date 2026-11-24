"""Full validation, bounded retries, and best-attempt selection for Task A."""
from __future__ import annotations
import time
from dataclasses import dataclass,field,replace
from typing import Callable
from app.task_a.config.labels import EvidenceStatus,FeedbackAnalysisStatus as Status,JobStatus
from app.task_a.schemas.requests import AnalysisOptions
from app.task_a.schemas.responses import AspectMention,Warning as ResponseWarning
from app.task_a.services.analysis_service import AnalysisService
from app.task_a.services.analysis_types import BatchAnalysis,ItemAnalysis,MentionIssue,ParseIssue
from app.task_a.services.evidence_validator import verify_mention_evidence
from app.task_a.services.feedback_validator import PreparedBatch,PreparedFeedback
from app.task_a.services.quality_flags import QualityFlag
from app.task_a.services.reference_validator import check_mention_references,validate_result_coverage
from app.task_a.services.schema_validator import validate_mention
MAX_NOTES=6
@dataclass
class PipelineResult:
    analysis:BatchAnalysis
    job_status:JobStatus
@dataclass
class _Attempt:
    result:ItemAnalysis
    accepted:list[AspectMention]=field(default_factory=list)
    issues:list[MentionIssue]=field(default_factory=list)
    rejected:int=0
    @property
    def score(self):return len(self.accepted),-self.rejected
class ValidatedPipeline:
    def __init__(self,service:AnalysisService|None=None,*,retry_delay_seconds:float=0.0,sleep:Callable[[float],None]=time.sleep):
        self.service=service or AnalysisService();self._delay=retry_delay_seconds;self._sleep=sleep
    def run_item(self,item:PreparedFeedback,options:AnalysisOptions,known_ids:set[str])->ItemAnalysis:
        max_attempts=1+options.max_retries;notes=None;best=None;last_failed=None;attempts=0
        for attempt in range(1,max_attempts+1):
            attempts=attempt;res=self.service.analyze_item(item,options,retry_notes=notes)
            if res.status==Status.SKIPPED.value:return replace(res,attempts=0)
            if res.status==Status.FAILED.value:
                last_failed=res
                if not res.retryable:break
                notes=[f"Your output could not be used: {res.error}"]
            else:
                current=self._validate(res,item,known_ids)
                if best is None or current.score>best.score:best=current
                if current.rejected==0:break
                notes=[f"{i.code}: {i.message}" for i in current.issues if i.is_error or i.code in {"quote_not_found","offset_mismatch"}][:MAX_NOTES]
            if attempt<max_attempts and self._delay:self._sleep(self._delay)
        return self._finalize(item,best,last_failed,attempts)
    def _finalize(self,item,best,last_failed,attempts):
        if best is None:return replace(last_failed,attempts=attempts)
        res=best.result;flags=list(dict.fromkeys(item.quality_flags+res.quality_flags))
        issues=list(res.issues)+[ParseIssue(i.code,i.message) for i in best.issues]
        if attempts>1:issues.append(ParseIssue("retried",f"Hasil akhir diperoleh setelah {attempts} percobaan."))
        if best.rejected:flags=_add(flags,QualityFlag.UNSUPPORTED_MENTION_DROPPED)
        if not best.accepted and best.rejected:
            return replace(res,status=Status.FAILED.value,mentions=[],issues=issues,attempts=attempts,
                quality_flags=_add(flags,QualityFlag.VALIDATION_FAILED),error=f"Semua mention gagal validasi setelah {attempts} percobaan.")
        return replace(res,mentions=best.accepted,issues=issues,quality_flags=flags,attempts=attempts)
    def _validate(self,res,item,known_ids):
        attempt=_Attempt(result=res)
        for mention in res.mentions:
            problems=validate_mention(mention)+check_mention_references(mention,owner_id=item.feedback_id,known_ids=known_ids)
            if any(p.is_error for p in problems):
                attempt.issues+=problems;attempt.rejected+=1;continue
            verified,evidence_issues=verify_mention_evidence(mention,item)
            attempt.issues += [p for p in problems if not p.is_error]+evidence_issues
            if not any(e.status==EvidenceStatus.VALID for e in verified.evidence):
                attempt.issues.append(MentionIssue("no_valid_evidence","Mention tidak memiliki kutipan valid; dibuang.",mention.mention_id))
                attempt.rejected+=1;continue
            attempt.accepted.append(verified)
        return attempt
    def run_batch(self,prepared:PreparedBatch)->PipelineResult:
        if not prepared.ok:raise ValueError("Batch tidak valid; tangani prepared.errors.")
        options=prepared.options or AnalysisOptions();known={i.feedback_id for i in prepared.items};start=time.perf_counter()
        items=[self.run_item(item,options,known) for item in prepared.items]
        analysis=self.service.assemble_batch(prepared,items,int((time.perf_counter()-start)*1000))
        for issue in validate_result_coverage([i.feedback_id for i in prepared.items],analysis.results):
            analysis.warnings.append(ResponseWarning(code=issue.code,message=issue.message))
        return PipelineResult(analysis,_job_status(items))
def _job_status(items):
    attempted=[i for i in items if i.status!=Status.SKIPPED.value]
    failed=[i for i in attempted if i.status==Status.FAILED.value]
    if not failed:return JobStatus.COMPLETED
    if len(failed)==len(attempted):return JobStatus.FAILED
    return JobStatus.PARTIALLY_COMPLETED
def run_validated_batch(prepared:PreparedBatch,service:AnalysisService|None=None)->PipelineResult:
    return ValidatedPipeline(service).run_batch(prepared)
def _add(flags:list[str],flag:QualityFlag)->list[str]:
    return flags if flag.value in flags else [*flags,flag.value]
