from app.task_a.services.privacy_filter import PrivacyFilter, contains_pii

def test_redacts_email_phone_nik_student_id_url_and_names():
    source = "Hubungi saya di user@example.com atau 081234567890, NIM 20240123456, https://example.com. Saya Bapak Budi."
    result = PrivacyFilter().redact(source)
    assert "[EMAIL]" in result.text
    assert "[PHONE]" in result.text
    assert "[STUDENT_ID]" in result.text
    assert "[URL]" in result.text
    assert "[NAME]" in result.text
    assert "user@example.com" not in result.text

def test_extra_terms_and_salary_currency_handling():
    result = PrivacyFilter(extra_terms=["Budi Santoso"]).redact("Budi Santoso menerima Rp 1500000.")
    assert "[NAME]" in result.text
    assert "Rp 1500000" in result.text

def test_maps_redacted_offsets_to_source():
    result = PrivacyFilter().redact("Email user@example.com segera")
    start = result.text.index("[EMAIL]")
    source_start, source_end = result.to_source_span(start, start + len("[EMAIL]"))
    assert result.source[source_start:source_end] == "user@example.com"

def test_contains_pii_detects_unredacted_data():
    assert contains_pii("test@example.com")
    assert not contains_pii("Layanan cepat dan ramah")
