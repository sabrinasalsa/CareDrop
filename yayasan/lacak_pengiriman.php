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
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
            Dashboard
        </a>
        <a href="kelola_katalog.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            Katalog Kebutuhan
        </a>
        <a href="tawaran_masuk.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
            Tawaran Masuk
            <?php if ($badge_menunggu > 0): ?>
                <span class="badge"><?= $badge_menunggu ?></span>
            <?php endif; ?>
        </a>
        <a href="konfirmasi_terima.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Konfirmasi Terima
            <?php if ($badge_dikirim > 0): ?>
                <span class="badge"><?= $badge_dikirim ?></span>
            <?php endif; ?>
        </a>
        <a href="lacak_pengiriman.php" class="nav-item active">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
            Lacak Pengiriman
        </a>
        <div class="nav-divider"></div>
        <a href="../backend/export_csv.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Laporan CSV
        </a>
        <a href="profil_yayasan.php" class="nav-item">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
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
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
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
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;vertical-align:middle"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803z"/></svg>
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
      <svg width="40" height="40" fill="none" stroke="#4aad6b" stroke-width="1" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
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
            ${done?'<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>':'<span style="width:8px;height:8px;border-radius:50%;background:currentColor;display:block;margin:auto"></span>'}
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
