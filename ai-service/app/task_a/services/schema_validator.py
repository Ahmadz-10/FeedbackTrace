"""Validate taxonomy membership and consistency between mention fields."""
from pydantic import ValidationError
from typing import Any
from app.task_a.config import reason_categories as rc,taxonomy
from app.task_a.config.labels import ReasonType,Sentiment
from app.task_a.schemas.responses import AnalyzeResponse,AspectMention
from app.task_a.services.analysis_types import MentionIssue
_ALLOWED={Sentiment.POSITIVE:{ReasonType.SATISFACTION_DRIVER},Sentiment.NEGATIVE:{ReasonType.DISSATISFACTION_DRIVER},Sentiment.NEUTRAL:{ReasonType.NEUTRAL_OBSERVATION},Sentiment.MIXED:{ReasonType.SATISFACTION_DRIVER,ReasonType.DISSATISFACTION_DRIVER}}
def validate_mention(m:AspectMention)->list[MentionIssue]:
    issues=[]
    def err(code,msg):issues.append(MentionIssue(code,msg,m.mention_id))
    if not m.mention_id.strip():err("missing_mention_id","mention_id kosong.")
    if not m.feedback_id.strip():err("missing_feedback_id","feedback_id kosong.")
    if not taxonomy.is_valid_aspect(m.aspect_code):err("unknown_aspect_code",f"aspect_code '{m.aspect_code[:40]}' tidak ada.")
    if m.is_proposed_aspect:
        if m.aspect_code!="other":err("proposal_requires_other","Usulan aspek harus memakai 'other'.")
        if not (m.proposed_aspect_label or "").strip():err("proposal_without_label","Usulan aspek tanpa label.")
    elif m.proposed_aspect_label:err("label_without_proposal","Label usulan terisi padahal bukan usulan.")
    if not rc.is_valid_reason_category(m.reason_category):err("unknown_reason_category",f"reason_category '{m.reason_category[:40]}' tidak dikenal.")
    elif not rc.is_type_allowed(m.reason_category,m.reason_type):err("reason_type_not_allowed_for_category","Jenis alasan tidak diizinkan untuk kategori.")
    if m.reason_type not in _ALLOWED[m.sentiment]:err("sentiment_reason_mismatch","Sentimen dan jenis alasan tidak selaras.")
    if not (m.reason_description or "").strip():issues.append(MentionIssue("missing_reason_description","reason_description kosong.",m.mention_id,"warning"))
    return issues
def validate_mentions(mentions:list[AspectMention]):
    accepted=[];rejected=[]
    for m in mentions:
        found=validate_mention(m)
        (rejected if any(i.is_error for i in found) else accepted).append((m,found) if any(i.is_error for i in found) else m)
    return accepted,rejected
def validate_response_payload(data:Any)->list[MentionIssue]:
    try:AnalyzeResponse.model_validate(data)
    except ValidationError as exc:
        return [MentionIssue("response_schema_error",f"{'.'.join(str(p) for p in e['loc'])}: {e['msg']}") for e in exc.errors()]
    return []
