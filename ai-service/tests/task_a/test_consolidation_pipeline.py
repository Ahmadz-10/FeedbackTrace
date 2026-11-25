from app.task_a.config.labels import EvidenceStatus
from app.task_a.schemas.responses import AspectMention,EvidenceItem,BatchStats
from app.task_a.services.analysis_types import ItemAnalysis,BatchAnalysis
from app.task_a.services.feedback_validator import validate_payload
from app.task_a.services.consolidation_service import consolidate
from app.task_a.services.finding_grouper import collect_records,group_mentions
from app.task_a.services.minority_preserver import every_group_is_kept
from app.task_a.services.similarity import cosine_similarity,calibrate_threshold

def make(fid,sentiment,reason,category,quote):
    return AspectMention(mention_id=f"{fid}-m1",feedback_id=fid,aspect_code="service_speed",
      sentiment=sentiment,reason_type=reason,reason_category=category,
      reason_description="Antrean lama." if sentiment=="negative" else "Pelayanan cepat.",
      evidence=[EvidenceItem(quote=quote,feedback_id=fid,start_char=0,end_char=len(quote),status=EvidenceStatus.VALID)])

def prepared(*rows):
    return validate_payload({"batch":{"batch_id":"B1"},"feedbacks":[
      {"feedback_id":fid,"question_id":"q1","comment_text":text} for fid,text in rows]})

def test_only_verified_mentions_become_findings_and_singletons_are_kept():
    items=[ItemAnalysis("f1","analyzed",mentions=[make("f1","negative","dissatisfaction_driver","long_waiting_time","lama")]),
           ItemAnalysis("f2","analyzed",mentions=[make("f2","negative","dissatisfaction_driver","long_waiting_time","lambat")])]
    prep=prepared(("f1","lama"),("f2","lambat"))
    result=consolidate(BatchAnalysis(items,BatchStats(),None),prep,use_llm_summaries=False)
    assert len(result.findings)==1
    assert result.findings[0].unique_feedback_count==2 and result.findings[0].mention_count==2
    records,_=collect_records(items,{"f1":0,"f2":1})
    assert every_group_is_kept(group_mentions(records),result.findings)

def test_opposing_minority_view_is_preserved_without_dropping_its_finding():
    items=[
      ItemAnalysis("f1","analyzed",mentions=[make("f1","negative","dissatisfaction_driver","long_waiting_time","lama")]),
      ItemAnalysis("f2","analyzed",mentions=[make("f2","negative","dissatisfaction_driver","long_waiting_time","lambat")]),
      ItemAnalysis("f3","analyzed",mentions=[make("f3","positive","satisfaction_driver","fast_service","cepat")])]
    prep=prepared(("f1","lama"),("f2","lambat"),("f3","cepat"))
    result=consolidate(BatchAnalysis(items,BatchStats(),None),prep,use_llm_summaries=False)
    assert len(result.findings)==2
    weak=next(f for f in result.findings if f.finding_type.value=="service_weakness")
    assert [v.feedback_id for v in weak.minority_views]==["f3"]
    assert any("f3" in f.supporting_feedback_ids for f in result.findings)

def test_similarity_is_an_evaluation_utility_and_calibration_prefers_precision():
    assert cosine_similarity([1,0],[1,0])==1.0
    report=calibrate_threshold([(0.9,True),(0.2,False)],min_precision=.95)
    assert report is not None and report.threshold==0.9
