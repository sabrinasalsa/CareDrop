<?php

ob_start();
ini_set('display_errors', 0);
error_reporting(0);
session_start();
require_once __DIR__ . '/koneksi.php';
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['ok' => false, 'error' => 'Belum login']); exit;
}

$resi = htmlspecialchars(trim($_GET['resi'] ?? ''));
if (empty($resi)) {
    echo json_encode(['ok' => false, 'error' => 'Nomor resi kosong']); exit;
}

try {
    $stmt = $koneksi->prepare(
        "SELECT
            p.no_resi,
            p.kurir,
            p.tipe_layanan,
            p.kota_asal,
            p.kota_tujuan,
            p.berat_kg,
            p.estimasi_ongkir,
            d.id              AS donasi_id,
            d.status_donasi   AS status,
            d.created_at,
            d.qty_donasi,
            COALESCE(k.nama_barang, '—')   AS nama_barang,
            COALESCE(ud.nama_lengkap, '—') AS nama_donatur,
            COALESCE(uy.nama_lengkap, '—') AS nama_yayasan,
            COALESCE(uy.alamat, '—')       AS alamat_yayasan
         FROM pengiriman p
         JOIN donasi d              ON d.id   = p.donasi_id
         LEFT JOIN katalog_kebutuhan k  ON k.id   = d.katalog_id
         LEFT JOIN users            ud ON ud.id = d.donatur_id
         LEFT JOIN users            uy ON uy.id = k.yayasan_id
         WHERE p.no_resi = ?
         LIMIT 1"
    );
    $stmt->bind_param("s", $resi);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $koneksi->close();

    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'Nomor resi tidak ditemukan di sistem']);
        exit;
    }

    // Buat timeline berdasarkan status_donasi
    $status = $row['status'];
    $tgl    = date('d M Y', strtotime($row['created_at']));

    $steps = [
        ['label' => 'Donasi Dibuat',             'desc' => "Permintaan donasi dibuat oleh {$row['nama_donatur']}",           'done' => true],
        ['label' => 'Diproses Kurir',             'desc' => "Barang siap dijemput oleh " . strtoupper($row['kurir']),          'done' => in_array($status, ['diproses','dikirim','selesai'])],
        ['label' => 'Paket dalam Perjalanan',     'desc' => "{$row['kota_asal']} → {$row['kota_tujuan']} via " . strtoupper($row['kurir']), 'done' => in_array($status, ['dikirim','selesai'])],
        ['label' => 'Tiba di Tujuan',             'desc' => "Paket tiba di {$row['nama_yayasan']}",                           'done' => $status === 'selesai'],
        ['label' => 'Dikonfirmasi Penerima ✅',   'desc' => "Yayasan telah mengkonfirmasi penerimaan barang",                 'done' => $status === 'selesai'],
    ];

    // Cari step yang sedang aktif (current)
    $currentIdx = 0;
    foreach ($steps as $i => $s) {
        if ($s['done']) $currentIdx = $i;
    }
    // Step berikutnya = current
    if ($currentIdx < count($steps) - 1 && $steps[$currentIdx]['done']) {
        $currentIdx++;
    }

    echo json_encode([
        'ok'     => true,
        'resi'   => $row,
        'steps'  => $steps,
        'current'=> $currentIdx,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
