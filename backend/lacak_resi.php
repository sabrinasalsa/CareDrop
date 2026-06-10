<?php

session_start();
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    json_error('Belum login', 401);
}

$resi = trim($_GET['resi'] ?? '');
if (empty($resi)) { json_error('Nomor resi kosong'); }
if (strlen($resi) > 100) { json_error('Nomor resi tidak valid'); }

// Sanitasi: hanya alfanumerik dan karakter umum resi
$resi = preg_replace('/[^A-Za-z0-9\-\_]/', '', $resi);

try {
    $stmt = $pdo->prepare(
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
    $stmt->execute([$resi]);
    $row = $stmt->fetch();
    $pdo = null;

    if (!$row) {
        json_error('Nomor resi tidak ditemukan di sistem');
    }

    // Timeline berdasarkan status_donasi
    $status = $row['status'];
    $steps  = [
        ['label' => 'Donasi Dibuat',           'desc' => "Permintaan donasi dibuat oleh {$row['nama_donatur']}",                    'done' => true],
        ['label' => 'Diproses Kurir',          'desc' => "Barang siap dijemput oleh " . strtoupper($row['kurir'] ?? ''),           'done' => in_array($status, ['diproses', 'dikirim', 'selesai'])],
        ['label' => 'Paket dalam Perjalanan',  'desc' => "{$row['kota_asal']} → {$row['kota_tujuan']} via " . strtoupper($row['kurir'] ?? ''), 'done' => in_array($status, ['dikirim', 'selesai'])],
        ['label' => 'Tiba di Tujuan',          'desc' => "Paket tiba di {$row['nama_yayasan']}",                                  'done' => $status === 'selesai'],
        ['label' => 'Dikonfirmasi Penerima ✅','desc' => "Yayasan telah mengkonfirmasi penerimaan barang",                         'done' => $status === 'selesai'],
    ];

    $currentIdx = 0;
    foreach ($steps as $i => $s) {
        if ($s['done']) $currentIdx = $i;
    }
    if ($currentIdx < count($steps) - 1 && $steps[$currentIdx]['done']) {
        $currentIdx++;
    }

    echo json_encode([
        'ok'      => true,
        'resi'    => $row,
        'steps'   => $steps,
        'current' => $currentIdx,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error', 500);
}
