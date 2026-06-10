<?php

session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) { json_error('Belum login', 401); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method tidak valid', 405); }

$user_id = (int)$_SESSION['id'];

// ── Validasi file ──
if (empty($_FILES['avatar']['tmp_name']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    json_error('File tidak valid atau tidak ada');
}

$file = $_FILES['avatar'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Validasi ekstensi
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
    json_error('Format harus JPG/PNG/WEBP');
}

// Validasi ukuran (maks 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    json_error('Ukuran maksimal 2MB');
}

// ── Validasi MIME type nyata (bukan hanya ekstensi) ──
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
$allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mimeType, $allowedMime, true)) {
    json_error('File bukan gambar yang valid');
}

$dir = dirname(__DIR__) . '/uploads/avatars/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

try {
    // Hapus avatar lama
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $old = $stmt->fetch();
    if (!empty($old['avatar']) && file_exists($dir . $old['avatar'])) {
        unlink($dir . $old['avatar']);
    }

    // Simpan file baru dengan nama yang tidak bisa ditebak
    $fname = 'av_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $fname)) {
        json_error('Gagal menyimpan file');
    }

    // Update DB
    $upd = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $upd->execute([$fname, $user_id]);

    // Update session
    $_SESSION['avatar'] = $fname;

    $pdo = null;
    echo json_encode(['ok' => true, 'url' => 'uploads/avatars/' . $fname]);

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error. Silakan coba lagi.', 500);
}
