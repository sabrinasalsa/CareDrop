<?php
session_start();
require_once __DIR__ . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$email    = htmlspecialchars(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo "<script>alert('Email dan sandi wajib diisi!'); window.location.href='../index.php';</script>";
    exit;
}

$stmt = $koneksi->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo "<script>alert('Email tidak ditemukan!'); window.location.href='../index.php';</script>";
    exit;
}

if (!password_verify($password, $row['password'])) {
    echo "<script>alert('Kata sandi salah!'); window.location.href='../index.php';</script>";
    exit;
}

// Cek verifikasi penerima
if ($row['role'] === 'penerima' && ($row['status_verifikasi'] ?? '') === 'pending') {
    echo "<script>alert('Akun Anda masih menunggu verifikasi Admin. Silakan tunggu konfirmasi.'); window.location.href='../index.php';</script>";
    exit;
}

// Set session
$_SESSION['id']      = $row['id'];
$_SESSION['nama']    = $row['nama_lengkap'];
$_SESSION['email']   = $row['email'];
$_SESSION['role']    = $row['role'];
$_SESSION['no_telp'] = $row['no_telp']  ?? '';
$_SESSION['alamat']  = $row['alamat']   ?? '';

$koneksi->close();
header('Location: ../index.php');
exit;
