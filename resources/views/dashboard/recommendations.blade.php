@extends('layouts.feedbacktrace')

@section('page-title', 'Recommendations')
@section('breadcrumb', 'Recommendations')

@section('content')
<section class="ft-page-heading">
    <div><div class="ft-eyebrow">DECISION SUPPORT</div><h1>Recommendations</h1><p>Review evidence-grounded improvement actions and record a staff decision.</p></div>
    <div class="ft-heading-actions"><select class="ft-select" aria-label="Recommendation owner"><option>All departments</option><option>Academic Office</option><option>IT Services</option><option>Facilities</option></select><button class="ft-button ft-button-secondary" type="button" data-toast="Recommendation export is a demo interaction.">⇩ Export list</button></div>
</section>

<section class="ft-kpi-grid">
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">TOTAL SUGGESTIONS</span><span class="ft-kpi-icon ft-kpi-blue">✳</span></div><div class="ft-kpi-value">12</div><div class="ft-kpi-foot"><span class="ft-trend-neutral">Across 5 service areas</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">HIGH PRIORITY</span><span class="ft-kpi-icon ft-kpi-amber">⚑</span></div><div class="ft-kpi-value">4</div><div class="ft-kpi-foot"><span class="ft-trend-negative">Needs attention</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">APPROVED ACTIONS</span><span class="ft-kpi-icon ft-kpi-green">✓</span></div><div class="ft-kpi-value" data-approved-count>5</div><div class="ft-kpi-foot"><span class="ft-trend-positive">Ready for planning</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">AWAITING REVIEW</span><span class="ft-kpi-icon ft-kpi-violet">◷</span></div><div class="ft-kpi-value" data-pending-count>7</div><div class="ft-kpi-foot"><span class="ft-trend-neutral">Staff decision required</span></div></article>
</section>

<section class="ft-recommendation-toolbar">
    <div class="ft-filter-tabs" role="group" aria-label="Recommendation status filter">
        <button class="is-active" type="button" data-rec-filter="All">All <span>12</span></button>
        <button type="button" data-rec-filter="Pending">Pending <span>7</span></button>
        <button type="button" data-rec-filter="Approved">Approved <span>5</span></button>
    </div>
    <label class="ft-search-input"><span>⌕</span><input type="search" placeholder="Search recommendations..." data-rec-search></label>
</section>

<section class="ft-recommendation-list" data-recommendation-list>
    <article class="ft-recommendation-card" data-rec-status="Pending" data-search="improve academic portal reliability digital access uptime">
        <div class="ft-rec-card-top"><div class="ft-rec-tags"><span class="ft-priority-tag ft-tag-high">High priority</span><span class="ft-module-label ft-module-b">IPA + RAG</span><span class="ft-rec-id">REC-2026-012</span></div><span class="ft-status-pill ft-status-review" data-rec-state>Pending review</span></div>
        <div class="ft-rec-card-content"><div><h3>Improve academic portal reliability during peak periods</h3><p>Prioritize system capacity and uptime improvements ahead of registration periods. Provide a clear incident status page for students.</p><div class="ft-rec-meta"><span>Owner: IT Services</span><i></i><span>Effort: Medium</span><i></i><span>Impact: High</span></div></div>
            <div class="ft-rec-evidence"><strong>WHY THIS IS RECOMMENDED</strong><p>High importance–performance gap (−1.24), supported by 18 approved student feedback findings.</p><a href="{{ route('dashboard.documents') }}">2 policy references ↗</a></div></div>
        <div class="ft-rec-card-bottom"><span class="ft-rec-source">Sources: Task A Finding F-2026-014 · Digital Transformation Plan</span><div class="ft-rec-actions"><button class="ft-button ft-button-secondary ft-button-small" type="button" data-rec-action="Rejected">Reject</button><button class="ft-button ft-button-primary ft-button-small" type="button" data-rec-action="Approved">✓ Approve</button></div></div>
    </article>
    <article class="ft-recommendation-card" data-rec-status="Pending" data-search="streamline administrative document processing response time">
        <div class="ft-rec-card-top"><div class="ft-rec-tags"><span class="ft-priority-tag ft-tag-high">High priority</span><span class="ft-module-label ft-module-b">IPA + RAG</span><span class="ft-rec-id">REC-2026-011</span></div><span class="ft-status-pill ft-status-review" data-rec-state>Pending review</span></div>
        <div class="ft-rec-card-content"><div><h3>Streamline administrative document processing</h3><p>Introduce request tracking, standard processing timelines, and automated status updates for common student administration requests.</p><div class="ft-rec-meta"><span>Owner: Academic Office</span><i></i><span>Effort: Medium</span><i></i><span>Impact: High</span></div></div>
            <div class="ft-rec-evidence"><strong>WHY THIS IS RECOMMENDED</strong><p>Gap score of −0.96 and a recurring delay theme across 32 approved feedback comments.</p><a href="{{ route('dashboard.documents') }}">1 policy reference ↗</a></div></div>
        <div class="ft-rec-card-bottom"><span class="ft-rec-source">Sources: Task A Finding F-2026-012 · Academic Service Standards</span><div class="ft-rec-actions"><button class="ft-button ft-button-secondary ft-button-small" type="button" data-rec-action="Rejected">Reject</button><button class="ft-button ft-button-primary ft-button-small" type="button" data-rec-action="Approved">✓ Approve</button></div></div>
    </article>
    <article class="ft-recommendation-card" data-rec-status="Approved" data-search="increase study space availability library campus facilities">
        <div class="ft-rec-card-top"><div class="ft-rec-tags"><span class="ft-priority-tag ft-tag-medium">Medium priority</span><span class="ft-module-label ft-module-b">IPA + RAG</span><span class="ft-rec-id">REC-2026-009</span></div><span class="ft-status-pill ft-status-done" data-rec-state>Approved</span></div>
        <div class="ft-rec-card-content"><div><h3>Increase access to collaborative study spaces</h3><p>Review room booking availability and extend access to group study areas during high-demand periods.</p><div class="ft-rec-meta"><span>Owner: Facilities</span><i></i><span>Effort: Low</span><i></i><span>Impact: Medium</span></div></div>
            <div class="ft-rec-evidence"><strong>WHY THIS IS RECOMMENDED</strong><p>Frequently mentioned in feedback, with positive performance overall but room for targeted capacity improvements.</p><a href="{{ route('dashboard.documents') }}">1 policy reference ↗</a></div></div>
        <div class="ft-rec-card-bottom"><span class="ft-rec-source">Sources: Task A Finding F-2026-009 · Campus Facility Guidelines</span><div class="ft-rec-actions"><button class="ft-button ft-button-secondary ft-button-small" type="button" data-rec-action="Pending">Return to review</button></div></div>
    </article>
</section>
<p class="ft-demo-footnote ft-rec-note">This is a UI prototype. Approvals and rejections change the displayed demo state only; no recommendation is stored or sent to a backend.</p>
@endsection
