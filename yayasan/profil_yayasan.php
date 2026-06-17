<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php'); exit;
}
$yayasan_id   = (int)$_SESSION['id'];
$nama_yayasan = htmlspecialchars($_SESSION['nama'] ?? 'Yayasan');

$user = [];
try {
    $r = $pdo->prepare("SELECT nama_lengkap, email, no_telp, alamat, avatar FROM users WHERE id=?");
    $r->execute([$yayasan_id]);
    $user = $r->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {}

$legalitas_map = [];
try {
    $pdo->query("CREATE TABLE IF NOT EXISTS berkas_legalitas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        yayasan_id INT NOT NULL,
        jenis VARCHAR(50) NOT NULL,
        nama_file VARCHAR(255) NOT NULL,
        keterangan TEXT,
        status ENUM('pending','verified','rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $r = $pdo->prepare("SELECT jenis, nama_file, keterangan, status, created_at FROM berkas_legalitas WHERE yayasan_id=?");
    $r->execute([$yayasan_id]);
    foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $legalitas_map[$row['jenis']] = $row;
    }
} catch (Exception $e) {}

$badge_menunggu = $badge_dikirim = 0;
try {
    $r = $pdo->prepare("SELECT COUNT(*) AS n FROM donasi d JOIN katalog_kebutuhan k ON k.id=d.katalog_id WHERE k.yayasan_id=? AND d.status_donasi='menunggu'");
    $r->execute([$yayasan_id]);
    $badge_menunggu = (int)($r->fetch(PDO::FETCH_ASSOC)['n'] ?? 0);

    $r = $pdo->prepare("SELECT COUNT(*) AS n FROM donasi d JOIN katalog_kebutuhan k ON k.id=d.katalog_id WHERE k.yayasan_id=? AND d.status_donasi='dikirim'");
    $r->execute([$yayasan_id]);
    $badge_dikirim = (int)($r->fetch(PDO::FETCH_ASSOC)['n'] ?? 0);
} catch (Exception $e) {}

$jenis_labels = [
    'akta'           => 'Akta Pendirian',
    'sk_kemenkumham' => 'SK Kemenkumham',
    'npwp'           => 'NPWP',
    'foto_gedung'    => 'Foto Gedung',
    'lainnya'        => 'Dokumen Lainnya',
];

function statusLegalitas(string $s): string {
    $map = [
        'pending'  => ['Menunggu Verifikasi', '#fffbeb', '#d97706'],
        'verified' => ['Terverifikasi', '#f0fdf4', '#16a34a'],
        'rejected' => ['Ditolak', '#fef2f2', '#dc2626'],
    ];
    [$label, $bg, $color] = $map[$s] ?? [$s, '#f3f4f6', '#6b7280'];
    return "<span style=\"display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:{$bg};color:{$color}\">{$label}</span>";
}

$avatar_url = !empty($user['avatar'])
    ? '../uploads/avatars/' . htmlspecialchars($user['avatar'])
    : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil &amp; Legalitas — CareDrop</title>
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

        /* SIDEBAR */
        .sidebar { width: 240px; min-height: 100vh; background: var(--forest); position: fixed; top: 0; left: 0; z-index: 100; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-brand .brand-name { font-size: 20px; font-weight: 800; color: var(--mint); letter-spacing: -0.5px; }
        .sidebar-brand .brand-role { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
        .sidebar-nav { flex: 1; padding: 16px 12px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; color: rgba(255,255,255,0.65); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.18s ease; margin-bottom: 2px; }
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

        /* MAIN */
        .main { margin-left: 240px; flex: 1; padding: 32px 36px; max-width: calc(100vw - 240px); }
        .page-header { margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; color: var(--forest); letter-spacing: -0.5px; }
        .page-subtitle { font-size: 14px; color: var(--muted); margin-top: 4px; }

        /* TWO-COLUMN LAYOUT */
        .grid-2 { display: grid; grid-template-columns: 340px 1fr; gap: 24px; align-items: start; }

        /* CARD */
        .card { background: var(--white); border: 1px solid var(--border); border-radius: 18px; overflow: hidden; box-shadow: 0 1px 4px rgba(12,46,24,0.05); }
        .card-header { padding: 20px 24px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
        .card-header-icon { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--moss), var(--sage)); display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; }
        .card-title { font-size: 15px; font-weight: 800; color: var(--forest); }
        .card-body { padding: 22px 24px; }

        /* AVATAR */
        .avatar-wrap { display: flex; flex-direction: column; align-items: center; gap: 14px; padding-bottom: 20px; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .avatar-ring { position: relative; width: 96px; height: 96px; }
        .avatar-img {
            width: 96px; height: 96px; border-radius: 50%; object-fit: cover;
            border: 3px solid var(--sage); background: var(--bg);
            display: flex; align-items: center; justify-content: center;
            font-size: 36px; font-weight: 800; color: var(--moss);
        }
        .avatar-edit {
            position: absolute; bottom: 0; right: 0;
            width: 28px; height: 28px; border-radius: 50%;
            background: linear-gradient(135deg, var(--moss), var(--sage));
            border: 2px solid var(--white); display: flex; align-items: center;
            justify-content: center; color: #fff; cursor: pointer; transition: opacity 0.15s;
        }
        .avatar-edit:hover { opacity: 0.85; }
        .avatar-name { font-size: 16px; font-weight: 800; color: var(--forest); }
        .avatar-email { font-size: 12px; color: var(--muted); margin-top: -10px; }

        /* FORM */
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .form-control {
            width: 100%; padding: 10px 14px; border: 1px solid var(--border);
            border-radius: 10px; font-family: inherit; font-size: 14px; color: var(--ink);
            background: var(--white); transition: border-color 0.15s; outline: none;
        }
        .form-control:focus { border-color: var(--sage); box-shadow: 0 0 0 3px rgba(74,173,107,0.12); }
        .form-control[readonly] { background: var(--bg); color: var(--muted); cursor: not-allowed; }
        textarea.form-control { resize: vertical; min-height: 80px; }

        /* BUTTONS */
        .btn-primary {
            display: inline-flex; align-items: center; gap: 7px;
            background: linear-gradient(135deg, var(--moss), var(--sage));
            color: #fff; border: none; padding: 10px 20px; border-radius: 10px;
            font-family: inherit; font-size: 14px; font-weight: 600; cursor: pointer; transition: opacity 0.15s;
        }
        .btn-primary:hover { opacity: 0.88; }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: 10px; border: 1px solid var(--border);
            background: none; font-family: inherit; font-size: 14px; font-weight: 600;
            color: var(--muted); cursor: pointer; transition: all 0.15s;
        }
        .btn-ghost:hover { background: var(--bg); border-color: var(--sage); color: var(--moss); }
        .btn-danger {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--red-light); color: var(--red); border: 1px solid var(--red-border);
            padding: 9px 18px; border-radius: 10px; font-family: inherit; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.15s;
        }
        .btn-danger:hover { background: var(--red); color: #fff; }

        /* LEGALITAS ITEMS */
        .leg-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 0; border-bottom: 1px solid var(--border);
        }
        .leg-item:last-child { border-bottom: none; padding-bottom: 0; }
        .leg-icon { width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #3b82f6; flex-shrink: 0; }
        .leg-icon.uploaded { background: #f0fdf4; color: var(--moss); }
        .leg-info { flex: 1; min-width: 0; }
        .leg-label { font-size: 13px; font-weight: 700; color: var(--forest); }
        .leg-meta  { font-size: 11px; color: var(--muted); margin-top: 2px; }
        .leg-actions { display: flex; gap: 8px; flex-shrink: 0; align-items: center; }
        .btn-upload-sm {
            display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px;
            border-radius: 8px; border: 1px solid var(--border); background: var(--white);
            font-family: inherit; font-size: 12px; font-weight: 600; color: var(--muted);
            cursor: pointer; transition: all 0.15s;
        }
        .btn-upload-sm:hover { border-color: var(--sage); color: var(--moss); background: #f0fdf4; }
        .btn-view-sm {
            display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px;
            border-radius: 8px; border: 1px solid #bfdbfe; background: #eff6ff;
            font-size: 12px; font-weight: 600; color: #3b82f6; text-decoration: none; transition: all 0.15s;
        }
        .btn-view-sm:hover { background: #3b82f6; color: #fff; }

        /* SECTION DIVIDER */
        .section-sep { height: 1px; background: var(--border); margin: 20px 0; }
        .section-label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 14px; }

        /* TOAST */
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
        .toast { display: flex; align-items: center; gap: 12px; background: var(--white); border: 1px solid var(--border); border-radius: 12px; padding: 14px 18px; min-width: 280px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); animation: slideIn 0.25s ease; }
        .toast.success { border-left: 4px solid var(--sage); }
        .toast.error   { border-left: 4px solid var(--red); }
        .toast-icon.success { color: var(--moss); }
        .toast-icon.error   { color: var(--red); }
        .toast-msg { font-size: 14px; font-weight: 500; color: var(--ink); }
        @keyframes slideIn { from { transform: translateX(60px); opacity: 0; } to { transform: none; opacity: 1; } }

        /* HIDDEN FILE INPUT */
        .hidden-file { display: none; }

        @media (max-width: 1100px) { .grid-2 { grid-template-columns: 1fr; } }
        @media (max-width: 900px)  { .main { padding: 20px 16px; } }
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
        <a href="dashboard_yayasan.php" class="nav-item">
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
        <a href="profil_yayasan.php" class="nav-item active">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
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

<!-- MAIN -->
<main class="main">
    <div class="page-header">
        <h1 class="page-title">Profil &amp; Legalitas</h1>
        <p class="page-subtitle">Kelola informasi yayasan dan unggah dokumen legalitas untuk verifikasi.</p>
    </div>

    <div class="grid-2">

        <!-- LEFT COLUMN: Avatar + Info -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Profile Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                    </div>
                    <span class="card-title">Informasi Yayasan</span>
                </div>
                <div class="card-body">
                    <!-- Avatar -->
                    <div class="avatar-wrap">
                        <div class="avatar-ring">
                            <?php if ($avatar_url): ?>
                                <img src="<?= $avatar_url ?>" alt="Avatar" class="avatar-img" id="avatarImg">
                            <?php else: ?>
                                <div class="avatar-img" id="avatarPlaceholder">
                                    <?= mb_strtoupper(mb_substr($user['nama_lengkap'] ?? 'Y', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <label class="avatar-edit" for="avatarFile" title="Ganti foto">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/></svg>
                            </label>
                            <input type="file" id="avatarFile" class="hidden-file" accept="image/jpg,image/jpeg,image/png,image/webp">
                        </div>
                        <div class="avatar-name" id="displayNama"><?= htmlspecialchars($user['nama_lengkap'] ?? '') ?></div>
                        <div class="avatar-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                    </div>

                    <!-- Edit Form -->
                    <form id="formProfil">
                        <div class="form-group">
                            <label class="form-label">Nama Yayasan</label>
                            <input type="text" name="nama" class="form-control" id="inputNama"
                                value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="no_telp" class="form-control"
                                value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>" placeholder="Contoh: 08123456789">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea name="alamat" class="form-control" placeholder="Jl. ..."><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Password Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon" style="background:linear-gradient(135deg,#7c3aed,#a78bfa)">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    </div>
                    <span class="card-title">Ganti Password</span>
                </div>
                <div class="card-body">
                    <form id="formPassword">
                        <div class="form-group">
                            <label class="form-label">Password Lama</label>
                            <input type="password" name="password_lama" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password_baru" class="form-control" placeholder="Min. 8 karakter" required minlength="8">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="password_konfirm" class="form-control" placeholder="Ulangi password baru" required>
                        </div>
                        <button type="submit" class="btn-danger" style="width:100%;justify-content:center;">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                            Ubah Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Legalitas -->
        <div class="card">
            <div class="card-header">
                <div class="card-header-icon" style="background:linear-gradient(135deg,#0369a1,#38bdf8)">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                </div>
                <span class="card-title">Dokumen Legalitas</span>
            </div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.6;">
                    Unggah dokumen resmi yayasan Anda untuk proses verifikasi oleh admin CareDrop.
                    Format yang diterima: <strong>PDF, JPG, PNG</strong> (maks. 5MB per file).
                </p>

                <?php foreach ($jenis_labels as $jenis => $label):
                    $doc = $legalitas_map[$jenis] ?? null;
                    $hasDoc = !empty($doc);
                    $fileExt = $hasDoc ? strtolower(pathinfo($doc['nama_file'], PATHINFO_EXTENSION)) : '';
                    $isPdf = $fileExt === 'pdf';
                ?>
                <div class="leg-item" id="leg-<?= $jenis ?>">
                    <div class="leg-icon <?= $hasDoc ? 'uploaded' : '' ?>">
                        <?php if ($hasDoc): ?>
                            <?php if ($isPdf): ?>
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                            <?php else: ?>
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 18h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v10.5a1.5 1.5 0 001.5 1.5z"/></svg>
                            <?php endif; ?>
                        <?php else: ?>
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="leg-info">
                        <div class="leg-label"><?= $label ?></div>
                        <div class="leg-meta">
                            <?php if ($hasDoc): ?>
                                <?= statusLegalitas($doc['status']) ?>
                                &nbsp;·&nbsp; Diunggah <?= date('d M Y', strtotime($doc['created_at'])) ?>
                            <?php else: ?>
                                <span style="color:#9ca3af">Belum diunggah</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="leg-actions">
                        <?php if ($hasDoc): ?>
                            <a href="../uploads/legalitas/<?= htmlspecialchars($doc['nama_file']) ?>"
                               target="_blank" class="btn-view-sm">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Lihat
                            </a>
                        <?php endif; ?>
                        <label class="btn-upload-sm" for="legFile_<?= $jenis ?>">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                            <?= $hasDoc ? 'Perbarui' : 'Unggah' ?>
                        </label>
                        <input type="file" id="legFile_<?= $jenis ?>" class="hidden-file"
                               accept=".pdf,.jpg,.jpeg,.png"
                               data-jenis="<?= $jenis ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- end grid-2 -->
</main>

<!-- TOAST -->
<div class="toast-container" id="toastContainer"></div>

<script>
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

document.getElementById('formProfil').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Menyimpan…';
    fetch('../backend/update_profil.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showToast('Profil berhasil diperbarui!');
                document.getElementById('displayNama').textContent = data.nama;
            } else {
                showToast(data.error || 'Gagal menyimpan.', 'error');
            }
        })
        .catch(() => showToast('Terjadi kesalahan koneksi.', 'error'))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Simpan Perubahan'; });
});

document.getElementById('formPassword').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Mengubah…';
    fetch('../backend/ganti_password.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showToast(data.msg || 'Password berhasil diubah!');
                this.reset();
            } else {
                showToast(data.error || 'Gagal mengubah password.', 'error');
            }
        })
        .catch(() => showToast('Terjadi kesalahan koneksi.', 'error'))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg> Ubah Password'; });
});

