@extends('layouts.feedbacktrace')

@section('page-title', 'AI Overview')
@section('breadcrumb', 'AI Overview')

@section('content')
<section class="ft-page-heading">
    <div><div class="ft-eyebrow">PLATFORM MONITORING</div><h1>AI Overview</h1><p>Review the AI components used by FeedbackTrace and understand how feedback intelligence flows into service decisions.</p></div>
    <span class="ft-quiet-chip">CONFIGURATION PREVIEW · NOT LIVE TELEMETRY</span>
</section>

<section class="ft-kpi-grid">
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">AI WORKFLOWS</span><span class="ft-kpi-icon ft-kpi-blue">⌘</span></div><div class="ft-kpi-value">2</div><div class="ft-kpi-foot"><span class="ft-trend-neutral">Task A + Task B</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">LLM PROVIDER</span><span class="ft-kpi-icon ft-kpi-violet">✧</span></div><div class="ft-kpi-value" style="font-size:22px">Groq</div><div class="ft-kpi-foot"><span>Provider configured by environment</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">EMBEDDINGS</span><span class="ft-kpi-icon ft-kpi-green">▦</span></div><div class="ft-kpi-value" style="font-size:20px">Multilingual E5</div><div class="ft-kpi-foot"><span>Shared vector representation</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">DATA HANDOFF</span><span class="ft-kpi-icon ft-kpi-amber">⇢</span></div><div class="ft-kpi-value" style="font-size:21px">Approved only</div><div class="ft-kpi-foot"><span>Task A findings → Task B</span></div></article>
</section>

<section class="ft-two-col-grid">
    <article class="ft-panel">
        <div class="ft-panel-header"><div><div class="ft-panel-title">AI components</div><div class="ft-panel-subtitle">Architecture-level overview · values are illustrative</div></div><span class="ft-status-pill ft-status-review">Preview</span></div>
        <div class="ft-priority-list">
            <div class="ft-priority-row"><span class="ft-priority-rank">01</span><div class="ft-priority-main"><strong>LLM inference</strong><small>Groq API · configured model name is read from environment at runtime</small></div><span class="ft-priority-tag ft-tag-good">Shared</span></div>
            <div class="ft-priority-row"><span class="ft-priority-rank">02</span><div class="ft-priority-main"><strong>Text embeddings</strong><small>intfloat/multilingual-e5-base · vector dimensions: 768</small></div><span class="ft-priority-tag ft-tag-good">Shared</span></div>
            <div class="ft-priority-row"><span class="ft-priority-rank">03</span><div class="ft-priority-main"><strong>Task A pipeline</strong><small>Validation, privacy filtering, aspect sentiment, evidence, consolidation</small></div><span class="ft-priority-tag ft-tag-high">Task A</span></div>
            <div class="ft-priority-row"><span class="ft-priority-rank">04</span><div class="ft-priority-main"><strong>Task B pipeline</strong><small>IPA calculations, document retrieval, and evidence-grounded recommendations</small></div><span class="ft-priority-tag ft-tag-medium">Task B</span></div>
        </div>
    </article>
    <article class="ft-panel">
        <div class="ft-panel-header"><div><div class="ft-panel-title">End-to-end workflow</div><div class="ft-panel-subtitle">AI suggestions remain reviewable by staff</div></div></div>
        <ol class="ft-dash-pipeline">
            <li><div class="ft-dash-pipeline-top"><span>Student feedback imported</span><strong>01</strong></div><span class="ft-dash-bar"><span style="width:100%"></span></span><small>Input records and data-quality checks</small></li>
            <li><div class="ft-dash-pipeline-top"><span>Task A analysis</span><strong>02</strong></div><span class="ft-dash-bar"><span style="width:78%"></span></span><small>Aspect-level sentiment and consolidated findings</small></li>
            <li><div class="ft-dash-pipeline-top"><span>Staff review</span><strong>03</strong></div><span class="ft-dash-bar"><span style="width:54%"></span></span><small>Evidence verification and approval decision</small></li>
            <li><div class="ft-dash-pipeline-top"><span>Task B decision support</span><strong>04</strong></div><span class="ft-dash-bar"><span style="width:32%"></span></span><small>IPA priorities + university-document retrieval + recommendations</small></li>
        </ol>
    </article>
</section>

<section class="ft-panel">
    <div class="ft-panel-header"><div><div class="ft-panel-title">Configuration notes</div><div class="ft-panel-subtitle">Operational values should be read from protected server configuration, never exposed as secrets in the browser.</div></div></div>
    <div class="ft-table-wrap"><table class="ft-table"><thead><tr><th>COMPONENT</th><th>CONFIGURATION</th><th>WHAT TO VERIFY</th><th>STATUS</th></tr></thead><tbody>
        <tr><td><strong>FastAPI service</strong></td><td>AI_SERVICE_URL</td><td>Reachability and authentication from Laravel</td><td><span class="ft-status-pill ft-status-review">Not checked in preview</span></td></tr>
        <tr><td><strong>LLM provider</strong></td><td>GROQ_API_KEY + GROQ_MODEL</td><td>Configured on server; API key remains secret</td><td><span class="ft-status-pill ft-status-review">Not checked in preview</span></td></tr>
        <tr><td><strong>Embedding model</strong></td><td>EMBEDDING_MODEL</td><td>Model availability, device, and vector size</td><td><span class="ft-status-pill ft-status-review">Not checked in preview</span></td></tr>
    </tbody></table></div>
</section>
@endsection
