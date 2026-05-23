<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php'); exit;
}
$yayasan_id   = (int)$_SESSION['id'];
$nama_yayasan = htmlspecialchars($_SESSION['nama'] ?? 'Yayasan');

$badge_menunggu = $badge_dikirim = 0;
try {
    $r = $koneksi->prepare("SELECT COUNT(*) AS n FROM donasi d
        JOIN katalog_kebutuhan k ON k.id = d.katalog_id
        WHERE k.yayasan_id = ? AND d.status_donasi = 'menunggu'");
    $r->bind_param("i", $yayasan_id); $r->execute();
    $badge_menunggu = (int)($r->get_result()->fetch_assoc()['n'] ?? 0); $r->close();

    $r = $koneksi->prepare("SELECT COUNT(*) AS n FROM donasi d
        JOIN katalog_kebutuhan k ON k.id = d.katalog_id
        WHERE k.yayasan_id = ? AND d.status_donasi = 'dikirim'");
    $r->bind_param("i", $yayasan_id); $r->execute();
    $badge_dikirim = (int)($r->get_result()->fetch_assoc()['n'] ?? 0); $r->close();
} catch (Exception $e) {}

$filter = $_GET['tab'] ?? 'dikirim';
if (!in_array($filter, ['dikirim', 'selesai'])) $filter = 'dikirim';

$donasi_list = [];
try {
    $r = $koneksi->prepare(
        "SELECT
            d.id, d.qty_donasi, d.deskripsi_kondisi, d.foto_barang,
            d.status_donasi, d.created_at, d.updated_at,
            k.nama_barang, k.kategori, k.urgensi,
            u.nama_lengkap AS nama_donatur, u.email AS email_donatur, u.no_telp,
            p.kurir, p.no_resi, p.tipe_layanan, p.kota_asal
         FROM donasi d
         JOIN katalog_kebutuhan k ON k.id = d.katalog_id
         JOIN users u ON u.id = d.donatur_id
         LEFT JOIN pengiriman p ON p.donasi_id = d.id
         WHERE k.yayasan_id = ? AND d.status_donasi = ?
         ORDER BY d.updated_at DESC"
    );
    $r->bind_param("is", $yayasan_id, $filter);
    $r->execute();
    $donasi_list = $r->get_result()->fetch_all(MYSQLI_ASSOC);
    $r->close();
} catch (Exception $e) {}

$count_dikirim = $badge_dikirim;
$count_selesai = 0;
try {
    $r = $koneksi->prepare("SELECT COUNT(*) AS n FROM donasi d
        JOIN katalog_kebutuhan k ON k.id = d.katalog_id
        WHERE k.yayasan_id = ? AND d.status_donasi = 'selesai'");
    $r->bind_param("i", $yayasan_id); $r->execute();
    $count_selesai = (int)($r->get_result()->fetch_assoc()['n'] ?? 0); $r->close();
} catch (Exception $e) {}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Terima — CareDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --forest: #0c2e18;
            --pine:   #1e5630;
            --moss:   #2d7a44;
            --sage:   #4aad6b;
            --mint:   #7ed9a3;
            --amber:  #f0c040;
            --ink:    #0b1f12;
            --muted:  #5c7d65;
            --bg:     #f4fbf6;
            --border: #d4e8db;
            --white:  #ffffff;
            --red:    #dc2626;
            --red-light: #fef2f2;
            --red-border: #fecaca;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--ink); display: flex; min-height: 100vh; }

        .sidebar {
            width: 240px; min-height: 100vh; background: var(--forest);
            position: fixed; top: 0; left: 0; z-index: 100;
            display: flex; flex-direction: column;
        }
        .sidebar-brand { padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-brand .brand-name { font-size: 20px; font-weight: 800; color: var(--mint); letter-spacing: -0.5px; }
        .sidebar-brand .brand-role { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
        .sidebar-nav { flex: 1; padding: 16px 12px; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            color: rgba(255,255,255,0.65); text-decoration: none;
            font-size: 14px; font-weight: 500;
            transition: all 0.18s ease; margin-bottom: 2px; position: relative;
        }
        .nav-item:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .nav-item.active { background: rgba(126,217,163,0.15); color: var(--mint); }
        .nav-item .badge { margin-left: auto; background: var(--amber); color: var(--ink); font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 20px; }
        .nav-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 10px 0; }
        .sidebar-footer { padding: 16px 12px; border-top: 1px solid rgba(255,255,255,0.08); }

        .main { margin-left: 240px; flex: 1; min-height: 100vh; padding: 32px 36px; }
        .page-header { margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; color: var(--forest); letter-spacing: -0.5px; }
        .page-subtitle { font-size: 14px; color: var(--muted); margin-top: 4px; }

        .tabs { display: flex; gap: 4px; margin-bottom: 24px; background: var(--white); border: 1px solid var(--border); border-radius: 12px; padding: 6px; width: fit-content; }
        .tab-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 20px; border-radius: 8px; border: none; cursor: pointer;
            font-family: inherit; font-size: 13px; font-weight: 600;
            color: var(--muted); background: none; transition: all 0.15s; text-decoration: none;
        }
        .tab-btn:hover { background: var(--bg); color: var(--ink); }
        .tab-btn.active { background: linear-gradient(135deg, var(--moss), var(--sage)); color: #fff; }
        .tab-btn.active.tab-selesai { background: linear-gradient(135deg, var(--pine), var(--moss)); }
        .tab-count { font-size: 11px; background: rgba(255,255,255,0.25); padding: 1px 6px; border-radius: 10px; }
        .tab-btn:not(.active) .tab-count { background: var(--bg); color: var(--muted); }

        .info-banner {
            display: flex; align-items: flex-start; gap: 14px;
            background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px;
            padding: 16px 20px; margin-bottom: 22px; color: #1e40af;
        }
        .info-banner svg { flex-shrink: 0; margin-top: 1px; }
        .info-banner p { font-size: 13px; line-height: 1.6; }

        .card-grid { display: flex; flex-direction: column; gap: 16px; }
        .donasi-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 1px 4px rgba(12,46,24,0.05);
            transition: box-shadow 0.15s;
            border-left: 4px solid var(--sage);
        }
        .donasi-card:hover { box-shadow: 0 4px 16px rgba(12,46,24,0.10); }
        .donasi-card.done { border-left-color: var(--pine); }

        .card-top { padding: 20px 22px; display: grid; grid-template-columns: auto 1fr auto; gap: 18px; align-items: start; }

        .card-foto {
            width: 88px; height: 88px; border-radius: 12px; flex-shrink: 0;
            background: var(--bg); border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center; color: var(--muted);
            overflow: hidden;
        }
        .card-foto img { width: 88px; height: 88px; object-fit: cover; }

        .card-info { min-width: 0; }
        .card-barang { font-size: 16px; font-weight: 700; color: var(--forest); margin-bottom: 5px; }
        .card-meta { display: flex; flex-wrap: wrap; gap: 7px; align-items: center; margin-bottom: 8px; }
        .card-donatur { font-size: 13px; color: var(--ink); font-weight: 500; margin-bottom: 2px; }
        .card-kontak  { font-size: 12px; color: var(--muted); }

        .resi-box {
            margin-top: 12px; padding: 14px 16px;
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 1px solid #86efac; border-radius: 12px;
            display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
        }
        .resi-box .resi-icon { color: var(--moss); flex-shrink: 0; }
        .resi-detail { flex: 1; min-width: 0; }
        .resi-label { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .resi-value { font-size: 15px; font-weight: 800; color: var(--forest); font-family: monospace; letter-spacing: 0.5px; }
        .resi-kurir { font-size: 12px; color: var(--moss); font-weight: 600; margin-top: 2px; }

        .done-box {
            margin-top: 12px; padding: 12px 16px;
            background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px;
            display: flex; align-items: center; gap: 10px;
            font-size: 13px; color: var(--moss); font-weight: 600;
        }

        .card-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; flex-shrink: 0; }
        .date-chip { font-size: 11px; color: var(--muted); white-space: nowrap; }
        .card-id   { font-size: 11px; color: var(--muted); font-family: monospace; }
        .badge-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-primary { background: #f0f9ff; color: #0284c7; }
        .badge-success { background: #f0fdf4; color: #16a34a; }
        .badge-kat  { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: #eff6ff; color: #3b82f6; }
        .qty-pill   { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; background: var(--bg); color: var(--forest); border: 1px solid var(--border); }

        .btn-confirm {
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, var(--moss), var(--sage));
            color: #fff; border: none; padding: 11px 20px; border-radius: 11px;
            font-family: inherit; font-size: 14px; font-weight: 700;
            cursor: pointer; transition: opacity 0.15s; white-space: nowrap;
        }
        .btn-confirm:hover { opacity: 0.88; }
        .btn-confirm:disabled { opacity: 0.5; cursor: not-allowed; }

        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 200;
            background: rgba(11,31,18,0.45); backdrop-filter: blur(2px);
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--white); border-radius: 20px; padding: 36px;
            width: 100%; max-width: 460px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.20);
            text-align: center;
        }
        .modal-icon {
            width: 72px; height: 72px; border-radius: 50%;
            background: linear-gradient(135deg, var(--moss), var(--sage));
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px; color: #fff;
        }
        .modal-title { font-size: 20px; font-weight: 800; color: var(--forest); margin-bottom: 10px; }
        .modal-body  { font-size: 14px; color: var(--muted); line-height: 1.6; margin-bottom: 28px; }
        .modal-body strong { color: var(--ink); }
        .modal-actions { display: flex; gap: 10px; justify-content: center; }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 22px; border-radius: 10px; border: 1px solid var(--border);
            background: none; font-family: inherit; font-size: 14px; font-weight: 600;
            color: var(--muted); cursor: pointer; transition: all 0.15s;
        }
        .btn-ghost:hover { background: var(--bg); border-color: var(--sage); color: var(--moss); }
        .btn-ok {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, var(--moss), var(--sage));
            color: #fff; border: none; padding: 10px 24px; border-radius: 10px;
            font-family: inherit; font-size: 14px; font-weight: 700;
            cursor: pointer; transition: opacity 0.15s;
        }
        .btn-ok:hover { opacity: 0.88; }

        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
        .toast {
            display: flex; align-items: center; gap: 12px;
            background: var(--white); border: 1px solid var(--border);
            border-radius: 12px; padding: 14px 18px; min-width: 280px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12); animation: slideIn 0.25s ease;
        }
        .toast.success { border-left: 4px solid var(--sage); }
        .toast.error   { border-left: 4px solid var(--red); }
        .toast-icon.success { color: var(--moss); }
        .toast-icon.error   { color: var(--red); }
        .toast-msg { font-size: 14px; font-weight: 500; color: var(--ink); }
        @keyframes slideIn { from { transform: translateX(60px); opacity: 0; } to { transform: none; opacity: 1; } }

        /* ── EMPTY STATE ───────────────────────────── */
        .empty-state { text-align: center; padding: 64px 20px; color: var(--muted); }
        .empty-state svg { margin: 0 auto 16px; display: block; opacity: 0.3; }
        .empty-state p { font-size: 15px; }
        .empty-state small { font-size: 13px; display: block; margin-top: 6px; }

        @media (max-width: 900px) {
            .main { padding: 20px 16px; }
            .card-top { grid-template-columns: 1fr; }
            .card-actions { flex-direction: row; flex-wrap: wrap; }
        }
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
        <a href="kelola_katalog.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            Dashboard
        </a>
        <a href="kelola_katalog.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            Katalog Kebutuhan
        </a>
        <a href="tawaran_masuk.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
            Tawaran Masuk
            <?php if ($badge_menunggu > 0): ?>
                <span class="badge"><?= $badge_menunggu ?></span>
            <?php endif; ?>
        </a>
        <a href="konfirmasi_terima.php" class="nav-item active">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Konfirmasi Terima
            <?php if ($badge_dikirim > 0): ?>
                <span class="badge"><?= $badge_dikirim ?></span>
            <?php endif; ?>
        </a>
        <div class="nav-divider"></div>
        <a href="../backend/export_csv.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Laporan CSV
        </a>
        <a href="profil_yayasan.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
            Profil &amp; Legalitas
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="../backend/logout.php" class="nav-item" style="color:rgba(255,255,255,0.5);">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
            Keluar
        </a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="page-header">
        <h1 class="page-title">Konfirmasi Terima</h1>
        <p class="page-subtitle">Selamat datang, <?= $nama_yayasan ?> — konfirmasi penerimaan barang donasi yang sudah dikirim.</p>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <a href="?tab=dikirim" class="tab-btn <?= $filter === 'dikirim' ? 'active' : '' ?>">
            Menunggu Konfirmasi
            <span class="tab-count"><?= $count_dikirim ?></span>
        </a>
        <a href="?tab=selesai" class="tab-btn tab-selesai <?= $filter === 'selesai' ? 'active tab-selesai' : '' ?>">
            Sudah Diterima
            <span class="tab-count"><?= $count_selesai ?></span>
        </a>
    </div>

    <?php if ($filter === 'dikirim' && !empty($donasi_list)): ?>
    <div class="info-banner">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
        <p>Klik <strong>Konfirmasi Terima</strong> setelah barang benar-benar sudah Anda terima. Tindakan ini akan menandai donasi sebagai <strong>Selesai</strong> dan memperbarui stok katalog secara otomatis.</p>
    </div>
    <?php endif; ?>

    <!-- CARD LIST -->
    <div class="card-grid">
        <?php if (empty($donasi_list)): ?>
        <div class="empty-state">
            <svg width="60" height="60" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <?php if ($filter === 'dikirim'): ?>
            <p>Tidak ada paket yang sedang dalam pengiriman.</p>
            <small>Paket akan muncul di sini setelah donatur mengisi nomor resi pengiriman.</small>
            <?php else: ?>
            <p>Belum ada donasi yang selesai dikonfirmasi.</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <?php foreach ($donasi_list as $d): ?>
        <div class="donasi-card <?= $d['status_donasi'] === 'selesai' ? 'done' : '' ?>" id="card-<?= htmlspecialchars($d['id']) ?>">
            <div class="card-top">

                <!-- Foto -->
                <div class="card-foto">
                    <?php if (!empty($d['foto_barang'])): ?>
                        <img src="../uploads/donasi/<?= htmlspecialchars($d['foto_barang']) ?>" alt="Foto barang">
                    <?php else: ?>
                        <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 18h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v10.5a1.5 1.5 0 001.5 1.5z"/></svg>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="card-info">
                    <div class="card-barang"><?= htmlspecialchars($d['nama_barang']) ?></div>
                    <div class="card-meta">
                        <?php if ($d['status_donasi'] === 'selesai'): ?>
                            <span class="badge-status badge-success">✓ Selesai</span>
                        <?php else: ?>
                            <span class="badge-status badge-primary">Dalam Pengiriman</span>
                        <?php endif; ?>
                        <span class="badge-kat"><?= ucfirst(htmlspecialchars($d['kategori'] ?? '-')) ?></span>
                        <span class="qty-pill">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5"/></svg>
                            <?= (int)$d['qty_donasi'] ?> unit
                        </span>
                    </div>
                    <div class="card-donatur">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                        <?= htmlspecialchars($d['nama_donatur']) ?>
                        <span class="card-kontak"> · <?= htmlspecialchars($d['email_donatur']) ?><?= $d['no_telp'] ? ' · ' . htmlspecialchars($d['no_telp']) : '' ?></span>
                    </div>

                    <!-- Resi / pengiriman -->
                    <?php if (!empty($d['no_resi'])): ?>
                    <div class="resi-box">
                        <div class="resi-icon">
                            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                        </div>
                        <div class="resi-detail">
                            <div class="resi-label">Nomor Resi</div>
                            <div class="resi-value"><?= htmlspecialchars($d['no_resi']) ?></div>
                            <div class="resi-kurir"><?= strtoupper(htmlspecialchars($d['kurir'] ?? '')) ?><?= $d['kota_asal'] ? ' · dari ' . htmlspecialchars($d['kota_asal']) : '' ?></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="margin-top:10px;font-size:13px;color:var(--muted);">
                        <em>Donatur belum mengisi nomor resi pengiriman.</em>
                    </div>
                    <?php endif; ?>

                    <!-- Done message -->
                    <?php if ($d['status_donasi'] === 'selesai'): ?>
                    <div class="done-box">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Barang telah dikonfirmasi diterima · <?= date('d M Y', strtotime($d['updated_at'])) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="card-actions">
                    <span class="date-chip"><?= date('d M Y', strtotime($d['created_at'])) ?></span>
                    <span class="card-id">#<?= htmlspecialchars($d['id']) ?></span>
                    <?php if ($d['status_donasi'] === 'dikirim'): ?>
                    <button
                        class="btn-confirm"
                        id="btn-<?= htmlspecialchars($d['id']) ?>"
                        onclick="openKonfirmasiModal('<?= htmlspecialchars($d['id']) ?>', '<?= htmlspecialchars(addslashes($d['nama_barang'])) ?>', <?= (int)$d['qty_donasi'] ?>)">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Konfirmasi Terima
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- MODAL KONFIRMASI -->
<div class="modal-overlay" id="modalKonfirmasi">
    <div class="modal">
        <div class="modal-icon">
            <svg width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h2 class="modal-title">Konfirmasi Penerimaan</h2>
        <p class="modal-body" id="modalKonfirmasiBody">
            Apakah Anda sudah menerima barang ini?<br>
            Tindakan ini tidak dapat dibatalkan.
        </p>
        <input type="hidden" id="konfirmasi_donasi_id">
        <div class="modal-actions">
            <button class="btn-ghost" onclick="closeModal('modalKonfirmasi')">Belum</button>
            <button class="btn-ok" id="btnKonfirmasiOk" onclick="konfirmasiTerima()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Ya, Sudah Diterima
            </button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast-container" id="toastContainer"></div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });
});

