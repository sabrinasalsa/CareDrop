<?php
session_start();
require_once __DIR__ . '/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_SESSION['id'])) {
    echo "<script>alert('Silakan login terlebih dahulu!'); window.location.href='../index.php';</script>";
    exit;
}

$donatur_id  = (int) $_SESSION['id'];
$katalog_id  = (int) ($_POST['katalog_id'] ?? 0);
$qty_donasi  = (int) ($_POST['qty'] ?? 0);
$deskripsi   = htmlspecialchars($_POST['deskripsi'] ?? '');
$kurir       = htmlspecialchars($_POST['kurir'] ?? '');
$kota_asal   = htmlspecialchars($_POST['kota_asal'] ?? '');
$kota_tujuan = htmlspecialchars($_POST['kota_tujuan'] ?? '');
$berat       = (float) ($_POST['berat'] ?? 1);

if ($katalog_id <= 0 || $qty_donasi <= 0) {
    echo "<script>alert('Data donasi tidak valid.'); window.location.href='../index.php';</script>";
    exit;
}

$id_donasi = 'CDR-' . date('Ymd') . '-' . rand(100, 999);
$no_resi   = 'CD' . strtoupper(substr($kurir, 0, 3)) . rand(1000, 9999) . 'ID';

// Handle upload foto
$fileName = null;
if (isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] === UPLOAD_ERR_OK) {
    $targetDir = dirname(__DIR__) . '/uploads/donasi/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['foto_barang']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp'])) {
        $fileName   = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        move_uploaded_file($_FILES['foto_barang']['tmp_name'], $targetDir . $fileName);
    }
}

// Insert donasi
$stmt1 = $koneksi->prepare(
    "INSERT INTO donasi (id, donatur_id, katalog_id, qty_donasi, deskripsi_kondisi, foto_barang)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt1->bind_param("siiiss", $id_donasi, $donatur_id, $katalog_id, $qty_donasi, $deskripsi, $fileName);

if ($stmt1->execute()) {
    $tipe_layanan    = in_array($kurir, ['gosend','grab']) ? 'instant' : 'reguler';
    $estimasi_ongkir = 15000 * $berat;

    $stmt2 = $koneksi->prepare(
        "INSERT INTO pengiriman (donasi_id, kurir, tipe_layanan, kota_asal, kota_tujuan, berat_kg, estimasi_ongkir, no_resi)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt2->bind_param("sssssids", $id_donasi, $kurir, $tipe_layanan, $kota_asal, $kota_tujuan, $berat, $estimasi_ongkir, $no_resi);
    $stmt2->execute();
    $stmt2->close();

    echo "<script>alert('Donasi Berhasil! No Resi Anda: $no_resi'); window.location.href='../index.php';</script>";
} else {
    echo "<script>alert('Gagal memproses donasi. Coba lagi.'); window.location.href='../index.php';</script>";
}

$stmt1->close();
$koneksi->close();
