<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php'); exit;
}
$yayasan_id  = (int)$_SESSION['id'];
$nama_yayasan = htmlspecialchars($_SESSION['nama'] ?? 'Yayasan');

// Query badge
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
} catch (Exception $e) { }

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak Pengiriman — CareDrop Yayasan</title>
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

        /* ── SIDEBAR ── */
        .sidebar { width: 240px; min-height: 100vh; background: var(--forest); position: fixed; top: 0; left: 0; z-index: 100; display: flex; flex-direction: column; }
        .sidebar-brand { padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-brand .brand-name { font-size: 20px; font-weight: 800; color: var(--mint); letter-spacing: -0.5px; }
        .sidebar-brand .brand-role { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
        .sidebar-nav { flex: 1; padding: 16px 12px; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; color: rgba(255,255,255,0.65); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.18s ease; margin-bottom: 2px; position: relative; }
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

        /* ── MAIN ── */
        .main { margin-left: 240px; flex: 1; min-height: 100vh; padding: 32px 36px; }
        .page-header { margin-bottom: 28px; }
        .page-title { font-size: 26px; font-weight: 800; color: var(--forest); letter-spacing: -0.5px; }
        .page-subtitle { font-size: 14px; color: var(--muted); margin-top: 4px; }
        .card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: 0 1px 4px rgba(12,46,24,0.05); }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .card-header h3 { font-size: 15px; font-weight: 700; color: var(--forest); }

        /* Lacak styles */
        .lacak-form{background:var(--white);border-radius:14px;border:1px solid var(--border);padding:24px;margin-bottom:20px;box-shadow: 0 1px 4px rgba(12,46,24,0.05);}
        .lacak-form h3{font-size:15px;font-weight:700;margin-bottom:14px;color:var(--forest)}
        .lacak-input-row{display:flex;gap:10px}
        .lacak-input-row input{flex:1;padding:10px 14px;border:1.5px solid var(--border);border-radius:9px;font-family:var(--ff);font-size:14px;outline:none;transition:.18s}
        .lacak-input-row input:focus{border-color:var(--moss)}
        .btn-lacak{padding:10px 20px;background:var(--moss);color:#fff;border:none;border-radius:9px;font-family:var(--ff);font-weight:700;font-size:14px;cursor:pointer;transition:.18s;white-space:nowrap}
        .btn-lacak:hover{background:var(--pine)}
        .timeline{list-style:none;padding:0;position:relative}
        .timeline::before{content:'';position:absolute;left:15px;top:0;bottom:0;width:2px;background:var(--border)}
        .timeline li{position:relative;padding:0 0 20px 44px}
        .tl-dot{position:absolute;left:0;top:0;width:32px;height:32px;border-radius:50%;border:2px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;z-index:1}
        .tl-dot.done{background:var(--moss);border-color:var(--moss);color:#fff}
        .tl-dot.active{background:var(--amber);border-color:var(--amber);color:var(--forest)}
        .tl-label{font-size:14px;font-weight:700;color:var(--ink);margin-bottom:2px}
        .tl-desc{font-size:12px;color:var(--muted)}
        
        #toast{position:fixed;bottom:22px;right:22px;z-index:300;background:var(--forest);color:var(--mint);padding:12px 20px;border-radius:12px;font-size:14px;font-weight:600;box-shadow:0 8px 30px rgba(0,0,0,.25);max-width:320px;transform:translateY(80px);opacity:0;transition:.3s}
        #toast.show{transform:translateY(0);opacity:1}
        #toast.err{background:#dc2626;color:#fff}
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
        <a href="lacak_pengiriman.php" class="nav-item active">
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

<main class="main">
    <div class="page-header">
        <h1 class="page-title">Lacak Pengiriman</h1>
        <p class="page-subtitle">Lacak status pengiriman barang donasi menggunakan nomor resi dari donatur.</p>
    </div>

    <div class="lacak-form">
      <h3>Lacak Status Pengiriman</h3>
      <p style="font-size:13px;color:var(--muted);margin-bottom:14px">Masukkan nomor resi untuk melihat status pengiriman barang donasi yang dikirimkan kepada Anda.</p>
      <div class="lacak-input-row">
        <input type="text" id="resiInput" placeholder="Contoh: JNE1234567890" value="<?=htmlspecialchars($_GET['resi']??'')?>">
        <button class="btn-lacak" onclick="lacakResi()">
          <i class="ph ph-magnifying-glass" style="font-size: 1.25em; vertical-align: middle;"></i>
          Lacak
        </button>
      </div>
    </div>
    
    <div id="lacakResult" style="display:none">
      <div class="card">
        <div class="card-header"><h3 id="lacakTitle">Informasi Pengiriman</h3></div>
        <div style="padding:20px">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px" id="lacakInfo"></div>
          <h4 style="font-size:14px;font-weight:700;margin-bottom:14px;color:var(--forest)">Timeline Status</h4>
          <ul class="timeline" id="lacakTimeline"></ul>
        </div>
      </div>
    </div>
    
    <div id="lacakEmpty" style="display:none;text-align:center;padding:40px;color:var(--muted)">
      <i class="ph ph-truck" style="font-size: 1.25em; vertical-align: middle;"></i>
      <p id="lacakErrMsg">Nomor resi tidak ditemukan di sistem.</p>
    </div>
</main>
<div id="toast"></div>

<script>
function showToast(msg, isErr=false) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  if(isErr) t.classList.add('err'); else t.classList.remove('err');
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

async function lacakResi() {
  const resi = document.getElementById('resiInput').value.trim();
  if (!resi) { showToast('Masukkan nomor resi terlebih dahulu', true); return; }
  document.getElementById('lacakResult').style.display = 'none';
  document.getElementById('lacakEmpty').style.display = 'none';
  try {
    const res = await fetch(`../backend/lacak_resi.php?resi=${encodeURIComponent(resi)}`);
    const data = await res.json();
    if (data.ok && data.resi) {
      const r = data.resi;
      document.getElementById('lacakTitle').textContent = `Resi: ${r.no_resi}`;
      document.getElementById('lacakInfo').innerHTML = `
        <div><label style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Ekspedisi</label><p style="font-weight:700;margin-top:3px;font-size:14px">${r.kurir||'—'}</p></div>
        <div><label style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Barang</label><p style="font-weight:700;margin-top:3px;font-size:14px">${r.nama_barang}</p></div>
        <div><label style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Donatur</label><p style="margin-top:3px;font-size:14px">${r.nama_donatur}</p></div>
        <div><label style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Yayasan</label><p style="margin-top:3px;font-size:14px">${r.nama_yayasan}</p></div>`;
      let tlHtml = '';
      (data.steps||[]).forEach((s,i) => {
        const done = s.done;
        const curr = i === data.current && !done;
        tlHtml += `<li>
          <div class="tl-dot ${done?'done':curr?'active':''}">
            ${done?'<i class="ph ph-check" style="font-size: 1.25em; vertical-align: middle;"></i>':'<span style="width:8px;height:8px;border-radius:50%;background:currentColor;display:block;margin:auto"></span>'}
          </div>
          <div class="tl-label" style="color:${done?'var(--moss)':curr?'var(--amber)':'var(--muted)'}">${s.label}</div>
          <div class="tl-desc">${s.desc}</div>
        </li>`;
      });
      document.getElementById('lacakTimeline').innerHTML = tlHtml;
      document.getElementById('lacakResult').style.display = 'block';
    } else {
      document.getElementById('lacakErrMsg').textContent = data.error || 'Nomor resi tidak ditemukan.';
      document.getElementById('lacakEmpty').style.display = 'block';
    }
  } catch(err) { showToast('Gagal terhubung ke server', true); }
}

<?php if (!empty($_GET['resi'])): ?>
window.addEventListener('DOMContentLoaded', () => lacakResi());
<?php endif; ?>
</script>
</body>
</html>
