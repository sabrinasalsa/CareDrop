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
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css" />
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
        <a href="profil_yayasan.php" class="nav-item active">
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
                        <i class="ph ph-user" style="font-size: 1.25em; vertical-align: middle;"></i>
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
                                <i class="ph ph-camera" style="font-size: 1.25em; vertical-align: middle;"></i>
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
                            <i class="ph ph-check-circle" style="font-size: 1.25em; vertical-align: middle;"></i>
                            Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Password Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon" style="background:linear-gradient(135deg,#7c3aed,#a78bfa)">
                        <i class="ph ph-lock-key" style="font-size: 1.25em; vertical-align: middle;"></i>
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
                            <i class="ph ph-lock-key" style="font-size: 1.25em; vertical-align: middle;"></i>
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
                    <i class="ph ph-buildings" style="font-size: 1.25em; vertical-align: middle;"></i>
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
                                <i class="ph ph-buildings" style="font-size: 1.25em; vertical-align: middle;"></i>
                            <?php else: ?>
                                <i class="ph ph-image" style="font-size: 1.25em; vertical-align: middle;"></i>
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="ph ph-buildings" style="font-size: 1.25em; vertical-align: middle;"></i>
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
                                <i class="ph ph-eye" style="font-size: 1.25em; vertical-align: middle;"></i>
                                Lihat
                            </a>
                        <?php endif; ?>
                        <label class="btn-upload-sm" for="legFile_<?= $jenis ?>">
                            <i class="ph ph-download-simple" style="font-size: 1.25em; vertical-align: middle;"></i>
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
        ? '<i class="ph ph-check-circle" style="font-size: 1.25em; vertical-align: middle;"></i>'
        : '<i class="ph ph-x-circle" style="font-size: 1.25em; vertical-align: middle;"></i>';
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
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="ph ph-check-circle" style="font-size: 1.25em; vertical-align: middle;"></i> Simpan Perubahan'; });
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
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="ph ph-lock-key" style="font-size: 1.25em; vertical-align: middle;"></i> Ubah Password'; });
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
