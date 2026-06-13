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
$activePage = 'kategori';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Master Kategori — CareDrop Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Assets/admin.css">
<style>
    .form-card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-bottom: 24px; }
    .form-card h2 { font-size: 15px; font-weight: 700; color: var(--forest); margin-bottom: 16px; }
    .form-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
    .field { display: flex; flex-direction: column; gap: 5px; }
    .field label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; }
    .field input { padding: 9px 12px; border: 1.5px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 13px; color: var(--ink); outline: none; transition: border-color .15s; background: var(--bg); }
    .field input:focus { border-color: var(--sage); background: var(--white); }
    .btn-submit { padding: 9px 18px; background: linear-gradient(135deg,var(--moss),var(--sage)); color: #fff; border: none; border-radius: 9px; font-family: inherit; font-size: 13px; font-weight: 700; cursor: pointer; transition: opacity .15s; }
    .btn-submit:hover { opacity: .88; }
    .badge-on  { background: #f0fdf4; color: #16a34a; padding: 3px 9px; border-radius: 99px; font-size: 11px; font-weight: 700; }
    .badge-off { background: #f3f4f6; color: var(--muted); padding: 3px 9px; border-radius: 99px; font-size: 11px; font-weight: 700; }
    code { background: #f0fdf4; padding: 2px 7px; border-radius: 4px; font-size: 12px; color: var(--moss); }
</style>
</head>
<body>
<?php require '_sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <h1 class="page-title">Master Kategori Barang</h1>
        <p class="page-subtitle">Kelola kategori barang yang tersedia di sistem CareDrop.</p>
    </div>

    <?php if($msg): ?><div class="flash flash-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if($err): ?><div class="flash flash-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

    <!-- Form tambah -->
    <div class="form-card">
        <h2>➕ Tambah Kategori Baru</h2>
        <form method="POST">
            <input type="hidden" name="act" value="tambah">
            <div class="form-row">
                <div class="field">
                    <label>Kode (huruf kecil, tanpa spasi)</label>
                    <input name="kode" placeholder="contoh: mainan" required style="width:160px">
                </div>
                <div class="field">
                    <label>Nama Kategori</label>
                    <input name="nama" placeholder="Mainan Anak" required style="width:220px">
                </div>
                <div class="field">
                    <label>Icon Emoji</label>
                    <input name="icon" placeholder="🧸" value="📦" style="width:70px;text-align:center">
                </div>
                <button type="submit" class="btn-submit">+ Tambah</button>
            </div>
        </form>
    </div>

    <!-- Tabel kategori -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">📋 Daftar Kategori (<?= count($kategori) ?>)</span>
        </div>
        <table>
            <thead>
                <tr><th>Icon</th><th>Kode</th><th>Nama</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php foreach($kategori as $k): ?>
                <tr>
                    <td style="font-size:1.4rem"><?= htmlspecialchars($k['icon']) ?></td>
                    <td><code><?= htmlspecialchars($k['kode']) ?></code></td>
                    <td><strong><?= htmlspecialchars($k['nama']) ?></strong></td>
                    <td><span class="<?= $k['aktif'] ? 'badge-on' : 'badge-off' ?>"><?= $k['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="act" value="toggle">
                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                <button class="btn btn-ghost"><?= $k['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                            </form>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus kategori ini?')">
                                <input type="hidden" name="act" value="hapus">
                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                <button class="btn btn-red">🗑 Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($kategori)): ?>
                <tr><td colspan="5" class="empty-cell">Belum ada kategori.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
