# Running FeedbackTrace Locally

This guide starts the Laravel application and the Python/FastAPI service side by side. It is written for the current `feature/task-b-decision-intelligence` branch.

> **Current implementation status:** the Laravel project currently has its welcome page and Laravel's `/up` health route. The AI service has `/health`, `/api/v1/task-b/health`, the Task B IPA calculation endpoint, and the shared chat/embedding endpoints. **The Laravel-to-AI persistence/UI integration for Task B is still under active development.** Starting both servers verifies the foundations and allows testing the Task B Decision Intelligence endpoints via Swagger UI.

## 1. Prerequisites

Install these tools before starting:

| Tool | Requirement / purpose |
|---|---|
| Git | Clone and switch to the Task B branch |
| PHP | PHP 8.3 or later, with the PDO SQLite extension for the default database |
| Composer | Install Laravel dependencies |
| Node.js and npm | Install and run Vite assets |
| Python | Python 3.11 recommended for the AI service |
| pip | Install Python packages |
| SQLite | Default local database; included as a PHP PDO driver or separate CLI tool depending on your installation |
| Groq API key | Needed only when calling endpoints that use the Groq model |

For a shared development database, MySQL is also supported by the Laravel configuration. The steps below use SQLite so a new developer can start without setting up a database server.

## 2. Get the Task B branch

Open PowerShell (Windows) or a terminal (macOS/Linux).

For an existing clone:

```powershell
cd C:\path\to\FeedbackTrace
git status
git fetch origin
git switch feature/task-b-decision-intelligence
git pull origin feature/task-b-decision-intelligence
```

For a fresh clone:

```powershell
git clone [https://github.com/Ahmadz-10/FeedbackTrace.git](https://github.com/Ahmadz-10/FeedbackTrace.git)
cd FeedbackTrace
git switch feature/task-b-decision-intelligence
```

Before switching branches, commit or stash any uncommitted work shown by `git status`.

## 3. Configure and start Laravel

Run these commands from the repository root (`FeedbackTrace/`).

### Install PHP dependencies and create the environment file

```powershell
composer install
if (!(Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
```

### Prepare the local SQLite database

The root `.env.example` defaults to SQLite. Create the empty database file:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force | Out-Null
```

Confirm that the following settings are present in the root `.env` file:

```dotenv
DB_CONNECTION=sqlite
```

The configured SQLite path defaults to `database/database.sqlite`. You do not need to set a database username or password for SQLite.

### Run migrations

```powershell
php artisan migrate
php artisan migrate:status
```

This creates the Laravel base tables and the feedback/decision intelligence schema tables.

**Important:** do not run `php artisan migrate:fresh` on a shared or existing database. It drops existing tables and data. Use it only on a disposable local database that you are prepared to reset.

### Install frontend packages and start Vite

```powershell
npm install
npm run dev
```

Keep this terminal open while developing so Vite can serve assets and refresh changes.

### Start the Laravel web server

Open a second terminal at the repository root:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Open:

- Laravel app: http://127.0.0.1:8000
- Laravel health check: http://127.0.0.1:8000/up

At this stage, the root URL displays the Laravel welcome page.

## 4. Configure and start the Python AI service

Open another terminal. The commands below assume the current directory is the repository root.

### Create and activate a virtual environment

```powershell
cd ai-service
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install --upgrade pip
pip install -r requirements.txt
```

If PowerShell blocks activation for this terminal, use:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\.venv\Scripts\Activate.ps1
```

### Create the AI service environment file

```powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

Open `ai-service/.env` and set the credentials as needed:

```dotenv
GROQ_API_KEY=your_groq_api_key
GROQ_MODEL=openai/gpt-oss-20b
EMBEDDING_MODEL=intfloat/multilingual-e5-base
EMBEDDING_DEVICE=cpu
EMBEDDING_BATCH_SIZE=16
SERVICE_API_KEY=replace_with_a_long_random_secret
CORS_ORIGINS=[http://127.0.0.1:8000](http://127.0.0.1:8000),http://localhost:8000
```

- Keep the Groq key private. Never commit `ai-service/.env`.
- A Groq key is needed for the shared chat endpoint and LLM-assisted reasoning.
- `SERVICE_API_KEY` is optional for local testing. When it is set, protected shared endpoints require the `X-Service-Key` request header. The health endpoints remain public.
- The embedding model weights are downloaded on first use of the embeddings endpoint. The initial download can be large.
- The Task B Decision Intelligence (IPA) services compute Importance-Performance Analysis metrics and quadrant classifications locally.

### Start FastAPI

From inside `ai-service/` with the virtual environment activated:

```powershell
uvicorn app.main:app --reload --host 127.0.0.1 --port 8001
```

Open:

- FastAPI interactive docs: http://127.0.0.1:8001/docs
- AI service health: http://127.0.0.1:8001/health
- Task B module health: http://127.0.0.1:8001/api/v1/task-b/health

## 5. Confirm the services are running

With both servers running, use a separate PowerShell window:

```powershell
Invoke-RestMethod [http://127.0.0.1:8000/up](http://127.0.0.1:8000/up)
Invoke-RestMethod [http://127.0.0.1:8001/health](http://127.0.0.1:8001/health)
Invoke-RestMethod [http://127.0.0.1:8001/api/v1/task-b/health](http://127.0.0.1:8001/api/v1/task-b/health)
```

Expected key values:

- Laravel `/up`: successful health response (HTTP 200).
- AI service `/health`: `status = ok`.
- Task B `/api/v1/task-b/health`: `status = ok`, `module = task_b`.

The current available AI endpoints are:

| Method | Path | Purpose |
|---|---|---|
| GET | `/health` | Shared AI service health |
| GET | `/api/v1/task-b/health` | Task B module health |
| POST | `/api/v1/task-b/calculate-ipa` | Compute IPA scores and quadrant classifications |
| POST | `/api/v1/shared/chat` | Shared Groq chat request |
| POST | `/api/v1/shared/embeddings` | Shared multilingual embedding request |

The shared POST endpoints are protected only when a non-empty `SERVICE_API_KEY` is configured. Use the interactive docs at `/docs` to inspect request schemas and test Task B endpoints.

## 6. Run tests

### Python AI service tests

From `ai-service/` with the virtual environment activated:

```powershell
python -m pytest -q
```

To run only Task B tests:

```powershell
python -m pytest tests/task_b -q
```

Live Groq tests are opt-in and are skipped unless `RUN_LIVE_LLM_TESTS=1` and a valid `GROQ_API_KEY` are set.

### Laravel tests

From the repository root:

```powershell
php artisan test
```

If the Laravel tests cannot start, confirm that `composer install` completed and the `vendor/` directory exists.

## 7. Everyday development: which terminal runs what?

Keep these processes in separate terminals:

| Terminal | Directory | Command |
|---|---|---|
| 1 — Laravel | repository root | `php artisan serve --host=127.0.0.1 --port=8000` |
| 2 — Vite | repository root | `npm run dev` |
| 3 — FastAPI | `ai-service/`, venv activated | `uvicorn app.main:app --reload --host 127.0.0.1 --port 8001` |

To stop a server, focus its terminal and press `Ctrl+C`. To leave the Python virtual environment, run `deactivate`.

## 8. Common problems

| Problem | What to check |
|---|---|
| `php` or `composer` is not recognized | Install PHP/Composer and reopen the terminal; confirm both are on PATH |
| `could not find driver` | Enable/install PHP's `pdo_sqlite` extension, or configure MySQL and enable `pdo_mysql` |
| SQLite database file not found | Create `database/database.sqlite` and confirm `DB_CONNECTION=sqlite` |
| `No application encryption key` | Run `php artisan key:generate` from the repository root |
| `uvicorn` is not recognized | Activate the AI service virtual environment and reinstall `requirements.txt`; alternatively run `python -m uvicorn app.main:app --reload --host 127.0.0.1 --port 8001` |
| Import error for `app` | Run Uvicorn from inside `ai-service/`, not from the repository root |
| Groq returns a configuration error | Check `GROQ_API_KEY` in `ai-service/.env`, then restart Uvicorn |
| Shared endpoint returns HTTP 401 | Send the exact `X-Service-Key` matching `SERVICE_API_KEY` in `ai-service/.env` |
| Port 8000 or 8001 is already in use | Stop the other process or choose another port and update the relevant URL/CORS setting |

## 9. Environment files and secrets

There are two separate environment files:

- `FeedbackTrace/.env` — Laravel app key, database, sessions, cache, queue, and app URL.
- `FeedbackTrace/ai-service/.env` — Groq key/model, embedding settings, service API key, and CORS origins.

Do not copy the contents of one environment file into the other, and do not commit either `.env` file. Commit changes to the corresponding `.env.example` only when a new non-secret setting is introduced.