<?php
ob_start(); ini_set('display_errors',0); error_reporting(0);
session_start(); require_once __DIR__.'/koneksi.php'; ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) { echo json_encode(['ok'=>false,'error'=>'Belum login']); exit; }

$user_id = (int)$_SESSION['id'];

if (empty($_FILES['avatar']['tmp_name']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok'=>false,'error'=>'File tidak valid']); exit;
}

$file = $_FILES['avatar'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
    echo json_encode(['ok'=>false,'error'=>'Format harus JPG/PNG/WEBP']); exit;
}
if ($file['size'] > 2*1024*1024) {
    echo json_encode(['ok'=>false,'error'=>'Ukuran maksimal 2MB']); exit;
}

$dir = dirname(__DIR__) . '/uploads/avatar/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

// Hapus avatar lama
$stmt = $koneksi->prepare("SELECT avatar FROM users WHERE id=?");
$stmt->bind_param("i",$user_id); $stmt->execute();
$old = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!empty($old['avatar']) && file_exists($dir.$old['avatar'])) {
    unlink($dir.$old['avatar']);
}

$fname = 'av_' . $user_id . '_' . time() . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $dir.$fname)) {
    echo json_encode(['ok'=>false,'error'=>'Gagal menyimpan file']); exit;
}

$stmt = $koneksi->prepare("UPDATE users SET avatar=? WHERE id=?");
$stmt->bind_param("si",$fname,$user_id); $stmt->execute(); $stmt->close();
$koneksi->close();

echo json_encode(['ok'=>true,'url'=>'uploads/avatar/'.$fname]);
