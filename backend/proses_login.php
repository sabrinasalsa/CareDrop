<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/koneksi.php';
ob_end_clean();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php'); exit;
}

$email    = htmlspecialchars(trim($_POST['email']    ?? ''));
$password = $_POST['password'] ?? '';
$ip       = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$key      = 'login_fail_' . md5($ip);

// ── Rate limiting: maksimal 5 percobaan per 5 menit ──
$attempts = (int)($_SESSION[$key . '_count'] ?? 0);
$lastFail = (int)($_SESSION[$key . '_time']  ?? 0);

if ($attempts >= 5 && (time() - $lastFail) < 300) {
    $wait = 300 - (time() - $lastFail);
    echo "<script>alert('Terlalu banyak percobaan login. Coba lagi dalam " . ceil($wait/60) . " menit.'); window.location.href='../login.php';</script>";
    exit;
}
// Reset counter kalau sudah lebih dari 5 menit
if ((time() - $lastFail) >= 300) {
    $_SESSION[$key . '_count'] = 0;
}

if (empty($email) || empty($password)) {
    echo "<script>alert('Email dan sandi wajib diisi!'); window.location.href='../login.php';</script>"; exit;
}

$stmt = $koneksi->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || !password_verify($password, $row['password'])) {
    // Catat percobaan gagal
    $_SESSION[$key . '_count'] = ($attempts + 1);
    $_SESSION[$key . '_time']  = time();
    $sisa = 5 - ($attempts + 1);
    $msg  = $sisa > 0 ? "Email atau sandi salah! Sisa percobaan: $sisa" : "Terlalu banyak percobaan. Tunggu 5 menit.";
    echo "<script>alert('$msg'); window.location.href='../login.php';</script>"; exit;
}

if ($row['role'] === 'penerima' && ($row['status_verifikasi'] ?? '') === 'pending') {
    echo "<script>alert('Akun Anda masih menunggu verifikasi Admin.'); window.location.href='../login.php';</script>"; exit;
}
if (($row['status_verifikasi'] ?? '') === 'rejected') {
    echo "<script>alert('Akun Anda telah ditolak atau dinonaktifkan. Hubungi admin.'); window.location.href='../login.php';</script>"; exit;
}

// Reset counter percobaan
unset($_SESSION[$key . '_count'], $_SESSION[$key . '_time']);

// Regenerate session ID untuk keamanan
session_regenerate_id(true);

$_SESSION['id']            = $row['id'];
$_SESSION['nama']          = $row['nama_lengkap'];
$_SESSION['email']         = $row['email'];
$_SESSION['role']          = $row['role'];
$_SESSION['no_telp']       = $row['no_telp']  ?? '';
$_SESSION['alamat']        = $row['alamat']   ?? '';
$_SESSION['last_activity'] = time();

$koneksi->close();

// Redirect sesuai role
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
