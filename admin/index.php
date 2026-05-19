<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php'); exit;
}

// Statistik global
$stats = [];
$q = [
    'total_user'    => "SELECT COUNT(*) AS n FROM users",
    'total_donatur' => "SELECT COUNT(*) AS n FROM users WHERE role='donatur'",
    'total_penerima'=> "SELECT COUNT(*) AS n FROM users WHERE role='penerima'",
    'pending_verif' => "SELECT COUNT(*) AS n FROM users WHERE role='penerima' AND status_verifikasi='pending'",
    'total_donasi'  => "SELECT COUNT(*) AS n FROM donasi",
    'donasi_aktif'  => "SELECT COUNT(*) AS n FROM donasi WHERE status_donasi NOT IN ('selesai','dibatalkan')",
    'donasi_selesai'=> "SELECT COUNT(*) AS n FROM donasi WHERE status_donasi='selesai'",
    'total_barang'  => "SELECT COALESCE(SUM(qty_donasi),0) AS n FROM donasi WHERE status_donasi='selesai'",
];
foreach ($q as $k => $sql) {
    $r = $koneksi->query($sql)->fetch_assoc();
    $stats[$k] = (int)($r['n'] ?? 0);
}

// Semua user
$users = $koneksi->query(
    "SELECT id, nama_lengkap, email, role, COALESCE(status_verifikasi,'—') AS status_verifikasi,
            COALESCE(no_telp,'—') AS no_telp, created_at
     FROM users ORDER BY created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

// Donasi terbaru
$donasi = $koneksi->query(
    "SELECT d.id, d.qty_donasi, d.status_donasi, d.created_at,
            COALESCE(k.nama_barang,'—') AS barang,
            COALESCE(ud.nama_lengkap,'—') AS donatur,
            COALESCE(up.nama_lengkap,'—') AS yayasan
     FROM donasi d
     LEFT JOIN katalog_kebutuhan k ON k.id=d.katalog_id
     LEFT JOIN users ud ON ud.id=d.donatur_id
     LEFT JOIN users up ON up.id=k.yayasan_id
     ORDER BY d.created_at DESC LIMIT 20"
)->fetch_all(MYSQLI_ASSOC);

$koneksi->close();

$nama_admin = $_SESSION['nama'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel – CareDrop</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body>

<header>
  <div>
    <span class="logo">🌿 CareDrop</span>
    <span class="logo"><span>Admin Panel</span><span class="admin-badge">ADMIN</span></span>
  </div>
  <nav>
    <a href="analitik.php">📊 Analitik</a>
    <a href="kelola_kategori.php">🏷️ Kategori</a>
    <a href="kelola_sertifikat.php">🏅 Sertifikat</a>
    <a href="../index.php">← Dashboard</a>
    <a href="../backend/logout.php">🚪 Keluar</a>
  </nav>
</header>

<div class="wrap">
  <h1>⚙️ Panel Admin</h1>
  <p class="sub">Selamat datang, <strong><?= htmlspecialchars($nama_admin) ?></strong> — kelola seluruh data CareDrop</p>

  <?php if(isset($_GET['msg'])): ?>
  <div class="flash flash-ok"><?= htmlspecialchars($_GET['msg']) ?></div>
  <?php elseif(isset($_GET['err'])): ?>
  <div class="flash flash-err"><?= htmlspecialchars($_GET['err']) ?></div>
  <?php endif; ?>

  <!-- STAT CARDS -->
  <div class="stat-grid">
    <div class="sc">
      <label>Total Pengguna</label>
      <big><?= $stats['total_user'] ?></big>
      <small><?= $stats['total_donatur'] ?> donatur · <?= $stats['total_penerima'] ?> penerima</small>
    </div>
    <div class="sc amber">
      <label>Menunggu Verifikasi</label>
      <big style="color:#d97706"><?= $stats['pending_verif'] ?></big>
      <small>penerima pending</small>
    </div>
    <div class="sc blue">
      <label>Donasi Aktif</label>
      <big style="color:#2563eb"><?= $stats['donasi_aktif'] ?></big>
      <small>dari <?= $stats['total_donasi'] ?> total donasi</small>
    </div>
    <div class="sc">
      <label>Barang Tersalurkan</label>
      <big style="color:var(--g5)"><?= number_format($stats['total_barang']) ?></big>
      <small>unit dari <?= $stats['donasi_selesai'] ?> donasi selesai</small>
    </div>
  </div>

  <!-- TABS -->
  <div class="section">
    <div class="tabs">
      <div class="tab on" onclick="switchTab('tab-user',this)">👥 Manajemen User (<?= count($users) ?>)</div>
      <div class="tab" onclick="switchTab('tab-donasi',this)">📦 Data Donasi (<?= count($donasi) ?>)</div>
    </div>

    <!-- TAB: USER -->
    <div class="tab-panel on" id="tab-user">
      <div class="search-wrap">
        <input type="text" id="search-user" placeholder="🔍 Cari nama, email, atau role..." oninput="filterTable('tbl-user',this.value,[0,1,2])">
      </div>
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>No. Telp</th>
              <th>Role</th>
              <th>Status</th>
              <th>Bergabung</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody id="tbl-user">
            <?php foreach ($users as $u):
              $roleCls = ['donatur'=>'role-d','penerima'=>'role-p','admin'=>'role-a'][$u['role']] ?? '';
              $st = $u['status_verifikasi'];
              $stCls = $st==='verified'?'tg':($st==='pending'?'ta':($st==='rejected'?'tr':''));
              $tgl = date('d M Y', strtotime($u['created_at']));
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($u['nama_lengkap']) ?></strong></td>
              <td style="color:var(--text2)"><?= htmlspecialchars($u['email']) ?></td>
              <td style="color:var(--text2)"><?= htmlspecialchars($u['no_telp']) ?></td>
              <td><span class="tag <?= $roleCls ?>"><?= ucfirst($u['role']) ?></span></td>
              <td>
                <?php if ($u['role']==='penerima'): ?>
                  <span class="tag <?= $stCls ?>"><?= ucfirst($st) ?></span>
                <?php else: ?>
                  <span style="color:var(--text3)">—</span>
                <?php endif; ?>
              </td>
              <td style="color:var(--text2);font-size:.8rem"><?= $tgl ?></td>
              <td>
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                  <?php if ($u['role']==='penerima' && $u['status_verifikasi']==='pending'): ?>
                    <a href="aksi_user.php?aksi=verif&id=<?= $u['id'] ?>" class="btn btn-green btn-sm"
                       onclick="return confirm('Verifikasi akun <?= htmlspecialchars(addslashes($u['nama_lengkap'])) ?>?')">✅ Verifikasi</a>
                    <a href="aksi_user.php?aksi=tolak&id=<?= $u['id'] ?>" class="btn btn-red btn-sm"
                       onclick="return confirm('Tolak akun ini?')">❌ Tolak</a>
                  <?php endif; ?>
                  <?php if ($u['role'] !== 'admin'): ?>
                    <a href="aksi_user.php?aksi=hapus&id=<?= $u['id'] ?>" class="btn btn-ghost btn-sm"
                       onclick="return confirm('Hapus akun <?= htmlspecialchars(addslashes($u['nama_lengkap'])) ?>? Data tidak bisa dikembalikan!')">🗑</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB: DONASI -->
    <div class="tab-panel" id="tab-donasi">
      <div class="search-wrap">
        <input type="text" id="search-donasi" placeholder="🔍 Cari ID, barang, donatur, atau yayasan..." oninput="filterTable('tbl-donasi',this.value,[0,1,2,3])">
      </div>
      <div style="overflow-x:auto">
        <table>
          <thead>
            <tr>
              <th>ID Donasi</th>
              <th>Barang</th>
              <th>Donatur</th>
              <th>Yayasan</th>
              <th>Qty</th>
              <th>Status</th>
              <th>Tanggal</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody id="tbl-donasi">
            <?php foreach ($donasi as $d):
              $stMap = [
                'menunggu'  => ['sd-menunggu','⏳ Menunggu'],
                'diproses'  => ['sd-diproses','📦 Diproses'],
                'dikirim'   => ['sd-dikirim','🚚 Dikirim'],
                'selesai'   => ['sd-selesai','✅ Selesai'],
                'dibatalkan'=> ['sd-dibatalkan','❌ Dibatalkan'],
              ];
              [$stCls,$stLbl] = $stMap[$d['status_donasi']] ?? ['','—'];
              $tgl = date('d M Y', strtotime($d['created_at']));
            ?>
            <tr>
              <td style="font-family:monospace;font-size:.75rem"><?= htmlspecialchars($d['id']) ?></td>
              <td><?= htmlspecialchars($d['barang']) ?></td>
              <td><?= htmlspecialchars($d['donatur']) ?></td>
              <td><?= htmlspecialchars($d['yayasan']) ?></td>
              <td><?= $d['qty_donasi'] ?> unit</td>
              <td><span class="tag <?= $stCls ?>"><?= $stLbl ?></span></td>
              <td style="color:var(--text2);font-size:.8rem"><?= $tgl ?></td>
              <td>
                <div style="display:flex;gap:5px">
                  <?php if ($d['status_donasi']==='menunggu'): ?>
                  <a href="aksi_donasi.php?aksi=proses&id=<?= urlencode($d['id']) ?>" class="btn btn-blue btn-sm">Proses</a>
                  <a href="aksi_donasi.php?aksi=batal&id=<?= urlencode($d['id']) ?>"
                     class="btn btn-red btn-sm" onclick="return confirm('Batalkan donasi ini?')">Batal</a>
                  <?php elseif($d['status_donasi']==='dikirim'): ?>
                  <a href="aksi_donasi.php?aksi=selesai&id=<?= urlencode($d['id']) ?>" class="btn btn-green btn-sm"
                     onclick="return confirm('Tandai donasi ini selesai?')">Selesaikan</a>
                  <?php else: ?>
                  <span style="color:var(--text3);font-size:.8rem">—</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<script src="admin.js"></script>
</body>
</html>