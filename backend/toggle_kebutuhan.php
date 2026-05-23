<?php
ob_start(); ini_set('display_errors',0); error_reporting(0);
session_start(); require_once __DIR__.'/koneksi.php'; ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    echo json_encode(['ok'=>false,'error'=>'Akses ditolak']); exit;
}

$yayasan_id = (int)$_SESSION['id'];
$id         = (int)($_POST['id'] ?? 0);

if ($id < 1) {
    echo json_encode(['ok'=>false,'error'=>'ID tidak valid']); exit;
}

try {
    // Toggle nilai aktif (0→1 atau 1→0), juga sync status_aktif
    $stmt = $koneksi->prepare(
        "UPDATE katalog_kebutuhan
         SET aktif = IF(aktif=1,0,1), status_aktif = IF(status_aktif=1,0,1)
         WHERE id = ? AND yayasan_id = ?"
    );
    $stmt->bind_param("ii", $id, $yayasan_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    // Ambil status terbaru
    $chk = $koneksi->prepare("SELECT aktif FROM katalog_kebutuhan WHERE id = ?");
    $chk->bind_param("i", $id);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();
    $koneksi->close();

    if ($affected > 0) {
        $statusBaru = (bool)($row['aktif'] ?? false);
        echo json_encode([
            'ok'     => true,
            'aktif'  => $statusBaru,
            'msg'    => $statusBaru ? 'Daftar kebutuhan dibuka kembali.' : 'Daftar kebutuhan ditutup.'
        ]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Item tidak ditemukan atau bukan milik yayasan ini.']);
    }
} catch(Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
