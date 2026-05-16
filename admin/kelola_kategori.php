<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

// Buat tabel jika belum ada
$koneksi->query("CREATE TABLE IF NOT EXISTS master_kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    icon VARCHAR(10) DEFAULT '📦',
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
// Seed default jika kosong
$cnt = $koneksi->query("SELECT COUNT(*) AS n FROM master_kategori")->fetch_assoc()['n'];
if ($cnt == 0) {
    $koneksi->query("INSERT INTO master_kategori (kode,nama,icon) VALUES
        ('pakaian','Pakaian & Sandang','👕'),
        ('buku','Buku & Alat Tulis','📚'),
        ('elektronik','Elektronik','💻'),
        ('perabot','Perabot Rumah','🛏️'),
        ('makanan','Makanan & Sembako','🍱'),
        ('lainnya','Lainnya','📦')");
}

// Handle POST aksi
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'tambah') {
        $kode = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['kode'] ?? '')));
        $nama = htmlspecialchars(trim($_POST['nama'] ?? ''));
        $icon = htmlspecialchars(trim($_POST['icon'] ?? '📦'));
        if ($kode && $nama) {
            try {
                $s = $koneksi->prepare("INSERT INTO master_kategori (kode,nama,icon) VALUES (?,?,?)");
                $s->bind_param("sss",$kode,$nama,$icon); $s->execute(); $s->close();
                $msg = "Kategori \"$nama\" berhasil ditambahkan!";
            } catch(Throwable $e) { $err = "Gagal: " . $e->getMessage(); }
        } else { $err = "Kode dan nama wajib diisi"; }
    } elseif ($act === 'toggle') {
        $id = (int)$_POST['id'];
        $koneksi->query("UPDATE master_kategori SET aktif = NOT aktif WHERE id=$id");
        $msg = "Status kategori diperbarui";
    } elseif ($act === 'hapus') {
        $id = (int)$_POST['id'];
        $koneksi->query("DELETE FROM master_kategori WHERE id=$id");
        $msg = "Kategori dihapus";
    }
}

$kategori = $koneksi->query("SELECT * FROM master_kategori ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Master Kategori – CareDrop Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--dark:#0f2419;--g5:#16a34a;--g6:#15803d;--text1:#1a2e22;--text2:#52735e;--text3:#94a39b;--surf:#f8fdf9;--shadow:0 2px 14px rgba(0,0,0,.08)}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--surf);color:var(--text1)}
header{background:var(--dark);padding:0 28px;height:58px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:9}
.logo{font-size:1.1rem;font-weight:800;color:#4ade80}
header a{color:#cbd5e1;text-decoration:none;font-size:.85rem;margin-left:16px}
header a:hover{color:#fff}
.wrap{max-width:860px;margin:0 auto;padding:30px 20px}
h1{font-size:1.35rem;font-weight:800;margin-bottom:4px}
.sub{color:var(--text2);font-size:.875rem;margin-bottom:24px}
.card{background:#fff;border-radius:12px;box-shadow:var(--shadow);padding:24px;margin-bottom:22px}
.card h2{font-size:.95rem;font-weight:700;margin-bottom:16px}
.row{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
label{display:block;font-size:.78rem;font-weight:600;color:var(--text2);margin-bottom:5px}
input,select{padding:9px 12px;border:1.5px solid #d1fae5;border-radius:7px;font-family:inherit;font-size:.875rem;color:var(--text1)}
input:focus,select:focus{outline:none;border-color:var(--g5)}
.btn{display:inline-flex;align-items:center;gap:5px;padding:9px 16px;border-radius:7px;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;border:none;transition:all .15s}
.btn-green{background:var(--g5);color:#fff}.btn-green:hover{background:var(--g6)}
.btn-ghost{background:#f3f4f6;color:var(--text1)}.btn-ghost:hover{background:#e5e7eb}
.btn-red{background:#fee2e2;color:#dc2626}.btn-red:hover{background:#fecaca}
.btn-sm{padding:5px 10px;font-size:.77rem}
table{width:100%;border-collapse:collapse}
thead th{background:#f8fdf9;padding:10px 14px;text-align:left;font-size:.72rem;font-weight:700;text-transform:uppercase;color:var(--text2);border-bottom:1px solid #e8f5ea;letter-spacing:.05em}
tbody tr{border-bottom:1px solid #f8fdf9;transition:background .12s}
tbody tr:hover{background:#f8fdf9}
td{padding:11px 14px;font-size:.875rem;vertical-align:middle}
.badge-on{background:#dcfce7;color:#15803d;padding:3px 9px;border-radius:99px;font-size:.72rem;font-weight:700}
.badge-off{background:#f3f4f6;color:var(--text3);padding:3px 9px;border-radius:99px;font-size:.72rem;font-weight:700}
.flash{padding:11px 16px;border-radius:8px;margin-bottom:18px;font-size:.875rem;font-weight:500}
.flash-ok{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.flash-err{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
</style>
</head>
<body>
<header>
  <span class="logo">🌿 CareDrop Admin</span>
  <nav><a href="index.php">← Panel Admin</a><a href="../backend/logout.php">Keluar</a></nav>
</header>
<div class="wrap">
  <h1>🏷️ Master Data Kategori Barang</h1>
  <p class="sub">Kelola kategori barang yang tersedia di sistem CareDrop</p>
  <?php if($msg): ?><div class="flash flash-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="flash flash-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="card">
    <h2>➕ Tambah Kategori Baru</h2>
    <form method="POST">
      <input type="hidden" name="act" value="tambah">
      <div class="row">
        <div><label>Kode (huruf kecil, tanpa spasi)</label><input name="kode" placeholder="contoh: mainan" required style="width:160px"></div>
        <div><label>Nama Kategori</label><input name="nama" placeholder="Mainan Anak" required style="width:200px"></div>
        <div><label>Icon Emoji</label><input name="icon" placeholder="🧸" value="📦" style="width:70px;text-align:center"></div>
        <div style="padding-bottom:1px"><button type="submit" class="btn btn-green">+ Tambah</button></div>
      </div>
    </form>
  </div>

  <div class="card">
    <h2>📋 Daftar Kategori (<?= count($kategori) ?>)</h2>
    <table>
      <thead><tr><th>Icon</th><th>Kode</th><th>Nama</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach($kategori as $k): ?>
        <tr>
          <td style="font-size:1.3rem"><?= htmlspecialchars($k['icon']) ?></td>
          <td><code style="background:#f0fdf4;padding:2px 7px;border-radius:4px;font-size:.8rem"><?= htmlspecialchars($k['kode']) ?></code></td>
          <td><?= htmlspecialchars($k['nama']) ?></td>
          <td><span class="<?= $k['aktif'] ? 'badge-on' : 'badge-off' ?>"><?= $k['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
          <td style="display:flex;gap:5px">
            <form method="POST" style="display:inline"><input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?= $k['id'] ?>">
              <button class="btn btn-ghost btn-sm"><?= $k['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus kategori ini?')"><input type="hidden" name="act" value="hapus"><input type="hidden" name="id" value="<?= $k['id'] ?>">
              <button class="btn btn-red btn-sm">🗑</button></form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body></html>
