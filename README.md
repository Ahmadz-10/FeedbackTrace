# FeedbackTrace

University feedback analytics and service-quality decision support.

FeedbackTrace is divided into two development tasks:
- **Task A — Student Feedback Intelligence:** feedback processing, aspect-level sentiment, evidence validation, and finding consolidation.
- **Task B — Service Quality Decision Intelligence:** survey analysis/IPA, university-document retrieval, RAG, and evidence-grounded recommendations.

## Running locally

Use **[Running FeedbackTrace — Task A and Task B](docs/development/run-locally.md)** for branch-specific instructions, prerequisites, environment setup, migrations, startup commands, tests, and troubleshooting.

The tasks use separate branches:
- `feature/task-a-feedback-intelligence`
- `feature/task-b-decision-intelligence`

**Important:** the current Task A branch has the shared FastAPI service and Task A processing modules, but not yet the public analysis endpoint or Laravel integration. The currently inspected Task B branch contains the Laravel starter application; its Task B AI pipeline/API has not yet been added. The run guide describes exactly which processes are available on each branch.

Never commit `.env` files or API credentials.
