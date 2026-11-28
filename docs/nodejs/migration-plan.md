# FeedbackTrace Node.js migration plan

## Objective

Replace the active Laravel + Python runtime with a TypeScript monorepo while preserving the Task A / Task B responsibility split and existing data contracts.

## Segment map

| Segment | Migration work | Current implementation |
|---|---|---|
| 00 | Node workspace, TypeScript, Docker dependencies, shared contracts | Started |
| 01 | NestJS API, config, health, Groq and embedding services | Implemented; startup check pending |
| 02 | Prisma schema, PostgreSQL relations, seed account | Schema and SQL migration added; migrate/validate pending |
| 03 | CSV import, field checks and privacy filtering | Implemented; dataset-specific privacy review pending |
| 04 | Groq extraction of aspects, sentiment and reasons | Implemented as first port; prompt parity/manual comparison pending |
| 05 | JSON response validation and exact evidence matching | Zod parsing and substring matching implemented; parity cases pending |
| 06 | Deterministic consolidation, support counts and minority views | Initial deterministic port; compare against Python output |
| 07 | BullMQ jobs, Nest API, persistence and review history | Implemented; PostgreSQL/Redis runtime tests pending |
| 08 | Next.js staff sign-in, batch import, findings list and review | Initial interface implemented; feedback detail/evidence explorer and dynamic dashboard need extension |
| 09 | Baselines, metrics, evidence and consolidation evaluation | Initial TypeScript utilities/tests added; annotated dataset/report pending |
| 10 | End-to-end test, approved Task A to Task B handoff, docs and demo | Pending |

## Runtime boundaries

- Web: apps/web
- HTTP API: apps/api/src/main.ts
- Background worker: apps/api/src/main.worker.ts
- Shared inference clients: apps/api/src/shared
- Shared validation/taxonomy: packages/contracts
- Database models/migrations: packages/database

## Required parity checks

1. Keep the original comment text immutable in the database.
2. Compare taxonomy codes, reason categories and status values with the former Python service.
3. Ensure every valid evidence quote is an exact substring of the original text.
4. Preserve singleton findings and opposing minority views.
5. Compute support counts in code, not by trusting LLM-generated counts.
6. Only export findings whose status is approved.
7. Compare Groq model ID, prompt version, schema version and pipeline version on every migrated run.
8. Compare Node and Python outputs on the same manually annotated fixture before retiring the legacy service.

## Cutover gate

Do not replace main with this branch until npm install and full build succeed in CI; the Prisma migration runs on a clean PostgreSQL database; staff sign-in, CSV upload, worker analysis and review pass an end-to-end test; evidence/privacy checks pass; the annotated evaluation report documents Node/Python parity; and the demonstration can run from a clean setup.
