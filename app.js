/**
 * CareDrop – app.js  v2.0
 * Logika utama: navigasi, auth, katalog, donasi, tracking, dashboard dinamis per user
 */

"use strict";

/* ═══════════════════════════════════════
   STATE GLOBAL
   ═══════════════════════════════════════ */
let cRole = 'donatur';
let aFil  = 'semua';
let sQ    = '';
let selectedCourier = null;

let currentUser = {
  id: null, nama: '', email: '', no_telp: '', alamat: '', role: '',
};

/* ═══════════════════════════════════════
   KATALOG FALLBACK (tampil saat demo / DB kosong)
   ═══════════════════════════════════════ */
const katalogFallback = [
  { id:1, org:'Panti Asuhan Al-Ikhlas',   kota:'Mataram', nama:'Seragam SD ukuran S & M',       kat:'pakaian',    urg:'high', ico:'👕', bg:'#edfbf3', butuh:50, kumpul:32 },
  { id:2, org:'Panti Asuhan Al-Ikhlas',   kota:'Mataram', nama:'Tas Sekolah Anak SD',            kat:'pakaian',    urg:'high', ico:'🎒', bg:'#edfbf3', butuh:20, kumpul:7  },
  { id:3, org:'Yayasan Peduli Anak NTB',  kota:'Lombok',  nama:'Buku Pelajaran SD Kelas 4–6',   kat:'buku',       urg:'med',  ico:'📚', bg:'#fef9ee', butuh:30, kumpul:21 },
  { id:4, org:'Yayasan Peduli Anak NTB',  kota:'Lombok',  nama:'Alat Tulis Pensil & Penggaris', kat:'buku',       urg:'low',  ico:'✏️', bg:'#fef9ee', butuh:60, kumpul:58 },
  { id:5, org:'Rumah Singgah Harapan',    kota:'Selong',  nama:'Laptop Bekas Layak Pakai',      kat:'elektronik', urg:'high', ico:'💻', bg:'#eff6ff', butuh:5,  kumpul:1  },
  { id:6, org:'Rumah Singgah Harapan',    kota:'Selong',  nama:'Kalkulator Ilmiah',             kat:'elektronik', urg:'med',  ico:'🧮', bg:'#eff6ff', butuh:10, kumpul:4  },
  { id:7, org:'Panti Asuhan Nur Hidayah', kota:'Praya',   nama:'Kasur Tipis / Alas Tidur',      kat:'perabot',    urg:'high', ico:'🛏️', bg:'#f5f3ff', butuh:15, kumpul:3  },
  { id:8, org:'Panti Asuhan Nur Hidayah', kota:'Praya',   nama:'Meja Belajar Lipat',            kat:'perabot',    urg:'med',  ico:'🪑', bg:'#f5f3ff', butuh:8,  kumpul:5  },
];
let katalogData = [...katalogFallback];

/* ═══════════════════════════════════════
   HELPER UMUM
   ═══════════════════════════════════════ */
function fmtTgl(str) {
  if (!str) return '—';
  const d = new Date(str);
  return d.toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' });
}

const statusBadge = {
  selesai    : '<span class="tag tg">Selesai</span>',
  dikirim    : '<span class="tag ta">Dalam Perjalanan</span>',
  diproses   : '<span class="tag ta">Diproses</span>',
  menunggu   : '<span class="tag tb">Menunggu</span>',
  dibatalkan : '<span class="tag tr">Dibatalkan</span>',
};
function badge(s) { return statusBadge[s] || `<span class="tag">${s || '—'}</span>`; }

function setText(id, v)     { const e = document.getElementById(id); if(e) e.textContent = v ?? '—'; }
function setHtml(id, v)     { const e = document.getElementById(id); if(e) e.innerHTML   = v; }
function setInputVal(id, v) { const e = document.getElementById(id); if(e) e.value       = v || ''; }
function setAv(id, v)       { const e = document.getElementById(id); if(e) e.textContent = v || '?'; }

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;')
                        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* setStat: ganti innerHTML elemen stat value */
function setStat(id, html) { const e = document.getElementById(id); if(e) e.innerHTML = html; }

/* bigNum: bungkus angka dalam <big> berwarna */
function bigNum(val, color) {
  const clrMap = { green:'var(--g5)', amber:'#d97706', red:'#dc2626' };
  const style  = color ? `style="color:${clrMap[color] || color}"` : '';
  return `<big ${style}>${val ?? 0}</big>`;
}

function emptyRow(cols, msg) {
  return `<tr><td colspan="${cols}" style="text-align:center;padding:22px 12px;color:var(--text2)">${msg}</td></tr>`;
}

/* ═══════════════════════════════════════
   PAGE NAVIGATION (halaman besar)
   ═══════════════════════════════════════ */
function show(id) {
  ['pg-landing','pg-login','pg-register','pg-app'].forEach(p => {
    const el = document.getElementById(p);
    if (el) el.classList.toggle('hide', p !== id);
  });
}
function goRegister() { show('pg-register'); }
function goLogin(role) { if (role) switchRole(role); show('pg-login'); }

function switchRole(r) {
  cRole = r;
  ['don','pen','adm'].forEach(k => document.getElementById('tab-'+k)?.classList.remove('on'));
  const tabMap = { donatur:'don', penerima:'pen', admin:'adm' };
  document.getElementById('tab-'+(tabMap[r]||'don'))?.classList.add('on');
  const hint = document.getElementById('l-role-hint');
  if (hint) hint.value = r;
}

function goToProfile() {
  const map = { donatur:'d-profil', penerima:'y-profil', admin:'a-profil' };
  nav(map[cRole] || 'd-profil');
}

/* ═══════════════════════════════════════
   REGISTER VALIDATION
   ═══════════════════════════════════════ */
function validateRegister() {
  const pass  = document.getElementById('r-pass').value;
  const pass2 = document.getElementById('r-pass2').value;
  const role  = document.getElementById('r-role').value;
  if (pass.length < 6)  { toast('Sandi minimal 6 karakter'); return false; }
  if (pass !== pass2)   { toast('Konfirmasi sandi tidak cocok'); return false; }
  if (!role)            { toast('Pilih peran terlebih dahulu'); return false; }
  return true;
}

/* ═══════════════════════════════════════
   DEMO LOGIN (tanpa DB)
   ═══════════════════════════════════════ */
const demoProfiles = {
  donatur : { nama:'Sabrina Salsabila',      email:'sabrina@email.com',   no_telp:'0812-3456-7890', alamat:'Jl. Majapahit No. 45, Mataram' },
  penerima: { nama:'Panti Asuhan Al-Ikhlas', email:'alikhlas@yayasan.id', no_telp:'(0370) 632-xxx', alamat:'Jl. Pejanggik No. 12, Mataram' },
  admin   : { nama:'Admin CareDrop',          email:'admin@caredrop.id',   no_telp:'—',              alamat:'—' },
};

function demoLogin(r) {
  const p = demoProfiles[r];
  currentUser = { id:0, nama:p.nama, email:p.email, no_telp:p.no_telp, alamat:p.alamat, role:r };
  loginAs(r, true);
}

/* ═══════════════════════════════════════
   LOGIN AS — titik masuk utama post-auth
   ═══════════════════════════════════════ */
const roleLabelMap = { donatur:'Donatur', penerima:'Penerima', admin:'Admin' };

function loginAs(r, isDemo = false) {
  cRole = r;

  const initials = currentUser.nama
    ? currentUser.nama.split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase()
    : '?';

  setText('uav',   initials);
  setText('uname', currentUser.nama || '—');
  setText('urole', roleLabelMap[r]  || r);

  ['rd','ry','ra'].forEach(id => document.getElementById(id)?.classList.add('hide'));
  const roleEl = { donatur:'rd', penerima:'ry', admin:'ra' };
  document.getElementById(roleEl[r])?.classList.remove('hide');

  buildNav(r);
  show('pg-app');

  const homeMap = { donatur:'d-home', penerima:'y-home', admin:'a-home' };
  nav(homeMap[r]);

  setText('d-greet', currentUser.nama.split(' ')[0]);
  setText('y-greet', currentUser.nama);

  renderProfile(r);
  toast('Selamat datang, ' + currentUser.nama + ' 👋');

  if (!isDemo) {
    loadDashboard(r);
    loadKatalogDB();   // ambil katalog dari DB
    startNotifPolling(); // mulai polling notifikasi
  } else {
    renderDashboardDemo(r);
    renderKatalog();   // pakai fallback data
  }
}

/* ═══════════════════════════════════════
   FETCH DASHBOARD DATA DARI SERVER
   ═══════════════════════════════════════ */
async function loadDashboard(r) {
  showStatsSkeleton(r);
  try {
    const res = await fetch('backend/dashboard_data.php', { credentials:'same-origin' });

    // Baca teks dulu untuk debug jika bukan JSON
    const rawText = await res.text();

    let data;
    try {
      data = JSON.parse(rawText);
    } catch (parseErr) {
      // Tampilkan preview teks untuk membantu debug
      const preview = rawText.substring(0, 200).replace(/</g,'&lt;');
      console.error('[CareDrop] Bukan JSON. Preview:', rawText.substring(0, 300));
      throw new Error(`Respons server bukan JSON. Preview: ${rawText.substring(0, 120)}`);
    }

    if (!res.ok || data.error) {
      throw new Error(data.error || `HTTP ${res.status}`);
    }

    if (r === 'donatur')  renderDashboardDonatur(data);
    if (r === 'penerima') renderDashboardPenerima(data);
    if (r === 'admin')    renderDashboardAdmin(data);

  } catch (err) {
    console.error('[CareDrop] Dashboard error:', err);
    showStatsError(r, err.message);
  }
}

