<?php
/**
 * CareDrop – index.php
 * Entry point utama aplikasi CareDrop
 * Rekayasa Perangkat Lunak – Universitas Mataram 2026
 */

// Konfigurasi dasar (dapat diperluas ke koneksi DB)
define('APP_NAME',    'CareDrop');
define('APP_VERSION', '1.0.0');
define('APP_DESC',    'Layanan Donasi Barang Layak Pakai Sesuaian Kebutuhan Penerima');

// Simulasi session login (di produksi pakai PHP session/database)
$demo_accounts = [
    'donatur' => [
        'name'  => 'Sabrina Salsabila',
        'email' => 'sabrina@email.com',
        'role'  => 'donatur',
    ],
    'yayasan' => [
        'name'  => 'Panti Asuhan Al-Ikhlas',
        'email' => 'alikhlas@yayasan.id',
        'role'  => 'yayasan',
    ],
    'relawan' => [
        'name'  => 'Budi Santoso',
        'email' => 'budi.relawan@email.com',
        'role'  => 'relawan',
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

<!-- ════════════════════════════════════════════
     LANDING PAGE
     ════════════════════════════════════════════ -->
<div id="pg-landing">
  <nav class="land-nav">
    <div class="logo" onclick="show('pg-landing')">Care<em>Drop</em></div>
    <div style="display:flex;gap:7px">
      <button class="btn btn-ghost btn-sm" onclick="goLogin()">Masuk</button>
      <button class="btn btn-green btn-sm" onclick="goLogin()">Daftar Gratis</button>
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
        <button class="btn btn-ow" onclick="goLogin('yayasan')">🏠 Daftarkan Yayasan</button>
        <button class="btn btn-ow" style="border-color:rgba(234,88,12,.5);color:#fdba74" onclick="goLogin('relawan')">🚗 Jadi Relawan</button>
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
      <div class="fc"><div class="fc-i">🏠</div><h3>Yayasan / Panti Asuhan</h3><p>Buat katalog kebutuhan spesifik, konfirmasi donasi masuk, dan terima laporan otomatis.</p></div>
      <div class="fc"><div class="fc-i">🚗</div><h3>Relawan Pengantar</h3><p>Ambil tugas penjemputan, navigasi GPS ke lokasi donatur, antar barang ke yayasan.</p></div>
      <div class="fc"><div class="fc-i">📜</div><h3>E-Sertifikat</h3><p>Foto bukti + sertifikat elektronik diterbitkan otomatis saat yayasan konfirmasi penerimaan.</p></div>
    </div>
  </div>
</div><!-- end pg-landing -->


<!-- ════════════════════════════════════════════
     LOGIN PAGE
     ════════════════════════════════════════════ -->
<div id="pg-login" class="hide">
  <div class="l-left">
    <div class="logo">Care<em>Drop</em></div>
    <h2>Selamat datang<br>kembali 👋</h2>
    <p>Masuk dengan akun Anda sesuai peran — donatur, yayasan, atau relawan pengantar.</p>
    <div class="role-pills">
      <div class="rp"><span>🤝</span><div><strong>Donatur</strong><em>Cari kebutuhan · Donasi · Lacak barang</em></div></div>
      <div class="rp"><span>🏠</span><div><strong>Yayasan</strong><em>Kelola katalog · Konfirmasi donasi</em></div></div>
      <div class="rp"><span>🚗</span><div><strong>Relawan</strong><em>Ambil tugas · Antar barang · Riwayat</em></div></div>
    </div>
  </div>

  <div class="l-right">
    <div class="lbox">
      <h3>Masuk ke <?= APP_NAME ?></h3>
      <p class="subt">Pilih peran lalu gunakan akun demo di bawah</p>

      <!-- Role Tabs -->
      <div class="role-tabs">
        <button class="rt on"  id="tab-don" onclick="switchRole('donatur')">🤝 Donatur</button>
        <button class="rt"     id="tab-yay" onclick="switchRole('yayasan')">🏠 Yayasan</button>
        <button class="rt"     id="tab-rel" onclick="switchRole('relawan')">🚗 Relawan</button>
      </div>

      <div class="fg">
        <label>Email</label>
        <input type="email" id="l-email" placeholder="email@contoh.com">
      </div>
      <div class="fg">
        <label>Kata Sandi</label>
        <input type="password" id="l-pass" placeholder="••••••••">
      </div>
      <button class="btn btn-green" style="width:100%;justify-content:center;margin-top:2px" onclick="doLogin()">
        Masuk →
      </button>

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
            $tagClass = ['donatur'=>'tg','yayasan'=>'ta','relawan'=>'to'][$role];
            $roleLabel = ['donatur'=>'Donatur','yayasan'=>'Yayasan','relawan'=>'Relawan'][$role];
          ?>
          <span class="tag <?= $tagClass ?>"><?= $roleLabel ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <p class="sw">Belum punya akun? <a onclick="toast('Fitur daftar segera hadir!')">Daftar sekarang</a></p>
    </div>
  </div>
</div><!-- end pg-login -->


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
            <select>
              <option>— Pilih Yayasan —</option>
              <option selected>Panti Asuhan Al-Ikhlas, Mataram</option>
              <option>Yayasan Peduli Anak NTB, Lombok</option>
              <option>Rumah Singgah Harapan, Selong</option>
              <option>Panti Asuhan Nur Hidayah, Praya</option>
            </select>
          </div>
        </div>

        <div class="fcard">
          <div class="fcard-t"><span class="sn">4</span>Jadwal Penjemputan</div>
          <div class="fg2">
            <label>Lokasi Penjemputan</label>
            <div class="mapbox" onclick="toast('Peta GPS dibuka — tandai lokasimu')">
              <span class="mi">📍</span>
              <span>Ketuk untuk pilih lokasi di peta</span>
            </div>
          </div>
          <div class="fg2"><label>Alamat Detail</label><input type="text" placeholder="Jalan, no rumah, RT/RW"></div>
          <div class="fg2">
            <label>Pilih Waktu</label>
            <div class="slots">
              <button class="slot" onclick="pickSlot(this)">Senin 08–12</button>
              <button class="slot on" onclick="pickSlot(this)">Selasa 08–12</button>
              <button class="slot" onclick="pickSlot(this)">Selasa 13–17</button>
              <button class="slot" onclick="pickSlot(this)">Rabu 08–12</button>
              <button class="slot" onclick="pickSlot(this)">Rabu 13–17</button>
            </div>
          </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:9px">
          <button class="btn btn-ghost" onclick="nav('d-kat')">Batal</button>
          <button class="btn btn-green" onclick="submitDon()">🎁 Daftarkan Donasi</button>
        </div>
      </div>
    </div><!-- end d-don -->

    <!-- Lacak Donasi -->
    <div class="inner-page" id="ip-d-lacak">
      <div class="ph"><h2>Lacak Donasi</h2><p>Pantau perjalanan barangmu secara real-time</p></div>
      <div class="tbox">
        <div class="tsrch">
          <input type="text" placeholder="ID donasi (cth: CDR-20260401-001)">
          <button class="btn btn-green" onclick="showTrack()">Lacak</button>
        </div>
        <div id="tr-empty" style="text-align:center;padding:42px 0">
          <div style="font-size:42px;margin-bottom:9px">📦</div>
          <p style="font-weight:700;color:var(--g1)">Masukkan ID Donasi</p>
          <p style="font-size:.84rem;color:var(--text2);margin-top:3px">ID dikirim ke email setelah donasi terdaftar</p>
          <button class="btn btn-outline btn-sm" style="margin-top:12px" onclick="showTrack()">Lihat Contoh</button>
        </div>
        <div id="tr-result" class="hide">
          <!-- Donasi 1: Dalam Perjalanan -->
          <div class="dcrd">
            <div class="dc-head">
              <div><div class="dc-id">CDR-20260404-007</div><div class="dc-ttl">3 Buku Pelajaran SD — Yayasan Peduli Anak NTB</div></div>
              <span class="tag ta">🚗 Dalam Perjalanan</span>
            </div>
            <div class="tl">
              <div class="tl-item"><div class="tl-dot done">✓</div><div class="tl-con"><h4>Donasi Terdaftar</h4><p>Sabrina · Mataram</p><time>4 Apr 2026, 08.40</time></div></div>
              <div class="tl-item"><div class="tl-dot done">✓</div><div class="tl-con"><h4>Relawan Ditugaskan</h4><p>Budi Santoso (Honda Beat)</p><time>4 Apr 2026, 09.20</time></div></div>
              <div class="tl-item"><div class="tl-dot done">✓</div><div class="tl-con"><h4>Barang Dijemput</h4><time>4 Apr 2026, 10.15</time></div></div>
              <div class="tl-item"><div class="tl-dot cur">→</div><div class="tl-con"><h4>Dalam Perjalanan ke Yayasan</h4><p>Estimasi tiba ±30 menit</p><time>4 Apr 2026, 10.50</time></div></div>
              <div class="tl-item"><div class="tl-dot" style="color:var(--text3);font-size:9px">···</div><div class="tl-con"><h4 style="color:var(--text3)">Menunggu Konfirmasi Yayasan</h4></div></div>
            </div>
          </div>
          <!-- Donasi 2: Selesai -->
          <div class="dcrd">
            <div class="dc-head">
              <div><div class="dc-id">CDR-20260401-001</div><div class="dc-ttl">5 Seragam SD — Panti Asuhan Al-Ikhlas</div></div>
              <span class="tag tg">✓ Selesai</span>
            </div>
            <div class="tl">
              <div class="tl-item"><div class="tl-dot done">✓</div><div class="tl-con"><h4>Donasi Terdaftar</h4><time>1 Apr 2026, 09.12</time></div></div>
              <div class="tl-item"><div class="tl-dot done">✓</div><div class="tl-con"><h4>Barang Dijemput</h4><time>1 Apr 2026, 11.30</time></div></div>
              <div class="tl-item">
                <div class="tl-dot done">✓</div>
                <div class="tl-con">
                  <h4>Diterima Yayasan</h4>
                  <p>Panti Asuhan Al-Ikhlas · Jl. Pejanggik No.12</p>
                  <time>1 Apr 2026, 14.20</time>
                  <div class="proof">📸 foto-serah-terima-CDR-001.jpg — tersimpan</div>
                </div>
              </div>
            </div>
            <div class="cert">
              <div><p>E-Sertifikat Diterbitkan</p><h4>CERT-CDR-20260401-001</h4></div>
              <button class="btn btn-green btn-sm" onclick="toast('Mengunduh E-Sertifikat…')">⬇ Unduh</button>
            </div>
          </div>
        </div>
      </div>
    </div><!-- end d-lacak -->

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


  <!-- ════════ ROLE: YAYASAN ════════ -->
  <div id="ry" class="hide">

    <!-- Dashboard Yayasan -->
    <div class="inner-page" id="ip-y-home">
      <div class="ph"><h2>Dashboard Yayasan</h2><p>Panti Asuhan Al-Ikhlas · Mataram, NTB</p></div>
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


  <!-- ════════ ROLE: RELAWAN ════════ -->
  <div id="rr" class="hide">

    <!-- Dashboard Relawan -->
    <div class="inner-page" id="ip-r-home">
      <div class="ph" style="background:linear-gradient(135deg,#3d1a00,#7c3a0a)">
        <h2>Dashboard Relawan</h2>
        <p>Budi Santoso · Relawan Aktif · Mataram, NTB</p>
      </div>
      <div class="wrap">
        <div class="stat-row">
          <div class="sc"><label>Total Antar Selesai</label><big id="rel-stat-selesai">2</big><small>sejak bergabung</small></div>
          <div class="sc sc-o"><label>Tugas Aktif</label><big id="rel-stat-aktif">0</big><small>sedang berjalan</small></div>
          <div class="sc sc-b"><label>Tugas Menunggu</label><big id="rel-stat-pending">3</big><small>bisa diambil</small></div>
          <div class="sc"><label>Total Jarak Tempuh</label><big id="rel-stat-km">6.4</big><small>km ditempuh</small></div>
        </div>

        <!-- Status kendaraan & ketersediaan -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
          <div class="fcard" style="margin-bottom:0">
            <div class="fcard-t" style="margin-bottom:14px">🚗 Kendaraan Saya</div>
            <div style="font-size:.88rem;color:var(--text2)">
              <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--surf)"><span>Jenis</span><strong>Sepeda Motor</strong></div>
              <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--surf)"><span>Plat</span><strong>DR 1234 AB</strong></div>
              <div style="display:flex;justify-content:space-between;padding:6px 0"><span>Warna</span><strong>Hitam</strong></div>
            </div>
          </div>
          <div class="fcard" style="margin-bottom:0">
            <div class="fcard-t" style="margin-bottom:14px">📶 Status Ketersediaan</div>
            <div style="display:flex;flex-direction:column;gap:10px">
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                <input type="radio" name="status-rel" checked onchange="toast('Status: Online — kamu akan menerima tugas baru')"> Online (siap menerima tugas)
              </label>
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                <input type="radio" name="status-rel" onchange="toast('Status: Istirahat — tidak menerima tugas sementara')"> Istirahat sementara
              </label>
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                <input type="radio" name="status-rel" onchange="toast('Status: Offline')"> Offline
              </label>
            </div>
          </div>
        </div>

        <div class="tcrd">
          <div class="thead"><h3>Tugas Terbaru</h3><button class="btn btn-orange btn-sm" onclick="nav('r-jobs')">Lihat Semua Tugas</button></div>
          <table>
            <thead><tr><th>ID</th><th>Barang</th><th>Rute</th><th>Jarak</th><th>Status</th></tr></thead>
            <tbody>
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260404-007</td><td>3 Buku Pelajaran SD</td><td>Cakranegara → Ampenan</td><td>4.2 km</td><td><span class="tag tb">Menunggu</span></td></tr>
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260320-003</td><td>Tas Sekolah 2 buah</td><td>Mataram → Al-Ikhlas</td><td>2.1 km</td><td><span class="tag tg">Selesai</span></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end r-home -->

    <!-- Daftar Tugas Jemput -->
    <div class="inner-page" id="ip-r-jobs">
      <div class="ph" style="background:linear-gradient(135deg,#3d1a00,#7c3a0a)">
        <h2>Tugas Penjemputan Tersedia</h2>
        <p>Pilih tugas yang ingin kamu ambil berdasarkan lokasi dan waktu</p>
      </div>
      <div class="wrap">
        <div class="job-grid" id="jobs-grid">
          <p style="color:var(--text2);padding:16px">Memuat tugas...</p>
        </div>
      </div>
    </div><!-- end r-jobs -->

    <!-- Tugas Aktif (Sedang Berjalan) -->
    <div class="inner-page" id="ip-r-aktif">
      <div class="ph" style="background:linear-gradient(135deg,#3d1a00,#7c3a0a)">
        <h2>Tugas Sedang Berjalan</h2>
        <p>Perbarui status pengantaran barang secara bertahap</p>
      </div>
      <div class="wrap" style="max-width:720px">
        <div id="active-job-container">
          <div style="text-align:center;padding:48px 24px">
            <div style="font-size:42px;margin-bottom:10px">🚗</div>
            <p style="font-weight:700;color:var(--g1)">Belum ada tugas aktif</p>
            <p style="font-size:.85rem;color:var(--text2);margin-top:4px">Ambil tugas dari halaman "Tugas Jemput" terlebih dahulu</p>
            <button class="btn btn-orange btn-sm" style="margin-top:14px" onclick="nav('r-jobs')">Lihat Tugas Tersedia</button>
          </div>
        </div>
      </div>
    </div><!-- end r-aktif -->

    <!-- Riwayat Relawan -->
    <div class="inner-page" id="ip-r-hist">
      <div class="ph" style="background:linear-gradient(135deg,#3d1a00,#7c3a0a)">
        <h2>Riwayat Pengantaran</h2>
        <p>Semua tugas yang pernah kamu selesaikan</p>
      </div>
      <div class="wrap">
        <div class="tcrd">
          <div class="thead">
            <h3>Semua Pengantaran</h3>
            <div style="display:flex;align-items:center;gap:6px;font-size:.83rem;color:var(--text2)">
              Total jarak: <strong style="color:var(--g3)" id="rel-total-km">6.4 km</strong>
            </div>
          </div>
          <table>
            <thead><tr><th>ID</th><th>Barang</th><th>Donatur</th><th>Yayasan</th><th>Tanggal</th><th>Jarak</th><th>Rating</th></tr></thead>
            <tbody id="rel-hist-tbody">
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260320-003</td><td>Tas Sekolah 2 buah</td><td>Dian Permata</td><td>Al-Ikhlas</td><td>20 Mar 2026</td><td>2.1 km</td><td>⭐⭐⭐⭐⭐</td></tr>
              <tr><td style="font-family:monospace;font-size:.76rem">CDR-20260328-009</td><td>4 Buku Cerita Anak</td><td>Hari Purnomo</td><td>Yayasan Peduli Anak</td><td>28 Mar 2026</td><td>3.7 km</td><td>⭐⭐⭐⭐⭐</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- end r-hist -->

    <!-- Profil Relawan -->
    <div class="inner-page" id="ip-r-profil">
      <div class="ph" style="background:linear-gradient(135deg,#3d1a00,#7c3a0a)">
        <h2>Profil Relawan</h2>
      </div>
      <div class="wrap" style="max-width:620px">
        <div class="pcrd">
          <div class="pav" style="background:linear-gradient(135deg,#7c3a0a,var(--orange))">🚗</div>
          <div class="pinf">
            <h3>Budi Santoso</h3>
            <p>budi.relawan@email.com · 0813-5555-7777</p>
            <span class="tag to rt2">Relawan Aktif</span>
          </div>
        </div>
        <div class="fcard">
          <div class="fcard-t" style="margin-bottom:17px">Data Diri &amp; Kendaraan</div>
          <div class="frow">
            <div class="fg2"><label>Nama Lengkap</label><input value="Budi Santoso"></div>
            <div class="fg2"><label>No. Telepon</label><input value="0813-5555-7777"></div>
          </div>
          <div class="fg2"><label>Area Operasional</label><input value="Kota Mataram &amp; sekitarnya"></div>
          <div class="frow">
            <div class="fg2">
              <label>Jenis Kendaraan</label>
              <select><option selected>Sepeda Motor</option><option>Mobil / Pickup</option><option>Sepeda</option></select>
            </div>
            <div class="fg2"><label>No. Plat Kendaraan</label><input value="DR 1234 AB"></div>
          </div>
          <div class="fg2">
            <label>SIM (foto)</label>
            <div class="up-box" onclick="toast('Upload foto SIM')"><div class="ui">📄</div><p>Upload foto SIM</p><span>JPG, PNG – maks 3MB</span></div>
          </div>
          <button class="btn btn-green btn-sm" onclick="toast('Profil relawan diperbarui!')">Simpan</button>
        </div>
        <div style="text-align:right;margin-top:10px">
          <button class="btn btn-red btn-sm" onclick="doLogout()">Keluar Akun</button>
        </div>
      </div>
    </div><!-- end r-profil -->

  </div><!-- end rr -->

</div><!-- end pg-app -->

<!-- JavaScript -->
<script src="app.js"></script>

</body>
</html>
