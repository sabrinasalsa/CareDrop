<?php
/**
 * CareDrop – backend/update_profil.php
 * Update profil user ke DB, kembalikan JSON
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

$user_id = (int) $_SESSION['id'];
$nama    = htmlspecialchars(trim($_POST['nama']    ?? ''));
$no_telp = htmlspecialchars(trim($_POST['no_telp'] ?? ''));
$alamat  = htmlspecialchars(trim($_POST['alamat']  ?? ''));

if (empty($nama)) {
    echo json_encode(['ok' => false, 'error' => 'Nama tidak boleh kosong']); exit;
}

try {
    $stmt = $koneksi->prepare(
        "UPDATE users SET nama_lengkap = ?, no_telp = ?, alamat = ? WHERE id = ?"
    );
    $stmt->bind_param("sssi", $nama, $no_telp, $alamat, $user_id);
    $stmt->execute();
    $stmt->close();

    // Update session
    $_SESSION['nama']    = $nama;
    $_SESSION['no_telp'] = $no_telp;
    $_SESSION['alamat']  = $alamat;

    $koneksi->close();
    echo json_encode(['ok' => true, 'nama' => $nama, 'no_telp' => $no_telp, 'alamat' => $alamat]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
