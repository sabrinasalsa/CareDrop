<?php
session_start();
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$yayasan_id   = (int) $_SESSION['id'];
$nama_barang  = htmlspecialchars(trim($_POST['nama_barang'] ?? ''));
$kategori     = $_POST['kategori'] ?? 'pakaian';
$urgensi      = $_POST['urgensi']  ?? 'med';
$target_butuh = (int) ($_POST['target'] ?? 0);

if (empty($nama_barang) || $target_butuh <= 0) {
    echo "<script>alert('Isi semua data kebutuhan!'); window.history.back();</script>";
    exit;
}

$stmt = $koneksi->prepare(
    "INSERT INTO katalog_kebutuhan (yayasan_id, nama_barang, kategori, urgensi, target_butuh)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("isssi", $yayasan_id, $nama_barang, $kategori, $urgensi, $target_butuh);

if ($stmt->execute()) {
    echo "<script>alert('Kebutuhan berhasil ditambahkan ke katalog!'); window.location.href='../yayasan/kelola_katalog.php';</script>";
} else {
    echo "<script>alert('Terjadi kesalahan teknis. Coba lagi.'); window.history.back();</script>";
}

$stmt->close();
$koneksi->close();
