<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php'); exit;
}

$id   = htmlspecialchars(trim($_GET['id']   ?? ''));
$aksi = htmlspecialchars(trim($_GET['aksi'] ?? ''));

if (empty($id)) { header('Location: index.php?err=ID+tidak+valid'); exit; }

try {
    $statusMap = [
        'proses'  => 'diproses',
        'kirim'   => 'dikirim',
        'selesai' => 'selesai',
        'batal'   => 'dibatalkan',
    ];

    if (!isset($statusMap[$aksi])) {
        header('Location: index.php?err=Aksi+tidak+valid'); exit;
    }

    $newStatus = $statusMap[$aksi];
    $stmt = $koneksi->prepare("UPDATE donasi SET status_donasi=? WHERE id=?");
    $stmt->bind_param("ss", $newStatus, $id);
    $stmt->execute();
    $stmt->close();

    // Jika selesai, update jumlah_terkumpul
    if ($newStatus === 'selesai') {
        $upd = $koneksi->prepare(
            "UPDATE katalog_kebutuhan k
             JOIN donasi d ON d.katalog_id = k.id
             SET k.jumlah_terkumpul = k.jumlah_terkumpul + d.qty_donasi
             WHERE d.id = ?"
        );
        $upd->bind_param("s", $id);
        $upd->execute();
        $upd->close();
    }

    $koneksi->close();
    header('Location: index.php?msg=Status+donasi+berhasil+diubah');
} catch (Throwable $e) {
    header('Location: index.php?err=' . urlencode($e->getMessage()));
}
exit;
