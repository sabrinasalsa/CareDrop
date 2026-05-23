<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php'); exit;
}
$yayasan_id  = (int)$_SESSION['id'];
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

$filter_status = $_GET['status'] ?? 'menunggu';
$allowed = ['menunggu','disetujui','ditolak','dikirim','selesai','semua'];
if (!in_array($filter_status, $allowed)) $filter_status = 'menunggu';
$donasi_list = [];
try {
    $where_status = ($filter_status === 'semua') ? '' : "AND d.status_donasi = '$filter_status'";
    $sql = "SELECT
                d.id, d.qty_donasi, d.deskripsi_kondisi, d.foto_barang,
                d.status_donasi, d.alasan_tolak, d.created_at,
                k.nama_barang, k.kategori, k.urgensi,
                u.nama_lengkap AS nama_donatur, u.email AS email_donatur, u.no_telp,
                p.kurir, p.no_resi, p.tipe_layanan
            FROM donasi d
            JOIN katalog_kebutuhan k ON k.id = d.katalog_id
            JOIN users u ON u.id = d.donatur_id
            LEFT JOIN pengiriman p ON p.donasi_id = d.id
            WHERE k.yayasan_id = ? $where_status
            ORDER BY d.created_at DESC";
    $r = $koneksi->prepare($sql);
    $r->bind_param("i", $yayasan_id); $r->execute();
    $donasi_list = $r->get_result()->fetch_all(MYSQLI_ASSOC); $r->close();
} catch (Exception $e) {}

