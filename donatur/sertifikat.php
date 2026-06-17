<?php
session_start();
if (!isset($_SESSION['id'])) { header('Location: ../login.php'); exit; }

require_once dirname(__DIR__) . '/backend/koneksi.php';

$donasi_id = trim($_GET['id'] ?? '');
if (empty($donasi_id)) { header('Location: dashboard.php?tab=sertifikat'); exit; }

$user_id = (int)$_SESSION['id'];

// Ambil data donasi + barang + yayasan
$stmt = $pdo->prepare(
    "SELECT d.id AS donasi_id, d.qty_donasi, d.updated_at AS tgl_selesai, d.status_donasi,
            d.donatur_id,
            COALESCE(k.nama_barang, '—') AS nama_barang,
            COALESCE(k.kategori, '—')   AS kategori,
            COALESCE(u_y.nama_lengkap, '—') AS nama_yayasan,
            COALESCE(u_y.alamat, '')    AS alamat_yayasan,
            COALESCE(u_d.nama_lengkap, '—') AS nama_donatur
     FROM donasi d
     LEFT JOIN katalog_kebutuhan k ON k.id = d.katalog_id
     LEFT JOIN users u_y ON u_y.id = k.yayasan_id
     LEFT JOIN users u_d ON u_d.id = d.donatur_id
     WHERE d.id = ? AND d.donatur_id = ? AND d.status_donasi = 'selesai'
     LIMIT 1"
);
$stmt->execute([$donasi_id, $user_id]);
$data = $stmt->fetch();
$pdo  = null;

if (!$data) {
    // Coba cek tanpa filter donatur (untuk admin) atau tampilkan error
    header('Location: dashboard.php?tab=sertifikat');
    exit;
}

