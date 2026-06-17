<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

// Statistik
$stats = [];
foreach ([
    'total_donatur'    => "SELECT COUNT(*) AS n FROM users WHERE role='donatur'",
    'yayasan_verified' => "SELECT COUNT(*) AS n FROM users WHERE role='penerima' AND status_verifikasi='verified'",
    'pending_verif'    => "SELECT COUNT(*) AS n FROM users WHERE role='penerima' AND status_verifikasi='pending'",
    'donasi_aktif'     => "SELECT COUNT(*) AS n FROM donasi WHERE status_donasi NOT IN ('selesai','dibatalkan')",
    'donasi_selesai'   => "SELECT COUNT(*) AS n FROM donasi WHERE status_donasi='selesai'",
    'total_donasi'     => "SELECT COUNT(*) AS n FROM donasi",
    'total_barang'     => "SELECT COALESCE(SUM(qty_donasi),0) AS n FROM donasi WHERE status_donasi='selesai'",
] as $k => $sql) {
    $stats[$k] = (int)($pdo->query($sql)->fetch(PDO::FETCH_ASSOC)['n'] ?? 0);
}

// 5 donasi terbaru
$recentDonasi = $pdo->query(
    "SELECT d.id, d.qty_donasi, d.status_donasi, d.created_at,
            COALESCE(k.nama_barang,'—') AS barang,
            COALESCE(ud.nama_lengkap,'—') AS donatur,
            COALESCE(up.nama_lengkap,'—') AS yayasan
     FROM donasi d
     LEFT JOIN katalog_kebutuhan k ON k.id=d.katalog_id
     LEFT JOIN users ud ON ud.id=d.donatur_id
     LEFT JOIN users up ON up.id=k.yayasan_id
     ORDER BY d.created_at DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// 5 user terbaru
$recentUsers = $pdo->query(
    "SELECT nama_lengkap, email, role, status_verifikasi, created_at
     FROM users ORDER BY created_at DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

$pdo = null;
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — CareDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Assets/admin.css">
    <style>
        /* Stat cards */
        .stat-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 28px; }
        .stat-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 14px; padding: 20px 22px;
            display: flex; align-items: center; gap: 16px;
            box-shadow: 0 1px 4px rgba(12,46,24,0.05);
            text-decoration: none; color: inherit;
            cursor: pointer; transition: box-shadow .18s, border-color .18s, transform .18s;
        }
        .stat-card:hover { box-shadow: 0 4px 16px rgba(12,46,24,0.12); border-color: var(--sage); transform: translateY(-2px); }
        .stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; }
        .ic-green  { background: linear-gradient(135deg,var(--moss),var(--sage)); }
        .ic-amber  { background: linear-gradient(135deg,#d97706,#f59e0b); }
        .ic-blue   { background: linear-gradient(135deg,#1d4ed8,var(--blue)); }
        .ic-purple { background: linear-gradient(135deg,#7c3aed,#a78bfa); }
        .stat-value { font-size: 28px; font-weight: 800; color: var(--forest); line-height: 1; }
        .stat-label { font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 2px; }
        .stat-sub   { font-size: 11px; color: var(--muted); margin-top: 3px; }

        /* Pending alert */
        .pending-alert {
            display: flex; align-items: center; gap: 12px;
            background: var(--amber-light); border: 1px solid var(--amber-border);
            border-radius: 12px; padding: 14px 18px; margin-bottom: 24px;
            text-decoration: none; color: var(--amber); font-size: 13px; font-weight: 600;
            transition: background .15s;
        }
        .pending-alert:hover { background: #fef3c7; }

        /* 2-col layout */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        @media (max-width: 1100px) { .stat-grid { grid-template-columns: 1fr 1fr; } .two-col { grid-template-columns: 1fr; } }
    </style>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css" />
</head>
<body>
<?php require '_sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <h1 class="page-title">Dashboard Admin</h1>
        <p class="page-subtitle">Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></strong> — ringkasan platform CareDrop hari ini.</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="flash flash-ok"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php elseif (isset($_GET['err'])): ?>
    <div class="flash flash-err"><?= htmlspecialchars($_GET['err']) ?></div>
    <?php endif; ?>

    <?php if ($stats['pending_verif'] > 0): ?>
    <a href="verifikasi.php" class="pending-alert">
        <i class="ph ph-info" style="font-size: 1.25em; vertical-align: middle;"></i>
        <span><?= $stats['pending_verif'] ?> yayasan menunggu verifikasi — klik untuk meninjau</span>
        <i class="ph ph-caret-right" style="font-size: 1.25em; vertical-align: middle;"></i>
    </a>
    <?php endif; ?>

    <!-- STAT CARDS -->
    <div class="stat-grid">
        <a class="stat-card" href="kelola_user.php">
            <div class="stat-icon ic-green">
                <i class="ph ph-user" style="font-size: 1.25em; vertical-align: middle;"></i>
            </div>
            <div>
                <div class="stat-label">Total Donatur</div>
                <div class="stat-value"><?= $stats['total_donatur'] ?></div>
                <div class="stat-sub">pengguna terdaftar</div>
            </div>
        </a>
        <a class="stat-card" href="verifikasi.php">
            <div class="stat-icon ic-amber">
                <i class="ph ph-check-circle" style="font-size: 1.25em; vertical-align: middle;"></i>
            </div>
            <div>
                <div class="stat-label">Yayasan Terverifikasi</div>
                <div class="stat-value" style="color:#d97706"><?= $stats['yayasan_verified'] ?></div>
                <div class="stat-sub"><?= $stats['pending_verif'] ?> menunggu verifikasi</div>
            </div>
        </a>
        <a class="stat-card" href="kelola_donasi.php">
            <div class="stat-icon ic-blue">
                <i class="ph ph-calendar" style="font-size: 1.25em; vertical-align: middle;"></i>
            </div>
            <div>
                <div class="stat-label">Donasi Berlangsung</div>
                <div class="stat-value" style="color:var(--blue)"><?= $stats['donasi_aktif'] ?></div>
                <div class="stat-sub"><?= $stats['donasi_selesai'] ?> sudah selesai</div>
            </div>
        </a>
        <a class="stat-card" href="kelola_donasi.php">
            <div class="stat-icon ic-purple">
                <i class="ph ph-truck" style="font-size: 1.25em; vertical-align: middle;"></i>
            </div>
            <div>
                <div class="stat-label">Barang Tersalurkan</div>
                <div class="stat-value"><?= number_format($stats['total_barang']) ?></div>
                <div class="stat-sub">unit dari <?= $stats['total_donasi'] ?> donasi</div>
            </div>
        </a>
    </div>

    <!-- RINGKASAN TERBARU -->
    <div class="two-col">

        <!-- Donasi terbaru -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Donasi Terbaru</span>
                <a href="kelola_donasi.php" class="btn btn-ghost" style="font-size:11px">Lihat semua</a>
            </div>
            <table>
                <thead><tr><th>Barang</th><th>Donatur</th><th>Status</th><th>Tanggal</th></tr></thead>
                <tbody>
                    <?php
                    $stMap2 = ['menunggu'=>['badge-menunggu','Menunggu'],'disetujui'=>['badge-disetujui','Disetujui'],'dikirim'=>['badge-dikirim','Dikirim'],'selesai'=>['badge-selesai','Selesai'],'dibatalkan'=>['badge-dibatalkan','Dibatalkan'],'ditolak'=>['badge-ditolak','Ditolak']];
                    foreach ($recentDonasi as $d):
                        [$cls,$lbl] = $stMap2[$d['status_donasi']] ?? ['','—'];
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars(mb_substr($d['barang'],0,25)) ?></strong></td>
                        <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars(mb_substr($d['donatur'],0,20)) ?></td>
                        <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
                        <td style="color:var(--muted);font-size:11px"><?= date('d M', strtotime($d['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentDonasi)): ?>
                    <tr><td colspan="4" class="empty-cell">Belum ada donasi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- User terbaru -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Pengguna Terbaru</span>
                <a href="kelola_user.php" class="btn btn-ghost" style="font-size:11px">Lihat semua</a>
            </div>
            <table>
                <thead><tr><th>Nama</th><th>Role</th><th>Status</th><th>Daftar</th></tr></thead>
                <tbody>
                    <?php foreach ($recentUsers as $u):
                        $rBadge = ['donatur'=>'badge-donatur','penerima'=>'badge-penerima','admin'=>'badge-admin'][$u['role']] ?? '';
                        $sBadge = ['verified'=>'badge-verified','pending'=>'badge-pending','rejected'=>'badge-rejected'][$u['status_verifikasi'] ?? ''] ?? '';
                        $sLabel = ['verified'=>'Verified','pending'=>'Pending','rejected'=>'Ditolak'][$u['status_verifikasi'] ?? ''] ?? '—';
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars(mb_substr($u['nama_lengkap'],0,22)) ?></strong><div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars(mb_substr($u['email'],0,25)) ?></div></td>
                        <td><span class="badge <?= $rBadge ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td><?php if ($u['role'] === 'penerima'): ?><span class="badge <?= $sBadge ?>"><?= $sLabel ?></span><?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?></td>
                        <td style="color:var(--muted);font-size:11px"><?= date('d M', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentUsers)): ?>
                    <tr><td colspan="4" class="empty-cell">Belum ada pengguna.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</main>
</body>
</html>