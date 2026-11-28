'use client';

import { FormEvent, useEffect, useMemo, useState } from 'react';
import { api, getToken, setToken } from '@/lib/api';

type User = { id: number; name: string; email: string; role: string };
type Batch = { id: number; batchCode: string; title: string | null; status: string; _count: { records: number; findings: number } };
type Finding = {
  id: number; findingCode: string; issueTitle: string; issueSummary: string; aspectCode: string;
  findingType: string; reviewStatus: string; uniqueFeedbackCount: number; mentionCount: number;
  sentimentDistribution: Record<string, number>;
};

export default function HomePage() {
  const [token, setTokenState] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [page, setPage] = useState<'overview' | 'batches' | 'findings' | 'ipa'>('overview');
  const [batches, setBatches] = useState<Batch[]>([]);
  const [findings, setFindings] = useState<Finding[]>([]);
  const [selectedFinding, setSelectedFinding] = useState<Finding | null>(null);
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
    setToken(null); setTokenState(null); setUser(null); setBatches([]); setFindings([]);
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

  async function review(action: 'approve' | 'reject') {
    if (!selectedFinding) return;
    setLoading(true); setError(''); setMessage('');
    try {
      await api('/api/v1/task-a/findings/' + encodeURIComponent(selectedFinding.findingCode) + '/review', {
        method: 'POST', body: JSON.stringify({ action, note: 'Reviewed in FeedbackTrace Node.js workspace.' }),
      });
      setMessage('Finding ' + selectedFinding.findingCode + ' marked ' + action + '.');
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

  return <div className="app-shell">
    <aside className="sidebar">
      <a className="brand" href="#"><span className="brand-mark">F</span><span><b>FeedbackTrace</b><small>INSIGHT & DECISION SUPPORT</small></span></a>
      <p className="nav-label">WORKSPACE</p>
      {(['overview', 'batches', 'findings', 'ipa'] as const).map((item) =>
        <button key={item} className={'nav-item ' + (page === item ? 'active' : '')} onClick={() => { setPage(item); setSelectedFinding(null); }}>
          {({ overview: 'Overview', batches: 'Feedback batches', findings: 'Finding review', ipa: 'Task B · IPA' } as const)[item]}
        </button>)}
      <div className="sidebar-bottom"><div><b>{user.name}</b><small>{user.role}</small></div><button className="quiet" onClick={logout}>Sign out</button></div>
    </aside>
    <main className="main">
      <header className="topbar"><div><span>Workspace</span><b>/</b>{page === 'ipa' ? 'Task B · IPA' : page[0].toUpperCase() + page.slice(1)}</div><span className="user-label">{user.email}</span></header>
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
          <Panel title="Latest batches"><BatchTable batches={batches.slice(0, 6)} /></Panel>
          <Panel title="Recent findings"><FindingTable findings={findings.slice(0, 6)} onSelect={setSelectedFinding} /></Panel>
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
          <Panel title="All batches"><BatchTable batches={batches} /></Panel>
        </>}
        {page === 'findings' && <>
          <div className="page-heading"><div><p className="eyebrow">TASK A · HUMAN VALIDATION</p><h1>Finding review</h1><p className="muted">Only approved findings are eligible for the Task B handoff.</p></div></div>
          {selectedFinding && <Panel title={selectedFinding.findingCode}>
            <h3>{selectedFinding.issueTitle}</h3><p>{selectedFinding.issueSummary}</p>
            <p className="muted">Aspect: {selectedFinding.aspectCode} · Support: {selectedFinding.uniqueFeedbackCount} feedback / {selectedFinding.mentionCount} mentions</p>
            {selectedFinding.reviewStatus === 'pending_review' ? <div className="actions">
              <button className="primary" disabled={loading} onClick={() => review('approve')}>Approve</button>
              <button className="danger" disabled={loading} onClick={() => review('reject')}>Reject</button>
              <button onClick={() => setSelectedFinding(null)}>Close</button>
            </div> : <p className="muted">This finding has already been reviewed.</p>}
          </Panel>}
          <Panel title="Consolidated findings"><FindingTable findings={findings} onSelect={setSelectedFinding} /></Panel>
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

function BatchTable({ batches }: { batches: Batch[] }) {
  if (!batches.length) return <p className="muted">No batches have been imported yet.</p>;
  return <div className="table-wrap"><table><thead><tr><th>Batch</th><th>Records</th><th>Findings</th><th>Status</th></tr></thead><tbody>
    {batches.map((batch) => <tr key={batch.id}><td><b>{batch.title || batch.batchCode}</b><small>{batch.batchCode}</small></td><td>{batch._count.records}</td><td>{batch._count.findings}</td><td><span className={'status ' + batch.status}>{batch.status.replaceAll('_', ' ')}</span></td></tr>)}
  </tbody></table></div>;
}

function FindingTable({ findings, onSelect }: { findings: Finding[]; onSelect: (finding: Finding) => void }) {
  if (!findings.length) return <p className="muted">No findings available yet.</p>;
  return <div className="table-wrap"><table><thead><tr><th>Finding</th><th>Aspect</th><th>Support</th><th>Review</th><th></th></tr></thead><tbody>
    {findings.map((finding) => <tr key={finding.id}><td><b>{finding.issueTitle}</b><small>{finding.findingCode}</small></td><td>{finding.aspectCode}</td><td>{finding.uniqueFeedbackCount} feedback</td><td><span className={'status ' + finding.reviewStatus}>{finding.reviewStatus.replaceAll('_', ' ')}</span></td><td><button className="link-button" onClick={() => onSelect(finding)}>View / review</button></td></tr>)}
  </tbody></table></div>;
}
