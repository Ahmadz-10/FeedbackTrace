<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f6f8fc">
    <title>FeedbackTrace — Student Feedback Intelligence</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="brand"><div class="brand-mark">F</div><div><div class="brand-name">FeedbackTrace</div><div class="brand-sub">Insight & Decision Support</div></div></div>
    <div class="nav-label">Workspace</div>
    <nav class="nav" aria-label="Main navigation">
      <button class="active" data-page="dashboard">Dashboard</button>
      <button data-page="inbox">Feedback Inbox</button>
      <button data-page="task-a">Analyze Student Feedback</button>
      <button data-page="task-b">Prioritize Service Quality</button>
      <button data-page="documents">University Documents</button>
      <button data-page="recommendations">Improvement Recommendations</button>
    </nav>
    <div class="sidebar-bottom">
      <div class="help-box"><strong>Need assistance?</strong><p>Explore the workflow and learn how feedback becomes actionable insight.</p><button id="guideBtn">View workflow →</button></div>
      <div class="profile"><div class="avatar">AD</div><div><div class="profile-name">Staff workspace</div><div class="profile-role">Development preview</div></div><span style="margin-left:auto;color:#9aa4b3">⌄</span></div>
    </div>
  </aside>
  <main class="main">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:12px"><button class="icon-btn hamburger" id="menuBtn" aria-label="Open menu">☰</button><div class="crumb">Workspace <span style="margin:0 7px;color:#c1c8d3">/</span> <strong id="crumbTitle">Dashboard</strong></div></div>
      <div class="top-actions"><div class="search"><input id="globalSearch" placeholder="Search feedback, findings..." /></div><button class="icon-btn" id="notificationBtn" title="Notifications">♧</button><div class="avatar" style="width:32px;height:32px">AD</div></div>
    </header>
    <div class="content">
      <!-- DASHBOARD -->
      <section class="page active" id="page-dashboard">
        <div class="page-head"><div><div class="eyebrow">Overview</div><h1>Good morning, Admin</h1><p class="subhead">Here is what is happening across student feedback and service quality.</p></div><button class="btn" id="exportDashboard">↓ Export overview</button></div>
        <div class="grid stats">
          <div class="card stat"><div class="stat-top">Total feedback <span class="stat-icon" style="background:#edf1ff;color:#5570e7">☷</span></div><div class="stat-value">{{ number_format($totalFeedback) }}</div><div class="stat-foot"><span class="positive">↑ 12.8%</span> compared with previous period</div></div>
          <div class="card stat"><div class="stat-top">Analyzed feedback <span class="stat-icon" style="background:#e9f8f2;color:#169b72">✳</span></div><div class="stat-value">{{ number_format($processedFeedback) }}</div><div class="stat-foot">{{ $processedFeedbackPercent }}% of collected feedback</div></div>
          <div class="card stat"><div class="stat-top">Issues identified <span class="stat-icon" style="background:#fff5e7;color:#d97706">⚑</span></div><div class="stat-value">{{ number_format($totalFindings) }}</div><div class="stat-foot">{{ number_format($pendingFindings) }} findings awaiting review</div></div>
          <div class="card stat"><div class="stat-top">Actions in progress <span class="stat-icon" style="background:#f2efff;color:#8064d8">◎</span></div><div class="stat-value">—</div><div class="stat-foot">Task B is a preview and is not connected on this branch</div></div>
        </div>
        <div class="grid two-col">
          <div class="card"><div class="card-head"><div><div class="card-title">Feedback sentiment</div><div class="card-desc">Distribution across analyzed aspect mentions</div></div><span class="pill blue">Current period</span></div><div class="card-body">
            <div style="display:flex;gap:22px;align-items:center;flex-wrap:wrap"><div style="width:126px;height:126px;border-radius:50%;background:conic-gradient(#35b58b 0 {{ $sentimentPercentages['positive'] }}%,#e57575 {{ $sentimentPercentages['positive'] }}% {{ $sentimentPercentages['positive'] + $sentimentPercentages['negative'] }}%,#eab15f {{ $sentimentPercentages['positive'] + $sentimentPercentages['negative'] }}% {{ $sentimentPercentages['positive'] + $sentimentPercentages['negative'] + $sentimentPercentages['neutral'] }}%,#8064d8 {{ $sentimentPercentages['positive'] + $sentimentPercentages['negative'] + $sentimentPercentages['neutral'] }}% 100%);display:grid;place-items:center;position:relative"><div style="width:84px;height:84px;border-radius:50%;background:white;display:grid;place-items:center;text-align:center"><div><strong style="font-size:21px">{{ number_format($sentimentMentionTotal) }}</strong><div class="muted" style="font-size:9px">Aspect mentions</div></div></div></div><div style="flex:1;min-width:155px"><div class="bar-row" style="grid-template-columns:75px 1fr 32px"><span class="bar-label">Positive</span><div class="bar-track"><div class="bar-fill green" style="width:{{ $sentimentPercentages['positive'] }}%"></div></div><b class="bar-num">{{ $sentimentPercentages['positive'] }}%</b></div><div class="bar-row" style="grid-template-columns:75px 1fr 32px"><span class="bar-label">Negative</span><div class="bar-track"><div class="bar-fill red" style="width:{{ $sentimentPercentages['negative'] }}%"></div></div><b class="bar-num">{{ $sentimentPercentages['negative'] }}%</b></div><div class="bar-row" style="grid-template-columns:75px 1fr 32px"><span class="bar-label">Neutral</span><div class="bar-track"><div class="bar-fill orange" style="width:{{ $sentimentPercentages['neutral'] }}%"></div></div><b class="bar-num">{{ $sentimentPercentages['neutral'] }}%</b></div><div class="bar-row" style="grid-template-columns:75px 1fr 32px"><span class="bar-label">Mixed</span><div class="bar-track"><div class="bar-fill" style="width:{{ $sentimentPercentages['mixed'] }}%;background:#8064d8"></div></div><b class="bar-num">{{ $sentimentPercentages['mixed'] }}%</b></div></div></div>
          </div></div>
          <div class="card"><div class="card-head"><div><div class="card-title">Recent activity</div><div class="card-desc">Illustrative activity examples · not live event data</div></div><button class="link-btn" data-goto="recommendations">View all</button></div><div class="card-body">
            <div class="activity-item"><span class="activity-dot"></span><div><div class="activity-text">A new finding was generated for <b>Academic Administration</b>.</div><div class="activity-meta">12 minutes ago · Task A</div></div></div>
            <div class="activity-item"><span class="activity-dot" style="background:#16a078;box-shadow:0 0 0 4px #e9f8f2"></span><div><div class="activity-text">Recommendation <b>REC-012</b> was approved.</div><div class="activity-meta">1 hour ago · Task B</div></div></div>
            <div class="activity-item"><span class="activity-dot" style="background:#d99a45;box-shadow:0 0 0 4px #fff5e7"></span><div><div class="activity-text"><b>Academic Service SOP</b> was added to the document library.</div><div class="activity-meta">Yesterday · Documents</div></div></div>
          </div></div>
        </div>
        
        <div class="card" style="margin-top:17px">
          <div class="card-head">
            <div><div class="card-title">Most discussed service aspects</div><div class="card-desc">Calculated from saved aspect mentions in Task A</div></div>
            <button class="link-btn" data-goto="task-a">Explore findings →</button>
          </div>
          <div class="card-body" style="max-width:700px">
            @forelse ($topAspects as $aspect)
              <div class="bar-row">
                <span class="bar-label">{{ \Illuminate\Support\Str::headline($aspect->aspect_code) }}</span>
                <div class="bar-track"><div class="bar-fill" style="width:{{ $topAspectMax > 0 ? ($aspect->mention_count / $topAspectMax) * 100 : 0 }}%"></div></div>
                <b class="bar-num">{{ number_format($aspect->mention_count) }}</b>
              </div>
            @empty
              <div class="empty">No aspect mentions are available yet. Run Task A analysis to populate this section.</div>
            @endforelse
          </div>
        </div>
      </section>
      <!-- INBOX -->
      <section class="page" id="page-inbox">
        <div class="page-head"><div><div class="eyebrow">Data collection</div><h1>Feedback Inbox</h1><p class="subhead">Review collected student comments before and after AI analysis.</p></div><button class="btn primary" id="importBtn">＋ Import feedback</button></div>
        <div class="card"><div class="card-head"><div><div class="card-title">Student feedback records</div><div class="card-desc">Sample records for prototype demonstration</div></div><div class="filters"><input class="field search-field" id="feedbackSearch" placeholder="Search comments or ID..." /><select class="field" id="sentimentFilter"><option value="all">All sentiment</option><option value="Negative">Negative</option><option value="Positive">Positive</option><option value="Neutral">Neutral</option></select></div></div>
          <div class="table-wrap"><table id="feedbackTable"><thead><tr><th>Feedback ID</th><th>Student comment</th><th>Service area</th><th>Sentiment</th><th>Analysis</th><th>Submitted</th></tr></thead><tbody>
            <tr><td><strong>FB-001</strong></td><td class="table-title">The process of getting an academic letter takes too long.</td><td>Academic Administration</td><td><span class="pill red">Negative</span></td><td><span class="pill green">Analyzed</span></td><td>18 Nov 2026</td></tr>
            <tr><td><strong>FB-002</strong></td><td class="table-title">The staff explained the requirements clearly.</td><td>Academic Administration</td><td><span class="pill green">Positive</span></td><td><span class="pill green">Analyzed</span></td><td>18 Nov 2026</td></tr>
            <tr><td><strong>FB-003</strong></td><td class="table-title">I received different information from two service counters.</td><td>Academic Administration</td><td><span class="pill red">Negative</span></td><td><span class="pill green">Analyzed</span></td><td>17 Nov 2026</td></tr>
            <tr><td><strong>FB-004</strong></td><td class="table-title">The online form is easy to access and understand.</td><td>Digital Services</td><td><span class="pill green">Positive</span></td><td><span class="pill green">Analyzed</span></td><td>17 Nov 2026</td></tr>
            <tr><td><strong>FB-005</strong></td><td class="table-title">The waiting room is sometimes crowded.</td><td>Student Services</td><td><span class="pill red">Negative</span></td><td><span class="pill orange">Pending</span></td><td>16 Nov 2026</td></tr>
            <tr><td><strong>FB-006</strong></td><td class="table-title">The service was acceptable, nothing particularly good or bad.</td><td>Student Services</td><td><span class="pill orange">Neutral</span></td><td><span class="pill green">Analyzed</span></td><td>16 Nov 2026</td></tr>
          </tbody></table></div><div class="card-body" style="padding-top:12px;display:flex;justify-content:space-between"><span class="muted" style="font-size:10px" id="feedbackCount">Showing 6 sample records</span><span class="muted" style="font-size:10px">Page 1 of 1</span></div>
        </div>
      </section>
      <!-- TASK A -->
      <section class="page" id="page-task-a">
        <div class="page-head"><div><div class="eyebrow">Task A · Student Feedback Intelligence</div><h1>Analyze Student Feedback</h1><p class="subhead">Turn unstructured student comments into consolidated, evidence-linked findings.</p></div><button class="btn primary" id="runAnalysis">✳ Run AI analysis</button></div>
        <div class="ai-note"><span style="font-size:15px">✦</span><div><b>AI analysis pipeline</b><br />The planned GroqCloud LLM extracts service aspects, sentiment, and reasons. Similarity processing groups related comments, while the backend validates evidence against original feedback.</div></div>
        <div class="grid stats" style="grid-template-columns:repeat(3,minmax(0,1fr))"><div class="card stat"><div class="stat-top">Analyzed comments <span class="pill green">Complete</span></div><div class="stat-value">{{ number_format($processedFeedback) }}</div><div class="stat-foot">From {{ number_format($totalFeedback) }} collected feedback items</div></div><div class="card stat"><div class="stat-top">Consolidated findings <span class="pill blue">Task A</span></div><div class="stat-value">{{ number_format($totalFindings) }}</div><div class="stat-foot">Grouped by service aspect and issue</div></div><div class="card stat"><div class="stat-top">Awaiting staff review <span class="pill orange">Review</span></div><div class="stat-value" id="pendingFindings">{{ number_format($pendingFindings) }}</div><div class="stat-foot">AI findings require human validation</div></div></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin:22px 0 12px;gap:12px"><div><div class="card-title">Consolidated findings</div><div class="card-desc">Live findings from the Task A database · supporting feedback IDs are shown without exposing raw comments</div></div><select class="field" id="findingFilter"><option value="all">All findings</option><option value="pending">Awaiting review</option><option value="approved">Approved</option></select></div>
        
        <div id="findingList">
          @forelse ($latestFindings as $finding)
            @php
              $reviewClass = $finding->review_status === 'approved' ? 'green' : ($finding->review_status === 'rejected' ? 'red' : 'orange');
              $findingTypeLabel = \Illuminate\Support\Str::headline($finding->finding_type);
              $aspectLabel = $finding->proposed_aspect_label ?: \Illuminate\Support\Str::headline($finding->aspect_code);
              $feedbackReferences = $finding->feedbackRecords->pluck('feedback_id')->take(5)->implode(', ');
              $distribution = $finding->sentiment_distribution ?? [];
            @endphp
            <article class="card finding-card" data-review="{{ $finding->review_status === 'pending_review' ? 'pending' : $finding->review_status }}">
              <div class="finding-top">
                <div><h3 class="finding-title">{{ $finding->issue_title }}</h3><p class="finding-summary">{{ $finding->issue_summary }}</p></div>
                <span class="pill {{ $reviewClass }} review-pill">{{ \Illuminate\Support\Str::headline($finding->review_status) }}</span>
              </div>
              <div class="finding-meta">
                <span class="pill blue">Aspect: {{ $aspectLabel }}</span>
                <span class="pill purple">{{ $findingTypeLabel }}</span>
                <span class="pill orange">Reason: {{ \Illuminate\Support\Str::headline($finding->reason_category) }}</span>
              </div>
              <div class="mini-kpis">
                <div class="mini-kpi"><strong>{{ number_format($finding->unique_feedback_count) }}</strong><span>Supporting feedback</span></div>
                <div class="mini-kpi"><strong>{{ number_format($distribution['negative'] ?? 0) }}</strong><span>Negative mentions</span></div>
                <div class="mini-kpi"><strong>{{ number_format(($distribution['positive'] ?? 0) + ($distribution['neutral'] ?? 0) + ($distribution['mixed'] ?? 0)) }}</strong><span>Other mentions</span></div>
              </div>
              <div class="evidence">
                <div class="evidence-label">Supporting feedback references</div>
                <div class="quote">{{ $feedbackReferences !== '' ? $feedbackReferences : 'No feedback references linked.' }}</div>
                <div class="evidence-id">{{ number_format($finding->mention_count) }} aspect mentions · finding {{ $finding->finding_code }}</div>
              </div>
              <div class="finding-bottom">
                <span class="muted" style="font-size:9px">Created {{ $finding->created_at?->format('d M Y') ?? '—' }}</span>
                <div class="review-actions">
                  <button class="btn small" onclick="showFindingDetails(this)"
                    data-finding="{{ $finding->finding_code }}"
                    data-title="{{ $finding->issue_title }}"
                    data-summary="{{ $finding->issue_summary }}"
                    data-references="{{ $feedbackReferences }}">View details</button>
                  @if ($finding->review_status === 'pending_review')
                    <span class="pill orange">Awaiting staff review</span>
                  @elseif ($finding->review_status === 'approved')
                    <span class="pill green">Approved by staff</span>
                  @else
                    <span class="pill red">Rejected</span>
                  @endif
                </div>
              </div>
            </article>
          @empty
            <div class="card empty">No consolidated findings yet. Import feedback and run Task A analysis to create evidence-linked findings.</div>
          @endforelse
        </div>
      </section>
      <!-- TASK B -->
      <section class="page" id="page-task-b">
        <div class="ai-note"><b>Preview</b> Task B preview only. IPA calculations, document retrieval, and RAG recommendations are not connected on this branch.</div>
        <div class="page-head"><div><div class="eyebrow">Task B · Service Quality Decision Intelligence</div><h1>Prioritize Service Quality</h1><p class="subhead">Identify improvement priorities and generate recommendations supported by university documents.</p></div><button class="btn primary" id="generateRec">✦ Generate recommendation</button></div>
        <div class="ai-note"><span style="font-size:15px">⌁</span><div><b>Decision intelligence pipeline</b><br />IPA scores and quadrants are calculated using statistical methods. Document retrieval finds relevant institutional evidence, which is supplied with approved Task A findings to the GroqCloud LLM through a RAG workflow.</div></div>
        <div class="grid two-col">
          <div class="card"><div class="card-head"><div><div class="card-title">Importance–Performance Analysis</div><div class="card-desc">Illustrative service aspect classification</div></div><span class="pill blue">IPA matrix</span></div><div class="card-body"><div style="display:flex;justify-content:space-between;color:#9aa4b3;font-size:9px;margin-bottom:8px"><span>LOW PERFORMANCE</span><span>HIGH PERFORMANCE →</span></div><div class="ipa-grid"><div class="ipa-cell ipa-high"><h4>Concentrate Here</h4><p>High importance · Low performance</p><div class="ipa-num">3 aspects</div></div><div class="ipa-cell ipa-maintain"><h4>Keep Up the Good Work</h4><p>High importance · High performance</p><div class="ipa-num">5 aspects</div></div><div class="ipa-cell ipa-low"><h4>Low Priority</h4><p>Low importance · Low performance</p><div class="ipa-num">2 aspects</div></div><div class="ipa-cell ipa-possible"><h4>Possible Overkill</h4><p>Low importance · High performance</p><div class="ipa-num">1 aspect</div></div></div><div style="display:flex;justify-content:space-between;color:#9aa4b3;font-size:9px;margin-top:9px"><span>← LOW IMPORTANCE</span><span>HIGH IMPORTANCE</span></div></div></div>
          <div class="card"><div class="card-head"><div><div class="card-title">Priority gaps</div><div class="card-desc">Importance compared with performance</div></div><span class="pill orange">Priority areas</span></div><div class="card-body"><div class="gap-row"><span>Service speed</span><div class="gap-track"><div class="gap-fill" style="width:88%"></div></div><b style="color:#d18a3e">1.4</b></div><div class="gap-row"><span>Info clarity</span><div class="gap-track"><div class="gap-fill" style="width:68%"></div></div><b style="color:#d18a3e">1.1</b></div><div class="gap-row"><span>Accessibility</span><div class="gap-track"><div class="gap-fill" style="width:45%"></div></div><b style="color:#d18a3e">0.7</b></div><div class="gap-row"><span>Staff response</span><div class="gap-track"><div class="gap-fill" style="width:30%"></div></div><b style="color:#d18a3e">0.5</b></div><div style="margin-top:18px;padding-top:13px;border-top:1px solid #f0f2f6;color:#9aa4b3;font-size:9px">Gap values shown are sample values for the prototype.</div></div></div>
        </div>
        <div class="grid two-col" style="margin-top:17px">
          <div class="card"><div class="card-head"><div><div class="card-title">AI-generated improvement recommendation</div><div class="card-desc">RAG output based on an approved finding and retrieved document context</div></div><span class="pill purple">LLM + RAG</span></div><div class="card-body"><div class="recommendation"><div style="display:flex;justify-content:space-between;gap:10px;align-items:center;margin-bottom:9px"><span class="pill orange">High priority</span><span class="muted" style="font-size:9px">REC-012</span></div><h3>Improve the academic letter processing workflow</h3><p>Establish a clear processing-time target, communicate expected completion dates to students, and provide a simple status-check mechanism to reduce repeated visits to the service counter.</p><div class="source">Evidence context: FIND-001 (approved finding) · Academic Service SOP (sample retrieved passage)</div></div><div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;gap:10px"><span class="muted" style="font-size:9px">Status: Awaiting staff decision</span><div><button class="btn small" onclick="showToast('Recommendation details opened (demo).')">Details</button> <button class="btn small success" onclick="approveRecommendation(this)">✓ Approve</button></div></div></div></div>
          <div class="card"><div class="card-head"><div><div class="card-title">Retrieved institutional evidence</div><div class="card-desc">Document passages used as context by the RAG pipeline</div></div><button class="link-btn" data-goto="documents">View library →</button></div><div class="card-body"><div class="doc-row"><div class="doc-icon">PDF</div><div style="flex:1"><div class="doc-name">Academic Service SOP</div><div class="doc-meta">SOP-LAY-02 · Section 3.1 · Processing standards</div></div><span class="pill green">Retrieved</span></div><div class="evidence"><div class="evidence-label">Retrieved passage · SOP-LAY-02</div><div class="quote">“Service units should communicate the required documents and estimated processing time to applicants at the beginning of the service process.”</div><div class="evidence-id">Passage displayed as illustrative sample content, not a verified university policy.</div></div><div style="font-size:9px;color:#9aa4b3;margin-top:12px">The LLM should use retrieved passages as evidence and must not invent institutional rules.</div></div></div>
        </div>
      </section>
      <!-- DOCUMENTS -->
      <section class="page" id="page-documents">
        <div class="ai-note"><b>Preview</b> Illustrative document library. Upload, indexing, and retrieval are not connected on this branch.</div>
        <div class="page-head"><div><div class="eyebrow">Knowledge base</div><h1>University Documents</h1><p class="subhead">Manage institutional references used by Task B's document retrieval and RAG workflow.</p></div><button class="btn primary" id="uploadDoc">＋ Add document</button></div>
        <div class="grid stats" style="grid-template-columns:repeat(3,minmax(0,1fr))"><div class="card stat"><div class="stat-top">Total documents <span class="stat-icon" style="background:#fff0f0;color:#d45c5c">▤</span></div><div class="stat-value" id="docTotal">18</div><div class="stat-foot">Available in the document library</div></div><div class="card stat"><div class="stat-top">Indexed documents <span class="stat-icon" style="background:#e9f8f2;color:#169b72">⌕</span></div><div class="stat-value">16</div><div class="stat-foot">Ready for retrieval</div></div><div class="card stat"><div class="stat-top">Pending indexing <span class="stat-icon" style="background:#fff5e7;color:#d97706">◷</span></div><div class="stat-value">2</div><div class="stat-foot">Awaiting processing</div></div></div>
        <div class="card"><div class="card-head"><div><div class="card-title">Document library</div><div class="card-desc">Illustrative records only · not connected to a document repository</div></div><div class="filters"><input class="field search-field" id="docSearch" placeholder="Search documents..." /><select class="field"><option>All categories</option><option>SOP</option><option>Regulation</option><option>Service standard</option></select></div></div><div class="card-body" id="docList"><div class="doc-row"><div class="doc-icon">PDF</div><div style="flex:1"><div class="doc-name">Academic Service SOP</div><div class="doc-meta">SOP-LAY-02 · Standard Operating Procedure · Updated 12 Oct 2026</div></div><span class="pill green">Indexed</span><button class="btn small" onclick="showToast('Document preview is simulated.')">View</button></div><div class="doc-row"><div class="doc-icon">PDF</div><div style="flex:1"><div class="doc-name">Student Academic Regulations</div><div class="doc-meta">REG-AKD-01 · University Regulation · Updated 04 Aug 2026</div></div><span class="pill green">Indexed</span><button class="btn small" onclick="showToast('Document preview is simulated.')">View</button></div><div class="doc-row"><div class="doc-icon">DOC</div><div style="flex:1"><div class="doc-name">Student Service Standards</div><div class="doc-meta">STD-MHS-03 · Service Standard · Updated 20 Sep 2026</div></div><span class="pill orange">Pending</span><button class="btn small" onclick="showToast('Document preview is simulated.')">View</button></div><div class="doc-row"><div class="doc-icon">PDF</div><div style="flex:1"><div class="doc-name">Digital Academic Services Guide</div><div class="doc-meta">GUIDE-DIG-04 · User Guide · Updated 02 Nov 2026</div></div><span class="pill green">Indexed</span><button class="btn small" onclick="showToast('Document preview is simulated.')">View</button></div></div></div>
      </section>
      <!-- RECOMMENDATIONS -->
      <section class="page" id="page-recommendations">
        <div class="ai-note"><b>Preview</b> Illustrative recommendation records. Task B generation and action tracking are not connected on this branch.</div>
        <div class="page-head"><div><div class="eyebrow">Action management</div><h1>Improvement Recommendations</h1><p class="subhead">Review AI-generated proposals and record staff decisions.</p></div><button class="btn" id="exportRecommendations">↓ Export list</button></div>
        <div class="grid stats" style="grid-template-columns:repeat(3,minmax(0,1fr))"><div class="card stat"><div class="stat-top">Total recommendations</div><div class="stat-value">12</div><div class="stat-foot">Generated from reviewed findings</div></div><div class="card stat"><div class="stat-top">Awaiting decision</div><div class="stat-value" id="pendingRecs">4</div><div class="stat-foot">Require staff review</div></div><div class="card stat"><div class="stat-top">Approved / in progress</div><div class="stat-value">8</div><div class="stat-foot">Recorded for follow-up</div></div></div>
        <div class="card"><div class="card-head"><div><div class="card-title">Recommendation register</div><div class="card-desc">Illustrative records only · not connected to Task B storage</div></div><div class="filters"><input class="field search-field" id="recSearch" placeholder="Search recommendations..." /><select class="field" id="recFilter"><option value="all">All statuses</option><option value="Awaiting decision">Awaiting decision</option><option value="Approved">Approved</option><option value="In progress">In progress</option></select></div></div><div class="table-wrap"><table id="recTable"><thead><tr><th>ID</th><th>Recommendation</th><th>Related finding</th><th>Priority</th><th>Status</th><th>Action</th></tr></thead><tbody><tr><td><strong>REC-012</strong></td><td><strong>Improve academic letter processing workflow</strong></td><td>FIND-001</td><td><span class="pill red">High</span></td><td><span class="pill orange">Awaiting decision</span></td><td><button class="btn small success" onclick="approveRecommendation(this)">Approve</button></td></tr><tr><td><strong>REC-011</strong></td><td><strong>Standardize information at service counters</strong></td><td>FIND-002</td><td><span class="pill red">High</span></td><td><span class="pill green">Approved</span></td><td><button class="btn small" onclick="showToast('REC-011 details opened (demo).')">View</button></td></tr><tr><td><strong>REC-010</strong></td><td><strong>Improve online service instructions</strong></td><td>FIND-005</td><td><span class="pill orange">Medium</span></td><td><span class="pill blue">In progress</span></td><td><button class="btn small" onclick="showToast('REC-010 details opened (demo).')">View</button></td></tr></tbody></table></div></div>
      </section>
    </div>
  </main>
</div>
<div class="toast" id="toast" role="status"></div>
<div class="modal-backdrop" id="modalBackdrop"><div class="modal"><h3 id="modalTitle">Prototype action</h3><p id="modalText">This action is simulated in the standalone prototype.</p><div class="modal-actions"><button class="btn" id="modalClose">Close</button><button class="btn primary" id="modalConfirm">Continue</button></div></div></div>


</body>
</html>