/* ─── Skeleton loading ─── */
const skelHtml = `<div class="sk-box"></div>`;

function showStatsSkeleton(r) {
  if (r === 'donatur') {
    ['stat-d-total','stat-d-berjalan','stat-d-selesai','stat-d-sertif'].forEach(id => setStat(id, skelHtml));
    setHtml('tbl-d-riw', `<tr><td colspan="6" class="tbl-loading">⏳ Memuat data donasi Anda...</td></tr>`);
  }
  if (r === 'penerima') {
    ['stat-y-total','stat-y-aktif','stat-y-konfirm','stat-y-pct'].forEach(id => setStat(id, skelHtml));
    setHtml('tbl-y-konfirm', `<tr><td colspan="5" class="tbl-loading">⏳ Memuat data konfirmasi...</td></tr>`);
    setHtml('tbl-y-riw',     `<tr><td colspan="6" class="tbl-loading">⏳ Memuat riwayat donasi...</td></tr>`);
  }
  if (r === 'admin') {
    ['stat-a-user','stat-a-aktif','stat-a-verif','stat-a-barang'].forEach(id => setStat(id, skelHtml));
    setHtml('tbl-a-pending', `<tr><td colspan="5" class="tbl-loading">⏳ Memuat data verifikasi...</td></tr>`);
    setHtml('tbl-a-riw',     `<tr><td colspan="5" class="tbl-loading">⏳ Memuat data donasi...</td></tr>`);
  }
}

/* ─── Error state ─── */
function showStatsError(r, msg) {
  const errStat = `<div class="stat-err">⚠️ Gagal memuat<br><small>${esc(msg || 'Refresh halaman')}</small></div>`;
  const errRow  = (cols) => `<tr><td colspan="${cols}" class="tbl-err">⚠️ Gagal memuat data. <button class="btn btn-ghost btn-xs" onclick="loadDashboard('${r}')">Coba lagi</button></td></tr>`;

  if (r === 'donatur') {
    ['stat-d-total','stat-d-berjalan','stat-d-selesai','stat-d-sertif'].forEach(id => setStat(id, errStat));
    setHtml('tbl-d-riw', errRow(6));
  }
  if (r === 'penerima') {
    ['stat-y-total','stat-y-aktif','stat-y-konfirm','stat-y-pct'].forEach(id => setStat(id, errStat));
    setHtml('tbl-y-konfirm', errRow(5));
    setHtml('tbl-y-riw',     errRow(6));
  }
  if (r === 'admin') {
    ['stat-a-user','stat-a-aktif','stat-a-verif','stat-a-barang'].forEach(id => setStat(id, errStat));
    setHtml('tbl-a-pending', errRow(5));
    setHtml('tbl-a-riw',     errRow(5));
  }
}

/* ─── RENDER: DONATUR ─── */
function renderDashboardDonatur(data) {
  const s = data.stats || {};

  // Stat cards: tampilkan angka nyata dari DB
  const total = s.total_donasi ?? 0;
  const berjalan = s.berjalan ?? 0;
  const selesai = s.selesai ?? 0;
  const sertif = s.sertifikat ?? 0;

  setStat('stat-d-total',    `<big>${total}</big><span class="stat-sub">${total === 0 ? 'Belum ada donasi' : 'kali berdonasi'}</span>`);
  setStat('stat-d-berjalan', `<big style="color:#d97706">${berjalan}</big><span class="stat-sub">${berjalan === 0 ? 'Semua selesai ✅' : 'sedang dikirim'}</span>`);
  setStat('stat-d-selesai',  `<big style="color:var(--g5)">${selesai}</big><span class="stat-sub">${selesai === 0 ? 'Belum ada' : 'donasi selesai'}</span>`);
  setStat('stat-d-sertif',   `<big style="color:var(--g5)">${sertif}</big><span class="stat-sub">${sertif === 0 ? 'Belum ada' : 'sertifikat'}</span>`);

  const rows = data.riwayat || [];
  if (!rows.length) {
    setHtml('tbl-d-riw', `<tr><td colspan="6">
      <div class="empty-state">
        <div class="es-icon">🎁</div>
        <h3>Belum ada donasi</h3>
        <p>Mulai donasi pertamamu dan bantu mereka yang membutuhkan</p>
        <button class="btn btn-green" onclick="nav('d-kat')">🔍 Lihat Katalog Kebutuhan</button>
      </div>
    </td></tr>`);
    return;
  }
  setHtml('tbl-d-riw', rows.map(r => `
    <tr>
      <td style="font-family:monospace;font-size:.76rem">${esc(r.donasi_id)}</td>
      <td>${esc(r.qty_donasi + ' × ' + (r.nama_barang || '—'))}</td>
      <td>${esc(r.nama_yayasan || '—')}</td>
      <td>${fmtTgl(r.created_at)}</td>
      <td>${badge(r.status)}</td>
      <td style="display:flex;gap:4px;flex-wrap:wrap">
        <button class="btn btn-ghost btn-xs" onclick="showDetail('${esc(r.donasi_id)}')">Detail</button>
        ${r.status === 'disetujui' ? `<button class="btn btn-green btn-xs" data-donasi-id="${esc(r.donasi_id)}" onclick="openInputResi(this)">📦 Input Resi</button>` : ''}
        ${r.no_resi ? `<button class="btn btn-ghost btn-xs" onclick="lacakResi('${esc(r.no_resi)}')">Lacak</button>` : ''}
        ${r.status === 'selesai' ? `<button class="btn btn-green btn-xs" onclick="showSertif('${esc(r.donasi_id)}','${esc(r.nama_barang)}','${esc(r.nama_yayasan)}','${fmtTgl(r.created_at)}')">🏅</button>` : ''}
      </td>
    </tr>`).join(''));

  // Sync ke ringkasan profil donatur
  setHtml('profil-d-total',  `<big>${total}</big>`);
  setHtml('profil-d-proses', `<big style="color:#d97706">${berjalan}</big>`);
  setHtml('profil-d-selesai',`<big style="color:var(--g5)">${selesai}</big>`);
}

/* ─── RENDER: PENERIMA ─── */
function renderDashboardPenerima(data) {
  const s = data.stats || {};
  const yTotal   = s.total_donasi      ?? 0;
  const yAktif   = s.kebutuhan_aktif   ?? 0;
  const yKonfirm = s.perlu_konfirmasi  ?? 0;
  const yPct     = s.pct_terpenuhi     ?? '0%';

  setStat('stat-y-total',   `<big>${yTotal}</big><span class="stat-sub">${yTotal === 0 ? 'Belum ada donasi' : 'total donasi masuk'}</span>`);
  const yTawaran = s.tawaran_masuk ?? 0;
  setStat('stat-y-aktif',   `<big style="color:#d97706">${yAktif}</big><span class="stat-sub">${yAktif === 0 ? 'Semua terpenuhi ✅' : 'kebutuhan aktif'}</span>`);
  setStat('stat-y-konfirm', `<big style="color:#dc2626">${yKonfirm}</big><span class="stat-sub">${yKonfirm === 0 ? 'Tidak ada ✅' : 'perlu dikonfirmasi'}</span>`);
  setStat('stat-y-pct',     `<big style="color:var(--g5)">${yPct}</big><span class="stat-sub">terpenuhi bulan ini</span>`);

  // Badge tawaran masuk di nav
  const navBadge = document.getElementById('nav-tawaran-badge');
  if (navBadge) { navBadge.textContent = yTawaran; navBadge.style.display = yTawaran > 0 ? 'inline' : 'none'; }

  // Sync ke ringkasan profil penerima
  setHtml('profil-y-total', `<big>${yTotal}</big>`);
  setHtml('profil-y-aktif', `<big style="color:#d97706">${yAktif}</big>`);
  setHtml('profil-y-pct',   `<big style="color:var(--g5)">${yPct}</big>`);

  // Tabel perlu konfirmasi = status 'dikirim'
  // Tabel tawaran menunggu persetujuan
  const tawaranRows = (data.riwayat || []).filter(r => r.status === 'menunggu');
  setHtml('tbl-y-tawaran', !tawaranRows.length
    ? `<tr><td colspan="5"><div class="empty-state" style="padding:28px"><div class="es-icon">📭</div><p style="color:var(--text2)">Tidak ada tawaran donasi baru</p></div></td></tr>`
    : tawaranRows.map(r => `
      <tr>
        <td style="font-family:monospace;font-size:.76rem">${esc(r.donasi_id)}</td>
        <td>${esc(r.nama_donatur||'—')}</td>
        <td>${esc(r.qty_donasi + ' × ' + (r.nama_barang||'—'))}</td>
        <td>${fmtTgl(r.created_at)}</td>
        <td style="display:flex;gap:5px">
          <button class="btn btn-green btn-xs" data-donasi-id="${esc(r.donasi_id)}" onclick="setujuiTawaran(this)">✅ Setujui</button>
          <button class="btn btn-red btn-xs"   data-donasi-id="${esc(r.donasi_id)}" onclick="tolakTawaran(this)">❌ Tolak</button>
        </td>
      </tr>`).join(''));

  const konfRows = (data.riwayat || []).filter(r => r.status === 'dikirim');
  setHtml('tbl-y-konfirm', !konfRows.length
    ? emptyRow(5, 'Tidak ada donasi yang menunggu konfirmasi ✅')
    : konfRows.map(r => `
        <tr>
          <td style="font-family:monospace;font-size:.76rem">${esc(r.donasi_id)}</td>
          <td>${esc(r.nama_donatur || '—')}</td>
          <td>${esc(r.qty_donasi + ' × ' + (r.nama_barang || '—'))}</td>
          <td>${esc(r.no_resi || '—')}</td>
          <td><button class="btn btn-green btn-xs" data-donasi-id="${esc(r.donasi_id)}" onclick="konfirm(this)">📸 Konfirmasi Terima</button></td>
        </tr>`).join(''));

  // Riwayat semua donasi masuk
  const rows = data.riwayat || [];
  setHtml('tbl-y-riw', !rows.length
    ? emptyStatePenerima()
    : rows.map(r => `
        <tr>
          <td style="font-family:monospace;font-size:.76rem">${esc(r.donasi_id)}</td>
          <td>${esc(r.nama_donatur || '—')}</td>
          <td>${esc(r.qty_donasi + ' × ' + (r.nama_barang || '—'))}</td>
          <td>${fmtTgl(r.created_at)}</td>
          <td>${badge(r.status)}</td>
          <td>${r.foto_barang
            ? `<button class="btn btn-ghost btn-xs" onclick="toast('Membuka foto bukti...')">📸 Lihat</button>`
            : '—'}</td>
        </tr>`).join(''));
}

