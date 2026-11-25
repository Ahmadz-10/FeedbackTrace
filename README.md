# FeedbackTrace

University feedback analytics and service-quality decision support.

FeedbackTrace is divided into two development tasks:
- **Task A — Student Feedback Intelligence:** feedback processing, aspect-level sentiment, evidence validation, finding consolidation, and staff review API.
- **Task B — Service Quality Decision Intelligence:** survey analysis/IPA, university-document retrieval, RAG, and evidence-grounded recommendations.

## Running locally

See **[Running FeedbackTrace — Task A and Task B](docs/development/run-locally.md)** for branch-specific setup, configuration, startup commands, queue worker, endpoints, tests, and troubleshooting.

## Task A status

The Task A branch includes:
- A database-backed FeedbackTrace dashboard at `/` with workspace metrics and recent feedback batches.
- FastAPI's asynchronous Task A analysis API, job status, results, summary, and finding-detail endpoints.
- A Laravel-to-FastAPI client, queued analysis job, transactional result persistence, and finding-review API.
- Review audit history and approval state, with an approved-finding data contract for the future Task B handoff.

Batch import and interactive staff review screens are not included yet. The review API currently requires an authenticated Laravel session; the repository does not yet include a login screen. FastAPI job state is in-memory and supports one Uvicorn process.

## Branches

- `feature/task-a-feedback-intelligence`
- `feature/task-b-decision-intelligence`

Keep work on the designated branch. Never commit `.env` files or API credentials.
