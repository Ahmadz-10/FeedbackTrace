<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101828">
    <title>Dashboard · {{ config('app.name', 'FeedbackTrace') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ft-body">
<div class="ft-shell">
    <aside class="ft-sidebar">
        <a class="ft-brand" href="{{ route('dashboard') }}" aria-label="FeedbackTrace dashboard">
            <span class="ft-brand-mark">F</span>
            <span class="ft-brand-copy"><strong>FeedbackTrace</strong><small>STUDENT INSIGHTS</small></span>
        </a>

        <div class="ft-nav-label">WORKSPACE</div>
        <nav class="ft-nav" aria-label="Main navigation">
            <a class="ft-nav-link is-active" href="{{ route('dashboard') }}">
                <span class="ft-nav-icon">▦</span><span>Overview</span>
            </a>
            <a class="ft-nav-link" href="#batches">
                <span class="ft-nav-icon">▤</span><span>Feedback batches</span>
            </a>
            <a class="ft-nav-link" href="#review-queue">
                <span class="ft-nav-icon">☷</span><span>Review queue</span>
                <span class="ft-nav-count">{{ $pendingFindings }}</span>
            </a>
        </nav>

        <div class="ft-sidebar-bottom">
            <div class="ft-sidebar-status"><span class="ft-status-dot"></span><span>Task A workspace</span></div>
            <p>Student Feedback Intelligence</p>
            <small>FeedbackTrace · Development</small>
        </div>
    </aside>

    <main class="ft-main">
        <header class="ft-topbar">
            <div class="ft-breadcrumb">Workspace <span>/</span> <strong>Overview</strong></div>
            <a class="ft-api-link" href="http://127.0.0.1:8001/docs" target="_blank" rel="noopener noreferrer">
                <span class="ft-api-dot"></span> API documentation <span aria-hidden="true">↗</span>
            </a>
        </header>

        <div class="ft-content">
            <section class="ft-page-heading">
                <div>
                    <div class="ft-eyebrow">STUDENT FEEDBACK INTELLIGENCE</div>
                    <h1>Dashboard</h1>
                    <p>Monitor feedback processing and review consolidated findings.</p>
                </div>
                <div class="ft-live-pill"><span></span> {{ $databaseReady ? "Live database overview" : "Waiting for database setup" }}</div>
            </section>

            @unless ($databaseReady)
                <div class="ft-db-notice" role="status">
                    <strong>Database setup is not complete.</strong>
                    <span>Configure the root .env file and run <code>php artisan migrate</code> from the FeedbackTrace project folder. Dashboard counts will appear once the Task A tables are available.</span>
                </div>
            @endunless

            <section class="ft-stat-grid" aria-label="Workspace statistics">
                <article class="ft-stat-card">
                    <div class="ft-stat-top"><span class="ft-stat-icon ft-icon-blue">▤</span><span class="ft-stat-caption">ALL BATCHES</span></div>
                    <div class="ft-stat-value">{{ number_format($totalBatches) }}</div>
                    <div class="ft-stat-description">{{ number_format($processingBatches) }} currently processing</div>
                </article>
                <article class="ft-stat-card">
                    <div class="ft-stat-top"><span class="ft-stat-icon ft-icon-violet">▧</span><span class="ft-stat-caption">FEEDBACK RECORDS</span></div>
                    <div class="ft-stat-value">{{ number_format($totalFeedback) }}</div>
                    <div class="ft-stat-description">{{ number_format($processedFeedback) }} analyzed or skipped</div>
                </article>
                <article class="ft-stat-card" id="review-queue">
                    <div class="ft-stat-top"><span class="ft-stat-icon ft-icon-amber">◷</span><span class="ft-stat-caption">PENDING REVIEW</span></div>
                    <div class="ft-stat-value">{{ number_format($pendingFindings) }}</div>
                    <div class="ft-stat-description">Findings awaiting staff decision</div>
                </article>
                <article class="ft-stat-card">
                    <div class="ft-stat-top"><span class="ft-stat-icon ft-icon-green">✓</span><span class="ft-stat-caption">APPROVED FINDINGS</span></div>
                    <div class="ft-stat-value">{{ number_format($approvedFindings) }}</div>
                    <div class="ft-stat-description">Available for approved handoff</div>
                </article>
            </section>

            <section class="ft-section-grid">
                <article class="ft-panel ft-batch-panel" id="batches">
                    <div class="ft-panel-heading">
                        <div><h2>Recent feedback batches</h2><p>Latest batches stored in this workspace</p></div>
                        <span class="ft-panel-tag">{{ number_format($completedBatches) }} completed</span>
                    </div>

                    @if ($latestBatches->isEmpty())
                        <div class="ft-empty-state">
                            <div class="ft-empty-icon">▤</div>
                            <strong>No feedback batches yet</strong>
                            <p>Once feedback batches are imported, their processing status and findings will appear here.</p>
                        </div>
                    @else
                        <div class="ft-table-wrap">
                            <table class="ft-table">
                                <thead><tr><th>BATCH</th><th>STATUS</th><th>FEEDBACK</th><th>FINDINGS</th><th>CREATED</th></tr></thead>
                                <tbody>
                                @foreach ($latestBatches as $batch)
                                    <tr>
                                        <td><strong>{{ $batch->title ?: $batch->batch_code }}</strong><small>{{ $batch->batch_code }}</small></td>
                                        <td><span class="ft-status ft-status-{{ str_replace('_', '-', $batch->status) }}">{{ str_replace('_', ' ', $batch->status) }}</span></td>
                                        <td>{{ number_format($batch->records_count) }}</td>
                                        <td>{{ number_format($batch->findings_count) }}</td>
                                        <td class="ft-date">{{ $batch->created_at?->format('d M Y') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </article>

                <aside class="ft-panel ft-pipeline-panel">
                    <div class="ft-panel-heading"><div><h2>Task A pipeline</h2><p>Current processing components</p></div></div>
                    <ol class="ft-pipeline">
                        <li class="is-ready"><span class="ft-step-mark">✓</span><div><strong>Input validation</strong><small>Text checks and quality flags</small></div><span class="ft-step-state">READY</span></li>
                        <li class="is-ready"><span class="ft-step-mark">✓</span><div><strong>Feedback analysis</strong><small>Aspect and sentiment extraction</small></div><span class="ft-step-state">READY</span></li>
                        <li class="is-ready"><span class="ft-step-mark">✓</span><div><strong>Evidence validation</strong><small>Quote and reference checks</small></div><span class="ft-step-state">READY</span></li>
                        <li class="is-ready"><span class="ft-step-mark">✓</span><div><strong>Finding consolidation</strong><small>Grouped findings and minority views</small></div><span class="ft-step-state">READY</span></li>
                        <li class="is-next"><span class="ft-step-mark">5</span><div><strong>Staff review</strong><small>Approve, edit, or reject findings</small></div><span class="ft-step-state">API READY</span></li>
                    </ol>
                    <div class="ft-note"><strong>Current scope</strong><p>This dashboard is an overview. Feedback import and interactive staff review screens are separate UI work and are not exposed here yet.</p></div>
                </aside>
            </section>

            <footer class="ft-footer">
                <span>FeedbackTrace <span>·</span> Task A</span>
                <span>Student feedback data stays in the Laravel database.</span>
            </footer>
        </div>
    </main>
</div>
</body>
</html>
