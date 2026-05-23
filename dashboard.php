<?php
/**
 * CareDrop – dashboard.php
 * Dashboard lengkap untuk role DONATUR
 * Tab: beranda | ajukan | tawaran | lacak | riwayat | sertifikat | profil
 */
session_start();
if (!isset($_SESSION['id']))          { header('Location: login.php'); exit; }
if ($_SESSION['role'] === 'admin')    { header('Location: admin/index.php'); exit; }
if ($_SESSION['role'] === 'penerima') { header('Location: yayasan/kelola_katalog.php'); exit; }

require_once __DIR__ . '/backend/koneksi.php';

// Validasi: pastikan user ID di session masih ada di database
// (mencegah error jika session lama / database di-reset)
$_chk = $koneksi->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
$_chk->bind_param("i", $_SESSION['id']);
$_chk->execute();
$_valid = $_chk->get_result()->fetch_assoc();
$_chk->close();
if (!$_valid) {
    session_unset(); session_destroy();
    header('Location: login.php?flash=timeout'); exit;
}

$user_id  = (int)$_SESSION['id'];
$nama     = $_SESSION['nama']   ?? 'Donatur';
$email    = $_SESSION['email']  ?? '';
$no_telp  = $_SESSION['no_telp'] ?? '';
$alamat   = $_SESSION['alamat'] ?? '';
$tab      = $_GET['tab'] ?? 'beranda';
$allowed  = ['beranda','ajukan','tawaran','lacak','riwayat','sertifikat','profil'];
if (!in_array($tab, $allowed)) $tab = 'beranda';

$inisial = mb_strtoupper(mb_substr($nama, 0, 2));

// ── Load data sesuai tab ─────────────────────────────────────────────────────
$stats   = [];
$riwayat = [];
$katalog = [];
$tawaran = [];
$selesai = [];
$profil  = [];

if ($tab === 'beranda' || $tab === 'tawaran' || $tab === 'riwayat') {
    $r = $koneksi->prepare("SELECT COUNT(*) AS n FROM donasi WHERE donatur_id=?");
    $r->bind_param("i",$user_id); $r->execute();
    $stats['total'] = (int)($r->get_result()->fetch_assoc()['n']??0); $r->close();

    $r = $koneksi->prepare("SELECT COUNT(*) AS n FROM donasi WHERE donatur_id=? AND status_donasi NOT IN('selesai','dibatalkan','ditolak')");
    $r->bind_param("i",$user_id); $r->execute();
    $stats['berjalan'] = (int)($r->get_result()->fetch_assoc()['n']??0); $r->close();

    $r = $koneksi->prepare("SELECT COUNT(*) AS n FROM donasi WHERE donatur_id=? AND status_donasi='selesai'");
    $r->bind_param("i",$user_id); $r->execute();
    $stats['selesai'] = (int)($r->get_result()->fetch_assoc()['n']??0); $r->close();

    $r = $koneksi->prepare("SELECT COUNT(*) AS n FROM donasi WHERE donatur_id=? AND status_donasi='disetujui'");
    $r->bind_param("i",$user_id); $r->execute();
    $stats['perlu_resi'] = (int)($r->get_result()->fetch_assoc()['n']??0); $r->close();
}

if ($tab === 'beranda') {
    $stmt = $koneksi->prepare(
        "SELECT d.id AS donasi_id,d.qty_donasi,d.status_donasi,d.created_at,
                COALESCE(k.nama_barang,'—') AS nama_barang,
                COALESCE(u.nama_lengkap,'—') AS nama_yayasan,
                p.no_resi,p.kurir
         FROM donasi d
         LEFT JOIN katalog_kebutuhan k ON k.id=d.katalog_id
         LEFT JOIN users u ON u.id=k.yayasan_id
         LEFT JOIN pengiriman p ON p.donasi_id=d.id
         WHERE d.donatur_id=? ORDER BY d.created_at DESC LIMIT 5"
    );
    $stmt->bind_param("i",$user_id); $stmt->execute();
    $riwayat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
}

if ($tab === 'ajukan') {
    $stmt = $koneksi->prepare(
        "SELECT k.id,k.nama_barang,k.kategori,k.urgensi,k.target_butuh,k.jumlah_terkumpul,k.deskripsi,
                u.nama_lengkap AS nama_yayasan,u.alamat AS kota_yayasan
         FROM katalog_kebutuhan k
         JOIN users u ON u.id=k.yayasan_id
         WHERE k.jumlah_terkumpul<k.target_butuh AND (k.aktif=1 OR k.status_aktif=1) AND u.status_verifikasi='verified'
         ORDER BY FIELD(k.urgensi,'high','med','low'),k.id DESC LIMIT 30"
    );
    $stmt->execute();
    $katalog = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
}

if ($tab === 'tawaran') {
    $stmt = $koneksi->prepare(
        "SELECT d.id AS donasi_id,d.qty_donasi,d.status_donasi,d.alasan_tolak,d.created_at,
                COALESCE(k.nama_barang,'—') AS nama_barang,
                COALESCE(u.nama_lengkap,'—') AS nama_yayasan,
                p.no_resi,p.kurir
         FROM donasi d
         LEFT JOIN katalog_kebutuhan k ON k.id=d.katalog_id
         LEFT JOIN users u ON u.id=k.yayasan_id
         LEFT JOIN pengiriman p ON p.donasi_id=d.id
         WHERE d.donatur_id=? ORDER BY d.created_at DESC LIMIT 50"
    );
    $stmt->bind_param("i",$user_id); $stmt->execute();
    $tawaran = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
}

if ($tab === 'riwayat') {
    $stmt = $koneksi->prepare(
        "SELECT d.id AS donasi_id,d.qty_donasi,d.status_donasi,d.created_at,
                COALESCE(k.nama_barang,'—') AS nama_barang,
                COALESCE(u.nama_lengkap,'—') AS nama_yayasan,
                p.no_resi,p.kurir
         FROM donasi d
         LEFT JOIN katalog_kebutuhan k ON k.id=d.katalog_id
         LEFT JOIN users u ON u.id=k.yayasan_id
         LEFT JOIN pengiriman p ON p.donasi_id=d.id
         WHERE d.donatur_id=? ORDER BY d.created_at DESC"
    );
    $stmt->bind_param("i",$user_id); $stmt->execute();
    $riwayat = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
}

