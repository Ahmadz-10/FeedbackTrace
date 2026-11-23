from functools import lru_cache
from typing import Literal
from app.core.config import settings

@lru_cache(maxsize=1)
def get_embedding_model():
    from sentence_transformers import SentenceTransformer
    return SentenceTransformer(settings.embedding_model, device=settings.embedding_device)

def embed_texts(texts: list[str], *, input_type: Literal['query','passage']='passage') -> list[list[float]]:
    if not texts: return []
    inputs=[f'{input_type}: {text.strip()}' for text in texts]
    vectors=get_embedding_model().encode(inputs,batch_size=settings.embedding_batch_size,normalize_embeddings=True,convert_to_numpy=True,show_progress_bar=False)
    return vectors.tolist()