function showToast(msg, type = 'success') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    const icon = type === 'success'
        ? '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        : '<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    t.innerHTML = `<span class="toast-icon ${type}">${icon}</span><span class="toast-msg">${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 4500);
}

function openKonfirmasiModal(donasi_id, nama_barang, qty) {
    document.getElementById('konfirmasi_donasi_id').value = donasi_id;
    document.getElementById('modalKonfirmasiBody').innerHTML =
        `Apakah Anda sudah menerima <strong>${qty} unit ${nama_barang}</strong>?<br>Stok katalog akan diperbarui secara otomatis.`;
    openModal('modalKonfirmasi');
}

function konfirmasiTerima() {
    const donasi_id = document.getElementById('konfirmasi_donasi_id').value;
    const btn = document.getElementById('btnKonfirmasiOk');
    btn.disabled = true;
    btn.textContent = 'Memproses...';

    const fd = new FormData();
    fd.append('donasi_id', donasi_id);

    fetch('../backend/konfirm_terima.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            closeModal('modalKonfirmasi');
            if (data.ok) {
                showToast(data.message || 'Donasi berhasil dikonfirmasi!');
                // Visually update the card without full reload
                const card = document.getElementById('card-' + donasi_id);
                if (card) {
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.4s';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 400);
                    }, 1000);
                }
                // Update badge
                const badge = document.querySelector('.nav-item.active .badge');
                if (badge) {
                    const n = parseInt(badge.textContent) - 1;
                    if (n <= 0) badge.remove(); else badge.textContent = n;
                }
            } else {
                showToast(data.error || 'Gagal mengkonfirmasi.', 'error');
            }
        })
        .catch(() => showToast('Terjadi kesalahan koneksi.', 'error'))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Ya, Sudah Diterima'; });
}

<?php if ($flash): ?>
showToast(<?= json_encode($flash['msg']) ?>, <?= json_encode($flash['type'] ?? 'success') ?>);
<?php endif; ?>
</script>
</body>
</html>
