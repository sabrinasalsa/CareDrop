<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

$donasi = $pdo->query(
    "SELECT d.id, d.qty_donasi, d.status_donasi, d.created_at,
            COALESCE(k.nama_barang,'—') AS barang,
            COALESCE(ud.nama_lengkap,'—') AS donatur,
            COALESCE(up.nama_lengkap,'—') AS yayasan
     FROM donasi d
     LEFT JOIN katalog_kebutuhan k ON k.id=d.katalog_id
     LEFT JOIN users ud ON ud.id=d.donatur_id
     LEFT JOIN users up ON up.id=k.yayasan_id
     ORDER BY d.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$pdo = null;
$activePage = 'donasi';

$aktifStatus  = ['menunggu','disetujui','dikirim','ditolak'];
$total        = count($donasi);
$berlangsung  = count(array_filter($donasi, fn($d) => in_array($d['status_donasi'], $aktifStatus)));
$selesai      = count(array_filter($donasi, fn($d) => $d['status_donasi'] === 'selesai'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Donasi — CareDrop Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Assets/admin.css">
    <style>
        .stat-mini-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; margin-bottom: 24px; }
        .stat-mini { background: var(--white); border: 1px solid var(--border); border-radius: 14px; padding: 18px 20px; box-shadow: 0 1px 4px rgba(12,46,24,0.05); }
        .stat-mini-label { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
        .stat-mini-value { font-size: 28px; font-weight: 800; margin-top: 4px; line-height: 1; }
        .stat-mini-sub { font-size: 11px; color: var(--muted); margin-top: 4px; }
    </style>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css" />
</head>
<body>
<?php require '_sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <h1 class="page-title">Data Donasi</h1>
        <p class="page-subtitle">Pantau dan kelola seluruh transaksi donasi barang di CareDrop.</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="flash flash-ok"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php elseif (isset($_GET['err'])): ?>
    <div class="flash flash-err"><?= htmlspecialchars($_GET['err']) ?></div>
    <?php endif; ?>

    <!-- Stat mini -->
    <div class="stat-mini-grid">
        <div class="stat-mini">
            <div class="stat-mini-label">Total Donasi</div>
            <div class="stat-mini-value" style="color:var(--forest)"><?= $total ?></div>
            <div class="stat-mini-sub">semua transaksi</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-label">Berlangsung</div>
            <div class="stat-mini-value" style="color:var(--blue)"><?= $berlangsung ?></div>
            <div class="stat-mini-sub">perlu perhatian</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-label">Selesai</div>
            <div class="stat-mini-value" style="color:#16a34a"><?= $selesai ?></div>
            <div class="stat-mini-sub">berhasil tersalurkan</div>
        </div>
    </div>

    <!-- Tabel donasi -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Semua Data Donasi</span>
        </div>
        <div class="tab-bar">
            <button class="tab-btn active" onclick="filterDonasi('semua',this)">Semua (<?= $total ?>)</button>
            <button class="tab-btn" onclick="filterDonasi('berlangsung',this)">Berlangsung (<?= $berlangsung ?>)</button>
            <button class="tab-btn" onclick="filterDonasi('selesai',this)">Selesai (<?= $selesai ?>)</button>
        </div>
        <div class="search-wrap">
            <div class="search-box">
                <i class="ph ph-magnifying-glass" style="font-size: 1.25em; vertical-align: middle;"></i>
                <input type="text" id="search-donasi" placeholder="Cari barang, donatur, atau yayasan..." oninput="applyFilter()">
            </div>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>ID Donasi</th>
                        <th>Barang</th>
                        <th>Donatur</th>
                        <th>Yayasan</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Tanggal</th>

                    </tr>
                </thead>
                <tbody id="tbl-donasi">
                    <?php
                    $stMap = [
                        'menunggu'   => ['badge-menunggu',   'Menunggu'],
                        'disetujui'  => ['badge-disetujui',  'Disetujui'],
                        'dikirim'    => ['badge-dikirim',    'Dikirim'],
                        'selesai'    => ['badge-selesai',    'Selesai'],
                        'dibatalkan' => ['badge-dibatalkan', 'Dibatalkan'],
                        'ditolak'    => ['badge-ditolak',    'Ditolak'],
                    ];
                    $aktif = $aktifStatus;
                    foreach ($donasi as $d):
                        [$stCls, $stLbl] = $stMap[$d['status_donasi']] ?? ['','—'];
                        $isBerlangsung   = in_array($d['status_donasi'], $aktif);
                    ?>
                    <tr data-status="<?= $d['status_donasi'] ?>" data-berlangsung="<?= $isBerlangsung ? '1' : '0' ?>">
                        <td class="mono"><?= htmlspecialchars($d['id']) ?></td>
                        <td><strong><?= htmlspecialchars($d['barang']) ?></strong></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($d['donatur']) ?></td>
                        <td style="color:var(--muted)"><?= htmlspecialchars($d['yayasan']) ?></td>
                        <td><?= (int)$d['qty_donasi'] ?> unit</td>
                        <td><span class="badge <?= $stCls ?>"><?= $stLbl ?></span></td>
                        <td style="color:var(--muted);font-size:12px"><?= date('d M Y', strtotime($d['created_at'])) ?></td>

                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($donasi)): ?>
                    <tr><td colspan="7" class="empty-cell">Belum ada data donasi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
let _tabFilter = 'semua';

function filterDonasi(tab, btn) {
    _tabFilter = tab;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilter();
}

function applyFilter() {
    const q = document.getElementById('search-donasi').value.toLowerCase();
    document.querySelectorAll('#tbl-donasi tr').forEach(row => {
        const status      = row.dataset.status || '';
        const berlangsung = row.dataset.berlangsung === '1';
        const text        = row.textContent.toLowerCase();

        let tabOk = true;
        if (_tabFilter === 'berlangsung') tabOk = berlangsung;
        else if (_tabFilter === 'selesai') tabOk = (status === 'selesai');

        const txtOk = !q || text.includes(q);
        row.style.display = (tabOk && txtOk) ? '' : 'none';
    });
}
</script>
</body>
</html>
