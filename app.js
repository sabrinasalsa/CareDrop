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
  renderKatalog();
  toast('Selamat datang, ' + currentUser.nama + ' 👋');

  if (!isDemo) {
    loadDashboard(r);
  } else {
    renderDashboardDemo(r);
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
    setHtml('tbl-d-riw', emptyRow(6, 'Belum ada donasi. Yuk mulai donasi pertamamu! 🎁'));
    return;
  }
  setHtml('tbl-d-riw', rows.map(r => `
    <tr>
      <td style="font-family:monospace;font-size:.76rem">${esc(r.donasi_id)}</td>
      <td>${esc(r.qty_donasi + ' × ' + (r.nama_barang || '—'))}</td>
      <td>${esc(r.nama_yayasan || '—')}</td>
      <td>${fmtTgl(r.created_at)}</td>
      <td>${badge(r.status)}</td>
      <td>${r.no_resi
        ? `<button class="btn btn-ghost btn-xs" onclick="lacakResi('${esc(r.no_resi)}')">Lacak</button>`
        : `<button class="btn btn-ghost btn-xs" onclick="toast('Resi belum tersedia')">Lihat</button>`}
      </td>
    </tr>`).join(''));
}

/* ─── RENDER: PENERIMA ─── */
function renderDashboardPenerima(data) {
  const s = data.stats || {};
  const yTotal   = s.total_donasi      ?? 0;
  const yAktif   = s.kebutuhan_aktif   ?? 0;
  const yKonfirm = s.perlu_konfirmasi  ?? 0;
  const yPct     = s.pct_terpenuhi     ?? '0%';

  setStat('stat-y-total',   `<big>${yTotal}</big><span class="stat-sub">${yTotal === 0 ? 'Belum ada donasi' : 'total donasi masuk'}</span>`);
  setStat('stat-y-aktif',   `<big style="color:#d97706">${yAktif}</big><span class="stat-sub">${yAktif === 0 ? 'Semua terpenuhi ✅' : 'kebutuhan aktif'}</span>`);
  setStat('stat-y-konfirm', `<big style="color:#dc2626">${yKonfirm}</big><span class="stat-sub">${yKonfirm === 0 ? 'Tidak ada ✅' : 'perlu dikonfirmasi'}</span>`);
  setStat('stat-y-pct',     `<big style="color:var(--g5)">${yPct}</big><span class="stat-sub">terpenuhi bulan ini</span>`);

  // Tabel perlu konfirmasi = status 'dikirim'
  const konfRows = (data.riwayat || []).filter(r => r.status === 'dikirim');
  setHtml('tbl-y-konfirm', !konfRows.length
    ? emptyRow(5, 'Tidak ada donasi yang menunggu konfirmasi ✅')
    : konfRows.map(r => `
        <tr>
          <td style="font-family:monospace;font-size:.76rem">${esc(r.donasi_id)}</td>
          <td>${esc(r.nama_donatur || '—')}</td>
          <td>${esc(r.qty_donasi + ' × ' + (r.nama_barang || '—'))}</td>
          <td>${esc(r.no_resi || '—')}</td>
          <td><button class="btn btn-green btn-xs" onclick="konfirm(this)">📸 Konfirmasi Terima</button></td>
        </tr>`).join(''));

  // Riwayat semua donasi masuk
  const rows = data.riwayat || [];
  setHtml('tbl-y-riw', !rows.length
    ? emptyRow(6, 'Belum ada donasi masuk ke yayasan Anda')
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

function simpanProfil() {
  const r = cRole;
  if (r === 'donatur') {
    currentUser.nama    = document.getElementById('d-edit-nama')?.value   || currentUser.nama;
    currentUser.no_telp = document.getElementById('d-edit-telp')?.value   || currentUser.no_telp;
    currentUser.alamat  = document.getElementById('d-edit-alamat')?.value || currentUser.alamat;
  } else if (r === 'penerima') {
    currentUser.nama    = document.getElementById('y-edit-nama')?.value   || currentUser.nama;
    currentUser.no_telp = document.getElementById('y-edit-telp')?.value   || currentUser.no_telp;
    currentUser.alamat  = document.getElementById('y-edit-alamat')?.value || currentUser.alamat;
  } else {
    currentUser.nama    = document.getElementById('a-edit-nama')?.value  || currentUser.nama;
    currentUser.no_telp = document.getElementById('a-edit-telp')?.value  || currentUser.no_telp;
  }
  const initials = currentUser.nama.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
  setText('uname', currentUser.nama);
  setText('uav',   initials);
  renderProfile(r);
  toast('✅ Profil berhasil diperbarui!');
}

/* ═══════════════════════════════════════
   KATALOG
   ═══════════════════════════════════════ */
function renderKatalog() {
  const g = document.getElementById('kat-grid');
  if (!g) return;

  let data = katalogData;
  if (aFil !== 'semua') data = data.filter(d => d.kat === aFil);
  if (sQ) data = data.filter(d =>
    (d.nama+d.org).toLowerCase().includes(sQ.toLowerCase()));

  if (!data.length) {
    g.innerHTML = '<p style="padding:22px;color:var(--text2)">Tidak ada hasil ditemukan.</p>';
    return;
  }

  const urgLabel = { high:'Urgen', med:'Sedang', low:'Terpenuhi' };
  const urgClass = { high:'tr',    med:'ta',     low:'tg' };
  const icoMap   = { pakaian:'👕', buku:'📚', elektronik:'💻', perabot:'🛏️' };
  const bgMap    = { pakaian:'#edfbf3', buku:'#fef9ee', elektronik:'#eff6ff', perabot:'#f5f3ff' };

  g.innerHTML = data.map(d => {
    const ico = d.ico || icoMap[d.kat] || '📦';
    const bg  = d.bg  || bgMap[d.kat]  || '#f4f4f4';
    const pct = Math.min(100, Math.round((d.kumpul / d.butuh) * 100));
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
          <button class="btn btn-green btn-xs" onclick="piliItem('${esc(d.nama)}','${esc(d.org)}')">Donasikan</button>
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
function piliItem(nama, org) {
  toast(`Siap donasi "${nama}" ke ${org}`);
  setTimeout(() => nav('d-don'), 700);
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

function submitDon() {
  if (!selectedCourier) { toast('Silakan pilih kurir terlebih dahulu!'); return; }
  const resi = 'CD'+Math.random().toString(36).substring(2,8).toUpperCase()+'ID';
  toast(`🎉 Donasi diproses! No. Resi: ${resi}`);
  setTimeout(() => {
    nav('d-lacak');
    const inp = document.getElementById('input-resi');
    if (inp) inp.value = resi;
    showTrack(resi);
  }, 1500);
}

/* ═══════════════════════════════════════
   TRACKING RESI
   ═══════════════════════════════════════ */
function lacakResi(resi) {
  nav('d-lacak');
  const inp = document.getElementById('input-resi');
  if (inp) inp.value = resi;
  showTrack(resi);
}

function showTrack(resiManual = null) {
  const resi = resiManual || document.getElementById('input-resi')?.value;
  if (!resi) { toast('Masukkan nomor resi!'); return; }

  document.getElementById('tr-empty')?.classList.add('hide');
  const resDiv = document.getElementById('tr-result');
  if (!resDiv) return;
  resDiv.classList.remove('hide');
  resDiv.innerHTML = `
    <div class="dcrd">
      <div class="dc-head">
        <div>
          <div class="dc-id">RESI: ${esc(resi)}</div>
          <div class="dc-ttl">Menuju: Yayasan Peduli Anak NTB</div>
        </div>
        <span class="tag ta">🚚 Sedang Dikirim</span>
      </div>
      <div class="tl">
        <div class="tl-item"><div class="tl-dot done">✓</div><div class="tl-con"><h4>Pickup Request Dibuat</h4><p>Sistem merespon permintaan ke kurir.</p><time>Hari ini, 08.40</time></div></div>
        <div class="tl-item"><div class="tl-dot done">✓</div><div class="tl-con"><h4>Barang Dipickup Kurir</h4><p>Kurir telah mengambil paket dari donatur.</p><time>Hari ini, 09.20</time></div></div>
        <div class="tl-item"><div class="tl-dot cur">→</div><div class="tl-con"><h4>Paket dalam Perjalanan</h4><p>Paket menuju yayasan tujuan.</p><time>Hari ini, 10.15</time></div></div>
        <div class="tl-item"><div class="tl-dot" style="color:var(--text3);font-size:9px">···</div><div class="tl-con"><h4 style="color:var(--text3)">Menunggu Konfirmasi Penerimaan</h4></div></div>
      </div>
    </div>`;
}

/* ═══════════════════════════════════════
   PENERIMA — konfirmasi & hapus row
   ═══════════════════════════════════════ */
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
