# FeedbackTrace

University feedback analytics and service-quality decision support.

FeedbackTrace uses one shared administrative dashboard for two modules:
- **Task A — Student Feedback Intelligence:** feedback management, aspect-level sentiment, evidence-backed findings, and staff review.
- **Task B — Service Quality Decision Intelligence:** survey processing, Importance–Performance Analysis (IPA), university document retrieval, and evidence-grounded recommendations.

## Shared dashboard preview

The shared UI prototype is available on the `develop` branch. It runs on the Laravel application without Task A's Python AI service or Task A database migrations.

Pages included:
- Dashboard overview
- Feedback records
- Task A analysis
- Task B analysis (IPA matrix and priority ranking)
- University documents
- Recommendations

The prototype uses clearly labelled illustrative sample data. Search, table filters, the IPA point selector, navigation, dialogs, and local review-state interactions are available for UI testing. File uploads, analysis, persistence, document indexing, and actual approvals are not connected in preview mode.

## Run the shared dashboard

From the repository root:

    composer install
    if (!(Test-Path .env)) { Copy-Item .env.example .env }
    php artisan key:generate

For the default SQLite setup, ensure `DB_CONNECTION=sqlite` is set in `.env`, then:

    New-Item -ItemType File -Path database/database.sqlite -Force | Out-Null
    php artisan migrate

Install and build frontend assets:

    npm install
    npm run build
    php artisan serve

Open http://127.0.0.1:8000.

No FastAPI setup, Groq API key, queue worker, or Task A migration is required to preview these pages.

For Vite hot reload during development, use `npm run dev` in a separate terminal instead of `npm run build`.

Do not run `php artisan migrate:fresh` on a database with data you need.

## Branch workflow

Use `develop` as the shared integration branch for the dashboard and cross-task UI. Keep Task A and Task B implementation work on their respective feature branches, then integrate their APIs and real data into the shared pages through reviewed pull requests.

- `feature/task-a-feedback-intelligence`
- `feature/task-b-decision-intelligence`

Do not commit real `.env` files or API credentials.