/* ─── RENDER: ADMIN ─── */
function renderDashboardAdmin(data) {
  const s = data.stats || {};
  const aUser   = s.total_user      ?? 0;
  const aAktif  = s.donasi_aktif    ?? 0;
  const aVerif  = s.penerima_verif  ?? 0;
  const aBarang = s.total_barang    ?? 0;

  setStat('stat-a-user',   `<big>${aUser}</big><span class="stat-sub">pengguna terdaftar</span>`);
  setStat('stat-a-aktif',  `<big style="color:#d97706">${aAktif}</big><span class="stat-sub">${aAktif === 0 ? 'Tidak ada aktif' : 'donasi aktif'}</span>`);
  setStat('stat-a-verif',  `<big style="color:var(--g5)">${aVerif}</big><span class="stat-sub">penerima terverifikasi</span>`);
  setStat('stat-a-barang', `<big style="color:var(--g5)">${aBarang}</big><span class="stat-sub">barang tersalurkan</span>`);

  const pending = data.pending || [];
  setHtml('tbl-a-pending', !pending.length
    ? emptyRow(5, 'Tidak ada permintaan verifikasi yang menunggu ✅')
    : pending.map(p => `
        <tr>
          <td>${esc(p.nama_lengkap)}</td>
          <td>${esc(p.email)}</td>
          <td>${esc(p.no_telp || '—')}</td>
          <td>${fmtTgl(p.created_at)}</td>
          <td style="display:flex;gap:6px;padding:10px 0">
            <button class="btn btn-green btn-xs" onclick="verifikasi(this,${p.id},'setuju')">✅ Setujui</button>
            <button class="btn btn-red btn-xs"   onclick="verifikasi(this,${p.id},'tolak')">❌ Tolak</button>
          </td>
        </tr>`).join(''));

  const rows = data.riwayat || [];
  setHtml('tbl-a-riw', !rows.length
    ? emptyRow(5, 'Belum ada transaksi donasi')
    : rows.map(r => `
        <tr>
          <td style="font-family:monospace;font-size:.76rem">${esc(r.donasi_id)}</td>
          <td>${esc(r.nama_donatur || '—')}</td>
          <td>${esc(r.nama_yayasan || '—')}</td>
          <td>${esc(r.qty_donasi + ' × ' + (r.nama_barang || '—'))}</td>
          <td>${badge(r.status)}</td>
        </tr>`).join(''));
}

/* ─── DEMO: render angka statis ─── */
function renderDashboardDemo(r) {
  if (r === 'donatur') {
    setStat('stat-d-total',    bigNum(7));
    setStat('stat-d-berjalan', bigNum(2,'amber'));
    setStat('stat-d-selesai',  bigNum(5,'green'));
    setStat('stat-d-sertif',   bigNum(5,'green'));
    setHtml('tbl-d-riw', `
      <tr>
        <td style="font-family:monospace;font-size:.76rem">CDR-20260404-007</td>
        <td>3 × Buku Pelajaran SD</td><td>Yayasan Peduli Anak NTB</td>
        <td>4 Apr 2026</td><td>${badge('dikirim')}</td>
        <td><button class="btn btn-ghost btn-xs" onclick="lacakResi('CDGOS1234ID')">Lacak</button></td>
      </tr>
      <tr>
        <td style="font-family:monospace;font-size:.76rem">CDR-20260401-001</td>
        <td>5 × Seragam SD</td><td>Panti Asuhan Al-Ikhlas</td>
        <td>1 Apr 2026</td><td>${badge('selesai')}</td>
        <td><button class="btn btn-ghost btn-xs" onclick="toast('Lihat detail donasi')">Lihat</button></td>
      </tr>
      <tr>
        <td style="font-family:monospace;font-size:.76rem">CDR-20260320-003</td>
        <td>2 × Tas Sekolah</td><td>Panti Asuhan Al-Ikhlas</td>
        <td>20 Mar 2026</td><td>${badge('selesai')}</td>
        <td><button class="btn btn-ghost btn-xs" onclick="toast('Lihat detail donasi')">Lihat</button></td>
      </tr>`);
  }
  if (r === 'penerima') {
    setStat('stat-y-total',   bigNum(142));
    setStat('stat-y-aktif',   bigNum(4,'amber'));
    setStat('stat-y-konfirm', bigNum(2,'red'));
    setStat('stat-y-pct',     bigNum('74%','green'));
    setHtml('tbl-y-konfirm', `
      <tr>
        <td style="font-family:monospace;font-size:.76rem">CDR-20260404-007</td>
        <td>Andi Wijaya</td><td>3 × Buku Pelajaran SD</td><td>CDGOS1234ID</td>
        <td><button class="btn btn-green btn-xs" onclick="konfirm(this)">📸 Konfirmasi Terima</button></td>
      </tr>
      <tr>
        <td style="font-family:monospace;font-size:.76rem">CDR-20260405-012</td>
        <td>Rini Hartati</td><td>2 × Tas Sekolah</td><td>CDJNT5678ID</td>
        <td><button class="btn btn-green btn-xs" onclick="konfirm(this)">📸 Konfirmasi Terima</button></td>
      </tr>`);
    setHtml('tbl-y-riw', `
      <tr>
        <td style="font-family:monospace;font-size:.76rem">CDR-20260401-001</td>
        <td>Sabrina Salsabila</td><td>5 × Seragam SD</td>
        <td>1 Apr 2026</td><td>${badge('selesai')}</td><td>—</td>
      </tr>
      <tr>
        <td style="font-family:monospace;font-size:.76rem">CDR-20260328-009</td>
        <td>Dian Permata</td><td>4 × Buku Cerita Anak</td>
        <td>28 Mar 2026</td><td>${badge('selesai')}</td><td>—</td>
      </tr>`);
  }
  if (r === 'admin') {
    setStat('stat-a-user',   bigNum(412));
    setStat('stat-a-aktif',  bigNum(38,'amber'));
    setStat('stat-a-verif',  bigNum(38,'green'));
    setStat('stat-a-barang', bigNum(1240,'green'));
    setHtml('tbl-a-pending', `
      <tr>
        <td>Rumah Belajar Cahaya</td><td>cahaya@posko.id</td><td>0812-0001-1111</td><td>2 Mei 2026</td>
        <td style="display:flex;gap:6px;padding:10px 0">
          <button class="btn btn-green btn-xs" onclick="toast('✅ Diverifikasi')">Setujui</button>
          <button class="btn btn-red btn-xs"   onclick="toast('❌ Ditolak')">Tolak</button>
        </td>
      </tr>
      <tr>
        <td>Yayasan Tunas Bangsa</td><td>tunas@yayasan.id</td><td>0813-0002-2222</td><td>4 Mei 2026</td>
        <td style="display:flex;gap:6px;padding:10px 0">
          <button class="btn btn-green btn-xs" onclick="toast('✅ Diverifikasi')">Setujui</button>
          <button class="btn btn-red btn-xs"   onclick="toast('❌ Ditolak')">Tolak</button>
        </td>
      </tr>`);
    setHtml('tbl-a-riw', `
      <tr>
        <td style="font-family:monospace;font-size:.76rem">CDR-20260508-021</td>
        <td>Sabrina S.</td><td>Al-Ikhlas</td><td>5 × Seragam SD</td><td>${badge('selesai')}</td>
      </tr>
      <tr>
        <td style="font-family:monospace;font-size:.76rem">CDR-20260507-018</td>
        <td>Andi W.</td><td>Peduli Anak NTB</td><td>3 × Buku SD</td><td>${badge('dikirim')}</td>
      </tr>`);
  }
}

/* ─── Verifikasi penerima (admin) via AJAX ─── */
function verifikasi(btn, userId, aksi) {
  btn.disabled = true;
  btn.textContent = aksi === 'setuju' ? 'Memproses…' : 'Menolak…';

  fetch('backend/proses_verifikasi.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/x-www-form-urlencoded' },
    body: `user_id=${userId}&aksi=${aksi}`,
    credentials: 'same-origin',
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      btn.closest('tr').remove();
      toast(aksi === 'setuju' ? '✅ Akun berhasil diverifikasi' : '❌ Akun ditolak');
    } else {
      toast('⚠️ Gagal: ' + (d.message || 'Error tak dikenal'));
      btn.disabled = false;
      btn.textContent = aksi === 'setuju' ? 'Setujui' : 'Tolak';
    }
  })
  .catch(() => {
    toast('⚠️ Koneksi gagal, coba lagi');
    btn.disabled = false;
    btn.textContent = aksi === 'setuju' ? 'Setujui' : 'Tolak';
  });
}

