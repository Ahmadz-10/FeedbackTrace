"""Deterministic groups from verified mentions; no semantic clustering by default."""
from dataclasses import dataclass,field
from app.task_a.config.labels import EvidenceStatus
from app.task_a.schemas.responses import AspectMention
from app.task_a.services.analysis_types import ItemAnalysis
GroupKey=tuple[str,str,str,str]
@dataclass(frozen=True)
class MentionRecord:
    mention:AspectMention
    feedback_id:str
    order:int
@dataclass
class Group:
    key:GroupKey
    records:list[MentionRecord]=field(default_factory=list)
    @property
    def aspect_code(self):return self.key[0]
    @property
    def aspect_label(self):return self.key[1]
    @property
    def reason_category(self):return self.key[2]
    @property
    def reason_type(self):return self.key[3]
    @property
    def aspect_identity(self):return self.key[0],self.key[1]
def is_verified(mention:AspectMention)->bool:
    return any(e.status==EvidenceStatus.VALID and e.start_char is not None for e in mention.evidence)
def group_key(m:AspectMention)->GroupKey:
    label=(m.proposed_aspect_label or "").strip().lower() if m.is_proposed_aspect else ""
    return m.aspect_code,label,m.reason_category,m.reason_type.value
def collect_records(items:list[ItemAnalysis],batch_order:dict[str,int]):
    records=[];excluded=[]
    for item in items:
        for mention in item.mentions:
            if is_verified(mention):records.append(MentionRecord(mention,mention.feedback_id,batch_order.get(mention.feedback_id,len(batch_order))))
            else:excluded.append(mention.mention_id)
    records.sort(key=lambda r:(r.order,r.mention.mention_id))
    return records,excluded
def group_mentions(records:list[MentionRecord])->list[Group]:
    groups={}
    for record in records:
        key=group_key(record.mention)
        groups.setdefault(key,Group(key)).records.append(record)
    return list(groups.values())
