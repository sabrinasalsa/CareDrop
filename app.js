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
  ['pg-landing', 'pg-login', 'pg-app'].forEach(p => {
    document.getElementById(p).classList.toggle('hide', p !== id);
  });
}

function goLogin(role) {
  if (role) switchRole(role);
  show('pg-login');
}

function switchRole(r) {
  cRole = r;
  ['don','yay','rel'].forEach(k => document.getElementById('tab-' + k).classList.remove('on'));
  document.getElementById('tab-' + r.substring(0,3)).classList.add('on');
}

/* =============================================
   AUTH
   ============================================= */
function doLogin() {
  const e = document.getElementById('l-email').value;
  const p = document.getElementById('l-pass').value;
  if (!e || !p) { toast('Isi email dan kata sandi'); return; }
  loginAs(cRole);
}

function demoLogin(r) {
  cRole = r;
  loginAs(r);
}

const userProfiles = {
  donatur: { name: 'Sabrina Salsabila', init: 'SS', roleLbl: 'Donatur' },
  yayasan: { name: 'Panti Asuhan Al-Ikhlas', init: 'AI', roleLbl: 'Yayasan' },
  relawan: { name: 'Budi Santoso', init: 'BS', roleLbl: 'Relawan' },
};

function loginAs(r) {
  cRole = r;
  const u = userProfiles[r];

  document.getElementById('uav').textContent    = u.init;
  document.getElementById('uname').textContent  = u.name;
  document.getElementById('urole').textContent  = u.roleLbl;

  buildNav(r);

  // tampilkan role container yang sesuai
  ['rd', 'ry', 'rr'].forEach(id => document.getElementById(id).classList.add('hide'));
  const roleMap = { donatur: 'rd', yayasan: 'ry', relawan: 'rr' };
  document.getElementById(roleMap[r]).classList.remove('hide');

  show('pg-app');

  const homeMap = { donatur: 'd-home', yayasan: 'y-home', relawan: 'r-home' };
  nav(homeMap[r]);

  if (r === 'donatur') document.getElementById('d-greet').textContent = u.name.split(' ')[0];
  if (r === 'relawan') renderJobs();
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
  yayasan: [
    { k: 'y-home',  l: '🏠 Dashboard' },
    { k: 'y-kat',   l: '📋 Katalog' },
    { k: 'y-riw',   l: '📦 Riwayat' },
    { k: 'y-profil',l: '🏠 Profil' },
  ],
  relawan: [
    { k: 'r-home',  l: '🏠 Dashboard' },
    { k: 'r-jobs',  l: '📦 Tugas Jemput' },
    { k: 'r-aktif', l: '🚗 Sedang Berjalan' },
    { k: 'r-hist',  l: '📋 Riwayat' },
    { k: 'r-profil',l: '👤 Profil' },
  ],
};

function buildNav(r) {
  const n = document.getElementById('app-nav');
  n.innerHTML = navItems[r]
    .map(i => `<button id="nb-${i.k}" onclick="nav('${i.k}')">${i.l}</button>`)
    .join('');
}

function nav(k) {
  const roleEl = { donatur: 'rd', yayasan: 'ry', relawan: 'rr' };
  document.querySelectorAll('#' + roleEl[cRole] + ' .inner-page')
          .forEach(p => p.classList.remove('on'));

  const t = document.getElementById('ip-' + k);
  if (t) t.classList.add('on');

  document.querySelectorAll('#app-nav button').forEach(b => b.classList.remove('on'));
  const nb = document.getElementById('nb-' + k);
  if (nb) nb.classList.add('on');

  window.scrollTo(0, 0);
}

