'use client';

import { type FormEvent, useEffect, useMemo, useState } from 'react';
import { api, getToken, setToken } from '@/lib/api';

type User = { id: number; name: string; email: string; role: string };
type Batch = { id: number; batchCode: string; title: string | null; status: string; _count: { records: number; findings: number } };
type Evidence = { quote: string; startChar: number | null; endChar: number | null; status: string };
type Mention = {
  aspectCode: string; proposedAspectLabel: string | null; sentiment: string;
  reasonCategory: string; reasonType: string; reasonDescription: string | null; evidence: Evidence[];
};
type FeedbackRecord = {
  id: number; feedbackId: string; questionId: string; commentText: string;
  submittedAt: string | null; qualityFlags: string[] | null;
  analysis: { status: string; qualityFlags: string[] | null; modelId: string | null; errorMessage: string | null; aspectMentions: Mention[] } | null;
};
type BatchDetails = Batch & { records: FeedbackRecord[]; findings: Finding[] };
type Finding = {
  id: number; findingCode: string; issueTitle: string; issueSummary: string; aspectCode: string;
  findingType: string; reviewStatus: string; uniqueFeedbackCount: number; mentionCount: number;
  sentimentDistribution: Record<string, number>; minorityViews: Array<{ feedback_id?: string; sentiment?: string; summary?: string }>;
};
type FindingDetails = Finding & {
  feedbackLinks: Array<{ feedbackRecord: FeedbackRecord }>;
  reviews: Array<{ action: string; note: string | null; createdAt: string; reviewer: { name: string } | null }>;
};

