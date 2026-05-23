<?php
/**
 * CareDrop – katalog.php
 * Halaman publik katalog kebutuhan barang — bisa diakses tanpa login
 */
session_start();
require_once __DIR__ . '/backend/koneksi.php';

$logged_in = isset($_SESSION['id']);
$role      = $_SESSION['role'] ?? '';

$filter_kat = $_GET['kat']   ?? '';
$filter_urg = $_GET['urg']   ?? '';
$search     = htmlspecialchars(trim($_GET['q'] ?? ''));

$where  = "WHERE k.jumlah_terkumpul < k.target_butuh AND (k.aktif = 1 OR k.status_aktif = 1) AND u.status_verifikasi = 'verified'";
$params = [];
$types  = '';

if ($filter_kat && in_array($filter_kat, ['pakaian','buku','elektronik','perabot','lainnya'])) {
    $where   .= " AND k.kategori = ?";
    $params[] = $filter_kat;
    $types   .= 's';
}
if ($filter_urg && in_array($filter_urg, ['high','med','low'])) {
    $where   .= " AND k.urgensi = ?";
    $params[] = $filter_urg;
    $types   .= 's';
}
if ($search) {
    $like     = "%$search%";
    $where   .= " AND (k.nama_barang LIKE ? OR u.nama_lengkap LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

$sql = "SELECT k.id, k.nama_barang, k.kategori, k.urgensi, k.target_butuh, k.jumlah_terkumpul, k.deskripsi,
               u.nama_lengkap AS nama_yayasan, u.alamat AS kota_yayasan, u.id AS yayasan_id
        FROM katalog_kebutuhan k
        JOIN users u ON u.id = k.yayasan_id
        $where
        ORDER BY FIELD(k.urgensi,'high','med','low'), k.id DESC
        LIMIT 60";

$stmt = $koneksi->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats
$totalKatalog = $koneksi->query("SELECT COUNT(*) AS n FROM katalog_kebutuhan WHERE jumlah_terkumpul < target_butuh AND (aktif=1 OR status_aktif=1)")->fetch_assoc()['n'];
$totalYayasan = $koneksi->query("SELECT COUNT(*) AS n FROM users WHERE role='penerima' AND status_verifikasi='verified'")->fetch_assoc()['n'];
$totalSelesai = $koneksi->query("SELECT COUNT(*) AS n FROM donasi WHERE status_donasi='selesai'")->fetch_assoc()['n'];
$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Katalog Kebutuhan – CareDrop</title>
  <meta name="description" content="Temukan kebutuhan barang dari yayasan dan panti asuhan terverifikasi. Donasikan barang layak pakai Anda sekarang.">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --forest:#0c2e18;--pine:#1e5630;--moss:#2d7a44;--sage:#4aad6b;
      --mint:#7ed9a3;--foam:#c9f2dc;--mist:#edfbf3;--amber:#f0c040;
      --ink:#0b1f12;--body:#2c4a35;--muted:#5c7d65;--bg:#f4fbf6;
      --card:#ffffff;--border:#d4e8db;--ff:'Plus Jakarta Sans',system-ui,sans-serif;
    }
    body{font-family:var(--ff);background:var(--bg);color:var(--ink)}
    a{text-decoration:none;color:inherit}

    /* NAVBAR */
    .navbar{background:var(--forest);height:62px;display:flex;align-items:center;padding:0 28px;
      position:sticky;top:0;z-index:100;box-shadow:0 2px 16px rgba(0,0,0,.25)}
    .navbar .logo{font-size:1.15rem;font-weight:800;color:var(--mint);flex:1}
    .navbar .logo span{color:var(--amber)}
    .nav-links{display:flex;align-items:center;gap:8px}
    .nav-links a{color:rgba(201,242,220,.75);font-size:.84rem;font-weight:500;
      padding:7px 14px;border-radius:8px;transition:.18s}
    .nav-links a:hover{color:var(--mint);background:rgba(255,255,255,.06)}
    .btn-login{background:var(--moss)!important;color:#fff!important;font-weight:700!important}
    .btn-login:hover{background:var(--pine)!important}

    /* HERO STRIP */
    .hero-strip{background:linear-gradient(135deg,var(--forest),var(--pine));
      padding:40px 28px;text-align:center;color:#fff}
    .hero-strip h1{font-size:clamp(1.6rem,3vw,2.2rem);font-weight:800;margin-bottom:10px}
    .hero-strip h1 span{color:var(--amber)}
    .hero-strip p{color:rgba(201,242,220,.75);font-size:.95rem;margin-bottom:24px}
    .stats-row{display:flex;justify-content:center;gap:32px;flex-wrap:wrap}
    .stat-pill{background:rgba(255,255,255,.08);border:1px solid rgba(126,217,163,.2);
      border-radius:12px;padding:12px 22px;text-align:center}
    .stat-pill strong{display:block;font-size:1.5rem;font-weight:800;color:var(--amber)}
    .stat-pill span{font-size:.75rem;color:rgba(201,242,220,.7)}

    /* SEARCH & FILTER */
    .filter-bar{max-width:1160px;margin:24px auto;padding:0 20px;display:flex;gap:10px;flex-wrap:wrap}
    .search-wrap{flex:1;min-width:220px;position:relative}
    .search-wrap svg{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted)}
    .search-wrap input{width:100%;padding:10px 12px 10px 40px;border:1.5px solid var(--border);
      border-radius:10px;font-family:var(--ff);font-size:.9rem;outline:none;
      background:#fff;transition:.18s}
    .search-wrap input:focus{border-color:var(--moss);box-shadow:0 0 0 3px rgba(45,122,68,.1)}
    .filter-select{padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;
      font-family:var(--ff);font-size:.85rem;outline:none;background:#fff;cursor:pointer;
      color:var(--ink);transition:.18s}
    .filter-select:focus{border-color:var(--moss)}
    .btn-filter{padding:10px 18px;background:var(--moss);color:#fff;border:none;
      border-radius:10px;font-family:var(--ff);font-weight:700;font-size:.85rem;cursor:pointer;
      transition:.18s}
    .btn-filter:hover{background:var(--pine)}
    .btn-reset{padding:10px 14px;background:transparent;color:var(--muted);border:1.5px solid var(--border);
      border-radius:10px;font-family:var(--ff);font-size:.85rem;cursor:pointer;transition:.18s}
    .btn-reset:hover{border-color:var(--muted);color:var(--body)}

    /* RESULT COUNT */
    .result-info{max-width:1160px;margin:0 auto 16px;padding:0 20px;
      font-size:.82rem;color:var(--muted)}
    .result-info strong{color:var(--body)}

    /* GRID */
    .katalog-grid{max-width:1160px;margin:0 auto;padding:0 20px 48px;
      display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}

    /* CARD */
    .kat-card{background:var(--card);border-radius:16px;border:1px solid var(--border);
      box-shadow:0 1px 6px rgba(0,0,0,.04);overflow:hidden;
      transition:transform .18s,box-shadow .18s;display:flex;flex-direction:column}
    .kat-card:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(45,122,68,.12)}

    .card-header{padding:18px 18px 0;display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
    .card-title{font-size:.95rem;font-weight:700;line-height:1.35;color:var(--ink)}
    .urg-badge{font-size:.65rem;font-weight:700;padding:3px 9px;border-radius:6px;
      text-transform:uppercase;letter-spacing:.5px;flex-shrink:0;white-space:nowrap}
    .urg-high{background:#fee2e2;color:#dc2626}
    .urg-med{background:#fff7ed;color:#c2410c}
    .urg-low{background:#f0fdf4;color:#15803d}

    .card-meta{padding:10px 18px 0;display:flex;align-items:center;gap:6px;
      font-size:.75rem;color:var(--muted)}
    .card-meta svg{flex-shrink:0}
    .card-kat{display:inline-block;margin-top:4px;background:var(--mist);color:var(--moss);
      font-size:.68rem;font-weight:700;padding:2px 8px;border-radius:5px;text-transform:uppercase;letter-spacing:.4px}

    .card-progress{padding:14px 18px 0}
    .prog-bar{height:6px;background:#e8f5ed;border-radius:99px;overflow:hidden}
    .prog-fill{height:100%;background:linear-gradient(90deg,var(--moss),var(--sage));border-radius:99px}
    .prog-label{display:flex;justify-content:space-between;font-size:.72rem;color:var(--muted);margin-top:5px}

    .card-desc{padding:10px 18px 0;font-size:.78rem;color:var(--muted);line-height:1.55;
      display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}

    .card-action{padding:14px 18px 18px;margin-top:auto}
    .btn-donate{width:100%;padding:10px;background:linear-gradient(135deg,var(--moss),var(--sage));
      color:#fff;border:none;border-radius:10px;font-family:var(--ff);
      font-size:.85rem;font-weight:700;cursor:pointer;transition:.18s;
      display:flex;align-items:center;justify-content:center;gap:7px}
    .btn-donate:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(45,122,68,.35)}
    .btn-detail{width:100%;padding:10px;background:transparent;color:var(--moss);
      border:1.5px solid var(--border);border-radius:10px;font-family:var(--ff);
      font-size:.82rem;font-weight:600;cursor:pointer;transition:.18s;margin-top:7px;
      display:flex;align-items:center;justify-content:center;gap:7px}
    .btn-detail:hover{border-color:var(--moss);background:var(--mist)}

    /* EMPTY STATE */
    .empty-state{grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--muted)}
    .empty-icon{width:72px;height:72px;background:var(--mist);border-radius:50%;
      display:flex;align-items:center;justify-content:center;margin:0 auto 16px;
      color:var(--sage)}
    .empty-state h3{font-size:1.05rem;font-weight:700;color:var(--body);margin-bottom:6px}

    /* MODAL */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
      z-index:200;align-items:center;justify-content:center;padding:20px}
    .overlay.open{display:flex}
    .modal{background:#fff;border-radius:20px;width:100%;max-width:480px;
      max-height:90vh;overflow-y:auto;padding:28px;
      box-shadow:0 24px 60px rgba(0,0,0,.2);animation:mIn .25s ease}
    @keyframes mIn{from{opacity:0;transform:scale(.95) translateY(14px)}to{opacity:1;transform:scale(1) translateY(0)}}
    .modal h3{font-size:1.1rem;font-weight:800;margin-bottom:4px}
    .modal .msub{font-size:.82rem;color:var(--muted);margin-bottom:18px}
    .field{margin-bottom:13px}
    .field label{display:block;font-size:.72rem;font-weight:700;color:var(--body);
      text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
    .field input,.field select,.field textarea{width:100%;padding:10px 13px;
      border:1.5px solid var(--border);border-radius:9px;font-family:var(--ff);
      font-size:.88rem;color:var(--ink);background:#fff;outline:none;transition:.18s}
    .field input:focus,.field select:focus,.field textarea:focus{border-color:var(--moss);box-shadow:0 0 0 3px rgba(45,122,68,.1)}
    .modal-footer{display:flex;gap:9px;margin-top:18px}
    .btn-prim{flex:1;padding:11px;background:linear-gradient(135deg,var(--moss),var(--sage));
      color:#fff;border:none;border-radius:10px;font-family:var(--ff);
      font-size:.9rem;font-weight:700;cursor:pointer;transition:.18s}
    .btn-prim:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(45,122,68,.35)}
    .btn-sec{padding:11px 18px;background:transparent;color:var(--muted);
      border:1.5px solid var(--border);border-radius:10px;font-family:var(--ff);
      font-size:.88rem;font-weight:600;cursor:pointer;transition:.18s}
    .btn-sec:hover{border-color:var(--muted)}

    /* TOAST */
    #toast{position:fixed;bottom:22px;right:22px;z-index:300;
      background:var(--forest);color:var(--mint);padding:12px 20px;
      border-radius:12px;font-size:.88rem;font-weight:600;
      box-shadow:0 8px 30px rgba(0,0,0,.25);max-width:320px;
      transform:translateY(80px);opacity:0;transition:.3s}
    #toast.show{transform:translateY(0);opacity:1}
    #toast.err{background:#dc2626;color:#fff}

    /* LOGIN PROMPT */
    .login-prompt{background:var(--mist);border:1px solid var(--foam);border-radius:14px;
      padding:22px;text-align:center;margin-bottom:14px}
    .login-prompt h4{font-size:1rem;font-weight:700;margin-bottom:6px}
    .login-prompt p{font-size:.82rem;color:var(--muted);margin-bottom:14px}
    .btn-login-prom{display:inline-block;padding:10px 24px;background:var(--moss);
      color:#fff;border-radius:10px;font-weight:700;font-size:.88rem;transition:.18s}
    .btn-login-prom:hover{background:var(--pine)}

    @media(max-width:640px){
      .filter-bar{flex-direction:column}
      .katalog-grid{grid-template-columns:1fr}
      .stats-row{gap:14px}
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="index.php" class="logo">Care<span>Drop</span></a>
  <div class="nav-links">
    <a href="katalog.php" style="color:var(--mint)">Katalog</a>
    <a href="index.php#tentang">Tentang</a>
    <?php if ($logged_in): ?>
      <?php if ($role === 'admin'): ?>
        <a href="admin/index.php" class="btn-login">Panel Admin</a>
      <?php elseif ($role === 'penerima'): ?>
        <a href="yayasan/kelola_katalog.php" class="btn-login">Dashboard</a>
      <?php else: ?>
        <a href="dashboard.php" class="btn-login">Dashboard</a>
      <?php endif; ?>
    <?php else: ?>
      <a href="login.php">Masuk</a>
      <a href="login.php?tab=register" class="btn-login">Daftar Gratis</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<div class="hero-strip">
  <h1>Katalog <span>Kebutuhan Barang</span></h1>
  <p>Pilih item kebutuhan dari yayasan terverifikasi dan donasikan barang layak pakai Anda.</p>
  <div class="stats-row">
    <div class="stat-pill"><strong><?= number_format($totalKatalog) ?></strong><span>Item Dibutuhkan</span></div>
    <div class="stat-pill"><strong><?= number_format($totalYayasan) ?></strong><span>Yayasan Aktif</span></div>
    <div class="stat-pill"><strong><?= number_format($totalSelesai) ?></strong><span>Donasi Selesai</span></div>
  </div>
</div>

<!-- FILTER -->
<form method="GET" action="katalog.php">
  <div class="filter-bar">
    <div class="search-wrap">
      <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803z"/></svg>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama barang atau yayasan...">
    </div>
    <select name="kat" class="filter-select">
      <option value="">Semua Kategori</option>
      <option value="pakaian" <?= $filter_kat==='pakaian'?'selected':'' ?>>Pakaian</option>
      <option value="buku"    <?= $filter_kat==='buku'?'selected':'' ?>>Buku</option>
      <option value="elektronik" <?= $filter_kat==='elektronik'?'selected':'' ?>>Elektronik</option>
      <option value="perabot" <?= $filter_kat==='perabot'?'selected':'' ?>>Perabot</option>
      <option value="lainnya" <?= $filter_kat==='lainnya'?'selected':'' ?>>Lainnya</option>
    </select>
    <select name="urg" class="filter-select">
      <option value="">Semua Urgensi</option>
      <option value="high" <?= $filter_urg==='high'?'selected':'' ?>>Mendesak</option>
      <option value="med"  <?= $filter_urg==='med'?'selected':'' ?>>Sedang</option>
      <option value="low"  <?= $filter_urg==='low'?'selected':'' ?>>Normal</option>
    </select>
    <button type="submit" class="btn-filter">Cari</button>
    <?php if ($filter_kat || $filter_urg || $search): ?>
      <a href="katalog.php" class="btn-reset">Reset</a>
    <?php endif; ?>
  </div>
</form>

<div class="result-info">
  Menampilkan <strong><?= count($items) ?></strong> item kebutuhan<?= ($search||$filter_kat||$filter_urg) ? ' (difilter)' : '' ?>
</div>

<!-- GRID -->
<div class="katalog-grid">
  <?php if (empty($items)): ?>
  <div class="empty-state">
    <div class="empty-icon">
      <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
    </div>
    <h3>Tidak ada item ditemukan</h3>
    <p>Coba ubah filter atau kata kunci pencarian.</p>
  </div>
  <?php endif; ?>

  <?php foreach ($items as $k):
    $pct = $k['target_butuh'] > 0 ? min(100, round(($k['jumlah_terkumpul'] / $k['target_butuh']) * 100)) : 0;
    $urgMap = ['high' => ['Mendesak','urg-high'], 'med' => ['Sedang','urg-med'], 'low' => ['Normal','urg-low']];
    [$urgLabel, $urgCls] = $urgMap[$k['urgensi']] ?? ['Normal','urg-low'];
    $katLabel = ['pakaian'=>'Pakaian','buku'=>'Buku','elektronik'=>'Elektronik','perabot'=>'Perabot','lainnya'=>'Lainnya'][$k['kategori']] ?? $k['kategori'];
  ?>
  <div class="kat-card">
    <div class="card-header">
      <div class="card-title"><?= htmlspecialchars($k['nama_barang']) ?></div>
      <span class="urg-badge <?= $urgCls ?>"><?= $urgLabel ?></span>
    </div>
    <div class="card-meta">
      <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>
      <?= htmlspecialchars(mb_substr($k['nama_yayasan'],0,30)) ?>
      <?php if ($k['kota_yayasan']): ?><span style="color:var(--border)">·</span><?= htmlspecialchars(mb_substr($k['kota_yayasan'],0,20)) ?><?php endif; ?>
    </div>
    <div style="padding:4px 18px 0"><span class="card-kat"><?= $katLabel ?></span></div>
    <div class="card-progress">
      <div class="prog-bar"><div class="prog-fill" style="width:<?= $pct ?>%"></div></div>
      <div class="prog-label">
        <span><?= number_format($k['jumlah_terkumpul']) ?> terkumpul</span>
        <span><?= number_format($k['target_butuh']) ?> dibutuhkan</span>
      </div>
    </div>
    <?php if ($k['deskripsi']): ?>
    <div class="card-desc"><?= htmlspecialchars($k['deskripsi']) ?></div>
    <?php endif; ?>
    <div class="card-action">
      <?php if (!$logged_in || $role === 'donatur'): ?>
      <button class="btn-donate" onclick="handleDonate(<?= $k['id'] ?>, '<?= addslashes(htmlspecialchars($k['nama_barang'])) ?>', '<?= addslashes(htmlspecialchars($k['nama_yayasan'])) ?>')">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
        Donasikan Sekarang
      </button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- MODAL DONASI (only shown if logged in as donatur) -->
<?php if ($logged_in && $role === 'donatur'): ?>
<div class="overlay" id="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <h3>Ajukan Tawaran Donasi</h3>
    <p class="msub" id="mSub">Donasikan untuk —</p>
    <form id="donasiForm" enctype="multipart/form-data">
      <input type="hidden" name="katalog_id" id="katalogId">
      <div class="field">
        <label>Jumlah Barang (unit)</label>
        <input type="number" name="qty" min="1" value="1" required>
      </div>
      <div class="field">
        <label>Deskripsi Kondisi Barang</label>
        <textarea name="deskripsi" rows="3" placeholder="Contoh: Pakaian kondisi baik, sudah dicuci, ukuran M-L"></textarea>
      </div>
      <div class="field">
        <label>Foto Barang (opsional)</label>
        <input type="file" name="foto_barang" accept="image/jpeg,image/png,image/webp">
        <p style="font-size:.72rem;color:var(--muted);margin-top:4px">JPG/PNG/WEBP maks 5MB. Foto membantu yayasan menilai kondisi barang.</p>
      </div>
      <div style="background:#f0fdf4;border:1px solid #c8e8d4;border-radius:10px;padding:12px;font-size:.8rem;color:var(--body);margin-bottom:4px">
        <strong>Alur Pengiriman Mandiri:</strong><br>
        Setelah tawaran disetujui, Anda kirim barang sendiri menggunakan ekspedisi pilihan Anda dan memasukkan nomor resi ke sistem.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-sec" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn-prim" id="subBtn">Ajukan Tawaran</button>
      </div>
    </form>
  </div>
</div>
<?php elseif (!$logged_in): ?>
<!-- Login prompt modal -->
<div class="overlay" id="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <h3>Login Diperlukan</h3>
    <p class="msub">Silakan login sebagai donatur untuk mengajukan tawaran donasi.</p>
    <div class="login-prompt">
      <h4>Bergabung dengan CareDrop</h4>
      <p>Daftar gratis sebagai donatur dan mulai berbagi kebaikan hari ini.</p>
      <a href="login.php" class="btn-login-prom">Masuk Sekarang</a>
      &nbsp;
      <a href="login.php?tab=register&role=donatur" style="font-size:.84rem;color:var(--moss);font-weight:600">Daftar Gratis</a>
    </div>
    <button type="button" class="btn-sec" onclick="closeModal()" style="width:100%">Tutup</button>
  </div>
</div>
<?php endif; ?>

<div id="toast"></div>

<script>
function handleDonate(id, nama, yayasan) {
  <?php if ($logged_in && $role === 'donatur'): ?>
  document.getElementById('katalogId').value = id;
  document.getElementById('mSub').textContent = `Untuk: "${nama}" dari ${yayasan}`;
  <?php endif; ?>
  document.getElementById('overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal() {
  document.getElementById('overlay').classList.remove('open');
  document.body.style.overflow = '';
  <?php if ($logged_in && $role === 'donatur'): ?>
  document.getElementById('donasiForm')?.reset();
  <?php endif; ?>
}
function showToast(msg, err=false) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = err ? 'err' : '';
  void t.offsetWidth; t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 4200);
}
<?php if ($logged_in && $role === 'donatur'): ?>
document.getElementById('donasiForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('subBtn');
  btn.disabled = true; btn.textContent = 'Mengirim...';
  try {
    const fd = new FormData(this);
    const res = await fetch('backend/proses_donasi.php', {method:'POST',body:fd});
    const data = await res.json();
    if (data.ok) {
      closeModal();
      showToast('Tawaran berhasil diajukan! Menunggu persetujuan yayasan.');
    } else {
      showToast(data.error || 'Gagal mengajukan tawaran', true);
    }
  } catch(err) { showToast('Koneksi error: ' + err.message, true); }
  btn.disabled = false; btn.textContent = 'Ajukan Tawaran';
});
<?php endif; ?>
</script>
</body>
</html>
