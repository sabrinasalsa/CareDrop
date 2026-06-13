<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

// Donasi per bulan (12 bulan terakhir)
$donasiPerBulan = $koneksi->query(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') AS bln, COUNT(*) AS total, SUM(qty_donasi) AS qty
     FROM donasi WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY bln ORDER BY bln"
)->fetch_all(MYSQLI_ASSOC);

// Donasi per status
$donasiStatus = $koneksi->query(
    "SELECT status_donasi, COUNT(*) AS total FROM donasi GROUP BY status_donasi"
)->fetch_all(MYSQLI_ASSOC);

// Top 5 kategori terbanyak didonasikan
$topKategori = $koneksi->query(
    "SELECT COALESCE(k.kategori,'lainnya') AS kategori, COUNT(*) AS total, SUM(d.qty_donasi) AS qty
     FROM donasi d LEFT JOIN katalog_kebutuhan k ON k.id=d.katalog_id
     WHERE d.status_donasi='selesai'
     GROUP BY kategori ORDER BY total DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

// Top 5 yayasan penerima
$topYayasan = $koneksi->query(
    "SELECT u.nama_lengkap, COUNT(d.id) AS total, SUM(d.qty_donasi) AS qty
     FROM donasi d
     JOIN katalog_kebutuhan k ON k.id=d.katalog_id
     JOIN users u ON u.id=k.yayasan_id
     WHERE d.status_donasi='selesai'
     GROUP BY u.id ORDER BY total DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

// Registrasi user per bulan
$regPerBulan = $koneksi->query(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') AS bln, role, COUNT(*) AS total
     FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY bln, role ORDER BY bln"
)->fetch_all(MYSQLI_ASSOC);

// Summary stats
$stats = [];
foreach ([
    'total_donasi'   => "SELECT COUNT(*) AS n FROM donasi",
    'donasi_selesai' => "SELECT COUNT(*) AS n FROM donasi WHERE status_donasi='selesai'",
    'total_barang'   => "SELECT COALESCE(SUM(qty_donasi),0) AS n FROM donasi WHERE status_donasi='selesai'",
    'total_donatur'  => "SELECT COUNT(*) AS n FROM users WHERE role='donatur'",
    'total_yayasan'  => "SELECT COUNT(*) AS n FROM users WHERE role='penerima' AND status_verifikasi='verified'",
    'donasi_bulan'   => "SELECT COUNT(*) AS n FROM donasi WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())",
] as $k=>$q) {
    $stats[$k] = (int)$koneksi->query($q)->fetch_assoc()['n'];
}
$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Analitik – CareDrop Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--dark:#0f2419;--g5:#16a34a;--g6:#15803d;--text1:#1a2e22;--text2:#52735e;--text3:#94a39b;--surf:#f8fdf9}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--surf);color:var(--text1)}
header{background:var(--dark);padding:0 28px;height:58px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:9}
.logo{font-size:1.1rem;font-weight:800;color:#4ade80}
header a{color:#cbd5e1;text-decoration:none;font-size:.85rem;margin-left:16px}
header a:hover{color:#fff}
.wrap{max-width:1200px;margin:0 auto;padding:28px 20px}
h1{font-size:1.4rem;font-weight:800;margin-bottom:4px}
.sub{color:var(--text2);font-size:.875rem;margin-bottom:24px}
.stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:26px}
.sc{background:#fff;border-radius:12px;padding:18px 20px;box-shadow:0 2px 12px rgba(0,0,0,.07);border-top:3px solid var(--g5)}
.sc.amber{border-color:#d97706}.sc.blue{border-color:#2563eb}.sc.purple{border-color:#9333ea}
.sc label{font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text3);display:block;margin-bottom:6px}
.sc big{font-size:1.9rem;font-weight:800;display:block;line-height:1}
.sc small{font-size:.75rem;color:var(--text2)}
.chart-grid{display:grid;grid-template-columns:2fr 1fr;gap:18px;margin-bottom:18px}
.chart-grid2{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
.ccard{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.07)}
.ccard h2{font-size:.95rem;font-weight:700;margin-bottom:16px;color:var(--text1)}
canvas{width:100%!important}
table{width:100%;border-collapse:collapse}
thead th{background:#f8fdf9;padding:9px 12px;text-align:left;font-size:.72rem;font-weight:700;text-transform:uppercase;color:var(--text2);border-bottom:1px solid #e8f5ea}
tbody tr{border-bottom:1px solid #f8fdf9}
td{padding:10px 12px;font-size:.855rem}
.bar-mini{height:6px;background:#e8f5ea;border-radius:99px;margin-top:4px;overflow:hidden}
.bar-fill{height:100%;background:var(--g5);border-radius:99px}
@media(max-width:768px){.stat-grid{grid-template-columns:repeat(2,1fr)}.chart-grid,.chart-grid2{grid-template-columns:1fr}}
</style>
</head>
<body>
<header>
  <span class="logo">🌿 CareDrop Admin</span>
  <nav><a href="index.php" style="display:inline-flex;align-items:center;gap:5px"><img src="https://img.icons8.com/?size=100&id=1806&format=png&color=000000" alt="" style="width:13px;height:13px;filter:invert(1);opacity:.7;"> Kembali ke Dashboard</a><a href="../backend/logout.php">Keluar</a></nav>
</header>
<div class="wrap">
  <h1>📊 Dashboard Analitik</h1>
  <p class="sub">Statistik dan tren platform CareDrop</p>

  <!-- STAT CARDS -->
  <div class="stat-grid">
    <div class="sc"><label>Total Donasi</label><big><?= number_format($stats['total_donasi']) ?></big><small><?= $stats['donasi_selesai'] ?> selesai · <?= $stats['donasi_bulan'] ?> bulan ini</small></div>
    <div class="sc amber"><label>Total Barang Tersalurkan</label><big style="color:#d97706"><?= number_format($stats['total_barang']) ?></big><small>unit dari donasi selesai</small></div>
    <div class="sc blue"><label>Donatur Aktif</label><big style="color:#2563eb"><?= number_format($stats['total_donatur']) ?></big><small><?= $stats['total_yayasan'] ?> yayasan terverifikasi</small></div>
  </div>

  <!-- CHARTS ROW 1 -->
  <div class="chart-grid">
    <div class="ccard">
      <h2>📈 Tren Donasi 12 Bulan Terakhir</h2>
      <canvas id="chartTren" height="120"></canvas>
    </div>
    <div class="ccard">
      <h2>🍩 Status Donasi</h2>
      <canvas id="chartStatus" height="160"></canvas>
    </div>
  </div>

  <!-- CHARTS ROW 2 -->
  <div class="chart-grid2">
    <div class="ccard">
      <h2>🏆 Top 5 Kategori Barang (Selesai)</h2>
      <table>
        <thead><tr><th>Kategori</th><th>Donasi</th><th>Unit</th></tr></thead>
        <tbody>
          <?php
          $maxKat = $topKategori ? max(array_column($topKategori,'total')) : 1;
          $icoMap = ['pakaian'=>'👕','buku'=>'📚','elektronik'=>'💻','perabot'=>'🛏️','makanan'=>'🍱','lainnya'=>'📦'];
          foreach($topKategori as $k):
            $pct = round(($k['total']/$maxKat)*100);
            $ico = $icoMap[$k['kategori']] ?? '📦';
          ?>
          <tr>
            <td><?= $ico ?> <?= ucfirst(htmlspecialchars($k['kategori'])) ?><div class="bar-mini"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div></td>
            <td><?= $k['total'] ?></td>
            <td><?= number_format($k['qty']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($topKategori)): ?><tr><td colspan="3" style="color:var(--text3);text-align:center;padding:20px">Belum ada data</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="ccard">
      <h2>🏠 Top 5 Yayasan Penerima</h2>
      <table>
        <thead><tr><th>Yayasan</th><th>Donasi</th><th>Unit</th></tr></thead>
        <tbody>
          <?php
          $maxY = $topYayasan ? max(array_column($topYayasan,'total')) : 1;
          foreach($topYayasan as $y):
            $pct = round(($y['total']/$maxY)*100);
          ?>
          <tr>
            <td><?= htmlspecialchars(mb_substr($y['nama_lengkap'],0,25)) ?><div class="bar-mini"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div></td>
            <td><?= $y['total'] ?></td>
            <td><?= number_format($y['qty']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($topYayasan)): ?><tr><td colspan="3" style="color:var(--text3);text-align:center;padding:20px">Belum ada data</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const donasiPerBulan = <?= json_encode($donasiPerBulan) ?>;
const donasiStatus   = <?= json_encode($donasiStatus) ?>;

// Chart 1: Tren donasi
const labels = donasiPerBulan.map(d => {
  const [y,m] = d.bln.split('-');
  return new Date(y,m-1).toLocaleDateString('id-ID',{month:'short',year:'2-digit'});
});
new Chart(document.getElementById('chartTren'), {
  type: 'line',
  data: {
    labels,
    datasets: [{
      label: 'Jumlah Donasi',
      data: donasiPerBulan.map(d => d.total),
      borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.1)',
      fill: true, tension: .4, pointRadius: 4, pointBackgroundColor: '#16a34a',
    },{
      label: 'Unit Barang',
      data: donasiPerBulan.map(d => d.qty),
      borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.06)',
      fill: true, tension: .4, pointRadius: 4, pointBackgroundColor: '#2563eb',
    }]
  },
  options: { responsive:true, plugins:{ legend:{position:'bottom'} }, scales:{ y:{ beginAtZero:true, grid:{color:'#f0fdf4'} } } }
});

// Chart 2: Donut status
const statusLabel = { menunggu:'Menunggu', disetujui:'Disetujui', dikirim:'Dikirim', selesai:'Selesai', ditolak:'Ditolak', dibatalkan:'Dibatalkan' };
const statusColor = { menunggu:'#fbbf24', disetujui:'#60a5fa', dikirim:'#34d399', selesai:'#16a34a', ditolak:'#f87171', dibatalkan:'#9ca3af' };
new Chart(document.getElementById('chartStatus'), {
  type: 'doughnut',
  data: {
    labels: donasiStatus.map(d => statusLabel[d.status_donasi]||d.status_donasi),
    datasets: [{ data: donasiStatus.map(d=>d.total), backgroundColor: donasiStatus.map(d=>statusColor[d.status_donasi]||'#ccc'), borderWidth:2, borderColor:'#fff' }]
  },
  options: { responsive:true, plugins:{ legend:{position:'bottom', labels:{font:{size:11}}} }, cutout:'65%' }
});
</script>
</body></html>
