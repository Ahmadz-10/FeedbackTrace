@extends('layouts.feedbacktrace')

@section('page-title', 'Task B Analysis')
@section('breadcrumb', 'Task B Analysis')

@section('content')
<section class="ft-page-heading">
    <div><div class="ft-eyebrow">SERVICE QUALITY INTELLIGENCE <span class="ft-eyebrow-divider">/</span> IMPORTANCE–PERFORMANCE ANALYSIS</div><h1>Task B Analysis</h1><p>Prioritize service improvements using survey scores, supporting documents, and approved feedback findings.</p></div>
    <div class="ft-heading-actions"><select class="ft-select" aria-label="Survey period"><option>Semester 1 · 2026</option><option>Semester 2 · 2025</option></select><button class="ft-button ft-button-secondary" type="button" data-toast="IPA report export is a demo interaction.">⇩ Export IPA</button></div>
</section>

<section class="ft-kpi-grid">
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">SURVEY RESPONSES</span><span class="ft-kpi-icon ft-kpi-blue">▤</span></div><div class="ft-kpi-value">1,248</div><div class="ft-kpi-foot"><span class="ft-trend-positive">89.4%</span><span>completion rate</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">AVG. IMPORTANCE</span><span class="ft-kpi-icon ft-kpi-violet">◎</span></div><div class="ft-kpi-value">4.38<span class="ft-kpi-unit">/ 5</span></div><div class="ft-kpi-foot"><span class="ft-trend-neutral">High</span><span>student expectation</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">AVG. PERFORMANCE</span><span class="ft-kpi-icon ft-kpi-green">⌁</span></div><div class="ft-kpi-value">3.62<span class="ft-kpi-unit">/ 5</span></div><div class="ft-kpi-foot"><span class="ft-trend-neutral">Room to improve</span><span>perceived service</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">AVERAGE GAP</span><span class="ft-kpi-icon ft-kpi-amber">↕</span></div><div class="ft-kpi-value">−0.76</div><div class="ft-kpi-foot"><span class="ft-trend-negative">Importance exceeds performance</span></div></article>
</section>

<section class="ft-ipa-grid">
    <article class="ft-panel ft-ipa-panel">
        <div class="ft-panel-header"><div><div class="ft-panel-title">Importance–Performance Matrix</div><div class="ft-panel-subtitle">Click an attribute to inspect its score and priority</div></div><span class="ft-quiet-chip">4 quadrants</span></div>
        <div class="ft-ipa-plot-wrap">
            <div class="ft-ipa-y-title">IMPORTANCE <span>HIGH ↑</span></div>
            <div class="ft-ipa-plot">
                <div class="ft-ipa-quadrant ft-q-improve"><strong>CONCENTRATE HERE</strong><small>High importance · Low performance</small></div>
                <div class="ft-ipa-quadrant ft-q-maintain"><strong>KEEP UP THE GOOD WORK</strong><small>High importance · High performance</small></div>
                <div class="ft-ipa-quadrant ft-q-low"><strong>LOW PRIORITY</strong><small>Low importance · Low performance</small></div>
                <div class="ft-ipa-quadrant ft-q-over"><strong>POSSIBLE OVERKILL</strong><small>Low importance · High performance</small></div>
                <div class="ft-ipa-cross-v"></div><div class="ft-ipa-cross-h"></div>
                <button class="ft-ipa-point ft-point-one" type="button" data-ipa-point data-name="Digital academic services" data-importance="4.72" data-performance="3.48" data-gap="-1.24"><span></span><b>Digital services</b></button>
                <button class="ft-ipa-point ft-point-two" type="button" data-ipa-point data-name="Administrative response time" data-importance="4.66" data-performance="3.70" data-gap="-0.96"><span></span><b>Administration</b></button>
                <button class="ft-ipa-point ft-point-three" type="button" data-ipa-point data-name="Campus facilities" data-importance="4.41" data-performance="3.79" data-gap="-0.62"><span></span><b>Facilities</b></button>
                <button class="ft-ipa-point ft-point-four" type="button" data-ipa-point data-name="Teaching quality" data-importance="4.74" data-performance="4.33" data-gap="-0.41"><span></span><b>Teaching</b></button>
                <button class="ft-ipa-point ft-point-five" type="button" data-ipa-point data-name="Student support" data-importance="3.82" data-performance="4.12" data-gap="+0.30"><span></span><b>Student support</b></button>
                <button class="ft-ipa-point ft-point-six" type="button" data-ipa-point data-name="Library facilities" data-importance="3.54" data-performance="4.35" data-gap="+0.81"><span></span><b>Library</b></button>
            </div>
            <div class="ft-ipa-x-title">PERFORMANCE <span>LOW → HIGH</span></div>
        </div>
        <div class="ft-chart-legend ft-chart-legend-bottom"><span><i class="ft-legend-dot ft-dot-negative"></i>Improvement priority</span><span><i class="ft-legend-dot ft-dot-positive"></i>Performing well</span><span><i class="ft-legend-dot ft-dot-neutral"></i>Lower priority</span></div>
    </article>

    <aside class="ft-panel ft-ipa-detail-panel">
        <div class="ft-panel-header"><div><div class="ft-panel-title">Selected attribute</div><div class="ft-panel-subtitle">Details from the IPA matrix</div></div><span class="ft-priority-tag ft-tag-high" data-ipa-priority>High priority</span></div>
        <div class="ft-selected-attribute"><div class="ft-selected-icon">↗</div><h3 data-ipa-name>Digital academic services</h3><p>Students expect digital academic services to be accessible and reliable.</p></div>
        <div class="ft-score-grid">
            <div><small>IMPORTANCE</small><strong data-ipa-importance>4.72</strong></div>
            <div><small>PERFORMANCE</small><strong data-ipa-performance>3.48</strong></div>
            <div><small>GAP SCORE</small><strong class="ft-score-negative" data-ipa-gap>−1.24</strong></div>
        </div>
        <div class="ft-ipa-summary"><strong>Interpretation</strong><p data-ipa-interpretation>High importance but lower performance. This attribute should receive focused improvement attention.</p></div>
        <a class="ft-text-link" href="{{ route('dashboard.recommendations') }}">View related recommendations →</a>
    </aside>
