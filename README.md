# FeedbackTrace

**University feedback analytics and service-quality decision support.**

FeedbackTrace combines student feedback intelligence (Task A) with service-quality decision intelligence (Task B).

## Modules
- **Task A:** aspect extraction, aspect-level sentiment, reason extraction, evidence validation, consolidation, and staff approval.
- **Task B:** survey analysis and Importance–Performance Analysis (IPA), university-document retrieval, RAG, and evidence-grounded recommendations.

## Architecture
Laravel / Blade / MySQL manages the web application and persistence. Python / FastAPI hosts shared AI infrastructure and independent task pipelines.

## Stack
Laravel 13, Blade, PHP 8.3+, MySQL or SQLite, Python / FastAPI, Groq API, and Sentence Transformers (`intfloat/multilingual-e5-base`).

## Run locally

See **[Running FeedbackTrace Locally](docs/development/run-locally.md)** for prerequisites, branch setup, environment configuration, database migrations, Laravel/Vite startup, FastAPI startup, tests, and troubleshooting.

Quick start:
- Laravel: `composer install`, configure root `.env`, run `php artisan migrate`, then `php artisan serve`.
- Frontend assets: `npm install` then `npm run dev`.
- AI service: from `ai-service/`, create/activate a Python virtual environment, install `requirements.txt`, configure `.env`, then run `uvicorn app.main:app --reload --host 127.0.0.1 --port 8001`.

## Current Task A API status

The shared AI service and Task A module health endpoint are available:
- `GET /health`
- `GET /api/v1/task-a/health`
- Shared endpoints: `POST /api/v1/shared/chat` and `POST /api/v1/shared/embeddings`

The Task A `POST /api/v1/task-a/analyze` endpoint and Laravel integration/persistence flow are still under development. Starting the services does not yet expose a complete feedback-analysis workflow.

## Tests

- AI service: from `ai-service/`, run `python -m pytest -q`.
- Laravel: from the repository root, run `php artisan test`.

## Branches

- `feature/shared-ai-service`
- `feature/task-a-feedback-intelligence`
- `feature/task-b-decision-intelligence`

Keep Task A and Task B changes on their designated branches. Never commit secrets or `.env` files.
