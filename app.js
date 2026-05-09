/**
 * CareDrop – app.js
 * Logika utama aplikasi: navigasi, login, katalog, form, tracking, relawan
 */

"use strict";

/* =============================================
   STATE
   ============================================= */
let cRole   = 'donatur';   // role yang sedang aktif
let aFil    = 'semua';     // filter katalog
let sQ      = '';          // search query katalog
let activeJobStatus = 1;   // status pekerjaan relawan aktif (1-4)

/* =============================================
   DATA KATALOG
   ============================================= */
const katalogData = [
  { id:1, org:'Panti Asuhan Al-Ikhlas',   kota:'Mataram', nama:'Seragam SD ukuran S & M',       kat:'pakaian',   urg:'high', ico:'👕', bg:'#edfbf3', butuh:50, kumpul:32 },
  { id:2, org:'Panti Asuhan Al-Ikhlas',   kota:'Mataram', nama:'Tas Sekolah Anak SD',            kat:'pakaian',   urg:'high', ico:'🎒', bg:'#edfbf3', butuh:20, kumpul:7  },
  { id:3, org:'Yayasan Peduli Anak NTB',  kota:'Lombok',  nama:'Buku Pelajaran SD Kelas 4–6',   kat:'buku',      urg:'med',  ico:'📚', bg:'#fef9ee', butuh:30, kumpul:21 },
  { id:4, org:'Yayasan Peduli Anak NTB',  kota:'Lombok',  nama:'Alat Tulis Pensil & Penggaris', kat:'buku',      urg:'low',  ico:'✏️', bg:'#fef9ee', butuh:60, kumpul:58 },
  { id:5, org:'Rumah Singgah Harapan',    kota:'Selong',  nama:'Laptop Bekas Layak Pakai',      kat:'elektronik',urg:'high', ico:'💻', bg:'#eff6ff', butuh:5,  kumpul:1  },
  { id:6, org:'Rumah Singgah Harapan',    kota:'Selong',  nama:'Kalkulator Ilmiah',              kat:'elektronik',urg:'med',  ico:'🧮', bg:'#eff6ff', butuh:10, kumpul:4  },
  { id:7, org:'Panti Asuhan Nur Hidayah', kota:'Praya',   nama:'Kasur Tipis / Alas Tidur',      kat:'perabot',   urg:'high', ico:'🛏️', bg:'#f5f3ff', butuh:15, kumpul:3  },
  { id:8, org:'Panti Asuhan Nur Hidayah', kota:'Praya',   nama:'Meja Belajar Lipat',             kat:'perabot',   urg:'med',  ico:'🪑', bg:'#f5f3ff', butuh:8,  kumpul:5  },
];

/* data pekerjaan antar-jemput untuk relawan */
const jobsData = [
  {
    id: 'CDR-20260404-007',
    item: '3 Buku Pelajaran SD',
    donatur: 'Andi Wijaya',
    donatur_hp: '0812-1234-5678',
    pickupAddr: 'Jl. Langko No. 22, Cakranegara, Mataram',
    yayasan: 'Yayasan Peduli Anak NTB',
    yayasanAddr: 'Jl. Saleh Sungkar No. 5, Ampenan, Mataram',
    waktu: 'Selasa 08.00 – 12.00',
    jarak: '4.2 km',
    status: 'menunggu',
  },
  {
    id: 'CDR-20260405-012',
    item: '2 Tas Sekolah',
    donatur: 'Rini Hartati',
    donatur_hp: '0813-9876-5432',
    pickupAddr: 'Jl. Bung Hatta No. 8, Mataram',
    yayasan: 'Panti Asuhan Al-Ikhlas',
    yayasanAddr: 'Jl. Pejanggik No. 12, Mataram',
    waktu: 'Selasa 13.00 – 17.00',
    jarak: '2.8 km',
    status: 'menunggu',
  },
  {
    id: 'CDR-20260406-015',
    item: '5 Seragam SD',
    donatur: 'Sabrina Salsabila',
    donatur_hp: '0812-3456-7890',
    pickupAddr: 'Jl. Majapahit No. 45, Mataram',
    yayasan: 'Panti Asuhan Al-Ikhlas',
    yayasanAddr: 'Jl. Pejanggik No. 12, Mataram',
    waktu: 'Rabu 08.00 – 12.00',
    jarak: '3.1 km',
    status: 'menunggu',
  },
];

