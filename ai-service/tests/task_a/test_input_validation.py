import pytest
from app.task_a.services.feedback_validator import validate_payload
from app.task_a.services.quality_flags import QualityFlag

def payload(items):
    return {"batch": {"batch_id": "B1"}, "feedbacks": items}

def item(fid="f1", text="Pelayanan tidak ramah"):
    return {"feedback_id": fid, "question_id": "q1", "comment_text": text}

def test_valid_batch_prepares_safe_text_and_preserves_original():
    result = validate_payload(payload([item(text="  Pelayanan   tidak ramah  ")]))
    assert result.ok and len(result.analyzable_items) == 1
    prepared = result.items[0]
    assert prepared.original_text == "  Pelayanan   tidak ramah  "
    assert prepared.normalized_text == "Pelayanan tidak ramah"
    assert "tidak" in prepared.analysis_text

def test_duplicate_feedback_ids_reject_whole_batch():
    result = validate_payload(payload([item("same"), item("same", "Layanan sangat cepat")]))
    assert not result.ok and not result.items
    assert any(issue.code == "duplicate_feedback_id" for issue in result.errors)

@pytest.mark.parametrize("text,flag", [
    ("", "empty_comment"), ("   ", "empty_comment"), ("12345", "no_text_content"),
    ("ok", "very_short"), ("x" * 5001, "too_long"),
])
def test_bad_comment_is_kept_as_skipped_item(text, flag):
    result = validate_payload(payload([item(text=text)]))
    assert result.ok and len(result.skipped_items) == 1
    assert flag in result.skipped_items[0].quality_flags
    assert result.skipped_items[0].analysis_text == ""

def test_malformed_item_does_not_drop_other_items():
    result = validate_payload(payload([item("f1", ""), item("f2", "Proses layanan sangat cepat")]))
    assert result.ok and len(result.items) == 2
    assert not result.items[0].analyzable and result.items[1].analyzable

def test_payload_and_schema_version_errors_are_batch_errors():
    assert not validate_payload(None).ok
    bad = {"batch": {"batch_id": "B1", "schema_version": "9"}, "feedbacks": [item()]}
    assert not validate_payload(bad).ok

def test_quote_span_maps_back_to_original():
    result = validate_payload(payload([item(text="  Saya tidak puas dengan layanan  ")]))
    prepared = result.items[0]
    start = prepared.analysis_text.index("tidak")
    a, b = prepared.analysis_span_to_original(start, start + len("tidak"))
    assert prepared.original_text[a:b] == "tidak"
