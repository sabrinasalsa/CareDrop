<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

$koneksi->query("CREATE TABLE IF NOT EXISTS templat_sertifikat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    warna_utama VARCHAR(10) DEFAULT '#16a34a',
    warna_aksen VARCHAR(10) DEFAULT '#14532d',
    teks_header VARCHAR(200) DEFAULT 'SERTIFIKAT DONASI',
    teks_body TEXT,
    logo_file VARCHAR(255),
    aktif TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$cnt = $koneksi->query("SELECT COUNT(*) AS n FROM templat_sertifikat")->fetch_assoc()['n'];
if ($cnt == 0) {
    $koneksi->query("INSERT INTO templat_sertifikat (nama,warna_utama,warna_aksen,teks_header,teks_body,aktif) VALUES
        ('Default Hijau','#16a34a','#14532d','SERTIFIKAT DONASI','Telah berhasil mendonasikan barang kepada yayasan sebagai wujud kepedulian terhadap sesama.',1),
        ('Biru Formal','#2563eb','#1e3a8a','CERTIFICATE OF DONATION','Has successfully donated goods to the foundation as a form of care for others.',0)");
}

$msg=''; $err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['act'] ?? '';
    if ($act==='aktifkan') {
        $id=(int)$_POST['id'];
        $koneksi->query("UPDATE templat_sertifikat SET aktif=0");
        $koneksi->query("UPDATE templat_sertifikat SET aktif=1 WHERE id=$id");
        $msg="Templat berhasil diaktifkan!";
    } elseif ($act==='simpan') {
        $id=(int)$_POST['id'];
        $nama=htmlspecialchars(trim($_POST['nama']??''));
        $warna=htmlspecialchars(trim($_POST['warna_utama']??'#16a34a'));
        $aksen=htmlspecialchars(trim($_POST['warna_aksen']??'#14532d'));
        $header=htmlspecialchars(trim($_POST['teks_header']??''));
        $body=htmlspecialchars(trim($_POST['teks_body']??''));
        $s=$koneksi->prepare("UPDATE templat_sertifikat SET nama=?,warna_utama=?,warna_aksen=?,teks_header=?,teks_body=? WHERE id=?");
        $s->bind_param("sssssi",$nama,$warna,$aksen,$header,$body,$id); $s->execute(); $s->close();
        $msg="Templat berhasil disimpan!";
    } elseif ($act==='upload_logo') {
        $id=(int)$_POST['id'];
        if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error']===0) {
            $ext=strtolower(pathinfo($_FILES['logo']['name'],PATHINFO_EXTENSION));
            if (in_array($ext,['png','jpg','jpeg','svg'])) {
                $dir=dirname(__DIR__).'/uploads/sertifikat/';
                if (!is_dir($dir)) mkdir($dir,0755,true);
                $fname='logo_sertif_'.$id.'.'.$ext;
                move_uploaded_file($_FILES['logo']['tmp_name'],$dir.$fname);
                $s=$koneksi->prepare("UPDATE templat_sertifikat SET logo_file=? WHERE id=?");
                $s->bind_param("si",$fname,$id); $s->execute(); $s->close();
                $msg="Logo berhasil diunggah!";
            } else { $err="Format harus PNG/JPG/SVG"; }
        }
    }
}
$templats=$koneksi->query("SELECT * FROM templat_sertifikat ORDER BY aktif DESC,id")->fetch_all(MYSQLI_ASSOC);
$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kelola E-Sertifikat – CareDrop Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--dark:#0f2419;--g5:#16a34a;--g6:#15803d;--text1:#1a2e22;--text2:#52735e;--text3:#94a39b;--surf:#f8fdf9}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--surf);color:var(--text1)}
header{background:var(--dark);padding:0 28px;height:58px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:9}
.logo{font-size:1.1rem;font-weight:800;color:#4ade80}
header a{color:#cbd5e1;text-decoration:none;font-size:.85rem;margin-left:16px}
header a:hover{color:#fff}
.wrap{max-width:1000px;margin:0 auto;padding:30px 20px}
h1{font-size:1.35rem;font-weight:800;margin-bottom:4px}
.sub{color:var(--text2);font-size:.875rem;margin-bottom:24px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(440px,1fr));gap:20px}
.tcard{background:#fff;border-radius:12px;box-shadow:0 2px 14px rgba(0,0,0,.08);overflow:hidden;border:2px solid transparent;transition:border .2s}
.tcard.aktif{border-color:var(--g5)}
.tcard-head{padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f0fdf4}
.tcard-head h3{font-size:.95rem;font-weight:700}
.tcard-body{padding:20px}
.preview{border-radius:10px;padding:22px 18px;text-align:center;margin-bottom:16px;border:2px solid;position:relative;overflow:hidden}
.preview::before{content:'';position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;opacity:.12;background:currentColor}
.prev-logo{font-size:.8rem;font-weight:700;margin-bottom:8px;letter-spacing:.5px}
.prev-title{font-size:.85rem;font-weight:800;letter-spacing:2px;margin-bottom:10px}
.prev-sub{font-size:.65rem;margin-bottom:4px;opacity:.7}
.prev-nama{font-size:1rem;font-weight:700;font-style:italic;margin-bottom:8px}
.prev-body{font-size:.65rem;line-height:1.5;opacity:.85;margin-bottom:10px}
.prev-footer{display:flex;justify-content:space-around;padding-top:10px;font-size:.65rem;border-top:1px solid;opacity:.5}
.field{margin-bottom:12px}
label{display:block;font-size:.78rem;font-weight:600;color:var(--text2);margin-bottom:4px}
input[type=text],input[type=color],textarea{width:100%;padding:8px 11px;border:1.5px solid #d1fae5;border-radius:7px;font-family:inherit;font-size:.85rem;color:var(--text1)}
input[type=color]{height:36px;padding:2px 4px;cursor:pointer}
textarea{min-height:70px;resize:vertical}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;border-radius:7px;font-family:inherit;font-size:.82rem;font-weight:600;cursor:pointer;border:none;transition:all .15s;text-decoration:none}
.btn-green{background:var(--g5);color:#fff}.btn-green:hover{background:var(--g6)}
.btn-ghost{background:#f3f4f6;color:var(--text1)}.btn-ghost:hover{background:#e5e7eb}
.btn-sm{padding:5px 10px;font-size:.75rem}
.badge-aktif{background:#dcfce7;color:#15803d;padding:3px 9px;border-radius:99px;font-size:.72rem;font-weight:700}
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
  <h1>🏅 Kelola Templat E-Sertifikat</h1>
  <p class="sub">Atur tampilan sertifikat donasi yang diterima donatur</p>
  <?php if($msg): ?><div class="flash flash-ok">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="flash flash-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="grid">
  <?php foreach($templats as $t): ?>
  <div class="tcard <?= $t['aktif'] ? 'aktif' : '' ?>">
    <div class="tcard-head">
      <h3><?= htmlspecialchars($t['nama']) ?> <?= $t['aktif'] ? '<span class="badge-aktif">✅ Aktif</span>' : '' ?></h3>
      <?php if(!$t['aktif']): ?>
      <form method="POST" style="display:inline">
        <input type="hidden" name="act" value="aktifkan">
        <input type="hidden" name="id" value="<?= $t['id'] ?>">
        <button class="btn btn-green btn-sm">Gunakan</button>
      </form>
      <?php endif; ?>
    </div>
    <div class="tcard-body">
      <!-- Preview -->
      <div class="preview" style="background:linear-gradient(135deg,<?= $t['warna_utama'] ?>18,<?= $t['warna_aksen'] ?>12);border-color:<?= $t['warna_utama'] ?>;color:<?= $t['warna_aksen'] ?>">
        <div class="prev-logo" style="color:<?= $t['warna_utama'] ?>">🌿 CareDrop</div>
        <div class="prev-title"><?= htmlspecialchars($t['teks_header']) ?></div>
        <div class="prev-sub">Diberikan kepada:</div>
        <div class="prev-nama">Nama Donatur</div>
        <div class="prev-body"><?= htmlspecialchars(substr($t['teks_body']??'',0,100)) ?>...</div>
        <div class="prev-footer"><span>Tanggal: 16 Mei 2026</span><span>No: CDR-001</span></div>
      </div>
      <!-- Edit form -->
      <form method="POST">
        <input type="hidden" name="act" value="simpan">
        <input type="hidden" name="id" value="<?= $t['id'] ?>">
        <div class="field"><label>Nama Templat</label><input type="text" name="nama" value="<?= htmlspecialchars($t['nama']) ?>"></div>
        <div class="row2">
          <div class="field"><label>Warna Utama</label><input type="color" name="warna_utama" value="<?= $t['warna_utama'] ?>" oninput="updatePreview(this)"></div>
          <div class="field"><label>Warna Aksen</label><input type="color" name="warna_aksen" value="<?= $t['warna_aksen'] ?>"></div>
        </div>
        <div class="field"><label>Teks Header</label><input type="text" name="teks_header" value="<?= htmlspecialchars($t['teks_header']) ?>"></div>
        <div class="field"><label>Teks Isi Sertifikat</label><textarea name="teks_body"><?= htmlspecialchars($t['teks_body']??'') ?></textarea></div>
        <button type="submit" class="btn btn-green btn-sm">💾 Simpan Perubahan</button>
      </form>
      <!-- Upload logo -->
      <form method="POST" enctype="multipart/form-data" style="margin-top:10px">
        <input type="hidden" name="act" value="upload_logo">
        <input type="hidden" name="id" value="<?= $t['id'] ?>">
        <div style="display:flex;gap:8px;align-items:center">
          <input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg" style="font-size:.78rem;flex:1">
          <button type="submit" class="btn btn-ghost btn-sm">⬆ Upload Logo</button>
        </div>
        <?php if(!empty($t['logo_file'])): ?>
        <p style="font-size:.72rem;color:var(--text2);margin-top:4px">✅ Logo: <?= htmlspecialchars($t['logo_file']) ?></p>
        <?php endif; ?>
      </form>
    </div>
  </div>
  <?php endforeach; ?>
  </div>
</div>
</body></html>
