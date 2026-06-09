<?php
/**
 * CareDrop – backend/dashboard_data.php
 * Endpoint JSON: data dashboard per role (donatur/penerima/admin)
 * PDO, session_config, multilevel user
 */
session_start();
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'], $_SESSION['role'])) {
    json_error('Unauthorized', 401);
}

$user_id = (int)$_SESSION['id'];
$role    = $_SESSION['role'];

/**
 * Jalankan query dan kembalikan satu baris (PDO).
 */
function queryOne(PDO $db, string $sql, array $params = []): array {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() ?: ['cnt' => 0, 'total' => 0];
}

/**
 * Jalankan query dan kembalikan semua baris (PDO).
 */
function queryAll(PDO $db, string $sql, array $params = []): array {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
}

$response = [];

try {

    /* ─── ROLE: DONATUR ─── */
    if ($role === 'donatur') {

        $total    = queryOne($pdo, "SELECT COUNT(*) AS cnt FROM donasi WHERE donatur_id = ?", [$user_id]);
        $berjalan = queryOne($pdo,
            "SELECT COUNT(*) AS cnt FROM donasi
             WHERE donatur_id = ? AND status_donasi NOT IN ('selesai','dibatalkan')", [$user_id]);
        $selesai  = queryOne($pdo,
            "SELECT COUNT(*) AS cnt FROM donasi WHERE donatur_id = ? AND status_donasi = 'selesai'", [$user_id]);

        $riwayat = queryAll($pdo,
            "SELECT
                d.id                                    AS donasi_id,
                d.qty_donasi,
                d.status_donasi                         AS status,
                d.created_at,
                COALESCE(k.nama_barang, '—')            AS nama_barang,
                COALESCE(u.nama_lengkap, '—')           AS nama_yayasan,
                p.no_resi
             FROM donasi d
             LEFT JOIN katalog_kebutuhan k ON k.id = d.katalog_id
             LEFT JOIN users             u ON u.id = k.yayasan_id
             LEFT JOIN pengiriman        p ON p.donasi_id = d.id
             WHERE d.donatur_id = ?
             ORDER BY d.created_at DESC LIMIT 10", [$user_id]);

        $response = [
            'role'    => 'donatur',
            'stats'   => [
                'total_donasi' => (int)($total['cnt']    ?? 0),
                'berjalan'     => (int)($berjalan['cnt'] ?? 0),
                'selesai'      => (int)($selesai['cnt']  ?? 0),
                'sertifikat'   => (int)($selesai['cnt']  ?? 0),
            ],
            'riwayat' => $riwayat,
        ];

    /* ─── ROLE: PENERIMA ─── */
    } elseif ($role === 'penerima') {

        $total            = queryOne($pdo,
            "SELECT COUNT(*) AS cnt FROM donasi d
             JOIN katalog_kebutuhan k ON k.id = d.katalog_id WHERE k.yayasan_id = ?", [$user_id]);
        $kebutuhan_aktif  = queryOne($pdo,
            "SELECT COUNT(*) AS cnt FROM katalog_kebutuhan
             WHERE yayasan_id = ? AND jumlah_terkumpul < target_butuh", [$user_id]);
        $perlu_konfirmasi = queryOne($pdo,
            "SELECT COUNT(*) AS cnt FROM donasi d
             JOIN katalog_kebutuhan k ON k.id = d.katalog_id
             WHERE k.yayasan_id = ? AND d.status_donasi = 'dikirim'", [$user_id]);
        $bulan_selesai    = queryOne($pdo,
            "SELECT COUNT(*) AS cnt FROM donasi d
             JOIN katalog_kebutuhan k ON k.id = d.katalog_id
             WHERE k.yayasan_id = ? AND d.status_donasi = 'selesai'
               AND MONTH(d.created_at) = MONTH(NOW()) AND YEAR(d.created_at) = YEAR(NOW())", [$user_id]);
        $target_bulan     = queryOne($pdo,
            "SELECT COALESCE(SUM(target_butuh), 0) AS total FROM katalog_kebutuhan
             WHERE yayasan_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())", [$user_id]);

        $pct = 0;
        if (!empty($target_bulan['total']) && $target_bulan['total'] > 0) {
            $pct = min(100, round(($bulan_selesai['cnt'] / $target_bulan['total']) * 100));
        }

        $riwayat = queryAll($pdo,
            "SELECT
                d.id                           AS donasi_id,
                d.qty_donasi,
                d.status_donasi                AS status,
                COALESCE(d.alasan_tolak,'')    AS alasan_tolak,
                d.created_at,
                COALESCE(k.nama_barang, '—')   AS nama_barang,
                COALESCE(u.nama_lengkap, '—')  AS nama_donatur,
                p.no_resi
             FROM donasi d
             JOIN katalog_kebutuhan k ON k.id = d.katalog_id
             JOIN users             u ON u.id = d.donatur_id
             LEFT JOIN pengiriman   p ON p.donasi_id = d.id
             WHERE k.yayasan_id = ?
             ORDER BY d.created_at DESC LIMIT 10", [$user_id]);

        $katalog = queryAll($pdo,
            "SELECT id, nama_barang, kategori, urgensi, target_butuh, jumlah_terkumpul
             FROM katalog_kebutuhan WHERE yayasan_id = ?
             ORDER BY FIELD(urgensi,'high','med','low'), id DESC LIMIT 6", [$user_id]);

        $response = [
            'role'    => 'penerima',
            'stats'   => [
                'total_donasi'     => (int)($total['cnt']              ?? 0),
                'kebutuhan_aktif'  => (int)($kebutuhan_aktif['cnt']    ?? 0),
                'perlu_konfirmasi' => (int)($perlu_konfirmasi['cnt']   ?? 0),
                'pct_terpenuhi'    => $pct . '%',
            ],
            'riwayat' => $riwayat,
            'katalog' => $katalog,
        ];

    /* ─── ROLE: ADMIN ─── */
    } elseif ($role === 'admin') {

        $total_user    = queryOne($pdo, "SELECT COUNT(*) AS cnt FROM users");
        $donasi_aktif  = queryOne($pdo,
            "SELECT COUNT(*) AS cnt FROM donasi WHERE status_donasi NOT IN ('selesai','dibatalkan')");
        $penerima_verif = queryOne($pdo,
            "SELECT COUNT(*) AS cnt FROM users WHERE role = 'penerima' AND status_verifikasi = 'verified'");
        $total_barang  = queryOne($pdo,
            "SELECT COALESCE(SUM(qty_donasi), 0) AS total FROM donasi WHERE status_donasi = 'selesai'");

        $pending = queryAll($pdo,
            "SELECT id, nama_lengkap, email, COALESCE(no_telp,'—') AS no_telp, created_at
             FROM users WHERE role = 'penerima' AND status_verifikasi = 'pending'
             ORDER BY created_at DESC LIMIT 10");

        $riwayat = queryAll($pdo,
            "SELECT
                d.id                            AS donasi_id,
                d.qty_donasi,
                d.status_donasi                 AS status,
                d.created_at,
                COALESCE(k.nama_barang, '—')    AS nama_barang,
                COALESCE(ud.nama_lengkap, '—')  AS nama_donatur,
                COALESCE(up.nama_lengkap, '—')  AS nama_yayasan
             FROM donasi d
             LEFT JOIN katalog_kebutuhan k  ON k.id  = d.katalog_id
             LEFT JOIN users             ud ON ud.id = d.donatur_id
             LEFT JOIN users             up ON up.id = k.yayasan_id
             ORDER BY d.created_at DESC LIMIT 10");

        $response = [
            'role'    => 'admin',
            'stats'   => [
                'total_user'     => (int)($total_user['cnt']     ?? 0),
                'donasi_aktif'   => (int)($donasi_aktif['cnt']   ?? 0),
                'penerima_verif' => (int)($penerima_verif['cnt'] ?? 0),
                'total_barang'   => (int)($total_barang['total'] ?? 0),
            ],
            'pending' => $pending,
            'riwayat' => $riwayat,
        ];

    } else {
        json_error('Role tidak dikenali', 403);
    }

} catch (PDOException $e) {
    json_error('Server error', 500);
}

$pdo = null;
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
