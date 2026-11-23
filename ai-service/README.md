# FeedbackTrace AI Service

FastAPI shared AI foundation for the Laravel FeedbackTrace app and its Task A / Task B pipelines.

## Setup
Python 3.11 recommended. From this directory:

`python -m venv .venv`

Activate it, install dependencies with `pip install -r requirements.txt`, and copy `.env.example` to `.env`. Set Groq API credentials and a strong optional service key.

## Run
`uvicorn app.main:app --reload --host 127.0.0.1 --port 8001`

Docs at `/docs`; health at `/health`.

## Shared endpoints
- POST `/api/v1/shared/chat` — smoke test LLM client
- POST `/api/v1/shared/embeddings` — multilingual E5 vector generation

Set `X-Service-Key` header when a service key is configured. Task pipelines should have distinct schemas/prompts and consume approved Task A findings.
