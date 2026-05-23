<?php
ob_start(); ini_set('display_errors',0); error_reporting(0);
session_start(); require_once __DIR__.'/koneksi.php'; ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    echo json_encode(['ok'=>false,'error'=>'Akses ditolak']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok'=>false,'error'=>'Method tidak valid']); exit;
}

$yayasan_id  = (int)$_SESSION['id'];
$id          = (int)($_POST['id'] ?? 0);
$nama_barang = htmlspecialchars(trim($_POST['nama_barang'] ?? ''));
$kategori    = $_POST['kategori']    ?? 'pakaian';
$urgensi     = $_POST['urgensi']     ?? 'med';
$target      = (int)($_POST['target_butuh'] ?? 0);
$deskripsi   = htmlspecialchars(trim($_POST['deskripsi'] ?? ''));

$allowKat = ['pakaian','buku','elektronik','perabot','lainnya'];
$allowUrg = ['high','med','low'];
if (!in_array($kategori, $allowKat)) $kategori = 'lainnya';
if (!in_array($urgensi, $allowUrg))  $urgensi  = 'med';

if ($id < 1 || empty($nama_barang) || $target < 1) {
    echo json_encode(['ok'=>false,'error'=>'Data tidak lengkap']); exit;
}

try {
    $stmt = $koneksi->prepare(
        "UPDATE katalog_kebutuhan
         SET nama_barang = ?, kategori = ?, urgensi = ?, target_butuh = ?, deskripsi = ?
         WHERE id = ? AND yayasan_id = ?"
    );
    $stmt->bind_param("sssisii", $nama_barang, $kategori, $urgensi, $target, $deskripsi, $id, $yayasan_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    $koneksi->close();

    if ($affected >= 0) // 0 jika tidak ada perubahan tapi query sukses
        echo json_encode(['ok'=>true,'msg'=>'Kebutuhan berhasil diperbarui.']);
    else
        echo json_encode(['ok'=>false,'error'=>'Item tidak ditemukan atau bukan milik yayasan ini.']);
} catch(Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