/* ═══════════════════════════════════════
   NAVIGASI INNER PAGE
   ═══════════════════════════════════════ */
const navItems = {
  donatur : [
    { k:'d-home',  l:'🏠 Dashboard' },
    { k:'d-kat',   l:'📋 Katalog'   },
    { k:'d-don',   l:'🎁 Donasi'    },
    { k:'d-lacak', l:'📍 Lacak'     },
    { k:'d-profil',l:'👤 Profil'    },
  ],
  penerima: [
    { k:'y-home',  l:'🏠 Dashboard' },
    { k:'y-kat',   l:'📋 Katalog'   },
    { k:'y-riw',   l:'📦 Riwayat'   },
    { k:'y-profil',l:'🏠 Profil'    },
  ],
  admin: [
    { k:'a-home',  l:'🏠 Dashboard' },
    { k:'a-users', l:'👥 Pengguna'  },
    { k:'a-donasi',l:'📦 Donasi'    },
    { k:'a-verif', l:'✅ Verifikasi' },
    { k:'a-lap',   l:'📊 Laporan'   },
    { k:'a-profil',l:'⚙️ Profil'    },
  ],
};

function buildNav(r) {
  const n = document.getElementById('app-nav');
  n.innerHTML = navItems[r]
    .map(i => `<button id="nb-${i.k}" onclick="nav('${i.k}')">${i.l}</button>`)
    .join('');
}

function nav(k) {
  const roleEl = { donatur:'rd', penerima:'ry', admin:'ra' };
  document.querySelectorAll('#'+(roleEl[cRole]||'rd')+' .inner-page')
          .forEach(p => p.classList.remove('on'));
  document.getElementById('ip-'+k)?.classList.add('on');
  document.querySelectorAll('#app-nav button').forEach(b => b.classList.remove('on'));
  document.getElementById('nb-'+k)?.classList.add('on');
  window.scrollTo(0,0);
}

function doLogout() { window.location.href = 'backend/logout.php'; }

/* ═══════════════════════════════════════
   PROFIL
   ═══════════════════════════════════════ */
function renderProfile(r) {
  const u = currentUser;
  const initials = u.nama ? u.nama.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase() : '?';

  if (r === 'donatur') {
    setText('d-profil-nama',  u.nama);
    setText('d-profil-email', u.email + (u.no_telp ? ' · '+u.no_telp : ''));
    setAv('d-profil-av', initials);
    setInputVal('d-edit-nama',   u.nama);
    setInputVal('d-edit-email',  u.email);
    setInputVal('d-edit-telp',   u.no_telp);
    setInputVal('d-edit-alamat', u.alamat);
  } else if (r === 'penerima') {
    setText('y-profil-nama',  u.nama);
    setText('y-profil-email', u.email + (u.no_telp ? ' · '+u.no_telp : ''));
    setAv('y-profil-av', initials);
    setInputVal('y-edit-nama',   u.nama);
    setInputVal('y-edit-email',  u.email);
    setInputVal('y-edit-telp',   u.no_telp);
    setInputVal('y-edit-alamat', u.alamat);
  } else {
    setText('a-profil-nama',  u.nama);
    setText('a-profil-email', u.email);
    setAv('a-profil-av', initials);
    setInputVal('a-edit-nama',  u.nama);
    setInputVal('a-edit-email', u.email);
    setInputVal('a-edit-telp',  u.no_telp);
  }
}

async function simpanProfil() {
  const r = cRole;
  let nama, no_telp, alamat;

  if (r === 'donatur') {
    nama    = document.getElementById('d-edit-nama')?.value.trim()   || '';
    no_telp = document.getElementById('d-edit-telp')?.value.trim()   || '';
    alamat  = document.getElementById('d-edit-alamat')?.value.trim() || '';
  } else if (r === 'penerima') {
    nama    = document.getElementById('y-edit-nama')?.value.trim()   || '';
    no_telp = document.getElementById('y-edit-telp')?.value.trim()   || '';
    alamat  = document.getElementById('y-edit-alamat')?.value.trim() || '';
  } else {
    nama    = document.getElementById('a-edit-nama')?.value.trim()   || '';
    no_telp = document.getElementById('a-edit-telp')?.value.trim()   || '';
    alamat  = '';
  }

  if (!nama) { toast('⚠️ Nama tidak boleh kosong!'); return; }

  // Cari tombol simpan dan beri loading state
  const btn = document.querySelector('[onclick="simpanProfil()"]');
  if (btn) { btn.disabled = true; btn.textContent = '⏳ Menyimpan...'; }

  try {
    const fd = new FormData();
    fd.append('nama',    nama);
    fd.append('no_telp', no_telp);
    fd.append('alamat',  alamat);

    const res  = await fetch('backend/update_profil.php', {
      method: 'POST', body: fd, credentials: 'same-origin',
    });
    const data = await res.json();

    if (data.ok) {
      // Update state lokal
      currentUser.nama    = data.nama;
      currentUser.no_telp = data.no_telp;
      currentUser.alamat  = data.alamat;

      const initials = currentUser.nama.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
      setText('uname', currentUser.nama);
      setText('uav',   initials);
      renderProfile(r);
      toast('✅ Profil berhasil disimpan ke database!');
    } else {
      toast('❌ ' + (data.error || 'Gagal menyimpan profil'));
    }
  } catch(e) {
    toast('❌ Koneksi error: ' + e.message);
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = '💾 Simpan Perubahan'; }
  }
}

/* ═══════════════════════════════════════
   KATALOG
   ═══════════════════════════════════════ */
/* ═══════════════════════════════════════
   KATALOG — load dari DB
   ═══════════════════════════════════════ */
async function loadKatalogDB() {
  try {
    const res  = await fetch('backend/katalog_data.php', { credentials:'same-origin' });
    const raw  = await res.text();
    const data = JSON.parse(raw);
    if (data.ok && Array.isArray(data.data) && data.data.length > 0) {
      // Normalisasi ke format internal katalogData
      katalogData = data.data.map(r => ({
        id     : r.id,
        org    : r.nama_yayasan || '—',
        kota   : r.kota_yayasan || 'NTB',
        nama   : r.nama_barang,
        kat    : r.kategori    || 'pakaian',
        urg    : r.urgensi     || 'med',
        butuh  : parseInt(r.target_butuh)       || 1,
        kumpul : parseInt(r.jumlah_terkumpul)   || 0,
        yayasan_id : r.yayasan_id,
      }));
      renderKatalog();
      isiFormDonasi();   // isi dropdown form donasi
    }
  } catch(e) {
    console.warn('[CareDrop] Katalog DB gagal, pakai fallback:', e);
    katalogData = [...katalogFallback];
    renderKatalog();
  }
}

function renderKatalog() {
  const g = document.getElementById('kat-grid');
  if (!g) return;

  let data = katalogData;
  if (aFil !== 'semua') data = data.filter(d => d.kat === aFil);
  if (sQ) data = data.filter(d =>
    (d.nama + d.org).toLowerCase().includes(sQ.toLowerCase()));

  if (!data.length) {
    g.innerHTML = '<p style="padding:22px;color:var(--text2)">Tidak ada hasil ditemukan.</p>';
    return;
  }

  const urgLabel = { high:'Urgen', med:'Sedang', low:'Terpenuhi' };
  const urgClass = { high:'tr',    med:'ta',     low:'tg' };
  const icoMap   = { pakaian:'👕', buku:'📚', elektronik:'💻', perabot:'🛏️' };
  const bgMap    = { pakaian:'#edfbf3', buku:'#fef9ee', elektronik:'#eff6ff', perabot:'#f5f3ff' };

  g.innerHTML = data.map(d => {
    const ico = icoMap[d.kat] || '📦';
    const bg  = bgMap[d.kat]  || '#f4f4f4';
    const pct = d.butuh > 0 ? Math.min(100, Math.round((d.kumpul / d.butuh) * 100)) : 0;
    return `
      <div class="kc">
        <div class="kc-img" style="background:${bg}">
          <span style="font-size:46px">${ico}</span>
          <span class="urg tag ${urgClass[d.urg]||'tg'}">${urgLabel[d.urg]||d.urg}</span>
        </div>
        <div class="kc-body">
          <div class="kc-org">${esc(d.org)}</div>
          <h4>${esc(d.nama)}</h4>
          <div class="prog-row">
            <div class="prog"><div class="pf" style="width:${pct}%"></div></div>
            <span>${d.kumpul}/${d.butuh}</span>
          </div>
        </div>
        <div class="kc-foot">
          <span class="kc-loc">📍 ${esc(d.kota)}</span>
          <button class="btn btn-green btn-xs" onclick="piliItemDB(${d.id})">Donasikan</button>
        </div>
      </div>`;
  }).join('');
}

function setChip(el, f) {
  document.querySelectorAll('#ip-d-kat .chip').forEach(c => c.classList.remove('on'));
  el.classList.add('on');
  aFil = f; renderKatalog();
}
function filterKat(q) { sQ = q; renderKatalog(); }

function piliItemDB(katalogId) {
  // Set dropdown form donasi ke katalog yg dipilih, lalu pindah ke form donasi
  const sel = document.getElementById('pilih-katalog');
  if (sel) {
    sel.value = katalogId;
    onPilihKatalog(sel);
  }
  nav('d-don');
}

