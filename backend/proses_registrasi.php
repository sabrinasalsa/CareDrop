<?php
require_once __DIR__ . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$nama     = htmlspecialchars(trim($_POST['nama_lengkap'] ?? ''));
$email    = htmlspecialchars(trim($_POST['email'] ?? ''));
$no_telp  = htmlspecialchars(trim($_POST['no_telp'] ?? ''));
$alamat   = htmlspecialchars(trim($_POST['alamat'] ?? ''));
$role     = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($nama) || empty($email) || empty($password) || empty($no_telp) || empty($role)) {
    echo "<script>alert('ERROR: ISI SEMUA DATA TERLEBIH DAHULU!'); window.location.href='../login.php?tab=register';</script>";
    exit;
}

if (strlen($password) < 6) {
    echo "<script>alert('Sandi minimal 6 karakter!'); window.location.href='../login.php?tab=register';</script>";
    exit;
}

$hashed           = password_hash($password, PASSWORD_DEFAULT);
$status_verifikasi = ($role === 'penerima') ? 'pending' : 'verified';

$stmt = $koneksi->prepare(
    "INSERT INTO users (nama_lengkap, email, password, no_telp, alamat, role, status_verifikasi)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssssss", $nama, $email, $hashed, $no_telp, $alamat, $role, $status_verifikasi);

try {
    if ($stmt->execute()) {
        if ($role === 'penerima') {
            echo "<script>alert('Registrasi Berhasil! Akun Penerima Anda sedang menunggu verifikasi Admin.'); window.location.href='../login.php?flash=registered';</script>";
        } else {
            header('Location: ../login.php?flash=registered');
            exit;
        }
    }
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) {
        echo "<script>alert('ERROR: EMAIL SUDAH TERDAFTAR!'); window.location.href='../login.php?tab=register';</script>";
    } else {
        echo "<script>alert('Terjadi kesalahan: " . addslashes($e->getMessage()) . "'); window.location.href='../login.php?tab=register';</script>";
    }
}

$stmt->close();
$koneksi->close();
