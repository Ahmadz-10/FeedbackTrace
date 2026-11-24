from app.task_a.services.feedback_validator import validate_payload
from app.task_a.services.analysis_service import AnalysisService
from app.task_a.services.retry_handler import run_validated_batch
from app.task_a.services.llm_gateway import LLMResponse
from app.task_a.config.labels import EvidenceStatus
class FakeGateway:
    def __init__(self,*outputs):self.outputs=list(outputs);self.calls=[]
    def complete(self,messages,*,temperature,max_completion_tokens,json_mode=True):
        self.calls.append(messages)
        value=self.outputs.pop(0)
        if isinstance(value,Exception):raise value
        import json
        return LLMResponse(content=json.dumps(value) if isinstance(value,dict) else value,model_id="fake")
def prep(text):
    return validate_payload({"batch":{"batch_id":"B"},"feedbacks":[{"feedback_id":"f1","question_id":"q1","comment_text":text}]})
GOOD={"mentions":[{"aspect_code":"service_speed","sentiment":"negative","sentiment_strength":"high","reason_type":"dissatisfaction_driver","reason_category":"long_waiting_time","reason_description":"Antrean lama.","evidence_quotes":["lama banget"]}]}
BAD={"mentions":[{"aspect_code":"service_speed","sentiment":"negative","reason_type":"dissatisfaction_driver","reason_category":"long_waiting_time","evidence_quotes":["tidak ada dalam komentar"]}]}
def test_verified_quote_maps_to_exact_original_offsets():
    text="  Antreannya   lama banget  "
    result=run_validated_batch(prep(text),AnalysisService(FakeGateway(GOOD)))
    ev=result.analysis.items[0].mentions[0].evidence[0]
    assert ev.status==EvidenceStatus.VALID and text[ev.start_char:ev.end_char]=="lama banget"
def test_false_quote_is_retried_and_not_returned_as_valid():
    gw=FakeGateway(BAD,GOOD)
    result=run_validated_batch(prep("Antreannya lama banget"),AnalysisService(gw))
    assert len(gw.calls)==2 and result.analysis.items[0].status=="analyzed"
    assert result.analysis.items[0].mentions[0].evidence[0].status==EvidenceStatus.VALID
def test_persistent_false_quotes_fail_closed():
    result=run_validated_batch(prep("Antreannya lama banget"),AnalysisService(FakeGateway(BAD,BAD,BAD)))
    assert result.analysis.items[0].status=="failed"
    assert "validation_failed" in result.analysis.items[0].quality_flags
def test_mixed_sentiment_type_and_reason_mismatch_rejected():
    bad={"mentions":[dict(GOOD["mentions"][0],sentiment="positive")]}
    result=run_validated_batch(prep("Antreannya lama banget"),AnalysisService(FakeGateway(bad,bad,bad)))
    assert result.analysis.items[0].status=="failed"
