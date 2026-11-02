/* ==========================================================================
   EXPORT — turns a .table-custom into a downloaded CSV file.
   Swap the table lookup for a real API response later; the CSV-building
   and download logic stays the same.
   ========================================================================== */
window.HMS = window.HMS || {};

window.HMS.downloadTextFile = function downloadTextFile(filename, content, mimeType) {
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

window.HMS.exportTableToCsv = function exportTableToCsv(table, filename) {
  if (!table) return;
  const rows = Array.from(table.querySelectorAll('tr'));
  const csv = rows.map((row) => {
    const cells = Array.from(row.querySelectorAll('th, td'));
    return cells
      .map((cell) => {
        // Strip nested markup (badges, icons, action buttons) down to text
        let text = cell.innerText.replace(/\s+/g, ' ').trim();
        if (text.includes(',') || text.includes('"') || text.includes('\n')) {
          text = '"' + text.replace(/"/g, '""') + '"';
        }
        return text;
      })
      .join(',');
  }).join('\r\n');

  window.HMS.downloadTextFile(filename || 'export.csv', csv, 'text/csv');
};

document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('exportDashboardBtn');
  if (!btn) return;
  btn.addEventListener('click', () => {
    const table = document.querySelector('#recentAppointmentsTable table');
    window.HMS.exportTableToCsv(table, 'recent-appointments.csv');
    if (window.HMS.showToast) {
      window.HMS.showToast({ type: 'success', title: 'Export ready', message: 'recent-appointments.csv has downloaded.' });
    }
  });
});
