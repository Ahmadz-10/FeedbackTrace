"""Validate feedback references and output coverage."""
from typing import Iterable
from app.task_a.schemas.responses import AspectMention,ConsolidatedFinding,FeedbackResult
from app.task_a.services.analysis_types import MentionIssue
def check_mention_references(mention:AspectMention,*,owner_id:str,known_ids:set[str])->list[MentionIssue]:
    mid=mention.mention_id; issues=[]
    if mention.feedback_id not in known_ids:issues.append(MentionIssue("unknown_feedback_id",f"feedback_id '{mention.feedback_id[:40]}' tidak ada.",mid))
    elif mention.feedback_id!=owner_id:issues.append(MentionIssue("feedback_id_mismatch","Mention merujuk komentar lain.",mid))
    if not mention.evidence:issues.append(MentionIssue("mention_without_source","Mention tidak memiliki kutipan sumber.",mid))
    for ev in mention.evidence:
        if ev.feedback_id not in known_ids:issues.append(MentionIssue("unknown_feedback_id",f"Bukti merujuk feedback_id '{ev.feedback_id[:40]}' yang tidak ada.",mid))
        elif ev.feedback_id!=mention.feedback_id:issues.append(MentionIssue("evidence_feedback_id_mismatch","Bukti berasal dari komentar berbeda.",mid))
    return issues
def validate_result_coverage(batch_ids:Iterable[str],results:list[FeedbackResult])->list[MentionIssue]:
    expected=list(batch_ids); exp=set(expected); issues=[]; seen={}; mids=set()
    for r in results:
        seen[r.feedback_id]=seen.get(r.feedback_id,0)+1
        for m in r.mentions:
            if m.mention_id in mids:issues.append(MentionIssue("duplicate_mention_id",f"mention_id '{m.mention_id}' tidak unik.",m.mention_id))
            mids.add(m.mention_id)
    for fid in expected:
        if fid not in seen:issues.append(MentionIssue("missing_result",f"Komentar '{fid}' tidak ada pada hasil."))
    for fid,n in seen.items():
        if fid not in exp:issues.append(MentionIssue("unknown_result_feedback_id",f"Hasil untuk feedback_id '{fid[:40]}' tidak dikenal."))
        elif n>1:issues.append(MentionIssue("duplicate_result",f"Komentar '{fid}' muncul {n} kali."))
    return issues
def validate_finding_references(findings:Iterable[ConsolidatedFinding],known_ids:set[str])->list[MentionIssue]:
    issues=[]
    for f in findings:
        fid=f.finding_id
        if not f.supporting_feedback_ids:issues.append(MentionIssue("finding_without_source",f"Finding '{fid}' tidak memiliki sumber.",fid))
        for ref in f.supporting_feedback_ids:
            if ref not in known_ids:issues.append(MentionIssue("unknown_feedback_id",f"Finding '{fid}' merujuk ID tidak dikenal '{ref[:40]}'.",fid))
        if len(set(f.supporting_feedback_ids))!=len(f.supporting_feedback_ids):issues.append(MentionIssue("duplicate_supporting_id",f"Finding '{fid}' memiliki ID pendukung duplikat.",fid))
        elif f.unique_feedback_count!=len(f.supporting_feedback_ids):issues.append(MentionIssue("count_mismatch",f"Finding '{fid}' jumlah feedback tidak cocok.",fid))
        for mv in f.minority_views:
            if mv.feedback_id not in known_ids:issues.append(MentionIssue("unknown_feedback_id",f"Finding '{fid}' memiliki minority view dari ID tidak dikenal.",fid))
    return issues
