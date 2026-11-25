# Task A — Student Feedback Intelligence

Task A contains the Python services for feedback validation, preprocessing, PII redaction, aspect-level analysis, evidence validation, retries, and findings consolidation.

## Current status
- Task A router is registered at prefix `/api/v1/task-a`.
- Available route: `GET /api/v1/task-a/health`.
- The `POST /api/v1/task-a/analyze` endpoint, Laravel-to-AI request integration, result persistence, and staff review screens are not yet implemented.
- Reuse shared components from `app.shared`; do not duplicate the Groq or embedding clients.

## Run
Follow [Running FeedbackTrace Locally](../../../docs/development/run-locally.md). Start the AI service from the `ai-service/` directory using Uvicorn.

## Test
From `ai-service/` with its virtual environment activated:

```powershell
python -m pytest tests/task_a -q
```
