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
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        <span><?= $stats['pending_verif'] ?> yayasan menunggu verifikasi — klik untuk meninjau</span>
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left:auto"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
    </a>
    <?php endif; ?>

    <!-- STAT CARDS -->
    <div class="stat-grid">
        <a class="stat-card" href="kelola_user.php">
            <div class="stat-icon ic-green">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            </div>
            <div>
                <div class="stat-label">Total Donatur</div>
                <div class="stat-value"><?= $stats['total_donatur'] ?></div>
                <div class="stat-sub">pengguna terdaftar</div>
            </div>
        </a>
        <a class="stat-card" href="verifikasi.php">
            <div class="stat-icon ic-amber">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/></svg>
            </div>
            <div>
                <div class="stat-label">Yayasan Terverifikasi</div>
                <div class="stat-value" style="color:#d97706"><?= $stats['yayasan_verified'] ?></div>
                <div class="stat-sub"><?= $stats['pending_verif'] ?> menunggu verifikasi</div>
            </div>
        </a>
        <a class="stat-card" href="kelola_donasi.php">
            <div class="stat-icon ic-blue">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            </div>
            <div>
                <div class="stat-label">Donasi Berlangsung</div>
                <div class="stat-value" style="color:var(--blue)"><?= $stats['donasi_aktif'] ?></div>
                <div class="stat-sub"><?= $stats['donasi_selesai'] ?> sudah selesai</div>
            </div>
        </a>
        <a class="stat-card" href="kelola_donasi.php">
            <div class="stat-icon ic-purple">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
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