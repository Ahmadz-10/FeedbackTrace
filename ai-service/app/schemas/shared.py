from typing import Annotated,Literal
from pydantic import BaseModel,Field,StringConstraints
MessageContent=Annotated[str,StringConstraints(min_length=1,max_length=50000)]
EmbeddingInput=Annotated[str,StringConstraints(min_length=1,max_length=8000)]
class ChatMessage(BaseModel):
    role:Literal['system','user','assistant']
    content:MessageContent
class ChatRequest(BaseModel):
    messages:list[ChatMessage]=Field(min_length=1,max_length=40)
    max_completion_tokens:int=Field(default=512,ge=1,le=8192)
class ChatResponse(BaseModel):
    model:str
    content:str
class EmbeddingRequest(BaseModel):
    texts:list[EmbeddingInput]=Field(min_length=1,max_length=64)
    input_type:Literal['query','passage']='passage'
class EmbeddingResponse(BaseModel):
    model:str
    dimensions:int
    embeddings:list[list[float]]
