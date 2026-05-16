<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php');
    exit;
}

$yayasan_id = (int) $_SESSION['id'];

// Ambil data katalog
$stmt = $koneksi->prepare(
    "SELECT * FROM katalog_kebutuhan WHERE yayasan_id = ? ORDER BY
     FIELD(urgensi,'high','med','low'), id DESC"
);
$stmt->bind_param("i", $yayasan_id);
$stmt->execute();
$result = $stmt->get_result();
$items  = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Katalog – CareDrop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --g1:#f0fdf4; --g2:#dcfce7; --g5:#16a34a; --g6:#15803d;
      --dark:#0f2419; --text1:#1a2e22; --text2:#52735e; --text3:#94a39b;
      --radius:12px; --shadow:0 2px 12px rgba(0,0,0,.08);
    }
    body { font-family:'Plus Jakarta Sans',sans-serif; background:#f8fdf9; color:var(--text1); min-height:100vh; }
    header {
      background:var(--dark); color:#fff; padding:14px 32px;
      display:flex; align-items:center; justify-content:space-between;
      position:sticky; top:0; z-index:99;
    }
    header .logo { font-size:1.25rem; font-weight:700; color:#4ade80; }
    header nav a {
      color:#cbd5e1; text-decoration:none; font-size:.875rem; margin-left:20px;
      transition:color .2s;
    }
    header nav a:hover { color:#fff; }
    .container { max-width:900px; margin:0 auto; padding:32px 20px; }
    h1 { font-size:1.5rem; font-weight:700; margin-bottom:4px; }
    .sub { color:var(--text2); font-size:.875rem; margin-bottom:24px; }
    .top-bar { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
    .btn {
      display:inline-flex; align-items:center; gap:6px;
      padding:10px 18px; border-radius:8px; font-size:.875rem; font-weight:600;
      cursor:pointer; border:none; text-decoration:none; transition:all .2s;
    }
    .btn-green  { background:var(--g5); color:#fff; }
    .btn-green:hover { background:var(--g6); }
    .btn-danger { background:#fee2e2; color:#dc2626; }
    .btn-danger:hover { background:#fecaca; }
    .btn-sm { padding:7px 12px; font-size:.78rem; }
    table { width:100%; border-collapse:collapse; background:#fff; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow); }
    thead th {
      background:var(--dark); color:#fff; text-align:left;
      padding:14px 16px; font-size:.78rem; font-weight:600; letter-spacing:.05em; text-transform:uppercase;
    }
    tbody tr { border-bottom:1px solid #f0fdf4; transition:background .15s; }
    tbody tr:hover { background:#f8fdf9; }
    tbody tr:last-child { border-bottom:none; }
    td { padding:14px 16px; font-size:.875rem; vertical-align:middle; }
    .tag {
      display:inline-block; padding:3px 10px; border-radius:20px;
      font-size:.72rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase;
    }
    .tag-high { background:#fee2e2; color:#dc2626; }
    .tag-med  { background:#fef3c7; color:#92400e; }
    .tag-low  { background:#dcfce7; color:#15803d; }
    .prog-wrap { display:flex; align-items:center; gap:8px; min-width:120px; }
    .prog { flex:1; height:6px; background:#e8f5ea; border-radius:99px; overflow:hidden; }
    .pf   { height:100%; background:var(--g5); border-radius:99px; transition:width .4s; }
    .prog-txt { font-size:.78rem; color:var(--text2); white-space:nowrap; }
    .empty { text-align:center; padding:48px 20px; color:var(--text3); }
    .empty span { font-size:2.5rem; display:block; margin-bottom:12px; }
    .flash {
      padding:12px 16px; border-radius:8px; margin-bottom:20px;
      font-size:.875rem; font-weight:500;
    }
    .flash-ok  { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
    .flash-err { background:#fee2e2; color:#dc2626; border:1px solid #fecaca; }
  </style>
</head>
<body>
<header>
  <span class="logo">🌿 CareDrop</span>
  <nav>
    <a href="../index.php">← Kembali ke Dashboard</a>
    <a href="../backend/logout.php">Keluar</a>
  </nav>
</header>

<div class="container">
  <h1>📋 Kelola Katalog Kebutuhan</h1>
  <p class="sub">Yayasan: <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong></p>

  <?php if (isset($_GET['added'])): ?>
    <div class="flash flash-ok">✅ Kebutuhan berhasil ditambahkan ke katalog!</div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="flash flash-ok">🗑 Kebutuhan berhasil dihapus dari katalog.</div>
  <?php elseif (isset($_GET['err'])): ?>
    <?php
      $errMap = [
        'ada_donasi_aktif' => '⚠️ Tidak bisa dihapus — masih ada donasi aktif untuk item ini.',
        'notfound'         => '❌ Item tidak ditemukan atau bukan milik yayasan Anda.',
        'invalid'          => '❌ ID tidak valid.',
      ];
      $errMsg = $errMap[$_GET['err']] ?? ('❌ Terjadi kesalahan: ' . htmlspecialchars($_GET['err']));
    ?>
    <div class="flash flash-err"><?= $errMsg ?></div>
  <?php endif; ?>

  <div class="top-bar">
    <span style="color:var(--text2);font-size:.875rem"><?= count($items) ?> item kebutuhan</span>
    <a href="tambah_kebutuhan.php" class="btn btn-green">+ Tambah Kebutuhan</a>
  </div>

  <table>
    <thead>
      <tr>
        <th>Barang</th>
        <th>Kategori</th>
        <th>Urgensi</th>
        <th>Progress</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
        <tr>
          <td colspan="5">
            <div class="empty">
              <span>📦</span>
              Belum ada kebutuhan yang diposting.<br>
              <a href="tambah_kebutuhan.php" class="btn btn-green btn-sm" style="margin-top:16px;display:inline-flex">+ Tambah Sekarang</a>
            </div>
          </td>
        </tr>
      <?php else: foreach ($items as $row):
          $pct = $row['target_butuh'] > 0
            ? min(100, round(($row['jumlah_terkumpul'] / $row['target_butuh']) * 100))
            : 0;
          $urgClass = ['high'=>'tag-high','med'=>'tag-med','low'=>'tag-low'][$row['urgensi']] ?? 'tag-low';
          $urgLabel = ['high'=>'Urgen','med'=>'Sedang','low'=>'Terpenuhi'][$row['urgensi']] ?? $row['urgensi'];
          $katIco   = ['pakaian'=>'👕','buku'=>'📚','elektronik'=>'💻','perabot'=>'🛏️'][$row['kategori']] ?? '📦';
      ?>
        <tr>
          <td><strong><?= htmlspecialchars($row['nama_barang']) ?></strong></td>
          <td><?= $katIco ?> <?= ucfirst(htmlspecialchars($row['kategori'])) ?></td>
          <td><span class="tag <?= $urgClass ?>"><?= $urgLabel ?></span></td>
          <td>
            <div class="prog-wrap">
              <div class="prog"><div class="pf" style="width:<?= $pct ?>%"></div></div>
              <span class="prog-txt"><?= $row['jumlah_terkumpul'] ?>/<?= $row['target_butuh'] ?></span>
            </div>
          </td>
          <td>
            <a href="hapus_kebutuhan.php?id=<?= $row['id'] ?>"
               class="btn btn-danger btn-sm"
               onclick="return confirm('Hapus kebutuhan &quot;<?= htmlspecialchars(addslashes($row['nama_barang'])) ?>&quot; dari katalog?')">
              🗑 Hapus
            </a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>
