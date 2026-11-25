# Shared Dashboard UI Preview

**Branch:** `develop`

This is a shared UI prototype so contributors can review the product interface without checking out Task A or installing its Python service.

## Page map

| Page | Route | Preview content |
|---|---|---|
| Dashboard | `/` | Workspace KPIs, feedback trend, sentiment distribution, service priorities, recent activity |
| Feedback | `/feedback` | Sample feedback records, search, sentiment/status filters, import dialog preview |
| Task A Analysis | `/task-a` | Sentiment by aspect, aspect frequency, consolidated findings, evidence excerpts, local review-state demo |
| Task B Analysis | `/task-b` | IPA quadrant matrix, selectable attributes, importance/performance/gap scores, priority ranking, source references |
| Documents | `/documents` | Sample document repository, search and type filtering, upload-selection preview |
| Recommendations | `/recommendations` | Evidence-linked recommendation cards, status filters, local approve/reject preview |

## Design direction

The interface combines:
- Metabase-style KPI cards, charts, analytical tables, and compact filters.
- Tableau-style data hierarchy and an interactive Importance–Performance Matrix.
- Linear-style dark sidebar, grouped navigation, compact labels, and consistent spacing.

The palette is dark navy for navigation, white panels on an off-white canvas, muted blue/indigo accents, Inter/system typography, and compact status indicators. Layout is desktop-first with tablet/mobile navigation and responsive grids.

## Demo mode: expected behaviour

All visible values and excerpts are illustrative. The interface does not call a Python or Laravel analysis API and does not require Task A models, migrations, queue workers, service keys, or environment variables.

The following interactions are local UI demonstrations only:
- Search and filter visible sample tables.
- Select an IPA attribute to update the detail panel.
- Open/close import and evidence dialogs.
- Select a local file to see its name (the file is not uploaded).
- Change review/recommendation badges for the current page view (the decision is not persisted).

The dashboard is intentionally backend-independent so Task B contributors can run it on `develop`. Real Task A and Task B API integration can replace the sample data later without splitting the shared design.
