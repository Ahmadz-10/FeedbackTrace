@extends('layouts.feedbacktrace')

@section('page-title', 'Task A Analysis')
@section('breadcrumb', 'Task A Analysis')

@section('content')
<section class="ft-page-heading">
    <div><div class="ft-eyebrow">FEEDBACK INTELLIGENCE <span class="ft-eyebrow-divider">/</span> NLP ANALYSIS</div><h1>Task A Analysis</h1><p>Understand what students say, how they feel, and the reasons behind their feedback.</p></div>
    <div class="ft-heading-actions"><select class="ft-select" aria-label="Analysis batch"><option>All feedback batches</option><option>Semester 1 · 2026</option><option>Student services · Nov 2026</option></select><button class="ft-button ft-button-secondary" type="button" data-toast="Analysis export is a demo interaction.">⇩ Export analysis</button></div>
</section>

<section class="ft-kpi-grid">
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">ANALYZED FEEDBACK</span><span class="ft-kpi-icon ft-kpi-blue">▤</span></div><div class="ft-kpi-value">2,312</div><div class="ft-kpi-foot"><span class="ft-trend-positive">93.0%</span><span>of total records</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">ASPECTS IDENTIFIED</span><span class="ft-kpi-icon ft-kpi-violet">◉</span></div><div class="ft-kpi-value">18</div><div class="ft-kpi-foot"><span class="ft-trend-neutral">6 categories</span><span>service themes</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">CONSOLIDATED FINDINGS</span><span class="ft-kpi-icon ft-kpi-amber">☷</span></div><div class="ft-kpi-value">46</div><div class="ft-kpi-foot"><span class="ft-trend-neutral">12 pending</span><span>staff review</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">EVIDENCE VALIDITY</span><span class="ft-kpi-icon ft-kpi-green">✓</span></div><div class="ft-kpi-value">98.6<span class="ft-kpi-unit">%</span></div><div class="ft-kpi-foot"><span class="ft-trend-positive">Verified quotes</span><span>source-linked</span></div></article>
</section>

<section class="ft-two-col-grid">
    <article class="ft-panel">
        <div class="ft-panel-header"><div><div class="ft-panel-title">Sentiment by aspect</div><div class="ft-panel-subtitle">Share of analyzed mentions in each service area</div></div><span class="ft-quiet-chip">2,312 records</span></div>
        <div class="ft-stacked-chart">
            <div class="ft-stack-row"><div class="ft-stack-label"><strong>Teaching quality</strong><small>684 mentions</small></div><div class="ft-stack-track"><span class="ft-stack-positive" style="width:74%"></span><span class="ft-stack-neutral" style="width:18%"></span><span class="ft-stack-negative" style="width:8%"></span></div><b>74%</b></div>
            <div class="ft-stack-row"><div class="ft-stack-label"><strong>Campus facilities</strong><small>512 mentions</small></div><div class="ft-stack-track"><span class="ft-stack-positive" style="width:63%"></span><span class="ft-stack-neutral" style="width:22%"></span><span class="ft-stack-negative" style="width:15%"></span></div><b>63%</b></div>
            <div class="ft-stack-row"><div class="ft-stack-label"><strong>Digital services</strong><small>428 mentions</small></div><div class="ft-stack-track"><span class="ft-stack-positive" style="width:42%"></span><span class="ft-stack-neutral" style="width:25%"></span><span class="ft-stack-negative" style="width:33%"></span></div><b>42%</b></div>
            <div class="ft-stack-row"><div class="ft-stack-label"><strong>Administration</strong><small>386 mentions</small></div><div class="ft-stack-track"><span class="ft-stack-positive" style="width:48%"></span><span class="ft-stack-neutral" style="width:21%"></span><span class="ft-stack-negative" style="width:31%"></span></div><b>48%</b></div>
            <div class="ft-stack-row"><div class="ft-stack-label"><strong>Student support</strong><small>302 mentions</small></div><div class="ft-stack-track"><span class="ft-stack-positive" style="width:81%"></span><span class="ft-stack-neutral" style="width:13%"></span><span class="ft-stack-negative" style="width:6%"></span></div><b>81%</b></div>
        </div>
        <div class="ft-chart-legend ft-chart-legend-bottom"><span><i class="ft-legend-dot ft-dot-positive"></i>Positive</span><span><i class="ft-legend-dot ft-dot-neutral"></i>Neutral</span><span><i class="ft-legend-dot ft-dot-negative"></i>Negative</span></div>
    </article>

    <article class="ft-panel">
        <div class="ft-panel-header"><div><div class="ft-panel-title">Aspect frequency</div><div class="ft-panel-subtitle">Most discussed topics in student feedback</div></div><a href="{{ route('dashboard.feedback') }}" class="ft-text-link">View feedback →</a></div>
        <div class="ft-aspect-bars">
            <div class="ft-aspect-bar-row"><div><span>Teaching quality</span><strong>684</strong></div><div class="ft-mini-track"><i style="width:100%"></i></div></div>
            <div class="ft-aspect-bar-row"><div><span>Campus facilities</span><strong>512</strong></div><div class="ft-mini-track"><i style="width:75%"></i></div></div>
            <div class="ft-aspect-bar-row"><div><span>Digital services</span><strong>428</strong></div><div class="ft-mini-track"><i style="width:63%"></i></div></div>
            <div class="ft-aspect-bar-row"><div><span>Administration</span><strong>386</strong></div><div class="ft-mini-track"><i style="width:56%"></i></div></div>
            <div class="ft-aspect-bar-row"><div><span>Student support</span><strong>302</strong></div><div class="ft-mini-track"><i style="width:44%"></i></div></div>
        </div>
    </article>
