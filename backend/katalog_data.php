<?php
/**
 * CareDrop – backend/katalog_data.php
 * Endpoint JSON: ambil semua katalog kebutuhan dari DB
 */
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
session_start();
require_once __DIR__ . '/koneksi.php';
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $koneksi->prepare(
        "SELECT
            k.id,
            k.nama_barang,
            k.kategori,
            k.urgensi,
            k.target_butuh,
            k.jumlah_terkumpul,
            u.nama_lengkap AS nama_yayasan,
            u.alamat       AS kota_yayasan,
            u.id           AS yayasan_id
         FROM katalog_kebutuhan k
         JOIN users u ON u.id = k.yayasan_id
         WHERE k.jumlah_terkumpul < k.target_butuh
         ORDER BY FIELD(k.urgensi,'high','med','low'), k.id DESC
         LIMIT 50"
    );
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $koneksi->close();
    echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
