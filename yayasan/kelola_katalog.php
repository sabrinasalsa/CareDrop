<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php'); exit;
}
$yayasan_id = (int)$_SESSION['id'];
$nama_yayasan = htmlspecialchars($_SESSION['nama'] ?? 'Yayasan');

$badge_menunggu = $badge_dikirim = 0;
$katalog_list = [];

try {

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

    $r = $pdo->prepare("SELECT k.*,
           (SELECT COUNT(*) FROM donasi d WHERE d.katalog_id = k.id
            AND d.status_donasi NOT IN ('dibatalkan','ditolak')) as total_donasi_item
        FROM katalog_kebutuhan k
        WHERE k.yayasan_id = ?
        ORDER BY k.created_at DESC");
    $r->execute([$yayasan_id]);
    $katalog_list = $r->fetchAll();

} catch (Exception $e) {

}


$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Kebutuhan — CareDrop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --forest: #0c2e18;
            --pine: #1e5630;
            --moss: #2d7a44;
            --sage: #4aad6b;
            --mint: #7ed9a3;
            --amber: #f0c040;
            --ink: #0b1f12;
            --muted: #5c7d65;
            --bg: #f4fbf6;
            --border: #d4e8db;
            --white: #ffffff;
            --red: #dc2626;
            --red-light: #fef2f2;
            --red-border: #fecaca;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--ink); display: flex; min-height: 100vh; }

        .sidebar {
            width: 240px; min-height: 100vh; background: var(--forest);
            position: fixed; top: 0; left: 0; z-index: 100;
            display: flex; flex-direction: column; padding: 0;
        }
        .sidebar-brand {
            padding: 24px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar-brand .brand-name {
            font-size: 20px; font-weight: 800; color: var(--mint);
            letter-spacing: -0.5px;
        }
        .sidebar-brand .brand-role {
            font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .sidebar-nav { flex: 1; padding: 16px 12px; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 10px;
            color: rgba(255,255,255,0.65); text-decoration: none;
            font-size: 14px; font-weight: 500;
            transition: all 0.18s ease; margin-bottom: 2px;
            position: relative;
        }
        .nav-item:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .nav-item.active { background: rgba(126,217,163,0.15); color: var(--mint); }
        .nav-item .badge {
            margin-left: auto; background: var(--amber); color: var(--ink);
            font-size: 11px; font-weight: 700; padding: 1px 7px; border-radius: 20px;
        }
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
        .main { margin-left: 240px; flex: 1; min-height: 100vh; padding: 32px 36px; }
        .page-header { margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; color: var(--forest); letter-spacing: -0.5px; }
        .page-subtitle { font-size: 14px; color: var(--muted); margin-top: 4px; }


        /* TOOLBAR */
        .toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, var(--moss), var(--sage));
            color: #fff; border: none; padding: 10px 20px; border-radius: 10px;
            font-family: inherit; font-size: 14px; font-weight: 600;
            cursor: pointer; transition: opacity 0.18s; text-decoration: none;
        }
        .btn-primary:hover { opacity: 0.88; }
        .search-box {
            display: flex; align-items: center; gap: 8px;
            background: var(--white); border: 1px solid var(--border);
            border-radius: 10px; padding: 8px 14px;
        }
        .search-box input {
            border: none; outline: none; font-family: inherit; font-size: 14px;
            color: var(--ink); background: transparent; width: 220px;
        }
        .search-box svg { color: var(--muted); flex-shrink: 0; }

        /* TABLE */
        .table-card {
            background: var(--white); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden;
            box-shadow: 0 1px 4px rgba(12,46,24,0.05);
        }
        table { width: 100%; border-collapse: collapse; }
        thead tr { border-bottom: 1px solid var(--border); }
        thead th {
            padding: 13px 16px; text-align: left;
            font-size: 12px; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.5px;
            background: #f9fdf9;
        }
        tbody tr { border-bottom: 1px solid var(--border); transition: background 0.12s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f9fdf9; }
        tbody td { padding: 14px 16px; font-size: 14px; color: var(--ink); vertical-align: middle; }

        /* BADGES */
        .badge-urgensi {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .urgensi-high { background: #fef2f2; color: #dc2626; }
        .urgensi-med { background: #fffbeb; color: #d97706; }
        .urgensi-low { background: #f0fdf4; color: #16a34a; }
        .badge-status {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .status-aktif { background: #f0fdf4; color: #16a34a; }
        .status-tutup { background: #f3f4f6; color: #6b7280; }
        .badge-kat {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 12px; font-weight: 600; background: #eff6ff; color: #3b82f6;
        }

        /* PROGRESS */
        .progress-wrap { display: flex; flex-direction: column; gap: 4px; min-width: 120px; }
        .progress-label { font-size: 12px; color: var(--muted); }
        .progress-bar-bg { background: var(--border); border-radius: 99px; height: 6px; overflow: hidden; }
        .progress-bar-fill { background: linear-gradient(90deg, var(--moss), var(--sage)); height: 100%; border-radius: 99px; transition: width 0.3s; }

        /* AKSI BUTTONS */
        .btn-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border);
            background: var(--white); cursor: pointer; color: var(--muted);
            transition: all 0.15s; text-decoration: none;
        }
        .btn-icon:hover { border-color: var(--sage); color: var(--moss); background: #f0fdf4; }
        .btn-icon.danger:hover { border-color: #fca5a5; color: var(--red); background: var(--red-light); }
        .btn-icon.warning:hover { border-color: #fcd34d; color: #d97706; background: #fffbeb; }
        .btn-icon.view:hover { border-color: #93c5fd; color: #2563eb; background: #eff6ff; }
        .aksi-group { display: flex; gap: 6px; align-items: center; }

        /* DETAIL MODAL */
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px; }
        .detail-field { display: flex; flex-direction: column; gap: 4px; }
        .detail-label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.4px; }
        .detail-value { font-size: 14px; font-weight: 600; color: var(--ink); }
        .detail-full { grid-column: 1 / -1; }
        .detail-desc { font-size: 13px; color: var(--muted); line-height: 1.6; background: var(--bg); border-radius: 8px; padding: 10px 12px; margin-top: 4px; }
        .detail-progress { grid-column: 1 / -1; }
        .detail-prog-bar { background: var(--border); border-radius: 99px; height: 10px; overflow: hidden; margin-top: 8px; }
        .detail-prog-fill { background: linear-gradient(90deg, var(--moss), var(--sage)); height: 100%; border-radius: 99px; transition: width 0.4s; }

        /* MODAL */
        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 200;
            background: rgba(11,31,18,0.45); backdrop-filter: blur(2px);
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--white); border-radius: 18px; padding: 32px;
            width: 100%; max-width: 520px; box-shadow: 0 20px 60px rgba(0,0,0,0.18);
            max-height: 90vh; overflow-y: auto;
        }
        .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
        .modal-title { font-size: 18px; font-weight: 800; color: var(--forest); }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border);
            background: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
            color: var(--muted); transition: all 0.15s;
        }
        .modal-close:hover { background: var(--red-light); color: var(--red); border-color: var(--red-border); }

        /* FORM */
        .form-group { margin-bottom: 18px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: var(--forest); margin-bottom: 7px; }
        .form-control {
            width: 100%; padding: 10px 14px; border: 1px solid var(--border);
            border-radius: 10px; font-family: inherit; font-size: 14px; color: var(--ink);
            background: var(--white); transition: border-color 0.15s; outline: none;
        }
        .form-control:focus { border-color: var(--sage); box-shadow: 0 0 0 3px rgba(74,173,107,0.12); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
        .btn-ghost {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: 10px; border: 1px solid var(--border);
            background: none; font-family: inherit; font-size: 14px; font-weight: 600;
            color: var(--muted); cursor: pointer; transition: all 0.15s;
        }
        .btn-ghost:hover { background: var(--bg); border-color: var(--sage); color: var(--moss); }

        /* TOAST */
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; }
        .toast {
            display: flex; align-items: center; gap: 12px;
            background: var(--white); border: 1px solid var(--border);
            border-radius: 12px; padding: 14px 18px; min-width: 280px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            animation: slideIn 0.25s ease;
        }
        .toast.success { border-left: 4px solid var(--sage); }
        .toast.error { border-left: 4px solid var(--red); }
        .toast-icon { flex-shrink: 0; }
        .toast.success .toast-icon { color: var(--moss); }
        .toast.error .toast-icon { color: var(--red); }
        .toast-msg { font-size: 14px; font-weight: 500; color: var(--ink); }
        @keyframes slideIn { from { transform: translateX(60px); opacity: 0; } to { transform: none; opacity: 1; } }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
        .empty-state svg { margin: 0 auto 12px; display: block; opacity: 0.35; }
        .empty-state p { font-size: 15px; }

        @media (max-width: 900px) {
            .main { padding: 20px 16px; }
        }
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
        <a href="kelola_katalog.php" class="nav-item active">
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
        <h1 class="page-title">Katalog Kebutuhan</h1>
        <p class="page-subtitle">Selamat datang, <?= $nama_yayasan ?> — kelola daftar kebutuhan barang donasi Anda.</p>
    </div>



    <!-- TOOLBAR -->
    <div class="toolbar">
        <?php if (($_SESSION['status_verifikasi'] ?? '') !== 'pending'): ?>
        <button class="btn-primary" onclick="openModal('modalTambah')">
            <i class="ph ph-plus" style="font-size: 1.25em; vertical-align: middle;"></i>
            Tambah Kebutuhan
        </button>
        <?php else: ?>
        <div style="font-size:13px; color:var(--red); font-weight:600;">Akun belum diverifikasi</div>
        <?php endif; ?>
        <div class="search-box">
            <i class="ph ph-magnifying-glass" style="font-size: 1.25em; vertical-align: middle;"></i>
            <input type="text" id="searchInput" placeholder="Cari nama barang..." oninput="filterTable()">
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-card">
        <?php if (empty($katalog_list)): ?>
        <div class="empty-state">
            <i class="ph ph-package" style="font-size: 1.25em; vertical-align: middle;"></i>
            <p>Belum ada katalog kebutuhan. Tambahkan item pertama Anda.</p>
        </div>
        <?php else: ?>
        <table id="tabelKatalog">
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Urgensi</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($katalog_list as $item):
                    $pct = ($item['target_butuh'] > 0) ? min(100, round(($item['jumlah_terkumpul'] / $item['target_butuh']) * 100)) : 0;
                    $urgensi_label = ['high' => 'Mendesak', 'med' => 'Sedang', 'low' => 'Normal'][$item['urgensi']] ?? $item['urgensi'];
                    $kat_label = ucfirst($item['kategori'] ?? '-');
                ?>
                <tr data-name="<?= strtolower(htmlspecialchars($item['nama_barang'])) ?>">
                    <td>
                        <div style="font-weight:600;color:var(--forest)"><?= htmlspecialchars($item['nama_barang']) ?></div>
                        <?php if (!empty($item['deskripsi'])): ?>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px"><?= htmlspecialchars(mb_substr($item['deskripsi'],0,60)) ?>...</div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge-kat"><?= $kat_label ?></span></td>
                    <td>
                        <span class="badge-urgensi urgensi-<?= $item['urgensi'] ?>">
                            <?= $urgensi_label ?>
                        </span>
                    </td>
                    <td>
                        <div class="progress-wrap">
                            <div class="progress-label"><?= $item['jumlah_terkumpul'] ?> / <?= $item['target_butuh'] ?> unit (<?= $pct ?>%)</div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width:<?= $pct ?>%"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge-status <?= $item['aktif'] ? 'status-aktif' : 'status-tutup' ?>">
                            <?= $item['aktif'] ? 'Aktif' : 'Tutup' ?>
                        </span>
                    </td>
                    <td>
                        <div class="aksi-group">
                            <button class="btn-icon view btn-detail" title="Lihat Detail"
                                data-item='<?= json_encode($item, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_TAG) ?>'>
                                <i class="ph ph-eye" style="font-size: 1.25em; vertical-align: middle;"></i>
                            </button>
                            <button class="btn-icon btn-edit" title="Edit"
                                data-item='<?= json_encode($item, JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_TAG) ?>'>
                                <i class="ph ph-pencil-simple" style="font-size: 1.25em; vertical-align: middle;"></i>
                            </button>
                            <a href="hapus_kebutuhan.php?id=<?= $item['id'] ?>" class="btn-icon danger" title="Hapus" onclick="return confirm('Hapus item ini? Aksi tidak dapat dibatalkan.')">
                                <i class="ph ph-trash" style="font-size: 1.25em; vertical-align: middle;"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</main>

<!-- MODAL TAMBAH -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Kebutuhan Baru</h2>
            <button class="modal-close" onclick="closeModal('modalTambah')">
                <i class="ph ph-x" style="font-size: 1.25em; vertical-align: middle;"></i>
            </button>
        </div>
        <form action="../backend/proses_tambah_kebutuhan.php" method="POST">
            <div class="form-group">
                <label class="form-label">Nama Barang <span style="color:var(--red)">*</span></label>
                <input type="text" name="nama_barang" class="form-control" placeholder="Contoh: Baju Anak Ukuran M" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kategori <span style="color:var(--red)">*</span></label>
                    <select name="kategori" class="form-control" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="pakaian">Pakaian</option>
                        <option value="buku">Buku</option>
                        <option value="elektronik">Elektronik</option>
                        <option value="perabot">Perabot</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Urgensi <span style="color:var(--red)">*</span></label>
                    <select name="urgensi" class="form-control" required>
                        <option value="">-- Pilih Urgensi --</option>
                        <option value="high">Mendesak</option>
                        <option value="med">Sedang</option>
                        <option value="low">Normal</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Target Kebutuhan (unit) <span style="color:var(--red)">*</span></label>
                <input type="number" name="target_butuh" class="form-control" min="1" placeholder="Contoh: 50" required>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3" placeholder="Jelaskan kondisi atau spesifikasi barang yang dibutuhkan..."></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-ghost" onclick="closeModal('modalTambah')">Batal</button>
                <button type="submit" class="btn-primary">
                    <i class="ph ph-plus" style="font-size: 1.25em; vertical-align: middle;"></i>
                    Simpan Kebutuhan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETAIL -->
<div class="modal-overlay" id="modalDetail">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h2 class="modal-title" id="detail_title">Detail Kebutuhan</h2>
            <button class="modal-close" onclick="closeModal('modalDetail')">
                <i class="ph ph-x" style="font-size: 1.25em; vertical-align: middle;"></i>
            </button>
        </div>
        <div class="detail-grid">
            <div class="detail-field">
                <span class="detail-label">Kategori</span>
                <span class="detail-value" id="detail_kategori">—</span>
            </div>
            <div class="detail-field">
                <span class="detail-label">Urgensi</span>
                <span class="detail-value" id="detail_urgensi">—</span>
            </div>
            <div class="detail-field">
                <span class="detail-label">Target Kebutuhan</span>
                <span class="detail-value" id="detail_target">—</span>
            </div>
            <div class="detail-field">
                <span class="detail-label">Terkumpul</span>
                <span class="detail-value" id="detail_terkumpul">—</span>
            </div>
            <div class="detail-field">
                <span class="detail-label">Status</span>
                <span class="detail-value" id="detail_status">—</span>
            </div>
            <div class="detail-field">
                <span class="detail-label">Tanggal Dibuat</span>
                <span class="detail-value" id="detail_tanggal">—</span>
            </div>
            <div class="detail-progress">
                <span class="detail-label">Progress Pemenuhan</span>
                <div class="detail-prog-bar" style="margin-top:8px">
                    <div class="detail-prog-fill" id="detail_progbar" style="width:0%"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-top:5px">
                    <span id="detail_prog_label">0 unit terkumpul</span>
                    <span id="detail_prog_pct">0%</span>
                </div>
            </div>
            <div class="detail-full detail-field">
                <span class="detail-label">Deskripsi</span>
                <div class="detail-desc" id="detail_deskripsi">—</div>
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="btn-ghost" onclick="closeModal('modalDetail')">Tutup</button>
            <button type="button" class="btn-primary" id="detail_edit_btn" onclick="switchToEdit()">
                <i class="ph ph-pencil-simple" style="font-size: 1.25em; vertical-align: middle;"></i>
                Edit Item
            </button>
        </div>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">Edit Kebutuhan</h2>
            <button class="modal-close" onclick="closeModal('modalEdit')">
                <i class="ph ph-x" style="font-size: 1.25em; vertical-align: middle;"></i>
            </button>
        </div>
        <form id="formEdit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label class="form-label">Nama Barang <span style="color:var(--red)">*</span></label>
                <input type="text" name="nama_barang" id="edit_nama" class="form-control" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select name="kategori" id="edit_kategori" class="form-control">
                        <option value="pakaian">Pakaian</option>
                        <option value="buku">Buku</option>
                        <option value="elektronik">Elektronik</option>
                        <option value="perabot">Perabot</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Urgensi</label>
                    <select name="urgensi" id="edit_urgensi" class="form-control">
                        <option value="high">Mendesak</option>
                        <option value="med">Sedang</option>
                        <option value="low">Normal</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Target Kebutuhan (unit)</label>
                <input type="number" name="target_butuh" id="edit_target" class="form-control" min="1">
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-ghost" onclick="closeModal('modalEdit')">Batal</button>
                <button type="submit" class="btn-primary">
                    <i class="ph ph-pencil-simple" style="font-size: 1.25em; vertical-align: middle;"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TOAST -->
<div class="toast-container" id="toastContainer"></div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

// Pasang event listener untuk tombol detail & edit via data-attribute
document.querySelectorAll('.btn-detail').forEach(btn => {
    btn.addEventListener('click', function() {
        try {
            const data = JSON.parse(this.getAttribute('data-item'));
            openDetailModal(data);
        } catch(e) {
            console.error('Gagal parse data item:', e);
        }
    });
});

document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function() {
        try {
            const data = JSON.parse(this.getAttribute('data-item'));
            openEditModal(data);
        } catch(e) {
            console.error('Gagal parse data item:', e);
        }
    });
});

let _currentDetailData = null;

function openDetailModal(data) {
    _currentDetailData = data;
    const urgLabel = {high:'Mendesak', med:'Sedang', low:'Normal'};
    const urgColor = {high:'#dc2626', med:'#d97706', low:'#16a34a'};
    const urgBg    = {high:'#fef2f2', med:'#fffbeb', low:'#f0fdf4'};
    const pct = data.target_butuh > 0
        ? Math.min(100, Math.round((data.jumlah_terkumpul / data.target_butuh) * 100))
        : 0;

    document.getElementById('detail_title').textContent = data.nama_barang;
    document.getElementById('detail_kategori').textContent = data.kategori
        ? data.kategori.charAt(0).toUpperCase() + data.kategori.slice(1) : '—';

    const uEl = document.getElementById('detail_urgensi');
    const uKey = data.urgensi || 'low';
    uEl.textContent = urgLabel[uKey] || data.urgensi;
    uEl.style.cssText = `display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;background:${urgBg[uKey]};color:${urgColor[uKey]}`;

    document.getElementById('detail_target').textContent = (data.target_butuh || 0) + ' unit';
    document.getElementById('detail_terkumpul').textContent = (data.jumlah_terkumpul || 0) + ' unit';

    const stEl = document.getElementById('detail_status');
    if (data.aktif == 1 || data.aktif === true) {
        stEl.textContent = 'Aktif';
        stEl.style.cssText = 'display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;background:#f0fdf4;color:#16a34a';
    } else {
        stEl.textContent = 'Tutup';
        stEl.style.cssText = 'display:inline-block;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;background:#f3f4f6;color:#6b7280';
    }

    if (data.created_at) {
        const d = new Date(data.created_at);
        document.getElementById('detail_tanggal').textContent =
            d.toLocaleDateString('id-ID', {day:'2-digit', month:'long', year:'numeric'});
    } else {
        document.getElementById('detail_tanggal').textContent = '—';
    }

    document.getElementById('detail_progbar').style.width = pct + '%';
    document.getElementById('detail_prog_label').textContent = (data.jumlah_terkumpul || 0) + ' unit terkumpul';
    document.getElementById('detail_prog_pct').textContent = pct + '%';
    document.getElementById('detail_deskripsi').textContent = data.deskripsi || 'Tidak ada deskripsi.';

    openModal('modalDetail');
}

function switchToEdit() {
    closeModal('modalDetail');
    if (_currentDetailData) openEditModal(_currentDetailData);
}

function openEditModal(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_nama').value = data.nama_barang;
    document.getElementById('edit_kategori').value = data.kategori;
    document.getElementById('edit_urgensi').value = data.urgensi;
    document.getElementById('edit_target').value = data.target_butuh;
    document.getElementById('edit_deskripsi').value = data.deskripsi || '';
    openModal('modalEdit');
}

function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#tabelKatalog tbody tr').forEach(row => {
        row.style.display = row.dataset.name.includes(q) ? '' : 'none';
    });
}