export default function HomePage() {
  const [token, setTokenState] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [page, setPage] = useState<'overview' | 'batches' | 'batch-detail' | 'findings' | 'ipa'>('overview');
  const [batches, setBatches] = useState<Batch[]>([]);
  const [findings, setFindings] = useState<Finding[]>([]);
  const [selectedBatch, setSelectedBatch] = useState<BatchDetails | null>(null);
  const [selectedFinding, setSelectedFinding] = useState<FindingDetails | null>(null);
  const [editTitle, setEditTitle] = useState('');
  const [editSummary, setEditSummary] = useState('');
  const [editNote, setEditNote] = useState('');
  const [csv, setCsv] = useState<File | null>(null);
  const [batchTitle, setBatchTitle] = useState('');
  const [importance, setImportance] = useState('4');
  const [performance, setPerformance] = useState('3');
  const [aspect, setAspect] = useState('service_speed');
  const [ipaResult, setIpaResult] = useState<unknown>(null);
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  async function loadWorkspace() {
    const [batchRows, findingRows] = await Promise.all([
      api<Batch[]>('/api/v1/task-a/batches'),
      api<Finding[]>('/api/v1/task-a/findings'),
    ]);
    setBatches(batchRows);
    setFindings(findingRows);
  }

  useEffect(() => {
    const existing = getToken();
    if (!existing) return;
    setTokenState(existing);
    api<User>('/api/v1/auth/me').then(setUser).catch(() => {
      setToken(null);
      setTokenState(null);
    });
  }, []);

  useEffect(() => {
    if (!token || !user) return;
    loadWorkspace().catch((reason: unknown) => setError(reason instanceof Error ? reason.message : 'Unable to load workspace.'));
  }, [token, user]);

  const stats = useMemo(() => ({
    batches: batches.length,
    feedback: batches.reduce((sum, batch) => sum + batch._count.records, 0),
    findings: findings.length,
    pending: findings.filter((finding) => finding.reviewStatus === 'pending_review').length,
  }), [batches, findings]);

  async function login(event: FormEvent) {
    event.preventDefault();
    setLoading(true); setError('');
    try {
      const result = await api<{ accessToken: string; user: User }>('/api/v1/auth/login', {
        method: 'POST', body: JSON.stringify({ email, password }),
      });
      setToken(result.accessToken);
      setTokenState(result.accessToken);
      setUser(result.user);
      setPassword('');
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Sign in failed.');
    } finally { setLoading(false); }
  }

  function logout() {
    setToken(null); setTokenState(null); setUser(null);
    setBatches([]); setFindings([]); setSelectedBatch(null); setSelectedFinding(null);
  }

  async function openBatch(batch: Batch) {
    setLoading(true); setError(''); setMessage('');
    try {
      const detail = await api<BatchDetails>('/api/v1/task-a/batches/' + batch.id + '/results');
      setSelectedBatch(detail);
      setPage('batch-detail');
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Unable to load batch details.');
    } finally { setLoading(false); }
  }

  async function openFinding(finding: Finding) {
    setSelectedFinding(null); setLoading(true); setError(''); setMessage('');
    try {
      const detail = await api<FindingDetails>('/api/v1/task-a/findings/' + encodeURIComponent(finding.findingCode));
      setSelectedFinding(detail);
      setEditTitle(detail.issueTitle);
      setEditSummary(detail.issueSummary);
      setEditNote('');
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Unable to load finding details.');
    } finally { setLoading(false); }
  }

  async function importBatch(event: FormEvent) {
    event.preventDefault();
    if (!csv) { setError('Select a CSV file first.'); return; }
    setLoading(true); setError(''); setMessage('');
    try {
      const form = new FormData();
      form.set('file', csv);
      if (batchTitle) form.set('title', batchTitle);
      form.set('source', 'csv_import');
      const result = await api<{ batchCode: string; feedbackCount: number }>('/api/v1/task-a/batches', { method: 'POST', body: form });
      setMessage('Imported ' + result.feedbackCount + ' feedback records. Analysis is queued for batch ' + result.batchCode + '.');
      setCsv(null); setBatchTitle('');
      await loadWorkspace();
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'CSV import failed.');
    } finally { setLoading(false); }
  }

  async function review(action: 'approve' | 'reject' | 'edit') {
    if (!selectedFinding) return;
    setLoading(true); setError(''); setMessage('');
    try {
      const body = action === 'edit'
        ? { action, issueTitle: editTitle, issueSummary: editSummary, note: editNote }
        : { action, note: editNote || 'Reviewed in FeedbackTrace Node.js workspace.' };
      await api('/api/v1/task-a/findings/' + encodeURIComponent(selectedFinding.findingCode) + '/review', {
        method: 'POST', body: JSON.stringify(body),
      });
      setMessage('Finding ' + selectedFinding.findingCode + ' updated: ' + action + '.');
      setSelectedFinding(null);
      await loadWorkspace();
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'Review failed.');
    } finally { setLoading(false); }
  }

  async function calculateIpa(event: FormEvent) {
    event.preventDefault(); setLoading(true); setError('');
    try {
      const result = await api('/api/v1/task-b/calculate-ipa', {
        method: 'POST',
        body: JSON.stringify({ responses: [{ aspectCode: aspect, importance: Number(importance), performance: Number(performance) }] }),
      });
      setIpaResult(result);
    } catch (reason) {
      setError(reason instanceof Error ? reason.message : 'IPA calculation failed.');
    } finally { setLoading(false); }
  }

  if (!token || !user) {
    return <main className="login-shell"><form className="login-card" onSubmit={login}>
      <div className="brand-mark">F</div><p className="eyebrow">NODE.JS EDITION</p><h1>FeedbackTrace</h1>
      <p className="muted">Sign in with an authorized staff account to analyze student feedback and review findings.</p>
      {error && <div className="alert error">{error}</div>}
      <label>Email<input type="email" autoComplete="username" required value={email} onChange={(e) => setEmail(e.target.value)} /></label>
      <label>Password<input type="password" autoComplete="current-password" required value={password} onChange={(e) => setPassword(e.target.value)} /></label>
      <button className="primary full" disabled={loading}>{loading ? 'Signing in…' : 'Sign in'}</button>
      <small className="muted">Create the first staff account with the documented seed command.</small>
    </form></main>;
  }

  const pageTitle = page === 'ipa' ? 'Task B · IPA'
    : page === 'batch-detail' ? 'Feedback batch detail'
    : page[0].toUpperCase() + page.slice(1);

  return <div className="app-shell">
    <aside className="sidebar">
      <a className="brand" href="#"><span className="brand-mark">F</span><span><b>FeedbackTrace</b><small>INSIGHT & DECISION SUPPORT</small></span></a>
      <p className="nav-label">WORKSPACE</p>
      {(['overview', 'batches', 'findings', 'ipa'] as const).map((item) =>
        <button key={item} className={'nav-item ' + (page === item || (item === 'batches' && page === 'batch-detail') ? 'active' : '')}
          onClick={() => { setPage(item); setSelectedFinding(null); setSelectedBatch(null); }}>
          {({ overview: 'Overview', batches: 'Feedback batches', findings: 'Finding review', ipa: 'Task B · IPA' } as const)[item]}
        </button>)}
      <div className="sidebar-bottom"><div><b>{user.name}</b><small>{user.role}</small></div><button className="quiet" onClick={logout}>Sign out</button></div>
    </aside>
    <main className="main">
      <header className="topbar"><div><span>Workspace</span><b>/</b>{pageTitle}</div><span className="user-label">{user.email}</span></header>
      <section className="content">
        {message && <div className="alert success">{message}</div>}
        {error && <div className="alert error">{error}</div>}
        {page === 'overview' && <>
          <div className="page-heading"><div><p className="eyebrow">STUDENT FEEDBACK INTELLIGENCE</p><h1>Overview</h1><p className="muted">Operational summary from saved Task A feedback records.</p></div><button className="primary" onClick={() => setPage('batches')}>Import feedback</button></div>
          <div className="stat-grid">
            <Stat label="Feedback batches" value={stats.batches} />
            <Stat label="Feedback records" value={stats.feedback} />
            <Stat label="Consolidated findings" value={stats.findings} />
            <Stat label="Awaiting staff review" value={stats.pending} />
          </div>
          <Panel title="Latest batches"><BatchTable batches={batches.slice(0, 6)} onSelect={openBatch} /></Panel>
          <Panel title="Recent findings"><FindingTable findings={findings.slice(0, 6)} onSelect={openFinding} /></Panel>
        </>}
        {page === 'batches' && <>
          <div className="page-heading"><div><p className="eyebrow">TASK A · INPUT & PROCESSING</p><h1>Feedback batches</h1><p className="muted">Upload a CSV and queue NLP analysis in the background.</p></div></div>
          <Panel title="Import feedback CSV">
            <form className="import-form" onSubmit={importBatch}>
              <label>Batch title<input value={batchTitle} onChange={(e) => setBatchTitle(e.target.value)} placeholder="Student services · November 2026" /></label>
              <label>CSV file<input type="file" accept=".csv,text/csv" onChange={(e) => setCsv(e.target.files?.[0] ?? null)} required /></label>
              <p className="muted">Required columns: feedback_id, question_id, comment_text. Optional: submitted_at, language_hint. Maximum 10 MB and 5,000 rows.</p>
              <button className="primary" disabled={loading || !csv}>{loading ? 'Importing…' : 'Import and queue analysis'}</button>
            </form>
          </Panel>
          <Panel title="All batches"><BatchTable batches={batches} onSelect={openBatch} /></Panel>
        </>}
        {page === 'batch-detail' && selectedBatch && <>
          <div className="page-heading">
            <div><p className="eyebrow">TASK A · {selectedBatch.batchCode}</p><h1>{selectedBatch.title || selectedBatch.batchCode}</h1><p className="muted">Batch status: {selectedBatch.status}</p></div>
            <button onClick={() => { setSelectedBatch(null); setPage('batches'); }}>Back to batches</button>
          </div>
          <div className="stat-grid">
            <Stat label="Feedback records" value={selectedBatch.records.length} />
            <Stat label="Consolidated findings" value={selectedBatch.findings.length} />
            <Stat label="Analyzed" value={selectedBatch.records.filter((record) => record.analysis?.status === 'analyzed').length} />
            <Stat label="Failed / skipped" value={selectedBatch.records.filter((record) => ['failed', 'skipped'].includes(record.analysis?.status ?? '')).length} />
          </div>
          <Panel title="Original feedback and analysis evidence">
            {selectedBatch.records.map((record) => <article className="feedback-detail" key={record.id}>
              <div className="evidence-heading"><b>{record.feedbackId}</b><span>{record.questionId}</span><span className={'status ' + (record.analysis?.status ?? 'pending')}>{record.analysis?.status ?? 'pending'}</span></div>
              <p className="source-comment">{record.commentText || '[Empty comment]'}</p>
              {record.analysis?.errorMessage && <p className="muted">{record.analysis.errorMessage}</p>}
              {record.analysis?.aspectMentions.map((mention, index) => <div className="mention-detail" key={record.id + '-' + index}>
                <div className="evidence-heading"><b>{mention.proposedAspectLabel || mention.aspectCode}</b><span>{mention.sentiment}</span><span>{mention.reasonCategory}</span></div>
                <p className="muted">{mention.reasonDescription || 'No reason description returned.'}</p>
                {mention.evidence.map((item, evidenceIndex) => <blockquote className="evidence-quote" key={evidenceIndex}>
                  “{item.quote}” <small>{item.status} · {item.startChar ?? '—'}–{item.endChar ?? '—'}</small>
                </blockquote>)}
              </div>)}
            </article>)}
          </Panel>
        </>}
        {page === 'findings' && <>
          <div className="page-heading"><div><p className="eyebrow">TASK A · HUMAN VALIDATION</p><h1>Finding review</h1><p className="muted">Only approved findings are eligible for the Task B handoff.</p></div></div>
          {selectedFinding && <Panel title={selectedFinding.findingCode}>
            <h3>{selectedFinding.issueTitle}</h3><p>{selectedFinding.issueSummary}</p>
            <p className="muted">Aspect: {selectedFinding.aspectCode} · Support: {selectedFinding.uniqueFeedbackCount} feedback / {selectedFinding.mentionCount} mentions</p>
            <div className="evidence-heading">{Object.entries(selectedFinding.sentimentDistribution ?? {}).map(([label, count]) => <span key={label}>{label}: {count}</span>)}</div>
            {selectedFinding.minorityViews?.length > 0 && <div className="detail-list"><b>Minority views</b>{selectedFinding.minorityViews.map((view, index) => <p key={index} className="muted">{view.feedback_id || 'Feedback'} · {view.sentiment || 'different view'} · {view.summary || 'Alternative view retained.'}</p>)}</div>}
            <div className="detail-list"><b>Supporting original feedback</b>
              {selectedFinding.feedbackLinks.map((link) => <article className="feedback-detail" key={link.feedbackRecord.id}>
                <div className="evidence-heading"><b>{link.feedbackRecord.feedbackId}</b><span>{link.feedbackRecord.questionId}</span></div>
                <p className="source-comment">{link.feedbackRecord.commentText}</p>
                {link.feedbackRecord.analysis?.aspectMentions.flatMap((mention) => mention.evidence).map((item, index) =>
                  <blockquote className="evidence-quote" key={index}>“{item.quote}” <small>{item.status}</small></blockquote>)}
              </article>)}
            </div>
            {selectedFinding.reviewStatus === 'pending_review' ? <>
              <div className="import-form">
                <label>Edited issue title<input value={editTitle} onChange={(e) => setEditTitle(e.target.value)} maxLength={255} /></label>
                <label>Edited issue summary<textarea value={editSummary} onChange={(e) => setEditSummary(e.target.value)} rows={3} maxLength={2000} /></label>
                <label>Staff note<textarea value={editNote} onChange={(e) => setEditNote(e.target.value)} rows={2} maxLength={2000} /></label>
              </div>
              <div className="actions">
                <button className="primary" disabled={loading || !editTitle.trim() || !editSummary.trim()} onClick={() => review('edit')}>Save edits</button>
                <button className="primary" disabled={loading} onClick={() => review('approve')}>Approve finding</button>
                <button className="danger" disabled={loading} onClick={() => review('reject')}>Reject finding</button>
                <button onClick={() => setSelectedFinding(null)}>Close</button>
              </div>
            </> : <p className="muted">This finding has already been reviewed. Review history: {selectedFinding.reviews.map((item) => item.action + ' by ' + (item.reviewer?.name ?? 'unknown reviewer')).join(' · ') || 'No actions recorded.'}</p>}
          </Panel>}
          <Panel title="Consolidated findings"><FindingTable findings={findings} onSelect={openFinding} /></Panel>
        </>}
        {page === 'ipa' && <>
          <div className="page-heading"><div><p className="eyebrow">TASK B · DECISION INTELLIGENCE</p><h1>Importance–Performance Analysis</h1><p className="muted">Calculate mean importance and performance scores by service aspect.</p></div></div>
          <Panel title="Calculate IPA">
            <form className="import-form" onSubmit={calculateIpa}>
              <label>Aspect code<input value={aspect} onChange={(e) => setAspect(e.target.value)} required /></label>
              <label>Importance (1–5)<input type="number" min="1" max="5" step="0.1" value={importance} onChange={(e) => setImportance(e.target.value)} /></label>
              <label>Performance (1–5)<input type="number" min="1" max="5" step="0.1" value={performance} onChange={(e) => setPerformance(e.target.value)} /></label>
              <button className="primary" disabled={loading}>Calculate IPA</button>
            </form>
            {ipaResult && <pre className="result-json">{JSON.stringify(ipaResult, null, 2)}</pre>}
          </Panel>
        </>}
      </section>
    </main>
  </div>;
}

