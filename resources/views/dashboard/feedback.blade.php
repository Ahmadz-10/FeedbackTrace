@extends('layouts.feedbacktrace')

@section('page-title', 'Feedback')
@section('breadcrumb', 'Feedback')

@section('content')
<section class="ft-page-heading">
    <div><div class="ft-eyebrow">FEEDBACK MANAGEMENT</div><h1>Feedback</h1><p>Explore student comments and manage feedback collection batches.</p></div>
    <div class="ft-heading-actions">
        <button class="ft-button ft-button-secondary" type="button" data-toast="CSV export is a demo interaction.">⇩ Export</button>
        <button class="ft-button ft-button-primary" type="button" data-open-upload>＋ Import feedback</button>
    </div>
</section>

<section class="ft-kpi-grid ft-kpi-grid-three">
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">TOTAL RESPONSES</span><span class="ft-kpi-icon ft-kpi-blue">▤</span></div><div class="ft-kpi-value">2,486</div><div class="ft-kpi-foot"><span class="ft-trend-positive">↗ 12.8%</span><span>this semester</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">FEEDBACK BATCHES</span><span class="ft-kpi-icon ft-kpi-violet">▧</span></div><div class="ft-kpi-value">8</div><div class="ft-kpi-foot"><span class="ft-trend-neutral">3 sources</span><span>survey, form, portal</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">READY FOR ANALYSIS</span><span class="ft-kpi-icon ft-kpi-green">✓</span></div><div class="ft-kpi-value">2,312</div><div class="ft-kpi-foot"><span class="ft-trend-positive">93.0%</span><span>quality checked</span></div></article>
</section>

<section class="ft-panel ft-feedback-list-panel">
    <div class="ft-panel-header ft-panel-header-wrap">
        <div><div class="ft-panel-title">Feedback records</div><div class="ft-panel-subtitle">Sample comments with identifying details already redacted</div></div>
        <div class="ft-table-controls">
            <label class="ft-search-input"><span>⌕</span><input type="search" placeholder="Search comments..." aria-label="Search feedback" data-table-search="feedback-table"></label>
            <select class="ft-select" aria-label="Filter sentiment" data-table-filter="feedback-table" data-filter-field="sentiment"><option value="">All sentiments</option><option value="Positive">Positive</option><option value="Neutral">Neutral</option><option value="Negative">Negative</option></select>
            <select class="ft-select" aria-label="Filter status" data-table-filter="feedback-table" data-filter-field="status"><option value="">All statuses</option><option value="Analyzed">Analyzed</option><option value="Pending">Pending</option><option value="Skipped">Skipped</option></select>
        </div>
    </div>
    <div class="ft-table-wrap">
        <table class="ft-table" id="feedback-table">
            <thead><tr><th>FEEDBACK</th><th>SOURCE</th><th>ASPECT</th><th>SENTIMENT</th><th>STATUS</th><th>SUBMITTED</th></tr></thead>
            <tbody>
                <tr data-search="academic portal login access problem" data-sentiment="Negative" data-status="Analyzed"><td><strong>“The academic portal is often slow when I try to access...”</strong><small>FB-2026-2486 · ID redacted</small></td><td>Student survey</td><td>Digital services</td><td><span class="ft-sentiment-tag ft-sentiment-negative">Negative</span></td><td><span class="ft-status-pill ft-status-done">Analyzed</span></td><td>24 Nov 2026</td></tr>
                <tr data-search="lecturer helpful responsive clear explanation" data-sentiment="Positive" data-status="Analyzed"><td><strong>“The lecturer was helpful and explained the material clearly.”</strong><small>FB-2026-2485 · ID redacted</small></td><td>Course evaluation</td><td>Teaching quality</td><td><span class="ft-sentiment-tag ft-sentiment-positive">Positive</span></td><td><span class="ft-status-pill ft-status-done">Analyzed</span></td><td>24 Nov 2026</td></tr>
                <tr data-search="administration waiting registration document long queue" data-sentiment="Negative" data-status="Pending"><td><strong>“I had to wait too long to get my registration document.”</strong><small>FB-2026-2484 · ID redacted</small></td><td>Service desk</td><td>Administration</td><td><span class="ft-sentiment-tag ft-sentiment-negative">Negative</span></td><td><span class="ft-status-pill ft-status-review">Pending</span></td><td>23 Nov 2026</td></tr>
                <tr data-search="library space comfortable study facilities" data-sentiment="Positive" data-status="Analyzed"><td><strong>“The library has comfortable spaces for group study.”</strong><small>FB-2026-2483 · ID redacted</small></td><td>Student survey</td><td>Facilities</td><td><span class="ft-sentiment-tag ft-sentiment-positive">Positive</span></td><td><span class="ft-status-pill ft-status-done">Analyzed</span></td><td>23 Nov 2026</td></tr>
                <tr data-search="parking campus traffic neutral" data-sentiment="Neutral" data-status="Skipped"><td><strong>“Parking is usually available after 10 AM.”</strong><small>FB-2026-2482 · ID redacted</small></td><td>Student survey</td><td>Facilities</td><td><span class="ft-sentiment-tag ft-sentiment-neutral">Neutral</span></td><td><span class="ft-status-pill ft-status-muted">Skipped</span></td><td>22 Nov 2026</td></tr>
                <tr data-search="wifi internet connectivity connection campus" data-sentiment="Negative" data-status="Analyzed"><td><strong>“The Wi-Fi connection drops frequently in the main building.”</strong><small>FB-2026-2481 · ID redacted</small></td><td>Student survey</td><td>IT infrastructure</td><td><span class="ft-sentiment-tag ft-sentiment-negative">Negative</span></td><td><span class="ft-status-pill ft-status-done">Analyzed</span></td><td>22 Nov 2026</td></tr>
            </tbody>
        </table>
    </div>
    <div class="ft-table-footer"><span data-table-count>Showing 6 sample records</span><div class="ft-pagination"><button disabled>←</button><button class="is-current">1</button><button disabled>2</button><button disabled>3</button><button disabled>→</button></div></div>
</section>

<div class="ft-modal-backdrop" data-upload-modal hidden>
    <div class="ft-modal" role="dialog" aria-modal="true" aria-labelledby="upload-title">
        <div class="ft-modal-heading"><div><h2 id="upload-title">Import feedback</h2><p>Preview the CSV import workflow. This demo does not upload or store files.</p></div><button class="ft-icon-button" type="button" data-close-upload aria-label="Close">×</button></div>
        <label class="ft-upload-drop" for="feedback-file"><span class="ft-upload-icon">⇧</span><strong>Choose a CSV file</strong><span>or drag a file here · CSV format</span><input type="file" id="feedback-file" accept=".csv,text/csv" data-file-name></label>
        <div class="ft-file-name" data-selected-file>No file selected</div>
        <div class="ft-modal-actions"><button class="ft-button ft-button-secondary" type="button" data-close-upload>Cancel</button><button class="ft-button ft-button-primary" type="button" data-demo-import>Continue preview</button></div>
    </div>
</div>
@endsection
