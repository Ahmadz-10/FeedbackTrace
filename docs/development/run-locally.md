# Running FeedbackTrace — Task A and Task B

This guide explains how to check out and run each task on its own Git branch. **Task A and Task B are separate development branches.** Use the branch assigned to the task you are working on; do not assume files from one branch are already present on the other.

## 1. Prerequisites

Install:
- Git
- PHP 8.3+ and the PDO SQLite extension (or PDO MySQL if using MySQL)
- Composer
- Node.js and npm
- Python 3.11+ and pip (needed for Task A's AI service)
- A Groq API key for Groq-backed AI calls (not required just to start Laravel or check health endpoints)

The commands below use Windows PowerShell and SQLite for a local database. MySQL can be configured instead if your team shares a database.

## 2. Common Laravel setup (both tasks)

Run from the repository root, `FeedbackTrace/`.

```powershell
composer install
if (!(Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
npm install
```

For a local SQLite database, create the file and ensure root `.env` contains `DB_CONNECTION=sqlite`:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force | Out-Null
```

Run migrations for the branch you have checked out:

```powershell
php artisan migrate
php artisan migrate:status
```

Do not use `php artisan migrate:fresh` on a database containing work you need to keep; it drops existing tables.

Start Laravel in one terminal:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Start Vite in another terminal:

```powershell
npm run dev
```

Open http://127.0.0.1:8000. Laravel's health route is http://127.0.0.1:8000/up.

## 3. Task A — Student Feedback Intelligence

### Check out Task A

For an existing clone:

```powershell
git status
git fetch origin
git switch feature/task-a-feedback-intelligence
git pull origin feature/task-a-feedback-intelligence
```

For a fresh clone:

```powershell
git clone https://github.com/Ahmadz-10/FeedbackTrace.git
cd FeedbackTrace
git switch feature/task-a-feedback-intelligence
```

Commit or stash local changes before switching branches.

### Start the Task A AI service

Keep Laravel and Vite running as described above. Open a third terminal at the repository root:

```powershell
cd ai-service
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r requirements.txt
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

If PowerShell blocks virtual-environment activation:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\.venv\Scripts\Activate.ps1
```

Configure `ai-service/.env` as required:

```dotenv
GROQ_API_KEY=your_groq_api_key
GROQ_MODEL=openai/gpt-oss-20b
EMBEDDING_MODEL=intfloat/multilingual-e5-base
EMBEDDING_DEVICE=cpu
EMBEDDING_BATCH_SIZE=16
SERVICE_API_KEY=replace_with_a_long_random_secret
CORS_ORIGINS=http://127.0.0.1:8000,http://localhost:8000
```

Keep API keys private. Never commit either `.env` file. A Groq key is needed for Groq-backed requests. If `SERVICE_API_KEY` is set, protected shared POST endpoints require the `X-Service-Key` header. Embedding model weights download on first use.

From inside `ai-service/`, start FastAPI:

```powershell
uvicorn app.main:app --reload --host 127.0.0.1 --port 8001
```

Open:
- API documentation: http://127.0.0.1:8001/docs
- Shared health: http://127.0.0.1:8001/health
- Task A health: http://127.0.0.1:8001/api/v1/task-a/health

### Test Task A

From `ai-service/` with the virtual environment activated:

```powershell
python -m pytest -q
python -m pytest tests/task_a -q
```

From the repository root, run Laravel tests:

```powershell
php artisan test
```

**Task A implementation status:** Task A processing services and the module health route exist. The public `POST /api/v1/task-a/analyze` endpoint, Laravel-to-AI integration, result persistence flow, and staff review screens are not yet implemented. Starting the servers confirms the foundations only; it does not yet run the complete feedback-analysis workflow.

## 4. Task B — Service Quality Decision Intelligence

### Check out Task B

For an existing clone:

```powershell
git status
git fetch origin
git switch feature/task-b-decision-intelligence
git pull origin feature/task-b-decision-intelligence
```

For a fresh clone:

```powershell
git clone https://github.com/Ahmadz-10/FeedbackTrace.git
cd FeedbackTrace
git switch feature/task-b-decision-intelligence
```

Commit or stash local changes before switching branches.

### Start Task B

On the currently inspected Task B branch, the Laravel project is present, but the Python `ai-service/` directory and Task B API/pipeline are not present yet. Therefore, start the Laravel application using **Common Laravel setup (both tasks)** above:

- Terminal 1 — repository root: `php artisan serve --host=127.0.0.1 --port=8000`
- Terminal 2 — repository root: `npm run dev`

Run its Laravel tests from the repository root:

```powershell
php artisan test
```

Check Laravel health at http://127.0.0.1:8000/up.

**Task B implementation status:** the inspected branch currently contains the Laravel starter application, not the Task B IPA, document retrieval, RAG, or recommendation pipeline. There is no Task B FastAPI command or Task B endpoint to run on this branch yet. Once the Task B AI service and routes are added, this section must be updated with its actual environment variables, startup command, endpoints, and tests. Do not copy Task A's AI commands and assume they start Task B.

## 5. Which processes should be running?

| Process | Task A | Task B (current branch state) |
|---|---|---|
| Laravel web server | Yes — port 8000 | Yes — port 8000 |
| Vite frontend | Yes — npm run dev | Yes — npm run dev |
| Python FastAPI | Yes — port 8001 | Not available on inspected branch yet |
| Task-specific health endpoint | `/api/v1/task-a/health` | Not implemented |
| Task-specific test suite | `python -m pytest tests/task_a -q` | Not implemented on inspected branch |

To stop a process, press `Ctrl+C` in its terminal. To leave the Python virtual environment, run `deactivate`.

## 6. Troubleshooting

| Problem | What to check |
|---|---|
| `php` or `composer` not recognized | Install PHP/Composer, add them to PATH, and reopen PowerShell |
| PHP `could not find driver` | Enable `pdo_sqlite` or configure MySQL and enable `pdo_mysql` |
| SQLite database missing | Create `database/database.sqlite`; verify `DB_CONNECTION=sqlite` |
| Missing Laravel app key | Run `php artisan key:generate` |
| `uvicorn` not recognized | Activate Task A's `ai-service/.venv`; reinstall requirements; or run `python -m uvicorn app.main:app --reload --host 127.0.0.1 --port 8001` |
| Python cannot import `app` | Run Uvicorn from inside `ai-service/` |
| Groq configuration error | Check `GROQ_API_KEY` in `ai-service/.env` and restart FastAPI |
| Shared endpoint returns HTTP 401 | Send `X-Service-Key` matching `SERVICE_API_KEY` |
| Port already in use | Stop the other process or use a different port |

## 7. Environment files

- Root `.env`: Laravel app key, database, sessions, cache, queue, and app URL.
- `ai-service/.env`: Task A/shared AI Groq settings, embedding settings, service API key, and CORS origins (Task A branch).

Never commit `.env` files or API credentials. Keep Task A and Task B changes on their designated branches.
