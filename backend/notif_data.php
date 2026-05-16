<?php
ob_start(); ini_set('display_errors',0); error_reporting(0);
session_start(); require_once __DIR__.'/koneksi.php'; ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'],$_SESSION['role'])) { echo json_encode(['ok'=>false,'notif',[]]); exit; }

$user_id = (int)$_SESSION['id'];
$role    = $_SESSION['role'];
$notifs  = [];

try {
    if ($role === 'donatur') {
        // Donasi yang baru selesai (status berubah jadi selesai dalam 7 hari terakhir)
        $stmt = $koneksi->prepare(
            "SELECT d.id, d.status_donasi, d.updated_at,
                    COALESCE(k.nama_barang,'—') AS nama_barang,
                    COALESCE(u.nama_lengkap,'—') AS nama_yayasan
             FROM donasi d
             LEFT JOIN katalog_kebutuhan k ON k.id = d.katalog_id
             LEFT JOIN users u ON u.id = k.yayasan_id
             WHERE d.donatur_id = ?
               AND d.status_donasi IN ('selesai','dikirim')
               AND d.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY d.updated_at DESC LIMIT 5"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($rows as $r) {
            $notifs[] = [
                'icon'  => $r['status_donasi'] === 'selesai' ? '✅' : '🚚',
                'pesan' => $r['status_donasi'] === 'selesai'
                    ? "Donasi \"{$r['nama_barang']}\" sudah diterima oleh {$r['nama_yayasan']}!"
                    : "Donasi \"{$r['nama_barang']}\" sedang dalam perjalanan ke {$r['nama_yayasan']}",
                'waktu' => $r['updated_at'],
                'unread'=> true,
            ];
        }
    } elseif ($role === 'penerima') {
        // Donasi baru masuk ke yayasan
        $stmt = $koneksi->prepare(
            "SELECT d.id, d.status_donasi, d.created_at,
                    COALESCE(k.nama_barang,'—') AS nama_barang,
                    COALESCE(u.nama_lengkap,'—') AS nama_donatur
             FROM donasi d
             JOIN katalog_kebutuhan k ON k.id = d.katalog_id
             JOIN users u ON u.id = d.donatur_id
             WHERE k.yayasan_id = ?
               AND d.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY d.created_at DESC LIMIT 5"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($rows as $r) {
            $notifs[] = [
                'icon'  => '🎁',
                'pesan' => "Donasi baru! {$r['nama_donatur']} mendonasikan \"{$r['nama_barang']}\"",
                'waktu' => $r['created_at'],
                'unread'=> $r['status_donasi'] === 'menunggu',
            ];
        }
    } elseif ($role === 'admin') {
        $stmt = $koneksi->prepare(
            "SELECT id, nama_lengkap, created_at FROM users
             WHERE role='penerima' AND status_verifikasi='pending'
             ORDER BY created_at DESC LIMIT 5"
        );
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($rows as $r) {
            $notifs[] = [
                'icon'  => '⚠️',
                'pesan' => "Verifikasi pending: {$r['nama_lengkap']} mendaftar sebagai penerima",
                'waktu' => $r['created_at'],
                'unread'=> true,
            ];
        }
    }
    $koneksi->close();
    echo json_encode(['ok'=>true,'notif'=>$notifs,'count'=>count(array_filter($notifs,fn($n)=>$n['unread']))], JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
    echo json_encode(['ok'=>false,'notif'=>[],'error'=>$e->getMessage()]);
}
