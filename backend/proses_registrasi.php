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
$konfirm  = $_POST['password_konfirm'] ?? $password; // opsional konfirmasi

// ── Validasi wajib isi ──
if (empty($nama) || empty($email) || empty($password) || empty($no_telp) || empty($role)) {
    echo "<script>alert('Semua field wajib diisi!'); window.location.href='../login.php?tab=register';</script>"; exit;
}

// ── Validasi format email ──
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Format email tidak valid!'); window.location.href='../login.php?tab=register';</script>"; exit;
}

// ── Validasi nama (hanya huruf, spasi, tanda titik) ──
if (!preg_match('/^[\p{L}\s.\'-]{2,150}$/u', $nama)) {
    echo "<script>alert('Nama tidak valid! Hanya huruf dan spasi.'); window.location.href='../login.php?tab=register';</script>"; exit;
}

// ── Validasi no_telp (hanya digit, +, -, spasi, maks 20 char) ──
if (!preg_match('/^[\d\+\-\s\(\)]{7,20}$/', $no_telp)) {
    echo "<script>alert('Nomor telepon tidak valid!'); window.location.href='../login.php?tab=register';</script>"; exit;
}

// ── Whitelist role ──
$allowed_roles = ['donatur', 'penerima'];
if (!in_array($role, $allowed_roles, true)) {
    echo "<script>alert('Role tidak valid!'); window.location.href='../login.php?tab=register';</script>"; exit;
}

// ── Validasi kekuatan password ──
if (strlen($password) < 8) {
    echo "<script>alert('Sandi minimal 8 karakter!'); window.location.href='../login.php?tab=register';</script>"; exit;
}
if (!preg_match('/[0-9]/', $password)) {
    echo "<script>alert('Sandi harus mengandung minimal 1 angka!'); window.location.href='../login.php?tab=register';</script>"; exit;
}
if ($konfirm !== $password) {
    echo "<script>alert('Konfirmasi sandi tidak cocok!'); window.location.href='../login.php?tab=register';</script>"; exit;
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

    if ($role === 'penerima') {
        echo "<script>alert('Registrasi Berhasil! Akun Anda sedang menunggu verifikasi Admin.'); window.location.href='../login.php?flash=registered';</script>";
    } else {
        header('Location: ../login.php?flash=registered'); exit;
    }

} catch (PDOException $e) {
    $pdo = null;
    // Duplicate entry (email sudah terdaftar)
    if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1062')) {
        echo "<script>alert('Email sudah terdaftar! Gunakan email lain.'); window.location.href='../login.php?tab=register';</script>";
    } else {
        echo "<script>alert('Terjadi kesalahan server. Silakan coba lagi.'); window.location.href='../login.php?tab=register';</script>";
    }
}
