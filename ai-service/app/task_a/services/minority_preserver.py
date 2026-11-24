"""Preserve minority/dissenting views as cross-references, not merged away."""
from app.task_a.config.labels import ReasonType
from app.task_a.schemas.responses import ConsolidatedFinding,MinorityView
from app.task_a.services.finding_grouper import Group,MentionRecord
MAX_VIEW_SUMMARY=300
_OPPOSITE={ReasonType.DISSATISFACTION_DRIVER.value:ReasonType.SATISFACTION_DRIVER.value,
    ReasonType.SATISFACTION_DRIVER.value:ReasonType.DISSATISFACTION_DRIVER.value}
def _view_text(record:MentionRecord)->str:
    m=record.mention;text=(m.reason_description or "").strip()
    if not text:text=next((e.quote for e in m.evidence if e.status.value=="valid"),"")
    return text[:MAX_VIEW_SUMMARY]
def attach_minority_views(pairs:list[tuple[ConsolidatedFinding,Group]])->list[ConsolidatedFinding]:
    out=[]
    for finding,group in pairs:
        opposite=_OPPOSITE.get(group.reason_type);views=[]
        if opposite:
            supporters=set(finding.supporting_feedback_ids);dissent={}
            for _,other in pairs:
                if other is group or other.aspect_identity!=group.aspect_identity or other.reason_type!=opposite:continue
                for record in other.records:
                    if record.feedback_id not in supporters:dissent.setdefault(record.feedback_id,record)
            if dissent and len(dissent)<finding.unique_feedback_count:
                for record in sorted(dissent.values(),key=lambda r:(r.order,r.mention.mention_id)):
                    views.append(MinorityView(feedback_id=record.feedback_id,sentiment=record.mention.sentiment,summary=_view_text(record)))
        out.append(finding.model_copy(update={"minority_views":views}) if views else finding)
    return out
def every_group_is_kept(groups:list[Group],findings:list[ConsolidatedFinding])->bool:
    return len(groups)==len(findings) and sum(len(g.records) for g in groups)==sum(f.mention_count for f in findings)