/* Isi dropdown form donasi dari katalogData */
function isiFormDonasi() {
  const sel     = document.getElementById('pilih-katalog');
  const loading = document.getElementById('don-katalog-loading');
  const list    = document.getElementById('don-katalog-list');
  const empty   = document.getElementById('don-katalog-empty');
  if (!sel) return;

  if (!katalogData.length || (katalogData.length === katalogFallback.length && katalogData[0].id === 1)) {
    if (loading) loading.style.display = 'none';
    if (empty)   empty.style.display   = 'block';
    return;
  }

  sel.innerHTML = '<option value="">— Pilih barang yang ingin didonasikan —</option>';
  katalogData.forEach(d => {
    const opt = document.createElement('option');
    opt.value       = d.id;
    opt.textContent = `${d.nama} — ${d.org} (${d.kumpul}/${d.butuh})`;
    sel.appendChild(opt);
  });

  if (loading) loading.style.display = 'none';
  if (list)    list.style.display    = 'block';
  if (empty)   empty.style.display   = 'none';
}

/* Tampilkan info item saat pilih dari dropdown */
function onPilihKatalog(sel) {
  const id   = parseInt(sel.value);
  const item = katalogData.find(d => d.id === id);
  const info = document.getElementById('don-item-info');

  document.getElementById('don-katalog-id')?.setAttribute('value', id || '');
  document.getElementById('don-yayasan-id')?.setAttribute('value', item?.yayasan_id || '');
  document.getElementById('don-kota-tujuan')?.setAttribute('value', item?.kota || '');

  if (item && info) {
    const pct = item.butuh > 0 ? Math.min(100, Math.round((item.kumpul / item.butuh) * 100)) : 0;
    const urgLabel = { high:'🔴 Urgen', med:'🟡 Sedang', low:'🟢 Hampir Terpenuhi' };
    info.style.display = 'block';
    info.innerHTML = `
      <strong>${esc(item.nama)}</strong> — ${esc(item.org)}<br>
      <span style="color:var(--text2)">📍 ${esc(item.kota)}</span> &nbsp;|&nbsp;
      <span>${urgLabel[item.urg] || item.urg}</span><br>
      <div style="display:flex;align-items:center;gap:8px;margin-top:8px">
        <div style="flex:1;height:7px;background:#e8f5ea;border-radius:99px;overflow:hidden">
          <div style="width:${pct}%;height:100%;background:var(--g5);border-radius:99px"></div>
        </div>
        <span style="font-size:.78rem;color:var(--text2)">${item.kumpul}/${item.butuh} terkumpul</span>
      </div>`;
  } else if (info) {
    info.style.display = 'none';
  }
}

function onFotoChange(inp) {
  const label = document.getElementById('don-foto-label');
  if (label && inp.files[0]) label.textContent = '✅ ' + inp.files[0].name;
}

/* ═══════════════════════════════════════
   FORM DONASI & KURIR
   ═══════════════════════════════════════ */
function pickSlot(el) {
  document.querySelectorAll('.slot').forEach(s => s.classList.remove('on'));
  el.classList.add('on');
}

const couriers = [
  { id:'gosend',  nama:'GoSend Instant', tipe:'instant', ico:'🛵', eta:'1-2 Jam',  tarifDasar:15000, perKm:2500 },
  { id:'grab',    nama:'GrabExpress',    tipe:'instant', ico:'🏍️',  eta:'1-2 Jam',  tarifDasar:14000, perKm:2500 },
  { id:'jne',     nama:'JNE Reguler',    tipe:'reguler', ico:'📦', eta:'2-3 Hari', tarifDasar:20000, perKm:0   },
  { id:'jnt',     nama:'J&T Express',    tipe:'reguler', ico:'🚚', eta:'2-3 Hari', tarifDasar:18000, perKm:0   },
  { id:'sicepat', nama:'SiCepat HALU',   tipe:'reguler', ico:'⚡', eta:'2-4 Hari', tarifDasar:16000, perKm:0   },
  { id:'pos',     nama:'Pos Indonesia',  tipe:'kargo',   ico:'🏤', eta:'3-7 Hari', tarifDasar:35000, perKm:0   },
];
const distanceMap = {
  'Mataram-Mataram':5,'Mataram-Lombok':25,'Mataram-Selong':55,'Mataram-Praya':30
};

function hitungEstimasiPengiriman() {
  const asal   = document.getElementById('kota-asal').value;
  const sel    = document.getElementById('pilih-yayasan');
  const txt    = sel.options[sel.selectedIndex].text;
  let tujuan   = 'Mataram';
  if (txt.includes('Lombok')) tujuan = 'Lombok';
  if (txt.includes('Selong')) tujuan = 'Selong';
  if (txt.includes('Praya'))  tujuan = 'Praya';

  const jarak = distanceMap[`${asal}-${tujuan}`] || 150;
  const berat = parseInt(document.getElementById('berat-barang').value) || 1;
  const cont  = document.getElementById('courier-result');

  const list = jarak <= 15 && berat <= 20
    ? couriers.filter(c => c.tipe === 'instant')
    : berat > 20
      ? couriers.filter(c => c.tipe === 'kargo')
      : couriers.filter(c => c.tipe === 'reguler' || c.tipe === 'kargo');

  let html = `<div style="font-size:.8rem;color:var(--text2);margin-bottom:10px">Rute: <strong>${asal} → ${tujuan}</strong> (~${jarak} km)</div><div class="courier-list">`;
  list.forEach(c => {
    const biaya = c.tipe==='instant' ? c.tarifDasar+(jarak*c.perKm) : c.tarifDasar*berat;
    const fmt   = new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(biaya);
    html += `
      <div class="courier-card" onclick="pilihKurir(this,'${c.id}','${fmt}')">
        <div class="cc-ico">${c.ico}</div>
        <div class="cc-info"><h4>${c.nama}</h4><span>Est. ${c.eta}</span></div>
        <div class="cc-price">${fmt}</div>
      </div>`;
  });
  html += '</div>';
  cont.innerHTML = html;
}

function pilihKurir(el, id, harga) {
  document.querySelectorAll('.courier-card').forEach(c => c.classList.remove('on'));
  el.classList.add('on');
  selectedCourier = id;
  toast(`Kurir dipilih: ${id.toUpperCase()} – ${harga}`);
}

async function submitDon() {
  const katalogId = document.getElementById('don-katalog-id')?.value;
  const qty       = parseInt(document.getElementById('don-qty')?.value) || 0;
  const deskripsi = document.getElementById('don-deskripsi')?.value || '';
  const kotaAsal  = document.getElementById('kota-asal')?.value || 'Mataram';
  const kotaTuj   = document.getElementById('don-kota-tujuan')?.value || 'Mataram';
  const berat     = parseFloat(document.getElementById('berat-barang')?.value) || 1;
  const fotoFile  = document.getElementById('don-foto')?.files[0];

  if (!katalogId) { toast('⚠️ Pilih barang dari katalog terlebih dahulu!'); return; }
  if (qty < 1)    { toast('⚠️ Jumlah barang harus minimal 1!'); return; }
  if (!selectedCourier) { toast('⚠️ Pilih kurir terlebih dahulu!'); return; }

  const btn = document.getElementById('btn-submit-don');
  if (btn) { btn.disabled = true; btn.textContent = '⏳ Menyimpan...'; }

  try {
    const fd = new FormData();
    fd.append('katalog_id',  katalogId);
    fd.append('qty',         qty);
    fd.append('deskripsi',   deskripsi);
    fd.append('kurir',       selectedCourier);
    fd.append('kota_asal',   kotaAsal);
    fd.append('kota_tujuan', kotaTuj);
    fd.append('berat',       berat);
    if (fotoFile) fd.append('foto_barang', fotoFile);

    const res  = await fetch('backend/proses_donasi.php', {
      method: 'POST', body: fd, credentials: 'same-origin',
    });
    const data = await res.json();

    if (data.ok) {
      toast(`🎉 Donasi berhasil! No. Resi: ${data.resi}`);
      // Reset form
      selectedCourier = null;
      if (document.getElementById('pilih-katalog')) document.getElementById('pilih-katalog').value = '';
      if (document.getElementById('don-item-info')) document.getElementById('don-item-info').style.display = 'none';
      if (document.getElementById('don-qty')) document.getElementById('don-qty').value = 1;
      if (document.getElementById('don-deskripsi')) document.getElementById('don-deskripsi').value = '';
      if (document.getElementById('courier-result')) document.getElementById('courier-result').innerHTML = '';
      setTimeout(() => {
        nav('d-lacak');
        const inp = document.getElementById('input-resi');
        if (inp) inp.value = data.resi;
        showTrack(data.resi);
      }, 1200);
    } else {
      toast('❌ ' + (data.error || 'Gagal menyimpan donasi'));
    }
  } catch(e) {
    toast('❌ Koneksi error: ' + e.message);
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = '🎁 Konfirmasi & Proses Donasi'; }
  }
}

/* ═══════════════════════════════════════
   TRACKING RESI
   ═══════════════════════════════════════ */
function lacakResi(resi) {
  // Kalau penerima, cukup tampilkan tracking saja
  if (cRole === 'donatur') nav('d-lacak');
  const inp = document.getElementById('input-resi');
  if (inp) inp.value = resi;
  showTrack(resi);
}

