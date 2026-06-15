<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

$activePage = 'analitik';

// Donasi per bulan (12 bulan terakhir)
$donasiPerBulan = $pdo->query(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') AS bln, COUNT(*) AS total, SUM(qty_donasi) AS qty
     FROM donasi WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY bln ORDER BY bln"
)->fetchAll(PDO::FETCH_ASSOC);

// Donasi per status
$donasiStatus = $pdo->query(
    "SELECT status_donasi, COUNT(*) AS total FROM donasi GROUP BY status_donasi"
)->fetchAll(PDO::FETCH_ASSOC);

// Top 5 kategori terbanyak didonasikan
$topKategori = $pdo->query(
    "SELECT COALESCE(k.kategori,'lainnya') AS kategori, COUNT(*) AS total, SUM(d.qty_donasi) AS qty
     FROM donasi d LEFT JOIN katalog_kebutuhan k ON k.id=d.katalog_id
     WHERE d.status_donasi='selesai'
     GROUP BY kategori ORDER BY total DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// Top 5 yayasan penerima
$topYayasan = $pdo->query(
    "SELECT u.nama_lengkap, COUNT(d.id) AS total, SUM(d.qty_donasi) AS qty
     FROM donasi d
     JOIN katalog_kebutuhan k ON k.id=d.katalog_id
     JOIN users u ON u.id=k.yayasan_id
     WHERE d.status_donasi='selesai'
     GROUP BY u.id ORDER BY total DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// Registrasi user per bulan
$regPerBulan = $pdo->query(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') AS bln, role, COUNT(*) AS total
     FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY bln, role ORDER BY bln"
)->fetchAll(PDO::FETCH_ASSOC);

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
    $stats[$k] = (int)$pdo->query($q)->fetch(PDO::FETCH_ASSOC)['n'];
}
$pdo = null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Analitik – CareDrop Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<link rel="stylesheet" href="Assets/admin.css">
<style>
.stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:26px}
.sc{background:var(--white);border-radius:12px;padding:18px 20px;box-shadow:0 2px 12px rgba(0,0,0,.07);border-top:3px solid var(--moss);border:1px solid var(--border)}
.sc.amber{border-top-color:#d97706}.sc.blue{border-top-color:var(--blue)}
.sc label{font-size:.68rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:6px}
.sc big{font-size:1.9rem;font-weight:800;display:block;line-height:1}
.sc small{font-size:.75rem;color:var(--muted)}
.chart-grid{display:grid;grid-template-columns:2fr 1fr;gap:18px;margin-bottom:18px}
.chart-grid2{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}
.ccard{background:var(--white);border-radius:12px;padding:20px;box-shadow:0 2px 12px rgba(0,0,0,.07);border:1px solid var(--border)}
.ccard h2{font-size:.95rem;font-weight:700;margin-bottom:16px;color:var(--forest)}
canvas{width:100%!important}
.bar-mini{height:6px;background:#e8f5ea;border-radius:99px;margin-top:4px;overflow:hidden}
.bar-fill{height:100%;background:var(--moss);border-radius:99px}
@media(max-width:900px){.stat-grid{grid-template-columns:repeat(2,1fr)}.chart-grid,.chart-grid2{grid-template-columns:1fr}}
</style>
</head>
<body>
<?php require '_sidebar.php'; ?>
<main class="main">
<div class="page-header">
  <h1 class="page-title">Dashboard Analitik</h1>
  <p class="page-subtitle">Statistik dan tren platform CareDrop</p>
</div>

  <!-- STAT CARDS -->
  <div class="stat-grid">
    <div class="sc"><label>Total Donasi</label><big><?= number_format($stats['total_donasi']) ?></big><small><?= $stats['donasi_selesai'] ?> selesai · <?= $stats['donasi_bulan'] ?> bulan ini</small></div>
    <div class="sc amber"><label>Total Barang Tersalurkan</label><big style="color:#d97706"><?= number_format($stats['total_barang']) ?></big><small>unit dari donasi selesai</small></div>
    <div class="sc blue"><label>Donatur Aktif</label><big style="color:#2563eb"><?= number_format($stats['total_donatur']) ?></big><small><?= $stats['total_yayasan'] ?> yayasan terverifikasi</small></div>
  </div>

  <!-- CHARTS ROW 1 -->
  <div class="chart-grid">
    <div class="ccard">
      <h2 style="display:flex;align-items:center;gap:8px"><img src="https://img.icons8.com/?size=100&id=101799&format=png&color=12B886" alt="" style="width:22px;height:22px;"> Tren Donasi 12 Bulan Terakhir</h2>
      <canvas id="chartTren" height="120"></canvas>
    </div>
    <div class="ccard">
      <h2>Status Donasi</h2>
      <canvas id="chartStatus" height="160"></canvas>
    </div>
  </div>

  <!-- CHARTS ROW 2 -->
  <div class="chart-grid2">
    <div class="ccard">
      <h2 style="display:flex;align-items:center;gap:8px"><img src="https://img.icons8.com/?size=100&id=kuU7I7uPlHfo&format=png&color=000000" alt="" style="width:22px;height:22px;"> Top 5 Kategori Barang (Selesai)</h2>
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
      <h2 style="display:flex;align-items:center;gap:8px"><img src="../uploads/icon/handshake.png" alt="" style="width:22px;height:22px;"> Top 5 Yayasan Penerima</h2>
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
</main>

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
