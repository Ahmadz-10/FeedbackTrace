# FeedbackTrace

**University feedback analytics and service-quality decision support.**

FeedbackTrace combines two task-specific modules on a shared Laravel application and FastAPI AI service.

## Modules

- **Task A — Student Feedback Intelligence:** aspect extraction, aspect-level sentiment, reason extraction, evidence validation, finding consolidation, batch processing, Laravel persistence, and staff review API.
- **Task B — Service Quality Decision Intelligence:** survey validation, Importance–Performance Analysis (IPA), quadrant classification, and validation of approved Task A findings before downstream use.

The shared AI service provides Groq chat and multilingual E5 embeddings. Task A and Task B keep separate schemas, API routes, and task-specific processing logic.

## Technology stack

Laravel 13, Blade, PHP 8.3+, SQLite/MySQL, Python/FastAPI, Groq API, and Sentence Transformers (`intfloat/multilingual-e5-base`).

## Running locally

See [Running FeedbackTrace Locally](docs/development/run-locally.md) for setup, environment configuration, migrations, startup commands, queue worker, API endpoints, tests, and troubleshooting.

## API modules

**Shared:** `GET /health`, `POST /api/v1/shared/chat`, `POST /api/v1/shared/embeddings`.

**Task A:** `GET /api/v1/task-a/health`, `POST /api/v1/task-a/analyze`, `GET /api/v1/task-a/jobs/{job_id}`, `GET /api/v1/task-a/batches/{batch_id}/results`, `GET /api/v1/task-a/batches/{batch_id}/summary`, `GET /api/v1/task-a/findings/{finding_id}`, and Laravel review at `POST /api/v1/task-a/findings/{finding_code}/review`.

**Task B:** `GET /api/v1/task-b/health`, `POST /api/v1/task-b/calculate-ipa`, and `POST /api/v1/task-b/validate-findings`.

## Current limitations

- FastAPI Task A job state is in-memory; run one Uvicorn worker and keep the service running while jobs are active.
- The Laravel queue worker is required for Task A batch submission, polling, and persistence.
- The review API requires an authenticated Laravel session. A complete login flow and interactive finding-review screen are not yet connected.
- Task B currently implements survey validation, IPA calculation/quadrants, and the approved-finding input contract. University-document ingestion, retrieval/RAG recommendation generation, and persistent Laravel-to-Task-B integration remain future work.
- Parts of the shared dashboard still display clearly labelled sample/demo data.

## Tests

From `ai-service/`: `python -m pytest tests/task_a tests/task_b -q`.

From the repository root: `php artisan test` and `npm run build`.

## Branch strategy

- `main` — shared, integrated, stable application
- `feature/task-a-feedback-intelligence` — Task A development
- `feature/task-b-decision-intelligence` — Task B development

Never commit real `.env` files, API credentials, passwords, or private student feedback.
