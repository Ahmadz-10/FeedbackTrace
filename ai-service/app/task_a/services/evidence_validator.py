"""Verify candidate quotes against original feedback text and its index mapping."""
from __future__ import annotations
import re
from dataclasses import dataclass
from app.task_a.config.labels import EvidenceStatus
from app.task_a.schemas.responses import AspectMention,EvidenceItem
from app.task_a.services.analysis_types import MentionIssue
from app.task_a.services.feedback_validator import PreparedFeedback
from app.task_a.services.preprocessor import preprocess
MIN_QUOTE_ALNUM=3
_PLACEHOLDER=re.compile(r"\[(?:NAME|EMAIL|PHONE|STUDENT_ID|NATIONAL_ID|URL)\]")
_OUTER_QUOTES='"\'“”‘’`'
_EDGE_PUNCT=" \t\r\n.,;:!?…"
@dataclass(frozen=True)
class EvidenceCheck:
    status:EvidenceStatus
    quote:str
    start_char:int|None=None
    end_char:int|None=None
    method:str|None=None
    reason:str|None=None
def _alnum(s:str)->int:return sum(ch.isalnum() for ch in s)
def _variants(q:str)->list[str]:
    out=[q]
    for c in (q.strip(_OUTER_QUOTES+" \t\r\n"),q.strip(_EDGE_PUNCT+_OUTER_QUOTES)):
        if c and c not in out:out.append(c)
    return out
def _find(text:str,q:str):
    idx=text.find(q)
    if idx!=-1:return idx,idx+len(q),"exact"
    for cand in _variants(q):
        if _alnum(cand)<MIN_QUOTE_ALNUM:continue
        idx=text.find(cand)
        if idx!=-1:return idx,idx+len(cand),"normalized"
        m=re.search(r"\s+".join(re.escape(t) for t in cand.split()),text,re.I)
        if m:return m.start(),m.end(),"normalized"
    return None
def locate_quote(quote:object,item:PreparedFeedback)->EvidenceCheck:
    def invalid(reason):return EvidenceCheck(EvidenceStatus.INVALID,quote if isinstance(quote,str) else "",reason=reason)
    if not isinstance(quote,str) or not quote.strip():return invalid("empty_quote")
    q=quote.strip()
    if _alnum(q)<MIN_QUOTE_ALNUM:return invalid("quote_too_short")
    if not item.analysis_text or item.preprocessing is None:return invalid("no_source_text")
    found=_find(item.analysis_text,q)
    if found is None:return invalid("quote_not_found")
    s,e,method=found; snapped=item.analysis_text[s:e]
    try:a,b=item.analysis_span_to_original(s,e)
    except ValueError:return invalid("offset_mismatch")
    original=item.original_text[a:b]
    consistent=bool(original.strip()) if _PLACEHOLDER.search(snapped) else preprocess(original).normalized==snapped
    if not consistent:return invalid("offset_mismatch")
    return EvidenceCheck(EvidenceStatus.VALID,snapped,a,b,method)
_MESSAGES={"empty_quote":"kutipan kosong.","quote_too_short":"kutipan terlalu pendek.","quote_not_found":"kutipan tidak ditemukan pada komentar.","offset_mismatch":"kutipan tidak cocok dengan teks asli.","no_source_text":"tidak ada teks sumber."}
def verify_mention_evidence(mention:AspectMention,item:PreparedFeedback):
    valid=[];invalid=[];issues=[];seen=set()
    for ev in mention.evidence:
        c=locate_quote(ev.quote,item)
        if c.status==EvidenceStatus.VALID:
            span=(c.start_char,c.end_char)
            if span in seen:continue
            seen.add(span)
            valid.append(EvidenceItem(quote=c.quote,feedback_id=item.feedback_id,start_char=c.start_char,end_char=c.end_char,status=EvidenceStatus.VALID))
        else:
            invalid.append(EvidenceItem(quote=ev.quote,feedback_id=item.feedback_id,status=EvidenceStatus.INVALID))
            preview=ev.quote[:80]+("..." if len(ev.quote)>80 else "")
            issues.append(MentionIssue(c.reason or "quote_invalid",f'"{preview}": {_MESSAGES.get(c.reason,"kutipan tidak valid.")}',mention.mention_id,"warning"))
    return mention.model_copy(update={"evidence":valid+invalid}),issues
