<?php
/**
 * CareDrop – backend/upload_legalitas.php
 * Upload berkas legalitas yayasan: PDO, CSRF, validasi MIME type nyata
 */
session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Autentikasi & otorisasi ──
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    json_error('Akses ditolak', 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method tidak valid', 405); }


$user_id    = (int)$_SESSION['id'];
$jenis      = trim($_POST['jenis']      ?? '');
$keterangan = htmlspecialchars(trim($_POST['keterangan'] ?? ''), ENT_QUOTES, 'UTF-8');

// ── Whitelist jenis dokumen ──
$allowedJenis = ['akta', 'sk_kemenkumham', 'npwp', 'foto_gedung', 'lainnya'];
if (!in_array($jenis, $allowedJenis, true)) {
    json_error('Jenis dokumen tidak valid');
}

// ── Validasi file ──
if (empty($_FILES['berkas']['tmp_name']) || $_FILES['berkas']['error'] !== UPLOAD_ERR_OK) {
    json_error('File tidak valid atau tidak ada');
}

$file = $_FILES['berkas'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Validasi ekstensi
if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
    json_error('Format harus PDF, JPG, atau PNG');
}

// Validasi ukuran (maks 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    json_error('Ukuran maksimal 5MB');
}

// ── Validasi MIME type nyata ──
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowedMime = ['application/pdf', 'image/jpeg', 'image/png'];
if (!in_array($mimeType, $allowedMime, true)) {
    json_error('File bukan PDF atau gambar yang valid');
}

$dir = dirname(__DIR__) . '/uploads/legalitas/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$fname = 'leg_' . $user_id . '_' . $jenis . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $dir . $fname)) {
    json_error('Gagal menyimpan file');
}

try {
    // Pastikan tabel ada
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

    // Hapus file lama jenis yang sama
    $old = $pdo->prepare("SELECT nama_file FROM berkas_legalitas WHERE yayasan_id = ? AND jenis = ?");
    $old->execute([$user_id, $jenis]);
    $oldRow = $old->fetch();
    if ($oldRow && file_exists($dir . $oldRow['nama_file'])) {
        unlink($dir . $oldRow['nama_file']);
    }

    $pdo->prepare("DELETE FROM berkas_legalitas WHERE yayasan_id = ? AND jenis = ?")
        ->execute([$user_id, $jenis]);

    $pdo->prepare("INSERT INTO berkas_legalitas (yayasan_id, jenis, nama_file, keterangan) VALUES (?, ?, ?, ?)")
        ->execute([$user_id, $jenis, $fname, $keterangan]);

    $pdo = null;
    echo json_encode(['ok' => true, 'file' => $fname, 'url' => 'uploads/legalitas/' . $fname]);

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error. Silakan coba lagi.', 500);
}
