"""Alat bantu bersama untuk tes Task A (model palsu, pembuat mention, penyiap komentar)."""
import json

from app.task_a.services.feedback_validator import validate_payload
from app.task_a.services.llm_gateway import LLMResponse


class ScriptedGateway:
    """Mengembalikan keluaran berurutan (str/dict/Exception) dan mencatat pesan yang diterima."""

    def __init__(self, *outputs, model_id="model-aktual-v1"):
        self.outputs = list(outputs)
        self.model_id = model_id
        self.calls = []

    def complete(self, messages, **kwargs):
        self.calls.append({"messages": messages, **kwargs})
        out = self.outputs.pop(0)
        if isinstance(out, Exception):
            raise out
        content = out if isinstance(out, str) else json.dumps(out)
        return LLMResponse(content=content, model_id=self.model_id, latency_ms=12, usage={"total_tokens": 99})


def m(aspect="service_speed", sentiment="negative", rtype="dissatisfaction_driver",
      cat="long_waiting_time", quotes=("lama",), **over):
    base = {"aspect_code": aspect, "is_proposed_aspect": False, "proposed_aspect_label": None,
            "sentiment": sentiment, "sentiment_strength": "medium", "reason_type": rtype,
            "reason_category": cat, "reason_description": "Deskripsi singkat.", "evidence_quotes": list(quotes)}
    base.update(over)
    return base


def prepare(texts, **options):
    texts = [texts] if isinstance(texts, str) else texts
    payload = {"batch": {"batch_id": "B1"}, "feedbacks": [
        {"feedback_id": f"f{i+1}", "question_id": "q1", "comment_text": t} for i, t in enumerate(texts)]}
    if options:
        payload["options"] = options
    prepared = validate_payload(payload)
    assert prepared.ok
    return prepared


def prep_item(text):
    return prepare(text).items[0]


class RouterGateway:
    """Model palsu untuk alur lengkap: panggilan ringkasan temuan dibedakan dari panggilan ekstraksi."""

    SUMMARY = {"issue_title": "Waktu tunggu layanan terlalu lama",
               "issue_summary": "Mahasiswa melaporkan harus menunggu lama sebelum dilayani."}

    def __init__(self, *extraction, summaries=None):
        self.extraction = list(extraction)
        self.summaries = list(summaries) if summaries is not None else []
        self.calls = []

    def complete(self, messages, **kwargs):
        self.calls.append(messages)
        is_summary = "Excerpts (between" in messages[1]["content"]
        queue = self.summaries if is_summary else self.extraction
        out = queue.pop(0) if queue else (self.SUMMARY if is_summary else {"mentions": []})
        if isinstance(out, Exception):
            raise out
        return LLMResponse(content=out if isinstance(out, str) else json.dumps(out), model_id="model-aktual-v1", latency_ms=5)
