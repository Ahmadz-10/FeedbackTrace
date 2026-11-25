# Segment 01: Fondasi Layanan Python & FastAPI (bagian Task A)

**Branch:** `feature/task-a-feedback-intelligence`

Layanan FastAPI bersama (`config`, klien Groq, embedding, health check, tes) sudah ada di `main` lewat PR #1
(`feature/shared-ai-service`). Dokumen ini hanya mencakup tambahan Task A di atasnya.

## Pemetaan ke checklist (lokasi sebenarnya di repo)

| Checklist | Lokasi di repo | Status |
|---|---|---|
| `shared/config.py` | `ai-service/app/core/config.py` | Sudah ada (shared) |
| `shared/groq_client.py` | `ai-service/app/shared/groq_client.py` | Sudah ada; timeout 60 dtk dan 2 retry berasal dari default SDK `groq` |
| `shared/embedding_service.py` | `ai-service/app/shared/embedding_service.py` | Sudah ada (`intfloat/multilingual-e5-base`, awalan `query:`/`passage:`) |
| `main.py` | `ai-service/app/main.py` | Task A menambah **satu baris**: `include_router(task_a_router)` |
| `task_a/api/routes.py` | `ai-service/app/task_a/api/routes.py` | Baru: `GET /api/v1/task-a/health` |
| `tests/task_a/` | `ai-service/tests/task_a/test_smoke.py` | Baru |
| `.env.example`, `requirements.txt` | `ai-service/` | Sudah ada (shared) |

## Aturan kerja
- Task A **tidak** mengubah file shared selain pendaftaran router di `main.py`. Butuh perubahan shared
  (mis. `temperature`, JSON mode, atau model aktual dari respons Groq di `create_chat_completion`)? Buat PR kecil
  terpisah dan beri tahu Task B.
- Endpoint analisis (Segment 07) wajib memakai `dependencies=[Depends(require_service_key)]`.
- Jangan membuat klien Groq atau SentenceTransformer sendiri (dijaga oleh `test_task_a_does_not_duplicate_shared_clients`).

## Menjalankan
```bash
cd ai-service
python -m venv .venv && source .venv/bin/activate     # Windows: .venv\Scripts\activate
pip install -r requirements.txt
python -m pytest -q
uvicorn app.main:app --reload --host 127.0.0.1 --port 8001
curl http://127.0.0.1:8001/api/v1/task-a/health
```