/* =============================================
   PAGE NAVIGATION
   ============================================= */
function show(id) {
  // Tambahkan 'pg-register' ke dalam daftar array ini
  ['pg-landing', 'pg-login', 'pg-register', 'pg-app'].forEach(p => {
    const el = document.getElementById(p);
    if(el) el.classList.toggle('hide', p !== id);
  });
}

// Tambahkan fungsi baru ini di bawah show()
function goRegister() {
  show('pg-register');
}
function goLogin(role) {
  if (role) switchRole(role);
  show('pg-login');
}

function switchRole(r) {
  cRole = r;
  const tabMap = { donatur: 'don', penerima: 'pen', admin: 'adm' };
  ['don','pen','adm'].forEach(k => {
    const el = document.getElementById('tab-' + k);
    if (el) el.classList.remove('on');
  });
  const active = document.getElementById('tab-' + (tabMap[r] || 'don'));
  if (active) active.classList.add('on');
  // Sync hidden input agar backend tahu role hint (opsional, role asli dari DB)
  const hint = document.getElementById('l-role-hint');
  if (hint) hint.value = r;
}


/* =============================================
   AUTH
   ============================================= */

/* --- Validasi Register (client-side sebelum submit ke backend) --- */
function validateRegister() {
  const pass  = document.getElementById('r-pass').value;
  const pass2 = document.getElementById('r-pass2').value;
  const role  = document.getElementById('r-role').value;

  if (pass.length < 6) {
    toast('Sandi minimal 6 karakter'); return false;
  }
  if (pass !== pass2) {
    toast('Konfirmasi sandi tidak cocok'); return false;
  }
  if (!role) {
    toast('Pilih peran terlebih dahulu'); return false;
  }
  return true; // lanjut submit ke backend
}

/* --- Login (tab role hanya update hidden input, submit ke backend) --- */
function doLogin() {
  // Tidak dipakai lagi — form submit langsung ke proses_login.php
}

const roleLabelMap = { donatur: 'Donatur', penerima: 'Penerima', admin: 'Admin' };

function demoLogin(r) {
  cRole = r;
  loginAs(r);
}

const userProfiles = {
  donatur:  { name: 'Sabrina Salsabila',      init: 'SS', roleLbl: 'Donatur'  },
  penerima: { name: 'Panti Asuhan Al-Ikhlas', init: 'AI', roleLbl: 'Penerima' },
  admin:    { name: 'Admin CareDrop',          init: 'AC', roleLbl: 'Admin'    },
};

function loginAs(r) {
  cRole = r;
  const u = userProfiles[r];

  document.getElementById('uav').textContent    = u.init;
  document.getElementById('uname').textContent  = u.name;
  document.getElementById('urole').textContent  = u.roleLbl;

  buildNav(r);

  // tampilkan role container yang sesuai
  ['rd', 'ry', 'ra'].forEach(id => document.getElementById(id).classList.add('hide'));
  const roleMap = { donatur: 'rd', penerima: 'ry', admin: 'ra' };
  document.getElementById(roleMap[r]).classList.remove('hide');

  show('pg-app');

  const homeMap = { donatur: 'd-home', penerima: 'y-home', admin: 'a-home' };
  nav(homeMap[r]);

  if (r === 'donatur') document.getElementById('d-greet').textContent = u.name.split(' ')[0];
  renderKatalog();

  toast('Selamat datang, ' + u.name + ' 👋');
}

