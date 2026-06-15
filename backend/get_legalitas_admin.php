<?php
session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// Hanya admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['ok' => false, 'data' => [], 'error' => 'Akses ditolak']); exit;
}

$yayasan_id = (int)($_GET['yayasan_id'] ?? 0);
if ($yayasan_id < 1) {
    echo json_encode(['ok' => false, 'data' => [], 'error' => 'ID tidak valid']); exit;
}

try {
    // Pastikan tabel ada (idempotent)
    $pdo->query(
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

    // Ambil info yayasan
    $uStmt = $pdo->prepare("SELECT nama_lengkap, email, no_telp FROM users WHERE id = ? AND role = 'penerima'");
    $uStmt->execute([$yayasan_id]);
    $yayasan = $uStmt->fetch(PDO::FETCH_ASSOC);

    if (!$yayasan) {
        echo json_encode(['ok' => false, 'data' => [], 'error' => 'Yayasan tidak ditemukan']); exit;
    }

    // Ambil berkas legalitas
    $stmt = $pdo->prepare(
        "SELECT jenis, nama_file, keterangan, status, created_at
         FROM berkas_legalitas WHERE yayasan_id = ? ORDER BY jenis"
    );
    $stmt->execute([$yayasan_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $pdo = null;

    echo json_encode([
        'ok'      => true,
        'yayasan' => $yayasan,
        'data'    => $rows
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'data' => [], 'error' => 'Server error']);
}
