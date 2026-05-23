<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
session_start();
require_once __DIR__ . '/koneksi.php';
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['ok' => false, 'error' => 'Belum login']); exit;
}
if ($_SESSION['role'] !== 'donatur') {
    echo json_encode(['ok' => false, 'error' => 'Hanya donatur yang dapat mengajukan tawaran']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method tidak valid']); exit;
}

$donatur_id = (int) $_SESSION['id'];

// Validasi: user ID harus benar-benar ada di database
$_vUser = $koneksi->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
$_vUser->bind_param("i", $donatur_id);
$_vUser->execute();
$_uRow = $_vUser->get_result()->fetch_assoc();
$_vUser->close();
if (!$_uRow) {
    echo json_encode(['ok' => false, 'error' => 'Sesi tidak valid. Silakan logout lalu login kembali.']); exit;
}
$katalog_id  = (int) ($_POST['katalog_id'] ?? 0);
$qty         = (int) ($_POST['qty']        ?? 0);
$deskripsi   = htmlspecialchars(trim($_POST['deskripsi'] ?? ''));

if ($katalog_id <= 0 || $qty < 1) {
    echo json_encode(['ok' => false, 'error' => 'Data tidak lengkap: katalog dan jumlah wajib diisi']); exit;
}

try {
    // Verifikasi katalog masih aktif dan belum terpenuhi
    $cek = $koneksi->prepare(
        "SELECT id, nama_barang, target_butuh, jumlah_terkumpul
         FROM katalog_kebutuhan
         WHERE id = ? AND (aktif = 1 OR status_aktif = 1) AND jumlah_terkumpul < target_butuh"
    );
    $cek->bind_param("i", $katalog_id);
    $cek->execute();
    $katalog = $cek->get_result()->fetch_assoc();
    $cek->close();

    if (!$katalog) {
        echo json_encode(['ok' => false, 'error' => 'Katalog tidak ditemukan atau kebutuhan sudah terpenuhi']); exit;
    }

    // Buat ID unik donasi
    $id_donasi = 'CDR-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    // Upload foto barang jika ada
    $foto = null;
    if (!empty($_FILES['foto_barang']['tmp_name']) && $_FILES['foto_barang']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_barang']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $dir = dirname(__DIR__) . '/uploads/donasi/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $foto = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['foto_barang']['tmp_name'], $dir . $foto);
        }
    }

    // Insert donasi — status menunggu, belum ada info pengiriman
    $stmt = $koneksi->prepare(
        "INSERT INTO donasi (id, donatur_id, katalog_id, qty_donasi, deskripsi_kondisi, foto_barang, status_donasi)
         VALUES (?, ?, ?, ?, ?, ?, 'menunggu')"
    );
    $stmt->bind_param("siiiss", $id_donasi, $donatur_id, $katalog_id, $qty, $deskripsi, $foto);
    $stmt->execute();
    $stmt->close();

    $koneksi->close();
    echo json_encode([
        'ok'      => true,
        'id'      => $id_donasi,
        'barang'  => $katalog['nama_barang'],
        'message' => 'Tawaran donasi berhasil diajukan! Silakan tunggu persetujuan dari yayasan.'
    ]);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
