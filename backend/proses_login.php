<?php
session_start();
require_once __DIR__ . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php'); exit;
}

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password'] ?? '';

// Helper: redirect ke login dengan pesan error inline
function loginError(string $msg, string $email = ''): never {
    $_SESSION['login_error'] = $msg;
    if ($email) $_SESSION['login_email'] = $email;
    header('Location: ../login.php'); exit;
}

// ── Validasi input dasar ──
if (empty($email) || empty($password)) {
    loginError('Email dan kata sandi wajib diisi.');
}

// ── Query user via PDO prepared statement ──
try {
    $stmt = $pdo->prepare("SELECT id, nama_lengkap, email, password, role, no_telp, alamat, status_verifikasi FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
} catch (PDOException $e) {
    loginError('Terjadi kesalahan server. Silakan coba lagi.', $email);
}

// ── Verifikasi password (bcrypt) ──
if (!$row || !password_verify($password, $row['password'])) {
    loginError('Email atau kata sandi yang Anda masukkan salah.', $email);
}

// ── Cek status verifikasi ──
if ($row['role'] === 'penerima' && ($row['status_verifikasi'] ?? '') === 'pending') {
    // Biarkan login, verifikasi dilakukan melalui dokumen
}
if (($row['status_verifikasi'] ?? '') === 'rejected') {
    loginError('Akun Anda telah ditolak atau dinonaktifkan. Hubungi admin CareDrop.');
}

// ── Login berhasil: bersihkan session error ──
unset($_SESSION['login_error'], $_SESSION['login_email']);
session_regenerate_id(true);

$_SESSION['id']            = (int)$row['id'];
$_SESSION['nama']          = $row['nama_lengkap'];
$_SESSION['email']         = $row['email'];
$_SESSION['role']          = $row['role'];
$_SESSION['no_telp']       = $row['no_telp']  ?? '';
$_SESSION['alamat']        = $row['alamat']   ?? '';
$_SESSION['status_verifikasi'] = $row['status_verifikasi'] ?? '';
$_SESSION['last_activity'] = time();

$pdo = null;

// ── Redirect sesuai role ──
switch ($row['role']) {
    case 'admin':    header('Location: ../admin/index.php'); break;
    case 'penerima': header('Location: ../yayasan/dashboard_yayasan.php'); break;
    default:         header('Location: ../dashboard.php'); break;
}
exit;