const navItems = {
  donatur: [
    { k: 'd-home',  l: '🏠 Dashboard' },
    { k: 'd-kat',   l: '📋 Katalog' },
    { k: 'd-don',   l: '🎁 Donasi' },
    { k: 'd-lacak', l: '📍 Lacak' },
    { k: 'd-profil',l: '👤 Profil' },
  ],
  penerima: [
    { k: 'y-home',  l: '🏠 Dashboard' },
    { k: 'y-kat',   l: '📋 Katalog' },
    { k: 'y-riw',   l: '📦 Riwayat' },
    { k: 'y-profil',l: '🏠 Profil' },
  ],
  admin: [
    { k: 'a-home',  l: '🏠 Dashboard' },
    { k: 'a-users', l: '👥 Pengguna' },
    { k: 'a-donasi',l: '📦 Donasi' },
    { k: 'a-verif', l: '✅ Verifikasi' },
    { k: 'a-lap',   l: '📊 Laporan' },
  ],
};

function buildNav(r) {
  const n = document.getElementById('app-nav');
  n.innerHTML = navItems[r]
    .map(i => `<button id="nb-${i.k}" onclick="nav('${i.k}')">${i.l}</button>`)
    .join('');
}

function nav(k) {
  const roleEl = { donatur: 'rd', penerima: 'ry', admin: 'ra' };
  document.querySelectorAll('#' + (roleEl[cRole] || 'rd') + ' .inner-page')
          .forEach(p => p.classList.remove('on'));

  const t = document.getElementById('ip-' + k);
  if (t) t.classList.add('on');

  document.querySelectorAll('#app-nav button').forEach(b => b.classList.remove('on'));
  const nb = document.getElementById('nb-' + k);
  if (nb) nb.classList.add('on');

  window.scrollTo(0, 0);
}

function doLogout() {
  window.location.href = 'backend/logout.php';
}

/* =============================================
   KATALOG (Donatur)
   ============================================= */
function renderKatalog() {
  const g = document.getElementById('kat-grid');
  if (!g) return;

  let data = katalogData;
  if (aFil !== 'semua') data = data.filter(d => d.kat === aFil);
  if (sQ)               data = data.filter(d => (d.nama + d.org).toLowerCase().includes(sQ.toLowerCase()));

  if (!data.length) {
    g.innerHTML = '<p style="padding:22px;color:var(--text2)">Tidak ada hasil ditemukan.</p>';
    return;
  }

  const urgLabel = { high: 'Urgen', med: 'Sedang', low: 'Terpenuhi' };
  const urgClass = { high: 'tr',    med: 'ta',     low: 'tg'        };

  g.innerHTML = data.map(d => {
    const pct = Math.round(d.kumpul / d.butuh * 100);
    return `
      <div class="kc">
        <div class="kc-img" style="background:${d.bg}">
          <span style="font-size:46px">${d.ico}</span>
          <span class="urg tag ${urgClass[d.urg]}">${urgLabel[d.urg]}</span>
        </div>
        <div class="kc-body">
          <div class="kc-org">${d.org}</div>
          <h4>${d.nama}</h4>
          <div class="prog-row">
            <div class="prog"><div class="pf" style="width:${pct}%"></div></div>
            <span>${d.kumpul}/${d.butuh}</span>
          </div>
        </div>
        <div class="kc-foot">
          <span class="kc-loc">📍 ${d.kota}</span>
          <button class="btn btn-green btn-xs" onclick="piliItem('${d.nama}','${d.org}')">Donasikan</button>
        </div>
      </div>`;
  }).join('');
}

function setChip(el, f) {
  document.querySelectorAll('#ip-d-kat .chip').forEach(c => c.classList.remove('on'));
  el.classList.add('on');
  aFil = f;
  renderKatalog();
}

function filterKat(q) {
  sQ = q;
  renderKatalog();
}

function piliItem(nama, org) {
  toast(`Siap donasi "${nama}" ke ${org}`);
  setTimeout(() => nav('d-don'), 700);
}

/* =============================================
   DONASI FORM
   ============================================= */
function pickSlot(el) {
  document.querySelectorAll('.slot').forEach(s => s.classList.remove('on'));
  el.classList.add('on');
}

function submitDon() {
  toast('🎉 Donasi terdaftar! ID: CDR-20260406-015');
  setTimeout(() => { nav('d-lacak'); showTrack(); }, 900);
}

/* =============================================
   TRACKING (Donatur)
   ============================================= */