</section>

<section class="ft-panel ft-findings-panel">
    <div class="ft-panel-header ft-panel-header-wrap"><div><div class="ft-panel-title">Consolidated findings</div><div class="ft-panel-subtitle">Themes grouped from validated feedback evidence</div></div>
        <div class="ft-table-controls"><label class="ft-search-input"><span>⌕</span><input type="search" placeholder="Search findings..." data-table-search="findings-table"></label><select class="ft-select" data-table-filter="findings-table" data-filter-field="priority" aria-label="Filter priority"><option value="">All priorities</option><option>High</option><option>Medium</option></select></div>
    </div>
    <div class="ft-table-wrap"><table class="ft-table" id="findings-table">
        <thead><tr><th>FINDING</th><th>ASPECT</th><th>SUPPORT</th><th>TYPE</th><th>PRIORITY</th><th>REVIEW</th><th></th></tr></thead>
        <tbody>
            <tr data-search="academic portal frequent login errors digital services" data-priority="High"><td><strong>Frequent login and access issues in the academic portal</strong><small>F-2026-014 · 18 supporting comments</small></td><td>Digital services</td><td>18 comments</td><td><span class="ft-sentiment-tag ft-sentiment-negative">Weakness</span></td><td><span class="ft-priority-tag ft-tag-high">High</span></td><td><span class="ft-review-status" data-review-status>Pending</span></td><td><button class="ft-small-button" type="button" data-open-evidence>View evidence</button></td></tr>
            <tr data-search="administrative response slow paperwork queue" data-priority="High"><td><strong>Long waiting times for administrative requests</strong><small>F-2026-012 · 32 supporting comments</small></td><td>Administration</td><td>32 comments</td><td><span class="ft-sentiment-tag ft-sentiment-negative">Weakness</span></td><td><span class="ft-priority-tag ft-tag-high">High</span></td><td><span class="ft-review-status">Approved</span></td><td><button class="ft-small-button" type="button" data-open-evidence>View evidence</button></td></tr>
            <tr data-search="library study spaces clean comfortable" data-priority="Medium"><td><strong>Positive feedback on library study spaces</strong><small>F-2026-009 · 41 supporting comments</small></td><td>Facilities</td><td>41 comments</td><td><span class="ft-sentiment-tag ft-sentiment-positive">Strength</span></td><td><span class="ft-priority-tag ft-tag-medium">Medium</span></td><td><span class="ft-review-status">Approved</span></td><td><button class="ft-small-button" type="button" data-open-evidence>View evidence</button></td></tr>
        </tbody>
    </table></div>
    <div class="ft-table-footer"><span data-table-count>Showing 3 sample findings</span><span class="ft-demo-footnote">Review actions are UI-only in this preview.</span></div>
</section>

<div class="ft-modal-backdrop" data-evidence-modal hidden>
    <div class="ft-modal ft-evidence-modal" role="dialog" aria-modal="true" aria-labelledby="evidence-title">
        <div class="ft-modal-heading"><div><div class="ft-eyebrow">FINDING EVIDENCE · F-2026-014</div><h2 id="evidence-title">Frequent login and access issues</h2><p>Supporting excerpts from anonymized student feedback.</p></div><button class="ft-icon-button" type="button" data-close-evidence aria-label="Close">×</button></div>
        <div class="ft-evidence-list">
            <blockquote>“I often have to refresh the academic portal several times before I can access my schedule.”<cite>Feedback FB-2026-2486 · Digital services · Negative</cite></blockquote>
            <blockquote>“The portal gets slow around registration deadlines, and sometimes it logs me out.”<cite>Feedback FB-2026-2401 · Digital services · Negative</cite></blockquote>
            <blockquote>“Once I get in, the portal is useful. Access is the main problem.”<cite>Feedback FB-2026-2388 · Digital services · Mixed view</cite></blockquote>
        </div>
        <div class="ft-evidence-actions"><button class="ft-button ft-button-secondary" type="button" data-close-evidence>Close</button><button class="ft-button ft-button-primary" type="button" data-demo-review="Approved">✓ Approve finding</button></div>
    </div>
</div>
@endsection
