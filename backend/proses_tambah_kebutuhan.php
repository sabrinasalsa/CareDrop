<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $yayasan_id    = $_SESSION['id'];
    $nama_barang   = htmlspecialchars($_POST['nama_barang']);
    $kategori      = $_POST['kategori'];
    $urgensi       = $_POST['urgensi'];
    $target_butuh  = (int) $_POST['target'];

    if (empty($nama_barang) || empty($target_butuh)) {
        echo "<script>alert('Isi semua data kebutuhan!'); window.history.back();</script>";
    } else {
        $query = "INSERT INTO katalog_kebutuhan (yayasan_id, nama_barang, kategori, urgensi, target_butuh) VALUES (?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("isssi", $yayasan_id, $nama_barang, $kategori, $urgensi, $target_butuh);

        if ($stmt->execute()) {
            echo "<script>alert('Kebutuhan berhasil ditambahkan ke katalog!'); window.location.href='../yayasan/kelola_katalog.php';</script>";
        } else {
            echo "<script>alert('Terjadi kesalahan teknis.'); window.history.back();</script>";
        }
        $stmt->close();
    }
}
$koneksi->close();
?>