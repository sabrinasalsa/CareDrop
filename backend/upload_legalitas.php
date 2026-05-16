<?php
ob_start(); ini_set('display_errors',0); error_reporting(0);
session_start(); require_once __DIR__.'/koneksi.php'; ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    echo json_encode(['ok'=>false,'error'=>'Akses ditolak']); exit;
}
$user_id  = (int)$_SESSION['id'];
$jenis    = htmlspecialchars($_POST['jenis'] ?? ''); // akta | sk_kemenkumham | npwp | foto_gedung | lainnya
$keterangan = htmlspecialchars(trim($_POST['keterangan'] ?? ''));

$allowedJenis = ['akta','sk_kemenkumham','npwp','foto_gedung','lainnya'];
if (!in_array($jenis, $allowedJenis)) { echo json_encode(['ok'=>false,'error'=>'Jenis dokumen tidak valid']); exit; }

if (empty($_FILES['berkas']['tmp_name']) || $_FILES['berkas']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok'=>false,'error'=>'File tidak valid']); exit;
}

$file = $_FILES['berkas'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['pdf','jpg','jpeg','png'])) {
    echo json_encode(['ok'=>false,'error'=>'Format harus PDF, JPG, atau PNG']); exit;
}
if ($file['size'] > 5*1024*1024) {
    echo json_encode(['ok'=>false,'error'=>'Ukuran maksimal 5MB']); exit;
}

$dir = dirname(__DIR__) . '/uploads/legalitas/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

$fname = 'leg_' . $user_id . '_' . $jenis . '_' . time() . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $dir.$fname)) {
    echo json_encode(['ok'=>false,'error'=>'Gagal menyimpan file']); exit;
}

try {
    // Simpan ke tabel berkas_legalitas (buat jika belum ada)
    $koneksi->query(
        "CREATE TABLE IF NOT EXISTS berkas_legalitas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            yayasan_id INT NOT NULL,
            jenis VARCHAR(50) NOT NULL,
            nama_file VARCHAR(255) NOT NULL,
            keterangan TEXT,
            status ENUM('pending','verified','rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (yayasan_id) REFERENCES users(id) ON DELETE CASCADE
        )"
    );
    // Hapus file lama jenis yang sama
    $old = $koneksi->prepare("SELECT nama_file FROM berkas_legalitas WHERE yayasan_id=? AND jenis=?");
    $old->bind_param("is",$user_id,$jenis); $old->execute();
    $oldRow = $old->get_result()->fetch_assoc(); $old->close();
    if ($oldRow && file_exists($dir.$oldRow['nama_file'])) unlink($dir.$oldRow['nama_file']);

    $del = $koneksi->prepare("DELETE FROM berkas_legalitas WHERE yayasan_id=? AND jenis=?");
    $del->bind_param("is",$user_id,$jenis); $del->execute(); $del->close();

    $ins = $koneksi->prepare("INSERT INTO berkas_legalitas (yayasan_id, jenis, nama_file, keterangan) VALUES (?,?,?,?)");
    $ins->bind_param("isss",$user_id,$jenis,$fname,$keterangan); $ins->execute(); $ins->close();
    $koneksi->close();
    echo json_encode(['ok'=>true,'file'=>$fname,'url'=>'uploads/legalitas/'.$fname]);
} catch(Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
