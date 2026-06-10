<?php

session_start();
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['ok' => false, 'data' => []]); exit;
}
$user_id = (int)$_SESSION['id'];

try {
    // Pastikan tabel ada (idempotent)
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS berkas_legalitas (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            yayasan_id  INT          NOT NULL,
            jenis       VARCHAR(80)  NOT NULL,
            nama_file   VARCHAR(255) NOT NULL,
            keterangan  TEXT         DEFAULT NULL,
            status      ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
            created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (yayasan_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $stmt = $pdo->prepare(
        "SELECT jenis, nama_file, keterangan, status, created_at
         FROM berkas_legalitas WHERE yayasan_id = ? ORDER BY jenis"
    );
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll();
    $pdo  = null;

    echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    $pdo = null;
    echo json_encode(['ok' => false, 'data' => [], 'error' => 'Server error']);
}
