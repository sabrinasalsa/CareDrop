<?php
ob_start(); ini_set('display_errors',0); error_reporting(0);
session_start(); require_once __DIR__.'/koneksi.php'; ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) { echo json_encode(['ok'=>false,'error'=>'Belum login']); exit; }

$donasi_id = htmlspecialchars(trim($_GET['id'] ?? ''));
if (empty($donasi_id)) { echo json_encode(['ok'=>false,'error'=>'ID tidak valid']); exit; }

try {
    $stmt = $koneksi->prepare(
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
    $stmt->bind_param("s", $donasi_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $koneksi->close();

    if (!$row) { echo json_encode(['ok'=>false,'error'=>'Donasi tidak ditemukan']); exit; }
    echo json_encode(['ok'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
