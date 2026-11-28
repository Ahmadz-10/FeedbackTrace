# FeedbackTrace — Node.js edition

University feedback intelligence and service-quality decision support.

The `main` branch is the active Node.js / TypeScript implementation used for development and demonstration. The original Laravel/Python implementation is preserved separately.

## Stack

- Web: Next.js 15, React 19 and TypeScript
- API: NestJS 11 and TypeScript
- Database: PostgreSQL 17 and Prisma ORM 7
- Background jobs: Redis 7 and BullMQ
- AI inference: Groq TypeScript SDK
- Embeddings: Transformers.js with multilingual E5
- Shared contracts: TypeScript and Zod

Node.js 22.16+ is required. NestJS 11 is used in this migration snapshot to keep the Nest/BullMQ stack aligned. Prisma ORM 7 is intentionally pinned to its stable major line; Prisma 8 is currently a release-candidate line in the source check.

## Workspaces

- apps/web — staff dashboard, CSV upload and finding review
- apps/api — NestJS API, authentication, Task A and Task B services
- apps/api worker entry point — BullMQ processing
- packages/contracts — shared request schemas, enums and taxonomy
- packages/database — Prisma schema and PostgreSQL migrations

## Quick start (PowerShell)

1. Install Node.js 22.16 or later and Docker Desktop.
2. Copy the root environment template:

    Copy-Item .env.example .env

3. Set a strong JWT_SECRET, a Groq API key, and the database and Redis URLs in .env.
4. Start PostgreSQL and Redis:

    docker compose up -d

5. Install dependencies and generate Prisma Client:

    npm install
    npm run db:generate
    npm run db:migrate

6. Set STAFF_EMAIL, STAFF_PASSWORD (at least 12 characters) and optional STAFF_NAME in the root .env, then run:

    npm run db:seed:staff

7. Start the API, worker and web UI in three separate PowerShell terminals:

    npm run dev:api

    npm run dev:worker

    npm run dev:web

Open http://localhost:3000. API health is at http://localhost:8001/health.

For the full setup guide, see [docs/nodejs/run-locally.md](docs/nodejs/run-locally.md).

## Main API routes

- POST /api/v1/auth/login and GET /api/v1/auth/me
- GET /api/v1/task-a/batches and POST /api/v1/task-a/batches (multipart CSV import)
- GET /api/v1/task-a/batches/:id, GET /api/v1/task-a/batches/:id/results, GET /api/v1/task-a/batches/:id/summary
- GET /api/v1/task-a/findings, GET /api/v1/task-a/findings/:code, POST /api/v1/task-a/findings/:code/review
- POST /api/v1/task-b/calculate-ipa and POST /api/v1/task-b/validate-findings

Except public sign-in and root health, routes require a JWT bearer token and staff/admin role.

## Task A processing

CSV import creates a batch and queues analyze-batch to BullMQ. A separate worker calls Groq, validates the JSON response, checks exact evidence quotations against the stored source comment, records model/prompt/pipeline metadata, consolidates by aspect and reason category, preserves minority views and single-feedback findings, and persists records through Prisma.

Direct identifiers covered by the initial privacy filter (email, labelled NIM/student ID, common phone-number patterns) are masked before a comment is sent to Groq. Original feedback remains in PostgreSQL for traceability. This filter is conservative and must be complemented with a dataset-specific privacy review.

## Task B

The Node implementation contains request validation for approved Task A findings and IPA calculations using mean importance/performance values and average cutoffs. University-document upload, retrieval/RAG recommendation generation, and the full Task B persistence flow are not yet migrated.

## Evaluation

Task A evaluation utilities are in apps/api/src/task-a/evaluation: keyword aspect baseline, TF-IDF logistic-regression sentiment baseline, precision/recall/Macro-F1/confusion matrix, evidence validity, pairwise consolidation metrics, ablation comparison and error-case summary.

A manually annotated dataset and a documented train/dev/test split are still required before reporting research-quality performance numbers. No benchmark metrics are claimed by this branch yet.

## Build and tests

    npm run build
    npm test

The build compiles shared contracts and database package before API and web. Tests currently cover IPA rules, evaluation metrics and privacy filtering. A local PostgreSQL + Redis integration run is still required before production cutover.

## Branch workflow

- `main` is the Node.js baseline and should receive completed Node.js work.
- `feature/task-a-feedback-intelligence-nodejs` is the Node.js Task A development branch.
- `feature/task-b-decision-intelligence-nodejs` is the Node.js Task B development branch.
- `legacy/laravel-python` preserves the original Laravel/Python implementation.
- The existing `feature/task-a-feedback-intelligence` and `feature/task-b-decision-intelligence` branches are preserved unchanged as historical Laravel/Python task branches. Do not use them as the base for new Node.js work.
- `feature/nodejs-migration` is retained as the migration snapshot; new Node.js development should start from `main` or the Node.js Task A/Task B branches.

Never commit .env, credentials, personal student data or private university documents.
