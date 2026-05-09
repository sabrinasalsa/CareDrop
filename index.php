<?php
session_start();

define('APP_NAME',    'CareDrop');
define('APP_VERSION', '1.0.0');
define('APP_DESC',    'Layanan Donasi Barang Layak Pakai Sesuaian Kebutuhan Penerima');

// Jika sudah login via session, siapkan data untuk JS
$session_user = null;
if (isset($_SESSION['id'], $_SESSION['role'], $_SESSION['nama'])) {
    $session_user = [
        'id'    => $_SESSION['id'],
        'nama'  => $_SESSION['nama'],
        'email' => $_SESSION['email'] ?? '',
        'role'  => $_SESSION['role'],
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
    <div class="user-pill" onclick="toast('Pengaturan akun')">
      <div class="uav" id="uav">?</div>
      <div>
        <div class="uname" id="uname">—</div>
        <div class="urole" id="urole">—</div>
      </div>
    </div>
  </header>

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
          <div class="sc"><label>Total Donasi</label><big>7</big><small>sejak bergabung</small></div>
          <div class="sc sc-a"><label>Sedang Berjalan</label><big>2</big><small>dalam proses</small></div>
          <div class="sc"><label>Selesai</label><big>5</big><small>tersalurkan</small></div>
          <div class="sc"><label>E-Sertifikat</label><big>5</big><small>diterbitkan</small></div>
        </div>
        <div class="tcrd">
          <div class="thead">
            <h3>Donasi Terbaru</h3>
            <button class="btn btn-green btn-sm" onclick="nav('d-kat')">+ Donasi Baru</button>
          </div>
          <table>
            <thead><tr><th>ID</th><th>Barang</th><th>Yayasan</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <tr>
                <td style="font-family:monospace;font-size:.77rem">CDR-20260404-007</td>
                <td>3 Buku Pelajaran SD</td>
                <td>Yayasan Peduli Anak NTB</td>
                <td>4 Apr 2026</td>
                <td><span class="tag ta">Dalam Perjalanan</span></td>
                <td><button class="btn btn-ghost btn-xs" onclick="nav('d-lacak');showTrack()">Lacak</button></td>
              </tr>
              <tr>
                <td style="font-family:monospace;font-size:.77rem">CDR-20260401-001</td>
                <td>5 Seragam SD</td>
                <td>Panti Asuhan Al-Ikhlas</td>
                <td>1 Apr 2026</td>
                <td><span class="tag tg">Selesai</span></td>
                <td><button class="btn btn-ghost btn-xs" onclick="nav('d-lacak');showTrack()">Lihat</button></td>
              </tr>
              <tr>
                <td style="font-family:monospace;font-size:.77rem">CDR-20260320-003</td>
                <td>Tas Sekolah 2 buah</td>
                <td>Panti Asuhan Al-Ikhlas</td>
                <td>20 Mar 2026</td>
                <td><span class="tag tg">Selesai</span></td>
                <td><button class="btn btn-ghost btn-xs">Lihat</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end d-home -->

    <!-- Katalog -->
    <div class="inner-page" id="ip-d-kat">
      <div class="ph"><h2>Katalog Kebutuhan</h2><p>Pilih barang berdasarkan kebutuhan nyata yayasan</p></div>
      <div class="fbar">
        <div class="srch"><input type="text" placeholder="Cari barang, yayasan…" oninput="filterKat(this.value)"></div>
        <button class="chip on" onclick="setChip(this,'semua')">Semua</button>
        <button class="chip" onclick="setChip(this,'pakaian')">👗 Pakaian</button>
        <button class="chip" onclick="setChip(this,'buku')">📚 Buku</button>
        <button class="chip" onclick="setChip(this,'elektronik')">💻 Elektronik</button>
        <button class="chip" onclick="setChip(this,'perabot')">🪑 Perabot</button>
      </div>
      <div class="kg" id="kat-grid"></div>
    </div><!-- end d-kat -->

    <!-- Form Donasi -->
    <div class="inner-page" id="ip-d-don">
      <div class="ph"><h2>Form Donasi Barang</h2><p>Lengkapi data untuk mendaftarkan barang donasimu</p></div>
      <div class="form-outer">
        <div class="fcard">
          <div class="fcard-t"><span class="sn">1</span>Data Donatur</div>
          <div class="frow">
            <div class="fg2"><label>Nama Lengkap</label><input type="text" value="Sabrina Salsabila"></div>
            <div class="fg2"><label>No. Telepon</label><input type="tel" placeholder="08xx-xxxx-xxxx"></div>
          </div>
          <div class="fg2"><label>Email</label><input type="email" value="sabrina@email.com"></div>
        </div>

        <div class="fcard">
          <div class="fcard-t"><span class="sn">2</span>Detail Barang</div>
          <div class="frow">
            <div class="fg2">
              <label>Kategori</label>
              <select>
                <option>— Pilih —</option>
                <option selected>Pakaian Layak Pakai</option>
                <option>Buku &amp; Alat Tulis</option>
                <option>Elektronik</option>
                <option>Perabot</option>
                <option>Lainnya</option>
              </select>
            </div>
            <div class="fg2"><label>Jumlah</label><input type="number" value="5" min="1"></div>
          </div>
          <div class="fg2">
            <label>Deskripsi Kondisi Barang</label>
            <textarea>Seragam SD ukuran M, kondisi baik, sudah dicuci bersih.</textarea>
          </div>
          <div class="fg2">
            <label>Foto Barang</label>
            <div class="up-box" onclick="toast('Pilih foto dari perangkatmu')">
              <div class="ui">📷</div>
              <p>Klik untuk unggah foto</p>
              <span>JPG, PNG – maks 5MB</span>
            </div>
          </div>
        </div>

        <div class="fcard">
          <div class="fcard-t"><span class="sn">3</span>Yayasan Tujuan</div>
          <div class="fg2">
            <label>Pilih Yayasan</label>
            <select id="pilih-yayasan">
              <option>— Pilih Yayasan —</option>
              <option selected>Panti Asuhan Al-Ikhlas, Mataram</option>
              <option>Yayasan Peduli Anak NTB, Lombok</option>
              <option>Rumah Singgah Harapan, Selong</option>
              <option>Panti Asuhan Nur Hidayah, Praya</option>
            </select>
          </div>
        </div>

 <div class="fcard">
          <div class="fcard-t"><span class="sn">4</span>Pengiriman & Logistik Terintegrasi</div>
          <div class="frow">
            <div class="fg2">
              <label>Kota Asal Penjemputan</label>
              <select id="kota-asal">
                <option value="Mataram" selected>Mataram</option>
                <option value="Praya">Praya</option>
                <option value="Selong">Selong</option>
              </select>
            </div>
            <div class="fg2">
              <label>Estimasi Berat/Dimensi (Kg)</label>
              <input type="number" id="berat-barang" value="2" min="1" max="100">
            </div>
          </div>
          <div class="fg2">
            <label>Alamat Lengkap Penjemputan</label>
            <input type="text" placeholder="Jalan, no rumah, RT/RW">
          </div>
          
          <div class="fg2" style="margin-top:20px; padding-top:15px; border-top:1px dashed var(--bord);">
            <button class="btn btn-outline" style="width:100%; justify-content:center" onclick="hitungEstimasiPengiriman()">
              📍 Cari Kurir & Estimasi Ongkir
            </button>
          </div>
          
          <div id="courier-result" style="margin-top: 15px;"></div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:9px">
          <button class="btn btn-ghost" onclick="nav('d-kat')">Batal</button>
          <button class="btn btn-green" onclick="submitDon()">🎁 Daftarkan Donasi</button>
        </div>
      </div> </div><div class="inner-page" id="ip-d-lacak">
      <div class="ph">
        <h2>Lacak Pengiriman</h2>
        <p>Pantau perjalanan barang donasi via Nomor Resi Terintegrasi</p>
      </div>
      <div class="tbox">
        <div class="tsrch">
          <input type="text" id="input-resi" placeholder="Masukkan Nomor Resi (cth: CDJNT9281ID)">
          <button class="btn btn-green" onclick="showTrack()">Lacak Resi</button>
        </div>
        <div id="tr-empty" style="text-align:center;padding:42px 0">
          <div style="font-size:42px;margin-bottom:9px">🚚</div>
          <p style="font-weight:700;color:var(--g1)">Lacak Otomatis</p>
          <p style="font-size:.84rem;color:var(--text2);margin-top:3px">Didukung oleh JNE, J&T, GoSend, GrabExpress, & Pos Indonesia</p>
        </div>
        <div id="tr-result" class="hide">
          </div>
      </div>

    <!-- Profil Donatur -->
    <div class="inner-page" id="ip-d-profil">
      <div class="ph"><h2>Profil Saya</h2></div>
      <div class="wrap" style="max-width:620px">
        <div class="pcrd">
          <div class="pav">👤</div>
          <div class="pinf">
            <h3>Sabrina Salsabila</h3>
            <p>sabrina@email.com · 0812-3456-7890</p>
            <span class="tag tg rt2">Donatur Terverifikasi</span>
          </div>
        </div>
        <div class="fcard">
          <div class="fcard-t" style="margin-bottom:17px">Edit Profil</div>
          <div class="frow">
            <div class="fg2"><label>Nama Lengkap</label><input value="Sabrina Salsabila"></div>
            <div class="fg2"><label>No. Telepon</label><input value="0812-3456-7890"></div>
          </div>
          <div class="fg2"><label>Alamat Utama</label><input value="Jl. Majapahit No. 45, Mataram"></div>
          <button class="btn btn-green btn-sm" onclick="toast('Profil diperbarui!')">Simpan</button>
        </div>
        <div style="text-align:right;margin-top:10px">
          <button class="btn btn-red btn-sm" onclick="doLogout()">Keluar Akun</button>
        </div>
      </div>
    </div><!-- end d-profil -->

  </div><!-- end rd -->


  <!-- ════════ ROLE: PENERIMA ════════ -->
  <div id="ry" class="hide">

    <!-- Dashboard Penerima -->
    <div class="inner-page" id="ip-y-home">
      <div class="ph"><h2>Dashboard Penerima</h2><p>Panti Asuhan Al-Ikhlas · Mataram, NTB</p></div>
      <div class="wrap">
        <div class="stat-row">
          <div class="sc"><label>Total Donasi Diterima</label><big>142</big><small>sejak bergabung</small></div>
          <div class="sc sc-a"><label>Kebutuhan Aktif</label><big>4</big><small>item terdaftar</small></div>
          <div class="sc sc-b"><label>Perlu Konfirmasi</label><big>2</big><small>menunggu aksi</small></div>
          <div class="sc"><label>Terpenuhi Bulan Ini</label><big>74%</big><small>dari target</small></div>
        </div>

        <div class="tcrd" style="margin-bottom:18px">
          <div class="thead"><h3>⚡ Perlu Konfirmasi Penerimaan</h3><span class="tag tb">2 menunggu</span></div>
          <table>
            <thead><tr><th>ID</th><th>Donatur</th><th>Barang</th><th>Estimasi Tiba</th><th>Aksi</th></tr></thead>
            <tbody>
              <tr>
                <td style="font-family:monospace;font-size:.76rem">CDR-20260404-007</td>
                <td>Andi Wijaya</td><td>3 Buku Pelajaran SD</td><td>Hari ini ~11.30</td>
                <td><button class="btn btn-green btn-xs" onclick="konfirm(this)">📸 Konfirmasi Terima</button></td>
              </tr>
              <tr>
                <td style="font-family:monospace;font-size:.76rem">CDR-20260405-012</td>
                <td>Rini Hartati</td><td>2 Tas Sekolah</td><td>Hari ini ~14.00</td>
                <td><button class="btn btn-green btn-xs" onclick="konfirm(this)">📸 Konfirmasi Terima</button></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="tcrd">
          <div class="thead"><h3>Katalog Kebutuhan Aktif</h3><button class="btn btn-green btn-sm" onclick="nav('y-kat')">Kelola Katalog</button></div>
          <table>
            <thead><tr><th>Barang</th><th>Dibutuhkan</th><th>Terkumpul</th><th>Urgensi</th></tr></thead>
            <tbody>
              <tr><td>👗 Seragam SD ukuran S–M</td><td>50 pasang</td><td>32 pasang</td><td><span class="tag tr">Tinggi</span></td></tr>
              <tr><td>🎒 Tas Sekolah Anak SD</td><td>20 buah</td><td>7 buah</td><td><span class="tag tr">Tinggi</span></td></tr>
              <tr><td>📚 Buku Pelajaran Kelas 4–6</td><td>30 buku</td><td>21 buku</td><td><span class="tag ta">Sedang</span></td></tr>
              <tr><td>👟 Sepatu Sekolah Anak</td><td>25 pasang</td><td>25 pasang</td><td><span class="tag tg">Terpenuhi</span></td></tr>
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
            <tbody>
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260401-001</td><td>Sabrina Salsabila</td><td>5 Seragam SD</td><td>1 Apr 2026</td><td><span class="tag tg">Selesai</span></td><td><button class="btn btn-ghost btn-xs" onclick="toast('Membuka foto bukti…')">📸 Lihat</button></td></tr>
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260328-009</td><td>Dian Permata</td><td>4 Buku Cerita Anak</td><td>28 Mar 2026</td><td><span class="tag tg">Selesai</span></td><td><button class="btn btn-ghost btn-xs" onclick="toast('Membuka foto bukti…')">📸 Lihat</button></td></tr>
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260404-007</td><td>Andi Wijaya</td><td>3 Buku Pelajaran SD</td><td>4 Apr 2026</td><td><span class="tag ta">Dalam Perjalanan</span></td><td>—</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end y-riw -->

    <!-- Profil Yayasan -->
    <div class="inner-page" id="ip-y-profil">
      <div class="ph"><h2>Profil Yayasan</h2></div>
      <div class="wrap" style="max-width:620px">
        <div class="pcrd">
          <div class="pav">🏠</div>
          <div class="pinf">
            <h3>Panti Asuhan Al-Ikhlas</h3>
            <p>alikhlas@yayasan.id · (0370) 632-xxx</p>
            <span class="tag ta rt2">Yayasan Terverifikasi</span>
          </div>
        </div>
        <div class="fcard">
          <div class="fcard-t" style="margin-bottom:17px">Informasi Lembaga</div>
          <div class="fg2"><label>Nama Lembaga</label><input value="Panti Asuhan Al-Ikhlas"></div>
          <div class="frow">
            <div class="fg2"><label>Telepon</label><input value="(0370) 632-xxx"></div>
            <div class="fg2"><label>Email</label><input value="alikhlas@yayasan.id"></div>
          </div>
          <div class="fg2"><label>Alamat</label><input value="Jl. Pejanggik No. 12, Mataram, NTB"></div>
          <div class="fg2"><label>Deskripsi</label><textarea>Panti asuhan yang mengasuh 45 anak yatim piatu di Mataram. Aktif sejak 1998.</textarea></div>
          <div style="display:flex;gap:9px;margin-top:4px">
            <button class="btn btn-green btn-sm" onclick="toast('Profil diperbarui!')">Simpan</button>
            <button class="btn btn-ghost btn-sm" onclick="toast('Unggah dokumen legalitas')">📄 Upload SK</button>
          </div>
        </div>
        <div style="text-align:right;margin-top:10px">
          <button class="btn btn-red btn-sm" onclick="doLogout()">Keluar Akun</button>
        </div>
      </div>
    </div><!-- end y-profil -->

  </div><!-- end ry -->



  <!-- ════════ ROLE: ADMIN ════════ -->
  <div id="ra" class="hide">

    <!-- Dashboard Admin -->
    <div class="inner-page" id="ip-a-home">
      <div class="ph" style="background:linear-gradient(135deg,#1a1a2e,#16213e)">
        <h2>Dashboard Admin</h2>
        <p>Admin CareDrop · Pantau & kelola seluruh aktivitas platform</p>
      </div>
      <div class="wrap">
        <div class="stat-row">
          <div class="sc"><label>Total Pengguna</label><big>412</big><small>terdaftar</small></div>
          <div class="sc sc-a"><label>Donasi Aktif</label><big>38</big><small>sedang berjalan</small></div>
          <div class="sc sc-b"><label>Penerima Terverif.</label><big>38</big><small>yayasan/posko</small></div>
          <div class="sc"><label>Total Barang</label><big>1.240</big><small>tersalurkan</small></div>
        </div>

        <div class="tcrd" style="margin-bottom:18px">
          <div class="thead"><h3>⚡ Permintaan Verifikasi Penerima</h3><span class="tag ta">3 menunggu</span></div>
          <table>
            <thead><tr><th>Nama</th><th>Jenis</th><th>Kota</th><th>Daftar</th><th>Aksi</th></tr></thead>
            <tbody>
              <tr><td>Rumah Belajar Cahaya</td><td>Posko Pendidikan</td><td>Mataram</td><td>2 Mei 2026</td><td><button class="btn btn-green btn-xs" onclick="toast('✅ Akun diverifikasi')">Verifikasi</button></td></tr>
              <tr><td>Yayasan Tunas Bangsa</td><td>Panti Asuhan</td><td>Praya</td><td>4 Mei 2026</td><td><button class="btn btn-green btn-xs" onclick="toast('✅ Akun diverifikasi')">Verifikasi</button></td></tr>
              <tr><td>Posko Bencana NTB</td><td>Posko Darurat</td><td>Selong</td><td>8 Mei 2026</td><td><button class="btn btn-green btn-xs" onclick="toast('✅ Akun diverifikasi')">Verifikasi</button></td></tr>
            </tbody>
          </table>
        </div>

        <div class="tcrd">
          <div class="thead"><h3>Donasi Terbaru</h3><button class="btn btn-green btn-sm" onclick="nav('a-donasi')">Lihat Semua</button></div>
          <table>
            <thead><tr><th>ID</th><th>Donatur</th><th>Penerima</th><th>Barang</th><th>Status</th></tr></thead>
            <tbody>
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260508-021</td><td>Sabrina S.</td><td>Al-Ikhlas</td><td>5 Seragam SD</td><td><span class="tag tg">Selesai</span></td></tr>
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260507-018</td><td>Andi W.</td><td>Peduli Anak NTB</td><td>3 Buku SD</td><td><span class="tag ta">Proses</span></td></tr>
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
        <p>Tinjau dan verifikasi pendaftaran yayasan & posko baru</p>
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

  </div><!-- end ra -->

</div><!-- end pg-app -->

<!-- JavaScript -->
<script>
// Data session dari PHP (null jika belum login)
const PHP_SESSION = <?= json_encode($session_user) ?>;
</script>
<script src="app.js"></script>

</body>
</html>