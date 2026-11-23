import logging
from fastapi import APIRouter,Depends,HTTPException
from starlette.concurrency import run_in_threadpool
from app.api.dependencies import require_service_key
from app.core.config import settings
from app.schemas.shared import ChatRequest,ChatResponse,EmbeddingRequest,EmbeddingResponse
from app.shared.groq_client import create_chat_completion
from app.shared.embedding_service import embed_texts
logger=logging.getLogger(__name__)
router=APIRouter(prefix='/api/v1/shared',tags=['shared'],dependencies=[Depends(require_service_key)])
@router.post('/chat',response_model=ChatResponse)
async def chat(req:ChatRequest):
    try: content=await run_in_threadpool(create_chat_completion,[m.model_dump() for m in req.messages],max_completion_tokens=req.max_completion_tokens)
    except RuntimeError as e: raise HTTPException(status_code=503,detail=str(e)) from e
    except Exception as e:
        logger.exception('LLM request failed')
        raise HTTPException(status_code=502,detail='Provider request failed; inspect service logs.') from e
    return ChatResponse(model=settings.groq_model,content=content)
@router.post('/embeddings',response_model=EmbeddingResponse)
async def embeddings(req:EmbeddingRequest):
    try: vectors=await run_in_threadpool(embed_texts,req.texts,input_type=req.input_type)
    except Exception as e:
        logger.exception('Embedding request failed')
        raise HTTPException(status_code=503,detail='Embedding model unavailable; inspect service logs.') from e
    return EmbeddingResponse(model=settings.embedding_model,dimensions=len(vectors[0]) if vectors else 0,embeddings=vectors)
