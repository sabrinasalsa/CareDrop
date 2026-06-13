<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php'); exit;
}

// Proses POST
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang  = htmlspecialchars(trim($_POST['nama_barang'] ?? ''));
    $kategori     = $_POST['kategori'] ?? 'pakaian';
    $urgensi      = $_POST['urgensi']  ?? 'med';
    $target_butuh = (int)($_POST['target_butuh'] ?? 0);
    $deskripsi    = htmlspecialchars(trim($_POST['deskripsi'] ?? ''));

    if (empty($nama_barang))  $errors[] = 'Nama barang wajib diisi';
    if ($target_butuh < 1)    $errors[] = 'Target jumlah minimal 1';
    if (!in_array($kategori, ['pakaian','buku','elektronik','perabot','makanan','lainnya'])) $errors[] = 'Kategori tidak valid';
    if (!in_array($urgensi, ['high','med','low'])) $errors[] = 'Urgensi tidak valid';

    if (empty($errors)) {
        try {
            $stmt = $koneksi->prepare(
                "INSERT INTO katalog_kebutuhan (yayasan_id, nama_barang, kategori, urgensi, target_butuh, jumlah_terkumpul, deskripsi)
                 VALUES (?, ?, ?, ?, ?, 0, ?)"
            );
            // Coba dengan kolom deskripsi dulu, kalau gagal tanpa deskripsi
            if (!$stmt) {
                $stmt = $koneksi->prepare(
                    "INSERT INTO katalog_kebutuhan (yayasan_id, nama_barang, kategori, urgensi, target_butuh, jumlah_terkumpul)
                     VALUES (?, ?, ?, ?, ?, 0)"
                );
                $stmt->bind_param("isssi", $_SESSION['id'], $nama_barang, $kategori, $urgensi, $target_butuh);
            } else {
                $uid = (int)$_SESSION['id'];
                $stmt->bind_param("isssiss", $uid, $nama_barang, $kategori, $urgensi, $target_butuh, $target_butuh, $deskripsi);
                // Re-prepare yang benar
                $stmt->close();
                $stmt = $koneksi->prepare(
                    "INSERT INTO katalog_kebutuhan (yayasan_id, nama_barang, kategori, urgensi, target_butuh, jumlah_terkumpul)
                     VALUES (?, ?, ?, ?, ?, 0)"
                );
                $uid = (int)$_SESSION['id'];
                $stmt->bind_param("isssi", $uid, $nama_barang, $kategori, $urgensi, $target_butuh);
            }
            $stmt->execute();
            $stmt->close();
            $koneksi->close();
            header('Location: kelola_katalog.php?added=1'); exit;
        } catch (Throwable $e) {
            $errors[] = 'Gagal menyimpan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tambah Kebutuhan – CareDrop</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --g5:#16a34a; --g6:#15803d; --dark:#0f2419;
      --text1:#1a2e22; --text2:#52735e; --text3:#94a39b;
      --radius:12px; --shadow:0 2px 16px rgba(0,0,0,.09);
    }
    body { font-family:'Plus Jakarta Sans',sans-serif; background:#f8fdf9; color:var(--text1); min-height:100vh; }
    header {
      background:var(--dark); color:#fff; padding:14px 32px;
      display:flex; align-items:center; justify-content:space-between;
      position:sticky; top:0; z-index:99;
    }
    .logo { font-size:1.2rem; font-weight:700; color:#4ade80; }
    header nav a { color:#cbd5e1; text-decoration:none; font-size:.875rem; margin-left:20px; }
    header nav a:hover { color:#fff; }
    .container { max-width:640px; margin:0 auto; padding:36px 20px; }
    h1 { font-size:1.4rem; font-weight:700; margin-bottom:4px; }
    .sub { color:var(--text2); font-size:.875rem; margin-bottom:28px; }
    .card { background:#fff; border-radius:var(--radius); padding:28px; box-shadow:var(--shadow); }
    .field { margin-bottom:18px; }
    label { display:block; font-size:.82rem; font-weight:600; color:var(--text2); margin-bottom:6px; letter-spacing:.02em; }
    input[type=text], input[type=number], select, textarea {
      width:100%; padding:11px 14px; border:1.5px solid #d1fae5; border-radius:8px;
      font-family:inherit; font-size:.9rem; color:var(--text1); background:#fff;
      transition:border .2s, box-shadow .2s;
    }
    input:focus, select:focus, textarea:focus {
      outline:none; border-color:var(--g5); box-shadow:0 0 0 3px rgba(22,163,74,.12);
    }
    textarea { min-height:80px; resize:vertical; }
    .row2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .btn {
      display:inline-flex; align-items:center; gap:7px;
      padding:11px 20px; border-radius:8px; font-family:inherit;
      font-size:.9rem; font-weight:600; cursor:pointer; border:none;
      text-decoration:none; transition:all .18s;
    }
    .btn-green { background:var(--g5); color:#fff; }
    .btn-green:hover { background:var(--g6); }
    .btn-ghost { background:#f3f4f6; color:var(--text1); }
    .btn-ghost:hover { background:#e5e7eb; }
    .actions { display:flex; gap:10px; margin-top:8px; }
    .errors { background:#fee2e2; border:1px solid #fecaca; border-radius:8px; padding:12px 16px; margin-bottom:20px; }
    .errors li { font-size:.85rem; color:#dc2626; margin-left:16px; }
    .urgensi-cards { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
    .urg-card {
      border:2px solid #e5e7eb; border-radius:10px; padding:12px 10px;
      text-align:center; cursor:pointer; transition:all .18s;
    }
    .urg-card input { display:none; }
    .urg-card:has(input:checked) { border-color:var(--g5); background:#f0fdf4; }
    .urg-card .ico { font-size:1.5rem; margin-bottom:4px; }
    .urg-card .lbl { font-size:.8rem; font-weight:600; }
    .urg-card .desc { font-size:.72rem; color:var(--text3); margin-top:2px; }
    .urg-card.red:has(input:checked) { border-color:#dc2626; background:#fff1f2; }
    .urg-card.yellow:has(input:checked) { border-color:#d97706; background:#fffbeb; }
    .tip { font-size:.75rem; color:var(--text3); margin-top:4px; }
    @media(max-width:500px) { .row2 { grid-template-columns:1fr; } .urgensi-cards { grid-template-columns:1fr; } }
  </style>
</head>
<body>
<header>
  <span class="logo"><img src="../uploads/icon/daun.png" alt="" style="height: 1.2em; vertical-align: middle; margin-top: -3px; margin-right: 6px;"> CareDrop</span>
  <nav>
    <a href="kelola_katalog.php">← Kembali ke Katalog</a>
    <a href="../backend/logout.php">Keluar</a>
  </nav>
</header>

<div class="container">
  <h1>[+] Tambah Kebutuhan Baru</h1>
  <p class="sub">Yayasan: <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong></p>

  <?php if (!empty($errors)): ?>
  <div class="errors">
    <ul><?php foreach($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" action="">
      <div class="field">
        <label>Nama Barang yang Dibutuhkan *</label>
        <input type="text" name="nama_barang" placeholder="Contoh: Seragam Sekolah SD, Buku Pelajaran Kelas 4"
               value="<?= htmlspecialchars($nama_barang ?? '') ?>" required>
        <p class="tip">Tulis nama yang spesifik agar donatur mudah memahami kebutuhan</p>
      </div>

      <div class="row2">
        <div class="field">
          <label>Kategori *</label>
          <select name="kategori">
            <?php
            $kats = ['pakaian'=>'👕 Pakaian','buku'=>'📚 Buku & Alat Tulis','elektronik'=>'💻 Elektronik','perabot'=>'🛏️ Perabot Rumah','makanan'=>'🍱 Makanan & Sembako','lainnya'=>'📦 Lainnya'];
            foreach ($kats as $val => $lbl):
                $sel = isset($kategori) && $kategori === $val ? 'selected' : '';
            ?>
            <option value="<?= $val ?>" <?= $sel ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Target Jumlah *</label>
          <input type="number" name="target_butuh" min="1" max="9999"
                 value="<?= (int)($target_butuh ?? 1) ?>" placeholder="Contoh: 10" required>
          <p class="tip">Berapa unit yang dibutuhkan?</p>
        </div>
      </div>

      <div class="field">
        <label>Tingkat Urgensi *</label>
        <div class="urgensi-cards">
          <label class="urg-card red">
            <input type="radio" name="urgensi" value="high" <?= (isset($urgensi) && $urgensi==='high') ? 'checked' : '' ?>>
            <div class="ico"><span><img src="uploads/icon/merah.png" alt=""></div>
            <div class="lbl">Urgen</div>
            <div class="desc">Sangat mendesak, dibutuhkan segera</div>
          </label>
          <label class="urg-card yellow">
            <input type="radio" name="urgensi" value="med" <?= (!isset($urgensi) || $urgensi==='med') ? 'checked' : '' ?>>
            <div class="ico"><span><img src="uploads/icon/kuning.png" alt=""></div>
            <div class="lbl">Sedang</div>
            <div class="desc">Dibutuhkan dalam waktu dekat</div>
          </label>
          <label class="urg-card">
            <input type="radio" name="urgensi" value="low" <?= (isset($urgensi) && $urgensi==='low') ? 'checked' : '' ?>>
            <div class="ico"><span><img src="uploads/icon/ijo.png" alt=""></div>
            <div class="lbl">Normal</div>
            <div class="desc">Tidak terlalu mendesak</div>
          </label>
        </div>
      </div>

      <div class="field">
        <label>Deskripsi Tambahan <span style="color:var(--text3);font-weight:400">(opsional)</span></label>
        <textarea name="deskripsi" placeholder="Contoh: Ukuran yang dibutuhkan, kondisi yang diterima, keterangan tambahan..."><?= htmlspecialchars($deskripsi ?? '') ?></textarea>
      </div>

      <div class="actions">
        <button type="submit" class="btn btn-green"><span><img src="uploads/icon/centang.png" alt=""> Simpan Kebutuhan</button>
        <a href="kelola_katalog.php" class="btn btn-ghost">Batal</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
