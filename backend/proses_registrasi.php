<?php
session_start();
require_once __DIR__ . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php'); exit;
}

// ── Sanitasi input ──
$nama     = trim($_POST['nama_lengkap'] ?? '');
$email    = trim($_POST['email'] ?? '');
$no_telp  = trim($_POST['no_telp'] ?? '');
$alamat   = trim($_POST['alamat'] ?? '');
$role     = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';
$konfirm  = $_POST['password_confirm'] ?? '';

// ── Helper: simpan data lama ke session lalu redirect dengan error ──
function redirectWithError(string $msg, array $old): void {
    $_SESSION['reg_old']   = $old;
    $_SESSION['reg_error'] = $msg;
    header('Location: ../login.php?tab=register');
    exit;
}

$old = compact('nama', 'email', 'no_telp', 'alamat', 'role');

// ── Validasi wajib isi ──
if (empty($nama) || empty($email) || empty($password) || empty($no_telp) || empty($role)) {
    redirectWithError('Semua field wajib diisi!', $old);
}

// ── Validasi format email ──
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithError('Format email tidak valid!', $old);
}

// ── Validasi nama (hanya huruf, spasi, tanda titik) ──
if (!preg_match('/^[\p{L}\s.\'-]{2,150}$/u', $nama)) {
    redirectWithError('Nama tidak valid! Hanya huruf dan spasi.', $old);
}

// ── Validasi no_telp (hanya digit, +, -, spasi, maks 20 char) ──
if (!preg_match('/^[\d\+\-\s\(\)]{7,20}$/', $no_telp)) {
    redirectWithError('Nomor telepon tidak valid!', $old);
}

// ── Whitelist role ──
$allowed_roles = ['donatur', 'penerima'];
if (!in_array($role, $allowed_roles, true)) {
    redirectWithError('Role tidak valid!', $old);
}

// ── Validasi kekuatan password ──
if (strlen($password) < 8) {
    redirectWithError('Sandi minimal 8 karakter!', $old);
}
if (!preg_match('/[0-9]/', $password)) {
    redirectWithError('Sandi harus mengandung minimal 1 angka!', $old);
}
if ($konfirm !== $password) {
    redirectWithError('Konfirmasi sandi tidak cocok!', $old);
}

// ── Hash password dengan bcrypt ──
$hashed            = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$status_verifikasi = ($role === 'penerima') ? 'pending' : 'verified';

// ── Insert ke database via PDO prepared statement ──
try {
    $stmt = $pdo->prepare(
        "INSERT INTO users (nama_lengkap, email, password, no_telp, alamat, role, status_verifikasi)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        htmlspecialchars($nama, ENT_QUOTES, 'UTF-8'),
        $email,
        $hashed,
        htmlspecialchars($no_telp, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($alamat, ENT_QUOTES, 'UTF-8'),
        $role,
        $status_verifikasi
    ]);

    $pdo = null;
    // Hapus data lama jika berhasil
    unset($_SESSION['reg_old'], $_SESSION['reg_error']);

    if ($role === 'penerima') {
        header('Location: ../index.php'); exit;
    } else {
        header('Location: ../login.php?flash=registered'); exit;
    }

} catch (PDOException $e) {
    $pdo = null;
    // Duplicate entry (email sudah terdaftar)
    if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1062')) {
        redirectWithError('Email sudah terdaftar! Gunakan email lain.', $old);
    } else {
        redirectWithError('Terjadi kesalahan server. Silakan coba lagi.', $old);
    }
}
