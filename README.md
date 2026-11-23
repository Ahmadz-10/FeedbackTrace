# FeedbackTrace

**University feedback analytics and service-quality decision support.**

FeedbackTrace combines student feedback intelligence (Task A) with service-quality decision intelligence (Task B).

## Modules
- **Task A:** aspect extraction, aspect-level sentiment, reason extraction, evidence, consolidation, and staff approval. Task B receives approved findings only.
- **Task B:** survey analysis and Importance–Performance Analysis (IPA), university-document retrieval, RAG, and evidence-grounded recommendations.

## Architecture
Laravel / Blade / MySQL manages the application and persistence. Python / FastAPI hosts shared AI infrastructure and independent task pipelines.

## Stack
Laravel, Blade, MySQL, Python/FastAPI, Groq API, Sentence Transformers (`intfloat/multilingual-e5-base`). Embeddings are 768-dimensional normalized vectors; use consistent `query:` and `passage:` prefixes/model for indexing and retrieval.

## Start Laravel
`composer install`, configure root `.env`, `php artisan key:generate`, `php artisan migrate`, then `php artisan serve`. Run npm asset commands if applicable.

## Start AI service
From `ai-service/`, create and activate a Python virtual environment, install `requirements.txt`, copy `.env.example` to `.env`, configure keys, then run:
`uvicorn app.main:app --reload --host 127.0.0.1 --port 8001`

Health: `/health`; docs: `/docs`.

## Tests
AI: from `ai-service/`, `python -m pytest -q`. Laravel: `php artisan test`.

## Branches
`feature/shared-ai-service`, `feature/task-a-feedback-intelligence`, and `feature/task-b-decision-intelligence`. Merge shared infrastructure into main, then update task branches. Never commit secrets or `.env` files.