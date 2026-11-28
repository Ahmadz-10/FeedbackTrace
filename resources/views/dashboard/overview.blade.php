@extends('layouts.feedbacktrace')

@section('page-title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
<section class="ft-page-heading">
    <div>
        <div class="ft-eyebrow">{{ strtoupper(now()->translatedFormat('l, F j, Y')) }} <span class="ft-eyebrow-divider">/</span> UNIVERSITY WORKSPACE</div>
        <h1>Good morning, Admin</h1>
        <p>Here’s what’s happening across your student feedback and service quality.</p>
    </div>
    <div class="ft-heading-actions">
        <select class="ft-select ft-range-select" aria-label="Date range" data-range-filter>
            <option>Last 30 days</option><option>Last 7 days</option><option>This semester</option>
        </select>
        <button class="ft-button ft-button-primary" type="button" data-toast="Report export is a demo interaction.">⇩ <span>Export report</span></button>
    </div>
</section>

<section class="ft-kpi-grid">
    <article class="ft-kpi-card">
        <div class="ft-kpi-top"><span class="ft-kpi-label">TOTAL FEEDBACK</span><span class="ft-kpi-icon ft-kpi-blue">▤</span></div>
        <div class="ft-kpi-value">2,486</div>
        <div class="ft-kpi-foot"><span class="ft-trend-positive">↗ 12.8%</span><span>vs. previous period</span></div>
        <div class="ft-kpi-spark"><svg viewBox="0 0 120 32" role="img" aria-label="Feedback trend rising"><path d="M1 25 L14 21 L27 24 L40 14 L53 18 L66 11 L79 15 L92 7 L105 10 L119 2" fill="none" stroke="#5c8bea" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
    </article>
    <article class="ft-kpi-card">
        <div class="ft-kpi-top"><span class="ft-kpi-label">AVERAGE SENTIMENT</span><span class="ft-kpi-icon ft-kpi-violet">◉</span></div>
        <div class="ft-kpi-value">68.4<span class="ft-kpi-unit">%</span></div>
        <div class="ft-kpi-foot"><span class="ft-trend-positive">↗ 4.2%</span><span>positive feedback</span></div>
        <div class="ft-kpi-spark"><svg viewBox="0 0 120 32" role="img" aria-label="Sentiment trend rising"><path d="M1 27 L14 20 L27 22 L40 15 L53 18 L66 12 L79 13 L92 7 L105 8 L119 3" fill="none" stroke="#8874df" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
    </article>
    <article class="ft-kpi-card">
        <div class="ft-kpi-top"><span class="ft-kpi-label">SERVICE PERFORMANCE</span><span class="ft-kpi-icon ft-kpi-green">⌁</span></div>
        <div class="ft-kpi-value">3.82<span class="ft-kpi-unit">/ 5</span></div>
        <div class="ft-kpi-foot"><span class="ft-trend-positive">↗ 0.18</span><span>average score</span></div>
        <div class="ft-kpi-spark"><svg viewBox="0 0 120 32" role="img" aria-label="Performance trend rising"><path d="M1 25 L14 22 L27 23 L40 19 L53 15 L66 17 L79 10 L92 13 L105 7 L119 4" fill="none" stroke="#38a982" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
    </article>
    <article class="ft-kpi-card">
        <div class="ft-kpi-top"><span class="ft-kpi-label">OPEN PRIORITIES</span><span class="ft-kpi-icon ft-kpi-amber">⚑</span></div>
        <div class="ft-kpi-value">12</div>
        <div class="ft-kpi-foot"><span class="ft-trend-neutral">4 high priority</span><span>awaiting action</span></div>
        <div class="ft-kpi-spark"><svg viewBox="0 0 120 32" role="img" aria-label="Priority trend"><path d="M1 8 L14 10 L27 7 L40 13 L53 12 L66 18 L79 14 L92 17 L105 21 L119 19" fill="none" stroke="#d5a34a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
    </article>
</section>

<section class="ft-overview-grid">
    <article class="ft-panel ft-trend-panel">
        <div class="ft-panel-header">
            <div><div class="ft-panel-title">Feedback activity</div><div class="ft-panel-subtitle">Submission volume over time</div></div>
            <div class="ft-chart-legend"><span><i class="ft-legend-dot ft-dot-blue"></i>Feedback received</span><span><i class="ft-legend-dot ft-dot-light"></i>Previous period</span></div>
        </div>
        <div class="ft-chart-area">
            <div class="ft-chart-ylabels"><span>600</span><span>450</span><span>300</span><span>150</span><span>0</span></div>
            <svg class="ft-line-chart" viewBox="0 0 720 220" preserveAspectRatio="none" role="img" aria-label="Feedback activity chart from June to November">
                <defs><linearGradient id="ft-area-blue" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#648de6" stop-opacity=".22"/><stop offset="1" stop-color="#648de6" stop-opacity="0"/></linearGradient></defs>
                <path d="M0 177H720 M0 132H720 M0 88H720 M0 44H720 M0 0H720" stroke="#edf1f6" stroke-dasharray="3 5" fill="none"/>
                <path d="M0 170 L72 157 L144 166 L216 127 L288 139 L360 95 L432 108 L504 72 L576 83 L648 42 L720 26 L720 220 L0 220Z" fill="url(#ft-area-blue)"/>
                <path d="M0 170 L72 157 L144 166 L216 127 L288 139 L360 95 L432 108 L504 72 L576 83 L648 42 L720 26" fill="none" stroke="#5e87df" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M0 187 L72 180 L144 177 L216 165 L288 171 L360 143 L432 154 L504 141 L576 129 L648 119 L720 106" fill="none" stroke="#b8c4d7" stroke-width="2" stroke-dasharray="5 6" stroke-linecap="round"/>
                <g fill="#fff" stroke="#5e87df" stroke-width="2"><circle cx="0" cy="170" r="4"/><circle cx="144" cy="166" r="4"/><circle cx="288" cy="139" r="4"/><circle cx="432" cy="108" r="4"/><circle cx="576" cy="83" r="4"/><circle cx="720" cy="26" r="4"/></g>
            </svg>
            <div class="ft-chart-xlabels"><span>Jun 01</span><span>Jun 15</span><span>Jul 01</span><span>Jul 15</span><span>Aug 01</span><span>Aug 15</span><span>Sep 01</span><span>Sep 15</span><span>Oct 01</span><span>Oct 15</span><span>Nov 01</span>
            </div>
        </div>
        <div class="ft-chart-foot"><span><strong>2,486</strong> submissions in this period</span><span class="ft-chart-foot-up">12.8% above previous period</span></div>
    </article>

    <article class="ft-panel ft-sentiment-panel">
        <div class="ft-panel-header"><div><div class="ft-panel-title">Sentiment distribution</div><div class="ft-panel-subtitle">Across all feedback</div></div><button class="ft-more-button" type="button" data-toast="Chart options are available in the connected analytics module.">···</button></div>
        <div class="ft-donut-wrap">
            <div class="ft-donut"><div class="ft-donut-center"><strong>2,486</strong><span>responses</span></div></div>
        </div>
        <div class="ft-sentiment-legend">
            <div><span><i class="ft-legend-dot ft-dot-positive"></i>Positive</span><strong>68.4%</strong></div>
            <div><span><i class="ft-legend-dot ft-dot-neutral"></i>Neutral</span><strong>21.2%</strong></div>
            <div><span><i class="ft-legend-dot ft-dot-negative"></i>Negative</span><strong>10.4%</strong></div>
        </div>
    </article>
</section>

<section class="ft-bottom-grid">
    <article class="ft-panel ft-priority-panel">
        <div class="ft-panel-header"><div><div class="ft-panel-title">Top service priorities</div><div class="ft-panel-subtitle">Areas with the highest improvement opportunity</div></div><a class="ft-text-link" href="{{ route('dashboard.task-b') }}">View IPA analysis <span>→</span></a></div>
        <div class="ft-priority-list">
            <div class="ft-priority-row"><span class="ft-priority-rank">01</span><div class="ft-priority-main"><strong>Digital academic services</strong><small>High importance · Low performance</small></div><span class="ft-priority-score">−1.24</span><span class="ft-priority-tag ft-tag-high">High</span></div>
            <div class="ft-priority-row"><span class="ft-priority-rank">02</span><div class="ft-priority-main"><strong>Administrative response time</strong><small>High importance · Low performance</small></div><span class="ft-priority-score">−0.96</span><span class="ft-priority-tag ft-tag-high">High</span></div>
            <div class="ft-priority-row"><span class="ft-priority-rank">03</span><div class="ft-priority-main"><strong>Campus facilities</strong><small>High importance · Moderate performance</small></div><span class="ft-priority-score">−0.62</span><span class="ft-priority-tag ft-tag-medium">Medium</span></div>
        </div>
    </article>

    <article class="ft-panel ft-insight-panel">
        <div class="ft-panel-header"><div><div class="ft-panel-title">Latest insight</div><div class="ft-panel-subtitle">Generated from approved feedback findings</div></div><span class="ft-insight-icon">✦</span></div>
        <div class="ft-insight-kicker">RECURRING THEME</div>
        <h3>Students want a faster, more reliable academic service experience.</h3>
        <p>Feedback mentions recurring delays in academic administration, with digital service access identified as a key improvement area.</p>
        <div class="ft-insight-meta"><span>Based on 184 feedback entries</span><span class="ft-dot-separator"></span><span>Task A</span></div>
        <a class="ft-text-link" href="{{ route('dashboard.task-a') }}">Explore findings <span>→</span></a>
    </article>
</section>

<section class="ft-panel ft-activity-panel">
    <div class="ft-panel-header"><div><div class="ft-panel-title">Recent activity</div><div class="ft-panel-subtitle">Latest updates across your workspace</div></div><button class="ft-more-button" type="button" data-toast="Activity log is part of the demo workspace.">···</button></div>
    <div class="ft-table-wrap">
        <table class="ft-table">
            <thead><tr><th>ACTIVITY</th><th>MODULE</th><th>OWNER</th><th>DATE</th><th>STATUS</th></tr></thead>
            <tbody>
            <tr><td><strong>Semester feedback survey completed</strong><small>2,486 feedback records collected</small></td><td><span class="ft-module-label ft-module-a">Feedback</span></td><td>Academic Office</td><td>24 Nov 2026</td><td><span class="ft-status-pill ft-status-done">Completed</span></td></tr>
            <tr><td><strong>Service priorities updated</strong><small>12 improvement areas identified</small></td><td><span class="ft-module-label ft-module-b">Task B</span></td><td>Quality Assurance</td><td>23 Nov 2026</td><td><span class="ft-status-pill ft-status-review">In review</span></td></tr>
            <tr><td><strong>New university handbook indexed</strong><small>Student Services Handbook · 2026</small></td><td><span class="ft-module-label ft-module-doc">Documents</span></td><td>Admin Demo</td><td>21 Nov 2026</td><td><span class="ft-status-pill ft-status-done">Completed</span></td></tr>
            </tbody>
        </table>
    </div>
</section>
@endsection
