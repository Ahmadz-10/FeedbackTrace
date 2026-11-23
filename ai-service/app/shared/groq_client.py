from functools import lru_cache
from groq import Groq
from app.core.config import settings

@lru_cache(maxsize=1)
def get_groq_client() -> Groq:
    if not settings.groq_api_key:
        raise RuntimeError('GROQ_API_KEY is not configured.')
    return Groq(api_key=settings.groq_api_key.get_secret_value())

def create_chat_completion(messages: list[dict], *, max_completion_tokens: int = 512) -> str:
    response = get_groq_client().chat.completions.create(model=settings.groq_model, messages=messages, max_tokens=max_completion_tokens)
    content = response.choices[0].message.content
    if not content:
        raise RuntimeError('The model returned an empty response.')
    return content
