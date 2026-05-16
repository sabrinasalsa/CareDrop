<?php
session_start();
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../yayasan/kelola_katalog.php'); exit;
}

$yayasan_id   = (int) $_SESSION['id'];
$nama_barang  = htmlspecialchars(trim($_POST['nama_barang'] ?? ''));
$kategori     = $_POST['kategori']    ?? 'pakaian';
$urgensi      = $_POST['urgensi']     ?? 'med';
$target_butuh = (int)($_POST['target_butuh'] ?? $_POST['target'] ?? 0);

if (empty($nama_barang) || $target_butuh < 1) {
    header('Location: ../yayasan/tambah_kebutuhan.php?err=empty'); exit;
}

try {
    $stmt = $koneksi->prepare(
        "INSERT INTO katalog_kebutuhan (yayasan_id, nama_barang, kategori, urgensi, target_butuh, jumlah_terkumpul)
         VALUES (?, ?, ?, ?, ?, 0)"
    );
    $stmt->bind_param("isssi", $yayasan_id, $nama_barang, $kategori, $urgensi, $target_butuh);
    $stmt->execute();
    $stmt->close();
    $koneksi->close();
    header('Location: ../yayasan/kelola_katalog.php?added=1'); exit;
} catch (Throwable $e) {
    $koneksi->close();
    header('Location: ../yayasan/tambah_kebutuhan.php?err=' . urlencode($e->getMessage())); exit;
}
