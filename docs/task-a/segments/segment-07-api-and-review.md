# Segment 07 — Job Processing, API, Persistence, and Review

**Branch:** `feature/task-a-feedback-intelligence`

## What this segment adds

| Area | Implementation |
|---|---|
| FastAPI jobs | In-memory job store, background analysis runner, polling status, bounded job history |
| FastAPI API | Start analysis, read job status, retrieve results, batch summary, finding detail |
| Response contract | Accepted-job response, finding detail, batch summary, proposed aspect label |
| Laravel AI client | Authenticated HTTP requests to the shared FastAPI service |
| Laravel queue job | Submit batch, poll status, retrieve completed results |
| Persistence | Transactional storage of analysis, mentions, evidence, findings, and supporting-feedback links |
| Staff review | Approve, edit, or reject findings and record review history |
| Dashboard | Read-only Task A overview at `/`, with database metrics and recent batch list |

## Local execution

Configure root Laravel `.env` with `AI_SERVICE_URL=http://127.0.0.1:8001`, `AI_SERVICE_KEY` matching FastAPI's `SERVICE_API_KEY`, `AI_SERVICE_TIMEOUT=30`, and `QUEUE_CONNECTION=database`.

Configure `ai-service/.env` with `SERVICE_API_KEY` and `GROQ_API_KEY`.

The keys must match. Run Laravel, Vite, FastAPI, and the Laravel queue worker in separate terminals. Apply migrations after pulling this branch with `php artisan migrate`.

## Endpoints

FastAPI:
- `POST /api/v1/task-a/analyze`
- `GET /api/v1/task-a/jobs/{job_id}`
- `GET /api/v1/task-a/batches/{batch_id}/results`
- `GET /api/v1/task-a/batches/{batch_id}/summary`
- `GET /api/v1/task-a/findings/{finding_id}`

Laravel review:
- `POST /api/v1/task-a/findings/{finding_code}/review`

All Task A FastAPI endpoints except health require `X-Service-Key` when configured. The Laravel review endpoint requires an authenticated session and CSRF protection.

## Important limits

- FastAPI job state is in-memory. Restarting FastAPI loses active job state; run only one Uvicorn worker.
- Laravel's database is the permanent record after results are persisted.
- The repository does not yet include a feedback import screen, login screen, or interactive finding-review screen. A developer can create a batch and records in the database and dispatch `AnalyzeFeedbackBatch::start($batch)` through Tinker.
- The review policy currently permits any authenticated user. Apply role checks when the application adds staff roles.
- Re-analysis is blocked after findings have been reviewed to protect staff decisions.

## Tests added

- Python: `ai-service/tests/task_a/test_api.py` and `test_contract_fixture.py`
- Laravel: `tests/Feature/TaskA/AiServiceClientTest.php`, `AnalyzeFeedbackBatchJobTest.php`, `AnalysisResultPersisterTest.php`, and `ReviewApiTest.php`
- Shared contract fixture: `tests/Feature/TaskA/fixtures/analyze_response.json`

The test files have been added to the branch, but have not been executed in this environment. Run `python -m pytest -q` inside `ai-service/` and `php artisan test` from the repository root after installing dependencies.
