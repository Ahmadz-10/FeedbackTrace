# Running FeedbackTrace — Task A and Task B

This guide covers local setup for both development branches. Run only the commands for the branch you have checked out.

## 1. Prerequisites

- Git
- PHP 8.3+ with PDO SQLite (or PDO MySQL)
- Composer
- Node.js and npm
- Python 3.11+ and pip (Task A only)
- A Groq API key for live LLM analysis

The PowerShell commands below assume SQLite for a local database. Never commit either environment file.

## 2. Common Laravel setup

Run from the repository root:

    composer install
    if (!(Test-Path .env)) { Copy-Item .env.example .env }
    php artisan key:generate
    npm install

For SQLite, set DB_CONNECTION=sqlite in root .env, then create the file if it does not exist:

    New-Item -ItemType File -Path database/database.sqlite -Force | Out-Null
    php artisan migrate
    php artisan migrate:status

Do not use php artisan migrate:fresh on a database with data you need. The Task A branch contains Task A migrations; coordinate before running migrations against a shared database.

Run Laravel and Vite in separate terminals:

    php artisan serve --host=127.0.0.1 --port=8000

    npm run dev

Open http://127.0.0.1:8000. On Task A, this now displays the FeedbackTrace dashboard instead of Laravel's default welcome page. The dashboard metrics are read from the local Laravel database.

## 3. Task A — Student Feedback Intelligence

### Get the branch

    git status
    git fetch origin
    git switch feature/task-a-feedback-intelligence
    git pull origin feature/task-a-feedback-intelligence

Commit or stash local changes before switching branches.

### Configure the AI service

From the repository root:

    cd ai-service
    python -m venv .venv
    .\.venv\Scripts\Activate.ps1
    python -m pip install --upgrade pip
    pip install -r requirements.txt
    if (!(Test-Path .env)) { Copy-Item .env.example .env }

If PowerShell blocks activation:

    Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
    .\.venv\Scripts\Activate.ps1

In ai-service/.env, configure at least:

    GROQ_API_KEY=your_groq_api_key
    GROQ_MODEL=openai/gpt-oss-20b
    EMBEDDING_MODEL=intfloat/multilingual-e5-base
    EMBEDDING_DEVICE=cpu
    EMBEDDING_BATCH_SIZE=16
    SERVICE_API_KEY=your_random_service_secret
    CORS_ORIGINS=http://127.0.0.1:8000,http://localhost:8000

In the root Laravel .env, configure:

    AI_SERVICE_URL=http://127.0.0.1:8001
    AI_SERVICE_KEY=the_same_value_as_SERVICE_API_KEY
    AI_SERVICE_TIMEOUT=30
    QUEUE_CONNECTION=database

The two service-key values must match exactly. Keep them secret. The embedding model downloads on first use.

Start FastAPI from inside ai-service:

    python -m uvicorn app.main:app --reload --host 127.0.0.1 --port 8001

Run one Uvicorn process only. Task A job state is currently held in that process's memory; do not use multiple workers.

### Start the queue worker

From the repository root, open another terminal:

    php artisan queue:work

The worker is required for Laravel to submit batches, poll FastAPI jobs, and persist results. Leave it running during analysis.

### Available endpoints

FastAPI:
- GET http://127.0.0.1:8001/health
- GET http://127.0.0.1:8001/api/v1/task-a/health
- POST http://127.0.0.1:8001/api/v1/task-a/analyze — start background analysis
- GET /api/v1/task-a/jobs/{job_id} — poll job status
- GET /api/v1/task-a/batches/{batch_id}/results — retrieve completed results
- GET /api/v1/task-a/batches/{batch_id}/summary — dashboard summary contract
- GET /api/v1/task-a/findings/{finding_id} — finding drilldown

The non-health Task A endpoints require X-Service-Key when SERVICE_API_KEY is set.

Laravel review API:
- POST /api/v1/task-a/findings/{finding_code}/review
- Requires an authenticated Laravel session and CSRF protection. The repository does not yet include a login screen or interactive review UI; this endpoint is available for integration and tests.

### Start a batch (temporary developer workflow)

Batch creation/import UI is not included yet. Once a FeedbackBatch and its FeedbackRecord rows exist, dispatch analysis through Tinker from the repository root:

    php artisan tinker

Then run:

    $batch = App\Models\TaskA\FeedbackBatch::where('batch_code', 'YOUR_BATCH_CODE')->firstOrFail();
    App\Jobs\TaskA\AnalyzeFeedbackBatch::start($batch);

Keep php artisan queue:work and FastAPI running. Check the batch status in the database/dashboard. The Python job store is in-memory; a FastAPI restart during an active job causes Laravel to mark that analysis as failed so it can be restarted. Laravel's database is the permanent record.

### Tests

From ai-service with the virtual environment activated:

    python -m pytest -q
    python -m pytest tests/task_a -q

From the repository root (after composer install):

    php artisan test

Live Groq tests are opt-in and need a valid API key. Do not share secrets in logs or commits.

## 4. Task B — Service Quality Decision Intelligence

### Get the branch

    git status
    git fetch origin
    git switch feature/task-b-decision-intelligence
    git pull origin feature/task-b-decision-intelligence

The currently inspected Task B branch contains the Laravel starter application only. Its IPA, document retrieval, RAG, recommendation pipeline, FastAPI module, and Task B routes have not yet been implemented on that branch.

Use Common Laravel setup above to run the Laravel app and Vite. Do not copy Task A's FastAPI or queue workflow and assume it starts Task B.

## 5. Troubleshooting

| Problem | Check |
|---|---|
| Laravel cannot find SQLite driver | Enable PHP pdo_sqlite or configure MySQL/pdo_mysql |
| Dashboard shows a database error | Confirm the Task A migrations have run on this branch |
| FastAPI returns 401 | Match Laravel AI_SERVICE_KEY and FastAPI SERVICE_API_KEY |
| Batch stays pending | Confirm php artisan queue:work is running |
| Laravel cannot connect to AI service | Confirm FastAPI is listening on port 8001 and AI_SERVICE_URL is correct |
| Job disappears after restart | FastAPI job state is in memory; re-dispatch the batch from Tinker |
| uvicorn not found | Activate ai-service/.venv or use python -m uvicorn |
| Port already in use | Stop the process occupying port 8000/8001 or update the configured URL |

## 6. Environment files

- Root .env: Laravel application, database, queue, and AI service connection settings.
- ai-service/.env: Groq, embedding, FastAPI service key, and CORS settings.

Never commit actual environment files or API credentials.