function showTrack() {
  document.getElementById('tr-empty').classList.add('hide');
  document.getElementById('tr-result').classList.remove('hide');
}

/* =============================================
   YAYASAN
   ============================================= */
function konfirm(btn) {
  toast('📸 Kamera dibuka — foto bukti penerimaan fisik');
  btn.textContent = '✓ Dikonfirmasi';
  btn.disabled    = true;
  btn.style.opacity = '.5';
}

function hapusRow(btn) {
  btn.closest('tr').remove();
  toast('Kebutuhan dihapus dari katalog');
}

/* =============================================
   LOGISTICS & COURIER ENGINE
   ============================================= */
const couriers = [
  { id: 'gosend', nama: 'GoSend Instant', tipe: 'instant', ico: '🛵', eta: '1 - 2 Jam', tarifDasar: 15000, perKm: 2500 },
  { id: 'grab', nama: 'GrabExpress', tipe: 'instant', ico: '🏍️', eta: '1 - 2 Jam', tarifDasar: 14000, perKm: 2500 },
  { id: 'jne', nama: 'JNE Reguler', tipe: 'reguler', ico: '📦', eta: '2 - 3 Hari', tarifDasar: 20000, perKm: 0 },
  { id: 'jnt', nama: 'J&T Express', tipe: 'reguler', ico: '🚚', eta: '2 - 3 Hari', tarifDasar: 18000, perKm: 0 },
  { id: 'sicepat', nama: 'SiCepat HALU', tipe: 'reguler', ico: '⚡', eta: '2 - 4 Hari', tarifDasar: 16000, perKm: 0 },
  { id: 'pos', nama: 'Pos Indonesia', tipe: 'kargo', ico: '🏤', eta: '3 - 7 Hari', tarifDasar: 35000, perKm: 0 }, // Cocok untuk barang berat/antar pulau
];

// Simulasi Jarak antar Kota (dalam km)
const distanceMap = {
  'Mataram-Mataram': 5,
  'Mataram-Lombok': 25,
  'Mataram-Selong': 55,
  'Mataram-Praya': 30,
};

let selectedCourier = null;

function hitungEstimasiPengiriman() {
  const asal = document.getElementById('kota-asal').value;
  const yayasanSelect = document.getElementById('pilih-yayasan');
  const namaYayasan = yayasanSelect.options[yayasanSelect.selectedIndex].text;
  
  // Ekstrak kota tujuan dari nama yayasan (mockup)
  let tujuan = 'Mataram';
  if (namaYayasan.includes('Lombok')) tujuan = 'Lombok';
  if (namaYayasan.includes('Selong')) tujuan = 'Selong';
  if (namaYayasan.includes('Praya')) tujuan = 'Praya';

  const routeKey = `${asal}-${tujuan}`;
  const jarak = distanceMap[routeKey] || 150; // Jika tidak ada di map, anggap luar pulau/jauh (150km)
  
  const beratBarang = parseInt(document.getElementById('berat-barang').value) || 1;
  const resContainer = document.getElementById('courier-result');
  resContainer.innerHTML = '';

  // Auto Delivery Method Logic
  let recommended = [];
  if (jarak <= 15 && beratBarang <= 20) {
    // Jarak dekat & ringan -> Instant Courier
    recommended = couriers.filter(c => c.tipe === 'instant');
  } else if (beratBarang > 20) {
    // Barang sangat berat -> Kargo
    recommended = couriers.filter(c => c.tipe === 'kargo');
  } else {
    // Antar kota / jarak menengah -> Reguler
    recommended = couriers.filter(c => c.tipe === 'reguler' || c.tipe === 'kargo');
  }

  // Generate UI
  let html = `<div style="font-size:.8rem; color:var(--text2); margin-bottom:10px;">Rute: <strong>${asal} → ${tujuan}</strong> (Est. Jarak: ${jarak} km)</div>`;
  html += `<div class="courier-list">`;
  
  recommended.forEach(c => {
    let biaya = c.tipe === 'instant' ? c.tarifDasar + (jarak * c.perKm) : (c.tarifDasar * beratBarang);
    // Format Rupiah
    let fBiaya = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(biaya);
    
    html += `
      <div class="courier-card" onclick="pilihKurir(this, '${c.id}', '${fBiaya}')">
        <div class="cc-ico">${c.ico}</div>
        <div class="cc-info">
          <h4>${c.nama}</h4>
          <span>Est. Tiba: ${c.eta}</span>
        </div>
        <div class="cc-price">${fBiaya}</div>
      </div>
    `;
  });
  
  html += `</div>`;
  resContainer.innerHTML = html;
}

