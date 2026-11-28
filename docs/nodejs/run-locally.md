# Running the FeedbackTrace Node.js edition

All commands below use PowerShell from the repository root.

## Prerequisites

- Node.js 22.16+
- npm 10+
- Docker Desktop
- Groq API key for live feedback analysis

## Environment

    Copy-Item .env.example .env

Set a long random JWT_SECRET, GROQ_API_KEY, STAFF_EMAIL and STAFF_PASSWORD. Keep secrets in the root .env; do not commit it.

## Database and queue

    docker compose up -d
    npm install
    npm run db:generate
    npm run db:migrate
    npm run db:seed:staff

Prisma CLI configuration loads the root .env file through the workspace-relative path. The first migration creates the Task A PostgreSQL schema.

## Run three processes

Terminal 1:

    npm run dev:api

Terminal 2:

    npm run dev:worker

Terminal 3:

    npm run dev:web

Web: http://localhost:3000

API health: http://localhost:8001/health

## Tests and production build

    npm test
    npm run build

To apply checked-in migrations in deployment, run npm run db:deploy after building the database workspace. Do not use migrate dev as a production migration command.