function Stat({ label, value }: { label: string; value: number }) {
  return <article className="stat-card"><span>{label}</span><strong>{value.toLocaleString()}</strong></article>;
}

function Panel({ title, children }: { title: string; children: React.ReactNode }) {
  return <section className="panel"><header><h2>{title}</h2></header><div className="panel-content">{children}</div></section>;
}

function BatchTable({ batches, onSelect }: { batches: Batch[]; onSelect: (batch: Batch) => void }) {
  if (!batches.length) return <p className="muted">No batches have been imported yet.</p>;
  return <div className="table-wrap"><table><thead><tr><th>Batch</th><th>Records</th><th>Findings</th><th>Status</th><th></th></tr></thead><tbody>
    {batches.map((batch) => <tr key={batch.id}><td><b>{batch.title || batch.batchCode}</b><small>{batch.batchCode}</small></td><td>{batch._count.records}</td><td>{batch._count.findings}</td><td><span className={'status ' + batch.status}>{batch.status.replaceAll('_', ' ')}</span></td><td><button className="link-button" onClick={() => onSelect(batch)}>Open batch</button></td></tr>)}
  </tbody></table></div>;
}

function FindingTable({ findings, onSelect }: { findings: Finding[]; onSelect: (finding: Finding) => void }) {
  if (!findings.length) return <p className="muted">No findings available yet.</p>;
  return <div className="table-wrap"><table><thead><tr><th>Finding</th><th>Aspect</th><th>Support</th><th>Review</th><th></th></tr></thead><tbody>
    {findings.map((finding) => <tr key={finding.id}><td><b>{finding.issueTitle}</b><small>{finding.findingCode}</small></td><td>{finding.aspectCode}</td><td>{finding.uniqueFeedbackCount} feedback</td><td><span className={'status ' + finding.reviewStatus}>{finding.reviewStatus.replaceAll('_', ' ')}</span></td><td><button className="link-button" onClick={() => onSelect(finding)}>View / review</button></td></tr>)}
  </tbody></table></div>;
}
