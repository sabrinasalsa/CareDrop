<?php

session_start();
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->prepare(
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
           AND (k.aktif = 1 OR k.status_aktif = 1)
         ORDER BY FIELD(k.urgensi,'high','med','low'), k.id DESC
         LIMIT 50"
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();
    $pdo  = null;
    echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (PDOException $e) {
    $pdo = null;
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}
