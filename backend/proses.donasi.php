<?php
session_start();
require 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Ambil data dari form
    $donatur_id = $_SESSION['id'] ?? 1; // Fallback ke 1 untuk testing jika belum login
    $katalog_id = (int) $_POST['katalog_id'];
    $qty_donasi = (int) $_POST['qty'];
    $deskripsi  = htmlspecialchars($_POST['deskripsi']);
    
    // Data Logistik
    $kurir       = $_POST['kurir'];
    $kota_asal   = $_POST['kota_asal'];
    $kota_tujuan = $_POST['kota_tujuan'];
    $berat       = (int) $_POST['berat'];
    
    // Generate ID Donasi & Resi otomatis
    $id_donasi = 'CDR-' . date('Ymd') . '-' . rand(100, 999);
    $no_resi   = 'CD' . strtoupper(substr($kurir, 0, 3)) . rand(1000, 9999) . 'ID';

    // 2. Handle Upload Foto Barang
    $fileName = null;
    if (isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../uploads/donasi/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true); // Buat folder jika belum ada
        
        $fileName = time() . "_" . basename($_FILES['foto_barang']['name']);
        $targetFile = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        
        if (in_array($fileType, ["jpg", "jpeg", "png"])) {
            move_uploaded_file($_FILES['foto_barang']['tmp_name'], $targetFile);
        } else {
            die("Format file tidak diizinkan.");
        }
    }

    // 3. Insert ke Tabel Donasi
    $stmt1 = $koneksi->prepare("INSERT INTO donasi (id, donatur_id, katalog_id, qty_donasi, deskripsi_kondisi, foto_barang) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt1->bind_param("siiiss", $id_donasi, $donatur_id, $katalog_id, $qty_donasi, $deskripsi, $fileName);
    
    if ($stmt1->execute()) {
        // 4. Insert ke Tabel Pengiriman (Logistik)
        $tipe_layanan = ($kurir == 'gosend' || $kurir == 'grab') ? 'instant' : 'reguler';
        $estimasi_ongkir = 15000 * $berat; // Simulasi tarif
        
        $stmt2 = $koneksi->prepare("INSERT INTO pengiriman (donasi_id, kurir, tipe_layanan, kota_asal, kota_tujuan, berat_kg, estimasi_ongkir, no_resi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("sssssids", $id_donasi, $kurir, $tipe_layanan, $kota_asal, $kota_tujuan, $berat, $estimasi_ongkir, $no_resi);
        $stmt2->execute();
        $stmt2->close();
        
        echo "<script>alert('Donasi Berhasil! No Resi Anda: $no_resi'); window.location='../index.php#lacak';</script>";
    } else {
        echo "<script>alert('Gagal memproses donasi.'); window.location='../index.php';</script>";
    }
    
    $stmt1->close();
}
?>