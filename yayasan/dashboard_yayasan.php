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
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-name">CareDrop</div>
        <div class="brand-role">Portal Yayasan</div>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard_yayasan.php" class="nav-item active">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            Dashboard
        </a>
        <a href="kelola_katalog.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            Katalog Kebutuhan
        </a>
        <a href="tawaran_masuk.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
            Tawaran Masuk
            <?php if ($badge_menunggu > 0): ?>
                <span class="badge"><?= $badge_menunggu ?></span>
            <?php endif; ?>
        </a>
        <a href="konfirmasi_terima.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Konfirmasi Terima
            <?php if ($badge_dikirim > 0): ?>
                <span class="badge"><?= $badge_dikirim ?></span>
            <?php endif; ?>
        </a>
        <a href="lacak_pengiriman.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
            Lacak Pengiriman
        </a>
        <div class="nav-divider"></div>
        <a href="../backend/export_csv.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Laporan CSV
        </a>
        <a href="profil_yayasan.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
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
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
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
        <svg width="22" height="22" fill="none" stroke="#dc2626" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <p style="color: #991b1b;"><strong>Akun Belum Terverifikasi</strong> — Anda harus mengunggah dokumen legalitas (seperti akta pendirian, KTP pengurus, dll) di menu Profil agar akun Anda dapat diverifikasi oleh admin. <a href="profil_yayasan.php" style="color:#dc2626">Unggah sekarang</a></p>
    </div>
    <?php endif; ?>

    <!-- ALERT jika ada donasi perlu dikonfirmasi -->
    <?php if ($badge_dikirim > 0): ?>
    <div class="alert-box">
        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        <p><strong><?= $badge_dikirim ?> donasi dikirim</strong> — Segera konfirmasi penerimaan barang. <a href="konfirmasi_terima.php">Konfirmasi sekarang</a></p>
    </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $total_item ?></div>
                <div class="stat-label">Total Katalog</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pine">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $item_aktif ?></div>
                <div class="stat-label">Katalog Aktif</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
            </div>
            <div>
                <div class="stat-value"><?= $total_donasi ?></div>
                <div class="stat-label">Total Donasi Masuk</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:linear-gradient(135deg,#15803d,#4aad6b)">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/></svg>
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
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </div>
                        Tambah Kebutuhan
                    </a>
                    <a href="tawaran_masuk.php" class="qa-btn" id="qa-tawaran">
                        <div class="qa-icon amber">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                        </div>
                        Review Tawaran
                    </a>
                    <a href="konfirmasi_terima.php" class="qa-btn" id="qa-konfirm">
                        <div class="qa-icon blue">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        Konfirmasi Terima
                    </a>
                    <a href="../backend/export_csv.php" class="qa-btn" id="qa-export">
                        <div class="qa-icon red">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
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