function pilihKurir(el, id, harga) {
  document.querySelectorAll('.courier-card').forEach(c => c.classList.remove('on'));
  el.classList.add('on');
  selectedCourier = id;
  toast(`Kurir dipilih: ${id.toUpperCase()} - ${harga}`);
}

function submitDon() {
  if (!selectedCourier) {
    toast('Silakan pilih metode pengiriman terlebih dahulu!');
    return;
  }
  // Generate Resi Otomatis
  const resi = 'CD' + Math.random().toString(36).substring(2, 8).toUpperCase() + 'ID';
  toast(`🎉 Donasi diproses! No. Resi: ${resi}`);
  setTimeout(() => { 
    nav('d-lacak'); 
    document.getElementById('input-resi').value = resi;
    showTrack(resi); 
  }, 1500);
}

/* =============================================
   TRACKING RESI
   ============================================= */
function showTrack(resiManual = null) {
  const resi = resiManual || document.getElementById('input-resi').value;
  if (!resi) {
    toast('Masukkan nomor resi!');
    return;
  }
  
  document.getElementById('tr-empty').classList.add('hide');
  const resDiv = document.getElementById('tr-result');
  resDiv.classList.remove('hide');
  
  // Render status tracking mockup berdasarkan resi
  resDiv.innerHTML = `
    <div class="dcrd">
      <div class="dc-head">
        <div>
          <div class="dc-id">RESI: ${resi}</div>
          <div class="dc-ttl">Menuju: Yayasan Peduli Anak NTB</div>
        </div>
        <span class="tag ta">🚚 Sedang Dikirim</span>
      </div>
      <div class="tl">
        <div class="tl-item"><div class="tl-dot done">✓</div><div class="tl-con"><h4>Pickup Request Dibuat</h4><p>Sistem merespon permintaan ke kurir.</p><time>Hari ini, 08.40</time></div></div>
        <div class="tl-item"><div class="tl-dot done">✓</div><div class="tl-con"><h4>Barang Dipickup Kurir</h4><p>Kurir telah mengambil paket dari donatur.</p><time>Hari ini, 09.20</time></div></div>
        <div class="tl-item"><div class="tl-dot cur">→</div><div class="tl-con"><h4>Paket dalam Perjalanan</h4><p>Paket dibawa menuju hub transit tujuan.</p><time>Hari ini, 10.15</time></div></div>
        <div class="tl-item"><div class="tl-dot" style="color:var(--text3);font-size:9px">···</div><div class="tl-con"><h4 style="color:var(--text3)">Menunggu Penerimaan Yayasan</h4></div></div>
      </div>
    </div>
  `;
}

/* =============================================
   TOAST
   ============================================= */
let toastTimer;
function toast(msg) {
  const el = document.getElementById('toast');
  el.innerHTML = `<span style="color:var(--g6)">✦</span> ${msg}`;
  el.classList.add('on');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('on'), 2800);
}

/* =============================================
   INIT
   ============================================= */
document.addEventListener('DOMContentLoaded', () => {
  // Jika ada session PHP aktif, langsung masuk ke app
  if (typeof PHP_SESSION !== 'undefined' && PHP_SESSION && PHP_SESSION.role) {
    const r = PHP_SESSION.role;
    const initials = PHP_SESSION.nama.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
    userProfiles[r] = {
      name:    PHP_SESSION.nama,
      init:    initials,
      roleLbl: roleLabelMap[r] || r,
    };
    loginAs(r);
  } else {
    show('pg-landing');
  }
});