$tgl_formatted = date('d F Y', strtotime($data['tgl_selesai']));
$tgl_en        = date('d M Y', strtotime($data['tgl_selesai']));
$cert_no       = 'CERT-' . strtoupper($data['donasi_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>E-Sertifikat Donasi – CareDrop</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --forest:#0c2e18;--pine:#1e5630;--moss:#2d7a44;--sage:#4aad6b;
      --mint:#7ed9a3;--amber:#f0c040;--ink:#0b1f12;--muted:#5c7d65;
      --bg:#f4fbf6;--border:#d4e8db;
    }
    body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:32px 16px}

    /* TOP BAR */
    .topbar{width:100%;max-width:820px;display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
    .topbar-brand{font-size:1.2rem;font-weight:800;color:var(--forest);text-decoration:none}
    .topbar-brand span{color:var(--moss)}
    .topbar-actions{display:flex;gap:10px}
    .btn-back{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:10px;border:1.5px solid var(--border);background:#fff;font-family:inherit;font-size:.85rem;font-weight:600;color:var(--muted);cursor:pointer;text-decoration:none;transition:.18s}
    .btn-back:hover{border-color:var(--moss);color:var(--moss)}
    .btn-print{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:10px;background:linear-gradient(135deg,var(--moss),var(--sage));border:none;font-family:inherit;font-size:.85rem;font-weight:700;color:#fff;cursor:pointer;transition:.18s}
    .btn-print:hover{opacity:.88;transform:translateY(-1px)}

    /* CERTIFICATE CARD */
    .cert-wrap{width:100%;max-width:820px}
    .cert-card{
      background:#fff;border-radius:20px;
      border:3px solid transparent;
      background-clip:padding-box;
      box-shadow:0 8px 48px rgba(12,46,24,.12);
      overflow:hidden;position:relative;
    }

    /* Decorative border gradient */
    .cert-card::before{
      content:'';position:absolute;inset:-3px;border-radius:23px;
      background:linear-gradient(135deg,var(--amber),var(--sage),var(--forest),var(--amber));
      z-index:-1;
    }

    /* Header */
    .cert-header{
      background:linear-gradient(135deg,var(--forest) 0%,var(--pine) 60%,#1a6635 100%);
      padding:36px 48px 28px;text-align:center;position:relative;overflow:hidden;
    }
    .cert-header::before{
      content:'';position:absolute;width:320px;height:320px;border-radius:50%;
      background:rgba(126,217,163,.06);right:-80px;top:-80px;
    }
    .cert-header::after{
      content:'';position:absolute;width:240px;height:240px;border-radius:50%;
      background:rgba(240,192,64,.05);left:-60px;bottom:-60px;
    }
    .cert-badge{
      display:inline-flex;align-items:center;gap:8px;
      background:rgba(240,192,64,.15);border:1.5px solid rgba(240,192,64,.4);
      border-radius:30px;padding:6px 16px;margin-bottom:16px;position:relative;
    }
    .cert-badge span{font-size:.72rem;font-weight:700;color:var(--amber);text-transform:uppercase;letter-spacing:.8px}
    .cert-logo{font-size:1.5rem;font-weight:800;color:var(--mint);margin-bottom:8px;position:relative}
    .cert-logo em{color:var(--amber);font-style:normal}
    .cert-title{font-family:'Playfair Display',Georgia,serif;font-size:2rem;font-weight:700;color:#fff;line-height:1.2;margin-bottom:6px;position:relative}
    .cert-subtitle{font-size:.88rem;color:rgba(201,242,220,.6);position:relative}

    /* Body */
    .cert-body{padding:40px 48px}
    .cert-intro{text-align:center;margin-bottom:32px}
    .cert-intro p{font-size:.9rem;color:var(--muted);margin-bottom:6px}
    .cert-name{font-family:'Playfair Display',Georgia,serif;font-size:2.4rem;font-weight:700;color:var(--forest);border-bottom:3px solid var(--amber);display:inline-block;padding-bottom:4px;margin-top:4px;margin-bottom:4px}
    .cert-role{font-size:.85rem;color:var(--muted)}

    /* Info Grid */
    .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:28px 0}
    .info-item{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:16px 18px}
    .info-item label{display:block;font-size:.65rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
    .info-item strong{font-size:.95rem;font-weight:700;color:var(--ink);display:block}
    .info-item.highlight{background:linear-gradient(135deg,#f0fdf4,#e8f8ef);border-color:#b6e7c9}
    .info-item.highlight strong{color:var(--moss);font-size:1.1rem}

    /* Appreciation text */
    .cert-appreciation{
      text-align:center;background:linear-gradient(135deg,#fffbeb,#fefce8);
      border:1px solid #fde68a;border-radius:14px;padding:22px 32px;margin:24px 0;
    }
    .cert-appreciation p{font-size:.9rem;color:#92400e;line-height:1.7}
    .cert-appreciation strong{color:var(--forest)}

    /* Footer */
    .cert-footer{
      display:flex;justify-content:space-between;align-items:flex-end;
      border-top:1px dashed var(--border);padding-top:28px;margin-top:8px;
    }
    .cert-no{font-size:.72rem;color:var(--muted);font-family:monospace}
    .cert-no strong{display:block;font-size:.78rem;color:var(--ink);margin-bottom:2px}
    .cert-sign{text-align:center}
    .cert-sign .sign-line{width:140px;height:2px;background:var(--border);margin:40px auto 6px}
    .cert-sign p{font-size:.8rem;font-weight:700;color:var(--forest)}
    .cert-sign span{font-size:.72rem;color:var(--muted)}
    .cert-watermark{
      position:absolute;bottom:30px;left:50%;transform:translateX(-50%);
      font-family:'Playfair Display',serif;font-size:6rem;font-weight:700;
      color:rgba(45,122,68,.04);pointer-events:none;white-space:nowrap;letter-spacing:4px;
    }

    /* Ornament lines */
    .ornament{text-align:center;margin:6px 0;color:var(--amber);font-size:1.1rem;letter-spacing:8px;opacity:.6}

    @media print{
      body{background:#fff;padding:0}
      .topbar{display:none}
      .cert-card{box-shadow:none}
    }

    /* Paksa warna background muncul saat print/PDF */
    *{
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      color-adjust: exact !important;
    }
    @media(max-width:600px){
      .cert-header{padding:28px 24px 22px}
      .cert-body{padding:28px 24px}
      .info-grid{grid-template-columns:1fr}
      .cert-footer{flex-direction:column;gap:24px;align-items:center;text-align:center}
      .cert-name{font-size:1.8rem}
      .cert-title{font-size:1.5rem}
    }
  </style>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css" />
</head>
<body>

<!-- TOP BAR -->
<div class="topbar">
  <a href="dashboard.php" class="topbar-brand">Care<span>Drop</span></a>
  <div class="topbar-actions">
    <a href="dashboard.php?tab=sertifikat" class="btn-back">
      <i class="ph ph-arrow-left" style="font-size: 1.25em; vertical-align: middle;"></i>
      Kembali
    </a>
    <button class="btn-print" onclick="window.print()">
      <i class="ph ph-hand-coins" style="font-size: 1.25em; vertical-align: middle;"></i>
      Cetak / Simpan PDF
    </button>
  </div>
</div>

<!-- CERTIFICATE -->
<div class="cert-wrap">
  <div class="cert-card">
    <!-- Decorative Watermark -->
    <div class="cert-watermark">CareDrop</div>

    <!-- Header -->
    <div class="cert-header">
      <div class="cert-badge">
        <i class="ph ph-map-pin" style="font-size: 1.25em; vertical-align: middle;"></i>
        <span>E-Sertifikat Resmi</span>
      </div>
      <div class="cert-logo">Care<em>Drop</em></div>
      <div class="cert-title">Sertifikat Apresiasi<br><em style="font-style:italic;font-size:1.6rem">Donasi Barang</em></div>
      <div class="cert-subtitle">Platform Donasi Barang Terverifikasi</div>
    </div>

    <!-- Body -->
    <div class="cert-body">
      <div class="cert-intro">
        <p>Dengan bangga diberikan kepada</p>
        <div class="cert-name"><?= htmlspecialchars($data['nama_donatur']) ?></div>
        <p class="cert-role">Sebagai Donatur CareDrop</p>
      </div>

      <div class="ornament">✦ &nbsp; ✦ &nbsp; ✦</div>

      <div class="info-grid">
        <div class="info-item highlight">
          <label>Barang Didonasikan</label>
          <strong><?= htmlspecialchars($data['nama_barang']) ?></strong>
        </div>
        <div class="info-item highlight">
          <label>Jumlah</label>
          <strong><?= number_format($data['qty_donasi']) ?> Unit</strong>
        </div>
        <div class="info-item">
          <label>Diserahkan Kepada</label>
          <strong><?= htmlspecialchars($data['nama_yayasan']) ?></strong>
        </div>
        <div class="info-item">
          <label>Kategori Barang</label>
          <strong><?= htmlspecialchars($data['kategori']) ?></strong>
        </div>
        <div class="info-item">
          <label>Tanggal Diselesaikan</label>
          <strong><?= $tgl_formatted ?></strong>
        </div>
        <div class="info-item">
          <label>ID Donasi</label>
          <strong style="font-family:monospace;font-size:.82rem"><?= htmlspecialchars($data['donasi_id']) ?></strong>
        </div>
      </div>

      <div class="cert-appreciation">
        <p>
          Atas kepercayaan dan kebaikan hati <strong><?= htmlspecialchars($data['nama_donatur']) ?></strong>
          dalam mendonasikan <strong><?= number_format($data['qty_donasi']) ?> unit <?= htmlspecialchars($data['nama_barang']) ?></strong>
          kepada <strong><?= htmlspecialchars($data['nama_yayasan']) ?></strong>,
          kami menyampaikan penghargaan yang setinggi-tingginya.
          Semoga kebaikan ini menjadi berkah dan inspirasi bagi banyak orang.
        </p>
      </div>

      <div class="ornament">✦ &nbsp; ✦ &nbsp; ✦</div>

      <!-- Footer -->
      <div class="cert-footer">
        <div class="cert-no">
          <strong>Nomor Sertifikat</strong>
          <?= htmlspecialchars($cert_no) ?>
          <span style="display:block;margin-top:4px">Diterbitkan: <?= $tgl_formatted ?></span>
        </div>
        <div class="cert-sign">
          <div class="sign-line"></div>
          <p>Tim CareDrop</p>
          <span>Platform Donasi Barang Terverifikasi</span>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
