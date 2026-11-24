"""Adaptor Task A di atas klien Groq bersama; klien tidak dibuat ulang."""
from __future__ import annotations
import time
from dataclasses import dataclass, field
from typing import Any, Protocol
from app.core.config import settings
from app.shared.groq_client import get_groq_client

PROVIDER = "groq"
class LLMError(Exception):
    """Kegagalan pemanggilan model."""

@dataclass(frozen=True)
class LLMResponse:
    content: str
    model_id: str
    provider: str = PROVIDER
    latency_ms: int = 0
    finish_reason: str | None = None
    usage: dict[str, Any] = field(default_factory=dict)

class LLMGateway(Protocol):
    def complete(self, messages: list[dict[str, str]], *, temperature: float,
                 max_completion_tokens: int, json_mode: bool = True) -> LLMResponse: ...

class GroqGateway:
    def complete(self, messages: list[dict[str, str]], *, temperature: float,
                 max_completion_tokens: int, json_mode: bool = True) -> LLMResponse:
        try:
            client = get_groq_client()
        except RuntimeError as exc:
            raise LLMError("GROQ_API_KEY belum dikonfigurasi.") from exc
        kwargs: dict[str, Any] = {
            "model": settings.groq_model, "messages": messages,
            "temperature": temperature, "max_completion_tokens": max_completion_tokens,
        }
        if json_mode:
            kwargs["response_format"] = {"type": "json_object"}
        started = time.perf_counter()
        try:
            response = client.chat.completions.create(**kwargs)
        except Exception as exc:
            raise LLMError(f"Pemanggilan Groq gagal: {type(exc).__name__}") from exc
        choice = response.choices[0]
        content = choice.message.content or ""
        finish = getattr(choice, "finish_reason", None)
        if not content.strip():
            hint = " (kemungkinan terpotong; naikkan LLM_MAX_COMPLETION_TOKENS)" if finish == "length" else ""
            raise LLMError(f"Model mengembalikan respons kosong{hint}.")
        usage = response.usage.model_dump() if getattr(response, "usage", None) else {}
        return LLMResponse(content=content,
            model_id=getattr(response, "model", None) or settings.groq_model,
            latency_ms=int((time.perf_counter() - started) * 1000),
            finish_reason=finish, usage=usage)
