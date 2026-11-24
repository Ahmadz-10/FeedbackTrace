"""Tes Groq langsung bersifat opt-in; tidak berjalan tanpa RUN_LIVE_LLM_TESTS dan API key."""
import os
import pytest
from app.core.config import settings
from app.task_a.services.analysis_service import AnalysisService
from app.task_a.services.feedback_validator import validate_payload

pytestmark = pytest.mark.skipif(os.getenv("RUN_LIVE_LLM_TESTS") != "1" or not settings.groq_api_key,
    reason="Tes live dimatikan; set RUN_LIVE_LLM_TESTS=1 dan GROQ_API_KEY.")
def test_live_model_returns_some_mentions():
    prepared = validate_payload({"batch":{"batch_id":"LIVE"},"feedbacks":[
        {"feedback_id":"f1","question_id":"q1","comment_text":"Antreannya lama dan petugasnya tidak ramah."}]})
    result = AnalysisService().analyze_batch(prepared)
    assert result.items[0].status == "analyzed"
    assert result.items[0].mentions
