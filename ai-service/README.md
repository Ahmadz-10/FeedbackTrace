# FeedbackTrace AI Service

FastAPI service used by the Laravel FeedbackTrace application. It provides shared Groq/embedding infrastructure and separate Task A / Task B modules.

For complete instructions to start Laravel, Vite, and FastAPI together, configure local databases and environment files, run tests, and troubleshoot common issues, see [Running FeedbackTrace Locally](../docs/development/run-locally.md).

## Quick setup

Python 3.11 is recommended. Run these commands from this directory:

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r requirements.txt
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

Set `GROQ_API_KEY` in `.env` before using Groq-backed endpoints. Set a strong `SERVICE_API_KEY` to protect the shared POST endpoints; protected requests then require the `X-Service-Key` header.

## Run

```powershell
uvicorn app.main:app --reload --host 127.0.0.1 --port 8001
```

- Interactive API docs: http://127.0.0.1:8001/docs
- Shared health: `GET /health`
- Task A health: `GET /api/v1/task-a/health`
- Shared chat: `POST /api/v1/shared/chat`
- Shared embeddings: `POST /api/v1/shared/embeddings`

**Task A status:** extraction, validation, evidence, and consolidation modules exist as Python services. A public `POST /api/v1/task-a/analyze` route has not yet been added, and the Laravel app is not yet connected to the Task A processing pipeline.

## Tests

```powershell
python -m pytest -q
python -m pytest tests/task_a -q
```

Live Groq tests are opt-in and require `RUN_LIVE_LLM_TESTS=1` plus a valid API key. Never commit `.env` or API credentials.
