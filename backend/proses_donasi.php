<?php
/**
 * CareDrop – backend/proses_donasi.php
 * Simpan donasi ke DB dengan kolom status_donasi
 */
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method tidak valid']); exit;
}

$donatur_id  = (int) $_SESSION['id'];
$katalog_id  = (int) ($_POST['katalog_id']  ?? 0);
$qty         = (int) ($_POST['qty']          ?? 0);
$deskripsi   = htmlspecialchars(trim($_POST['deskripsi']   ?? ''));
$kurir       = htmlspecialchars(trim($_POST['kurir']        ?? ''));
$kota_asal   = htmlspecialchars(trim($_POST['kota_asal']   ?? ''));
$kota_tujuan = htmlspecialchars(trim($_POST['kota_tujuan'] ?? ''));
$berat       = max(1, (float)($_POST['berat'] ?? 1));

if ($katalog_id <= 0 || $qty <= 0 || empty($kurir)) {
    echo json_encode(['ok' => false, 'error' => 'Data tidak lengkap']); exit;
}

try {
    // Buat ID unik
    $id_donasi = 'CDR-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $no_resi   = 'CD' . strtoupper(substr($kurir, 0, 3)) . rand(1000, 9999) . 'ID';
    $status    = 'menunggu';

    // Upload foto jika ada
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

    // Insert donasi — pakai kolom status_donasi
    $stmt = $koneksi->prepare(
        "INSERT INTO donasi (id, donatur_id, katalog_id, qty_donasi, deskripsi_kondisi, foto_barang, status_donasi)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("siiiiss", $id_donasi, $donatur_id, $katalog_id, $qty, $deskripsi, $foto, $status);
    $stmt->execute();
    $stmt->close();

    // Insert pengiriman
    $tipe_layanan    = in_array($kurir, ['gosend','grab']) ? 'instant' : 'reguler';
    $estimasi_ongkir = round(15000 * $berat);

    $stmt2 = $koneksi->prepare(
        "INSERT INTO pengiriman (donasi_id, kurir, tipe_layanan, kota_asal, kota_tujuan, berat_kg, estimasi_ongkir, no_resi)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt2->bind_param("sssssids", $id_donasi, $kurir, $tipe_layanan, $kota_asal, $kota_tujuan, $berat, $estimasi_ongkir, $no_resi);
    $stmt2->execute();
    $stmt2->close();

    $koneksi->close();
    echo json_encode(['ok' => true, 'resi' => $no_resi, 'id' => $id_donasi]);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