function doLogout() {
  show('pg-landing');
  toast('Kamu telah keluar dari akun');
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
   RELAWAN – Daftar Tugas
   ============================================= */
function renderJobs() {
  const g = document.getElementById('jobs-grid');
  if (!g) return;

  const pending = jobsData.filter(j => j.status === 'menunggu');

  if (!pending.length) {
    g.innerHTML = `
      <div style="grid-column:1/-1;text-align:center;padding:44px">
        <div style="font-size:40px;margin-bottom:10px">🎉</div>
        <p style="font-weight:700;color:var(--g1)">Tidak ada tugas baru</p>
        <p style="font-size:.85rem;color:var(--text2);margin-top:4px">Semua tugas penjemputan sudah diambil</p>
      </div>`;
    return;
  }

  g.innerHTML = pending.map(j => `
    <div class="job-card" id="job-${j.id}">
      <div class="job-card-head">
        <div>
          <div class="job-id">${j.id}</div>
          <div class="job-item">${j.item}</div>
        </div>
        <span class="tag tb">Baru</span>
      </div>
      <div class="job-body">
        <div class="info-row"><span class="lbl">👤 Donatur</span><span>${j.donatur} · ${j.donatur_hp}</span></div>
        <div class="info-row"><span class="lbl">📍 Jemput</span><span>${j.pickupAddr}</span></div>
        <div class="info-row"><span class="lbl">🏠 Tujuan</span><span>${j.yayasan}<br><small style="color:var(--text3)">${j.yayasanAddr}</small></span></div>
        <div class="info-row"><span class="lbl">🕐 Waktu</span><span>${j.waktu}</span></div>
        <div class="info-row"><span class="lbl">📏 Jarak</span><span>${j.jarak}</span></div>
      </div>
      <div class="job-footer">
        <button class="btn btn-ghost btn-sm" onclick="tolakJob('${j.id}')">Tolak</button>
        <button class="btn btn-green btn-sm" onclick="ambilJob('${j.id}')">✓ Ambil Tugas</button>
      </div>
    </div>`).join('');
}

function ambilJob(id) {
  const job = jobsData.find(j => j.id === id);
  if (!job) return;
  job.status = 'aktif';
  toast('✓ Tugas diambil! Navigasi menuju lokasi donatur…');
  activeJobStatus = 1;
  updateActiveJob(job);
  setTimeout(() => nav('r-aktif'), 800);
  renderJobs();
  updateRelDashboard();
}

function tolakJob(id) {
  const job = jobsData.find(j => j.id === id);
  if (!job) return;
  job.status = 'ditolak';
  toast('Tugas ditolak. Tugas akan dialihkan ke relawan lain.');
  renderJobs();
  updateRelDashboard();
  const card = document.getElementById('job-' + id);
  if (card) card.remove();
}

/* =============================================
   RELAWAN – Tugas Aktif
   ============================================= */
function updateActiveJob(job) {
  const container = document.getElementById('active-job-container');
  if (!container) return;

  container.innerHTML = `
    <div class="active-job">
      <div class="active-job-head">
        <h3>🚗 Sedang Mengantarkan Barang</h3>
        <span>${job.id}</span>
      </div>
      <div class="active-job-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px">
          <div>
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);margin-bottom:4px">Barang</div>
            <div style="font-weight:700;color:var(--g1)">${job.item}</div>
          </div>
          <div>
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);margin-bottom:4px">Donatur</div>
            <div style="font-weight:600;color:var(--text)">${job.donatur}</div>
            <div style="font-size:.78rem;color:var(--text3)">${job.donatur_hp}</div>
          </div>
          <div>
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);margin-bottom:4px">📍 Lokasi Jemput</div>
            <div style="font-size:.86rem;color:var(--text)">${job.pickupAddr}</div>
          </div>
          <div>
            <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text3);margin-bottom:4px">🏠 Tujuan Antar</div>
            <div style="font-size:.86rem;color:var(--text)">${job.yayasan}</div>
            <div style="font-size:.78rem;color:var(--text3)">${job.yayasanAddr}</div>
          </div>
        </div>

        <div class="status-steps" id="status-steps">
          <button class="sts ${activeJobStatus >= 1 ? (activeJobStatus === 1 ? 'on' : 'done') : ''}" onclick="setJobStatus(1)">Menuju Donatur</button>
          <button class="sts ${activeJobStatus >= 2 ? (activeJobStatus === 2 ? 'on' : 'done') : ''}" onclick="setJobStatus(2)">Ambil Barang</button>
          <button class="sts ${activeJobStatus >= 3 ? (activeJobStatus === 3 ? 'on' : 'done') : ''}" onclick="setJobStatus(3)">Menuju Yayasan</button>
          <button class="sts ${activeJobStatus >= 4 ? (activeJobStatus === 4 ? 'on' : 'done') : ''}" onclick="setJobStatus(4)">Serah Terima</button>
        </div>

        <div id="status-hint" style="font-size:.84rem;color:var(--text2);background:var(--surf);border-radius:8px;padding:10px 14px;margin-top:4px"></div>

        <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
          <button class="btn btn-ghost btn-sm" onclick="toast('Menghubungi donatur…')">📞 Hubungi Donatur</button>
          <button class="btn btn-amber btn-sm" id="btn-next-status" onclick="nextStatus()">Lanjut →</button>
        </div>
      </div>
    </div>`;

  refreshStatusHint();
}

const statusHints = [
  'Pergi ke lokasi donatur untuk mengambil barang. Hubungi donatur jika perlu.',
  'Konfirmasi pengambilan barang. Periksa kondisi dan jumlah sesuai deskripsi.',
  'Bawa barang ke lokasi yayasan penerima. Hati-hati di jalan!',
  'Serahkan barang ke pihak yayasan. Minta foto bukti penerimaan.',
];

function refreshStatusHint() {
  const hint = document.getElementById('status-hint');
  if (hint) hint.textContent = '💡 ' + (statusHints[activeJobStatus - 1] || '');
}

function setJobStatus(s) {
  if (s > activeJobStatus) return; // tidak bisa skip mundur
  activeJobStatus = s;
  const steps = document.querySelectorAll('.sts');
  steps.forEach((el, i) => {
    el.classList.remove('on', 'done');
    if (i + 1 < s)       el.classList.add('done');
    else if (i + 1 === s) el.classList.add('on');
  });
  refreshStatusHint();
}

function nextStatus() {
  if (activeJobStatus < 4) {
    activeJobStatus++;
    const steps = document.querySelectorAll('.sts');
    steps.forEach((el, i) => {
      el.classList.remove('on', 'done');
      if (i + 1 < activeJobStatus)       el.classList.add('done');
      else if (i + 1 === activeJobStatus) el.classList.add('on');
    });
    refreshStatusHint();
    const msgs = [
      null,
      'Status diperbarui: sedang menuju ke donatur',
      '✓ Barang berhasil diambil dari donatur',
      'Status diperbarui: menuju yayasan penerima',
    ];
    if (msgs[activeJobStatus]) toast(msgs[activeJobStatus]);
  } else {
    // selesai
    toast('🎉 Tugas selesai! Terima kasih, relawan hebat!');
    const aktif = jobsData.find(j => j.status === 'aktif');
    if (aktif) aktif.status = 'selesai';
    setTimeout(() => {
      nav('r-hist');
      renderRelHistory();
      updateRelDashboard();
    }, 1000);
  }
}

/* =============================================
   RELAWAN – Riwayat
   ============================================= */
function renderRelHistory() {
  const tbody = document.getElementById('rel-hist-tbody');
  if (!tbody) return;

  const done = jobsData.filter(j => j.status === 'selesai');
  const base = [
    { id: 'CDR-20260320-003', item: 'Tas Sekolah 2 buah',   donatur: 'Dian Permata',     yayasan: 'Al-Ikhlas',           tanggal: '20 Mar 2026', km: '2.1', rating: 5 },
    { id: 'CDR-20260328-009', item: '4 Buku Cerita Anak',   donatur: 'Hari Purnomo',     yayasan: 'Yayasan Peduli Anak', tanggal: '28 Mar 2026', km: '3.7', rating: 5 },
  ];

  const all = [
    ...base,
    ...done.map(j => ({ id: j.id, item: j.item, donatur: j.donatur, yayasan: j.yayasan, tanggal: 'Hari ini', km: j.jarak.replace(' km',''), rating: 0 })),
  ];

  tbody.innerHTML = all.map(r => `
    <tr>
      <td style="font-family:monospace;font-size:.76rem">${r.id}</td>
      <td>${r.item}</td>
      <td>${r.donatur}</td>
      <td>${r.yayasan}</td>
      <td>${r.tanggal}</td>
      <td>${r.km} km</td>
      <td>
        ${r.rating > 0
          ? '⭐'.repeat(r.rating)
          : '<span class="tag ta">Menunggu</span>'}
      </td>
    </tr>`).join('');
}

/* =============================================
   RELAWAN – Dashboard Stats
   ============================================= */
function updateRelDashboard() {
  const selesai = jobsData.filter(j => j.status === 'selesai').length + 2; // + data awal
  const aktif   = jobsData.filter(j => j.status === 'aktif').length;
  const pending  = jobsData.filter(j => j.status === 'menunggu').length;

  const el = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
  el('rel-stat-selesai', selesai);
  el('rel-stat-aktif',   aktif);
  el('rel-stat-pending', pending);
  const km = (selesai * 3.2).toFixed(1);
  el('rel-stat-km', km);
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
  // pastikan landing tampil pertama
  show('pg-landing');
});
