<?php
/**
 * CareDrop – backend/proses_verifikasi.php
 * AJAX endpoint untuk Admin: setujui / tolak akun Penerima
 */
session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

$user_id = (int) ($_POST['user_id'] ?? 0);
$aksi    = $_POST['aksi'] ?? '';

if (!$user_id || !in_array($aksi, ['setuju','tolak'])) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak valid']);
    exit;
}

$status = ($aksi === 'setuju') ? 'verified' : 'rejected';
$stmt   = $koneksi->prepare("UPDATE users SET status_verifikasi = ? WHERE id = ? AND role = 'penerima'");
$stmt->bind_param("si", $status, $user_id);
$ok = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
$koneksi->close();

if ($ok && $affected > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status']);
}