if ($tab === 'sertifikat') {
    $stmt = $koneksi->prepare(
        "SELECT d.id AS donasi_id,d.qty_donasi,d.updated_at AS tgl_selesai,
                COALESCE(k.nama_barang,'—') AS nama_barang,
                COALESCE(u.nama_lengkap,'—') AS nama_yayasan
         FROM donasi d
         LEFT JOIN katalog_kebutuhan k ON k.id=d.katalog_id
         LEFT JOIN users u ON u.id=k.yayasan_id
         WHERE d.donatur_id=? AND d.status_donasi='selesai'
         ORDER BY d.updated_at DESC"
    );
    $stmt->bind_param("i",$user_id); $stmt->execute();
    $selesai = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
}

if ($tab === 'profil') {
    $stmt = $koneksi->prepare("SELECT nama_lengkap,email,no_telp,alamat,avatar FROM users WHERE id=?");
    $stmt->bind_param("i",$user_id); $stmt->execute();
    $profil = $stmt->get_result()->fetch_assoc() ?? []; $stmt->close();
}

$koneksi->close();

// Status map
$stMap = [
    'menunggu'  => ['Menunggu','#fff7ed','#c2410c'],
    'disetujui' => ['Disetujui','#eff6ff','#2563eb'],
    'ditolak'   => ['Ditolak','#fff1f2','#dc2626'],
    'dikirim'   => ['Dikirim','#f0fdf4','#15803d'],
    'selesai'   => ['Selesai','#f0fdf4','#166534'],
    'dibatalkan'=> ['Dibatalkan','#f9fafb','#6b7280'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard – CareDrop</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{--forest:#0c2e18;--pine:#1e5630;--moss:#2d7a44;--sage:#4aad6b;--mint:#7ed9a3;--amber:#f0c040;--ink:#0b1f12;--muted:#5c7d65;--bg:#f4fbf6;--card:#fff;--border:#d4e8db;--ff:'Plus Jakarta Sans',system-ui,sans-serif;--sw:240px}
    body{font-family:var(--ff);background:var(--bg);color:var(--ink);min-height:100vh;display:flex}

    /* SIDEBAR */
    .sidebar{width:var(--sw);background:var(--forest);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:50;overflow-y:auto}
    .sidebar-logo{padding:22px 20px 16px;border-bottom:1px solid rgba(255,255,255,.07)}
    .sidebar-logo a{font-size:1.15rem;font-weight:800;color:var(--mint);text-decoration:none}
    .sidebar-logo a span{color:var(--amber)}
    .sidebar-logo p{font-size:.7rem;color:rgba(201,242,220,.45);margin-top:2px}
    .sidebar-nav{padding:12px 10px;flex:1}
    .nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:9px;
      color:rgba(201,242,220,.65);font-size:.85rem;font-weight:500;text-decoration:none;
      transition:.18s;margin-bottom:2px;position:relative}
    .nav-item:hover{color:rgba(201,242,220,.95);background:rgba(255,255,255,.06)}
    .nav-item.active{background:rgba(255,255,255,.1);color:#fff;font-weight:700}
    .nav-item.active::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:3px;background:var(--amber);border-radius:0 3px 3px 0}
    .nav-item svg{flex-shrink:0;opacity:.7}
    .nav-item.active svg{opacity:1}
    .nav-badge{margin-left:auto;background:#ef4444;color:#fff;font-size:.65rem;font-weight:700;padding:1px 7px;border-radius:99px;min-width:18px;text-align:center}
    .sidebar-user{padding:14px 16px;border-top:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:10px}
    .user-av{width:36px;height:36px;border-radius:50%;background:var(--moss);border:2px solid var(--sage);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden}
    .user-av img{width:100%;height:100%;object-fit:cover}
    .user-info{overflow:hidden}
    .user-info strong{display:block;font-size:.8rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .user-info span{font-size:.68rem;color:rgba(201,242,220,.5)}
    .nav-separator{height:1px;background:rgba(255,255,255,.06);margin:8px 10px}

    /* MAIN */
    .main{margin-left:var(--sw);flex:1;display:flex;flex-direction:column;min-height:100vh}
    .topbar{background:#fff;border-bottom:1px solid var(--border);padding:0 28px;height:58px;display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:40}
    .topbar h2{font-size:1rem;font-weight:700;flex:1}
    .topbar-actions{display:flex;align-items:center;gap:8px}
    .content{padding:26px 28px;flex:1}

    /* STAT CARDS */
    .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
    .sc{background:var(--card);border-radius:14px;border:1px solid var(--border);padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,.04);transition:.18s}
    .sc:hover{box-shadow:0 4px 18px rgba(45,122,68,.1);transform:translateY(-1px)}
    .sc-icon{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin-bottom:12px}
    .sc label{font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:4px}
    .sc big{font-size:1.9rem;font-weight:800;display:block;line-height:1;margin-bottom:2px}
    .sc small{font-size:.72rem;color:var(--muted)}
    .ic-green{background:#f0fdf4;color:#15803d}
    .ic-amber{background:#fffbeb;color:#d97706}
    .ic-blue{background:#eff6ff;color:#2563eb}
    .ic-red{background:#fff1f2;color:#dc2626}

    /* SECTION */
    .section-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
    .section-hdr h3{font-size:.95rem;font-weight:700}
    .section-hdr a,.section-hdr button{font-size:.78rem;font-weight:600;color:var(--moss);background:none;border:none;cursor:pointer;text-decoration:none}

    /* ALERT */
    .alert-resi{background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px}
    .alert-resi svg{flex-shrink:0;color:#d97706}
    .alert-resi p{font-size:.85rem;color:#92400e;font-weight:600}
    .alert-resi a{color:var(--moss);text-decoration:underline;font-weight:700}

    /* TABLE */
    .card{background:var(--card);border-radius:14px;border:1px solid var(--border);box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden;margin-bottom:22px}
    .card-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .card-header h3{font-size:.92rem;font-weight:700}
    table{width:100%;border-collapse:collapse}
    thead th{padding:10px 14px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);background:#f8fdf9;text-align:left;border-bottom:1px solid var(--border)}
    tbody td{padding:11px 14px;font-size:.83rem;border-bottom:1px solid #f0f7f2;vertical-align:middle}
    tbody tr:last-child td{border-bottom:none}
    tbody tr:hover{background:#f8fdf9}
    .st-badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:.7rem;font-weight:700}
    .mono{font-family:monospace;font-size:.75rem}
    .empty-row td{text-align:center;padding:36px;color:var(--muted)}

    /* KATALOG GRID */
    .kat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;margin-bottom:22px}
    .kat-card{background:var(--card);border-radius:14px;border:1px solid var(--border);padding:16px;box-shadow:0 1px 4px rgba(0,0,0,.04);transition:.18s;display:flex;flex-direction:column}
    .kat-card:hover{transform:translateY(-2px);box-shadow:0 6px 22px rgba(45,122,68,.1)}
    .kat-top{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px}
    .kat-top h4{font-size:.88rem;font-weight:700;line-height:1.3}
    .urg{font-size:.62rem;font-weight:700;padding:2px 7px;border-radius:5px;text-transform:uppercase;flex-shrink:0}
    .urg-high{background:#fee2e2;color:#dc2626}
    .urg-med{background:#fff7ed;color:#c2410c}
    .urg-low{background:#f0fdf4;color:#15803d}
    .kat-yay{font-size:.73rem;color:var(--muted);margin-bottom:10px}
    .prog-bar{height:5px;background:#e8f5ed;border-radius:99px;overflow:hidden;margin-bottom:5px}
    .prog-fill{height:100%;background:linear-gradient(90deg,var(--moss),var(--sage));border-radius:99px}
    .prog-lbl{display:flex;justify-content:space-between;font-size:.68rem;color:var(--muted);margin-bottom:12px}
    .btn-don{width:100%;padding:9px;background:linear-gradient(135deg,var(--moss),var(--sage));color:#fff;border:none;border-radius:9px;font-family:var(--ff);font-size:.8rem;font-weight:700;cursor:pointer;transition:.18s;margin-top:auto}
    .btn-don:hover{transform:translateY(-1px);box-shadow:0 5px 16px rgba(45,122,68,.32)}

    /* TAWARAN ACTIONS */
    .btn-resi{padding:5px 12px;background:var(--moss);color:#fff;border:none;border-radius:7px;font-family:var(--ff);font-size:.75rem;font-weight:700;cursor:pointer;transition:.18s}
    .btn-resi:hover{background:var(--pine)}
    .btn-cert{padding:5px 12px;background:#eff6ff;color:#2563eb;border:none;border-radius:7px;font-family:var(--ff);font-size:.75rem;font-weight:700;cursor:pointer;transition:.18s;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
    .btn-cert:hover{background:#dbeafe}

    /* SERTIFIKAT GRID */
    .cert-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
    .cert-card{background:linear-gradient(135deg,var(--forest),var(--pine));border-radius:16px;padding:22px;color:#fff;position:relative;overflow:hidden}
    .cert-card::before{content:'';position:absolute;width:180px;height:180px;border-radius:50%;background:rgba(126,217,163,.07);right:-40px;top:-40px}
    .cert-card h4{font-size:.92rem;font-weight:700;margin-bottom:4px;position:relative}
    .cert-card p{font-size:.75rem;color:rgba(201,242,220,.7);margin-bottom:2px;position:relative}
    .cert-card .cert-qty{font-size:1.4rem;font-weight:800;color:var(--amber);margin:10px 0;display:block;position:relative}
    .cert-dl{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.1);color:var(--mint);border:1px solid rgba(126,217,163,.25);border-radius:8px;padding:7px 14px;font-size:.78rem;font-weight:700;text-decoration:none;transition:.18s;position:relative}
    .cert-dl:hover{background:rgba(255,255,255,.18)}

    /* PROFIL */
    .prof-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
    .prof-section{background:var(--card);border-radius:14px;border:1px solid var(--border);padding:22px;margin-bottom:18px}
    .prof-section h3{font-size:.92rem;font-weight:700;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border)}
    .field{margin-bottom:13px}
    .field label{display:block;font-size:.7rem;font-weight:700;color:var(--body);text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
    .field input,.field textarea{width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:9px;font-family:var(--ff);font-size:.88rem;color:var(--ink);background:#fff;outline:none;transition:.18s}
    .field input:focus,.field textarea:focus{border-color:var(--moss);box-shadow:0 0 0 3px rgba(45,122,68,.1)}
    .avatar-section{text-align:center;margin-bottom:18px}
    .avatar-circle{width:80px;height:80px;border-radius:50%;background:var(--moss);border:3px solid var(--sage);margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;color:#fff;overflow:hidden}
    .avatar-circle img{width:100%;height:100%;object-fit:cover}

    /* LACAK */
    .lacak-form{background:var(--card);border-radius:14px;border:1px solid var(--border);padding:24px;margin-bottom:20px}
    .lacak-form h3{font-size:.92rem;font-weight:700;margin-bottom:14px}
    .lacak-input-row{display:flex;gap:10px}
    .lacak-input-row input{flex:1;padding:10px 14px;border:1.5px solid var(--border);border-radius:9px;font-family:var(--ff);font-size:.88rem;outline:none;transition:.18s}
    .lacak-input-row input:focus{border-color:var(--moss)}
    .btn-lacak{padding:10px 20px;background:var(--moss);color:#fff;border:none;border-radius:9px;font-family:var(--ff);font-weight:700;font-size:.88rem;cursor:pointer;transition:.18s;white-space:nowrap}
    .btn-lacak:hover{background:var(--pine)}
    .timeline{list-style:none;padding:0;position:relative}
    .timeline::before{content:'';position:absolute;left:15px;top:0;bottom:0;width:2px;background:var(--border)}
    .timeline li{position:relative;padding:0 0 20px 44px}
    .tl-dot{position:absolute;left:0;top:0;width:32px;height:32px;border-radius:50%;border:2px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;z-index:1}
    .tl-dot.done{background:var(--moss);border-color:var(--moss);color:#fff}
    .tl-dot.active{background:var(--amber);border-color:var(--amber);color:var(--forest)}
    .tl-label{font-size:.88rem;font-weight:700;color:var(--ink);margin-bottom:2px}
    .tl-desc{font-size:.78rem;color:var(--muted)}

    /* MODAL */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:200;align-items:center;justify-content:center;padding:20px}
    .overlay.open{display:flex}
    .modal{background:#fff;border-radius:18px;width:100%;max-width:460px;max-height:90vh;overflow-y:auto;padding:26px;box-shadow:0 24px 60px rgba(0,0,0,.2);animation:mIn .22s ease}
    @keyframes mIn{from{opacity:0;transform:scale(.96) translateY(12px)}to{opacity:1;transform:scale(1) translateY(0)}}
    .modal h3{font-size:1.05rem;font-weight:800;margin-bottom:4px}
    .modal .msub{font-size:.8rem;color:var(--muted);margin-bottom:18px}
    .modal-footer{display:flex;gap:8px;margin-top:16px}
    .btn-prim{flex:1;padding:11px;background:linear-gradient(135deg,var(--moss),var(--sage));color:#fff;border:none;border-radius:9px;font-family:var(--ff);font-size:.9rem;font-weight:700;cursor:pointer;transition:.18s}
    .btn-prim:hover{transform:translateY(-1px);box-shadow:0 5px 16px rgba(45,122,68,.32)}
    .btn-sec{padding:11px 16px;background:transparent;color:var(--muted);border:1.5px solid var(--border);border-radius:9px;font-family:var(--ff);font-size:.85rem;font-weight:600;cursor:pointer;transition:.18s}
    .btn-sec:hover{border-color:var(--muted)}
    .btn-danger{padding:11px 16px;background:#fff1f2;color:#dc2626;border:1.5px solid #fecaca;border-radius:9px;font-family:var(--ff);font-size:.85rem;font-weight:700;cursor:pointer;transition:.18s}

    /* INFO BOX */
    .info-box{background:#f0fdf4;border:1px solid #c8e8d4;border-radius:10px;padding:11px 14px;font-size:.78rem;color:var(--body);margin-bottom:4px}

    /* TOAST */
    #toast{position:fixed;bottom:22px;right:22px;z-index:300;background:var(--forest);color:var(--mint);padding:12px 20px;border-radius:12px;font-size:.87rem;font-weight:600;box-shadow:0 8px 30px rgba(0,0,0,.25);max-width:320px;transform:translateY(80px);opacity:0;transition:.3s}
    #toast.show{transform:translateY(0);opacity:1}
    #toast.err{background:#dc2626;color:#fff}

    /* BUTTONS */
    .btn-submit{padding:10px 22px;background:linear-gradient(135deg,var(--moss),var(--sage));color:#fff;border:none;border-radius:9px;font-family:var(--ff);font-size:.88rem;font-weight:700;cursor:pointer;transition:.18s}
    .btn-submit:hover{transform:translateY(-1px);box-shadow:0 5px 16px rgba(45,122,68,.32)}
    .btn-outline{padding:10px 18px;background:transparent;color:var(--moss);border:1.5px solid var(--border);border-radius:9px;font-family:var(--ff);font-size:.85rem;font-weight:600;cursor:pointer;transition:.18s;text-decoration:none;display:inline-flex;align-items:center;gap:7px}
    .btn-outline:hover{border-color:var(--moss)}

    /* FILTER */
    .filter-tabs{display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap}
    .ftab{padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:#fff;color:var(--muted);transition:.18s}
    .ftab.active{background:var(--moss);color:#fff;border-color:var(--moss)}

    /* RESPONSIVE */
    @media(max-width:900px){.stat-grid{grid-template-columns:1fr 1fr}.prof-grid{grid-template-columns:1fr}}
    @media(max-width:640px){.sidebar{transform:translateX(-100%)}.main{margin-left:0}.stat-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <a href="dashboard.php">Care<span>Drop</span></a>
    <p>Dashboard Donatur</p>
  </div>
  <nav class="sidebar-nav">
    <a href="?tab=beranda"    class="nav-item <?= $tab==='beranda'   ?'active':'' ?>">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
      Beranda
    </a>
    <a href="?tab=ajukan"     class="nav-item <?= $tab==='ajukan'    ?'active':'' ?>">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
      Ajukan Donasi
    </a>
    <a href="?tab=tawaran"    class="nav-item <?= $tab==='tawaran'   ?'active':'' ?>">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
      Tawaran Saya
      <?php if(isset($stats['perlu_resi']) && $stats['perlu_resi']>0): ?><span class="nav-badge"><?=$stats['perlu_resi']?></span><?php endif; ?>
    </a>
    <a href="?tab=lacak"      class="nav-item <?= $tab==='lacak'     ?'active':'' ?>">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
      Lacak Pengiriman
    </a>
    <a href="?tab=riwayat"    class="nav-item <?= $tab==='riwayat'   ?'active':'' ?>">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      Riwayat Donasi
    </a>
    <a href="?tab=sertifikat" class="nav-item <?= $tab==='sertifikat'?'active':'' ?>">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/></svg>
      E-Sertifikat
    </a>
    <div class="nav-separator"></div>
    <a href="?tab=profil"     class="nav-item <?= $tab==='profil'    ?'active':'' ?>">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
      Kelola Profil
    </a>
    <a href="backend/logout.php" class="nav-item">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
      Keluar
    </a>
  </nav>
  <div class="sidebar-user">
    <div class="user-av"><?php
      $avPath = !empty($_SESSION['avatar']) ? 'uploads/avatars/'.$_SESSION['avatar'] : null;
      if ($avPath && file_exists(__DIR__.'/'.$avPath)): ?>
        <img src="<?=$avPath?>" alt="">
      <?php else: ?><?=$inisial?><?php endif; ?>
    </div>
    <div class="user-info">
      <strong><?= htmlspecialchars(mb_substr($nama,0,20)) ?></strong>
      <span>Donatur</span>
    </div>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
  <div class="topbar">
    <h2><?php
      $titles=['beranda'=>'Beranda','ajukan'=>'Ajukan Donasi','tawaran'=>'Tawaran Saya',
                'lacak'=>'Lacak Pengiriman','riwayat'=>'Riwayat Donasi','sertifikat'=>'E-Sertifikat','profil'=>'Kelola Profil'];
      echo $titles[$tab]??'Dashboard';
    ?></h2>
    <div class="topbar-actions">
      <a href="katalog.php" class="btn-outline" style="font-size:.8rem;padding:7px 14px">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
        Katalog Publik
      </a>
    </div>
  </div>

  <div class="content">

  <?php /* ═══ BERANDA ═══ */ if ($tab==='beranda'): ?>
    <!-- Stat Cards -->
    <div class="stat-grid">
      <div class="sc">
        <div class="sc-icon ic-green"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg></div>
        <label>Total Donasi</label><big><?=$stats['total']?></big><small>sejak bergabung</small>
      </div>
      <div class="sc">
        <div class="sc-icon ic-amber"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <label>Sedang Berjalan</label><big><?=$stats['berjalan']?></big><small>dalam proses</small>
      </div>
      <div class="sc">
        <div class="sc-icon ic-green"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <label>Donasi Selesai</label><big><?=$stats['selesai']?></big><small>berhasil diterima</small>
      </div>
      <div class="sc">
        <div class="sc-icon ic-blue"><svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/></svg></div>
        <label>E-Sertifikat</label><big><?=$stats['selesai']?></big><small>dapat diunduh</small>
      </div>
    </div>
    <?php if ($stats['perlu_resi']>0): ?>
    <div class="alert-resi">
      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
      <p><strong><?=$stats['perlu_resi']?> tawaran disetujui</strong> — Segera masukkan nomor resi untuk melanjutkan pengiriman. <a href="?tab=tawaran">Lihat sekarang</a></p>
    </div>
    <?php endif; ?>
    <div class="section-hdr"><h3>Donasi Terbaru</h3><a href="?tab=riwayat">Lihat semua</a></div>
    <div class="card">
      <div style="overflow-x:auto">
      <table>
        <thead><tr><th>ID</th><th>Barang</th><th>Yayasan</th><th>Qty</th><th>Status</th><th>Tanggal</th></tr></thead>
        <tbody>
          <?php if(empty($riwayat)): ?><tr class="empty-row"><td colspan="6">Belum ada donasi. <a href="?tab=ajukan" style="color:var(--moss);font-weight:600">Mulai donasi pertama</a></td></tr><?php endif; ?>
          <?php foreach($riwayat as $d):
            [$stLbl,$stBg,$stClr]=$stMap[$d['status_donasi']]??[$d['status_donasi'],'#f3f4f6','#6b7280']; ?>
          <tr>
            <td><span class="mono"><?=htmlspecialchars($d['donasi_id'])?></span></td>
            <td><strong><?=htmlspecialchars($d['nama_barang'])?></strong></td>
            <td style="color:var(--muted)"><?=htmlspecialchars($d['nama_yayasan'])?></td>
            <td><?=$d['qty_donasi']?> unit</td>
            <td><span class="st-badge" style="background:<?=$stBg?>;color:<?=$stClr?>"><?=$stLbl?></span></td>
            <td style="color:var(--muted);font-size:.78rem"><?=date('d M Y',strtotime($d['created_at']))?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

  <?php /* ═══ AJUKAN DONASI ═══ */ elseif($tab==='ajukan'): ?>
    <p style="font-size:.85rem;color:var(--muted);margin-bottom:18px">Pilih item kebutuhan dari yayasan terverifikasi. Setelah tawaran disetujui, Anda kirim barang sendiri dan input nomor resi.</p>
    <?php if(empty($katalog)): ?>
    <div style="text-align:center;padding:60px 20px;color:var(--muted)">
      <div style="width:64px;height:64px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px"><svg width="28" height="28" fill="none" stroke="#4aad6b" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg></div>
      <h3 style="font-size:1rem;font-weight:700;color:var(--body)">Semua kebutuhan sudah terpenuhi</h3>
      <p style="font-size:.82rem;margin-top:6px">Tidak ada item yang membutuhkan donasi saat ini.</p>
    </div>
    <?php else: ?>
    <div class="kat-grid">
      <?php foreach($katalog as $k):
        $pct = $k['target_butuh']>0?min(100,round(($k['jumlah_terkumpul']/$k['target_butuh'])*100)):0;
        $urgMap=['high'=>['Mendesak','urg-high'],'med'=>['Sedang','urg-med'],'low'=>['Normal','urg-low']];
        [$urgLbl,$urgCls]=$urgMap[$k['urgensi']]??['Normal','urg-low'];
      ?>
      <div class="kat-card">
        <div class="kat-top">
          <h4><?=htmlspecialchars($k['nama_barang'])?></h4>
          <span class="urg <?=$urgCls?>"><?=$urgLbl?></span>
        </div>
        <div class="kat-yay">
          <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="display:inline;vertical-align:middle"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>
          <?=htmlspecialchars($k['nama_yayasan'])?><?php if($k['kota_yayasan']): ?> · <?=htmlspecialchars(mb_substr($k['kota_yayasan'],0,25))?><?php endif; ?>
        </div>
        <div class="prog-bar"><div class="prog-fill" style="width:<?=$pct?>%"></div></div>
        <div class="prog-lbl"><span><?=number_format($k['jumlah_terkumpul'])?> terkumpul</span><span><?=number_format($k['target_butuh'])?> dibutuhkan</span></div>
        <?php if($k['deskripsi']): ?><p style="font-size:.73rem;color:var(--muted);margin-bottom:10px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?=htmlspecialchars($k['deskripsi'])?></p><?php endif; ?>
        <button class="btn-don" onclick="openDonasi(<?=$k['id']?>,'<?=addslashes(htmlspecialchars($k['nama_barang']))?>', '<?=addslashes(htmlspecialchars($k['nama_yayasan']))?>')">Donasikan Sekarang</button>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  <?php /* ═══ TAWARAN SAYA ═══ */ elseif($tab==='tawaran'): ?>
    <div class="filter-tabs">
      <button class="ftab active" onclick="filterTawaran('semua',this)">Semua (<?=count($tawaran)?>)</button>
      <?php
        $stCount=['menunggu'=>0,'disetujui'=>0,'ditolak'=>0,'dikirim'=>0,'selesai'=>0];
        foreach($tawaran as $t) { if(isset($stCount[$t['status_donasi']])) $stCount[$t['status_donasi']]++; }
      ?>
      <?php foreach(['menunggu'=>'Menunggu','disetujui'=>'Perlu Resi','ditolak'=>'Ditolak','dikirim'=>'Dikirim','selesai'=>'Selesai'] as $k=>$v): ?>
        <?php if($stCount[$k]>0): ?>
        <button class="ftab" onclick="filterTawaran('<?=$k?>',this)"><?=$v?> (<?=$stCount[$k]?>)</button>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <div class="card">
      <div style="overflow-x:auto">
      <table>
        <thead><tr><th>ID</th><th>Barang</th><th>Yayasan</th><th>Qty</th><th>Status</th><th>Resi</th><th>Tanggal</th><th>Aksi</th></tr></thead>
        <tbody id="tawaranTbl">
          <?php if(empty($tawaran)): ?><tr class="empty-row"><td colspan="8">Belum ada tawaran donasi. <a href="?tab=ajukan" style="color:var(--moss);font-weight:600">Ajukan sekarang</a></td></tr><?php endif; ?>
          <?php foreach($tawaran as $d):
            [$stLbl,$stBg,$stClr]=$stMap[$d['status_donasi']]??[$d['status_donasi'],'#f3f4f6','#6b7280']; ?>
          <tr data-status="<?=$d['status_donasi']?>">
            <td><span class="mono"><?=htmlspecialchars($d['donasi_id'])?></span></td>
            <td><strong><?=htmlspecialchars($d['nama_barang'])?></strong></td>
            <td style="color:var(--muted);font-size:.8rem"><?=htmlspecialchars($d['nama_yayasan'])?></td>
            <td><?=$d['qty_donasi']?></td>
            <td><span class="st-badge" style="background:<?=$stBg?>;color:<?=$stClr?>"><?=$stLbl?></span></td>
            <td><?php if($d['no_resi']): ?><span class="mono"><?=htmlspecialchars($d['kurir'].' '.$d['no_resi'])?></span><?php else: ?><span style="color:var(--muted);font-size:.75rem">—</span><?php endif; ?></td>
            <td style="color:var(--muted);font-size:.78rem"><?=date('d M Y',strtotime($d['created_at']))?></td>
            <td>
              <?php if($d['status_donasi']==='disetujui'): ?>
                <button class="btn-resi" onclick="openResi('<?=$d['donasi_id']?>','<?=htmlspecialchars($d['nama_barang'])?>')">Input Resi</button>
              <?php elseif($d['status_donasi']==='dikirim'): ?>
                <a href="?tab=lacak&resi=<?=urlencode($d['no_resi'])?>" class="btn-cert">Lacak</a>
              <?php elseif($d['status_donasi']==='selesai'): ?>
                <a href="sertifikat.php?id=<?=$d['donasi_id']?>" class="btn-cert" target="_blank">
                  <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                  Sertifikat
                </a>
              <?php elseif($d['status_donasi']==='ditolak' && $d['alasan_tolak']): ?>
                <span style="font-size:.72rem;color:#dc2626" title="<?=htmlspecialchars($d['alasan_tolak'])?>">Ditolak: <?=htmlspecialchars(mb_substr($d['alasan_tolak'],0,25))?><?=mb_strlen($d['alasan_tolak'])>25?'...':''?></span>
              <?php else: ?><span style="color:var(--muted);font-size:.75rem">—</span><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

  <?php /* ═══ LACAK ═══ */ elseif($tab==='lacak'): ?>
    <div class="lacak-form">
      <h3>Lacak Status Pengiriman</h3>
      <p style="font-size:.82rem;color:var(--muted);margin-bottom:14px">Masukkan nomor resi untuk melihat status pengiriman barang donasi Anda.</p>
      <div class="lacak-input-row">
        <input type="text" id="resiInput" placeholder="Contoh: JNE1234567890" value="<?=htmlspecialchars($_GET['resi']??'')?>">
        <button class="btn-lacak" onclick="lacakResi()">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:inline;vertical-align:middle"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803z"/></svg>
          Lacak
        </button>
      </div>
    </div>
    <div id="lacakResult" style="display:none">
      <div class="card">
        <div class="card-header"><h3 id="lacakTitle">Informasi Pengiriman</h3></div>
        <div style="padding:20px">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px" id="lacakInfo"></div>
          <h4 style="font-size:.85rem;font-weight:700;margin-bottom:14px;color:var(--body)">Timeline Status</h4>
          <ul class="timeline" id="lacakTimeline"></ul>
        </div>
      </div>
    </div>
    <div id="lacakEmpty" style="display:none;text-align:center;padding:40px;color:var(--muted)">
      <svg width="40" height="40" fill="none" stroke="#4aad6b" stroke-width="1" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
      <p id="lacakErrMsg">Nomor resi tidak ditemukan di sistem.</p>
    </div>

  <?php /* ═══ RIWAYAT ═══ */ elseif($tab==='riwayat'): ?>
    <div class="section-hdr">
      <h3>Semua Riwayat Donasi (<?=count($riwayat)?>)</h3>
      <a href="backend/export_csv.php" class="btn-outline" style="padding:7px 14px;font-size:.8rem">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
        Export CSV
      </a>
    </div>
    <div class="card">
      <div style="overflow-x:auto">
      <table>
        <thead><tr><th>ID Donasi</th><th>Barang</th><th>Yayasan</th><th>Qty</th><th>Ekspedisi</th><th>No. Resi</th><th>Status</th><th>Tanggal</th></tr></thead>
        <tbody>
          <?php if(empty($riwayat)): ?><tr class="empty-row"><td colspan="8">Belum ada riwayat donasi.</td></tr><?php endif; ?>
          <?php foreach($riwayat as $d):
            [$stLbl,$stBg,$stClr]=$stMap[$d['status_donasi']]??[$d['status_donasi'],'#f3f4f6','#6b7280']; ?>
          <tr>
            <td><span class="mono"><?=htmlspecialchars($d['donasi_id'])?></span></td>
            <td><strong style="font-size:.83rem"><?=htmlspecialchars($d['nama_barang'])?></strong></td>
            <td style="color:var(--muted);font-size:.8rem"><?=htmlspecialchars($d['nama_yayasan'])?></td>
            <td><?=$d['qty_donasi']?></td>
            <td style="font-size:.8rem"><?=htmlspecialchars($d['kurir']??'—')?></td>
            <td><?php if($d['no_resi']): ?><span class="mono"><?=htmlspecialchars($d['no_resi'])?></span><?php else: ?>—<?php endif; ?></td>
            <td><span class="st-badge" style="background:<?=$stBg?>;color:<?=$stClr?>"><?=$stLbl?></span></td>
            <td style="color:var(--muted);font-size:.78rem;white-space:nowrap"><?=date('d M Y',strtotime($d['created_at']))?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

  <?php /* ═══ SERTIFIKAT ═══ */ elseif($tab==='sertifikat'): ?>
    <p style="font-size:.85rem;color:var(--muted);margin-bottom:20px">E-sertifikat tersedia untuk setiap donasi yang telah dikonfirmasi diterima oleh yayasan.</p>
    <?php if(empty($selesai)): ?>
    <div style="text-align:center;padding:60px 20px;color:var(--muted)">
      <div style="width:64px;height:64px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px"><svg width="28" height="28" fill="none" stroke="#2563eb" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/></svg></div>
      <h3 style="font-size:1rem;font-weight:700;color:var(--body);margin-bottom:6px">Belum ada E-Sertifikat</h3>
      <p style="font-size:.82rem">Selesaikan donasi untuk mendapatkan e-sertifikat resmi CareDrop.</p>
    </div>
    <?php else: ?>
    <div class="cert-grid">
      <?php foreach($selesai as $s): ?>
      <div class="cert-card">
        <h4><?=htmlspecialchars($s['nama_barang'])?></h4>
        <p>Kepada: <?=htmlspecialchars($s['nama_yayasan'])?></p>
        <span class="cert-qty"><?=$s['qty_donasi']?> unit</span>
        <p style="margin-bottom:12px">Selesai: <?=date('d M Y',strtotime($s['tgl_selesai']))?></p>
        <a href="sertifikat.php?id=<?=$s['donasi_id']?>" target="_blank" class="cert-dl">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
          Unduh Sertifikat
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  <?php /* ═══ PROFIL ═══ */ elseif($tab==='profil'): ?>
    <div class="prof-grid">
      <div>
        <!-- Avatar -->
        <div class="prof-section">
          <div class="avatar-section">
            <div class="avatar-circle" id="profilAvatarCircle"><?php
              $avPath2 = !empty($profil['avatar']) ? 'uploads/avatars/'.$profil['avatar'] : null;
              if ($avPath2 && file_exists(__DIR__.'/'.$avPath2)): ?>
                <img src="<?=$avPath2?>?t=<?=time()?>" alt="" id="profilAvatarImg">
              <?php else: ?>
                <span id="profilAvatarInitial"><?=$inisial?></span>
              <?php endif; ?>
            </div>
            <p style="font-size:.8rem;color:var(--muted);margin-bottom:12px">Format JPG/PNG/WEBP, maks 2MB</p>
            <form id="avatarForm" enctype="multipart/form-data">
              <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" id="avatarFile" style="display:none" onchange="uploadAvatar()">
              <button type="button" id="avatarBtn" onclick="document.getElementById('avatarFile').click()" class="btn-submit" style="font-size:.82rem;padding:8px 16px">Ganti Foto</button>
            </form>
          </div>
        </div>
        <!-- Edit Profil -->
        <div class="prof-section">
          <h3>Informasi Profil</h3>
          <form id="profilForm">
            <div class="field"><label>Nama Lengkap</label><input type="text" name="nama" value="<?=htmlspecialchars($profil['nama_lengkap']??$nama)?>" required></div>
            <div class="field"><label>Email</label><input type="email" value="<?=htmlspecialchars($profil['email']??$email)?>" disabled style="background:#f9fafb;color:var(--muted)"></div>
            <div class="field"><label>Nomor Telepon</label><input type="tel" name="no_telp" value="<?=htmlspecialchars($profil['no_telp']??$no_telp)?>" placeholder="08xxxxxxxx"></div>
            <div class="field"><label>Alamat</label><textarea name="alamat" rows="2" placeholder="Alamat lengkap"><?=htmlspecialchars($profil['alamat']??$alamat)?></textarea></div>
            <button type="submit" class="btn-submit">Simpan Perubahan</button>
          </form>
        </div>
      </div>
      <!-- Ganti Password -->
      <div>
        <div class="prof-section">
          <h3>Ganti Password</h3>
          <form id="passForm">
            <div class="field"><label>Password Lama</label><input type="password" name="password_lama" placeholder="••••••••" required></div>
            <div class="field"><label>Password Baru</label><input type="password" name="password_baru" placeholder="Minimal 6 karakter" minlength="6" required></div>
            <div class="field"><label>Konfirmasi Password Baru</label><input type="password" name="password_konfirm" placeholder="Ulangi password baru" required></div>
            <button type="submit" class="btn-submit">Ubah Password</button>
          </form>
        </div>
        <div class="prof-section" style="background:#fff1f2;border-color:#fecaca">
          <h3 style="color:#dc2626">Keluar dari Akun</h3>
          <p style="font-size:.82rem;color:#6b7280;margin-bottom:14px">Anda akan keluar dari semua sesi aktif.</p>
          <a href="backend/logout.php" class="btn-danger" style="display:inline-block;padding:9px 18px;border-radius:9px;text-decoration:none;font-weight:700;font-size:.85rem">Keluar Sekarang</a>
        </div>
      </div>
    </div>
  <?php endif; ?>

  </div><!-- /content -->
</main>

<!-- MODAL: Ajukan Donasi -->
<div class="overlay" id="donasiOverlay" onclick="if(event.target===this)closeModal('donasiOverlay')">
  <div class="modal">
    <h3>Ajukan Tawaran Donasi</h3>
    <p class="msub" id="donasiSub">—</p>
    <form id="donasiForm" enctype="multipart/form-data">
      <input type="hidden" name="katalog_id" id="katalogId">
      <div class="field"><label>Jumlah Barang (unit)</label><input type="number" name="qty" min="1" value="1" required></div>
      <div class="field"><label>Deskripsi Kondisi Barang</label><textarea name="deskripsi" rows="3" placeholder="Jelaskan kondisi barang yang akan didonasikan..."></textarea></div>
      <div class="field"><label>Foto Barang (opsional)</label><input type="file" name="foto_barang" accept="image/jpeg,image/png,image/webp"><p style="font-size:.7rem;color:var(--muted);margin-top:3px">Foto membantu yayasan menilai kondisi barang.</p></div>
      <div class="info-box"><strong>Pengiriman Mandiri:</strong> Setelah tawaran disetujui yayasan, Anda kirim barang sendiri menggunakan ekspedisi pilihan, lalu input nomor resi di menu "Tawaran Saya".</div>
      <div class="modal-footer">
        <button type="button" class="btn-sec" onclick="closeModal('donasiOverlay')">Batal</button>
        <button type="submit" class="btn-prim" id="donasiBtn">Ajukan Tawaran</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: Input Resi -->
<div class="overlay" id="resiOverlay" onclick="if(event.target===this)closeModal('resiOverlay')">
  <div class="modal">
    <h3>Masukkan Nomor Resi</h3>
    <p class="msub" id="resiSub">—</p>
    <form id="resiForm">
      <input type="hidden" name="aksi" value="input_resi">
      <input type="hidden" name="donasi_id" id="resiDonasiId">
      <div class="field"><label>Nama Ekspedisi</label><input type="text" name="kurir" placeholder="Contoh: JNE, J&T, SiCepat, Pos Indonesia..." required></div>
      <div class="field"><label>Nomor Resi / Tracking</label><input type="text" name="no_resi" placeholder="Masukkan nomor resi pengiriman" required></div>
      <div class="info-box" style="font-size:.75rem">Yayasan akan melacak paket secara mandiri menggunakan nomor ini dan menekan tombol konfirmasi setelah barang diterima.</div>
      <div class="modal-footer">
        <button type="button" class="btn-sec" onclick="closeModal('resiOverlay')">Batal</button>
        <button type="submit" class="btn-prim" id="resiBtn">Simpan Resi</button>
      </div>
    </form>
  </div>
</div>

<div id="toast"></div>

<script>
function showToast(msg, err=false) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.className = err ? 'err' : '';
  void t.offsetWidth; t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 4500);
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow = '';
}
function openDonasi(id, nama, yayasan) {
  document.getElementById('katalogId').value = id;
  document.getElementById('donasiSub').textContent = `"${nama}" — ${yayasan}`;
  document.getElementById('donasiOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function openResi(donasiId, barang) {
  document.getElementById('resiDonasiId').value = donasiId;
  document.getElementById('resiSub').textContent = `Donasi: ${barang} (${donasiId})`;
  document.getElementById('resiOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

// Submit donasi
document.getElementById('donasiForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('donasiBtn');
  btn.disabled = true; btn.textContent = 'Mengirim...';
  try {
    const fd = new FormData(this);
    const res = await fetch('backend/proses_donasi.php', {method:'POST',body:fd});
    const data = await res.json();
    if (data.ok) {
      closeModal('donasiOverlay'); this.reset();
      showToast('Tawaran berhasil diajukan! Tunggu persetujuan yayasan.');
      setTimeout(() => location.href = '?tab=tawaran', 1800);
    } else { showToast(data.error || 'Gagal mengajukan tawaran', true); }
  } catch(err) { showToast('Koneksi error', true); }
  btn.disabled = false; btn.textContent = 'Ajukan Tawaran';
});

// Submit resi
document.getElementById('resiForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('resiBtn');
  btn.disabled = true; btn.textContent = 'Menyimpan...';
  try {
    const fd = new FormData(this);
    const res = await fetch('backend/aksi_tawaran.php', {method:'POST',body:fd});
    const data = await res.json();
    if (data.ok) {
      closeModal('resiOverlay'); this.reset();
      showToast('Resi berhasil disimpan! Yayasan akan melacak paket.');
      setTimeout(() => location.reload(), 1800);
    } else { showToast(data.error || 'Gagal menyimpan resi', true); }
  } catch(err) { showToast('Koneksi error', true); }
  btn.disabled = false; btn.textContent = 'Simpan Resi';
});

// Filter tawaran
function filterTawaran(status, btn) {
  document.querySelectorAll('.ftab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#tawaranTbl tr').forEach(tr => {
    if (status === 'semua' || tr.dataset.status === status) tr.style.display = '';
    else tr.style.display = 'none';
  });
}

// Lacak resi
async function lacakResi() {
  const resi = document.getElementById('resiInput').value.trim();
  if (!resi) { showToast('Masukkan nomor resi terlebih dahulu', true); return; }
  document.getElementById('lacakResult').style.display = 'none';
  document.getElementById('lacakEmpty').style.display = 'none';
  try {
    const res = await fetch(`backend/lacak_resi.php?resi=${encodeURIComponent(resi)}`);
    const data = await res.json();
    if (data.ok && data.resi) {
      const r = data.resi;
      document.getElementById('lacakTitle').textContent = `Resi: ${r.no_resi}`;
      document.getElementById('lacakInfo').innerHTML = `
        <div><label style="font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Ekspedisi</label><p style="font-weight:700;margin-top:3px">${r.kurir||'—'}</p></div>
        <div><label style="font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Barang</label><p style="font-weight:700;margin-top:3px">${r.nama_barang}</p></div>
        <div><label style="font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Donatur</label><p style="margin-top:3px">${r.nama_donatur}</p></div>
        <div><label style="font-size:.68rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.4px">Yayasan</label><p style="margin-top:3px">${r.nama_yayasan}</p></div>`;
      let tlHtml = '';
      (data.steps||[]).forEach((s,i) => {
        const done = s.done;
        const curr = i === data.current && !done;
        tlHtml += `<li>
          <div class="tl-dot ${done?'done':curr?'active':''}">
            ${done?'<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>':'<span style="width:8px;height:8px;border-radius:50%;background:currentColor;display:block;margin:auto"></span>'}
          </div>
          <div class="tl-label" style="color:${done?'var(--moss)':curr?'var(--amber)':'var(--muted)'}">${s.label}</div>
          <div class="tl-desc">${s.desc}</div>
        </li>`;
      });
      document.getElementById('lacakTimeline').innerHTML = tlHtml;
      document.getElementById('lacakResult').style.display = 'block';
    } else {
      document.getElementById('lacakErrMsg').textContent = data.error || 'Nomor resi tidak ditemukan.';
      document.getElementById('lacakEmpty').style.display = 'block';
    }
  } catch(err) { showToast('Gagal terhubung ke server', true); }
}

// Auto-lacak jika ada ?resi=
<?php if (!empty($_GET['resi'])): ?>
window.addEventListener('DOMContentLoaded', () => lacakResi());
<?php endif; ?>

// Submit profil
document.getElementById('profilForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = this.querySelector('[type=submit]');
  btn.disabled = true; btn.textContent = 'Menyimpan...';
  try {
    const fd = new FormData(this);
    const res = await fetch('backend/update_profil.php', {method:'POST',body:fd});
    const data = await res.json();
    if (data.ok) showToast('Profil berhasil diperbarui!');
    else showToast(data.error || 'Gagal menyimpan', true);
  } catch(err) { showToast('Koneksi error', true); }
  btn.disabled = false; btn.textContent = 'Simpan Perubahan';
});

// Submit ganti password
document.getElementById('passForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const baru = this.password_baru.value, konfirm = this.password_konfirm.value;
  if (baru !== konfirm) { showToast('Konfirmasi password tidak cocok', true); return; }
  const btn = this.querySelector('[type=submit]');
  btn.disabled = true; btn.textContent = 'Mengubah...';
  try {
    const fd = new FormData(this);
    const res = await fetch('backend/ganti_password.php', {method:'POST',body:fd});
    const data = await res.json();
    if (data.ok) { this.reset(); showToast('Password berhasil diubah!'); }
    else showToast(data.error || 'Gagal mengubah password', true);
  } catch(err) { showToast('Koneksi error', true); }
  btn.disabled = false; btn.textContent = 'Ubah Password';
});

// Upload avatar
// Upload avatar — update foto langsung tanpa reload halaman
async function uploadAvatar() {
  const file = document.getElementById('avatarFile').files[0];
  if (!file) return;

  const btn = document.getElementById('avatarBtn');
  btn.disabled = true; btn.textContent = 'Mengupload...';

  // Preview lokal langsung
  const reader = new FileReader();
  reader.onload = e => {
    updateAvatarUI(e.target.result);
  };
  reader.readAsDataURL(file);

  const form = document.getElementById('avatarForm');
  const fd = new FormData(form);
  try {
    const res = await fetch('backend/upload_avatar.php', {method:'POST',body:fd});
    const data = await res.json();
    if (data.ok) {
      // Gunakan URL dari server (bukan blob lokal) agar konsisten
      updateAvatarUI(data.url + '?t=' + Date.now());
      showToast('✅ Foto profil berhasil diperbarui!');
    } else {
      showToast(data.error || 'Gagal upload foto', true);
    }
  } catch(err) {
    showToast('Koneksi error', true);
  }
  btn.disabled = false; btn.textContent = 'Ganti Foto';
  document.getElementById('avatarFile').value = '';
}

// Update semua elemen avatar di halaman (sidebar + profil)
function updateAvatarUI(src) {
  // Sidebar user avatar
  const sideAv = document.querySelector('.sidebar .user-av');
  if (sideAv) {
    sideAv.innerHTML = `<img src="${src}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;
  }
  // Profil avatar circle
  const profAv = document.getElementById('profilAvatarCircle');
  if (profAv) {
    profAv.innerHTML = `<img src="${src}" alt="" id="profilAvatarImg">`;
  }
}
</script>
</body>
</html>
