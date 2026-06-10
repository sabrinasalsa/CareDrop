<?php

session_start();
require_once __DIR__ . '/koneksi.php';

// ── Hanya terima POST ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php'); exit;
}

// ── Wajib login (tidak ada fallback ke ID dummy) ──
if (!isset($_SESSION['id'])) {
    echo "<script>alert('Anda harus login terlebih dahulu!'); window.location.href='../login.php';</script>"; exit;
}


// ── Hanya role donatur yang bisa donasi ──
if (($_SESSION['role'] ?? '') !== 'donatur') {
    echo "<script>alert('Hanya donatur yang dapat mengajukan donasi.'); window.location.href='../index.php';</script>"; exit;
}

$donatur_id = (int)$_SESSION['id'];

// ── Ambil & validasi input ──
$katalog_id  = (int)($_POST['katalog_id'] ?? 0);
$qty_donasi  = (int)($_POST['qty'] ?? 0);
$deskripsi   = htmlspecialchars(trim($_POST['deskripsi'] ?? ''), ENT_QUOTES, 'UTF-8');

// Data Logistik
$allowed_kurir = ['jne', 'jnt', 'sicepat', 'pos', 'tiki', 'anteraja', 'gosend', 'grab', 'mandiri'];
$kurir         = strtolower(trim($_POST['kurir'] ?? ''));
$kota_asal     = htmlspecialchars(trim($_POST['kota_asal'] ?? ''), ENT_QUOTES, 'UTF-8');
$kota_tujuan   = htmlspecialchars(trim($_POST['kota_tujuan'] ?? ''), ENT_QUOTES, 'UTF-8');
$berat         = (int)($_POST['berat'] ?? 0);

// Validasi field wajib
if ($katalog_id <= 0 || $qty_donasi < 1) {
    echo "<script>alert('Data katalog dan jumlah wajib diisi dengan benar!'); window.location.href='../index.php';</script>"; exit;
}
if (!in_array($kurir, $allowed_kurir, true)) {
    echo "<script>alert('Ekspedisi tidak valid!'); window.location.href='../index.php';</script>"; exit;
}
if (empty($kota_asal) || empty($kota_tujuan)) {
    echo "<script>alert('Kota asal dan tujuan wajib diisi!'); window.location.href='../index.php';</script>"; exit;
}
if ($berat < 1) {
    echo "<script>alert('Berat paket tidak valid!'); window.location.href='../index.php';</script>"; exit;
}

try {
    // ── Verifikasi katalog aktif dan masih butuh donasi ──
    $cek = $pdo->prepare(
        "SELECT id, nama_barang FROM katalog_kebutuhan
         WHERE id = ? AND (aktif = 1 OR status_aktif = 1) AND jumlah_terkumpul < target_butuh"
    );
    $cek->execute([$katalog_id]);
    $katalog = $cek->fetch();

    if (!$katalog) {
        echo "<script>alert('Katalog tidak ditemukan atau kebutuhan sudah terpenuhi.'); window.location.href='../index.php';</script>"; exit;
    }

    // ── Generate ID Donasi & Resi otomatis ──
    $id_donasi = 'CDR-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $no_resi   = 'CD' . strtoupper(substr($kurir, 0, 3)) . strtoupper(bin2hex(random_bytes(2))) . 'ID';

    // ── Handle Upload Foto Barang ──
    $fileName = null;
    if (!empty($_FILES['foto_barang']['tmp_name']) && $_FILES['foto_barang']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['foto_barang'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed, true)) {
            echo "<script>alert('Format foto tidak diizinkan. Gunakan JPG/PNG/WEBP.'); window.location.href='../index.php';</script>"; exit;
        }
        if ($file['size'] > 3 * 1024 * 1024) {
            echo "<script>alert('Ukuran foto maksimal 3MB!'); window.location.href='../index.php';</script>"; exit;
        }
        // Validasi MIME type nyata
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMime, true)) {
            echo "<script>alert('File bukan gambar yang valid!'); window.location.href='../index.php';</script>"; exit;
        }

        $targetDir = dirname(__DIR__) . '/uploads/donasi/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        $fileName   = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $targetDir . $fileName);
    }

    // ── Insert ke Tabel Donasi ──
    $stmtD = $pdo->prepare(
        "INSERT INTO donasi (id, donatur_id, katalog_id, qty_donasi, deskripsi_kondisi, foto_barang, status_donasi)
         VALUES (?, ?, ?, ?, ?, ?, 'menunggu')"
    );
    $stmtD->execute([$id_donasi, $donatur_id, $katalog_id, $qty_donasi, $deskripsi, $fileName]);

    // ── Insert ke Tabel Pengiriman ──
    $tipe_layanan    = in_array($kurir, ['gosend', 'grab'], true) ? 'instant' : 'reguler';
    $estimasi_ongkir = 15000 * $berat;

    $stmtP = $pdo->prepare(
        "INSERT INTO pengiriman (donasi_id, kurir, tipe_layanan, kota_asal, kota_tujuan, berat_kg, estimasi_ongkir, no_resi)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmtP->execute([$id_donasi, $kurir, $tipe_layanan, $kota_asal, $kota_tujuan, $berat, $estimasi_ongkir, $no_resi]);

    $pdo = null;
    echo "<script>alert('Donasi Berhasil! No Resi Anda: $no_resi'); window.location='../index.php#lacak';</script>";

} catch (PDOException $e) {
    $pdo = null;
    echo "<script>alert('Gagal memproses donasi. Silakan coba lagi.'); window.location='../index.php';</script>";
}
?>