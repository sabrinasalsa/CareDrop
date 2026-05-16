<?php
ob_start(); ini_set('display_errors',0); error_reporting(0);
session_start(); require_once __DIR__.'/koneksi.php'; ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) { echo json_encode(['ok'=>false,'data',[]]); exit; }
$user_id = (int)$_SESSION['id'];

try {
    $koneksi->query(
        "CREATE TABLE IF NOT EXISTS berkas_legalitas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            yayasan_id INT NOT NULL,
            jenis VARCHAR(50) NOT NULL,
            nama_file VARCHAR(255) NOT NULL,
            keterangan TEXT,
            status ENUM('pending','verified','rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    );
    $stmt = $koneksi->prepare("SELECT jenis, nama_file, keterangan, status, created_at FROM berkas_legalitas WHERE yayasan_id=? ORDER BY jenis");
    $stmt->bind_param("i",$user_id); $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    $koneksi->close();
    echo json_encode(['ok'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
    echo json_encode(['ok'=>false,'data'=>[],'error'=>$e->getMessage()]);
}
