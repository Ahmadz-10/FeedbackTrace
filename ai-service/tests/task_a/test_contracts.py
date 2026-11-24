"""Uji kontrak Segment 02: taxonomy, kategori alasan, label, dan schema."""
import pytest
from pydantic import ValidationError

from app.task_a.config import labels, reason_categories as rc, taxonomy
from app.task_a.config.labels import ReasonType, Sentiment, values
from app.task_a.schemas.requests import AnalyzeRequest, FeedbackItem
from app.task_a.schemas.responses import (
    AnalyzeResponse, AspectMention, ConsolidatedFinding, EvidenceItem,
    SentimentDistribution,
)


def test_taxonomy_has_ten_unique_codes_with_other():
    assert len(taxonomy.ASPECT_CODES) == 10
    assert len(set(taxonomy.ASPECT_CODES)) == 10
    assert taxonomy.is_valid_aspect("other")
    assert not taxonomy.is_valid_aspect("made_up_aspect")


def test_taxonomy_exact_codes_from_checklist():
    assert set(taxonomy.ASPECT_CODES) == {
        "procedure_clarity", "information_consistency", "service_speed",
        "staff_responsiveness", "staff_professionalism", "service_accessibility",
        "digital_service_usability", "requirement_clarity", "service_availability", "other",
    }


def test_reason_categories_eleven_unique_and_typed():
    assert len(rc.REASON_CATEGORY_CODES) == 11
    assert len(set(rc.REASON_CATEGORY_CODES)) == 11
    assert all(c.description and c.allowed_types for c in rc.REASON_CATEGORIES)
    assert rc.is_type_allowed("long_waiting_time", ReasonType.DISSATISFACTION_DRIVER)
    assert not rc.is_type_allowed("long_waiting_time", ReasonType.SATISFACTION_DRIVER)
    assert rc.is_type_allowed("other_reason", ReasonType.NEUTRAL_OBSERVATION)
    assert not rc.is_type_allowed("nope", ReasonType.NEUTRAL_OBSERVATION)


def test_label_values_match_checklist():
    assert values(labels.Sentiment) == ("positive", "negative", "neutral", "mixed")
    assert values(labels.SentimentStrength) == ("low", "medium", "high")
    assert values(labels.ReasonType) == ("satisfaction_driver", "dissatisfaction_driver", "neutral_observation")
    assert values(labels.FindingType) == ("service_strength", "service_weakness", "mixed", "observation")
    assert "partially_completed" in values(labels.JobStatus)
    assert values(labels.ReviewStatus) == ("pending_review", "approved", "rejected")


def _req(**over):
    base = {
        "batch": {"batch_id": "B1"},
        "feedbacks": [{"feedback_id": "f1", "question_id": "q1", "comment_text": "Antreannya lama"}],
    }
    base.update(over)
    return base


def test_valid_request_gets_defaults():
    r = AnalyzeRequest.model_validate(_req())
    assert r.batch.schema_version == "1.0"
    assert r.options.max_retries == 2
    assert r.feedbacks[0].submitted_at is None


@pytest.mark.parametrize("missing", ["feedback_id", "question_id", "comment_text"])
def test_required_feedback_fields(missing):
    item = {"feedback_id": "f1", "question_id": "q1", "comment_text": "x"}
    item.pop(missing)
    with pytest.raises(ValidationError):
        FeedbackItem.model_validate(item)


def test_empty_comment_accepted_so_it_can_be_flagged_later():
    assert FeedbackItem(feedback_id="f", question_id="q", comment_text="").comment_text == ""


def test_request_rejects_empty_batch_and_unknown_fields():
    with pytest.raises(ValidationError):
        AnalyzeRequest.model_validate(_req(feedbacks=[]))
    with pytest.raises(ValidationError):
        AnalyzeRequest.model_validate(_req(unexpected=1))


def test_bad_language_hint_rejected():
    with pytest.raises(ValidationError):
        FeedbackItem(feedback_id="f", question_id="q", comment_text="x", language_hint="Indonesia!!")


def test_mention_rejects_illegal_labels():
    with pytest.raises(ValidationError):
        AspectMention(mention_id="m", feedback_id="f", aspect_code="service_speed",
                      sentiment="angry", reason_type="dissatisfaction_driver",
                      reason_category="long_waiting_time")


def test_evidence_defaults_to_unverified_and_response_roundtrip():
    ev = EvidenceItem(quote="antreannya lama", feedback_id="f1")
    assert ev.status == "unverified" and ev.start_char is None
    m = AspectMention(mention_id="m1", feedback_id="f1", aspect_code="service_speed",
                      sentiment=Sentiment.NEGATIVE, reason_type=ReasonType.DISSATISFACTION_DRIVER,
                      reason_category="long_waiting_time", evidence=[ev])
    f = ConsolidatedFinding(finding_id="F1", aspect_code="service_speed",
                            reason_category="long_waiting_time", finding_type="service_weakness",
                            issue_title="t", issue_summary="s", unique_feedback_count=1,
                            mention_count=1, supporting_feedback_ids=["f1"],
                            sentiment_distribution=SentimentDistribution(negative=1),
                            single_feedback_support=True)
    assert f.review_status == "pending_review"
    resp = AnalyzeResponse(job_id="J1", batch_id="B1", status="completed", findings=[f])
    again = AnalyzeResponse.model_validate_json(resp.model_dump_json())
    assert again.findings[0].single_feedback_support is True
    assert m.evidence[0].quote == "antreannya lama"
