from fastapi.testclient import TestClient
from app.main import app
from app.api.routes import shared
client=TestClient(app)
def test_validation_empty_embeddings():
    assert client.post('/api/v1/shared/embeddings',json={'texts':[]}).status_code==422
def test_chat_mock(monkeypatch):
    monkeypatch.setattr(shared,'create_chat_completion',lambda messages,max_completion_tokens=512:'OK')
    r=client.post('/api/v1/shared/chat',json={'messages':[{'role':'user','content':'test'}]})
    assert r.status_code==200 and r.json()['content']=='OK'
def test_embeddings_mock(monkeypatch):
    monkeypatch.setattr(shared,'embed_texts',lambda texts,input_type='passage':[[1.0,0.0] for _ in texts])
    r=client.post('/api/v1/shared/embeddings',json={'texts':['test']})
    assert r.status_code==200 and r.json()['dimensions']==2
