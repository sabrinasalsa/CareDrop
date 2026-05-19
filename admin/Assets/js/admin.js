function switchTab(id, el) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('on'));
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('on'));
  document.getElementById(id)?.classList.add('on');
  el.classList.add('on');
}
 
function filterTable(tbodyId, q, cols) {
  const rows = document.querySelectorAll('#' + tbodyId + ' tr');
  const kw   = q.toLowerCase();
  rows.forEach(row => {
    const tds = row.querySelectorAll('td');
    const match = cols.some(i => (tds[i]?.textContent || '').toLowerCase().includes(kw));
    row.style.display = match ? '' : 'none';
  });
}
 