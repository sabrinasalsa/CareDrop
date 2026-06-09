<?php
/**
 * CareDrop – backend/konfirm_terima.php
 * Penerima mengkonfirmasi terima donasi → update status_donasi = 'selesai'
 * PDO, CSRF, role penerima
 */
session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Autentikasi & otorisasi ──
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    json_error('Akses ditolak', 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method tidak valid', 405); }


$donasi_id  = trim($_POST['donasi_id'] ?? '');
$yayasan_id = (int)$_SESSION['id'];

if (empty($donasi_id)) { json_error('ID donasi tidak valid'); }

try {
    // Pastikan donasi ini memang milik yayasan yang login & statusnya 'dikirim'
    $stmt = $pdo->prepare(
        "UPDATE donasi d
         JOIN katalog_kebutuhan k ON k.id = d.katalog_id
         SET d.status_donasi = 'selesai'
         WHERE d.id = ? AND k.yayasan_id = ? AND d.status_donasi = 'dikirim'"
    );
    $stmt->execute([$donasi_id, $yayasan_id]);

    if ($stmt->rowCount() > 0) {
        // Tambah jumlah_terkumpul di katalog
        $upd = $pdo->prepare(
            "UPDATE katalog_kebutuhan k
             JOIN donasi d ON d.katalog_id = k.id
             SET k.jumlah_terkumpul = k.jumlah_terkumpul + d.qty_donasi
             WHERE d.id = ?"
        );
        $upd->execute([$donasi_id]);

        $pdo = null;
        echo json_encode(['ok' => true, 'message' => 'Donasi berhasil dikonfirmasi!']);
    } else {
        $pdo = null;
        json_error('Donasi tidak ditemukan atau bukan milik yayasan ini');
    }

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error. Silakan coba lagi.', 500);
}
