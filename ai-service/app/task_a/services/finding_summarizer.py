"""LLM writes only finding text; all counts and source IDs are computed deterministically."""
from __future__ import annotations
import hashlib,json,re
from dataclasses import dataclass
from functools import lru_cache
from pathlib import Path
from app.task_a.config import reason_categories as rc,taxonomy
from app.task_a.config.labels import FindingType
from app.task_a.config.versions import LLM_MAX_COMPLETION_TOKENS,LLM_TEMPERATURE,SUMMARY_PROMPT_VERSION
from app.task_a.services.analysis_types import LLMOutputError
from app.task_a.services.aspect_extractor import unique_delimiter
from app.task_a.services.finding_grouper import Group
from app.task_a.services.llm_gateway import LLMError,LLMGateway
from app.task_a.services.quality_flags import QualityFlag
_PROMPT_DIR=Path(__file__).resolve().parents[1]/"prompts"
MAX_TITLE=120;MAX_SUMMARY=600;MAX_EXCERPTS=8;MAX_EXCERPT_CHARS=200
_QUANTIFIERS=("banyak","sebagian besar","mayoritas","kebanyakan","seluruh","semua","many","most","majority","all ")
_PLACEHOLDER=re.compile(r"\[[A-Z_]{3,}\]");_NUMBER=re.compile(r"\d+")
_KIND={FindingType.SERVICE_WEAKNESS:("complaint","Mahasiswa melaporkan kendala pada aspek"),
 FindingType.SERVICE_STRENGTH:("praise","Mahasiswa menyatakan hal positif pada aspek"),
 FindingType.MIXED:("mixed opinions","Mahasiswa menyampaikan penilaian beragam pada aspek"),
 FindingType.OBSERVATION:("neutral observation","Mahasiswa menyampaikan pengamatan pada aspek")}
@dataclass(frozen=True)
class FindingText:
    title:str
    summary:str
    source:str
    model_id:str|None=None
    reject_reason:str|None=None
    @property
    def flags(self):return [QualityFlag.SUMMARY_FALLBACK.value] if self.source=="template" else []
@lru_cache
def _template(version:str=SUMMARY_PROMPT_VERSION)->str:
    return (_PROMPT_DIR/f"{version}.txt").read_text(encoding="utf-8")
def summary_prompt_fingerprint(version:str=SUMMARY_PROMPT_VERSION)->str:
    return hashlib.sha256(_template(version).encode()).hexdigest()[:12]
def aspect_display(group:Group)->str:
    return f"usulan: {group.aspect_label}" if group.aspect_label else taxonomy.get_aspect(group.aspect_code).name.lower()
def template_text(group:Group,finding_type:FindingType)->FindingText:
    cat=rc.get_reason_category(group.reason_category);aspect=aspect_display(group)
    title=f"{taxonomy.get_aspect(group.aspect_code).name if not group.aspect_label else group.aspect_label}: {cat.name}"
    summary=f"{_KIND[finding_type][1]} {aspect}: {cat.name.lower()}. {cat.description}"
    return FindingText(title[:MAX_TITLE],summary[:MAX_SUMMARY],"template")
def _excerpts(group:Group)->list[str]:
    lines=[]
    for record in group.records[:MAX_EXCERPTS]:
        mention=record.mention
        quote=next((e.quote for e in mention.evidence if e.status.value=="valid"),"")
        reason=(mention.reason_description or "").strip()
        lines.append(f'- reason: {reason[:MAX_EXCERPT_CHARS]} | quote: "{quote[:MAX_EXCERPT_CHARS]}"')
    return lines
def build_messages(group:Group,finding_type:FindingType):
    body="\n".join(_excerpts(group));delimiter=unique_delimiter(body);cat=rc.get_reason_category(group.reason_category)
    user=(f"Finding: {_KIND[finding_type][0]} about {aspect_display(group)}; reason category: {cat.code} ({cat.description})\n"
      f"Excerpts (between the two delimiter lines):\n{delimiter}\n{body}\n{delimiter}")
    return [{"role":"system","content":_template()},{"role":"user","content":user}],body
def validate_text(raw:object,excerpt_text:str)->tuple[str,str]:
    if not isinstance(raw,dict):raise LLMOutputError("bukan objek JSON")
    title,summary=raw.get("issue_title"),raw.get("issue_summary")
    if not isinstance(title,str) or not isinstance(summary,str) or not title.strip() or not summary.strip():
        raise LLMOutputError("issue_title/issue_summary hilang atau kosong")
    title,summary=" ".join(title.split())," ".join(summary.split())
    if len(title)>MAX_TITLE or len(summary)>MAX_SUMMARY:raise LLMOutputError("teks terlalu panjang")
    text=f"{title} {summary}"
    if _PLACEHOLDER.search(text):raise LLMOutputError("memuat placeholder privasi")
    allowed=set(_NUMBER.findall(excerpt_text))
    if any(n not in allowed for n in _NUMBER.findall(text)):raise LLMOutputError("memuat angka yang tidak ada pada kutipan")
    low,source= text.lower(),excerpt_text.lower()
    if any(q in low and q not in source for q in _QUANTIFIERS):raise LLMOutputError("memuat kata kuantitas yang tidak ada pada kutipan")
    return title,summary
class FindingSummarizer:
    def __init__(self,gateway:LLMGateway|None,*,max_calls:int=50):
        self._gateway=gateway;self._remaining=max_calls
    def summarize(self,group:Group,finding_type:FindingType)->FindingText:
        fallback=template_text(group,finding_type)
        if self._gateway is None:return fallback
        if self._remaining<=0:return FindingText(fallback.title,fallback.summary,"template",reject_reason="llm_call_limit")
        self._remaining-=1;messages,excerpts=build_messages(group,finding_type)
        try:
            response=self._gateway.complete(messages,temperature=LLM_TEMPERATURE,max_completion_tokens=LLM_MAX_COMPLETION_TOKENS,json_mode=True)
        except LLMError as exc:return FindingText(fallback.title,fallback.summary,"template",reject_reason=f"llm_error: {str(exc)[:100]}")
        try:
            raw=_parse(response.content);title,summary=validate_text(raw,excerpts)
        except LLMOutputError as exc:return FindingText(fallback.title,fallback.summary,"template",response.model_id,reject_reason=str(exc))
        return FindingText(title,summary,"llm",response.model_id)
def _parse(content:str)->object:
    text=(content or "").strip();fence=re.search(r"```(?:json)?\s*(.*?)```",text,re.S|re.I)
    candidates=[fence.group(1).strip()] if fence else []
    candidates.append(text);a,b=text.find("{"),text.rfind("}")
    if a!=-1 and b>a:candidates.append(text[a:b+1])
    for c in candidates:
        try:return json.loads(c)
        except (ValueError,TypeError):pass
    raise LLMOutputError("bukan JSON yang valid")
