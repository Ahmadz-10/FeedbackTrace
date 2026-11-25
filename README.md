# FeedbackTrace

University feedback analytics and service-quality decision support.

FeedbackTrace is divided into two development tasks:
- **Task A — Student Feedback Intelligence:** feedback processing, aspect-level sentiment, evidence validation, finding consolidation, and staff review API.
- **Task B — Service Quality Decision Intelligence:** survey analysis/IPA, university-document retrieval, RAG, and evidence-grounded recommendations.

## Running locally

See **[Running FeedbackTrace — Task A and Task B](docs/development/run-locally.md)** for branch-specific setup, configuration, startup commands, queue worker, endpoints, tests, and troubleshooting.

## Task A status

The Task A branch now includes:
- A FeedbackTrace dashboard at / with live counts and recent feedback batches from Laravel's database.
- FastAPI's asynchronous Task A analysis API, job status, results, summary, and finding-detail endpoints.
- Laravel-to-FastAPI client, queued analysis job, transactional result persistence, and finding-review API.
- Review audit history and approval state; only approved findings can be exported to Task B through the approved model contract.

Batch import and the interactive feedback/review screens are still separate UI work. The review API currently requires an authenticated Laravel session; the repository does not yet include a login screen. FastAPI job state is in-memory and supports one Uvicorn process.

## Branches

- feature/task-a-feedback-intelligence
- feature/task-b-decision-intelligence

Keep work on the designated branch. Never commit .env files or API credentials.