$counts = ['menunggu'=>0,'disetujui'=>0,'dikirim'=>0,'selesai'=>0,'ditolak'=>0];
try {
    $r = $koneksi->prepare("SELECT d.status_donasi, COUNT(*) AS n FROM donasi d
        JOIN katalog_kebutuhan k ON k.id = d.katalog_id
        WHERE k.yayasan_id = ?
        GROUP BY d.status_donasi");
    $r->bind_param("i", $yayasan_id); $r->execute();
    $rows = $r->get_result()->fetch_all(MYSQLI_ASSOC); $r->close();
    foreach ($rows as $row) {
        if (isset($counts[$row['status_donasi']])) $counts[$row['status_donasi']] = (int)$row['n'];
    }
} catch (Exception $e) {}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function statusBadge(string $s): string {
    $map = [
        'menunggu'  => ['label'=>'Menunggu',  'cls'=>'badge-warning'],
        'disetujui' => ['label'=>'Disetujui', 'cls'=>'badge-info'],
        'dikirim'   => ['label'=>'Dikirim',   'cls'=>'badge-primary'],
        'selesai'   => ['label'=>'Selesai',   'cls'=>'badge-success'],
        'ditolak'   => ['label'=>'Ditolak',   'cls'=>'badge-danger'],
        'dibatalkan'=> ['label'=>'Dibatalkan','cls'=>'badge-muted'],
    ];
    $d = $map[$s] ?? ['label'=>ucfirst($s),'cls'=>'badge-muted'];
    return "<span class=\"badge-status {$d['cls']}\">{$d['label']}</span>";
}
function urgensiLabel(string $u): string {
    return ['high'=>'Mendesak','med'=>'Sedang','low'=>'Normal'][$u] ?? ucfirst($u);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tawaran Masuk — CareDrop</title>
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
            padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer;
            font-family: inherit; font-size: 13px; font-weight: 600;
            color: var(--muted); background: none; transition: all 0.15s;
        }
        .tab-btn:hover { background: var(--bg); color: var(--ink); }
        .tab-btn.active { background: linear-gradient(135deg, var(--moss), var(--sage)); color: #fff; }
        .tab-btn .tab-count { font-size: 11px; background: rgba(255,255,255,0.25); padding: 1px 6px; border-radius: 10px; }
        .tab-btn:not(.active) .tab-count { background: var(--bg); color: var(--muted); }
        .tab-btn.active.tab-menunggu { background: linear-gradient(135deg, #d97706, var(--amber)); color: var(--ink); }
        .tab-btn.active.tab-ditolak  { background: linear-gradient(135deg, #b91c1c, var(--red)); }
        .tab-btn.active.tab-selesai  { background: linear-gradient(135deg, var(--pine), var(--moss)); }

        .card-grid { display: flex; flex-direction: column; gap: 14px; }

        .donasi-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 1px 4px rgba(12,46,24,0.05);
            transition: box-shadow 0.15s;
        }
        .donasi-card:hover { box-shadow: 0 4px 16px rgba(12,46,24,0.10); }
        .donasi-card.highlight { border-left: 4px solid var(--amber); }
        .donasi-card.highlight-green { border-left: 4px solid var(--sage); }
        .donasi-card.highlight-red   { border-left: 4px solid var(--red); }

        .card-body { padding: 20px 22px; display: grid; grid-template-columns: auto 1fr auto; gap: 18px; align-items: start; }

        .card-foto {
            width: 80px; height: 80px; border-radius: 12px; object-fit: cover;
            border: 1px solid var(--border); flex-shrink: 0; background: var(--bg);
            display: flex; align-items: center; justify-content: center; color: var(--muted);
        }
        .card-foto img { width: 80px; height: 80px; border-radius: 12px; object-fit: cover; }

        .card-info { min-width: 0; }
        .card-barang { font-size: 16px; font-weight: 700; color: var(--forest); margin-bottom: 4px; }
        .card-meta { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 8px; }
        .card-id { font-size: 11px; color: var(--muted); font-family: monospace; }
        .card-donatur { font-size: 13px; color: var(--ink); font-weight: 500; }
        .card-kontak  { font-size: 12px; color: var(--muted); }
        .card-desc    { font-size: 13px; color: var(--muted); margin-top: 6px; line-height: 1.5; }
        .resi-info    { margin-top: 10px; padding: 10px 14px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .resi-info span { font-size: 13px; font-weight: 600; color: var(--moss); }
        .resi-info small { font-size: 12px; color: var(--muted); }
        .alasan-tolak { margin-top: 10px; padding: 10px 14px; background: var(--red-light); border: 1px solid var(--red-border); border-radius: 10px; font-size: 13px; color: var(--red); }

        .card-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; flex-shrink: 0; }

        .badge-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-warning { background: #fffbeb; color: #d97706; }
        .badge-info    { background: #eff6ff; color: #3b82f6; }
        .badge-primary { background: #f0f9ff; color: #0284c7; }
        .badge-success { background: #f0fdf4; color: #16a34a; }
        .badge-danger  { background: var(--red-light); color: var(--red); }
        .badge-muted   { background: #f3f4f6; color: #6b7280; }

        .badge-kat {
            display: inline-block; padding: 2px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600; background: #eff6ff; color: #3b82f6;
        }
        .badge-urgensi {
            display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .urgensi-high { background: #fef2f2; color: #dc2626; }
        .urgensi-med  { background: #fffbeb; color: #d97706; }
        .urgensi-low  { background: #f0fdf4; color: #16a34a; }
        .qty-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; background: var(--bg); color: var(--forest); border: 1px solid var(--border); }

        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px; border-radius: 10px; border: none; cursor: pointer;
            font-family: inherit; font-size: 13px; font-weight: 600; transition: all 0.15s;
        }
        .btn-approve { background: linear-gradient(135deg, var(--moss), var(--sage)); color: #fff; }
        .btn-approve:hover { opacity: 0.88; }
        .btn-reject  { background: var(--red-light); color: var(--red); border: 1px solid var(--red-border); }
        .btn-reject:hover { background: var(--red); color: #fff; }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: 10px; border: 1px solid var(--border);
            background: none; font-family: inherit; font-size: 14px; font-weight: 600;
            color: var(--muted); cursor: pointer; transition: all 0.15s;
        }
        .btn-ghost:hover { background: var(--bg); border-color: var(--sage); color: var(--moss); }
        .btn-primary-sm {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, var(--moss), var(--sage));
            color: #fff; border: none; padding: 9px 18px; border-radius: 10px;
            font-family: inherit; font-size: 14px; font-weight: 600; cursor: pointer; transition: opacity 0.15s;
        }
        .btn-primary-sm:hover { opacity: 0.88; }

        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 200;
            background: rgba(11,31,18,0.45); backdrop-filter: blur(2px);
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--white); border-radius: 18px; padding: 32px;
            width: 100%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.18);
        }
        .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .modal-title { font-size: 18px; font-weight: 800; color: var(--forest); }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border);
            background: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: var(--muted); transition: all 0.15s;
        }
        .modal-close:hover { background: var(--red-light); color: var(--red); border-color: var(--red-border); }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--forest); margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 10px 14px; border: 1px solid var(--border);
            border-radius: 10px; font-family: inherit; font-size: 14px; color: var(--ink);
            background: var(--white); transition: border-color 0.15s; outline: none;
        }
        .form-control:focus { border-color: var(--sage); box-shadow: 0 0 0 3px rgba(74,173,107,0.12); }
        .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 6px; }

        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
        .toast {
            display: flex; align-items: center; gap: 12px;
            background: var(--white); border: 1px solid var(--border);
            border-radius: 12px; padding: 14px 18px; min-width: 280px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12); animation: slideIn 0.25s ease;
        }
        .toast.success { border-left: 4px solid var(--sage); }
        .toast.error   { border-left: 4px solid var(--red); }
        .toast-icon { flex-shrink: 0; }
        .toast.success .toast-icon { color: var(--moss); }
        .toast.error   .toast-icon { color: var(--red); }
        .toast-msg { font-size: 14px; font-weight: 500; color: var(--ink); }
        @keyframes slideIn { from { transform: translateX(60px); opacity: 0; } to { transform: none; opacity: 1; } }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
        .empty-state svg { margin: 0 auto 14px; display: block; opacity: 0.3; }
        .empty-state p { font-size: 15px; }
        .date-chip { font-size: 11px; color: var(--muted); white-space: nowrap; }

        @media (max-width: 900px) {
            .main { padding: 20px 16px; }
            .card-body { grid-template-columns: 1fr; }
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
        <a href="tawaran_masuk.php" class="nav-item active">
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
        <a href="../backend/logout.php" class="nav-item" style="color:rgba(255,255,255,0.5);">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
            Keluar
        </a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="page-header">
        <h1 class="page-title">Tawaran Masuk</h1>
        <p class="page-subtitle">Selamat datang, <?= $nama_yayasan ?> — kelola dan tindak-lanjuti tawaran donasi dari donatur.</p>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <?php
        $tabs = [
            ['key'=>'menunggu',  'label'=>'Menunggu',  'cls'=>'tab-menunggu'],
            ['key'=>'disetujui', 'label'=>'Disetujui', 'cls'=>''],
            ['key'=>'dikirim',   'label'=>'Dikirim',   'cls'=>''],
            ['key'=>'selesai',   'label'=>'Selesai',   'cls'=>'tab-selesai'],
            ['key'=>'ditolak',   'label'=>'Ditolak',   'cls'=>'tab-ditolak'],
            ['key'=>'semua',     'label'=>'Semua',     'cls'=>''],
        ];
        foreach ($tabs as $t):
            $active = $filter_status === $t['key'] ? 'active ' . $t['cls'] : '';
            $cnt = $t['key'] === 'semua' ? array_sum($counts) : ($counts[$t['key']] ?? 0);
        ?>
        <a href="?status=<?= $t['key'] ?>" class="tab-btn <?= $active ?>">
            <?= $t['label'] ?>
            <span class="tab-count"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- CARD LIST -->
    <div class="card-grid">
        <?php if (empty($donasi_list)): ?>
        <div class="empty-state">
            <svg width="56" height="56" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
            <p>Belum ada tawaran donasi dengan status <strong><?= htmlspecialchars($filter_status) ?></strong>.</p>
        </div>
        <?php else: ?>
        <?php foreach ($donasi_list as $d):
            $hl = '';
            if ($d['status_donasi'] === 'menunggu') $hl = 'highlight';
            elseif ($d['status_donasi'] === 'selesai' || $d['status_donasi'] === 'dikirim') $hl = 'highlight-green';
            elseif ($d['status_donasi'] === 'ditolak') $hl = 'highlight-red';
        ?>
        <div class="donasi-card <?= $hl ?>">
            <div class="card-body">

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
                        <?= statusBadge($d['status_donasi']) ?>
                        <span class="badge-kat"><?= ucfirst(htmlspecialchars($d['kategori'] ?? '-')) ?></span>
                        <span class="badge-urgensi urgensi-<?= $d['urgensi'] ?>"><?= urgensiLabel($d['urgensi']) ?></span>
                        <span class="qty-pill">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5"/></svg>
                            <?= (int)$d['qty_donasi'] ?> unit
                        </span>
                    </div>
                    <div class="card-donatur">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                        <?= htmlspecialchars($d['nama_donatur']) ?>
                        <span class="card-kontak"> · <?= htmlspecialchars($d['email_donatur']) ?><?= $d['no_telp'] ? ' · ' . htmlspecialchars($d['no_telp']) : '' ?></span>
                    </div>
                    <?php if (!empty($d['deskripsi_kondisi'])): ?>
                    <div class="card-desc">"<?= htmlspecialchars($d['deskripsi_kondisi']) ?>"</div>
                    <?php endif; ?>

                    <!-- Resi info (dikirim / selesai) -->
                    <?php if (!empty($d['no_resi'])): ?>
                    <div class="resi-info">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                        <div>
                            <span><?= strtoupper(htmlspecialchars($d['kurir'])) ?> — <?= htmlspecialchars($d['no_resi']) ?></span>
                            <br><small>Resi pengiriman dari donatur</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Alasan tolak -->
                    <?php if ($d['status_donasi'] === 'ditolak' && !empty($d['alasan_tolak'])): ?>
                    <div class="alasan-tolak">
                        <strong>Alasan penolakan:</strong> <?= htmlspecialchars($d['alasan_tolak']) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="card-actions">
                    <span class="date-chip"><?= date('d M Y', strtotime($d['created_at'])) ?></span>
                    <span class="card-id">#<?= htmlspecialchars($d['id']) ?></span>
                    <?php if ($d['status_donasi'] === 'menunggu'): ?>
                        <button class="btn btn-approve" onclick="setujuiDonasi('<?= htmlspecialchars($d['id']) ?>')">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Setujui
                        </button>
                        <button class="btn btn-reject" onclick="openTolakModal('<?= htmlspecialchars($d['id']) ?>')">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Tolak
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<!-- MODAL TOLAK -->
<div class="modal-overlay" id="modalTolak">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Tolak Tawaran</h2>
            <button class="modal-close" onclick="closeModal('modalTolak')">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <input type="hidden" id="tolak_donasi_id">
        <div class="form-group">
            <label class="form-label">Alasan Penolakan <span style="color:var(--red)">*</span></label>
            <textarea id="tolak_alasan" class="form-control" rows="3" placeholder="Jelaskan mengapa tawaran ini tidak dapat diterima..."></textarea>
        </div>
        <div class="form-actions">
            <button class="btn-ghost" onclick="closeModal('modalTolak')">Batal</button>
            <button class="btn btn-reject" id="btnKonfirmasiTolak" onclick="konfirmasiTolak()">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Tolak Tawaran
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
    t.innerHTML = `<span class="toast-icon">${icon}</span><span class="toast-msg">${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}

function setujuiDonasi(donasi_id) {
    if (!confirm('Setujui tawaran donasi ini? Donatur akan diminta mengirim barang dan memasukkan nomor resi.')) return;
    const fd = new FormData();
    fd.append('aksi', 'setujui');
    fd.append('donasi_id', donasi_id);
    fetch('../backend/aksi_tawaran.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showToast(data.msg || 'Tawaran berhasil disetujui!');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast(data.error || 'Gagal menyetujui tawaran.', 'error');
            }
        })
        .catch(() => showToast('Terjadi kesalahan koneksi.', 'error'));
}

function openTolakModal(donasi_id) {
    document.getElementById('tolak_donasi_id').value = donasi_id;
    document.getElementById('tolak_alasan').value = '';
    openModal('modalTolak');
}

function konfirmasiTolak() {
    const donasi_id = document.getElementById('tolak_donasi_id').value;
    const alasan    = document.getElementById('tolak_alasan').value.trim();
    if (!alasan) { showToast('Harap isi alasan penolakan.', 'error'); return; }

    const fd = new FormData();
    fd.append('aksi', 'tolak');
    fd.append('donasi_id', donasi_id);
    fd.append('alasan', alasan);
    fetch('../backend/aksi_tawaran.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            closeModal('modalTolak');
            if (data.ok) {
                showToast(data.msg || 'Tawaran berhasil ditolak.');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast(data.error || 'Gagal menolak tawaran.', 'error');
            }
        })
        .catch(() => showToast('Terjadi kesalahan koneksi.', 'error'));
}

<?php if ($flash): ?>
showToast(<?= json_encode($flash['msg']) ?>, <?= json_encode($flash['type'] ?? 'success') ?>);
<?php endif; ?>
</script>
</body>
</html>
