<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php'); exit;
}

// Statistik global
$stats = [];
$q = [
    'total_user'     => "SELECT COUNT(*) AS n FROM users",
    'total_donatur'  => "SELECT COUNT(*) AS n FROM users WHERE role='donatur'",
    'total_penerima' => "SELECT COUNT(*) AS n FROM users WHERE role='penerima'",
    'pending_verif'  => "SELECT COUNT(*) AS n FROM users WHERE role='penerima' AND status_verifikasi='pending'",
    'total_donasi'   => "SELECT COUNT(*) AS n FROM donasi",
    'donasi_aktif'   => "SELECT COUNT(*) AS n FROM donasi WHERE status_donasi NOT IN ('selesai','dibatalkan')",
    'donasi_selesai' => "SELECT COUNT(*) AS n FROM donasi WHERE status_donasi='selesai'",
    'total_barang'   => "SELECT COALESCE(SUM(qty_donasi),0) AS n FROM donasi WHERE status_donasi='selesai'",
];
foreach ($q as $k => $sql) {
    $r = $koneksi->query($sql)->fetch_assoc();
    $stats[$k] = (int)($r['n'] ?? 0);
}

// Semua user
$users = $koneksi->query(
    "SELECT id, nama_lengkap, email, role,
            COALESCE(status_verifikasi,'—') AS status_verifikasi,
            COALESCE(no_telp,'—') AS no_telp, created_at
     FROM users ORDER BY created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

// Donasi terbaru
$donasi = $koneksi->query(
    "SELECT d.id, d.qty_donasi, d.status_donasi, d.created_at,
            COALESCE(k.nama_barang,'—') AS barang,
            COALESCE(ud.nama_lengkap,'—') AS donatur,
            COALESCE(up.nama_lengkap,'—') AS yayasan
     FROM donasi d
     LEFT JOIN katalog_kebutuhan k ON k.id=d.katalog_id
     LEFT JOIN users ud ON ud.id=d.donatur_id
     LEFT JOIN users up ON up.id=k.yayasan_id
     ORDER BY d.created_at DESC LIMIT 20"
)->fetch_all(MYSQLI_ASSOC);

$koneksi->close();
$nama_admin = htmlspecialchars($_SESSION['nama'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — CareDrop</title>
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
            --blue: #2563eb; --blue-light: #eff6ff;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--ink); display: flex; min-height: 100vh; }

        /* ── SIDEBAR ───────────────────────────────── */
        .sidebar {
            width: 240px; min-height: 100vh; background: var(--forest);
            position: fixed; top: 0; left: 0; z-index: 100;
            display: flex; flex-direction: column;
        }
        .sidebar-brand { padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .brand-name { font-size: 20px; font-weight: 800; color: var(--mint); letter-spacing: -0.5px; }
        .brand-role { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
        .sidebar-nav { flex: 1; padding: 16px 12px; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            color: rgba(255,255,255,0.65); text-decoration: none;
            font-size: 14px; font-weight: 500;
            transition: all 0.18s; margin-bottom: 2px;
        }
        .nav-item:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .nav-item.active { background: rgba(126,217,163,0.15); color: var(--mint); }
        .nav-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 10px 0; }
        .sidebar-footer { padding: 16px 12px; border-top: 1px solid rgba(255,255,255,0.08); }

        /* ── MAIN ──────────────────────────────────── */
        .main { margin-left: 240px; flex: 1; padding: 32px 36px; }
        .page-header { margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; color: var(--forest); letter-spacing: -0.5px; }
        .page-subtitle { font-size: 14px; color: var(--muted); margin-top: 4px; }

        /* ── FLASH ─────────────────────────────────── */
        .flash { padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
        .flash-ok  { background: #f0fdf4; color: var(--moss); border: 1px solid #bbf7d0; }
        .flash-err { background: var(--red-light); color: var(--red); border: 1px solid var(--red-border); }

        /* ── STAT GRID ─────────────────────────────── */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .stat-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 14px; padding: 20px 22px;
            display: flex; align-items: center; gap: 16px;
            box-shadow: 0 1px 4px rgba(12,46,24,0.05);
        }
        .stat-icon {
            width: 46px; height: 46px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; flex-shrink: 0;
        }
        .ic-green  { background: linear-gradient(135deg, var(--moss), var(--sage)); }
        .ic-amber  { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .ic-blue   { background: linear-gradient(135deg, #1d4ed8, var(--blue)); }
        .ic-purple { background: linear-gradient(135deg, #7c3aed, #a78bfa); }
        .stat-value { font-size: 28px; font-weight: 800; color: var(--forest); line-height: 1; }
        .stat-label { font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 2px; }
        .stat-sub   { font-size: 11px; color: var(--muted); margin-top: 3px; }

        /* ── CARD ──────────────────────────────────── */
        .card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 1px 4px rgba(12,46,24,0.05);
            margin-bottom: 24px;
        }
        .card-header {
            padding: 16px 20px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-title { font-size: 15px; font-weight: 700; color: var(--forest); }

        /* ── TABS ──────────────────────────────────── */
        .tab-bar { display: flex; gap: 4px; padding: 6px; background: var(--bg); border-bottom: 1px solid var(--border); }
        .tab-btn {
            padding: 8px 18px; border-radius: 8px; border: none; cursor: pointer;
            font-family: inherit; font-size: 13px; font-weight: 600;
            color: var(--muted); background: none; transition: all 0.15s;
        }
        .tab-btn:hover { background: var(--white); color: var(--ink); }
        .tab-btn.active { background: var(--white); color: var(--forest); box-shadow: 0 1px 4px rgba(12,46,24,0.08); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ── SEARCH ────────────────────────────────── */
        .search-wrap { padding: 14px 20px; border-bottom: 1px solid var(--border); }
        .search-box {
            display: flex; align-items: center; gap: 8px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 10px; padding: 8px 14px; max-width: 380px;
        }
        .search-box svg { color: var(--muted); flex-shrink: 0; }
        .search-box input {
            border: none; outline: none; background: transparent;
            font-family: inherit; font-size: 13px; color: var(--ink); width: 100%;
        }

        /* ── TABLE ─────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; }
        thead th {
            padding: 11px 16px; text-align: left;
            font-size: 11px; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.5px;
            background: #f9fdf9; border-bottom: 1px solid var(--border);
        }
        tbody tr { border-bottom: 1px solid var(--border); transition: background 0.12s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f9fdf9; }
        tbody td { padding: 12px 16px; font-size: 13px; color: var(--ink); vertical-align: middle; }
        .mono { font-family: monospace; font-size: 12px; }

        /* ── BADGES ────────────────────────────────── */
        .badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }
        .badge-donatur  { background: #f0fdf4; color: var(--moss); }
        .badge-penerima { background: var(--blue-light); color: var(--blue); }
        .badge-admin    { background: #faf5ff; color: #7c3aed; }
        .badge-verified { background: #f0fdf4; color: #16a34a; }
        .badge-pending  { background: #fffbeb; color: #d97706; }
        .badge-rejected { background: var(--red-light); color: var(--red); }
        .badge-menunggu  { background: #fffbeb; color: #d97706; }
        .badge-disetujui { background: var(--blue-light); color: var(--blue); }
        .badge-dikirim   { background: #f0f9ff; color: #0284c7; }
        .badge-selesai   { background: #f0fdf4; color: #16a34a; }
        .badge-dibatalkan{ background: #f3f4f6; color: #6b7280; }
        .badge-ditolak   { background: var(--red-light); color: var(--red); }

        /* ── BUTTONS ───────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 12px; border-radius: 8px; border: none; cursor: pointer;
            font-family: inherit; font-size: 12px; font-weight: 600;
            text-decoration: none; transition: all 0.15s;
        }
        .btn-green  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .btn-green:hover  { background: #16a34a; color: #fff; }
        .btn-red    { background: var(--red-light); color: var(--red); border: 1px solid var(--red-border); }
        .btn-red:hover    { background: var(--red); color: #fff; }
        .btn-blue   { background: var(--blue-light); color: var(--blue); border: 1px solid #bfdbfe; }
        .btn-blue:hover   { background: var(--blue); color: #fff; }
        .btn-ghost  { background: var(--bg); color: var(--muted); border: 1px solid var(--border); }
        .btn-ghost:hover  { border-color: var(--red-border); color: var(--red); background: var(--red-light); }

        .empty-cell { text-align: center; padding: 40px; color: var(--muted); font-size: 13px; }

        @media (max-width: 1100px) { .stat-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 900px)  { .main { padding: 20px 16px; } }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-name">CareDrop</div>
        <div class="brand-role">Panel Admin</div>
    </div>
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item active">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            Dashboard
        </a>
        <a href="analitik.php" class="nav-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
            Analitik
        </a>
        <a href="kelola_kategori.php" class="nav-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.595.33a18.095 18.095 0 005.223-5.223c.542-.815.369-1.896-.33-2.595L9.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
            Kategori
        </a>
        <a href="kelola_sertifikat.php" class="nav-item">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/></svg>
            Sertifikat
        </a>
        <div class="nav-divider"></div>
        <a href="../backend/logout.php" class="nav-item" style="color:rgba(255,255,255,0.5);">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
            Keluar
        </a>
    </nav>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="page-header">
        <h1 class="page-title">Dashboard Admin</h1>
        <p class="page-subtitle">Selamat datang, <strong><?= $nama_admin ?></strong> — kelola seluruh data CareDrop.</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="flash flash-ok"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php elseif (isset($_GET['err'])): ?>
    <div class="flash flash-err"><?= htmlspecialchars($_GET['err']) ?></div>
    <?php endif; ?>

    <!-- STAT CARDS -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon ic-green">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
            <div>
                <div class="stat-label">Total Pengguna</div>
                <div class="stat-value"><?= $stats['total_user'] ?></div>
                <div class="stat-sub"><?= $stats['total_donatur'] ?> donatur · <?= $stats['total_penerima'] ?> yayasan</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ic-amber">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            </div>
            <div>
                <div class="stat-label">Menunggu Verifikasi</div>
                <div class="stat-value" style="color:#d97706"><?= $stats['pending_verif'] ?></div>
                <div class="stat-sub">yayasan pending</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ic-blue">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            </div>
            <div>
                <div class="stat-label">Donasi Aktif</div>
                <div class="stat-value" style="color:var(--blue)"><?= $stats['donasi_aktif'] ?></div>
                <div class="stat-sub">dari <?= $stats['total_donasi'] ?> total donasi</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ic-purple">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
            </div>
            <div>
                <div class="stat-label">Barang Tersalurkan</div>
                <div class="stat-value"><?= number_format($stats['total_barang']) ?></div>
                <div class="stat-sub">unit dari <?= $stats['donasi_selesai'] ?> donasi selesai</div>
            </div>
        </div>
    </div>

    <!-- TABS CARD -->
    <div class="card">
        <div class="tab-bar">
            <button class="tab-btn active" onclick="switchTab('tab-user', this)">
                Manajemen User (<?= count($users) ?>)
            </button>
            <button class="tab-btn" onclick="switchTab('tab-donasi', this)">
                Data Donasi (<?= count($donasi) ?>)
            </button>
        </div>

        <!-- TAB: USER -->
        <div class="tab-panel active" id="tab-user">
            <div class="search-wrap">
                <div class="search-box">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803z"/></svg>
                    <input type="text" id="search-user" placeholder="Cari nama, email, atau role..." oninput="filterTable('tbl-user', this.value, [0,1,3])">
                </div>
            </div>
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbl-user">
                        <?php foreach ($users as $u):
                            $roleBadge = [
                                'donatur'  => 'badge-donatur',
                                'penerima' => 'badge-penerima',
                                'admin'    => 'badge-admin',
                            ][$u['role']] ?? '';
                            $st = $u['status_verifikasi'];
                            $stBadge = [
                                'verified' => 'badge-verified',
                                'pending'  => 'badge-pending',
                                'rejected' => 'badge-rejected',
                            ][$st] ?? '';
                            $stLabel = [
                                'verified' => 'Verified',
                                'pending'  => 'Pending',
                                'rejected' => 'Ditolak',
                            ][$st] ?? $st;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['nama_lengkap']) ?></strong></td>
                            <td style="color:var(--muted)"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="color:var(--muted)"><?= htmlspecialchars($u['no_telp']) ?></td>
                            <td><span class="badge <?= $roleBadge ?>"><?= ucfirst($u['role']) ?></span></td>
                            <td>
                                <?php if ($u['role'] === 'penerima'): ?>
                                    <span class="badge <?= $stBadge ?>"><?= $stLabel ?></span>
                                <?php else: ?>
                                    <span style="color:var(--muted)">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--muted);font-size:12px"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    <?php if ($u['role'] === 'penerima' && $u['status_verifikasi'] === 'pending'): ?>
                                        <a href="aksi_user.php?aksi=verif&id=<?= $u['id'] ?>" class="btn btn-green"
                                           onclick="return confirm('Verifikasi akun <?= htmlspecialchars(addslashes($u['nama_lengkap'])) ?>?')">Verifikasi</a>
                                        <a href="aksi_user.php?aksi=tolak&id=<?= $u['id'] ?>" class="btn btn-red"
                                           onclick="return confirm('Tolak akun ini?')">Tolak</a>
                                    <?php endif; ?>
                                    <?php if ($u['role'] !== 'admin'): ?>
                                        <a href="aksi_user.php?aksi=hapus&id=<?= $u['id'] ?>" class="btn btn-ghost"
                                           onclick="return confirm('Hapus akun <?= htmlspecialchars(addslashes($u['nama_lengkap'])) ?>? Data tidak bisa dikembalikan!')">Hapus</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                        <tr><td colspan="7" class="empty-cell">Tidak ada pengguna.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB: DONASI -->
        <div class="tab-panel" id="tab-donasi">
            <div class="search-wrap">
                <div class="search-box">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803z"/></svg>
                    <input type="text" id="search-donasi" placeholder="Cari ID, barang, donatur, atau yayasan..." oninput="filterTable('tbl-donasi', this.value, [0,1,2,3])">
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
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbl-donasi">
                        <?php foreach ($donasi as $d):
                            $stMap = [
                                'menunggu'   => ['badge-menunggu',   'Menunggu'],
                                'disetujui'  => ['badge-disetujui',  'Disetujui'],
                                'dikirim'    => ['badge-dikirim',    'Dikirim'],
                                'selesai'    => ['badge-selesai',    'Selesai'],
                                'dibatalkan' => ['badge-dibatalkan', 'Dibatalkan'],
                                'ditolak'    => ['badge-ditolak',    'Ditolak'],
                            ];
                            [$stCls, $stLbl] = $stMap[$d['status_donasi']] ?? ['', '—'];
                        ?>
                        <tr>
                            <td class="mono"><?= htmlspecialchars($d['id']) ?></td>
                            <td><strong><?= htmlspecialchars($d['barang']) ?></strong></td>
                            <td style="color:var(--muted)"><?= htmlspecialchars($d['donatur']) ?></td>
                            <td style="color:var(--muted)"><?= htmlspecialchars($d['yayasan']) ?></td>
                            <td><?= (int)$d['qty_donasi'] ?> unit</td>
                            <td><span class="badge <?= $stCls ?>"><?= $stLbl ?></span></td>
                            <td style="color:var(--muted);font-size:12px"><?= date('d M Y', strtotime($d['created_at'])) ?></td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <?php if ($d['status_donasi'] === 'menunggu'): ?>
                                        <a href="aksi_donasi.php?aksi=proses&id=<?= urlencode($d['id']) ?>" class="btn btn-blue">Proses</a>
                                        <a href="aksi_donasi.php?aksi=batal&id=<?= urlencode($d['id']) ?>" class="btn btn-red"
                                           onclick="return confirm('Batalkan donasi ini?')">Batal</a>
                                    <?php elseif ($d['status_donasi'] === 'dikirim'): ?>
                                        <a href="aksi_donasi.php?aksi=selesai&id=<?= urlencode($d['id']) ?>" class="btn btn-green"
                                           onclick="return confirm('Tandai donasi ini selesai?')">Selesaikan</a>
                                    <?php else: ?>
                                        <span style="color:var(--muted);font-size:12px">—</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($donasi)): ?>
                        <tr><td colspan="8" class="empty-cell">Belum ada data donasi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
function switchTab(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
}

function filterTable(tbodyId, query, cols) {
    const q = query.toLowerCase();
    document.querySelectorAll('#' + tbodyId + ' tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        const match = cols.some(i => cells[i] && cells[i].textContent.toLowerCase().includes(q));
        row.style.display = (!q || match) ? '' : 'none';
    });
}
</script>
</body>
</html>