# Running FeedbackTrace Locally

This guide covers the shared Laravel app and both Python task modules. Keep task-specific work on its designated branch:
- `feature/task-a-feedback-intelligence`
- `feature/task-b-decision-intelligence`

## Prerequisites
PHP 8.3+, Composer, Node.js/npm, Python 3.11+, pip, SQLite with PHP's `pdo_sqlite` extension (or MySQL), and a Groq API key for live LLM analysis.

## 1. Laravel and dashboard

From the repository root:

```powershell
composer install
if (!(Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
npm install
```

For SQLite, set `DB_CONNECTION=sqlite` in root `.env`, then create the database file if missing:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force | Out-Null
php artisan migrate
```

Start Laravel and Vite in separate terminals:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

```powershell
npm run dev
```

Open http://127.0.0.1:8000. The shared multi-page FeedbackTrace dashboard is available from the sidebar. Pages marked as demo or preview still use sample data.

## 2. Shared Python AI service

From the repository root:

```powershell
cd ai-service
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r requirements.txt
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

In `ai-service/.env`, configure Groq, embedding, service-key, and CORS settings. Start FastAPI from inside `ai-service/`:

```powershell
python -m uvicorn app.main:app --reload --host 127.0.0.1 --port 8001
```

Keep one Uvicorn process running. Task A's job store is currently in-memory.

## 3. Task A — Student Feedback Intelligence

In root Laravel `.env`, configure:

```dotenv
AI_SERVICE_URL=http://127.0.0.1:8001
AI_SERVICE_KEY=the_same_value_as_SERVICE_API_KEY
AI_SERVICE_TIMEOUT=30
QUEUE_CONNECTION=database
```

The service-key values must match exactly.

Task A endpoints:
- `GET /api/v1/task-a/health`
- `POST /api/v1/task-a/analyze`
- `GET /api/v1/task-a/jobs/{job_id}`
- `GET /api/v1/task-a/batches/{batch_id}/results`
- `GET /api/v1/task-a/batches/{batch_id}/summary`
- `GET /api/v1/task-a/findings/{finding_id}`
- Laravel review: `POST /api/v1/task-a/findings/{finding_code}/review`

Start a Laravel queue worker in another terminal from the repository root:

```powershell
php artisan queue:work
```

The queue worker submits batches, polls FastAPI job status, and persists completed results. The review API requires an authenticated Laravel session; a complete login flow and interactive finding-review screen are not yet connected.

## 4. Task B — Service Quality Decision Intelligence

Task B currently provides survey validation, IPA calculation, quadrant classification, and validation of approved Task A finding payloads.

Endpoints:
- `GET /api/v1/task-b/health`
- `POST /api/v1/task-b/calculate-ipa`
- `POST /api/v1/task-b/validate-findings`

Explore the Task B API at http://127.0.0.1:8001/docs. University-document ingestion, retrieval/RAG recommendation generation, and persistent Laravel-to-Task-B integration are not yet implemented.

## 5. Tests

From `ai-service/` with the virtual environment active:

```powershell
python -m pytest tests/task_a tests/task_b -q
```

From the repository root:

```powershell
php artisan test
npm run build
```

Live Groq tests are opt-in and require a valid API key. Never commit actual `.env` files, API keys, passwords, or private student feedback.
