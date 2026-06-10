<?php

session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Autentikasi & otorisasi ──
if (!isset($_SESSION['id'])) {
    json_error('Belum login', 401);
}
if ($_SESSION['role'] !== 'donatur') {
    json_error('Hanya donatur yang dapat mengajukan tawaran', 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method tidak valid', 405);
}


$donatur_id = (int)$_SESSION['id'];

// ── Verifikasi user benar-benar ada di DB ──
try {
    $vUser = $pdo->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
    $vUser->execute([$donatur_id]);
    if (!$vUser->fetch()) {
        json_error('Sesi tidak valid. Silakan logout lalu login kembali.', 401);
    }

    // ── Validasi input ──
    $katalog_id = (int)($_POST['katalog_id'] ?? 0);
    $qty        = (int)($_POST['qty']        ?? 0);
    $deskripsi  = htmlspecialchars(trim($_POST['deskripsi'] ?? ''), ENT_QUOTES, 'UTF-8');

    if ($katalog_id <= 0) { json_error('ID katalog tidak valid'); }
    if ($qty < 1)         { json_error('Jumlah donasi minimal 1'); }
    if (strlen($deskripsi) > 1000) { json_error('Deskripsi terlalu panjang (maks 1000 karakter)'); }

    // ── Verifikasi katalog aktif & belum terpenuhi ──
    $cek = $pdo->prepare(
        "SELECT id, nama_barang, target_butuh, jumlah_terkumpul
         FROM katalog_kebutuhan
         WHERE id = ? AND (aktif = 1 OR status_aktif = 1) AND jumlah_terkumpul < target_butuh"
    );
    $cek->execute([$katalog_id]);
    $katalog = $cek->fetch();

    if (!$katalog) {
        json_error('Katalog tidak ditemukan atau kebutuhan sudah terpenuhi');
    }

    // ── Generate ID unik donasi ──
    $id_donasi = 'CDR-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

    // ── Upload foto barang (opsional) ──
    $foto = null;
    if (!empty($_FILES['foto_barang']['tmp_name']) && $_FILES['foto_barang']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_barang'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            json_error('Format foto tidak valid (gunakan JPG/PNG/WEBP)');
        }
        if ($file['size'] > 3 * 1024 * 1024) {
            json_error('Ukuran foto maksimal 3MB');
        }
        // Validasi MIME type nyata
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            json_error('File bukan gambar yang valid');
        }

        $dir = dirname(__DIR__) . '/uploads/donasi/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $foto = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $dir . $foto);
    }

    // ── Insert donasi ──
    $stmt = $pdo->prepare(
        "INSERT INTO donasi (id, donatur_id, katalog_id, qty_donasi, deskripsi_kondisi, foto_barang, status_donasi)
         VALUES (?, ?, ?, ?, ?, ?, 'menunggu')"
    );
    $stmt->execute([$id_donasi, $donatur_id, $katalog_id, $qty, $deskripsi, $foto]);

    $pdo = null;
    echo json_encode([
        'ok'      => true,
        'id'      => $id_donasi,
        'barang'  => $katalog['nama_barang'],
        'message' => 'Tawaran donasi berhasil diajukan! Silakan tunggu persetujuan dari yayasan.'
    ]);

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error. Silakan coba lagi.', 500);
}
