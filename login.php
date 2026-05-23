<?php
session_start();

$tab   = $_GET['tab']   ?? 'login';
$role  = $_GET['role']  ?? 'donatur';
$flash = $_GET['flash'] ?? '';

if (isset($_SESSION['id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CareDrop – Masuk / Daftar</title>

  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;0,9..144,800;1,9..144,400&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --forest:  #0c2e18;
      --deep:    #163d22;
      --pine:    #1e5630;
      --moss:    #2d7a44;
      --sage:    #4aad6b;
      --mint:    #7ed9a3;
      --foam:    #c9f2dc;
      --mist:    #edfbf3;
      --gold:    #d4a017;
      --amber:   #f0c040;
      --ink:     #0b1f12;
      --body:    #2c4a35;
      --muted:   #5c7d65;
      --bg:      #f4fbf6;
      --ff-display: 'Fraunces', Georgia, serif;
      --ff-body:    'DM Sans', system-ui, sans-serif;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: var(--ff-body);
      min-height: 100vh;
      background: var(--forest);
      display: flex;
      align-items: stretch;
      overflow-x: hidden;
    }

    /* ── Left panel (decorative) ── */
    .panel-left {
      flex: 0 0 46%;
      background: linear-gradient(160deg, var(--forest) 0%, var(--deep) 50%, #122e1c 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px 56px;
      position: relative;
      overflow: hidden;
    }

    .panel-left::before {
      content: '';
      position: absolute;
      width: 500px; height: 500px;
      border-radius: 50%;
      background: rgba(45,122,68,.15);
      top: -150px; left: -150px;
      filter: blur(60px);
    }
    .panel-left::after {
      content: '';
      position: absolute;
      width: 380px; height: 380px;
      border-radius: 50%;
      background: rgba(126,217,163,.08);
      bottom: -80px; right: -80px;
      filter: blur(50px);
    }

    .panel-left .logo {
      font-family: var(--ff-display);
      font-size: 1.55rem;
      font-weight: 700;
      color: var(--mint);
      text-decoration: none;
      margin-bottom: 52px;
      display: flex;
      align-items: center;
      gap: 8px;
      position: relative; z-index: 1;
    }
    .panel-left .logo span { color: var(--amber); }

    .panel-left h2 {
      font-family: var(--ff-display);
      font-size: clamp(1.9rem, 3.2vw, 2.8rem);
      font-weight: 800;
      color: #fff;
      line-height: 1.1;
      letter-spacing: -1px;
      margin-bottom: 20px;
      position: relative; z-index: 1;
    }
    .panel-left h2 em { color: var(--amber); font-style: italic; }

    .panel-left p {
      font-size: .95rem;
      color: rgba(201,242,220,.65);
      line-height: 1.75;
      max-width: 340px;
      position: relative; z-index: 1;
      margin-bottom: 40px;
    }

    .trust-pills {
      display: flex;
      flex-direction: column;
      gap: 11px;
      position: relative; z-index: 1;
    }
    .trust-pill {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255,255,255,.05);
      border: 1px solid rgba(126,217,163,.15);
      border-radius: 10px;
      padding: 10px 14px;
      font-size: .84rem;
      color: rgba(201,242,220,.8);
      backdrop-filter: blur(8px);
    }
    .trust-pill span { font-size: 1rem; }

    .deco-number {
      position: absolute;
      bottom: 40px; right: 40px;
      z-index: 0;
      font-family: var(--ff-display);
      font-size: 11rem;
      font-weight: 800;
      color: rgba(255,255,255,.03);
      line-height: 1;
      user-select: none;
    }

    /* ── Right panel (form) ── */
    .panel-right {
      flex: 1;
      background: var(--bg);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 48px 5vw;
      min-height: 100vh;
      overflow-y: auto;
    }

    .auth-box {
      width: 100%;
      max-width: 440px;
    }

    /* Tab switcher */
    .tab-bar {
      display: flex;
      background: #e8f5ed;
      border-radius: 14px;
      padding: 5px;
      margin-bottom: 32px;
      gap: 4px;
    }
    .tab-btn {
      flex: 1;
      padding: 11px 16px;
      border: none;
      background: transparent;
      border-radius: 10px;
      font-family: var(--ff-body);
      font-size: .9rem;
      font-weight: 600;
      color: var(--muted);
      cursor: pointer;
      transition: all .22s;
    }
    .tab-btn.active {
      background: var(--forest);
      color: var(--mint);
      box-shadow: 0 4px 16px rgba(12,46,24,.25);
    }

    /* Form sections */
    .form-section { display: none; animation: fadeUp .35s ease; }
    .form-section.visible { display: block; }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .form-title {
      font-family: var(--ff-display);
      font-size: 1.65rem;
      font-weight: 700;
      color: var(--ink);
      margin-bottom: 6px;
      letter-spacing: -0.4px;
    }
    .form-title em { color: var(--moss); font-style: italic; }
    .form-sub {
      font-size: .875rem;
      color: var(--muted);
      margin-bottom: 28px;
    }

    /* Role picker (register only) */
    .role-pick {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 22px;
    }
    .role-opt {
      border: 2px solid #d0e8d8;
      border-radius: 12px;
      padding: 14px 12px;
      cursor: pointer;
      text-align: center;
      transition: all .2s;
      position: relative;
    }
    .role-opt input { position: absolute; opacity: 0; pointer-events: none; }
    .role-opt:has(input:checked) {
      border-color: var(--moss);
      background: var(--mist);
    }
    .role-opt .ro-icon { font-size: 1.6rem; margin-bottom: 6px; display: block; }
    .role-opt .ro-label { font-size: .82rem; font-weight: 700; color: var(--ink); display: block; }
    .role-opt .ro-desc  { font-size: .72rem; color: var(--muted); display: block; margin-top: 2px; }

    /* Fields */
    .field { margin-bottom: 16px; }
    .field label {
      display: block;
      font-size: .78rem;
      font-weight: 700;
      color: var(--body);
      letter-spacing: .5px;
      text-transform: uppercase;
      margin-bottom: 6px;
    }
    .field input,
    .field select {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid #c8e8d4;
      border-radius: 10px;
      font-family: var(--ff-body);
      font-size: .92rem;
      color: var(--ink);
      background: #fff;
      outline: none;
      transition: all .2s;
    }
    .field input:focus,
    .field select:focus {
      border-color: var(--moss);
      box-shadow: 0 0 0 3px rgba(45,122,68,.12);
    }
    .field input::placeholder { color: #9db8a5; }

    .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    /* Password toggle */
    .pass-wrap { position: relative; }
    .pass-wrap input { padding-right: 46px; }
    .pass-eye {
      position: absolute;
      right: 14px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      cursor: pointer; font-size: 1.1rem;
      color: var(--muted); padding: 0;
      line-height: 1;
    }

    /* Password strength */
    .strength-bar { height: 3px; border-radius: 99px; background: #dde8e2; margin-top: 6px; overflow: hidden; }
    .strength-fill { height: 100%; border-radius: 99px; transition: width .3s, background .3s; width: 0; }
    .strength-text { font-size: .72rem; color: var(--muted); margin-top: 4px; }

    /* Submit */
    .btn-submit {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, var(--moss), var(--sage));
      color: #fff;
      border: none;
      border-radius: 12px;
      font-family: var(--ff-body);
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 6px 22px rgba(45,122,68,.38);
      transition: all .22s;
      margin-top: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(45,122,68,.45); }
    .btn-submit:active { transform: translateY(0); }

    /* Divider */
    .divider {
      display: flex; align-items: center; gap: 12px;
      margin: 22px 0; color: var(--muted); font-size: .8rem;
    }
    .divider::before, .divider::after {
      content: ''; flex: 1; height: 1px; background: #d4e8db;
    }

    /* Back to home */
    .back-link {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: .82rem; color: var(--muted); text-decoration: none;
      margin-bottom: 28px; transition: color .15s;
    }
    .back-link:hover { color: var(--pine); }

    /* Flash messages */
    .flash {
      padding: 12px 15px;
      border-radius: 9px;
      font-size: .875rem;
      font-weight: 500;
      margin-bottom: 20px;
    }
    .flash-err { background: #fff1f2; border: 1px solid #fecaca; color: #dc2626; }
    .flash-ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }

    /* Step indicator (register) */
    .step-dots {
      display: flex; gap: 6px; margin-bottom: 20px;
    }
    .step-dot {
      width: 28px; height: 4px; border-radius: 99px;
      background: #d0e8d8; transition: background .2s;
    }
    .step-dot.done { background: var(--sage); }
    .step-dot.active { background: var(--moss); }

    /* Mobile */
    @media (max-width: 800px) {
      body { flex-direction: column; }
      .panel-left { flex: none; padding: 32px 28px 40px; min-height: auto; }
      .panel-left h2 { font-size: 1.7rem; }
      .trust-pills { display: none; }
      .deco-number { display: none; }
      .panel-right { padding: 36px 20px 48px; }
      .field-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<div class="panel-left">
  <a href="index.php" class="logo">🌿 Care<span>Drop</span></a>

  <h2>
    Salurkan Barang.<br>
    Sentuh <em>Kehidupan</em><br>
    yang Membutuhkan.
  </h2>

  <p>
    Bergabung dengan ribuan donatur dan yayasan terverifikasi. Donasi barang lebih mudah, transparan, dan tepat sasaran.
  </p>

  <div class="trust-pills">
    <div class="trust-pill"><span>✅</span> Yayasan terverifikasi admin CareDrop</div>
    <div class="trust-pill"><span>📦</span> Lacak donasi secara real-time</div>
    <div class="trust-pill"><span>🏅</span> Dapatkan e-sertifikat resmi</div>
    <div class="trust-pill"><span>🔒</span> Data Anda aman & terlindungi</div>
  </div>

  <div class="deco-number">CD</div>
</div>

<!-- Right form panel -->
<div class="panel-right">
  <div class="auth-box">

    <a href="index.php" class="back-link">← Kembali ke Beranda</a>

    <?php if ($flash === 'registered'): ?>
      <div class="flash flash-ok">🎉 Registrasi berhasil! Silakan masuk dengan akun Anda.</div>
    <?php elseif ($flash === 'timeout'): ?>
      <div class="flash flash-err">⏱ Sesi Anda berakhir. Silakan masuk kembali.</div>
    <?php endif; ?>

    <div class="tab-bar">
      <button class="tab-btn <?= $tab !== 'register' ? 'active' : '' ?>"
              onclick="switchTab('login', this)">Masuk</button>
      <button class="tab-btn <?= $tab === 'register' ? 'active' : '' ?>"
              onclick="switchTab('register', this)">Daftar Gratis</button>
    </div>

    <div class="form-section <?= $tab !== 'register' ? 'visible' : '' ?>" id="form-login">
      <h1 class="form-title">Selamat <em>Datang</em></h1>
      <p class="form-sub">Masuk untuk melanjutkan perjalanan donasimu.</p>

      <form action="backend/proses_login.php" method="POST" id="loginForm">
        <div class="field">
          <label>Alamat Email</label>
          <input type="email" name="email" placeholder="nama@email.com" required autocomplete="email">
        </div>

        <div class="field">
          <label>Kata Sandi</label>
          <div class="pass-wrap">
            <input type="password" name="password" id="loginPass" placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="pass-eye" onclick="togglePass('loginPass', this)">👁</button>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          <span>Masuk ke CareDrop</span> <span>→</span>
        </button>
      </form>

      <div class="divider">atau</div>

      <p style="text-align:center;font-size:.875rem;color:var(--muted)">
        Belum punya akun?
        <a href="#" onclick="switchTab('register', document.querySelectorAll('.tab-btn')[1]); return false;"
           style="color:var(--pine);font-weight:600;text-decoration:none">Daftar sekarang →</a>
      </p>
    </div>

    <div class="form-section <?= $tab === 'register' ? 'visible' : '' ?>" id="form-register">
      <h1 class="form-title">Buat <em>Akun</em> Baru</h1>
      <p class="form-sub">Gratis selamanya. Mulai berdonasi atau terima donasi hari ini.</p>

      <form action="backend/proses_registrasi.php" method="POST" id="registerForm" novalidate>

        <!-- Role picker -->
        <div class="role-pick">
          <label class="role-opt">
            <input type="radio" name="role" value="donatur"
                   <?= $role !== 'penerima' ? 'checked' : '' ?>>
            <span class="ro-icon">🤝</span>
            <span class="ro-label">Donatur</span>
            <span class="ro-desc">Saya ingin berdonasi</span>
          </label>
          <label class="role-opt">
            <input type="radio" name="role" value="penerima"
                   <?= $role === 'penerima' ? 'checked' : '' ?>>
            <span class="ro-icon">🏠</span>
            <span class="ro-label">Yayasan</span>
            <span class="ro-desc">Kami menerima donasi</span>
          </label>
        </div>

        <div class="field">
          <label>Nama Lengkap / Nama Lembaga</label>
          <input type="text" name="nama_lengkap" placeholder="Masukkan nama lengkap Anda" required>
        </div>

        <div class="field-row">
          <div class="field">
            <label>Alamat Email</label>
            <input type="email" name="email" placeholder="nama@email.com" required autocomplete="email">
          </div>
          <div class="field">
            <label>Nomor Telepon</label>
            <input type="tel" name="no_telp" placeholder="08xxxxxxxxxx" required>
          </div>
        </div>

        <div class="field">
          <label>Alamat / Kota</label>
          <input type="text" name="alamat" placeholder="Contoh: Jl. Mawar No.12, Mataram">
        </div>

        <div class="field">
          <label>Kata Sandi</label>
          <div class="pass-wrap">
            <input type="password" name="password" id="regPass" placeholder="Minimal 6 karakter"
                   required minlength="6" oninput="checkStrength(this.value)">
            <button type="button" class="pass-eye" onclick="togglePass('regPass', this)">👁</button>
          </div>
          <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
          <div class="strength-text" id="strengthText"></div>
        </div>

        <div class="field">
          <label>Konfirmasi Kata Sandi</label>
          <div class="pass-wrap">
            <input type="password" name="password_confirm" id="regPass2" placeholder="Ulangi kata sandi" required>
            <button type="button" class="pass-eye" onclick="togglePass('regPass2', this)">👁</button>
          </div>
        </div>

        <div id="reg-note" style="font-size:.78rem;color:var(--muted);background:#f0fdf4;border:1px solid #c8e8d4;border-radius:8px;padding:10px 12px;margin-bottom:14px;display:none">
          ℹ️ <strong>Akun Yayasan</strong> memerlukan verifikasi admin (1–3 hari kerja) sebelum dapat digunakan.
        </div>

        <button type="submit" class="btn-submit" id="regBtn">
          <span>Daftar Sekarang</span> <span>→</span>
        </button>
      </form>

      <div class="divider">atau</div>

      <p style="text-align:center;font-size:.875rem;color:var(--muted)">
        Sudah punya akun?
        <a href="#" onclick="switchTab('login', document.querySelectorAll('.tab-btn')[0]); return false;"
           style="color:var(--pine);font-weight:600;text-decoration:none">Masuk di sini →</a>
      </p>
    </div>

  </div>
</div>

<script>
  function switchTab(name, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.form-section').forEach(f => {
      f.classList.remove('visible');
      f.style.display = 'none';
    });
    btn.classList.add('active');
    const target = document.getElementById('form-' + name);
    target.style.display = 'block';
    // trigger animation
    void target.offsetWidth;
    target.classList.add('visible');
  }

  function togglePass(id, btn) {
    const inp = document.getElementById(id);
    if (inp.type === 'password') { inp.type = 'text'; btn.textContent = '🙈'; }
    else { inp.type = 'password'; btn.textContent = '👁'; }
  }
  function checkStrength(val) {
    const fill = document.getElementById('strengthFill');
    const txt  = document.getElementById('strengthText');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^a-zA-Z0-9]/.test(val)) score++;
    const levels = [
      { w:'0%',   c:'#e5e7eb', t:'' },
      { w:'20%',  c:'#ef4444', t:'Sangat lemah' },
      { w:'40%',  c:'#f97316', t:'Lemah' },
      { w:'60%',  c:'#eab308', t:'Cukup' },
      { w:'80%',  c:'#22c55e', t:'Kuat' },
      { w:'100%', c:'#16a34a', t:'Sangat kuat ✓' },
    ];
    const lv = levels[score] || levels[0];
    fill.style.width      = lv.w;
    fill.style.background = lv.c;
    txt.textContent = lv.t;
    txt.style.color = lv.c;
  }

  document.querySelectorAll('input[name="role"]').forEach(r => {
    r.addEventListener('change', () => {
      const note = document.getElementById('reg-note');
      note.style.display = r.value === 'penerima' ? 'block' : 'none';
    });
  });

  const checkedRole = document.querySelector('input[name="role"]:checked');
  if (checkedRole && checkedRole.value === 'penerima') {
    document.getElementById('reg-note').style.display = 'block';
  }

  document.getElementById('registerForm').addEventListener('submit', function(e) {
    const p1 = document.getElementById('regPass').value;
    const p2 = document.getElementById('regPass2').value;
    if (p1 !== p2) {
      e.preventDefault();
      alert('Kata sandi dan konfirmasi tidak cocok!');
    }
  });

  document.querySelectorAll('.form-section:not(.visible)').forEach(f => {
    f.style.display = 'none';
  });
</script>
</body>
</html>