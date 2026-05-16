<?php
/**
 * CareDrop – backend/konfirm_terima.php
 * Penerima mengkonfirmasi terima donasi → update status_donasi = 'selesai'
 */
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
session_start();
require_once __DIR__ . '/koneksi.php';
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    echo json_encode(['ok' => false, 'error' => 'Akses ditolak']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method salah']); exit;
}

$donasi_id  = htmlspecialchars(trim($_POST['donasi_id'] ?? ''));
$yayasan_id = (int) $_SESSION['id'];

if (empty($donasi_id)) {
    echo json_encode(['ok' => false, 'error' => 'ID donasi tidak valid']); exit;
}

try {
    // Pastikan donasi ini memang milik yayasan yang login
    $stmt = $koneksi->prepare(
        "UPDATE donasi d
         JOIN katalog_kebutuhan k ON k.id = d.katalog_id
         SET d.status_donasi = 'selesai'
         WHERE d.id = ? AND k.yayasan_id = ? AND d.status_donasi = 'dikirim'"
    );
    $stmt->bind_param("si", $donasi_id, $yayasan_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected > 0) {
        // Update jumlah_terkumpul di katalog
        $upd = $koneksi->prepare(
            "UPDATE katalog_kebutuhan k
             JOIN donasi d ON d.katalog_id = k.id
             SET k.jumlah_terkumpul = k.jumlah_terkumpul + d.qty_donasi
             WHERE d.id = ?"
        );
        $upd->bind_param("s", $donasi_id);
        $upd->execute();
        $upd->close();

        $koneksi->close();
        echo json_encode(['ok' => true, 'message' => 'Donasi berhasil dikonfirmasi!']);
    } else {
        $koneksi->close();
        echo json_encode(['ok' => false, 'error' => 'Donasi tidak ditemukan atau bukan milik yayasan ini']);
    }

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