function showToast(msg, type = 'success') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    const icon = type === 'success'
        ? '<i class="ph ph-check-circle" style="font-size: 1.25em; vertical-align: middle;"></i>'
        : '<i class="ph ph-x-circle" style="font-size: 1.25em; vertical-align: middle;"></i>';
    t.innerHTML = `<span class="toast-icon">${icon}</span><span class="toast-msg">${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}

function toggleKatalog(id, btn) {
    fetch('../backend/toggle_kebutuhan.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Status berhasil diubah');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Gagal mengubah status', 'error');
        }
    })
    .catch(() => showToast('Terjadi kesalahan koneksi', 'error'));
}

// ── Submit edit via AJAX ──
document.getElementById('formEdit').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    fetch('../backend/edit_kebutuhan.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                closeModal('modalEdit');
                showToast(data.msg || 'Kebutuhan berhasil diperbarui.', 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast(data.msg || 'Gagal menyimpan perubahan.', 'error');
            }
        })
        .catch(() => showToast('Terjadi kesalahan koneksi.', 'error'))
        .finally(() => { btn.disabled = false; });
});

</script>
<?php if ($flash): ?>
<script>showToast(<?= json_encode($flash['msg']) ?>, <?= json_encode($flash['type'] ?? 'success') ?>);</script>
<?php endif; ?>
</body>
</html>
