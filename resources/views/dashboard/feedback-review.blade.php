@extends('layouts.feedbacktrace')

@section('page-title', 'Feedback Review')
@section('breadcrumb', 'Feedback Review')

@section('content')
<section class="ft-page-heading">
    <div><div class="ft-eyebrow">STAFF QUALITY CONTROL</div><h1>Feedback Review</h1><p>Inspect AI-generated findings, check their supporting evidence, and record a staff decision.</p></div>
    <div class="ft-heading-actions"><span class="ft-quiet-chip">UI PREVIEW · SAMPLE DATA</span></div>
</section>

<section class="ft-kpi-grid">
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">PENDING REVIEW</span><span class="ft-kpi-icon ft-kpi-amber">◷</span></div><div class="ft-kpi-value">12</div><div class="ft-kpi-foot"><span class="ft-trend-neutral">Requires staff attention</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">APPROVED</span><span class="ft-kpi-icon ft-kpi-green">✓</span></div><div class="ft-kpi-value">31</div><div class="ft-kpi-foot"><span class="ft-trend-positive">Eligible for Task B</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">REJECTED</span><span class="ft-kpi-icon ft-kpi-violet">×</span></div><div class="ft-kpi-value">3</div><div class="ft-kpi-foot"><span class="ft-trend-neutral">Excluded from handoff</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">EVIDENCE CHECK</span><span class="ft-kpi-icon ft-kpi-blue">⌕</span></div><div class="ft-kpi-value">98.6<span class="ft-kpi-unit">%</span></div><div class="ft-kpi-foot"><span class="ft-trend-positive">Sample quote validation</span></div></article>
</section>

<section class="ft-panel">
    <div class="ft-panel-header ft-panel-header-wrap">
        <div><div class="ft-panel-title">Finding awaiting review</div><div class="ft-panel-subtitle">FND-2026-018 · Academic administration · Generated from sample student comments</div></div>
        <span class="ft-status-pill ft-status-review" data-review-status>Pending review</span>
    </div>
    <div class="ft-two-col-grid">
        <div>
            <div class="ft-insight-kicker">CONSOLIDATED FINDING</div>
            <h3>Students receive inconsistent information from different service counters.</h3>
            <p class="ft-rec-card-content">Multiple students report receiving different instructions when asking about the same academic administration process. The issue appears across several feedback entries and should be checked against the current service procedure.</p>
            <div class="ft-rec-meta"><span>Aspect: Information consistency</span><i></i><span>12 feedback records</span><i></i><span>9 negative mentions</span></div>
            <div class="ft-heading-actions" style="margin-top:18px">
                <button class="ft-button ft-button-secondary" type="button" data-open-evidence>View evidence</button>
                <button class="ft-button ft-button-primary" type="button" data-demo-review="Approved">Approve finding</button>
                <button class="ft-button ft-button-secondary" type="button" data-demo-review="Rejected">Reject</button>
            </div>
            <p class="ft-panel-subtitle" style="margin-top:12px">These buttons change only the sample status in this browser view. They do not write to Laravel.</p>
        </div>
        <div class="ft-rec-evidence">
            <strong>REVIEW CHECKLIST</strong>
            <p>✓ Finding title describes a specific issue</p>
            <p>✓ Aspect label matches the feedback theme</p>
            <p>✓ Supporting quotes are present</p>
            <p>✓ Minority views are retained</p>
            <p>○ Staff confirms relevance to the institution</p>
            <div class="ft-insight-kicker" style="margin-top:16px">AI SUMMARY</div>
            <p>The finding is a candidate for inclusion in service-quality analysis after staff approval. Task B should consume approved findings only.</p>
        </div>
    </div>
</section>

<div class="ft-modal-backdrop" data-evidence-modal hidden>
    <div class="ft-modal" role="dialog" aria-modal="true" aria-labelledby="evidence-title">
        <div class="ft-panel-header"><div><div class="ft-panel-title" id="evidence-title">Supporting evidence</div><div class="ft-panel-subtitle">Illustrative excerpts from sample feedback</div></div><button class="ft-icon-button" type="button" data-close-evidence aria-label="Close">×</button></div>
        <blockquote>“I was told to submit the form at one counter, but the other counter said the process was different.”</blockquote>
        <p class="ft-panel-subtitle">Feedback FB-2041 · Original wording shown for review · Demo excerpt</p>
        <blockquote>“The information on the portal does not match what the staff explained.”</blockquote>
        <p class="ft-panel-subtitle">Feedback FB-2088 · Demo excerpt</p>
        <div class="ft-heading-actions"><button class="ft-button ft-button-secondary" type="button" data-close-evidence>Close evidence</button><button class="ft-button ft-button-primary" type="button" data-demo-review="Approved">Approve finding</button></div>
    </div>
</div>
@endsection
