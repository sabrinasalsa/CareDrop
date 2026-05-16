<?php
session_start();

define('APP_NAME',    'CareDrop');
define('APP_VERSION', '1.0.0');
define('APP_DESC',    'Layanan Donasi Barang Layak Pakai Sesuaian Kebutuhan Penerima');

// Jika sudah login via session, siapkan data untuk JS
$session_user = null;
if (isset($_SESSION['id'], $_SESSION['role'], $_SESSION['nama'])) {
    $session_user = [
        'id'      => $_SESSION['id'],
        'nama'    => $_SESSION['nama'],
        'email'   => $_SESSION['email']   ?? '',
        'role'    => $_SESSION['role'],
        'no_telp' => $_SESSION['no_telp'] ?? '',
        'alamat'  => $_SESSION['alamat']  ?? '',
    ];
}

$demo_accounts = [
    'donatur' => [
        'name'  => 'Sabrina Salsabila',
        'email' => 'sabrina@email.com',
        'role'  => 'donatur',
    ],
    'penerima' => [
        'name'  => 'Panti Asuhan Al-Ikhlas',
        'email' => 'alikhlas@yayasan.id',
        'role'  => 'penerima',
    ],
    'admin' => [
        'name'  => 'Admin CareDrop',
        'email' => 'admin@caredrop.id',
        'role'  => 'admin',
    ],
];

$page_title = APP_NAME . ' – ' . APP_DESC;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <meta name="description" content="<?= htmlspecialchars(APP_DESC) ?>">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Stylesheet -->
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php if(isset($_GET['timeout'])): ?>
<div style="background:#fef3c7;color:#92400e;padding:10px 20px;text-align:center;font-size:.875rem;font-weight:500">
  ⏰ Sesi Anda telah berakhir karena tidak aktif selama 2 jam. Silakan login kembali.
</div>
<?php endif; ?>

<!-- ── Toast Notification ── -->
<div id="toast"></div>

<!-- LANDING PAGE-->
<div id="pg-landing">
  <nav class="land-nav">
    <div class="logo" onclick="show('pg-landing')">Care<em>Drop</em></div>
    <div style="display:flex;gap:7px">
      <button class="btn btn-ghost btn-sm" onclick="goLogin()">Masuk</button>
      <button class="btn btn-green btn-sm" onclick="goRegister()">Daftar Gratis</button>
    </div>
  </nav>

  <div class="land-hero">
    <div>
      <div class="badge-live"><span class="dot"></span>Platform Aktif</div>
      <h1>Donasi Barang<br><mark>Tepat Sasaran,</mark><br>Tepat Guna.</h1>
      <p>
        CareDrop menghubungkan donatur, yayasan, dan relawan dalam satu ekosistem
        digital yang transparan — penjemputan otomatis, pelacakan real-time, dan
        sertifikat elektronik.
      </p>
      <div class="hero-btns">
        <button class="btn btn-wh" onclick="goLogin('donatur')">🤝 Saya Ingin Donasi</button>
        <button class="btn btn-ow" onclick="goLogin('penerima')">🏠 Daftarkan Yayasan/Posko</button>
      </div>
    </div>
    <div class="hero-cards">
      <div class="hc"><div class="hc-i ig">🎯</div><div><h4>Need Matching</h4><p>Barang cocok kebutuhan yayasan otomatis</p></div></div>
      <div class="hc"><div class="hc-i ia">🚗</div><div><h4>Penjemputan Gratis</h4><p>Relawan terdekat datang ke lokasi donatur</p></div></div>
      <div class="hc"><div class="hc-i ib">📍</div><div><h4>Pelacakan Real-Time</h4><p>Transparan dari jemput hingga diterima</p></div></div>
      <div class="hc"><div class="hc-i io">📜</div><div><h4>E-Sertifikat Otomatis</h4><p>Bukti sahih dikirim ke donatur</p></div></div>
    </div>
  </div>

  <!-- Stats Strip -->
  <div class="strip">
    <div class="si"><big>1.240</big><small>Barang Tersalurkan</small></div>
    <div class="si"><big>38</big><small>Yayasan Terdaftar</small></div>
    <div class="si"><big>127</big><small>Relawan Aktif</small></div>
    <div class="si"><big>97%</big><small>Tepat Sasaran</small></div>
  </div>

  <!-- Features -->
  <div class="feats">
    <div style="text-align:center;margin-bottom:30px">
      <p class="sec-label">Ekosistem Tiga Peran</p>
      <p class="sec-title" style="margin:0 auto">Satu Platform, Tiga Peran, Satu Tujuan</p>
    </div>
    <div class="feat-g">
      <div class="fc"><div class="fc-i">🤝</div><h3>Donatur</h3><p>Pilih kebutuhan yayasan, unggah foto barang, jadwalkan penjemputan tanpa keluar rumah.</p></div>
      <div class="fc"><div class="fc-i">🏠</div><h3>Penerima (Yayasan/Posko)</h3><p>Buat katalog kebutuhan spesifik, konfirmasi donasi masuk, dan terima laporan otomatis.</p></div>
      <div class="fc"><div class="fc-i">⚙️</div><h3>Admin</h3><p>Kelola pengguna, verifikasi yayasan, pantau alur donasi, dan cetak laporan platform.</p></div>
    </div>
  </div>
</div><!-- end pg-landing -->