async function showTrack(resiManual = null) {
  const resi = resiManual || document.getElementById('input-resi')?.value?.trim();
  if (!resi) { toast('Masukkan nomor resi!'); return; }

  document.getElementById('tr-empty')?.classList.add('hide');
  const resDiv = document.getElementById('tr-result');
  if (!resDiv) return;
  resDiv.classList.remove('hide');

  // Loading state
  resDiv.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text2)">
    <div style="font-size:28px;margin-bottom:10px">🔍</div>
    <p>Mencari data resi <strong>${esc(resi)}</strong>...</p>
  </div>`;

  try {
    const res  = await fetch(`backend/lacak_resi.php?resi=${encodeURIComponent(resi)}`, { credentials:'same-origin' });
    const data = await res.json();

    if (!data.ok) {
      resDiv.innerHTML = `
        <div style="text-align:center;padding:40px">
          <div style="font-size:36px;margin-bottom:12px">❌</div>
          <p style="font-weight:600;margin-bottom:6px">Resi tidak ditemukan</p>
          <p style="color:var(--text2);font-size:.875rem">${esc(data.error || 'Pastikan nomor resi benar')}</p>
        </div>`;
      return;
    }

    const r   = data.resi;
    const statusTag = {
      menunggu  : '<span class="tag tb">⏳ Menunggu Pickup</span>',
      diproses  : '<span class="tag ta">📦 Diproses</span>',
      dikirim   : '<span class="tag ta">🚚 Sedang Dikirim</span>',
      selesai   : '<span class="tag tg">✅ Selesai</span>',
      dibatalkan: '<span class="tag tr">❌ Dibatalkan</span>',
    }[r.status] || `<span class="tag">${r.status}</span>`;

    const fmt = new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits:0 });

    const timelineHtml = data.steps.map((s, i) => {
      const isDone    = s.done;
      const isCurrent = !isDone && i === data.current;
      const dotClass  = isDone ? 'done' : (isCurrent ? 'cur' : '');
      const dotText   = isDone ? '✓' : (isCurrent ? '→' : '···');
      const titleStyle= isDone || isCurrent ? '' : 'style="color:var(--text3)"';
      return `
        <div class="tl-item">
          <div class="tl-dot ${dotClass}" style="${dotText==='···'?'font-size:9px;color:var(--text3)':''}">${dotText}</div>
          <div class="tl-con">
            <h4 ${titleStyle}>${esc(s.label)}</h4>
            ${isDone || isCurrent ? `<p>${esc(s.desc)}</p>` : ''}
          </div>
        </div>`;
    }).join('');

    resDiv.innerHTML = `
      <div class="dcrd">
        <div class="dc-head">
          <div>
            <div class="dc-id">RESI: ${esc(r.no_resi)}</div>
            <div class="dc-ttl">${esc(r.qty_donasi)} × ${esc(r.nama_barang)} → ${esc(r.nama_yayasan)}</div>
          </div>
          ${statusTag}
        </div>
        <div style="display:flex;gap:20px;flex-wrap:wrap;margin:14px 0;padding:14px;background:var(--surf);border-radius:10px;font-size:.83rem;color:var(--text2)">
          <span>🚚 <strong>${esc(r.kurir?.toUpperCase())}</strong></span>
          <span>📍 ${esc(r.kota_asal)} → ${esc(r.kota_tujuan)}</span>
          <span>⚖️ ${r.berat_kg} kg</span>
          <span>💰 ${fmt.format(r.estimasi_ongkir)}</span>
        </div>
        <div class="tl">${timelineHtml}</div>
        ${r.status === 'selesai' ? `
        <div style="margin-top:16px;padding:14px;background:#f0fdf4;border-radius:10px;border:1px solid #bbf7d0;text-align:center">
          <span style="font-size:1.5rem">🎉</span>
          <p style="font-weight:600;color:var(--g6);margin-top:6px">Donasi berhasil tersalurkan!</p>
          <p style="font-size:.83rem;color:var(--text2)">Terima kasih atas kebaikanmu, ${esc(r.nama_donatur)}</p>
        </div>` : ''}
      </div>`;
  } catch(e) {
    resDiv.innerHTML = `<div style="text-align:center;padding:32px;color:var(--text2)">
      ❌ Gagal mengambil data: ${esc(e.message)}
    </div>`;
  }
}

/* ═══════════════════════════════════════
   PENERIMA — konfirmasi & hapus row
   ═══════════════════════════════════════ */
async function konfirm(btn) {
  const row      = btn.closest('tr');
  const donasiId = row?.dataset?.donasiId || btn.dataset?.donasiId;

  if (!donasiId) {
    toast('⚠️ ID donasi tidak ditemukan');
    return;
  }

  btn.disabled    = true;
  btn.textContent = '⏳ Memproses...';

  try {
    const fd = new FormData();
    fd.append('donasi_id', donasiId);

    const res  = await fetch('backend/konfirm_terima.php', {
      method: 'POST', body: fd, credentials: 'same-origin',
    });
    const data = await res.json();

    if (data.ok) {
      toast('✅ Donasi berhasil dikonfirmasi diterima!');
      btn.textContent   = '✓ Diterima';
      btn.style.opacity = '.5';
      // Pindahkan baris ke tabel riwayat dengan status selesai
      if (row) {
        // Update status cell jika ada
        const statusCell = row.querySelector('td:nth-child(5)');
        if (statusCell) statusCell.innerHTML = badge('selesai');
        // Hapus dari tabel konfirmasi
        setTimeout(() => row.remove(), 1000);
      }
    } else {
      toast('❌ ' + (data.error || 'Gagal konfirmasi'));
      btn.disabled    = false;
      btn.textContent = '📸 Konfirmasi Terima';
    }
  } catch(e) {
    toast('❌ Koneksi error');
    btn.disabled    = false;
    btn.textContent = '📸 Konfirmasi Terima';
  }
}
function hapusRow(btn) {
  btn.closest('tr').remove();
  toast('Kebutuhan dihapus dari katalog');
}

/* ═══════════════════════════════════════
   TOAST
   ═══════════════════════════════════════ */
let toastTimer;
function toast(msg) {
  const el = document.getElementById('toast');
  el.innerHTML = `<span style="color:var(--g6)">✦</span> ${msg}`;
  el.classList.add('on');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('on'), 2800);
}

/* ═══════════════════════════════════════
   INIT
   ═══════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  if (typeof PHP_SESSION !== 'undefined' && PHP_SESSION && PHP_SESSION.role) {
    currentUser = {
      id:      PHP_SESSION.id      || null,
      nama:    PHP_SESSION.nama    || '',
      email:   PHP_SESSION.email   || '',
      no_telp: PHP_SESSION.no_telp || '',
      alamat:  PHP_SESSION.alamat  || '',
      role:    PHP_SESSION.role,
    };
    loginAs(PHP_SESSION.role, false); // false = fetch data real dari DB
  } else {
    show('pg-landing');
  }
});

// Polling notifikasi setiap 60 detik saat sudah login
let notifInterval = null;
function startNotifPolling() {
  loadNotif();
  clearInterval(notifInterval);
  notifInterval = setInterval(loadNotif, 60000);
}

/* ═══════════════════════════════════════════════════════════
   v5 — MOBILE NAV, MODAL, NOTIFIKASI, DETAIL,
        E-SERTIFIKAT, GANTI PASSWORD, EXPORT CSV, AVATAR
   ═══════════════════════════════════════════════════════════ */

/* ── Mobile Nav ── */
function toggleMobileNav() {
  const nav     = document.getElementById('mobile-nav');
  const overlay = document.getElementById('mobile-nav-overlay');
  const isOpen  = nav.classList.contains('open');
  if (isOpen) {
    nav.classList.remove('open');
    overlay.classList.add('hide');
  } else {
    // Sync isi mobile nav dengan desktop nav
    const desktopBtns = document.querySelectorAll('#app-nav button');
    const mobileLinks = document.getElementById('mobile-nav-links');
    mobileLinks.innerHTML = '';
    desktopBtns.forEach(btn => {
      const clone = btn.cloneNode(true);
      clone.addEventListener('click', () => {
        btn.click();
        closeMobileNav();
      });
      mobileLinks.appendChild(clone);
    });
    // Sync user info
    const uname = document.getElementById('uname')?.textContent || '—';
    const urole = document.getElementById('urole')?.textContent || '—';
    const uav   = document.getElementById('uav')?.textContent   || '?';
    setText('uname-mob',  uname);
    setText('urole-mob',  urole);
    setText('uav-mob',    uav);
    nav.classList.add('open');
    overlay.classList.remove('hide');
    overlay.classList.remove('hide');
  }
}
function closeMobileNav() {
  document.getElementById('mobile-nav')?.classList.remove('open');
  document.getElementById('mobile-nav-overlay')?.classList.add('hide');
}

/* ── Modal helpers ── */
function openModal(id) {
  document.getElementById(id)?.classList.remove('hide');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id)?.classList.add('hide');
  document.body.style.overflow = '';
}
// Tutup modal dengan Esc
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay:not(.hide)').forEach(m => {
      m.classList.add('hide');
      document.body.style.overflow = '';
    });
    closeNotif();
    closeMobileNav();
  }
});

/* ── Detail Donasi ── */
async function showDetail(donasiId) {
  openModal('modal-detail');
  setHtml('modal-detail-body', '<p style="text-align:center;padding:24px;color:var(--text3)">⏳ Memuat detail...</p>');
  try {
    const res  = await fetch(`backend/detail_donasi.php?id=${encodeURIComponent(donasiId)}`, { credentials:'same-origin' });
    const data = await res.json();
    if (!data.ok) { setHtml('modal-detail-body', `<p style="color:#dc2626">❌ ${esc(data.error)}</p>`); return; }
    const d = data.data;
    const fmt = new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits:0 });
    const statusColors = { menunggu:'#d97706', diproses:'#d97706', dikirim:'#2563eb', selesai:'#16a34a', dibatalkan:'#dc2626' };
    setHtml('modal-detail-body', `
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <span style="font-family:monospace;font-size:.8rem;color:var(--text2)">${esc(d.donasi_id)}</span>
        ${badge(d.status)}
      </div>
      <div class="detail-grid">
        <div class="detail-item"><label>Barang</label><span>${esc(d.nama_barang)}</span></div>
        <div class="detail-item"><label>Kategori</label><span>${esc(d.kategori)}</span></div>
        <div class="detail-item"><label>Jumlah</label><span>${esc(d.qty_donasi)} unit</span></div>
        <div class="detail-item"><label>Tanggal</label><span>${fmtTgl(d.created_at)}</span></div>
        <div class="detail-item"><label>Donatur</label><span>${esc(d.nama_donatur)}</span></div>
        <div class="detail-item"><label>Yayasan Tujuan</label><span>${esc(d.nama_yayasan)}</span></div>
      </div>
      ${d.deskripsi_kondisi ? `
      <div style="background:var(--surf);border-radius:8px;padding:12px;margin-bottom:12px;font-size:.85rem">
        <label style="font-size:.72rem;font-weight:700;color:var(--text3);text-transform:uppercase;display:block;margin-bottom:4px">Kondisi Barang</label>
        ${esc(d.deskripsi_kondisi)}
      </div>` : ''}
      ${d.no_resi ? `
      <div style="background:#eff6ff;border-radius:8px;padding:12px;margin-bottom:12px;font-size:.85rem">
        <label style="font-size:.72rem;font-weight:700;color:#1d4ed8;text-transform:uppercase;display:block;margin-bottom:6px">Info Pengiriman</label>
        <div class="detail-grid" style="margin-bottom:0">
          <div class="detail-item"><label>No. Resi</label><span style="font-family:monospace">${esc(d.no_resi)}</span></div>
          <div class="detail-item"><label>Kurir</label><span>${esc((d.kurir||'').toUpperCase())}</span></div>
          <div class="detail-item"><label>Rute</label><span>${esc(d.kota_asal)} → ${esc(d.kota_tujuan)}</span></div>
          <div class="detail-item"><label>Est. Ongkir</label><span>${fmt.format(d.estimasi_ongkir||0)}</span></div>
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-ghost btn-sm" onclick="closeModal('modal-detail');lacakResi('${esc(d.no_resi)}')">🔍 Lacak Resi</button>
        ${d.status==='selesai' ? `<button class="btn btn-green btn-sm" onclick="closeModal('modal-detail');showSertif('${esc(d.donasi_id)}','${esc(d.nama_barang)}','${esc(d.nama_yayasan)}','${fmtTgl(d.created_at)}')">🏅 E-Sertifikat</button>` : ''}
      </div>` : ''}
    `);
  } catch(e) {
    setHtml('modal-detail-body', `<p style="color:#dc2626">❌ Error: ${esc(e.message)}</p>`);
  }
}

/* ── E-Sertifikat ── */
function showSertif(donasiId, namaBarang, namaYayasan, tgl) {
  setText('sertif-nama',  currentUser.nama || '—');
  setText('sertif-tgl',   tgl);
  setText('sertif-id',    donasiId);
  setHtml('sertif-body',
    `Telah berhasil mendonasikan <strong>${esc(namaBarang)}</strong><br>
     kepada <strong>${esc(namaYayasan)}</strong><br>
     sebagai wujud kepedulian terhadap sesama.`
  );
  openModal('modal-sertif');
}

function downloadSertif() {
  const card = document.getElementById('sertif-card');
  if (!card) return;
  // Buat versi print-friendly
  const w = window.open('', '_blank', 'width=600,height=500');
  w.document.write(`<!DOCTYPE html><html><head>
    <title>E-Sertifikat CareDrop</title>
    <style>
      body { margin:0; font-family: Georgia, serif; background:#f0fdf4; display:flex; align-items:center; justify-content:center; min-height:100vh; }
      .card { background: linear-gradient(135deg,#f0fdf4,#dcfce7); border: 2px solid #16a34a; border-radius:16px; padding:40px 36px; max-width:480px; text-align:center; }
      .logo { font-size:.9rem; font-weight:700; color:#15803d; letter-spacing:.5px; margin-bottom:12px; font-family:sans-serif; }
      .title { font-size:1rem; font-weight:800; color:#14532d; letter-spacing:3px; margin-bottom:20px; font-family:sans-serif; }
      .sub { font-size:.78rem; color:#52735e; margin-bottom:8px; }
      .nama { font-size:1.6rem; font-style:italic; font-weight:700; color:#14532d; margin-bottom:16px; }
      .body { font-size:.85rem; color:#166534; line-height:1.7; margin-bottom:20px; }
      .footer { display:flex; justify-content:space-around; padding-top:16px; border-top:1px solid #bbf7d0; font-size:.75rem; }
    </style>
  </head><body><div class="card">${card.innerHTML}</div>
  <script>window.onload=()=>{window.print();}<\/script>
  </body></html>`);
  w.document.close();
}

/* ── Ganti Password ── */
async function gantiPassword() {
  const passLama  = document.getElementById('pass-lama')?.value  || '';
  const passBaru  = document.getElementById('pass-baru')?.value  || '';
  const passBaru2 = document.getElementById('pass-baru2')?.value || '';

  if (!passLama)            { toast('⚠️ Masukkan password lama!'); return; }
  if (passBaru.length < 6)  { toast('⚠️ Password baru minimal 6 karakter!'); return; }
  if (passBaru !== passBaru2){ toast('⚠️ Konfirmasi password tidak cocok!'); return; }

  const btn = document.getElementById('btn-ganti-pass');
  if (btn) { btn.disabled = true; btn.textContent = '⏳ Menyimpan...'; }

  try {
    const fd = new FormData();
    fd.append('pass_lama',  passLama);
    fd.append('pass_baru',  passBaru);
    fd.append('pass_baru2', passBaru2);

    const res  = await fetch('backend/ganti_password.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();

    if (data.ok) {
      toast('✅ Password berhasil diubah!');
      closeModal('modal-ganti-pass');
      // Reset fields
      ['pass-lama','pass-baru','pass-baru2'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
      });
    } else {
      toast('❌ ' + (data.error || 'Gagal mengubah password'));
    }
  } catch(e) {
    toast('❌ Koneksi error: ' + e.message);
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = '🔒 Simpan Password Baru'; }
  }
}

/* ── Notifikasi ── */
let notifData = [];
async function loadNotif() {
  try {
    const res  = await fetch('backend/notif_data.php', { credentials:'same-origin' });
    const data = await res.json();
    if (!data.ok) return;

    notifData = data.notif || [];
    const count = data.count || 0;
    const badge = document.getElementById('notif-badge');
    if (badge) {
      badge.textContent = count;
      count > 0 ? badge.classList.remove('hide') : badge.classList.add('hide');
    }
    renderNotifList();
  } catch(e) { /* silent fail */ }
}

function renderNotifList() {
  const list = document.getElementById('notif-list');
  if (!list) return;
  if (!notifData.length) {
    list.innerHTML = '<p style="padding:20px;color:var(--text3);text-align:center;font-size:.85rem">Tidak ada notifikasi baru</p>';
    return;
  }
  list.innerHTML = notifData.map(n => `
    <div class="notif-item ${n.unread ? 'unread' : ''}">
      <div>${n.icon} ${esc(n.pesan)}</div>
      <div class="notif-time">🕐 ${fmtTgl(n.waktu)}</div>
    </div>`).join('');
}

function toggleNotif() {
  const panel = document.getElementById('notif-panel');
  if (!panel) return;
  const isHidden = panel.classList.contains('hide');
  if (isHidden) {
    panel.classList.remove('hide');
    // Tandai semua sudah dibaca
    const badge = document.getElementById('notif-badge');
    if (badge) badge.classList.add('hide');
  } else {
    panel.classList.add('hide');
  }
}
function closeNotif() {
  document.getElementById('notif-panel')?.classList.add('hide');
}

// Tutup notif panel saat klik di luar
document.addEventListener('click', e => {
  const bell  = document.getElementById('notif-bell');
  const panel = document.getElementById('notif-panel');
  if (panel && bell && !bell.contains(e.target) && !panel.contains(e.target)) {
    panel.classList.add('hide');
  }
});

/* ── Export CSV ── */
function exportCSV() {
  toast('⏳ Mengunduh CSV...');
  window.location.href = 'backend/export_csv.php';
}

/* ── Avatar Upload ── */
async function uploadAvatar(inp) {
  if (!inp.files[0]) return;
  const file = inp.files[0];
  const ext  = file.name.split('.').pop().toLowerCase();
  if (!['jpg','jpeg','png','webp'].includes(ext)) {
    toast('❌ Format foto harus JPG atau PNG'); return;
  }
  if (file.size > 2 * 1024 * 1024) {
    toast('❌ Ukuran foto maksimal 2MB'); return;
  }
  const fd = new FormData();
  fd.append('avatar', file);
  try {
    const res  = await fetch('backend/upload_avatar.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();
    if (data.ok) {
      // Update semua elemen avatar di halaman
      document.querySelectorAll('.uav-img').forEach(img => { img.src = data.url + '?t=' + Date.now(); });
      toast('✅ Foto profil berhasil diperbarui!');
    } else {
      toast('❌ ' + (data.error || 'Gagal upload foto'));
    }
  } catch(e) {
    toast('❌ Koneksi error');
  }
}

/* ── Empty state penerima ── */
function emptyStatePenerima() {
  return `<tr><td colspan="5">
    <div class="empty-state">
      <div class="es-icon">📬</div>
      <h3>Belum ada donasi masuk</h3>
      <p>Tambahkan kebutuhan ke katalog agar donatur dapat menemukan yayasan Anda</p>
      <button class="btn btn-green" onclick="nav('y-kat')">+ Tambah Kebutuhan</button>
    </div>
  </td></tr>`;
}


/* ═══════════════════════════════════════
   v6 — PAGINATION & SEARCH RIWAYAT
   ═══════════════════════════════════════ */

/* Search filter tabel riwayat donatur */
function searchRiwayat(q) {
  const rows = document.querySelectorAll('#tbl-d-riw tr');
  const kw   = q.toLowerCase().trim();
  rows.forEach(row => {
    const txt = row.textContent.toLowerCase();
    row.style.display = (!kw || txt.includes(kw)) ? '' : 'none';
  });
}

/* Search filter tabel penerima */
function searchRiwayatY(q) {
  const rows = document.querySelectorAll('#tbl-y-riw tr, #tbl-y-konfirm tr');
  const kw   = q.toLowerCase().trim();
  rows.forEach(row => {
    const txt = row.textContent.toLowerCase();
    row.style.display = (!kw || txt.includes(kw)) ? '' : 'none';
  });
}

/* ═══════════════════════════════════════════════════════════
   v7 — TAWARAN DONASI, INPUT RESI, LEGALITAS, FITUR 3–6
   ═══════════════════════════════════════════════════════════ */

/* ── Yayasan: Setujui Tawaran ── */
async function setujuiTawaran(btn) {
  const donasiId = btn.dataset.donasiId;
  if (!confirm('Setujui tawaran donasi ini?')) return;
  btn.disabled = true; btn.textContent = '⏳';
  try {
    const fd = new FormData();
    fd.append('aksi', 'setujui'); fd.append('donasi_id', donasiId);
    const res  = await fetch('backend/aksi_tawaran.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();
    if (data.ok) {
      toast('✅ ' + data.msg);
      btn.closest('tr')?.remove();
      // Refresh dashboard
      loadDashboard(cRole);
    } else { toast('❌ ' + data.error); btn.disabled = false; btn.textContent = '✅ Setujui'; }
  } catch(e) { toast('❌ Error: ' + e.message); btn.disabled = false; btn.textContent = '✅ Setujui'; }
}

/* ── Yayasan: Tolak Tawaran ── */
async function tolakTawaran(btn) {
  const donasiId = btn.dataset.donasiId;
  const alasan   = prompt('Alasan penolakan (opsional):', 'Tidak sesuai kebutuhan kami');
  if (alasan === null) return; // user cancel
  btn.disabled = true; btn.textContent = '⏳';
  try {
    const fd = new FormData();
    fd.append('aksi','tolak'); fd.append('donasi_id',donasiId); fd.append('alasan',alasan||'');
    const res  = await fetch('backend/aksi_tawaran.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();
    if (data.ok) {
      toast('Tawaran ditolak');
      btn.closest('tr')?.remove();
      loadDashboard(cRole);
    } else { toast('❌ ' + data.error); btn.disabled = false; btn.textContent = '❌ Tolak'; }
  } catch(e) { toast('❌ Error: ' + e.message); btn.disabled = false; btn.textContent = '❌ Tolak'; }
}

/* ── Donatur: Buka Modal Input Resi ── */
function openInputResi(btn) {
  const donasiId = btn.dataset.donasiId;
  openModal('modal-input-resi');
  document.getElementById('resi-donasi-id').value = donasiId;
  document.getElementById('inp-no-resi').value    = '';
  document.getElementById('inp-kurir-resi').value = '';
}

/* ── Donatur: Submit Nomor Resi ── */
async function submitResi() {
  const donasiId = document.getElementById('resi-donasi-id')?.value || '';
  const noResi   = document.getElementById('inp-no-resi')?.value.trim() || '';
  const kurir    = document.getElementById('inp-kurir-resi')?.value || '';
  if (!noResi) { toast('⚠️ Nomor resi tidak boleh kosong!'); return; }
  const btn = document.getElementById('btn-submit-resi');
  if (btn) { btn.disabled = true; btn.textContent = '⏳ Menyimpan...'; }
  try {
    const fd = new FormData();
    fd.append('aksi','input_resi'); fd.append('donasi_id',donasiId);
    fd.append('no_resi',noResi);   fd.append('kurir',kurir);
    const res  = await fetch('backend/aksi_tawaran.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();
    if (data.ok) {
      toast('✅ ' + data.msg);
      closeModal('modal-input-resi');
      loadDashboard(cRole);
    } else { toast('❌ ' + data.error); }
  } catch(e) { toast('❌ Error: ' + e.message); }
  finally { if (btn) { btn.disabled = false; btn.textContent = '📦 Konfirmasi Pengiriman'; } }
}

/* ── Penerima: Tutup/Nonaktifkan Kebutuhan ── */
async function tutupKebutuhan(btn, katalogId) {
  if (!confirm('Nonaktifkan kebutuhan ini dari katalog publik?')) return;
  btn.disabled = true; btn.textContent = '⏳';
  try {
    const fd = new FormData();
    fd.append('katalog_id', katalogId); fd.append('aksi', 'tutup');
    const res  = await fetch('backend/aksi_katalog.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();
    if (data.ok) {
      toast('✅ Kebutuhan berhasil ditutup');
      btn.textContent = '🔓 Buka Lagi';
      btn.onclick = () => bukaKebutuhan(btn, katalogId);
      btn.disabled = false;
    } else { toast('❌ ' + data.error); btn.disabled = false; btn.textContent = '🔒 Tutup'; }
  } catch(e) { toast('❌ Error'); btn.disabled = false; btn.textContent = '🔒 Tutup'; }
}

async function bukaKebutuhan(btn, katalogId) {
  btn.disabled = true; btn.textContent = '⏳';
  try {
    const fd = new FormData();
    fd.append('katalog_id', katalogId); fd.append('aksi', 'buka');
    const res  = await fetch('backend/aksi_katalog.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();
    if (data.ok) {
      toast('✅ Kebutuhan kembali ditampilkan di katalog');
      btn.textContent = '🔒 Tutup';
      btn.onclick = () => tutupKebutuhan(btn, katalogId);
      btn.disabled = false;
    } else { toast('❌ ' + data.error); btn.disabled = false; btn.textContent = '🔓 Buka Lagi'; }
  } catch(e) { toast('❌ Error'); btn.disabled = false; }
}

/* ── Penerima: Load & Upload Berkas Legalitas ── */
async function loadLegalitas() {
  const wrap = document.getElementById('legalitas-list');
  if (!wrap) return;
  try {
    const res  = await fetch('backend/get_legalitas.php', { credentials:'same-origin' });
    const data = await res.json();
    if (!data.ok || !data.data.length) {
      wrap.innerHTML = '<p style="color:var(--text3);text-align:center;padding:16px">Belum ada berkas yang diunggah</p>';
      return;
    }
    const stLabel = { pending:'⏳ Menunggu', verified:'✅ Terverifikasi', rejected:'❌ Ditolak' };
    const stColor = { pending:'#d97706', verified:'#16a34a', rejected:'#dc2626' };
    wrap.innerHTML = data.data.map(b => `
      <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f0fdf4">
        <div style="flex:1">
          <strong style="font-size:.875rem">${esc(jenisLabel[b.jenis]||b.jenis)}</strong>
          <div style="font-size:.75rem;color:var(--text2)">${esc(b.keterangan||'—')} · ${fmtTgl(b.created_at)}</div>
        </div>
        <span style="font-size:.75rem;font-weight:600;color:${stColor[b.status]||'#666'}">${stLabel[b.status]||b.status}</span>
        <a href="uploads/legalitas/${esc(b.nama_file)}" target="_blank" class="btn btn-ghost btn-xs">📄 Lihat</a>
      </div>`).join('');
  } catch(e) { console.warn('loadLegalitas error:', e); }
}

const jenisLabel = {
  akta:'Akta Pendirian', sk_kemenkumham:'SK Kemenkumham', npwp:'NPWP',
  foto_gedung:'Foto Gedung/Kantor', lainnya:'Dokumen Lainnya'
};

async function uploadLegalitas(inp, jenis) {
  if (!inp.files[0]) return;
  const fd = new FormData();
  fd.append('berkas', inp.files[0]);
  fd.append('jenis',  jenis);
  fd.append('keterangan', document.getElementById('ket-' + jenis)?.value || '');
  const btn = document.getElementById('btn-upload-' + jenis);
  if (btn) { btn.disabled = true; btn.textContent = '⏳ Mengunggah...'; }
  try {
    const res  = await fetch('backend/upload_legalitas.php', { method:'POST', body:fd, credentials:'same-origin' });
    const data = await res.json();
    if (data.ok) {
      toast('✅ Berkas berhasil diunggah! Menunggu verifikasi admin.');
      loadLegalitas();
    } else { toast('❌ ' + data.error); }
  } catch(e) { toast('❌ Error: ' + e.message); }
  finally { if (btn) { btn.disabled = false; btn.textContent = '⬆ Upload'; } }
}

/* ── Badge untuk status baru ── */
const _badgeOrig = window._badgeOrig || badge;
window._badgeOrig = _badgeOrig;
function badge(s) {
  const extra = {
    disetujui: '<span class="tag" style="background:#dbeafe;color:#1d4ed8">✅ Disetujui</span>',
    ditolak   : '<span class="tag tr">❌ Ditolak</span>',
  };
  return extra[s] || _badgeOrig(s);
}
