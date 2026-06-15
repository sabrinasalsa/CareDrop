<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

$yayasans = $pdo->query(
    "SELECT id, nama_lengkap, email, no_telp, alamat, status_verifikasi, created_at
     FROM users WHERE role = 'penerima' ORDER BY
     FIELD(status_verifikasi,'pending','rejected','verified'), created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$pdo = null;
$activePage = 'verifikasi';

$totalPending  = count(array_filter($yayasans, fn($y) => $y['status_verifikasi'] === 'pending'));
$totalVerified = count(array_filter($yayasans, fn($y) => $y['status_verifikasi'] === 'verified'));
$totalRejected = count(array_filter($yayasans, fn($y) => $y['status_verifikasi'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Yayasan — CareDrop Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Assets/admin.css">
    <style>
        .stat-mini-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 24px; }
        .stat-mini { background: var(--white); border: 1px solid var(--border); border-radius: 14px; padding: 18px 20px; box-shadow: 0 1px 4px rgba(12,46,24,0.05); }
        .stat-mini-label { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
        .stat-mini-value { font-size: 28px; font-weight: 800; margin-top: 4px; line-height: 1; }
        .stat-mini-sub { font-size: 11px; color: var(--muted); margin-top: 4px; }

        /* MODAL LEGALITAS */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(11,31,18,0.55); backdrop-filter: blur(4px);
            z-index: 999; align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: var(--white); border-radius: 20px;
            width: 640px; max-width: calc(100vw - 32px);
            max-height: 85vh; display: flex; flex-direction: column;
            box-shadow: 0 24px 60px rgba(11,31,18,0.22);
            animation: modalIn 0.22s ease;
        }
        @keyframes modalIn {
            from { opacity:0; transform:translateY(18px) scale(0.97); }
            to   { opacity:1; transform:translateY(0) scale(1); }
        }
        .modal-head {
            padding: 20px 24px 16px; border-bottom: 1px solid var(--border);
            display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
        }
        .modal-title { font-size: 16px; font-weight: 800; color: var(--forest); }
        .modal-subtitle { font-size: 12px; color: var(--muted); margin-top: 3px; }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px; border: none;
            background: var(--bg); color: var(--muted); cursor: pointer;
            font-size: 18px; display: flex; align-items: center; justify-content: center;
            transition: all 0.15s;
        }
        .modal-close:hover { background: var(--red-light); color: var(--red); }
        .modal-body { padding: 20px 24px; overflow-y: auto; flex: 1; }
        .modal-footer { padding: 14px 24px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; }
        .doc-list { display: flex; flex-direction: column; gap: 10px; }
        .doc-card {
            border: 1px solid var(--border); border-radius: 12px;
            padding: 14px 16px; display: flex; align-items: center; gap: 14px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .doc-card:hover { border-color: var(--sage); box-shadow: 0 2px 10px rgba(45,122,68,0.08); }
        .doc-icon {
            width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg,var(--moss),var(--sage));
            font-size: 18px;
        }
        .doc-info { flex: 1; min-width: 0; }
        .doc-jenis { font-size: 13px; font-weight: 700; color: var(--forest); }
        .doc-keterangan { font-size: 11px; color: var(--muted); margin-top: 2px; }
        .doc-date { font-size: 10px; color: var(--muted); margin-top: 4px; }
        .doc-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; flex-wrap: wrap; justify-content: flex-end; }
        .doc-status { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 20px; }
        .doc-status.pending  { background:var(--amber-light); color:var(--amber); }
        .doc-status.verified { background:#f0fdf4; color:#16a34a; }
        .doc-status.rejected { background:var(--red-light); color:var(--red); }
        .doc-view { padding:5px 12px; border-radius:8px; font-size:11px; font-weight:700; background:var(--blue-light); color:var(--blue); border:1px solid var(--blue-border); text-decoration:none; transition:all .15s; }
        .doc-view:hover { background:var(--blue); color:#fff; }
        .btn-doc-green { padding:5px 11px; border-radius:8px; font-size:11px; font-weight:700; background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; cursor:pointer; font-family:inherit; transition:all .15s; }
        .btn-doc-green:hover { background:#16a34a; color:#fff; }
        .btn-doc-green:disabled { opacity:.4; cursor:not-allowed; }
        .btn-doc-red { padding:5px 11px; border-radius:8px; font-size:11px; font-weight:700; background:var(--red-light); color:var(--red); border:1px solid var(--red-border); cursor:pointer; font-family:inherit; transition:all .15s; }
        .btn-doc-red:hover { background:var(--red); color:#fff; }
        .btn-doc-red:disabled { opacity:.4; cursor:not-allowed; }
        .doc-empty { text-align:center; padding:40px 20px; color:var(--muted); font-size:13px; }
        .yayasan-strip { background:#f4fbf6; border:1px solid var(--border); border-radius:10px; padding:12px 16px; display:flex; gap:20px; margin-bottom:16px; flex-wrap:wrap; }
        .yayasan-strip span { font-size:12px; color:var(--muted); }
        .yayasan-strip strong { color:var(--forest); }
        .modal-loading { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:50px 20px; gap:14px; }
        .spinner { width:36px; height:36px; border:3px solid var(--border); border-top-color:var(--moss); border-radius:50%; animation:spin .7s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }

        /* Row highlight pending */
        tr.row-pending { background: #fffbeb; }
        tr.row-pending:hover { background: #fef3c7; }
    </style>
</head>
<body>
<?php require '_sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <h1 class="page-title">Verifikasi Yayasan</h1>
        <p class="page-subtitle">Tinjau dan verifikasi akun yayasan serta dokumen legalitasnya.</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="flash flash-ok"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php elseif (isset($_GET['err'])): ?>
    <div class="flash flash-err"><?= htmlspecialchars($_GET['err']) ?></div>
    <?php endif; ?>

    <!-- Stat mini -->
    <div class="stat-mini-grid">
        <div class="stat-mini">
            <div class="stat-mini-label">Menunggu Verifikasi</div>
            <div class="stat-mini-value" style="color:var(--amber)"><?= $totalPending ?></div>
            <div class="stat-mini-sub">yayasan pending</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-label">Terverifikasi</div>
            <div class="stat-mini-value" style="color:#16a34a"><?= $totalVerified ?></div>
            <div class="stat-mini-sub">yayasan aktif</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-label">Ditolak</div>
            <div class="stat-mini-value" style="color:var(--red)"><?= $totalRejected ?></div>
            <div class="stat-mini-sub">yayasan ditolak</div>
        </div>
    </div>

    <!-- Tabel yayasan -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Daftar Yayasan</span>
            <span style="font-size:12px;color:var(--muted)">🟡 Baris kuning = menunggu verifikasi</span>
        </div>
        <div class="tab-bar">
            <button class="tab-btn active" onclick="filterStatus('semua',this)">Semua (<?= count($yayasans) ?>)</button>
            <button class="tab-btn" onclick="filterStatus('pending',this)">Pending (<?= $totalPending ?>)</button>
            <button class="tab-btn" onclick="filterStatus('verified',this)">Terverifikasi (<?= $totalVerified ?>)</button>
            <button class="tab-btn" onclick="filterStatus('rejected',this)">Ditolak (<?= $totalRejected ?>)</button>
        </div>
        <div class="search-wrap">
            <div class="search-box">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803z"/></svg>
                <input type="text" id="search-verif" placeholder="Cari nama atau email yayasan..." oninput="cariYayasan(this.value)">
            </div>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>Nama Yayasan</th>
                        <th>Email</th>
                        <th>No. Telepon</th>
                        <th>Status</th>
                        <th>Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbl-verif">
                    <?php foreach ($yayasans as $y):
                        $st = $y['status_verifikasi'];
                        $stBadge = ['verified'=>'badge-verified','pending'=>'badge-pending','rejected'=>'badge-rejected'][$st] ?? '';
                        $stLabel = ['verified'=>'Verified','pending'=>'Pending','rejected'=>'Ditolak'][$st] ?? $st;
                        $rowClass = ($st === 'pending') ? 'row-pending' : '';
                    ?>
                    <tr class="<?= $rowClass ?>" data-status="<?= $st ?>">
                        <td><strong><?= htmlspecialchars($y['nama_lengkap']) ?></strong></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($y['email']) ?></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($y['no_telp'] ?: '—') ?></td>
                        <td><span class="badge <?= $stBadge ?>"><?= $stLabel ?></span></td>
                        <td style="color:var(--muted);font-size:12px"><?= date('d M Y', strtotime($y['created_at'])) ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap">
                                <?php if ($st === 'pending'): ?>
                                    <a href="aksi_user.php?aksi=verif&id=<?= $y['id'] ?>" class="btn btn-green"
                                       onclick="return confirm('Verifikasi akun <?= htmlspecialchars(addslashes($y['nama_lengkap'])) ?>?')">✓ Verifikasi</a>
                                    <a href="aksi_user.php?aksi=tolak&id=<?= $y['id'] ?>" class="btn btn-red"
                                       onclick="return confirm('Tolak akun ini?')">✕ Tolak</a>
                                <?php endif; ?>
                                <button class="btn btn-blue" onclick="lihatDokumen(<?= $y['id'] ?>, '<?= htmlspecialchars(addslashes($y['nama_lengkap'])) ?>')">📄 Dokumen</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($yayasans)): ?>
                    <tr><td colspan="6" class="empty-cell">Belum ada yayasan terdaftar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- MODAL DOKUMEN LEGALITAS -->
<div class="modal-overlay" id="modal-legalitas" onclick="tutupModal(event)">
    <div class="modal-box" role="dialog" aria-modal="true">
        <div class="modal-head">
            <div>
                <div class="modal-title">📄 Dokumen Legalitas</div>
                <div class="modal-subtitle" id="modal-nama-yayasan">—</div>
            </div>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body" id="modal-body">
            <div class="modal-loading"><div class="spinner"></div><span style="font-size:13px;color:var(--muted)">Memuat dokumen...</span></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal()">Tutup</button>
        </div>
    </div>
</div>

<script>
let _statusFilter = 'semua';

function filterStatus(status, btn) {
    _statusFilter = status;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilter();
}

function cariYayasan(q) { applyFilter(); }

function applyFilter() {
    const q = document.getElementById('search-verif').value.toLowerCase();
    document.querySelectorAll('#tbl-verif tr').forEach(row => {
        const st   = row.dataset.status || '';
        const text = row.textContent.toLowerCase();
        const stOk   = (_statusFilter === 'semua') || (st === _statusFilter);
        const txtOk  = !q || text.includes(q);
        row.style.display = (stOk && txtOk) ? '' : 'none';
    });
}

/* ── Modal Legalitas ── */
function lihatDokumen(yayasanId, namaYayasan) {
    const overlay  = document.getElementById('modal-legalitas');
    const body     = document.getElementById('modal-body');
    const subtitle = document.getElementById('modal-nama-yayasan');

    subtitle.textContent = namaYayasan;
    body.innerHTML = `<div class="modal-loading"><div class="spinner"></div><span style="font-size:13px;color:var(--muted)">Memuat dokumen...</span></div>`;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch(`../backend/get_legalitas_admin.php?yayasan_id=${yayasanId}`)
        .then(r => r.json())
        .then(res => {
            if (!res.ok) {
                body.innerHTML = `<div class="doc-empty"><div style="font-size:40px;margin-bottom:10px">⚠️</div><p>${res.error || 'Gagal memuat data'}</p></div>`;
                return;
            }
            const info = res.yayasan;
            let html = `<div class="yayasan-strip">
                <span>📧 <strong>${escHtml(info.email)}</strong></span>
                <span>📞 <strong>${escHtml(info.no_telp || '—')}</strong></span>
            </div>`;

            if (!res.data || res.data.length === 0) {
                html += `<div class="doc-empty"><div style="font-size:40px;margin-bottom:10px">📂</div><p>Yayasan ini belum mengunggah dokumen legalitas.</p></div>`;
            } else {
                const statusLabel = { pending:'Menunggu', verified:'Terverifikasi', rejected:'Ditolak' };
                html += '<div class="doc-list">';
                res.data.forEach(doc => {
                    const tgl   = new Date(doc.created_at).toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'numeric'});
                    const ext   = doc.nama_file.split('.').pop().toUpperCase();
                    const icon  = ext === 'PDF' ? '📄' : '🖼️';
                    const btnTerima = doc.status !== 'verified'
                        ? `<button class="btn-doc-green" onclick="aksiDokumen(this, ${yayasanId}, '${escHtml(doc.jenis)}', 'terima')">Terima</button>`
                        : `<button class="btn-doc-green" disabled>Terima</button>`;
                    const btnTolak = doc.status !== 'rejected'
                        ? `<button class="btn-doc-red" onclick="aksiDokumen(this, ${yayasanId}, '${escHtml(doc.jenis)}', 'tolak')">Tolak</button>`
                        : `<button class="btn-doc-red" disabled>Tolak</button>`;

                    html += `<div class="doc-card" id="doc-card-${CSS.escape(doc.jenis)}">
                        <div class="doc-icon">${icon}</div>
                        <div class="doc-info">
                            <div class="doc-jenis">${escHtml(doc.jenis)}</div>
                            <div class="doc-keterangan">${escHtml(doc.keterangan || '—')}</div>
                            <div class="doc-date">Diunggah: ${tgl}</div>
                        </div>
                        <div class="doc-actions">
                            <span class="doc-status ${doc.status}" id="doc-status-${CSS.escape(doc.jenis)}">${statusLabel[doc.status] || doc.status}</span>
                            <a href="../uploads/legalitas/${escHtml(doc.nama_file)}" target="_blank" class="doc-view">👁 Lihat</a>
                            ${btnTerima}${btnTolak}
                        </div>
                    </div>`;
                });
                html += '</div>';
            }
            body.innerHTML = html;
        })
        .catch(() => { body.innerHTML = `<div class="doc-empty"><p>Terjadi kesalahan koneksi.</p></div>`; });
}

function aksiDokumen(btnEl, yayasanId, jenis, aksi) {
    if (!confirm(`Yakin ingin ${aksi === 'terima' ? 'menerima' : 'menolak'} dokumen "${jenis}"?`)) return;
    const card = btnEl.closest('.doc-card');
    card.querySelectorAll('button').forEach(b => b.disabled = true);
    const fd = new FormData();
    fd.append('yayasan_id', yayasanId); fd.append('jenis', jenis); fd.append('aksi', aksi);
    fetch('aksi_legalitas.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                const el = document.getElementById(`doc-status-${CSS.escape(jenis)}`);
                const map = { verified:'Terverifikasi', rejected:'Ditolak', pending:'Menunggu' };
                if (el) { el.className = `doc-status ${res.status}`; el.textContent = map[res.status]; }
                card.querySelectorAll('button').forEach(b => {
                    if (b.classList.contains('btn-doc-green')) b.disabled = (res.status === 'verified');
                    if (b.classList.contains('btn-doc-red'))   b.disabled = (res.status === 'rejected');
                });
            } else {
                alert('Gagal: ' + (res.error || 'Terjadi kesalahan'));
                card.querySelectorAll('button').forEach(b => b.disabled = false);
            }
        })
        .catch(() => { alert('Terjadi kesalahan koneksi.'); card.querySelectorAll('button').forEach(b => b.disabled = false); });
}

function closeModal() { document.getElementById('modal-legalitas').classList.remove('open'); document.body.style.overflow = ''; }
function tutupModal(e) { if (e.target === document.getElementById('modal-legalitas')) closeModal(); }
function escHtml(str) { if (!str) return ''; return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
</body>
</html>
