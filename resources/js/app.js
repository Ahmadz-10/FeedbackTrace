document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('.ft-app-shell');
    const toastRegion = document.querySelector('[data-toast-region]');

    function toast(message) {
        if (!toastRegion) return;
        const item = document.createElement('div');
        item.className = 'ft-toast';
        item.textContent = message;
        toastRegion.appendChild(item);
        window.setTimeout(() => item.remove(), 3000);
    }

    document.addEventListener('click', (event) => {
        const toastButton = event.target.closest('[data-toast]');
        if (toastButton) toast(toastButton.dataset.toast);

        if (event.target.closest('[data-sidebar-toggle]')) shell?.classList.add('sidebar-open');
        if (event.target.closest('[data-sidebar-close]')) shell?.classList.remove('sidebar-open');
        if (event.target.closest('.ft-nav-link')) shell?.classList.remove('sidebar-open');
    });

    const globalSearch = document.querySelector('[data-global-search]');
    globalSearch?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && globalSearch.value.trim()) {
            const q = globalSearch.value.trim().toLowerCase();
            const rows = Array.from(document.querySelectorAll('.ft-table tbody tr, .ft-recommendation-card'));
            let matches = 0;
            rows.forEach(row => {
                const match = row.textContent.toLowerCase().includes(q);
                row.hidden = !match;
                if (match) matches += 1;
            });
            toast(matches + ' matching item(s) on this page.');
        }
    });
    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            globalSearch?.focus();
        }
        if (event.key === 'Escape') {
            document.querySelectorAll('.ft-modal-backdrop:not([hidden])').forEach(modal => modal.hidden = true);
            shell?.classList.remove('sidebar-open');
        }
    });

    function filterTable(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const search = document.querySelector('[data-table-search="' + tableId + '"]');
        const filters = Array.from(document.querySelectorAll('[data-table-filter="' + tableId + '"]'));
        let visible = 0;
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        rows.forEach(row => {
            const q = (search?.value || '').toLowerCase().trim();
            const text = (row.dataset.search || row.textContent).toLowerCase();
            const searchOk = !q || text.includes(q);
            const filterOk = filters.every(filter => {
                if (!filter.value) return true;
                const field = filter.dataset.filterField;
                return (row.dataset[field] || '').toLowerCase() === filter.value.toLowerCase();
            });
            row.hidden = !(searchOk && filterOk);
            if (!row.hidden) visible += 1;
        });
        const count = table.closest('.ft-panel')?.querySelector('[data-table-count]');
        if (count) count.textContent = 'Showing ' + visible + ' of ' + rows.length + ' sample records';
    }

    document.querySelectorAll('[data-table-search]').forEach(input => {
        input.addEventListener('input', () => filterTable(input.dataset.tableSearch));
    });
    document.querySelectorAll('[data-table-filter]').forEach(select => {
        select.addEventListener('change', () => filterTable(select.dataset.tableFilter));
    });

    const uploadModal = document.querySelector('[data-upload-modal]');
    const evidenceModal = document.querySelector('[data-evidence-modal]');
    document.querySelectorAll('[data-open-upload]').forEach(button => button.addEventListener('click', () => { if (uploadModal) uploadModal.hidden = false; }));
    document.querySelectorAll('[data-close-upload]').forEach(button => button.addEventListener('click', () => { if (uploadModal) uploadModal.hidden = true; }));
    document.querySelectorAll('[data-open-evidence]').forEach(button => button.addEventListener('click', () => { if (evidenceModal) evidenceModal.hidden = false; }));
    document.querySelectorAll('[data-close-evidence]').forEach(button => button.addEventListener('click', () => { if (evidenceModal) evidenceModal.hidden = true; }));
    document.querySelector('[data-file-name]')?.addEventListener('change', event => {
        const file = event.target.files?.[0];
        const label = document.querySelector('[data-selected-file]');
        if (label) label.textContent = file ? file.name + ' · ' + Math.ceil(file.size / 1024) + ' KB' : 'No file selected';
    });
    document.querySelector('[data-demo-import]')?.addEventListener('click', () => {
        const file = document.querySelector('[data-file-name]')?.files?.[0];
        toast(file ? 'File selected. Upload and import are not connected in this UI preview.' : 'Choose a CSV file to preview the import workflow.');
    });
    document.querySelector('[data-open-document-upload]')?.addEventListener('click', () => document.querySelector('[data-document-file]')?.click());
    document.querySelector('[data-document-file]')?.addEventListener('change', event => {
        const file = event.target.files?.[0];
        if (file) toast(file.name + ' selected. Document indexing is not connected in this UI preview.');
    });

    document.querySelectorAll('[data-demo-review]').forEach(button => button.addEventListener('click', () => {
        const status = document.querySelector('[data-review-status]');
        if (status) {
            status.textContent = button.dataset.demoReview;
            status.classList.toggle('is-approved', button.dataset.demoReview === 'Approved');
            status.classList.toggle('is-rejected', button.dataset.demoReview === 'Rejected');
        }
        if (evidenceModal) evidenceModal.hidden = true;
        toast('Demo review state changed locally. No decision was saved.');
    }));

    document.querySelectorAll('[data-ipa-point]').forEach(point => point.addEventListener('click', () => {
        const name = point.dataset.name;
        const importance = point.dataset.importance;
        const performance = point.dataset.performance;
        const gap = point.dataset.gap;
        const high = Number(importance) >= 4.2 && Number(performance) < 4;
        document.querySelector('[data-ipa-name]').textContent = name;
        document.querySelector('[data-ipa-importance]').textContent = importance;
        document.querySelector('[data-ipa-performance]').textContent = performance;
        document.querySelector('[data-ipa-gap]').textContent = gap.replace('-', '−');
        document.querySelector('[data-ipa-priority]').textContent = high ? 'High priority' : 'Monitor';
        document.querySelector('[data-ipa-priority]').className = 'ft-priority-tag ' + (high ? 'ft-tag-high' : 'ft-tag-good');
        document.querySelector('[data-ipa-interpretation]').textContent = high
            ? 'High importance with lower perceived performance. This attribute should receive focused improvement attention.'
            : 'Performance is comparatively stronger or importance is lower. Continue monitoring this service area.';
        document.querySelectorAll('[data-ipa-point]').forEach(p => p.classList.toggle('is-selected', p === point));
    }));

    let recFilter = 'All';
    function filterRecommendations() {
        const q = (document.querySelector('[data-rec-search]')?.value || '').toLowerCase().trim();
        let pending = 0, approved = 0;
        document.querySelectorAll('.ft-recommendation-card').forEach(card => {
            const status = card.dataset.recStatus;
            const match = (recFilter === 'All' || status === recFilter) && card.textContent.toLowerCase().includes(q);
            card.hidden = !match;
            if (status === 'Pending') pending += 1;
            if (status === 'Approved') approved += 1;
        });
        const p = document.querySelector('[data-pending-count]');
        const a = document.querySelector('[data-approved-count]');
        if (p) p.textContent = pending;
        if (a) a.textContent = approved;
    }
    document.querySelectorAll('[data-rec-filter]').forEach(button => button.addEventListener('click', () => {
        recFilter = button.dataset.recFilter;
        document.querySelectorAll('[data-rec-filter]').forEach(item => item.classList.toggle('is-active', item === button));
        filterRecommendations();
    }));
    document.querySelector('[data-rec-search]')?.addEventListener('input', filterRecommendations);
    document.querySelectorAll('[data-rec-action]').forEach(button => button.addEventListener('click', () => {
        const card = button.closest('.ft-recommendation-card');
        const status = button.dataset.recAction;
        card.dataset.recStatus = status;
        const badge = card.querySelector('[data-rec-state]');
        if (badge) {
            badge.textContent = status === 'Approved' ? 'Approved' : status === 'Rejected' ? 'Rejected' : 'Pending review';
            badge.className = 'ft-status-pill ' + (status === 'Approved' ? 'ft-status-done' : status === 'Pending' ? 'ft-status-review' : 'ft-status-muted');
        }
        card.querySelectorAll('[data-rec-action]').forEach(action => action.disabled = status !== 'Pending');
        filterRecommendations();
        toast('Recommendation marked ' + status.toLowerCase() + ' in this demo only. No data was saved.');
    }));

    document.querySelector('[data-range-filter]')?.addEventListener('change', event => toast('Showing the sample preview for: ' + event.target.value + '.'));
});