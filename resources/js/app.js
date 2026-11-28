
  const titles = { dashboard:'Dashboard', inbox:'Feedback Inbox', 'task-a':'Analyze Student Feedback', 'task-b':'Prioritize Service Quality', documents:'University Documents', recommendations:'Improvement Recommendations' };
  function goTo(page) {
    if (!titles[page]) return;
    document.querySelectorAll('.page').forEach(p => p.classList.toggle('active', p.id === 'page-' + page));
    document.querySelectorAll('.nav button').forEach(b => b.classList.toggle('active', b.dataset.page === page));
    document.getElementById('crumbTitle').textContent = titles[page];
    document.getElementById('sidebar').classList.remove('open');
    window.scrollTo({top:0, behavior:'smooth'});
  }
  document.querySelectorAll('.nav button').forEach(b => b.addEventListener('click', () => goTo(b.dataset.page)));
  document.querySelectorAll('[data-goto]').forEach(b => b.addEventListener('click', () => goTo(b.dataset.goto)));
  document.getElementById('menuBtn').addEventListener('click', () => document.getElementById('sidebar').classList.toggle('open'));
  let toastTimer;
  function showToast(message) {
    const el = document.getElementById('toast'); el.textContent = message; el.classList.add('show');
    clearTimeout(toastTimer); toastTimer = setTimeout(() => el.classList.remove('show'), 2800);
  }
  function openModal(title, text, confirmLabel='Got it') {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalText').textContent = text;
    document.getElementById('modalConfirm').textContent = confirmLabel;
    document.getElementById('modalBackdrop').classList.add('show');
  }
  function closeModal(){ document.getElementById('modalBackdrop').classList.remove('show'); }
  document.getElementById('modalClose').addEventListener('click', closeModal);
  document.getElementById('modalConfirm').addEventListener('click', closeModal);
  document.getElementById('modalBackdrop').addEventListener('click', e => { if(e.target.id==='modalBackdrop') closeModal(); });
  function showDetails(id) { openModal('Finding details · '+id, 'This panel would show all extracted aspects, sentiment labels, reason categories, supporting feedback IDs, exact evidence quotations, and the staff review history. The standalone prototype uses sample data.'); }
  function approveFinding(button) {
    const card = button.closest('.finding-card');
    if (!card || card.dataset.review === 'approved') return;
    card.dataset.review = 'approved';
    const pill = card.querySelector('.review-pill'); pill.textContent = 'Approved'; pill.className = 'pill green review-pill';
    card.querySelector('.review-actions').innerHTML = '<button class="btn small" onclick="showDetails(\'FIND-001\')">View details</button> <span class="pill green">✓ Approved by staff</span>';
    const pending = document.querySelectorAll('.finding-card[data-review="pending"]').length;
    document.getElementById('pendingFindings').textContent = pending + 6;
    showToast('Finding approved in this demo session.');
  }
  function approveRecommendation(button) {
    const row = button.closest('tr');
    if (row) {
      const status = row.querySelector('td:nth-child(5)');
      status.innerHTML = '<span class="pill green">Approved</span>';
      button.textContent = 'View'; button.className = 'btn small'; button.onclick = () => showToast('Recommendation is already approved (demo).');
    } else {
      const label = button.closest('.card-body')?.querySelector('.muted');
      if (label) label.textContent = 'Status: Approved by staff';
      button.textContent = '✓ Approved'; button.disabled = true; button.classList.remove('success');
    }
    showToast('Recommendation approved in this demo session.');
  }
  document.getElementById('runAnalysis').addEventListener('click', () => openModal('Run AI analysis', 'The Task A API and background job pipeline are implemented. The feedback selection/import workflow is not connected to this dashboard yet. Create a feedback batch and records before dispatching the Laravel analysis job.', 'Understood'));
  document.getElementById('generateRec').addEventListener('click', () => openModal('Generate recommendation', 'In the complete application, approved findings, IPA results, and retrieved university document passages would be sent to the Task B RAG workflow. The LLM would generate a draft recommendation for staff review. No real AI request is made by this prototype.', 'Understood'));
  document.getElementById('importBtn').addEventListener('click', () => openModal('Import feedback', 'A full version would allow staff to upload a CSV or Excel file, map columns, validate records, and save the feedback batch. File import is not connected in this HTML prototype.', 'Close'));
  document.getElementById('uploadDoc').addEventListener('click', () => openModal('Add university document', 'A full version would accept an institutional PDF or DOCX, extract its text, split it into chunks, create embeddings, and index the content for retrieval. Upload and indexing are not connected here.', 'Close'));
  document.getElementById('guideBtn').addEventListener('click', () => openModal('FeedbackTrace workflow', '1. Import student feedback. 2. Task A extracts aspects, sentiment, reasons, and evidence-linked findings. 3. Staff review and approve findings. 4. Task B calculates IPA priorities and retrieves institutional documents. 5. The LLM drafts grounded recommendations. 6. Staff approve and track improvement actions.'));
  document.getElementById('notificationBtn').addEventListener('click', () => showToast('You are all caught up.'));
  document.getElementById('exportDashboard').addEventListener('click', () => showToast('Overview export is a placeholder in this demo.'));
  document.getElementById('exportRecommendations').addEventListener('click', () => showToast('Recommendation export is a placeholder in this demo.'));
  // Feedback inbox filtering
  function filterFeedback(){
    const q = document.getElementById('feedbackSearch').value.toLowerCase();
    const s = document.getElementById('sentimentFilter').value; let visible=0;
    document.querySelectorAll('#feedbackTable tbody tr').forEach(row=>{
      const text = row.innerText.toLowerCase(); const matchQ = text.includes(q); const matchS = s==='all' || row.children[3].innerText.trim()===s;
      row.style.display = matchQ && matchS ? '' : 'none'; if(matchQ && matchS) visible++;
    });
    document.getElementById('feedbackCount').textContent = `Showing ${visible} matching sample records`;
  }
  document.getElementById('feedbackSearch').addEventListener('input', filterFeedback);
  document.getElementById('sentimentFilter').addEventListener('change', filterFeedback);
  // Finding filter
  document.getElementById('findingFilter').addEventListener('change', e=>{
    const val=e.target.value; document.querySelectorAll('.finding-card').forEach(card=>{card.style.display=val==='all'||card.dataset.review===val?'':'none';});
  });
  // Document search
  document.getElementById('docSearch').addEventListener('input', e=>{
    const q=e.target.value.toLowerCase(); document.querySelectorAll('#docList .doc-row').forEach(row=>row.style.display=row.innerText.toLowerCase().includes(q)?'':'none');
  });
  // Recommendation filters
  function filterRecs(){
    const q=document.getElementById('recSearch').value.toLowerCase(), s=document.getElementById('recFilter').value;
    document.querySelectorAll('#recTable tbody tr').forEach(row=>{const status=row.children[4].innerText.trim(); row.style.display=row.innerText.toLowerCase().includes(q)&&(s==='all'||status===s)?'':'none';});
  }
  document.getElementById('recSearch').addEventListener('input',filterRecs); document.getElementById('recFilter').addEventListener('change',filterRecs);
  // Lightweight global search routes to the inbox and filters its rows.
  document.getElementById('globalSearch').addEventListener('keydown', e=>{ if(e.key==='Enter' && e.target.value.trim()){goTo('inbox');document.getElementById('feedbackSearch').value=e.target.value;filterFeedback();} });

function showFindingDetails(button) {
  const code = button.dataset.finding || 'Finding';
  const title = button.dataset.title || '';
  const summary = button.dataset.summary || '';
  const references = button.dataset.references || 'No feedback references';
  openModal('Finding details · ' + code, title + '\\n\\n' + summary + '\\n\\nSupporting feedback: ' + references);
}
Object.assign(window, { showToast, showDetails, showFindingDetails, approveFinding, approveRecommendation });
