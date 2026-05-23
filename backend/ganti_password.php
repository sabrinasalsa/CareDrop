<?php
ob_start(); ini_set('display_errors',0); error_reporting(0);
session_start(); require_once __DIR__.'/koneksi.php'; ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['ok'=>false,'error'=>'Belum login']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'error'=>'Method tidak valid']); exit;
}

$user_id  = (int)$_SESSION['id'];
$lama     = $_POST['password_lama']    ?? '';
$baru     = $_POST['password_baru']    ?? '';
$konfirm  = $_POST['password_konfirm'] ?? '';

if (empty($lama) || empty($baru)) {
    echo json_encode(['ok'=>false,'error'=>'Password lama dan baru wajib diisi']); exit;
}
if (strlen($baru) < 6) {
    echo json_encode(['ok'=>false,'error'=>'Password baru minimal 6 karakter']); exit;
}
if ($baru !== $konfirm) {
    echo json_encode(['ok'=>false,'error'=>'Konfirmasi password tidak cocok']); exit;
}

try {
    $stmt = $koneksi->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($lama, $row['password'])) {
        echo json_encode(['ok'=>false,'error'=>'Password lama tidak sesuai']); exit;
    }

    $hash = password_hash($baru, PASSWORD_DEFAULT);
    $upd  = $koneksi->prepare("UPDATE users SET password = ? WHERE id = ?");
    $upd->bind_param("si", $hash, $user_id);
    $upd->execute();
    $upd->close();
    $koneksi->close();

    echo json_encode(['ok'=>true,'msg'=>'Password berhasil diubah.']);
} catch(Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
