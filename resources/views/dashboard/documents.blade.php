@extends('layouts.feedbacktrace')

@section('page-title', 'Documents')
@section('breadcrumb', 'Documents')

@section('content')
<section class="ft-page-heading">
    <div><div class="ft-eyebrow">KNOWLEDGE BASE</div><h1>Documents</h1><p>Manage university policies and reference materials used to ground recommendations.</p></div>
    <button class="ft-button ft-button-primary" type="button" data-open-document-upload>＋ Add document</button>
</section>

<section class="ft-kpi-grid ft-kpi-grid-three">
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">TOTAL DOCUMENTS</span><span class="ft-kpi-icon ft-kpi-blue">▧</span></div><div class="ft-kpi-value">24</div><div class="ft-kpi-foot"><span class="ft-trend-neutral">PDF and DOCX</span><span>in workspace</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">INDEXED PAGES</span><span class="ft-kpi-icon ft-kpi-violet">▤</span></div><div class="ft-kpi-value">1,284</div><div class="ft-kpi-foot"><span class="ft-trend-positive">Ready for retrieval</span></div></article>
    <article class="ft-kpi-card"><div class="ft-kpi-top"><span class="ft-kpi-label">KNOWLEDGE COVERAGE</span><span class="ft-kpi-icon ft-kpi-green">✓</span></div><div class="ft-kpi-value">92<span class="ft-kpi-unit">%</span></div><div class="ft-kpi-foot"><span class="ft-trend-positive">Service policy topics</span></div></article>
</section>

<section class="ft-panel ft-documents-panel">
    <div class="ft-panel-header ft-panel-header-wrap"><div><div class="ft-panel-title">Document repository</div><div class="ft-panel-subtitle">University knowledge base · sample files</div></div><div class="ft-table-controls"><label class="ft-search-input"><span>⌕</span><input type="search" placeholder="Search documents..." data-table-search="documents-table"></label><select class="ft-select" data-table-filter="documents-table" data-filter-field="type" aria-label="Filter file type"><option value="">All types</option><option value="PDF">PDF</option><option value="DOCX">DOCX</option></select></div></div>
    <div class="ft-table-wrap"><table class="ft-table" id="documents-table">
        <thead><tr><th>DOCUMENT</th><th>TYPE</th><th>CATEGORY</th><th>CONTENT</th><th>STATUS</th><th>UPDATED</th><th></th></tr></thead>
        <tbody>
            <tr data-search="student services handbook policies student affairs" data-type="PDF"><td><div class="ft-document-cell"><span class="ft-file-icon">PDF</span><div><strong>Student Services Handbook 2026</strong><small>student-services-handbook-2026.pdf</small></div></div></td><td>PDF</td><td>Student affairs</td><td>42 pages · 86 chunks</td><td><span class="ft-status-pill ft-status-done">Indexed</span></td><td>21 Nov 2026</td><td><button class="ft-more-button" type="button" data-toast="Document preview is a demo interaction.">···</button></td></tr>
            <tr data-search="academic service standards charter administration" data-type="PDF"><td><div class="ft-document-cell"><span class="ft-file-icon ft-file-blue">PDF</span><div><strong>Academic Service Standards</strong><small>academic-service-standards.pdf</small></div></div></td><td>PDF</td><td>Administration</td><td>18 pages · 34 chunks</td><td><span class="ft-status-pill ft-status-done">Indexed</span></td><td>19 Nov 2026</td><td><button class="ft-more-button" type="button" data-toast="Document preview is a demo interaction.">···</button></td></tr>
            <tr data-search="digital transformation plan it strategy portal" data-type="PDF"><td><div class="ft-document-cell"><span class="ft-file-icon ft-file-green">PDF</span><div><strong>Digital Transformation Plan</strong><small>digital-transformation-plan.pdf</small></div></div></td><td>PDF</td><td>IT strategy</td><td>26 pages · 51 chunks</td><td><span class="ft-status-pill ft-status-done">Indexed</span></td><td>17 Nov 2026</td><td><button class="ft-more-button" type="button" data-toast="Document preview is a demo interaction.">···</button></td></tr>
            <tr data-search="student feedback survey procedure quality assurance" data-type="DOCX"><td><div class="ft-document-cell"><span class="ft-file-icon ft-file-word">DOC</span><div><strong>Student Feedback Procedure</strong><small>student-feedback-procedure.docx</small></div></div></td><td>DOCX</td><td>Quality assurance</td><td>12 pages · 20 chunks</td><td><span class="ft-status-pill ft-status-review">Processing</span></td><td>15 Nov 2026</td><td><button class="ft-more-button" type="button" data-toast="Document preview is a demo interaction.">···</button></td></tr>
            <tr data-search="campus facility maintenance regulation" data-type="PDF"><td><div class="ft-document-cell"><span class="ft-file-icon">PDF</span><div><strong>Campus Facility Guidelines</strong><small>campus-facility-guidelines.pdf</small></div></div></td><td>PDF</td><td>Facilities</td><td>31 pages · — chunks</td><td><span class="ft-status-pill ft-status-muted">Not indexed</span></td><td>12 Nov 2026</td><td><button class="ft-more-button" type="button" data-toast="Document preview is a demo interaction.">···</button></td></tr>
        </tbody>
    </table></div>
    <div class="ft-table-footer"><span data-table-count>Showing 5 sample documents</span><span class="ft-demo-footnote">Files shown here are illustrative only.</span></div>
</section>

<div class="ft-panel ft-upload-inline"><div class="ft-upload-inline-icon">⇧</div><div><strong>Add a university reference</strong><p>PDF or DOCX documents can be selected to preview the upload workflow. Files are not sent or stored in this demo.</p></div><button class="ft-button ft-button-secondary" type="button" data-open-document-upload>Choose file</button><input type="file" hidden accept=".pdf,.doc,.docx" data-document-file></div>
@endsection
