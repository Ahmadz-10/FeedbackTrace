"""Hitung angka dan ID temuan di Python, bukan dengan meminta LLM menghitung."""
import hashlib
from app.task_a.config.labels import FindingType,ReasonType,Sentiment
from app.task_a.schemas.responses import ConsolidatedFinding,SentimentDistribution
from app.task_a.services.finding_grouper import Group,GroupKey
def finding_id_for(batch_id:str,key:GroupKey)->str:
    return "F-"+hashlib.sha256("\x1f".join((batch_id,*key)).encode()).hexdigest()[:12]
def finding_type_for(group:Group)->FindingType:
    if any(r.mention.sentiment==Sentiment.MIXED for r in group.records):return FindingType.MIXED
    return {ReasonType.SATISFACTION_DRIVER.value:FindingType.SERVICE_STRENGTH,
        ReasonType.DISSATISFACTION_DRIVER.value:FindingType.SERVICE_WEAKNESS}.get(group.reason_type,FindingType.OBSERVATION)
def supporting_feedback_ids(group:Group)->list[str]:
    seen={}
    for record in group.records:seen.setdefault(record.feedback_id,None)
    return list(seen)
def sentiment_distribution(group:Group)->SentimentDistribution:
    dist=SentimentDistribution()
    for record in group.records:
        name=record.mention.sentiment.value
        setattr(dist,name,getattr(dist,name)+1)
    return dist
def build_finding(batch_id:str,group:Group,*,title:str,summary:str,quality_flags:list[str]|None=None)->ConsolidatedFinding:
    ids=supporting_feedback_ids(group)
    return ConsolidatedFinding(finding_id=finding_id_for(batch_id,group.key),aspect_code=group.aspect_code,
        reason_category=group.reason_category,finding_type=finding_type_for(group),issue_title=title,issue_summary=summary,
        unique_feedback_count=len(ids),mention_count=len(group.records),supporting_feedback_ids=ids,
        sentiment_distribution=sentiment_distribution(group),single_feedback_support=len(ids)==1,
        quality_flags=list(quality_flags or []))
def sort_findings(findings:list[ConsolidatedFinding])->list[ConsolidatedFinding]:
    return sorted(findings,key=lambda f:(-f.unique_feedback_count,-f.mention_count,f.aspect_code,f.reason_category,f.finding_id))
