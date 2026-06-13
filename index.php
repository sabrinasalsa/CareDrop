<?php
session_start();

if (isset($_SESSION['id'], $_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/index.php'); exit;
        case 'penerima':
            header('Location: yayasan/kelola_katalog.php'); exit;
        case 'donatur':
        default:
            header('Location: dashboard.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CareDrop – Donasi Barang untuk yang Membutuhkan</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;0,9..144,800;1,9..144,400&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
  
  <link rel="stylesheet" href="Assets/css/style.css">
</head>
<body>

<nav class="navbar" id="navbar">
  <a href="#" class="nav-logo"><img src="uploads/icon/daun.png" alt="" style="height: 1.2em; vertical-align: middle; margin-top: -3px; margin-right: 6px;"> Care<span>Drop</span></a>
  <div class="nav-links">
    <a href="#tentang">Tentang</a>
    <a href="#cara-kerja">Cara Kerja</a>
    <a href="#dampak">Dampak</a>
    <a href="#faq">FAQ</a>
  </div>
  <div class="nav-actions">
    <a href="login.php" class="btn-nav-outline">Masuk</a>
    <a href="login.php?tab=register" class="btn-nav-solid">Daftar Gratis</a>
  </div>
</nav>

<section class="hero">
  <div class="hero-orb orb1"></div>
  <div class="hero-orb orb2"></div>
  <div class="hero-orb orb3"></div>

  <div class="hero-badge" data-aos="fade-down" data-aos-duration="600">
    <span class="badge-dot"></span>
    Platform Donasi Barang Terpercaya
  </div>

  <h1 data-aos="fade-up" data-aos-duration="750" data-aos-delay="100">
    Salurkan Barang.<br>
    Sentuh <em>Kehidupan</em><br>
    yang <span class="hl">Membutuhkan</span>.
  </h1>

  <p class="hero-sub" data-aos="fade-up" data-aos-duration="700" data-aos-delay="200">
    CareDrop menghubungkan donatur dengan yayasan & panti asuhan terverifikasi. Mudah, transparan, dan tepat sasaran.
  </p>

  <div class="hero-actions" data-aos="fade-up" data-aos-duration="700" data-aos-delay="300">
    <a href="login.php?tab=register&role=donatur" class="btn-hero-primary">
      Ayo Mulai Berdonasi
    </a>
    <a href="#cara-kerja" class="btn-hero-ghost">Lihat Cara Kerja</a>
  </div>


  <div class="hero-wave">
    <svg viewBox="0 0 1440 90" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0,45 C240,90 480,0 720,45 C960,90 1200,10 1440,45 L1440,90 L0,90 Z" fill="#f4fbf6"/>
    </svg>
  </div>
</section>

<div class="trust-bar" data-aos="fade-up" data-aos-duration="500">
  <div class="trust-inner">
    <div class="trust-item"><span><img src="uploads/icon/verified.png" alt=""></span> Yayasan Terverifikasi</div>
    <div class="trust-item"><span><img src="uploads/icon/shield.png" alt=""></span> Data Aman & Terlindungi</div>
    <div class="trust-item"><span><img src="uploads/icon/tracking.png" alt=""></span> Lacak Real-time</div>
    <div class="trust-item"><span><img src="uploads/icon/certificate.png" alt=""></span> E-Sertifikat Resmi</div>
    <div class="trust-item"><span><img src="uploads/icon/target.png" alt=""></span> Donasi Tepat Sasaran</div>
  </div>
</div>

<section class="section" id="tentang">
  <div class="container">
    <div style="max-width:580px; margin-bottom:52px;">
      <span class="section-label" data-aos="fade-up">Tentang CareDrop</span>
      <h2 class="section-title" data-aos="fade-up" data-aos-delay="80">
        Kenapa Ribuan Orang<br><em>Mempercayai</em> CareDrop?
      </h2>
      <p class="section-sub" data-aos="fade-up" data-aos-delay="140">
        Kami hadir untuk memastikan setiap donasi barang sampai ke tangan yang benar-benar membutuhkan, dengan sistem yang transparan dan mudah digunakan.
      </p>
    </div>

    <div class="features-grid">
      <div class="feature-card" data-aos="fade-up" data-aos-delay="0">
        <div class="feat-icon"><img src="uploads/icon/verified.png" alt=""></div>
        <h3>Yayasan Terverifikasi</h3>
        <p>Setiap penerima donasi melalui proses verifikasi ketat oleh tim admin CareDrop sebelum dapat bergabung di platform kami.</p>
      </div>
      <div class="feature-card" data-aos="fade-up" data-aos-delay="80">
        <div class="feat-icon"><img src="uploads/icon/tracking.png" alt=""></div>
        <h3>Lacak Real-time</h3>
        <p>Pantau status donasi dari diterima, diproses, dikirim, hingga selesai secara transparan — seperti lacak paket online.</p>
      </div>
      <div class="feature-card" data-aos="fade-up" data-aos-delay="160">
        <div class="feat-icon"><img src="uploads/icon/certificate.png" alt=""></div>
        <h3>Sertifikat Donasi</h3>
        <p>Dapatkan e-sertifikat resmi sebagai bukti kontribusi nyata kamu terhadap sesama yang bisa disimpan atau dibagikan.</p>
      </div>
      <div class="feature-card" data-aos="fade-up" data-aos-delay="240">
        <div class="feat-icon"><img src="uploads/icon/target.png" alt=""></div>
        <h3>Donasi Tepat Sasaran</h3>
        <p>Pilih langsung dari katalog kebutuhan yayasan agar barang yang didonasikan benar-benar dibutuhkan dan tepat guna.</p>
      </div>
    </div>
  </div>
</section>

<div class="wave-divider" style="background:#f0f9f3;">
  <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="fill:#f4fbf6">
    <path d="M0,30 C360,60 1080,0 1440,30 L1440,0 L0,0 Z"/>
  </svg>
</div>


<section class="section bg-light" id="cara-kerja">
  <div class="container" style="text-align:center;">
    <span class="section-label" data-aos="fade-up">Cara Kerja</span>
    <h2 class="section-title" data-aos="fade-up" data-aos-delay="80">
      3 Langkah Mudah<br><em>Berdonasi</em>
    </h2>
    <p class="section-sub" style="margin:0 auto 56px;" data-aos="fade-up" data-aos-delay="140">
      Proses donasi yang simpel dan transparan — dari pendaftaran hingga sertifikat, semuanya terekam di sistem kami.
    </p>

    <div class="steps-wrapper">
      <div class="step" data-aos="fade-right" data-aos-delay="0" data-aos-duration="700">
        <div class="step-circle">
          <span class="step-num">1</span>
        </div>
        <h3>Daftar & Pilih Katalog</h3>
        <p>Buat akun donatur gratis, lalu jelajahi katalog kebutuhan dari yayasan-yayasan terverifikasi di seluruh Indonesia.</p>
      </div>
      <div class="step" data-aos="fade-up" data-aos-delay="150" data-aos-duration="700">
        <div class="step-circle">
          <span class="step-num">2</span>
        </div>
        <h3>Kirim Donasi Barang</h3>
        <p>Kirimkan barang donasi sesuai kebutuhan. Pilih kurir dan pantau pengiriman secara real-time dari dashboard kamu.</p>
      </div>
      <div class="step" data-aos="fade-left" data-aos-delay="300" data-aos-duration="700">
        <div class="step-circle">
          <span class="step-num">3</span>
        </div>
        <h3>Terima Sertifikat</h3>
        <p>Setelah donasi dikonfirmasi diterima, kamu mendapat e-sertifikat resmi sebagai bukti kepedulianmu yang nyata.</p>
      </div>
    </div>
  </div>
</section>


<div class="wave-divider" style="background:#f0f9f3;">
  <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="fill:#0c2e18">
    <path d="M0,30 C480,60 960,0 1440,30 L1440,60 L0,60 Z"/>
  </svg>
</div>

<section class="section bg-dark" id="dampak">
  <div class="container" style="text-align:center;">
    <span class="section-label light" data-aos="fade-up">Dampak Nyata</span>
    <h2 class="section-title light" data-aos="fade-up" data-aos-delay="80">
      Bersama Kita Sudah<br><em style="color:var(--amber)">Berbuat Banyak</em>
    </h2>
    <p class="section-sub light" style="margin:0 auto 52px;" data-aos="fade-up" data-aos-delay="140">
      Angka-angka ini bukan sekadar statistik — di baliknya ada ribuan anak yang kini sekolah dengan seragam baru, buku pelajaran, dan harapan yang tumbuh.
    </p>
  </div>
</section>

<section class="section" id="testimoni">
  <div class="container">
    <span class="section-label" data-aos="fade-up">Cerita Nyata</span>
    <h2 class="section-title" data-aos="fade-up" data-aos-delay="80">
      Apa Kata <em>Mereka</em>?
    </h2>
    <p class="section-sub" data-aos="fade-up" data-aos-delay="140">
      Ribuan donatur dan yayasan telah merasakan manfaat platform kami.
    </p>

    <div class="testi-grid">
      <div class="testi-card" data-aos="fade-up" data-aos-delay="0">
        <p class="testi-text">Prosesnya benar-benar mudah! Saya bisa pilih kebutuhan yang spesifik dari yayasan terdekat dan langsung kirim. Transparansinya bikin hati tenang.</p>
        <div class="testi-author">
          <div class="testi-av av-g">SS</div>
          <div>
            <strong>Sabrina Salsabila</strong>
            <span>Donatur, Mataram</span>
          </div>
        </div>
      </div>
      <div class="testi-card" data-aos="fade-up" data-aos-delay="100">
        <p class="testi-text">Sebagai yayasan, CareDrop membantu kami mendapatkan donasi barang yang sesuai kebutuhan riil anak-anak kami. Sistem konfirmasinya sangat mudah digunakan.</p>
        <div class="testi-author">
          <div class="testi-av av-b">AI</div>
          <div>
            <strong>Panti Asuhan Al-Ikhlas</strong>
            <span>Yayasan Terverifikasi, Mataram</span>
          </div>
        </div>
      </div>
      <div class="testi-card" data-aos="fade-up" data-aos-delay="200">
        <p class="testi-text">E-sertifikat yang saya terima sangat berharga. Saya tunjukkan ke keluarga dan teman sebagai bukti bahwa kita bisa berkontribusi nyata meski dari jarak jauh.</p>
        <div class="testi-author">
          <div class="testi-av av-a">AW</div>
          <div>
            <strong>Andi Wijaya</strong>
            <span>Donatur, Lombok</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section bg-light" id="bergabung" style="padding-bottom:80px;">
  <div class="container">
    <div style="text-align:center; margin-bottom:52px;">
      <span class="section-label" data-aos="fade-up">Untuk Siapa?</span>
      <h2 class="section-title" data-aos="fade-up" data-aos-delay="80">
        Platform untuk <em>Semua</em>
      </h2>
    </div>

    <div class="roles-grid">
      <div class="role-card" data-aos="fade-right" data-aos-duration="700">
        <div class="role-icon donatur"><img src="uploads/icon/handshake.png" alt=""></div>
        <h3>Donatur</h3>
        <p>Individu atau organisasi yang ingin menyalurkan barang layak pakai kepada yang membutuhkan secara langsung dan terverifikasi.</p>
        <a href="login.php?tab=register&role=donatur" class="btn-role">Daftar sebagai Donatur </a>
      </div>

      <div class="role-card featured" data-aos="fade-left" data-aos-duration="700">
        <div class="role-badge"> Paling Banyak</div>
        <div class="role-icon yayasan"><img src="uploads/icon/home.png" alt=""></div>
        <h3>Yayasan / Panti</h3>
        <p>Lembaga sosial terverifikasi yang dapat mengajukan kebutuhan barang spesifik untuk penerima manfaat secara mudah dan terpercaya.</p>
        <a href="login.php?tab=register&role=penerima" class="btn-role green">Daftar sebagai Yayasan </a>
      </div>
    </div>
  </div>
</section>

<section class="section" id="faq">
  <div class="container">
    <div style="text-align:center; margin-bottom:52px;">
      <span class="section-label" data-aos="fade-up">FAQ</span>
      <h2 class="section-title" data-aos="fade-up" data-aos-delay="80">Pertanyaan <em>Umum</em></h2>
    </div>

    <div class="faq-list">
      <div class="faq-item" data-aos="fade-up" data-aos-delay="0">
        <button class="faq-q" onclick="toggleFaq(this)">
          Apakah CareDrop gratis digunakan?
          <span class="faq-icon">+</span>
        </button>
        <div class="faq-a">
          Ya, CareDrop sepenuhnya gratis untuk donatur maupun yayasan. Tidak ada biaya pendaftaran atau biaya transaksi apapun.
        </div>
      </div>
      <div class="faq-item" data-aos="fade-up" data-aos-delay="60">
        <button class="faq-q" onclick="toggleFaq(this)">
          Bagaimana cara memastikan donasi sampai ke tujuan?
          <span class="faq-icon">+</span>
        </button>
        <div class="faq-a">
          Sistem pelacakan real-time kami memungkinkan kamu memantau status donasi di setiap tahap pengiriman. Yayasan juga wajib mengkonfirmasi penerimaan barang dengan foto bukti, sehingga kamu dapat melihat bukti nyata donasi telah diterima.
        </div>
      </div>
      <div class="faq-item" data-aos="fade-up" data-aos-delay="120">
        <button class="faq-q" onclick="toggleFaq(this)">
          Apa saja barang yang bisa didonasikan?
          <span class="faq-icon">+</span>
        </button>
        <div class="faq-a">
          Berbagai kategori barang dapat didonasikan: pakaian layak pakai, peralatan sekolah, buku pelajaran, makanan non-perishable, peralatan rumah tangga, dan masih banyak lagi sesuai dengan katalog kebutuhan dari masing-masing yayasan.
        </div>
      </div>
      <div class="faq-item" data-aos="fade-up" data-aos-delay="180">
        <button class="faq-q" onclick="toggleFaq(this)">
          Berapa lama proses verifikasi yayasan?
          <span class="faq-icon">+</span>
        </button>
        <div class="faq-a">
          Proses verifikasi biasanya memakan waktu 1–3 hari kerja. Tim admin kami akan memeriksa kelengkapan dokumen, legalitas lembaga, dan melakukan konfirmasi langsung sebelum akun diaktifkan.
        </div>
      </div>
      <div class="faq-item" data-aos="fade-up" data-aos-delay="240">
        <button class="faq-q" onclick="toggleFaq(this)">
          Apakah saya bisa berdonasi ke yayasan di luar kota?
          <span class="faq-icon">+</span>
        </button>
        <div class="faq-a">
          Tentu! CareDrop mendukung pengiriman ke seluruh wilayah Indonesia. Sistem kami akan membantu menghitung estimasi ongkos kirim dan merekomendasikan kurir terbaik berdasarkan lokasi asalmu dan tujuan yayasan.
        </div>
      </div>
    </div>
  </div>
</section>

<section class="cta-section">
  <div class="container">
    <div class="cta-box" data-aos="zoom-in" data-aos-duration="700">
      <h2>Siap Membuat Perbedaan?</h2>
      <p>Bergabunglah bersama ribuan donatur yang sudah menyentuh kehidupan banyak orang lewat CareDrop. Mulai perjalananmu hari ini — gratis.</p>
      <div class="cta-actions">
        <a href="login.php?tab=register" class="btn-cta-white"><img src="uploads/icon/gift.png" alt=""> Ayo Donasi Sekarang </a>
        <a href="login.php" class="btn-cta-outline">Sudah punya akun? Masuk</a>
      </div>
    </div>
  </div>
</section>

<footer class="footer">
  <div class="container">
    <div class="footer-top">
      <div class="footer-brand">
        <a href="#" class="nav-logo" style="font-size:1.3rem"><img src="uploads/icon/daun.png" alt="" style="height: 1.2em; vertical-align: middle; margin-top: -3px; margin-right: 6px;"> Care<span>Drop</span></a>
        <p>Platform donasi barang yang menghubungkan kepedulian dengan kebutuhan nyata. Bersama kita bisa membuat perbedaan yang berarti.</p>
      </div>
      <div class="footer-col">
        <h4>Platform</h4>
        <a href="#cara-kerja">Cara Kerja</a>
        <a href="#tentang">Tentang Kami</a>
        <a href="#faq">FAQ</a>
        <a href="#dampak">Dampak</a>
      </div>
      <div class="footer-col">
        <h4>Akun</h4>
        <a href="login.php">Masuk</a>
        <a href="login.php?tab=register">Daftar Gratis</a>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 CareDrop. Dibuat dengan ❤️ untuk Indonesia.</p>
      <p style="font-size:.78rem; color:rgba(201,242,220,.2)">Menghubungkan kepedulian dengan kebutuhan nyata</p>
    </div>
  </div>
</footer>

<!-- External Scripts -->
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<!-- Custom Script -->
<script src="Assets/js/app.js"></script>
</html>