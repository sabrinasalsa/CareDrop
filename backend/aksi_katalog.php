<?php
ob_start(); ini_set('display_errors',0); error_reporting(0);
session_start(); require_once __DIR__.'/koneksi.php'; ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') { echo json_encode(['ok'=>false,'error'=>'Akses ditolak']); exit; }
$katalog_id = (int)($_POST['katalog_id'] ?? 0);
$aksi       = $_POST['aksi'] ?? '';
$user_id    = (int)$_SESSION['id'];
if ($katalog_id <= 0) { echo json_encode(['ok'=>false,'error'=>'ID tidak valid']); exit; }
try {
    $aktif = ($aksi === 'buka') ? 1 : 0;
    $stmt  = $koneksi->prepare("UPDATE katalog_kebutuhan SET aktif=? WHERE id=? AND yayasan_id=?");
    $stmt->bind_param("iii",$aktif,$katalog_id,$user_id);
    $stmt->execute(); $affected = $stmt->affected_rows; $stmt->close();
    $koneksi->close();
    if ($affected > 0) echo json_encode(['ok'=>true]);
    else echo json_encode(['ok'=>false,'error'=>'Item tidak ditemukan']);
} catch(Throwable $e) { echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
