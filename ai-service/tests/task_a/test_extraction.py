from app.task_a.services.analysis_service import AnalysisService
from app.task_a.services.llm_gateway import LLMResponse
from app.task_a.services.feedback_validator import validate_payload

class FakeGateway:
    def __init__(self, content):
        self.content = content
        self.calls = 0
    def complete(self, messages, *, temperature, max_completion_tokens, json_mode=True):
        self.calls += 1
        assert json_mode is True
        return LLMResponse(content=self.content, model_id="fake-model")

def payload(text):
    return {"batch": {"batch_id": "B1"}, "feedbacks": [
        {"feedback_id": "f1", "question_id": "q1", "comment_text": text}
    ]}

def test_extracts_aspect_sentiment_reason_and_quote_candidate():
    gateway = FakeGateway('{"mentions":[{"aspect_code":"service_speed","sentiment":"negative","sentiment_strength":"high","reason_type":"dissatisfaction_driver","reason_category":"long_waiting_time","reason_description":"Antrean lama.","evidence_quotes":["Antreannya lama"]}]}')
    result = AnalysisService(gateway).analyze_batch(validate_payload(payload("Antreannya lama banget")))
    mention = result.items[0].mentions[0]
    assert mention.aspect_code == "service_speed"
    assert mention.sentiment.value == "negative"
    assert mention.evidence[0].status.value == "unverified"
    assert result.metadata.model_id == "fake-model"

def test_skipped_items_never_call_model():
    gateway = FakeGateway('{"mentions":[]}')
    result = AnalysisService(gateway).analyze_batch(validate_payload(payload("")))
    assert gateway.calls == 0 and result.stats.skipped_feedbacks == 1

def test_malformed_output_marks_item_failed_without_stopping_batch():
    gateway = FakeGateway("not json")
    result = AnalysisService(gateway).analyze_batch(validate_payload(payload("Layanan cepat dan ramah")))
    assert result.items[0].status == "failed"
    assert "invalid_llm_output" in result.items[0].quality_flags

def test_empty_mentions_are_valid_analyzed_result():
    gateway = FakeGateway('{"mentions":[]}')
    result = AnalysisService(gateway).analyze_batch(validate_payload(payload("Hanya ingin menyapa semua staf")))
    assert result.items[0].status == "analyzed"
    assert "no_aspect_found" in result.items[0].quality_flags

def test_duplicate_mention_merges_quotes():
    content = '{"mentions":['       '{"aspect_code":"service_speed","sentiment":"negative","reason_type":"dissatisfaction_driver","reason_category":"long_waiting_time","evidence_quotes":["lama"]},'       '{"aspect_code":"service_speed","sentiment":"negative","reason_type":"dissatisfaction_driver","reason_category":"long_waiting_time","evidence_quotes":["antre"]}]}'
    result = AnalysisService(FakeGateway(content)).analyze_batch(validate_payload(payload("Antrean lama dan lambat")))
    assert len(result.items[0].mentions) == 1
    assert [e.quote for e in result.items[0].mentions[0].evidence] == ["lama", "antre"]