document.getElementById('avatarFile').addEventListener('change', function() {
    if (!this.files[0]) return;
    const fd = new FormData();
    fd.append('avatar', this.files[0]);
    showToast('Mengunggah foto…');
    fetch('../backend/upload_avatar.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showToast('Foto profil berhasil diperbarui!');
                // Replace placeholder with real image di main area
                const ring = document.querySelector('.avatar-ring');
                const placeholder = document.getElementById('avatarPlaceholder');
                let img = document.getElementById('avatarImg');
                if (!img) {
                    if (placeholder) placeholder.remove();
                    img = document.createElement('img');
                    img.id = 'avatarImg';
                    img.alt = 'Avatar';
                    img.className = 'avatar-img';
                    ring.insertBefore(img, ring.querySelector('.avatar-edit'));
                }
                const newSrc = '../' + data.url + '?t=' + Date.now();
                img.src = newSrc;

                // Update juga foto profil di sidebar footer
                const footerAv = document.querySelector('.profile-av');
                if (footerAv) {
                    let footerImg = footerAv.querySelector('img');
                    if (!footerImg) {
                        footerAv.innerHTML = '';
                        footerImg = document.createElement('img');
                        footerImg.alt = 'foto profil';
                        footerAv.appendChild(footerImg);
                    }
                    footerImg.src = newSrc;
                }
            } else {
                showToast(data.error || 'Gagal mengunggah foto.', 'error');
            }
        })
        .catch(() => showToast('Terjadi kesalahan koneksi.', 'error'));
});

document.querySelectorAll('.hidden-file[data-jenis]').forEach(input => {
    input.addEventListener('change', function() {
        if (!this.files[0]) return;
        const jenis = this.dataset.jenis;
        const fd = new FormData();
        fd.append('berkas', this.files[0]);
        fd.append('jenis', jenis);
        showToast('Mengunggah dokumen…');
        fetch('../backend/upload_legalitas.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    showToast('Dokumen berhasil diunggah! Menunggu verifikasi admin.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.error || 'Gagal mengunggah dokumen.', 'error');
                }
            })
            .catch(() => showToast('Terjadi kesalahan koneksi.', 'error'));
    });
});
</script>
</body>
</html>
