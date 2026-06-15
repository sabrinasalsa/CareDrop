<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// Hanya admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['ok' => false, 'error' => 'Akses ditolak']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method tidak valid']); exit;
}

$yayasan_id = (int)($_POST['yayasan_id'] ?? 0);
$jenis      = trim($_POST['jenis'] ?? '');
$aksi       = trim($_POST['aksi'] ?? ''); // 'terima' | 'tolak'

if ($yayasan_id < 1 || $jenis === '') {
    echo json_encode(['ok' => false, 'error' => 'Parameter tidak valid']); exit;
}
if (!in_array($aksi, ['terima', 'tolak'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Aksi tidak valid']); exit;
}

$status = ($aksi === 'terima') ? 'verified' : 'rejected';

try {
    $stmt = $pdo->prepare(
        "UPDATE berkas_legalitas SET status = ? WHERE yayasan_id = ? AND jenis = ?"
    );
    $stmt->execute([$status, $yayasan_id, $jenis]);
    $affected = $stmt->rowCount();
    $pdo = null;

    if ($affected > 0) {
        echo json_encode(['ok' => true, 'status' => $status]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Dokumen tidak ditemukan']);
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}
