<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

$activePage = 'sertifikat';

$pdo->query("CREATE TABLE IF NOT EXISTS templat_sertifikat (
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
$cnt = $pdo->query("SELECT COUNT(*) AS n FROM templat_sertifikat")->fetch(PDO::FETCH_ASSOC)['n'];
if ($cnt == 0) {
    $pdo->query("INSERT INTO templat_sertifikat (nama,warna_utama,warna_aksen,teks_header,teks_body,aktif) VALUES
        ('Default Hijau','#16a34a','#14532d','SERTIFIKAT DONASI','Telah berhasil mendonasikan barang kepada yayasan sebagai wujud kepedulian terhadap sesama.',1),
        ('Biru Formal','#2563eb','#1e3a8a','CERTIFICATE OF DONATION','Has successfully donated goods to the foundation as a form of care for others.',0)");
}

$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'aktifkan') {
        $id = (int)$_POST['id'];
        $pdo->query("UPDATE templat_sertifikat SET aktif=0");
        $pdo->query("UPDATE templat_sertifikat SET aktif=1 WHERE id=$id");
        $msg = "Templat berhasil diaktifkan!";
    } elseif ($act === 'simpan') {
        $id     = (int)$_POST['id'];
        $nama   = htmlspecialchars(trim($_POST['nama']         ?? ''));
        $warna  = htmlspecialchars(trim($_POST['warna_utama']  ?? '#16a34a'));
        $aksen  = htmlspecialchars(trim($_POST['warna_aksen']  ?? '#14532d'));
        $header = htmlspecialchars(trim($_POST['teks_header']  ?? ''));
        $body   = htmlspecialchars(trim($_POST['teks_body']    ?? ''));
        $s = $pdo->prepare("UPDATE templat_sertifikat SET nama=?,warna_utama=?,warna_aksen=?,teks_header=?,teks_body=? WHERE id=?");
        $s->execute([$nama, $warna, $aksen, $header, $body, $id]);
        $msg = "Templat berhasil disimpan!";
    } elseif ($act === 'upload_logo') {
        $id = (int)$_POST['id'];
        if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg'])) {
                $dir = dirname(__DIR__) . '/uploads/sertifikat/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = 'logo_sertif_' . $id . '.' . $ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $fname);
                $s = $pdo->prepare("UPDATE templat_sertifikat SET logo_file=? WHERE id=?");
                $s->execute([$fname, $id]);
                $msg = "Logo berhasil diunggah!";
            } else { $err = "Format harus PNG, JPG, atau SVG."; }
        } else { $err = "Pilih file terlebih dahulu."; }
    }
}
$templats = $pdo->query("SELECT * FROM templat_sertifikat ORDER BY aktif DESC, id")->fetchAll(PDO::FETCH_ASSOC);
$pdo = null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola E-Sertifikat — CareDrop Admin</title>
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
        .brand-name { font-size: 20px; font-weight: 800; color: var(--mint); letter-spacing: -0.5px; }
        .brand-role { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
        .sidebar-nav { flex: 1; padding: 16px 12px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; color: rgba(255,255,255,0.65); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.18s; margin-bottom: 2px; }
        .nav-item:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .nav-item.active { background: rgba(126,217,163,0.15); color: var(--mint); }
        .nav-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 10px 0; }
        .sidebar-footer { padding: 16px 12px; border-top: 1px solid rgba(255,255,255,0.08); }

        /* MAIN */
        .main { margin-left: 240px; flex: 1; padding: 32px 36px; }
        .page-header { margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; color: var(--forest); letter-spacing: -0.5px; }
        .page-subtitle { font-size: 14px; color: var(--muted); margin-top: 4px; }

        /* FLASH */
        .flash { padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; margin-bottom: 22px; }
        .flash-ok  { background: #f0fdf4; color: var(--moss); border: 1px solid #bbf7d0; }
        .flash-err { background: var(--red-light); color: var(--red); border: 1px solid var(--red-border); }

        /* INFO BANNER */
        .info-banner { display: flex; align-items: flex-start; gap: 12px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px; color: #0369a1; font-size: 13px; line-height: 1.6; }
        .info-banner svg { flex-shrink: 0; margin-top: 1px; }

        /* GRID */
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(460px, 1fr)); gap: 22px; }

        /* TEMPLATE CARD */
        .tcard { background: var(--white); border-radius: 16px; border: 2px solid var(--border); overflow: hidden; box-shadow: 0 1px 6px rgba(12,46,24,0.06); transition: border-color 0.2s; }
        .tcard.aktif { border-color: var(--sage); }
        .tcard-head { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .tcard-head h3 { font-size: 15px; font-weight: 700; color: var(--forest); }
        .tcard-body { padding: 20px; }

        /* SERTIFIKAT PREVIEW */
        .preview {
            border-radius: 12px; padding: 20px 18px;
            text-align: center; margin-bottom: 18px;
            border: 1.5px solid; position: relative; overflow: hidden;
        }
        .prev-logo { font-size: 11px; font-weight: 700; margin-bottom: 8px; letter-spacing: 1px; opacity: 0.7; }
        .prev-title { font-size: 13px; font-weight: 800; letter-spacing: 2px; margin-bottom: 10px; }
        .prev-sub { font-size: 10px; margin-bottom: 3px; opacity: 0.65; }
        .prev-nama { font-size: 15px; font-weight: 700; font-style: italic; margin-bottom: 8px; }
        .prev-body { font-size: 10px; line-height: 1.5; opacity: 0.8; margin-bottom: 10px; }
        .prev-footer { display: flex; justify-content: space-around; padding-top: 10px; font-size: 10px; border-top: 1px solid; opacity: 0.45; }

        /* FORM */
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .field input[type=text], .field textarea {
            width: 100%; padding: 9px 12px; border: 1px solid var(--border);
            border-radius: 8px; font-family: inherit; font-size: 13px; color: var(--ink);
            background: var(--white); outline: none; transition: border-color 0.15s;
        }
        .field input[type=text]:focus, .field textarea:focus { border-color: var(--sage); box-shadow: 0 0 0 3px rgba(74,173,107,0.12); }
        .field input[type=color] { height: 38px; padding: 3px 5px; cursor: pointer; border: 1px solid var(--border); border-radius: 8px; width: 100%; }
        .field textarea { min-height: 72px; resize: vertical; }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        /* UPLOAD LOGO SECTION */
        .upload-section { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); }
        .upload-label { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; }
        .upload-hint { font-size: 11px; color: var(--muted); margin-bottom: 10px; line-height: 1.5; }
        .upload-row { display: flex; gap: 8px; align-items: center; }
        .upload-row input[type=file] { flex: 1; font-size: 12px; font-family: inherit; color: var(--muted); }
        .logo-current { margin-top: 8px; font-size: 12px; color: var(--moss); font-weight: 600; display: flex; align-items: center; gap: 6px; }

        /* BUTTONS */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 9px; font-family: inherit; font-size: 13px; font-weight: 600; cursor: pointer; border: none; transition: all 0.15s; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--moss), var(--sage)); color: #fff; }
        .btn-primary:hover { opacity: 0.88; }
        .btn-outline { background: var(--bg); color: var(--muted); border: 1px solid var(--border); }
        .btn-outline:hover { border-color: var(--sage); color: var(--moss); background: #f0fdf4; }
        .btn-green-sm { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 5px 12px; font-size: 12px; }
        .btn-green-sm:hover { background: #16a34a; color: #fff; }

        /* ACTIVE BADGE */
        .badge-aktif { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

        @media (max-width: 1100px) { .grid { grid-template-columns: 1fr; } }
        @media (max-width: 900px)  { .main { padding: 20px 16px; } }
    </style>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css" />
</head>
<body>

<?php require '_sidebar.php'; ?>

<!-- MAIN -->
<main class="main">
    <div class="page-header">
        <h1 class="page-title">Kelola E-Sertifikat</h1>
        <p class="page-subtitle">Atur tampilan sertifikat donasi yang diterima donatur setelah donasi selesai.</p>
    </div>

    <?php if ($msg): ?><div class="flash flash-ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="flash flash-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="info-banner">
        <i class="ph ph-medal" style="font-size: 1.25em; vertical-align: middle;"></i>
        <div>
            <strong>Upload Logo</strong> — Unggah logo CareDrop atau logo organisasi (PNG/JPG/SVG, maks. 2MB) yang akan tampil di bagian atas sertifikat donasi. Hanya satu templat yang bisa aktif sekaligus.
        </div>
    </div>

    <div class="grid">
        <?php foreach ($templats as $t): ?>
        <div class="tcard <?= $t['aktif'] ? 'aktif' : '' ?>">
            <div class="tcard-head">
                <h3>
                    <?= htmlspecialchars($t['nama']) ?>
                    <?php if ($t['aktif']): ?>
                        <span class="badge-aktif" style="margin-left:8px">Aktif</span>
                    <?php endif; ?>
                </h3>
                <?php if (!$t['aktif']): ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="act" value="aktifkan">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button class="btn btn-green-sm">Gunakan Templat Ini</button>
                </form>
                <?php endif; ?>
            </div>
            <div class="tcard-body">

                <!-- Preview mini sertifikat -->
                <div class="preview" style="background:linear-gradient(135deg,<?= $t['warna_utama'] ?>18,<?= $t['warna_aksen'] ?>12);border-color:<?= $t['warna_utama'] ?>;color:<?= $t['warna_aksen'] ?>">
                    <div class="prev-logo" style="color:<?= $t['warna_utama'] ?>">
                        <?php if (!empty($t['logo_file'])): ?>
                            <img src="../uploads/sertifikat/<?= htmlspecialchars($t['logo_file']) ?>" alt="Logo" style="height:24px;object-fit:contain;">
                        <?php else: ?>
                            CareDrop
                        <?php endif; ?>
                    </div>
                    <div class="prev-title"><?= htmlspecialchars($t['teks_header']) ?></div>
                    <div class="prev-sub">Diberikan kepada:</div>
                    <div class="prev-nama">Nama Donatur</div>
                    <div class="prev-body"><?= htmlspecialchars(substr($t['teks_body'] ?? '', 0, 100)) ?>...</div>
                    <div class="prev-footer"><span>Tanggal: <?= date('d M Y') ?></span><span>No: CDR-XXXXXX</span></div>
                </div>

                <!-- Form edit templat -->
                <form method="POST">
                    <input type="hidden" name="act" value="simpan">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <div class="field">
                        <label>Nama Templat</label>
                        <input type="text" name="nama" value="<?= htmlspecialchars($t['nama']) ?>">
                    </div>
                    <div class="row2">
                        <div class="field">
                            <label>Warna Utama</label>
                            <input type="color" name="warna_utama" value="<?= $t['warna_utama'] ?>">
                        </div>
                        <div class="field">
                            <label>Warna Aksen</label>
                            <input type="color" name="warna_aksen" value="<?= $t['warna_aksen'] ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label>Teks Header</label>
                        <input type="text" name="teks_header" value="<?= htmlspecialchars($t['teks_header']) ?>">
                    </div>
                    <div class="field">
                        <label>Teks Isi Sertifikat</label>
                        <textarea name="teks_body"><?= htmlspecialchars($t['teks_body'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-check-circle" style="font-size: 1.25em; vertical-align: middle;"></i>
                        Simpan Perubahan
                    </button>
                </form>

                <!-- Upload logo -->
                <div class="upload-section">
                    <span class="upload-label">Logo Sertifikat</span>
                    <p class="upload-hint">
                        Upload logo yang tampil di bagian atas sertifikat.<br>
                        Format: PNG, JPG, atau SVG. Ukuran maks. 2MB. Disarankan transparan (PNG).
                    </p>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="act" value="upload_logo">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <div class="upload-row">
                            <input type="file" name="logo" accept=".png,.jpg,.jpeg,.svg">
                            <button type="submit" class="btn btn-outline">
                                <i class="ph ph-download-simple" style="font-size: 1.25em; vertical-align: middle;"></i>
                                Upload Logo
                            </button>
                        </div>
                        <?php if (!empty($t['logo_file'])): ?>
                        <div class="logo-current">
                            <i class="ph ph-check-circle" style="font-size: 1.25em; vertical-align: middle;"></i>
                            Logo aktif: <?= htmlspecialchars($t['logo_file']) ?>
                        </div>
                        <?php else: ?>
                        <p style="font-size:12px;color:var(--muted);margin-top:6px">Belum ada logo yang diunggah.</p>
                        <?php endif; ?>
                    </form>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html>
