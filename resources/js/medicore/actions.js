/* ==========================================================================
   ACTIONS — print-to-PDF, per-row downloads, and "view details" (eye icon)
   Event-delegated from document so this works identically on every page
   without any page needing its own wiring.
   ========================================================================== */

/* ---- Shared helper: makes actions.js self-sufficient, so per-row downloads
   work on every page even when export.js (CSV export) isn't loaded. ---- */
window.HMS = window.HMS || {};
if (!window.HMS.downloadTextFile) {
  window.HMS.downloadTextFile = function (filename, content, mimeType) {
    const blob = new Blob([content], { type: (mimeType || 'text/plain') + ';charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename || 'download.txt';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };
}

/* ---- Print / "Export PDF" — uses the browser's native print dialog,
   whose "Save as PDF" destination produces a real PDF with no library. ---- */
document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-print]');
  if (!btn) return;
  window.print();
});

/* ---- Per-row "Report" / download buttons — generates a real text file
   from that row's own data. Swap this for a real report-fetch API later;
   the trigger and download mechanism stay the same. ---- */
document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-download-row]');
  if (!btn || btn.disabled) return;
  const row = btn.closest('tr');
  if (!row) return;
  const cells = Array.from(row.querySelectorAll('td'))
    .map((td) => td.innerText.replace(/\s+/g, ' ').trim())
    .filter(Boolean);
  if (!cells.length) return;
  const content = cells.join('\n');
  const filename = cells[0].replace(/[^a-z0-9]+/gi, '-').toLowerCase().replace(/^-+|-+$/g, '') + '.txt';
  window.HMS.downloadTextFile(filename, content);
  if (window.HMS.showToast) {
    window.HMS.showToast({ type: 'success', title: 'Download ready', message: filename + ' has downloaded.' });
  }
});

/* ---- Eye icon — "view details" for rows that don't have their own
   detail page. Builds a small modal on the fly from the row's cells,
   labeled using the table's header row. ---- */
document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-view-row]');
  if (!btn) return;
  const row = btn.closest('tr');
  const table = row && row.closest('table');
  if (!row || !table) return;

  const headers = Array.from(table.querySelectorAll('thead th')).map((th) => th.innerText.trim());
  const cells = Array.from(row.querySelectorAll('td'));

  let modal = document.getElementById('rowDetailsModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.id = 'rowDetailsModal';
    modal.innerHTML = `
      <div class="modal-custom" style="max-width:440px;">
        <div class="modal-custom__header"><h3>Details</h3><button class="icon-btn btn-sm-custom" data-modal-close><i class="bi bi-x-lg"></i></button></div>
        <div class="modal-custom__body" id="rowDetailsBody"></div>
        <div class="modal-custom__footer"><button class="btn-custom btn-ghost-custom" data-modal-close>Close</button></div>
      </div>`;
    document.body.appendChild(modal);
  }

  const body = modal.querySelector('#rowDetailsBody');
  body.innerHTML = cells.map((td, i) => {
    const label = headers[i] || '';
    const text = td.innerText.replace(/\s+/g, ' ').trim();
    if (!label || !text) return '';
    return `<div style="margin-bottom:12px;">
      <div class="text-label" style="margin-bottom:2px;">${label}</div>
      <div class="text-small">${text}</div>
    </div>`;
  }).join('');

  modal.classList.add('is-open');
});
