<?php

session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Autentikasi & otorisasi ──
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    json_error('Akses ditolak', 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method tidak valid', 405); }


$yayasan_id = (int)$_SESSION['id'];
$id         = (int)($_POST['id'] ?? 0);

if ($id < 1) { json_error('ID tidak valid'); }

try {
    // Toggle nilai aktif & sync status_aktif
    $stmt = $pdo->prepare(
        "UPDATE katalog_kebutuhan
         SET aktif = IF(aktif=1,0,1), status_aktif = IF(status_aktif=1,0,1)
         WHERE id = ? AND yayasan_id = ?"
    );
    $stmt->execute([$id, $yayasan_id]);

    if ($stmt->rowCount() > 0) {
        // Ambil status terbaru
        $chk = $pdo->prepare("SELECT aktif FROM katalog_kebutuhan WHERE id = ?");
        $chk->execute([$id]);
        $row = $chk->fetch();
        $pdo = null;

        $statusBaru = (bool)($row['aktif'] ?? false);
        echo json_encode([
            'ok'    => true,
            'aktif' => $statusBaru,
            'msg'   => $statusBaru ? 'Daftar kebutuhan dibuka kembali.' : 'Daftar kebutuhan ditutup.',
        ]);
    } else {
        $pdo = null;
        json_error('Item tidak ditemukan atau bukan milik yayasan ini.');
    }

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error. Silakan coba lagi.', 500);
}
