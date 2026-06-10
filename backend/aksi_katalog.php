<?php

session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Autentikasi & otorisasi ──
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    json_error('Akses ditolak', 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method tidak valid', 405); }


$katalog_id = (int)($_POST['katalog_id'] ?? 0);
$aksi       = trim($_POST['aksi'] ?? '');
$user_id    = (int)$_SESSION['id'];

if ($katalog_id <= 0) { json_error('ID katalog tidak valid'); }
if (!in_array($aksi, ['buka', 'tutup'], true)) { json_error('Aksi tidak valid'); }

try {
    $aktif = ($aksi === 'buka') ? 1 : 0;
    $stmt  = $pdo->prepare("UPDATE katalog_kebutuhan SET aktif = ?, status_aktif = ? WHERE id = ? AND yayasan_id = ?");
    $stmt->execute([$aktif, $aktif, $katalog_id, $user_id]);

    $pdo = null;
    if ($stmt->rowCount() > 0)
        echo json_encode(['ok' => true]);
    else
        json_error('Item tidak ditemukan atau bukan milik yayasan ini');

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error. Silakan coba lagi.', 500);
}
