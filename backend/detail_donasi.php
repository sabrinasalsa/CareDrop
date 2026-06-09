<?php
/**
 * CareDrop – backend/detail_donasi.php
 * Endpoint JSON: detail donasi berdasarkan ID (PDO)
 */
session_start();
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) { json_error('Belum login', 401); }

$donasi_id = trim($_GET['id'] ?? '');
if (empty($donasi_id)) { json_error('ID tidak valid'); }

// Sanitasi ID (hanya karakter aman)
$donasi_id = preg_replace('/[^A-Za-z0-9\-]/', '', $donasi_id);

try {
    $stmt = $pdo->prepare(
        "SELECT
            d.id AS donasi_id, d.qty_donasi, d.status_donasi AS status,
            d.deskripsi_kondisi, d.foto_barang, d.created_at,
            COALESCE(k.nama_barang,'—')   AS nama_barang,
            COALESCE(k.kategori,'—')      AS kategori,
            COALESCE(u.nama_lengkap,'—')  AS nama_donatur,
            COALESCE(uy.nama_lengkap,'—') AS nama_yayasan,
            COALESCE(uy.alamat,'—')       AS alamat_yayasan,
            p.no_resi, p.kurir, p.kota_asal, p.kota_tujuan, p.estimasi_ongkir, p.tipe_layanan
         FROM donasi d
         LEFT JOIN katalog_kebutuhan k ON k.id = d.katalog_id
         LEFT JOIN users u  ON u.id  = d.donatur_id
         LEFT JOIN users uy ON uy.id = k.yayasan_id
         LEFT JOIN pengiriman p ON p.donasi_id = d.id
         WHERE d.id = ?
         LIMIT 1"
    );
    $stmt->execute([$donasi_id]);
    $row = $stmt->fetch();
    $pdo = null;

    if (!$row) { json_error('Donasi tidak ditemukan'); }
    echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error', 500);
}
