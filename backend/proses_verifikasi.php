<?php

session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Hanya admin ──
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    json_error('Akses ditolak', 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method tidak valid', 405); }


$user_id = (int)($_POST['user_id'] ?? 0);
$aksi    = trim($_POST['aksi'] ?? '');

// ── Validasi parameter ──
if ($user_id < 1) { json_error('ID user tidak valid'); }
if (!in_array($aksi, ['setuju', 'tolak'], true)) { json_error('Aksi tidak valid'); }

// Pastikan tidak bisa ubah status diri sendiri
if ($user_id === (int)$_SESSION['id']) { json_error('Tidak dapat mengubah status akun sendiri'); }

try {
    $status = ($aksi === 'setuju') ? 'verified' : 'rejected';

    $stmt = $pdo->prepare("UPDATE users SET status_verifikasi = ? WHERE id = ? AND role = 'penerima'");
    $stmt->execute([$status, $user_id]);

    $pdo = null;
    if ($stmt->rowCount() > 0)
        echo json_encode(['success' => true]);
    else
        json_error('Gagal memperbarui status. User tidak ditemukan atau bukan penerima.');

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error. Silakan coba lagi.', 500);
}
