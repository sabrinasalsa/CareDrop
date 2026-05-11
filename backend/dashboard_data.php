<?php


session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');


if (!isset($_SESSION['id'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = (int) $_SESSION['id'];
$role    = $_SESSION['role'];


function queryOne(mysqli $db, string $sql, string $types = '', ...$params): array {
    $stmt = $db->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: [];
}

// ── Fungsi bantu: jalankan query & kembalikan semua baris
function queryAll(mysqli $db, string $sql, string $types = '', ...$params): array {
    $stmt = $db->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows ?: [];
}

$response = [];

/* ═══════════════════════════════════════════════════════════════════════════
   ROLE: DONATUR
   ═══════════════════════════════════════════════════════════════════════════ */
if ($role === 'donatur') {

    // 1. Total donasi sejak bergabung
    $total = queryOne($koneksi,
        "SELECT COUNT(*) AS cnt FROM donasi WHERE donatur_id = ?",
        "i", $user_id);

    // 2. Sedang berjalan (status bukan 'selesai' dan bukan 'dibatalkan')
    $berjalan = queryOne($koneksi,
        "SELECT COUNT(*) AS cnt FROM donasi
         WHERE donatur_id = ? AND status NOT IN ('selesai','dibatalkan')",
        "i", $user_id);

    // 3. Selesai
    $selesai = queryOne($koneksi,
        "SELECT COUNT(*) AS cnt FROM donasi
         WHERE donatur_id = ? AND status = 'selesai'",
        "i", $user_id);

    // 4. E-Sertifikat (donasi selesai = sertifikat diterbitkan)
    $sertifikat = queryOne($koneksi,
        "SELECT COUNT(*) AS cnt FROM donasi
         WHERE donatur_id = ? AND status = 'selesai'",
        "i", $user_id);

    // 5. Donasi terbaru (5 terakhir) + nama barang dari katalog + nama yayasan
    $riwayat = queryAll($koneksi,
        "SELECT
            d.id            AS donasi_id,
            d.qty_donasi,
            d.status,
            d.created_at,
            k.nama_barang,
            u.nama_lengkap  AS nama_yayasan,
            p.no_resi
         FROM donasi d
         LEFT JOIN katalog_kebutuhan k ON k.id  = d.katalog_id
         LEFT JOIN users             u ON u.id  = k.yayasan_id
         LEFT JOIN pengiriman        p ON p.donasi_id = d.id
         WHERE d.donatur_id = ?
         ORDER BY d.created_at DESC
         LIMIT 5",
        "i", $user_id);

    $response = [
        'role'       => 'donatur',
        'stats'      => [
            'total_donasi' => (int)($total['cnt']     ?? 0),
            'berjalan'     => (int)($berjalan['cnt']  ?? 0),
            'selesai'      => (int)($selesai['cnt']   ?? 0),
            'sertifikat'   => (int)($sertifikat['cnt']?? 0),
        ],
        'riwayat' => $riwayat,
    ];

/* 
   ROLE: PENERIMA*/
} elseif ($role === 'penerima') {

    // 1. Total donasi yang diterima yayasan ini (via katalog milik yayasan)
    $total = queryOne($koneksi,
        "SELECT COUNT(*) AS cnt
         FROM donasi d
         JOIN katalog_kebutuhan k ON k.id = d.katalog_id
         WHERE k.yayasan_id = ?",
        "i", $user_id);

    // 2. Kebutuhan aktif (katalog yang masih terbuka / belum terpenuhi)
    $kebutuhan_aktif = queryOne($koneksi,
        "SELECT COUNT(*) AS cnt FROM katalog_kebutuhan
         WHERE yayasan_id = ? AND jumlah_terkumpul < target_butuh",
        "i", $user_id);

    // 3. Perlu konfirmasi (donasi dengan status 'dikirim' / dalam perjalanan)
    $perlu_konfirmasi = queryOne($koneksi,
        "SELECT COUNT(*) AS cnt
         FROM donasi d
         JOIN katalog_kebutuhan k ON k.id = d.katalog_id
         WHERE k.yayasan_id = ? AND d.status = 'dikirim'",
        "i", $user_id);

    // 4. Persentase terpenuhi bulan ini
    //    = (donasi selesai bulan ini) / (target total katalog aktif bulan ini) * 100
    $bulan_selesai = queryOne($koneksi,
        "SELECT COUNT(*) AS cnt
         FROM donasi d
         JOIN katalog_kebutuhan k ON k.id = d.katalog_id
         WHERE k.yayasan_id = ? AND d.status = 'selesai'
           AND MONTH(d.created_at) = MONTH(NOW())
           AND YEAR(d.created_at)  = YEAR(NOW())",
        "i", $user_id);

    $target_bulan = queryOne($koneksi,
        "SELECT SUM(target_butuh) AS total FROM katalog_kebutuhan
         WHERE yayasan_id = ?
           AND MONTH(created_at) = MONTH(NOW())
           AND YEAR(created_at)  = YEAR(NOW())",
        "i", $user_id);

    $pct = 0;
    if (!empty($target_bulan['total']) && $target_bulan['total'] > 0) {
        $pct = round(($bulan_selesai['cnt'] / $target_bulan['total']) * 100);
    }

    // 5. Donasi terbaru yang masuk ke yayasan ini (5 terakhir)
    $riwayat = queryAll($koneksi,
        "SELECT
            d.id           AS donasi_id,
            d.qty_donasi,
            d.status,
            d.created_at,
            k.nama_barang,
            u.nama_lengkap AS nama_donatur,
            p.no_resi,
            p.estimasi_ongkir
         FROM donasi d
         JOIN katalog_kebutuhan k ON k.id  = d.katalog_id
         JOIN users             u ON u.id  = d.donatur_id
         LEFT JOIN pengiriman   p ON p.donasi_id = d.id
         WHERE k.yayasan_id = ?
         ORDER BY d.created_at DESC
         LIMIT 5",
        "i", $user_id);

    // 6. Katalog kebutuhan aktif (tampil di dashboard)
    $katalog = queryAll($koneksi,
        "SELECT id, nama_barang, kategori, urgensi, target_butuh, jumlah_terkumpul
         FROM katalog_kebutuhan
         WHERE yayasan_id = ?
         ORDER BY
            FIELD(urgensi,'high','med','low'),
            id DESC
         LIMIT 6",
        "i", $user_id);

    $response = [
        'role'  => 'penerima',
        'stats' => [
            'total_donasi'     => (int)($total['cnt']              ?? 0),
            'kebutuhan_aktif'  => (int)($kebutuhan_aktif['cnt']    ?? 0),
            'perlu_konfirmasi' => (int)($perlu_konfirmasi['cnt']   ?? 0),
            'pct_terpenuhi'    => $pct . '%',
        ],
        'riwayat' => $riwayat,
        'katalog' => $katalog,
    ];

/* 
   ROLE: ADMIN */
} elseif ($role === 'admin') {

    // 1. Total pengguna
    $total_user = queryOne($koneksi, "SELECT COUNT(*) AS cnt FROM users");

    // 2. Donasi aktif (bukan selesai / bukan dibatalkan)
    $donasi_aktif = queryOne($koneksi,
        "SELECT COUNT(*) AS cnt FROM donasi
         WHERE status NOT IN ('selesai','dibatalkan')");

    // 3. Penerima terverifikasi
    $penerima_verif = queryOne($koneksi,
        "SELECT COUNT(*) AS cnt FROM users
         WHERE role = 'penerima' AND status_verifikasi = 'verified'");

    // 4. Total barang tersalurkan (sum qty donasi selesai)
    $total_barang = queryOne($koneksi,
        "SELECT COALESCE(SUM(qty_donasi),0) AS total FROM donasi WHERE status = 'selesai'");

    // 5. Penerima pending verifikasi
    $pending = queryAll($koneksi,
        "SELECT id, nama_lengkap, email, no_telp, created_at
         FROM users
         WHERE role = 'penerima' AND status_verifikasi = 'pending'
         ORDER BY created_at DESC LIMIT 5");

    // 6. Donasi terbaru lintas semua user
    $riwayat = queryAll($koneksi,
        "SELECT
            d.id           AS donasi_id,
            d.qty_donasi,
            d.status,
            d.created_at,
            k.nama_barang,
            ud.nama_lengkap AS nama_donatur,
            up.nama_lengkap AS nama_yayasan
         FROM donasi d
         LEFT JOIN katalog_kebutuhan k  ON k.id  = d.katalog_id
         LEFT JOIN users             ud ON ud.id = d.donatur_id
         LEFT JOIN users             up ON up.id = k.yayasan_id
         ORDER BY d.created_at DESC LIMIT 5");

    $response = [
        'role'  => 'admin',
        'stats' => [
            'total_user'      => (int)($total_user['cnt']     ?? 0),
            'donasi_aktif'    => (int)($donasi_aktif['cnt']   ?? 0),
            'penerima_verif'  => (int)($penerima_verif['cnt'] ?? 0),
            'total_barang'    => (int)($total_barang['total'] ?? 0),
        ],
        'pending' => $pending,
        'riwayat' => $riwayat,
    ];

} else {
    http_response_code(403);
    echo json_encode(['error' => 'Role tidak dikenali']);
    exit;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
$koneksi->close();
?>