</section>

<section class="ft-panel">
    <div class="ft-panel-header ft-panel-header-wrap"><div><div class="ft-panel-title">Improvement priority ranking</div><div class="ft-panel-subtitle">Attributes ranked by importance–performance gap</div></div><span class="ft-quiet-chip">Sample survey dataset</span></div>
    <div class="ft-table-wrap"><table class="ft-table">
        <thead><tr><th>RANK</th><th>SERVICE ATTRIBUTE</th><th>IMPORTANCE</th><th>PERFORMANCE</th><th>GAP</th><th>QUADRANT</th></tr></thead>
        <tbody>
            <tr><td>01</td><td><strong>Digital academic services</strong><small>Portal availability and access</small></td><td>4.72</td><td>3.48</td><td class="ft-score-negative">−1.24</td><td><span class="ft-priority-tag ft-tag-high">Concentrate here</span></td></tr>
            <tr><td>02</td><td><strong>Administrative response time</strong><small>Request and document processing</small></td><td>4.66</td><td>3.70</td><td class="ft-score-negative">−0.96</td><td><span class="ft-priority-tag ft-tag-high">Concentrate here</span></td></tr>
            <tr><td>03</td><td><strong>Campus facilities</strong><small>Learning spaces and facilities</small></td><td>4.41</td><td>3.79</td><td class="ft-score-negative">−0.62</td><td><span class="ft-priority-tag ft-tag-medium">Concentrate here</span></td></tr>
            <tr><td>04</td><td><strong>Teaching quality</strong><small>Teaching delivery and feedback</small></td><td>4.74</td><td>4.33</td><td class="ft-score-negative">−0.41</td><td><span class="ft-priority-tag ft-tag-good">Keep it up</span></td></tr>
            <tr><td>05</td><td><strong>Student support</strong><small>Advising and wellbeing support</small></td><td>3.82</td><td>4.12</td><td class="ft-score-positive">+0.30</td><td><span class="ft-priority-tag ft-tag-good">Keep it up</span></td></tr>
        </tbody>
    </table></div>
</section>

<section class="ft-two-col-grid">
    <article class="ft-panel"><div class="ft-panel-header"><div><div class="ft-panel-title">Evidence connected to priorities</div><div class="ft-panel-subtitle">Approved Task A findings supporting Task B interpretation</div></div><span class="ft-module-label ft-module-a">Task A handoff</span></div>
        <div class="ft-evidence-compact"><div><strong>Portal access failures during peak periods</strong><p>18 approved feedback comments support this finding.</p></div><span class="ft-status-pill ft-status-done">Approved</span></div>
        <div class="ft-evidence-compact"><div><strong>Slow administrative document processing</strong><p>32 approved feedback comments support this finding.</p></div><span class="ft-status-pill ft-status-done">Approved</span></div>
        <a class="ft-text-link" href="{{ route('dashboard.task-a') }}">Open Task A findings →</a>
    </article>
    <article class="ft-panel"><div class="ft-panel-header"><div><div class="ft-panel-title">Knowledge base references</div><div class="ft-panel-subtitle">Documents available to ground recommendations</div></div><a class="ft-text-link" href="{{ route('dashboard.documents') }}">Browse →</a></div>
        <div class="ft-document-mini"><span class="ft-file-icon">PDF</span><div><strong>Student Services Handbook 2026</strong><small>Policy reference · 42 pages</small></div><span class="ft-status-pill ft-status-done">Indexed</span></div>
        <div class="ft-document-mini"><span class="ft-file-icon ft-file-blue">PDF</span><div><strong>Academic Service Standards</strong><small>Service charter · 18 pages</small></div><span class="ft-status-pill ft-status-done">Indexed</span></div>
        <div class="ft-document-mini"><span class="ft-file-icon ft-file-green">DOC</span><div><strong>Digital Transformation Plan</strong><small>Strategic plan · 26 pages</small></div><span class="ft-status-pill ft-status-done">Indexed</span></div>
    </article>
</section>
@endsection