<div id="pg-login" class="hide">
  <div class="l-left">
    <div class="logo">Care<em>Drop</em></div>
    <h2>Selamat datang<br>kembali 👋</h2>
    <p>Masuk dengan akun Anda sesuai peran — donatur, penerima, atau admin.</p>
    <div class="role-pills">
      <div class="rp"><span>🤝</span><div><strong>Donatur</strong><em>Cari kebutuhan · Donasi · Lacak barang</em></div></div>
      <div class="rp"><span>🏠</span><div><strong>Penerima</strong><em>Yayasan / Posko · Kelola katalog · Konfirmasi donasi</em></div></div>
      <div class="rp"><span>⚙️</span><div><strong>Admin</strong><em>Kelola pengguna · Verifikasi · Laporan</em></div></div>
    </div>
  </div>

  <div class="l-right">
    <div class="lbox">
      <h3>Masuk ke <?= APP_NAME ?></h3>
      <p class="subt">Masuk dengan akun terdaftar, atau coba akun demo</p>

      <!-- Role Tabs -->
      <div class="role-tabs">
        <button class="rt on"  id="tab-don" onclick="switchRole('donatur')">🤝 Donatur</button>
        <button class="rt"     id="tab-pen" onclick="switchRole('penerima')">🏠 Penerima</button>
        <button class="rt"     id="tab-adm" onclick="switchRole('admin')">⚙️ Admin</button>
      </div>

      <form action="backend/proses_login.php" method="POST">
        <input type="hidden" name="role_hint" id="l-role-hint" value="donatur">
        <div class="fg">
          <label>Email</label>
          <input type="email" name="email" id="l-email" placeholder="email@contoh.com" required>
        </div>
        <div class="fg">
          <label>Kata Sandi</label>
          <input type="password" name="password" id="l-pass" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-green" style="width:100%;justify-content:center;margin-top:2px">
          Masuk →
        </button>
      </form>

      <div class="divider">atau gunakan akun demo</div>

      <!-- Demo Accounts -->
      <div class="demos">
        <?php foreach ($demo_accounts as $role => $acc): ?>
        <div class="demo" onclick="demoLogin('<?= $role ?>')">
          <div>
            <strong><?= htmlspecialchars($acc['name']) ?></strong>
            <span><?= htmlspecialchars($acc['email']) ?> · demo123</span>
          </div>
          <?php
            $tagClass = ['donatur'=>'tg','penerima'=>'ta','admin'=>'tb'][$role] ?? 'tg';
            $roleLabel = ['donatur'=>'Donatur','penerima'=>'Penerima','admin'=>'Admin'][$role] ?? $role;
          ?>
          <span class="tag <?= $tagClass ?>"><?= $roleLabel ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <p class="sw">Belum punya akun? <a onclick="goRegister()">Daftar sekarang</a></p>
    </div><!-- end lbox -->
  </div><!-- end l-right -->
</div><!-- end pg-login -->

<div id="pg-register" class="hide">
  <div class="l-left" style="background: linear-gradient(150deg, var(--g3), var(--g1));">
    <div class="logo">Care<em>Drop</em></div>
    <h2>Mulai Langkah<br>Kebaikanmu 🤝</h2>
    <p>Bergabunglah menjadi bagian dari ekosistem donasi yang transparan, mudah, dan tepat sasaran.</p>
  </div>

  <div class="l-right">
    <div class="lbox" style="max-width: 420px;">
      <h3>Daftar Akun Baru</h3>
      <p class="subt">Lengkapi data diri untuk mulai bergabung</p>

      <form action="backend/proses_registrasi.php" method="POST">
        <div class="fg">
          <label>Nama Lengkap</label>
          <input type="text" name="nama_lengkap" id="r-nama" placeholder="Budi Santoso" required>
        </div>
        <div class="fg">
          <label>Email</label>
          <input type="email" name="email" id="r-email" placeholder="email@contoh.com" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="fg">
            <label>Kata Sandi</label>
            <input type="password" name="password" id="r-pass" placeholder="min. 6 karakter" required minlength="6">
          </div>
          <div class="fg">
            <label>Konfirmasi Sandi</label>
            <input type="password" name="password_confirm" id="r-pass2" placeholder="ulangi sandi" required>
          </div>
        </div>
        <div class="fg">
          <label>No. Telepon / WA</label>
          <input type="tel" name="no_telp" id="r-telp" placeholder="0812xxxx" required>
        </div>
        <div class="fg">
          <label>Daftar Sebagai</label>
          <select name="role" id="r-role" required style="width:100%;padding:9px 13px;border:1px solid var(--bord);border-radius:8px;font-family:inherit;font-size:.9rem;background:var(--white);color:var(--text)">
            <option value="" disabled selected>— Pilih Peran —</option>
            <option value="donatur">🤝 Donatur Pribadi</option>
            <option value="penerima">🏠 Yayasan / Posko Penerima</option>
          </select>
        </div>
        <div class="fg">
          <label>Alamat Lengkap</label>
          <textarea name="alamat" id="r-alamat" placeholder="Jalan, RT/RW, Kecamatan..." rows="2" required style="width:100%;padding:9px 13px;border:1px solid var(--bord);border-radius:8px;font-family:inherit;font-size:.9rem;resize:none"></textarea>
        </div>

        <button type="submit" class="btn btn-green" style="width:100%;justify-content:center;margin-top:8px" onclick="return validateRegister()">
          Daftar Sekarang →
        </button>
      </form>

      <p class="sw">Sudah punya akun? <a onclick="goLogin()">Masuk di sini</a></p>
    </div>
  </div>
</div>


<!-- ════════════════════════════════════════════
     APP SHELL (Post-Login)
     ════════════════════════════════════════════ -->
