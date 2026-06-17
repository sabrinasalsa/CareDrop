<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php'); exit;
}
$yayasan_id  = (int)$_SESSION['id'];
$nama_yayasan = htmlspecialchars($_SESSION['nama'] ?? 'Yayasan');

// Stats
$total_item = $item_aktif = $item_tutup = $total_donasi = 0;
$badge_menunggu = $badge_dikirim = 0;
$katalog_list = [];
$donasi_terbaru = [];

try {
    $r = $pdo->prepare("SELECT COUNT(*) AS n FROM katalog_kebutuhan WHERE yayasan_id=?");
    $r->execute([$yayasan_id]);
    $total_item = (int)($r->fetch()['n'] ?? 0);

    $r = $pdo->prepare("SELECT COUNT(*) AS n FROM katalog_kebutuhan WHERE yayasan_id=? AND aktif=1");
    $r->execute([$yayasan_id]);
    $item_aktif = (int)($r->fetch()['n'] ?? 0);
    $item_tutup = $total_item - $item_aktif;

    $r = $pdo->prepare("SELECT COUNT(*) AS n FROM donasi d
        JOIN katalog_kebutuhan k ON d.katalog_id = k.id
        WHERE k.yayasan_id = ? AND d.status_donasi NOT IN ('dibatalkan','ditolak')");
    $r->execute([$yayasan_id]);
    $total_donasi = (int)($r->fetch()['n'] ?? 0);

    $r = $pdo->prepare("SELECT COUNT(*) AS n FROM donasi d
        JOIN katalog_kebutuhan k ON d.katalog_id = k.id
        WHERE k.yayasan_id = ? AND d.status_donasi = 'menunggu'");
    $r->execute([$yayasan_id]);
    $badge_menunggu = (int)($r->fetch()['n'] ?? 0);

    $r = $pdo->prepare("SELECT COUNT(*) AS n FROM donasi d
        JOIN katalog_kebutuhan k ON d.katalog_id = k.id
        WHERE k.yayasan_id = ? AND d.status_donasi = 'dikirim'");
    $r->execute([$yayasan_id]);
    $badge_dikirim = (int)($r->fetch()['n'] ?? 0);

    // Donasi terbaru
    $r = $pdo->prepare("SELECT d.id AS donasi_id, d.qty_donasi, d.status_donasi, d.created_at,
           k.nama_barang, u.nama_lengkap AS nama_donatur
        FROM donasi d
        JOIN katalog_kebutuhan k ON d.katalog_id = k.id
        JOIN users u ON d.donatur_id = u.id
        WHERE k.yayasan_id = ?
        ORDER BY d.created_at DESC LIMIT 8");
    $r->execute([$yayasan_id]);
    $donasi_terbaru = $r->fetchAll();

    // Katalog teratas (top 4 paling mendesak/belum terpenuhi)
    $r = $pdo->prepare("SELECT id, nama_barang, kategori, urgensi, target_butuh, jumlah_terkumpul, aktif
        FROM katalog_kebutuhan WHERE yayasan_id=?
        ORDER BY FIELD(urgensi,'high','med','low'), aktif DESC LIMIT 4");
    $r->execute([$yayasan_id]);
    $katalog_list = $r->fetchAll();

    // Donasi selesai bulan ini
    $r = $pdo->prepare("SELECT COUNT(*) AS n FROM donasi d
        JOIN katalog_kebutuhan k ON d.katalog_id = k.id
        WHERE k.yayasan_id = ? AND d.status_donasi = 'selesai'
        AND MONTH(d.created_at)=MONTH(NOW()) AND YEAR(d.created_at)=YEAR(NOW())");
    $r->execute([$yayasan_id]);
    $selesai_bulan = (int)($r->fetch()['n'] ?? 0);

} catch (Exception $e) { }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CareDrop Yayasan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --forest: #0c2e18; --pine: #1e5630; --moss: #2d7a44; --sage: #4aad6b;
            --mint: #7ed9a3; --amber: #f0c040; --ink: #0b1f12; --muted: #5c7d65;
            --bg: #f4fbf6; --border: #d4e8db; --white: #ffffff;
            --red: #dc2626; --red-light: #fef2f2; --red-border: #fecaca;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--ink); display: flex; min-height: 100vh; }

        /* ── SIDEBAR ── */
        .sidebar { width: 240px; min-height: 100vh; background: var(--forest); position: fixed; top: 0; left: 0; z-index: 100; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-brand .brand-name { font-size: 20px; font-weight: 800; color: var(--mint); letter-spacing: -0.5px; }
        .sidebar-brand .brand-role { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
        .sidebar-nav { flex: 1; padding: 16px 12px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; color: rgba(255,255,255,0.65); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.18s ease; margin-bottom: 2px; position: relative; }
        .nav-item:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .nav-item.active { background: rgba(126,217,163,0.15); color: var(--mint); }
        .nav-item .badge { margin-left: auto; background: var(--amber); color: var(--ink); font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 20px; }
        .nav-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 10px 0; }
        .sidebar-footer { padding: 14px 12px; border-top: 1px solid rgba(255,255,255,0.08); }
        .sidebar-profile { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 10px; transition: background 0.15s; }
        .sidebar-profile:hover { background: rgba(255,255,255,0.06); }
        .profile-av { width: 36px; height: 36px; border-radius: 50%; background: var(--moss); border: 2px solid var(--sage); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0; overflow: hidden; }
        .profile-av img { width: 100%; height: 100%; object-fit: cover; }
        .profile-info { overflow: hidden; flex: 1; }
        .profile-info strong { display: block; font-size: 12px; font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .profile-info span { font-size: 10px; color: rgba(255,255,255,0.4); }
        .logout-btn { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 10px; color: rgba(255,255,255,0.45); text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.15s; margin-top: 4px; }
        .logout-btn:hover { background: rgba(220,38,38,0.15); color: #f87171; }

        /* ── MAIN ── */
        .main { margin-left: 240px; flex: 1; min-height: 100vh; padding: 32px 36px; }
        .page-header { margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; color: var(--forest); letter-spacing: -0.5px; }
        .page-subtitle { font-size: 14px; color: var(--muted); margin-top: 4px; }

        /* ── STAT CARDS ── */
        .stats-bar { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-card { background: var(--white); border: 1px solid var(--border); border-radius: 14px; padding: 20px 22px; display: flex; align-items: center; gap: 16px; transition: all 0.18s; }
        .stat-card:hover { box-shadow: 0 4px 18px rgba(45,122,68,0.1); transform: translateY(-1px); }
        .stat-icon { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, var(--moss), var(--sage)); display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; }
        .stat-icon.amber { background: linear-gradient(135deg, #d97706, var(--amber)); }
        .stat-icon.pine  { background: linear-gradient(135deg, var(--pine), var(--moss)); }
        .stat-icon.muted { background: linear-gradient(135deg, var(--muted), #7a9e85); }
        .stat-icon.red   { background: linear-gradient(135deg, #dc2626, #f87171); }
        .stat-value { font-size: 26px; font-weight: 800; color: var(--forest); line-height: 1; }
        .stat-label { font-size: 12px; color: var(--muted); margin-top: 3px; }

        /* ── GRID LAYOUT ── */
        .dashboard-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; margin-bottom: 28px; }

        /* ── CARDS ── */
        .card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: 0 1px 4px rgba(12,46,24,0.05); }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .card-header h3 { font-size: 15px; font-weight: 700; color: var(--forest); }
        .card-header a { font-size: 13px; font-weight: 600; color: var(--moss); text-decoration: none; }
        .card-header a:hover { text-decoration: underline; }

        /* ── TABLE ── */
        table { width: 100%; border-collapse: collapse; }
        thead th { padding: 10px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); background: #f9fdf9; text-align: left; border-bottom: 1px solid var(--border); }
        tbody tr { border-bottom: 1px solid var(--border); transition: background 0.12s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f9fdf9; }
        tbody td { padding: 12px 16px; font-size: 13px; color: var(--ink); vertical-align: middle; }
        .empty-row td { text-align: center; padding: 36px; color: var(--muted); font-size: 13px; }

        /* ── STATUS BADGES ── */
        .st-badge { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; }

        /* ── KATALOG ITEMS ── */
        .kat-list { padding: 12px 16px; display: flex; flex-direction: column; gap: 10px; }
        .kat-item { display: flex; flex-direction: column; gap: 6px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px; transition: background 0.15s; }
        .kat-item:hover { background: #f4fbf6; }
        .kat-item-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
        .kat-item-name { font-size: 13px; font-weight: 700; color: var(--forest); }
        .urg { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 5px; text-transform: uppercase; flex-shrink: 0; }
        .urg-high { background: #fee2e2; color: #dc2626; }
        .urg-med  { background: #fff7ed; color: #c2410c; }
        .urg-low  { background: #f0fdf4; color: #15803d; }
        .prog-bar { height: 5px; background: #e8f5ed; border-radius: 99px; overflow: hidden; }
        .prog-fill { height: 100%; background: linear-gradient(90deg, var(--moss), var(--sage)); border-radius: 99px; }
        .prog-info { display: flex; justify-content: space-between; font-size: 11px; color: var(--muted); }

        /* ── ALERT ── */
        .alert-box { display: flex; align-items: center; gap: 12px; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; }
        .alert-box svg { flex-shrink: 0; color: #d97706; }
        .alert-box p { font-size: 13px; color: #92400e; font-weight: 600; }
        .alert-box a { color: var(--moss); font-weight: 700; text-decoration: underline; }

        /* ── QUICK ACTIONS ── */
        .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding: 16px; }
        .qa-btn { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid var(--border); border-radius: 10px; text-decoration: none; color: var(--ink); font-size: 13px; font-weight: 600; transition: all 0.18s; }
        .qa-btn:hover { border-color: var(--sage); background: #f0fdf4; color: var(--moss); }
        .qa-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .qa-icon.green { background: #f0fdf4; color: var(--moss); }
        .qa-icon.amber { background: #fffbeb; color: #d97706; }
        .qa-icon.blue  { background: #eff6ff; color: #2563eb; }
        .qa-icon.red   { background: #fff1f2; color: #dc2626; }

        @media (max-width: 1100px) { .dashboard-grid { grid-template-columns: 1fr; } }
        @media (max-width: 900px) { .stats-bar { grid-template-columns: 1fr 1fr; } .main { padding: 20px 16px; } }
    </style>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css" />
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-name">Care<span style="color: #f59e0b;">Drop</span></div>
        <div class="brand-role">Portal Yayasan</div>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard_yayasan.php" class="nav-item active">
            <i class="ph ph-house" style="font-size: 1.25em; vertical-align: middle;"></i>
            Dashboard
        </a>
        <a href="kelola_katalog.php" class="nav-item">
            <i class="ph ph-package" style="font-size: 1.25em; vertical-align: middle;"></i>
            Katalog Kebutuhan
        </a>
        <a href="tawaran_masuk.php" class="nav-item">
            <i class="ph ph-clipboard-text" style="font-size: 1.25em; vertical-align: middle;"></i>
            Tawaran Masuk
            <?php if ($badge_menunggu > 0): ?>
                <span class="badge"><?= $badge_menunggu ?></span>
            <?php endif; ?>
        </a>
        <a href="konfirmasi_terima.php" class="nav-item">
            <i class="ph ph-check-circle" style="font-size: 1.25em; vertical-align: middle;"></i>
            Konfirmasi Terima
            <?php if ($badge_dikirim > 0): ?>
                <span class="badge"><?= $badge_dikirim ?></span>
            <?php endif; ?>
        </a>
        <a href="lacak_pengiriman.php" class="nav-item">
            <i class="ph ph-truck" style="font-size: 1.25em; vertical-align: middle;"></i>
            Lacak Pengiriman
        </a>
        <div class="nav-divider"></div>
        <a href="../backend/export_csv.php" class="nav-item">
            <i class="ph ph-download-simple" style="font-size: 1.25em; vertical-align: middle;"></i>
            Laporan CSV
        </a>
        <a href="profil_yayasan.php" class="nav-item">
            <i class="ph ph-user" style="font-size: 1.25em; vertical-align: middle;"></i>
            Profil &amp; Legalitas
        </a>
    </nav>
    <div class="sidebar-footer">
        <?php
            $av = $_SESSION['avatar'] ?? null;
            $inisial_yayasan = mb_strtoupper(mb_substr($_SESSION['nama'] ?? 'Y', 0, 2));
            $avPath = $av ? '../uploads/avatars/' . htmlspecialchars($av) : null;
        ?>
        <a href="profil_yayasan.php" class="sidebar-profile" style="text-decoration:none">
            <div class="profile-av">
                <?php if ($avPath && file_exists(dirname(__DIR__) . '/uploads/avatars/' . $av)): ?>
                    <img src="<?= $avPath ?>" alt="foto profil">
                <?php else: ?>
                    <?= $inisial_yayasan ?>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <strong><?= htmlspecialchars(mb_substr($_SESSION['nama'] ?? 'Yayasan', 0, 22)) ?></strong>
                <span>Yayasan</span>
            </div>
        </a>
        <a href="../backend/logout.php" class="logout-btn">
            <i class="ph ph-sign-out" style="font-size: 1.25em; vertical-align: middle;"></i>
            Keluar
        </a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main">
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Selamat datang, <?= $nama_yayasan ?> — ringkasan aktivitas yayasan Anda.</p>
    </div>

    <!-- ALERT status pending -->
    <?php if (($_SESSION['status_verifikasi'] ?? '') === 'pending'): ?>
    <div class="alert-box" style="background: #fff1f2; border-color: #fecaca;">
        <i class="ph ph-warning" style="font-size: 1.25em; vertical-align: middle;"></i>
        <p style="color: #991b1b;"><strong>Akun Belum Terverifikasi</strong> — Anda harus mengunggah dokumen legalitas (seperti akta pendirian, KTP pengurus, dll) di menu Profil agar akun Anda dapat diverifikasi oleh admin. <a href="profil_yayasan.php" style="color:#dc2626">Unggah sekarang</a></p>
    </div>
    <?php endif; ?>

    <!-- ALERT jika ada donasi perlu dikonfirmasi -->
    <?php if ($badge_dikirim > 0): ?>
    <div class="alert-box">
        <i class="ph ph-warning" style="font-size: 1.25em; vertical-align: middle;"></i>
        <p><strong><?= $badge_dikirim ?> donasi dikirim</strong> — Segera konfirmasi penerimaan barang. <a href="konfirmasi_terima.php">Konfirmasi sekarang</a></p>
    </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="ph ph-package" style="font-size: 1.25em; vertical-align: middle;"></i>
            </div>
            <div>
                <div class="stat-value"><?= $total_item ?></div>
                <div class="stat-label">Total Katalog</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pine">
                <i class="ph ph-check-circle" style="font-size: 1.25em; vertical-align: middle;"></i>
            </div>
            <div>
                <div class="stat-value"><?= $item_aktif ?></div>
                <div class="stat-label">Katalog Aktif</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">
                <i class="ph ph-truck" style="font-size: 1.25em; vertical-align: middle;"></i>
            </div>
            <div>
                <div class="stat-value"><?= $total_donasi ?></div>
                <div class="stat-label">Total Donasi Masuk</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#15803d,#4aad6b)">
                <i class="ph ph-map-pin" style="font-size: 1.25em; vertical-align: middle;"></i>
            </div>
            <div>
                <div class="stat-value"><?= $selesai_bulan ?></div>
                <div class="stat-label">Selesai Bulan Ini</div>
            </div>
        </div>
    </div>

    <!-- MAIN GRID -->
    <div class="dashboard-grid">

        <!-- Donasi Terbaru -->
        <div class="card">
            <div class="card-header">
                <h3>Donasi Terbaru</h3>
                <a href="tawaran_masuk.php">Lihat semua</a>
            </div>
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Donatur</th>
                            <th>Barang</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($donasi_terbaru)): ?>
                        <tr class="empty-row"><td colspan="5">Belum ada donasi masuk.</td></tr>
                        <?php endif; ?>
                        <?php
                        $stMap = [
                            'menunggu'   => ['Menunggu','#fff7ed','#c2410c'],
                            'disetujui'  => ['Disetujui','#eff6ff','#2563eb'],
                            'ditolak'    => ['Ditolak','#fff1f2','#dc2626'],
                            'dikirim'    => ['Dikirim','#f0fdf4','#15803d'],
                            'selesai'    => ['Selesai','#dcfce7','#166534'],
                            'dibatalkan' => ['Batal','#f9fafb','#6b7280'],
                        ];
                        foreach ($donasi_terbaru as $d):
                            [$stLbl,$stBg,$stClr] = $stMap[$d['status_donasi']] ?? [$d['status_donasi'],'#f3f4f6','#6b7280'];
                        ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($d['nama_donatur']) ?></td>
                            <td style="color:var(--muted);font-size:12px"><?= htmlspecialchars($d['nama_barang']) ?></td>
                            <td><?= $d['qty_donasi'] ?> unit</td>
                            <td><span class="st-badge" style="background:<?= $stBg ?>;color:<?= $stClr ?>"><?= $stLbl ?></span></td>
                            <td style="color:var(--muted);font-size:12px;white-space:nowrap"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sidebar kanan -->
        <div style="display:flex;flex-direction:column;gap:20px">

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3>Aksi Cepat</h3>
                </div>
                <div class="quick-actions">
                    <a href="kelola_katalog.php" class="qa-btn" id="qa-tambah">
                        <div class="qa-icon green">
                            <i class="ph ph-plus" style="font-size: 1.25em; vertical-align: middle;"></i>
                        </div>
                        Tambah Kebutuhan
                    </a>
                    <a href="tawaran_masuk.php" class="qa-btn" id="qa-tawaran">
                        <div class="qa-icon amber">
                            <i class="ph ph-clipboard-text" style="font-size: 1.25em; vertical-align: middle;"></i>
                        </div>
                        Review Tawaran
                    </a>
                    <a href="konfirmasi_terima.php" class="qa-btn" id="qa-konfirm">
                        <div class="qa-icon blue">
                            <i class="ph ph-check-circle" style="font-size: 1.25em; vertical-align: middle;"></i>
                        </div>
                        Konfirmasi Terima
                    </a>
                    <a href="../backend/export_csv.php" class="qa-btn" id="qa-export">
                        <div class="qa-icon red">
                            <i class="ph ph-download-simple" style="font-size: 1.25em; vertical-align: middle;"></i>
                        </div>
                        Export CSV
                    </a>
                </div>
            </div>

            <!-- Katalog Kebutuhan Teratas -->
            <div class="card">
                <div class="card-header">
                    <h3>Kebutuhan Prioritas</h3>
                    <a href="kelola_katalog.php">Kelola</a>
                </div>
                <?php if (empty($katalog_list)): ?>
                <div style="padding:24px;text-align:center;color:var(--muted);font-size:13px">
                    Belum ada katalog. <a href="kelola_katalog.php" style="color:var(--moss);font-weight:600">Tambah sekarang</a>
                </div>
                <?php else: ?>
                <div class="kat-list">
                    <?php foreach ($katalog_list as $k):
                        $pct = ($k['target_butuh'] > 0) ? min(100, round(($k['jumlah_terkumpul'] / $k['target_butuh']) * 100)) : 0;
                        $urgMap = ['high'=>['Mendesak','urg-high'],'med'=>['Sedang','urg-med'],'low'=>['Normal','urg-low']];
                        [$urgLbl, $urgCls] = $urgMap[$k['urgensi']] ?? ['Normal','urg-low'];
                    ?>
                    <div class="kat-item">
                        <div class="kat-item-top">
                            <div class="kat-item-name"><?= htmlspecialchars($k['nama_barang']) ?></div>
                            <span class="urg <?= $urgCls ?>"><?= $urgLbl ?></span>
                        </div>
                        <div class="prog-bar"><div class="prog-fill" style="width:<?= $pct ?>%"></div></div>
                        <div class="prog-info">
                            <span><?= $k['jumlah_terkumpul'] ?> terkumpul</span>
                            <span><?= $pct ?>% dari <?= $k['target_butuh'] ?> unit</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>

</body>
</html>
