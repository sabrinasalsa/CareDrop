<?php
session_start();
require_once __DIR__ . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php'); exit;
}


$email    = trim($_POST['email']    ?? '');
$password = $_POST['password'] ?? '';
$ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$key      = 'login_fail_' . md5($ip);

// ── Validasi input dasar ──
if (empty($email) || empty($password)) {
    echo "<script>alert('Email dan sandi wajib diisi!'); window.location.href='../login.php';</script>"; exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Format email tidak valid!'); window.location.href='../login.php';</script>"; exit;
}

// ── Rate limiting: maks 5 percobaan per 5 menit ──
$attempts = (int)($_SESSION[$key . '_count'] ?? 0);
$lastFail = (int)($_SESSION[$key . '_time']  ?? 0);

if ($attempts >= 5 && (time() - $lastFail) < 300) {
    $wait = 300 - (time() - $lastFail);
    echo "<script>alert('Terlalu banyak percobaan login. Coba lagi dalam " . ceil($wait / 60) . " menit.'); window.location.href='../login.php';</script>"; exit;
}
// Reset counter kalau sudah lebih dari 5 menit
if ((time() - $lastFail) >= 300) {
    $_SESSION[$key . '_count'] = 0;
}

// ── Query user via PDO prepared statement ──
try {
    $stmt = $pdo->prepare("SELECT id, nama_lengkap, email, password, role, no_telp, alamat, status_verifikasi FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
} catch (PDOException $e) {
    echo "<script>alert('Terjadi kesalahan server. Silakan coba lagi.'); window.location.href='../login.php';</script>"; exit;
}

// ── Verifikasi password (bcrypt) ──
if (!$row || !password_verify($password, $row['password'])) {
    $_SESSION[$key . '_count'] = $attempts + 1;
    $_SESSION[$key . '_time']  = time();
    $sisa = 5 - ($attempts + 1);
    $msg  = $sisa > 0
        ? "Email atau sandi salah! Sisa percobaan: $sisa"
        : "Terlalu banyak percobaan. Tunggu 5 menit.";
    echo "<script>alert('$msg'); window.location.href='../login.php';</script>"; exit;
}

// ── Cek status verifikasi ──
if ($row['role'] === 'penerima' && ($row['status_verifikasi'] ?? '') === 'pending') {
    echo "<script>alert('Akun Anda masih menunggu verifikasi Admin.'); window.location.href='../login.php';</script>"; exit;
}
if (($row['status_verifikasi'] ?? '') === 'rejected') {
    echo "<script>alert('Akun Anda telah ditolak atau dinonaktifkan. Hubungi admin.'); window.location.href='../login.php';</script>"; exit;
}

// ── Login berhasil: reset counter & set session ──
unset($_SESSION[$key . '_count'], $_SESSION[$key . '_time']);
session_regenerate_id(true); // Cegah session fixation

$_SESSION['id']            = (int)$row['id'];
$_SESSION['nama']          = $row['nama_lengkap'];
$_SESSION['email']         = $row['email'];
$_SESSION['role']          = $row['role'];
$_SESSION['no_telp']       = $row['no_telp']  ?? '';
$_SESSION['alamat']        = $row['alamat']   ?? '';
$_SESSION['last_activity'] = time();

$pdo = null; // Tutup koneksi PDO

// ── Redirect sesuai role (multilevel) ──
switch ($row['role']) {
    case 'admin':
        header('Location: ../admin/index.php'); break;
    case 'penerima':
        header('Location: ../yayasan/kelola_katalog.php'); break;
    case 'donatur':
    default:
        header('Location: ../dashboard.php'); break;
}
exit;