<div id="pg-app" class="hide">

  <!-- App Header -->
  <header class="app-hd">
    <div class="logo" onclick="doLogout()">Care<em>Drop</em></div>
    <nav id="app-nav"></nav>
    <div style="display:flex;align-items:center;gap:10px">
      <!-- Notifikasi bell -->
      <div class="notif-bell" id="notif-bell" onclick="toggleNotif()" title="Notifikasi">
        🔔
        <span class="notif-badge hide" id="notif-badge">0</span>
      </div>
      <div class="user-pill" onclick="goToProfile()">
        <div class="uav" id="uav">?</div>
        <div class="user-pill-txt">
          <div class="uname" id="uname">—</div>
          <div class="urole" id="urole">—</div>
        </div>
      </div>
      <!-- Hamburger (mobile) -->
      <button class="hamburger" id="hamburger" onclick="toggleMobileNav()" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>
  <!-- Mobile nav drawer -->
  <div class="mobile-nav-overlay hide" id="mobile-nav-overlay" onclick="closeMobileNav()"></div>
  <div class="mobile-nav hide" id="mobile-nav">
    <div class="mobile-nav-head">
      <div style="display:flex;align-items:center;gap:10px">
        <div class="uav" id="uav-mob">?</div>
        <div>
          <div style="font-weight:700;color:#fff" id="uname-mob">—</div>
          <div style="font-size:.75rem;color:#86efac" id="urole-mob">—</div>
        </div>
      </div>
      <button onclick="closeMobileNav()" style="background:none;border:none;color:#fff;font-size:1.3rem;cursor:pointer">✕</button>
    </div>
    <nav id="mobile-nav-links"></nav>
    <div style="padding:16px;border-top:1px solid rgba(255,255,255,.1);margin-top:auto">
      <button class="btn btn-red btn-sm" style="width:100%;justify-content:center" onclick="doLogout()">🚪 Keluar Akun</button>
    </div>
  </div>
  <!-- Notifikasi panel -->
  <div class="notif-panel hide" id="notif-panel">
    <div class="notif-head"><h4>🔔 Notifikasi</h4><button onclick="closeNotif()" style="background:none;border:none;cursor:pointer;font-size:1rem">✕</button></div>
    <div id="notif-list"><p style="padding:16px;color:var(--text3);text-align:center">Belum ada notifikasi</p></div>
  </div>

  <!-- ════════ ROLE: DONATUR ════════ -->
  <div id="rd" class="hide">

    <!-- Dashboard -->
    <div class="inner-page" id="ip-d-home">
      <div class="ph">
        <h2>Dashboard Donatur</h2>
        <p>Selamat datang kembali, <span id="d-greet"></span> 👋</p>
      </div>
      <div class="wrap">
        <div class="stat-row">
          <div class="sc"><label>Total Donasi</label><span id="stat-d-total"><big>—</big></span><small>sejak bergabung</small></div>
          <div class="sc sc-a"><label>Sedang Berjalan</label><span id="stat-d-berjalan"><big>—</big></span><small>dalam proses</small></div>
          <div class="sc"><label>Selesai</label><span id="stat-d-selesai"><big>—</big></span><small>tersalurkan</small></div>
          <div class="sc"><label>E-Sertifikat</label><span id="stat-d-sertif"><big>—</big></span><small>diterbitkan</small></div>
        </div>
        <div class="tcrd">
          <div class="thead">
            <h3>Donasi Terbaru</h3>
            <div style="display:flex;gap:8px;align-items:center">
              <input type="text" placeholder="🔍 Cari..." oninput="searchRiwayat(this.value)"
                style="padding:6px 10px;border:1.5px solid #d1fae5;border-radius:7px;font-size:.8rem;font-family:inherit;width:150px">
              <button class="btn btn-green btn-sm" onclick="nav('d-kat')">+ Donasi Baru</button>
            </div>
          </div>
          <table>
            <thead><tr><th>ID</th><th>Barang</th><th>Yayasan</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
            <tbody id="tbl-d-riw">
              <tr><td colspan="6" class="tbl-loading">⏳ Memuat data donasi Anda...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end d-home -->

    <!-- Katalog -->
    <div class="inner-page" id="ip-d-kat">
      <div class="ph">
        <h2>Katalog Kebutuhan</h2>
        <p>Pilih kebutuhan yayasan yang ingin kamu bantu</p>
      </div>
      <div class="fbar">
        <button class="chip on" onclick="setChip(this,'semua')">Semua</button>
        <button class="chip" onclick="setChip(this,'pakaian')">👗 Pakaian</button>
        <button class="chip" onclick="setChip(this,'buku')">📚 Buku</button>
        <button class="chip" onclick="setChip(this,'elektronik')">💻 Elektronik</button>
        <button class="chip" onclick="setChip(this,'perabot')">🛏️ Perabot</button>
        <div class="srch"><input type="text" placeholder="Cari barang atau yayasan..." oninput="filterKat(this.value)"></div>
      </div>
      <div class="kg" id="kat-grid"></div>
    </div><!-- end d-kat -->

    <!-- Form Donasi -->
    <div class="inner-page" id="ip-d-don">
      <div class="ph">
        <h2>Form Donasi Barang</h2>
        <p>Lengkapi detail donasi dan pengiriman</p>
      </div>
      <div class="form-outer">
        <!-- Step 1: Pilih Item dari Katalog DB -->
        <div class="fcard">
          <div class="fcard-t"><span class="sn">1</span> Pilih Kebutuhan dari Katalog</div>
          <div id="don-katalog-loading" style="padding:16px;color:var(--text2);text-align:center">⏳ Memuat katalog...</div>
          <div id="don-katalog-list" style="display:none">
            <select id="pilih-katalog" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1fae5;font-size:.9rem" onchange="onPilihKatalog(this)">
              <option value="">— Pilih barang yang ingin didonasikan —</option>
            </select>
            <div id="don-item-info" style="display:none;margin-top:12px;padding:12px;background:#f0fdf4;border-radius:8px;font-size:.875rem"></div>
          </div>
          <div id="don-katalog-empty" style="display:none;padding:16px;color:var(--text3);text-align:center">
            📭 Belum ada kebutuhan yang tersedia di katalog
          </div>
          <!-- Hidden fields -->
          <input type="hidden" id="don-katalog-id"  value="">
          <input type="hidden" id="don-kota-tujuan" value="">
          <input type="hidden" id="don-yayasan-id"  value="">

          <div class="frow" style="margin-top:14px">
            <div class="fg2">
              <label>Jumlah Barang yang Didonasikan</label>
              <input type="number" id="don-qty" min="1" value="1" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1fae5">
            </div>
            <div class="fg2">
              <label>Deskripsi Kondisi Barang</label>
              <textarea id="don-deskripsi" placeholder="Contoh: Masih layak pakai, sedikit bekas..." style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1fae5;min-height:52px;resize:none"></textarea>
            </div>
          </div>

          <div style="margin-top:14px">
            <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:6px">Foto Barang (opsional)</label>
            <label for="don-foto" class="up-box" style="cursor:pointer">
              <div class="ui">📷</div>
              <p id="don-foto-label">Klik untuk unggah foto barang</p>
              <span>JPG, PNG · Max 5MB</span>
            </label>
            <input type="file" id="don-foto" accept="image/*" style="display:none" onchange="onFotoChange(this)">
          </div>
        </div>

        <!-- Step 2: Pengiriman -->
        <div class="fcard">
          <div class="fcard-t"><span class="sn">2</span> Logistik & Pengiriman</div>
          <div class="frow">
            <div class="fg2">
              <label>Kota Asal (Kamu)</label>
              <select id="kota-asal" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1fae5">
                <option value="Mataram">Mataram</option>
                <option value="Lombok">Lombok</option>
                <option value="Selong">Selong</option>
                <option value="Praya">Praya</option>
              </select>
            </div>
            <div class="fg2">
              <label>Berat Estimasi (kg)</label>
              <input type="number" id="berat-barang" value="2" min="1" max="50" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1fae5">
            </div>
          </div>
          <button class="btn btn-outline btn-sm" onclick="hitungEstimasiPengiriman()" style="margin-bottom:14px">
            🚚 Cari Opsi Pengiriman
          </button>
          <div id="courier-result"></div>
        </div>

        <!-- Step 3: Jadwal -->
        <div class="fcard">
          <div class="fcard-t"><span class="sn">3</span> Jadwal Penjemputan</div>
          <div class="slots">
            <button class="slot on" onclick="pickSlot(this)">Hari ini 09.00–12.00</button>
            <button class="slot" onclick="pickSlot(this)">Hari ini 13.00–17.00</button>
            <button class="slot" onclick="pickSlot(this)">Besok 09.00–12.00</button>
            <button class="slot" onclick="pickSlot(this)">Besok 13.00–17.00</button>
          </div>
        </div>

        <button class="btn btn-green" id="btn-submit-don" style="width:100%;justify-content:center;padding:14px" onclick="submitDon()">
          🎁 Konfirmasi & Proses Donasi
        </button>
      </div>
    </div><!-- end d-don -->

    <!-- Lacak -->
    <div class="inner-page" id="ip-d-lacak">
      <div class="ph"><h2>Lacak Donasi</h2><p>Pantau status pengiriman barang donasimu</p></div>
      <div class="tbox">
        <div class="tsrch">
          <input type="text" id="input-resi" placeholder="Masukkan nomor resi (contoh: CDGOS1234ID)">
          <button class="btn btn-green btn-sm" onclick="showTrack()">Cari</button>
        </div>
        <div id="tr-empty" style="text-align:center;padding:44px;color:var(--text3)">
          <div style="font-size:38px;margin-bottom:10px">📍</div>
          <p>Masukkan nomor resi untuk melihat status pengiriman</p>
        </div>
        <div id="tr-result" class="hide"></div>
      </div>
    </div><!-- end d-lacak -->

    <!-- Profil Donatur — konten dirender oleh JS dari session -->
    <div class="inner-page" id="ip-d-profil">
      <div class="ph"><h2>Profil Saya</h2><p id="d-profil-sub">Kelola informasi akun donaturmu</p></div>
      <div class="wrap" style="max-width:640px">
        <!-- Avatar & Info ringkas -->
        <div class="pcrd">
          <div class="avatar-wrap">
              <div class="pav" id="d-profil-av">👤</div>
              <label class="avatar-upload-btn" for="inp-avatar-d" title="Ganti foto">✏️</label>
              <input type="file" id="inp-avatar-d" accept="image/*" style="display:none" onchange="uploadAvatar(this)">
            </div>
          <div class="pinf">
            <h3 id="d-profil-nama">—</h3>
            <p id="d-profil-email">—</p>
            <span class="tag tg rt2">Donatur Terverifikasi</span>
          </div>
        </div>

        <!-- Form edit -->
        <div class="fcard">
          <div class="fcard-t" style="margin-bottom:17px">✏️ Edit Profil</div>
          <div class="frow">
            <div class="fg2">
              <label>Nama Lengkap</label>
              <input id="d-edit-nama" placeholder="Nama lengkap">
            </div>
            <div class="fg2">
              <label>No. Telepon</label>
              <input id="d-edit-telp" placeholder="0812xxxx">
            </div>
          </div>
          <div class="fg2">
            <label>Email</label>
            <input id="d-edit-email" type="email" placeholder="email@contoh.com" readonly style="background:var(--surf);cursor:not-allowed">
          </div>
          <div class="fg2">
            <label>Alamat</label>
            <input id="d-edit-alamat" placeholder="Alamat lengkap">
          </div>
          <button class="btn btn-green btn-sm" onclick="simpanProfil()" id="btn-simpan-profil-d">💾 Simpan Perubahan</button>
        </div>

        <!-- Ringkasan aktivitas -->
        <div class="fcard">
          <div class="fcard-t" style="margin-bottom:14px">📊 Ringkasan Aktivitas</div>
          <div class="stat-row" style="margin-bottom:0">
            <div class="sc"><label>Total Donasi</label><span id="profil-d-total"><big>—</big></span><small>sejak bergabung</small></div>
            <div class="sc sc-a"><label>Dalam Proses</label><span id="profil-d-proses"><big>—</big></span><small>aktif</small></div>
            <div class="sc"><label>Selesai</label><span id="profil-d-selesai"><big>—</big></span><small>tersalurkan</small></div>
          </div>
        </div>

        <div style="text-align:right;margin-top:10px">
          <button class="btn btn-outline btn-sm" onclick="openModal('modal-ganti-pass')">🔒 Ganti Password</button>
          <button class="btn btn-red btn-sm" onclick="doLogout()">🚪 Keluar Akun</button>
        </div>
      </div>
    </div><!-- end d-profil -->

  </div><!-- end rd -->


  <!-- ════════ ROLE: PENERIMA ════════ -->
  <div id="ry" class="hide">

    <!-- Dashboard Penerima -->
    <div class="inner-page" id="ip-y-home">
      <div class="ph">
        <h2>Dashboard Penerima</h2>
        <p id="y-dash-sub">Selamat datang kembali, <span id="y-greet"></span> 👋</p>
      </div>
      <div class="wrap">
        <div class="stat-row">
          <div class="sc"><label>Total Donasi Diterima</label><span id="stat-y-total"><big>—</big></span><small>sejak bergabung</small></div>
          <div class="sc sc-a"><label>Kebutuhan Aktif</label><span id="stat-y-aktif"><big>—</big></span><small>item terdaftar</small></div>
          <div class="sc sc-b"><label>Perlu Konfirmasi</label><span id="stat-y-konfirm"><big>—</big></span><small>menunggu aksi</small></div>
          <div class="sc"><label>Terpenuhi Bulan Ini</label><span id="stat-y-pct"><big>—</big></span><small>dari target</small></div>
        </div>

        <!-- Tabel Tawaran Masuk -->
        <div class="tcrd" style="margin-bottom:18px">
          <div class="thead">
            <h3>📬 Tawaran Donasi Masuk <span id="nav-tawaran-badge" style="display:none;background:#dc2626;color:#fff;font-size:.68rem;font-weight:700;padding:2px 7px;border-radius:99px;margin-left:6px">0</span></h3>
            <span style="font-size:.8rem;color:var(--text2)">Setujui atau tolak sebelum barang dikirim</span>
          </div>
          <table>
            <thead><tr><th>ID</th><th>Donatur</th><th>Barang</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody id="tbl-y-tawaran">
              <tr><td colspan="5" class="tbl-loading">⏳ Memuat...</td></tr>
            </tbody>
          </table>
        </div>

        <div class="tcrd" style="margin-bottom:18px">
          <div class="thead"><h3>⚡ Perlu Konfirmasi Penerimaan</h3><span class="tag tb" id="lbl-y-konfirm">—</span></div>
          <table>
            <thead><tr><th>ID</th><th>Donatur</th><th>Barang</th><th>No. Resi</th><th>Aksi</th></tr></thead>
            <tbody id="tbl-y-konfirm">
              <tr><td colspan="5" class="tbl-loading">⏳ Memuat data konfirmasi...</td></tr>
            </tbody>
          </table>
        </div>

        <div class="tcrd">
          <div class="thead"><h3>Riwayat Donasi Masuk</h3><button class="btn btn-ghost btn-sm" onclick="exportCSV()">⬇ Export CSV</button></div>
          <table>
            <thead><tr><th>ID</th><th>Donatur</th><th>Barang</th><th>Tanggal</th><th>Status</th><th>Bukti</th></tr></thead>
            <tbody id="tbl-y-riw">
              <tr><td colspan="6" class="tbl-loading">⏳ Memuat riwayat donasi...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end y-home -->

    <!-- Kelola Katalog -->
    <div class="inner-page" id="ip-y-kat">
      <div class="ph"><h2>Kelola Katalog Kebutuhan</h2><p>Tambah, edit, atau hapus daftar kebutuhan yayasanmu</p></div>
      <div class="wrap">
        <div style="display:flex;justify-content:flex-end;margin-bottom:14px">
          <button class="btn btn-green" onclick="toast('Form tambah kebutuhan dibuka')">+ Tambah Kebutuhan Baru</button>
        </div>
        <div class="tcrd">
          <table>
            <thead><tr><th>Barang</th><th>Butuh</th><th>Terkumpul</th><th>Urgensi</th><th>Tampil</th><th>Aksi</th></tr></thead>
            <tbody id="kat-tbl">
              <tr><td>👗 Seragam SD ukuran S–M</td><td>50 pasang</td><td>32 pasang</td><td><select style="padding:4px 7px;font-size:.77rem;border-radius:6px"><option selected>Tinggi</option><option>Sedang</option><option>Rendah</option></select></td><td><label><input type="checkbox" checked onchange="toast(this.checked?'Ditampilkan':'Disembunyikan')"> Aktif</label></td><td><button class="btn btn-red btn-xs" onclick="hapusRow(this)">Hapus</button></td></tr>
              <tr><td>🎒 Tas Sekolah Anak SD</td><td>20 buah</td><td>7 buah</td><td><select style="padding:4px 7px;font-size:.77rem;border-radius:6px"><option selected>Tinggi</option><option>Sedang</option><option>Rendah</option></select></td><td><label><input type="checkbox" checked onchange="toast(this.checked?'Ditampilkan':'Disembunyikan')"> Aktif</label></td><td><button class="btn btn-red btn-xs" onclick="hapusRow(this)">Hapus</button></td></tr>
              <tr><td>📚 Buku Pelajaran Kelas 4–6</td><td>30 buku</td><td>21 buku</td><td><select style="padding:4px 7px;font-size:.77rem;border-radius:6px"><option>Tinggi</option><option selected>Sedang</option><option>Rendah</option></select></td><td><label><input type="checkbox" checked onchange="toast(this.checked?'Ditampilkan':'Disembunyikan')"> Aktif</label></td><td><button class="btn btn-red btn-xs" onclick="hapusRow(this)">Hapus</button></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end y-kat -->

    <!-- Riwayat Donasi -->
    <div class="inner-page" id="ip-y-riw">
      <div class="ph"><h2>Riwayat Donasi</h2><p>Semua donasi yang pernah diterima yayasanmu</p></div>
      <div class="wrap">
        <div class="tcrd">
          <div class="thead"><h3>Semua Donasi Diterima</h3><button class="btn btn-ghost btn-sm" onclick="toast('Mengekspor laporan CSV…')">⬇ Export CSV</button></div>
          <table>
            <thead><tr><th>ID</th><th>Donatur</th><th>Barang</th><th>Tanggal</th><th>Status</th><th>Bukti</th></tr></thead>
            <tbody id="tbl-y-riw-full">
              <tr><td colspan="6" class="tbl-loading">⏳ Memuat riwayat donasi...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end y-riw -->

    <!-- Profil Yayasan — konten dirender oleh JS dari session -->
    <div class="inner-page" id="ip-y-profil">
      <div class="ph"><h2>Profil Yayasan / Lembaga</h2><p id="y-profil-sub">Kelola informasi lembagamu</p></div>
      <div class="wrap" style="max-width:640px">

        <!-- Avatar & Info ringkas -->
        <div class="pcrd">
          <div class="avatar-wrap">
              <div class="pav" id="y-profil-av">🏠</div>
              <label class="avatar-upload-btn" for="inp-avatar-y" title="Ganti foto">✏️</label>
              <input type="file" id="inp-avatar-y" accept="image/*" style="display:none" onchange="uploadAvatar(this)">
            </div>
          <div class="pinf">
            <h3 id="y-profil-nama">—</h3>
            <p id="y-profil-email">—</p>
            <span class="tag ta rt2">Yayasan Terverifikasi</span>
          </div>
        </div>

        <!-- Form edit -->
        <div class="fcard">
          <div class="fcard-t" style="margin-bottom:17px">✏️ Informasi Lembaga</div>
          <div class="fg2">
            <label>Nama Lembaga</label>
            <input id="y-edit-nama" placeholder="Nama yayasan / posko">
          </div>
          <div class="frow">
            <div class="fg2">
              <label>No. Telepon</label>
              <input id="y-edit-telp" placeholder="0812xxxx">
            </div>
            <div class="fg2">
              <label>Email</label>
              <input id="y-edit-email" type="email" placeholder="email" readonly style="background:var(--surf);cursor:not-allowed">
            </div>
          </div>
          <div class="fg2">
            <label>Alamat Lembaga</label>
            <input id="y-edit-alamat" placeholder="Alamat lengkap lembaga">
          </div>
          <div class="fg2">
            <label>Deskripsi Singkat</label>
            <textarea id="y-edit-desc" placeholder="Ceritakan tentang lembaga Anda..." style="width:100%;padding:8px 12px;min-height:80px"></textarea>
          </div>
          <div style="display:flex;gap:9px;margin-top:4px">
            <button class="btn btn-green btn-sm" onclick="simpanProfil()" id="btn-simpan-profil-y">💾 Simpan Perubahan</button>
            <button class="btn btn-ghost btn-sm" onclick="toast('Unggah dokumen legalitas')">📄 Upload SK</button>
          </div>
        </div>

        <!-- Ringkasan aktivitas -->
        <div class="fcard">
          <div class="fcard-t" style="margin-bottom:14px">📊 Ringkasan Lembaga</div>
          <div class="stat-row" style="margin-bottom:0">
            <div class="sc"><label>Donasi Diterima</label><span id="profil-y-total"><big>—</big></span><small>total</small></div>
            <div class="sc sc-a"><label>Kebutuhan Aktif</label><span id="profil-y-aktif"><big>—</big></span><small>item</small></div>
            <div class="sc sc-b"><label>Terpenuhi</label><span id="profil-y-pct"><big>—</big></span><small>bulan ini</small></div>
          </div>
        </div>

        <div style="text-align:right;margin-top:10px">
          <button class="btn btn-outline btn-sm" onclick="openModal('modal-ganti-pass')">🔒 Ganti Password</button>
          <button class="btn btn-red btn-sm" onclick="doLogout()">🚪 Keluar Akun</button>
        </div>
      </div>
      <!-- Legalitas Section -->
      <div class="inner-page" id="ip-y-legal">
        <div class="ph"><h2>📋 Berkas Legalitas</h2><p>Upload dokumen legalitas yayasan untuk verifikasi admin</p></div>
        <div class="wrap">
          <div class="fcard" style="margin-bottom:16px">
            <div class="fcard-t">📂 Dokumen yang Diunggah</div>
            <div id="legalitas-list"><p style="color:var(--text3);text-align:center;padding:16px">⏳ Memuat...</p></div>
          </div>
          <div class="fcard">
            <div class="fcard-t">⬆ Upload Berkas Baru</div>
            <div style="display:grid;gap:14px">
              <?php
              $jenisDoc = ['akta'=>'Akta Pendirian','sk_kemenkumham'=>'SK Kemenkumham','npwp'=>'NPWP','foto_gedung'=>'Foto Gedung/Kantor','lainnya'=>'Dokumen Lainnya'];
              foreach($jenisDoc as $kode => $nama): ?>
              <div style="background:#f8fdf9;border-radius:8px;padding:14px">
                <label style="font-weight:600;font-size:.875rem;display:block;margin-bottom:8px"><?= $nama ?></label>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                  <input type="file" id="file-<?= $kode ?>" accept=".pdf,.jpg,.jpeg,.png"
                    onchange="uploadLegalitas(this,'<?= $kode ?>')"
                    style="font-size:.8rem;flex:1;min-width:200px">
                  <button id="btn-upload-<?= $kode ?>" class="btn btn-ghost btn-sm" onclick="document.getElementById('file-<?= $kode ?>').click()">⬆ Upload</button>
                </div>
                <input id="ket-<?= $kode ?>" type="text" placeholder="Keterangan (opsional)"
                  style="margin-top:8px;width:100%;padding:7px 10px;border:1px solid #d1fae5;border-radius:6px;font-size:.82rem;font-family:inherit">
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div><!-- end y-legal -->
    </div><!-- end y-profil -->

  </div><!-- end ry -->



  <!-- ════════ ROLE: ADMIN ════════ -->
  <div id="ra" class="hide">

    <!-- Dashboard Admin -->
    <div class="inner-page" id="ip-a-home">
      <div class="ph" style="background:linear-gradient(135deg,#1a1a2e,#16213e)">
        <h2>Dashboard Admin</h2>
        <p>Admin CareDrop · Pantau &amp; kelola seluruh aktivitas platform</p>
      </div>
      <div class="wrap">
        <div class="stat-row">
          <div class="sc"><label>Total Pengguna</label><span id="stat-a-user"><big>—</big></span><small>terdaftar</small></div>
          <div class="sc sc-a"><label>Donasi Aktif</label><span id="stat-a-aktif"><big>—</big></span><small>sedang berjalan</small></div>
          <div class="sc sc-b"><label>Penerima Terverif.</label><span id="stat-a-verif"><big>—</big></span><small>yayasan/posko</small></div>
          <div class="sc"><label>Total Barang</label><span id="stat-a-barang"><big>—</big></span><small>tersalurkan</small></div>
        </div>

        <div class="tcrd" style="margin-bottom:18px">
          <div class="thead"><h3>⚡ Permintaan Verifikasi Penerima</h3><span class="tag ta" id="lbl-a-pending">—</span></div>
          <table>
            <thead><tr><th>Nama</th><th>Email</th><th>No. Telp</th><th>Daftar</th><th>Aksi</th></tr></thead>
            <tbody id="tbl-a-pending">
              <tr><td colspan="5" class="tbl-loading">⏳ Memuat data verifikasi...</td></tr>
            </tbody>
          </table>
        </div>

        <div class="tcrd">
          <div class="thead"><h3>Donasi Terbaru</h3><button class="btn btn-green btn-sm" onclick="nav('a-donasi')">Lihat Semua</button></div>
          <table>
            <thead><tr><th>ID</th><th>Donatur</th><th>Penerima</th><th>Barang</th><th>Status</th></tr></thead>
            <tbody id="tbl-a-riw">
              <tr><td colspan="5" class="tbl-loading">⏳ Memuat data donasi...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end a-home -->

    <!-- Kelola Pengguna -->
    <div class="inner-page" id="ip-a-users">
      <div class="ph" style="background:linear-gradient(135deg,#1a1a2e,#16213e)">
        <h2>Kelola Pengguna</h2>
        <p>Lihat, nonaktifkan, atau hapus akun pengguna platform</p>
      </div>
      <div class="wrap">
        <div class="tcrd">
          <div class="thead">
            <h3>Semua Pengguna</h3>
            <input type="text" placeholder="Cari nama / email..." style="padding:7px 12px;border:1px solid var(--bord);border-radius:8px;font-size:.85rem;font-family:inherit" oninput="toast('Mencari: '+this.value)">
          </div>
          <table>
            <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Daftar</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <tr><td>Sabrina Salsabila</td><td>sabrina@email.com</td><td><span class="tag tg">Donatur</span></td><td>Jan 2026</td><td><span class="tag tg">Aktif</span></td><td><button class="btn btn-ghost btn-xs" onclick="toast('Detail akun dibuka')">Detail</button></td></tr>
              <tr><td>Panti Asuhan Al-Ikhlas</td><td>alikhlas@yayasan.id</td><td><span class="tag ta">Penerima</span></td><td>Feb 2026</td><td><span class="tag tg">Aktif</span></td><td><button class="btn btn-ghost btn-xs" onclick="toast('Detail akun dibuka')">Detail</button></td></tr>
              <tr><td>Rumah Belajar Cahaya</td><td>cahaya@posko.id</td><td><span class="tag ta">Penerima</span></td><td>Mei 2026</td><td><span class="tag ta">Menunggu</span></td><td><button class="btn btn-ghost btn-xs" onclick="toast('Detail akun dibuka')">Detail</button></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end a-users -->

    <!-- Kelola Donasi -->
    <div class="inner-page" id="ip-a-donasi">
      <div class="ph" style="background:linear-gradient(135deg,#1a1a2e,#16213e)">
        <h2>Semua Donasi</h2>
        <p>Pantau dan kelola seluruh transaksi donasi</p>
      </div>
      <div class="wrap">
        <div class="tcrd">
          <div class="thead"><h3>Daftar Donasi</h3><span class="tag tg">38 aktif</span></div>
          <table>
            <thead><tr><th>ID</th><th>Donatur</th><th>Penerima</th><th>Barang</th><th>Tanggal</th><th>Status</th></tr></thead>
            <tbody>
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260508-021</td><td>Sabrina S.</td><td>Al-Ikhlas</td><td>5 Seragam SD</td><td>8 Mei 2026</td><td><span class="tag tg">Selesai</span></td></tr>
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260507-018</td><td>Andi W.</td><td>Peduli Anak NTB</td><td>3 Buku SD</td><td>7 Mei 2026</td><td><span class="tag ta">Proses</span></td></tr>
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260506-015</td><td>Rini H.</td><td>Al-Ikhlas</td><td>2 Tas Sekolah</td><td>6 Mei 2026</td><td><span class="tag tg">Selesai</span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end a-donasi -->

    <!-- Verifikasi Penerima -->
    <div class="inner-page" id="ip-a-verif">
      <div class="ph" style="background:linear-gradient(135deg,#1a1a2e,#16213e)">
        <h2>Verifikasi Penerima</h2>
        <p>Tinjau dan verifikasi pendaftaran yayasan &amp; posko baru</p>
      </div>
      <div class="wrap">
        <div class="tcrd">
          <div class="thead"><h3>Menunggu Verifikasi</h3><span class="tag ta">3 pending</span></div>
          <table>
            <thead><tr><th>Nama</th><th>Jenis</th><th>Kota</th><th>Kontak</th><th>Dok. Legalitas</th><th>Aksi</th></tr></thead>
            <tbody>
              <tr>
                <td>Rumah Belajar Cahaya</td><td>Posko Pendidikan</td><td>Mataram</td><td>0812-0001-1111</td>
                <td><button class="btn btn-ghost btn-xs" onclick="toast('Membuka dokumen SK...')">📄 Lihat SK</button></td>
                <td style="display:flex;gap:6px;padding:10px 0">
                  <button class="btn btn-green btn-xs" onclick="toast('✅ Rumah Belajar Cahaya diverifikasi')">Setujui</button>
                  <button class="btn btn-red btn-xs" onclick="toast('❌ Ditolak')">Tolak</button>
                </td>
              </tr>
              <tr>
                <td>Yayasan Tunas Bangsa</td><td>Panti Asuhan</td><td>Praya</td><td>0813-0002-2222</td>
                <td><button class="btn btn-ghost btn-xs" onclick="toast('Membuka dokumen SK...')">📄 Lihat SK</button></td>
                <td style="display:flex;gap:6px;padding:10px 0">
                  <button class="btn btn-green btn-xs" onclick="toast('✅ Yayasan Tunas Bangsa diverifikasi')">Setujui</button>
                  <button class="btn btn-red btn-xs" onclick="toast('❌ Ditolak')">Tolak</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end a-verif -->

    <!-- Laporan -->
    <div class="inner-page" id="ip-a-lap">
      <div class="ph" style="background:linear-gradient(135deg,#1a1a2e,#16213e)">
        <h2>Laporan Platform</h2>
        <p>Ringkasan statistik dan aktivitas CareDrop</p>
      </div>
      <div class="wrap">
        <div class="stat-row">
          <div class="sc"><label>Donasi Bulan Ini</label><big>87</big><small>transaksi</small></div>
          <div class="sc sc-a"><label>Nilai Barang Est.</label><big>Rp 24jt</big><small>perkiraan nilai</small></div>
          <div class="sc sc-b"><label>Penerima Aktif</label><big>38</big><small>yayasan/posko</small></div>
          <div class="sc"><label>Tingkat Tepat Sasaran</label><big>97%</big><small>sesuai kebutuhan</small></div>
        </div>
        <div class="fcard">
          <div class="fcard-t" style="margin-bottom:14px">📊 Ekspor Laporan</div>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button class="btn btn-green btn-sm" onclick="toast('📥 Mengunduh laporan donasi bulan ini...')">📥 Laporan Bulan Ini (PDF)</button>
            <button class="btn btn-ghost btn-sm" onclick="toast('📥 Mengunduh rekap tahunan...')">📥 Rekap Tahunan (Excel)</button>
            <button class="btn btn-ghost btn-sm" onclick="toast('📧 Laporan dikirim ke email admin')">📧 Kirim ke Email</button>
          </div>
        </div>
      </div>
    </div><!-- end a-lap -->

    <!-- Profil Admin -->
    <div class="inner-page" id="ip-a-profil">
      <div class="ph" style="background:linear-gradient(135deg,#1a1a2e,#16213e)">
        <h2>Profil Admin</h2>
        <p>Kelola informasi akun administrator</p>
      </div>
      <div class="wrap" style="max-width:640px">
        <div class="pcrd">
          <div class="pav" id="a-profil-av" style="background:linear-gradient(135deg,#1a1a2e,#16213e)">⚙️</div>
          <div class="pinf">
            <h3 id="a-profil-nama">—</h3>
            <p id="a-profil-email">—</p>
            <span class="tag tb rt2">Administrator Platform</span>
          </div>
        </div>
        <div class="fcard">
          <div class="fcard-t" style="margin-bottom:17px">✏️ Edit Akun Admin</div>
          <div class="frow">
            <div class="fg2">
              <label>Nama Lengkap</label>
              <input id="a-edit-nama" placeholder="Nama admin">
            </div>
            <div class="fg2">
              <label>No. Telepon</label>
              <input id="a-edit-telp" placeholder="0812xxxx">
            </div>
          </div>
          <div class="fg2">
            <label>Email</label>
            <input id="a-edit-email" type="email" readonly style="background:var(--surf);cursor:not-allowed">
          </div>
          <button class="btn btn-green btn-sm" onclick="simpanProfil()">💾 Simpan</button>
        </div>
        <div style="text-align:right;margin-top:10px">
          <button class="btn btn-red btn-sm" onclick="doLogout()">🚪 Keluar Akun</button>
        </div>
      </div>
    </div><!-- end a-profil -->

  </div><!-- end ra -->

  <!-- ════ MODAL: Input Nomor Resi ════ -->
  <div class="modal-overlay hide" id="modal-input-resi" onclick="closeModal('modal-input-resi')">
    <div class="modal-box" onclick="event.stopPropagation()" style="max-width:420px">
      <div class="modal-head">
        <h3>📦 Input Nomor Resi Pengiriman</h3>
        <button onclick="closeModal('modal-input-resi')">✕</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="resi-donasi-id">
        <p style="font-size:.85rem;color:var(--text2);margin-bottom:16px">
          Tawaran donasi kamu telah <strong style="color:#16a34a">disetujui yayasan</strong>!<br>
          Kirim barang dan masukkan nomor resi di bawah.
        </p>
        <div class="fg2" style="margin-bottom:12px">
          <label>Pilih Kurir</label>
          <select id="inp-kurir-resi" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1fae5;font-family:inherit">
            <option value="jne">JNE</option>
            <option value="jnt">J&T Express</option>
            <option value="sicepat">SiCepat</option>
            <option value="anteraja">Anteraja</option>
            <option value="pos">Pos Indonesia</option>
            <option value="gosend">GoSend</option>
            <option value="grab">GrabExpress</option>
            <option value="tiki">TIKI</option>
          </select>
        </div>
        <div class="fg2" style="margin-bottom:16px">
          <label>Nomor Resi / Tracking</label>
          <input type="text" id="inp-no-resi" placeholder="Contoh: JNE123456789"
            style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1fae5;font-family:inherit;font-size:.9rem;text-transform:uppercase"
            oninput="this.value=this.value.toUpperCase()">
        </div>
        <button class="btn btn-green" id="btn-submit-resi" style="width:100%;justify-content:center" onclick="submitResi()">
          📦 Konfirmasi Pengiriman
        </button>
      </div>
    </div>
  </div>

  <!-- ════ MODAL: Detail Donasi ════ -->
  <div class="modal-overlay hide" id="modal-detail" onclick="closeModal('modal-detail')">
    <div class="modal-box" onclick="event.stopPropagation()">
      <div class="modal-head">
        <h3>📦 Detail Donasi</h3>
        <button onclick="closeModal('modal-detail')">✕</button>
      </div>
      <div class="modal-body" id="modal-detail-body">
        <p style="color:var(--text3);text-align:center">Memuat...</p>
      </div>
    </div>
  </div>

  <!-- ════ MODAL: E-Sertifikat ════ -->
  <div class="modal-overlay hide" id="modal-sertif" onclick="closeModal('modal-sertif')">
    <div class="modal-box" onclick="event.stopPropagation()" style="max-width:520px">
      <div class="modal-head">
        <h3>🏅 E-Sertifikat Donasi</h3>
        <button onclick="closeModal('modal-sertif')">✕</button>
      </div>
      <div class="modal-body">
        <div id="sertif-card" class="sertif-card">
          <div class="sertif-logo">🌿 CareDrop</div>
          <div class="sertif-title">SERTIFIKAT DONASI</div>
          <p class="sertif-sub">Diberikan kepada:</p>
          <div class="sertif-nama" id="sertif-nama">—</div>
          <p class="sertif-body" id="sertif-body">—</p>
          <div class="sertif-footer">
            <div>
              <div style="font-size:.7rem;color:#666">Tanggal</div>
              <div style="font-weight:600" id="sertif-tgl">—</div>
            </div>
            <div>
              <div style="font-size:.7rem;color:#666">No. Donasi</div>
              <div style="font-weight:600;font-size:.8rem" id="sertif-id">—</div>
            </div>
          </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:14px">
          <button class="btn btn-green" style="flex:1;justify-content:center" onclick="downloadSertif()">⬇ Unduh PDF</button>
          <button class="btn btn-ghost" style="flex:1;justify-content:center" onclick="closeModal('modal-sertif')">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ════ MODAL: Ganti Password ════ -->
  <div class="modal-overlay hide" id="modal-ganti-pass" onclick="closeModal('modal-ganti-pass')">
    <div class="modal-box" onclick="event.stopPropagation()" style="max-width:420px">
      <div class="modal-head">
        <h3>🔒 Ganti Password</h3>
        <button onclick="closeModal('modal-ganti-pass')">✕</button>
      </div>
      <div class="modal-body">
        <div class="fg2" style="margin-bottom:12px">
          <label>Password Lama</label>
          <input type="password" id="pass-lama" placeholder="Masukkan password saat ini" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1fae5">
        </div>
        <div class="fg2" style="margin-bottom:12px">
          <label>Password Baru</label>
          <input type="password" id="pass-baru" placeholder="Minimal 6 karakter" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1fae5">
        </div>
        <div class="fg2" style="margin-bottom:16px">
          <label>Konfirmasi Password Baru</label>
          <input type="password" id="pass-baru2" placeholder="Ulangi password baru" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1fae5">
        </div>
        <button class="btn btn-green" id="btn-ganti-pass" style="width:100%;justify-content:center" onclick="gantiPassword()">🔒 Simpan Password Baru</button>
      </div>
    </div>
  </div>

</div><!-- end pg-app -->

<!-- JavaScript -->
<script>
// Data session dari PHP (null jika belum login)
const PHP_SESSION = <?= json_encode($session_user) ?>;
</script>
<script src="app.js"></script>

</body>
</html>
