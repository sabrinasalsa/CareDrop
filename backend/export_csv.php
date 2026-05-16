<?php
ob_start(); ini_set('display_errors',0); error_reporting(0);
session_start(); require_once __DIR__.'/koneksi.php'; ob_end_clean();

if (!isset($_SESSION['id'],$_SESSION['role'])) { header('Location: ../index.php'); exit; }

$user_id = (int)$_SESSION['id'];
$role    = $_SESSION['role'];

try {
    if ($role === 'donatur') {
        $stmt = $koneksi->prepare(
            "SELECT d.id, d.qty_donasi, d.status_donasi, d.created_at,
                    COALESCE(k.nama_barang,'—') AS barang,
                    COALESCE(u.nama_lengkap,'—') AS yayasan,
                    p.no_resi, p.kurir
             FROM donasi d
             LEFT JOIN katalog_kebutuhan k ON k.id=d.katalog_id
             LEFT JOIN users u ON u.id=k.yayasan_id
             LEFT JOIN pengiriman p ON p.donasi_id=d.id
             WHERE d.donatur_id=? ORDER BY d.created_at DESC"
        );
        $stmt->bind_param("i",$user_id);
        $headers = ['ID Donasi','Barang','Jumlah','Yayasan','Status','Tanggal','No Resi','Kurir'];
        $fields  = ['id','barang','qty_donasi','yayasan','status_donasi','created_at','no_resi','kurir'];
    } else {
        $stmt = $koneksi->prepare(
            "SELECT d.id, d.qty_donasi, d.status_donasi, d.created_at,
                    COALESCE(k.nama_barang,'—') AS barang,
                    COALESCE(u.nama_lengkap,'—') AS donatur,
                    p.no_resi, p.kurir
             FROM donasi d
             JOIN katalog_kebutuhan k ON k.id=d.katalog_id
             JOIN users u ON u.id=d.donatur_id
             LEFT JOIN pengiriman p ON p.donasi_id=d.id
             WHERE k.yayasan_id=? ORDER BY d.created_at DESC"
        );
        $stmt->bind_param("i",$user_id);
        $headers = ['ID Donasi','Barang','Jumlah','Donatur','Status','Tanggal','No Resi','Kurir'];
        $fields  = ['id','barang','qty_donasi','donatur','status_donasi','created_at','no_resi','kurir'];
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $koneksi->close();

    $fname = 'caredrop_riwayat_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$fname\"");
    header('Pragma: no-cache');

    $out = fopen('php://output','w');
    fputs($out, "\xEF\xBB\xBF"); // BOM for Excel
    fputcsv($out, $headers);
    foreach ($rows as $r) {
        fputcsv($out, array_map(fn($f) => $r[$f] ?? '—', $fields));
    }
    fclose($out);
} catch(Throwable $e) {
    die('Error: '.$e->getMessage());
}
