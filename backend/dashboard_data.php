<?php
/**
 * CareDrop – backend/dashboard_data.php
 * API endpoint JSON murni — HARUS tidak ada output HTML sama sekali
 */

// ── 1. Buffer semua output agar PHP error/warning tidak bocor ke JSON ──
ob_start();

// ── 2. Matikan display_errors untuk endpoint ini ──
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

session_start();
require_once __DIR__ . '/koneksi.php';

// ── 3. Buang buffer (tangkap warning PHP dari koneksi dll) ──
ob_end_clean();

// ── 4. Set header JSON ──
header('Content-Type: application/json; charset=utf-8');

// ── 5. Guard: harus sudah login ──
if (!isset($_SESSION['id'], $_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - silakan login ulang']);
    exit;
}

$user_id = (int) $_SESSION['id'];
$role    = $_SESSION['role'];

// ── Helper: query satu baris ──
function queryOne(mysqli $db, string $sql, string $types = '', ...$params): array {
    $stmt = $db->prepare($sql);
    if (!$stmt) return ['cnt' => 0, 'total' => 0];
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: ['cnt' => 0, 'total' => 0];
}

// ── Helper: query banyak baris ──
function queryAll(mysqli $db, string $sql, string $types = '', ...$params): array {
    $stmt = $db->prepare($sql);
    if (!$stmt) return [];
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows ?: [];
}

$response = [];

try {

    /* ════════════════════════════════════════
       ROLE: DONATUR
    ════════════════════════════════════════ */
    if ($role === 'donatur') {

        $total    = queryOne($koneksi,
            "SELECT COUNT(*) AS cnt FROM donasi WHERE donatur_id = ?",
            "i", $user_id);

        $berjalan = queryOne($koneksi,
            "SELECT COUNT(*) AS cnt FROM donasi
             WHERE donatur_id = ? AND status NOT IN ('selesai','dibatalkan')",
            "i", $user_id);

        $selesai  = queryOne($koneksi,
            "SELECT COUNT(*) AS cnt FROM donasi
             WHERE donatur_id = ? AND status = 'selesai'",
            "i", $user_id);

        $sertifikat = queryOne($koneksi,
            "SELECT COUNT(*) AS cnt FROM donasi
             WHERE donatur_id = ? AND status = 'selesai'",
            "i", $user_id);

        $riwayat = queryAll($koneksi,
            "SELECT
                d.id            AS donasi_id,
                d.qty_donasi,
                d.status,
                d.created_at,
                COALESCE(k.nama_barang, '(barang dihapus)') AS nama_barang,
                COALESCE(u.nama_lengkap, '(yayasan)')       AS nama_yayasan,
                p.no_resi
             FROM donasi d
             LEFT JOIN katalog_kebutuhan k ON k.id = d.katalog_id
             LEFT JOIN users             u ON u.id = k.yayasan_id
             LEFT JOIN pengiriman        p ON p.donasi_id = d.id
             WHERE d.donatur_id = ?
             ORDER BY d.created_at DESC
             LIMIT 10",
            "i", $user_id);

        $response = [
            'role'    => 'donatur',
            'stats'   => [
                'total_donasi' => (int)($total['cnt']      ?? 0),
                'berjalan'     => (int)($berjalan['cnt']   ?? 0),
                'selesai'      => (int)($selesai['cnt']    ?? 0),
                'sertifikat'   => (int)($sertifikat['cnt'] ?? 0),
            ],
            'riwayat' => $riwayat,
        ];

    /* ════════════════════════════════════════
       ROLE: PENERIMA
    ════════════════════════════════════════ */
    } elseif ($role === 'penerima') {

        $total = queryOne($koneksi,
            "SELECT COUNT(*) AS cnt
             FROM donasi d
             JOIN katalog_kebutuhan k ON k.id = d.katalog_id
             WHERE k.yayasan_id = ?",
            "i", $user_id);

        $kebutuhan_aktif = queryOne($koneksi,
            "SELECT COUNT(*) AS cnt FROM katalog_kebutuhan
             WHERE yayasan_id = ? AND jumlah_terkumpul < target_butuh",
            "i", $user_id);

        $perlu_konfirmasi = queryOne($koneksi,
            "SELECT COUNT(*) AS cnt
             FROM donasi d
             JOIN katalog_kebutuhan k ON k.id = d.katalog_id
             WHERE k.yayasan_id = ? AND d.status = 'dikirim'",
            "i", $user_id);

        $bulan_selesai = queryOne($koneksi,
            "SELECT COUNT(*) AS cnt
             FROM donasi d
             JOIN katalog_kebutuhan k ON k.id = d.katalog_id
             WHERE k.yayasan_id = ?
               AND d.status = 'selesai'
               AND MONTH(d.created_at) = MONTH(NOW())
               AND YEAR(d.created_at)  = YEAR(NOW())",
            "i", $user_id);

        $target_bulan = queryOne($koneksi,
            "SELECT COALESCE(SUM(target_butuh), 0) AS total
             FROM katalog_kebutuhan
             WHERE yayasan_id = ?
               AND MONTH(created_at) = MONTH(NOW())
               AND YEAR(created_at)  = YEAR(NOW())",
            "i", $user_id);

        $pct = 0;
        if (!empty($target_bulan['total']) && $target_bulan['total'] > 0) {
            $pct = min(100, round(($bulan_selesai['cnt'] / $target_bulan['total']) * 100));
        }

        $riwayat = queryAll($koneksi,
            "SELECT
                d.id            AS donasi_id,
                d.qty_donasi,
                d.status,
                d.created_at,
                COALESCE(k.nama_barang, '—')   AS nama_barang,
                COALESCE(u.nama_lengkap, '—')  AS nama_donatur,
                p.no_resi,
                p.estimasi_ongkir
             FROM donasi d
             JOIN katalog_kebutuhan k ON k.id = d.katalog_id
             JOIN users             u ON u.id = d.donatur_id
             LEFT JOIN pengiriman   p ON p.donasi_id = d.id
             WHERE k.yayasan_id = ?
             ORDER BY d.created_at DESC
             LIMIT 10",
            "i", $user_id);

        $katalog = queryAll($koneksi,
            "SELECT id, nama_barang, kategori, urgensi, target_butuh, jumlah_terkumpul
             FROM katalog_kebutuhan
             WHERE yayasan_id = ?
             ORDER BY FIELD(urgensi,'high','med','low'), id DESC
             LIMIT 6",
            "i", $user_id);

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

    /* ════════════════════════════════════════
       ROLE: ADMIN
    ════════════════════════════════════════ */
    } elseif ($role === 'admin') {

        $total_user = queryOne($koneksi,
            "SELECT COUNT(*) AS cnt FROM users");

        $donasi_aktif = queryOne($koneksi,
            "SELECT COUNT(*) AS cnt FROM donasi
             WHERE status NOT IN ('selesai','dibatalkan')");

        $penerima_verif = queryOne($koneksi,
            "SELECT COUNT(*) AS cnt FROM users
             WHERE role = 'penerima' AND status_verifikasi = 'verified'");

        $total_barang = queryOne($koneksi,
            "SELECT COALESCE(SUM(qty_donasi), 0) AS total
             FROM donasi WHERE status = 'selesai'");

        $pending = queryAll($koneksi,
            "SELECT id, nama_lengkap, email,
                    COALESCE(no_telp,'—') AS no_telp, created_at
             FROM users
             WHERE role = 'penerima' AND status_verifikasi = 'pending'
             ORDER BY created_at DESC LIMIT 10");

        $riwayat = queryAll($koneksi,
            "SELECT
                d.id                            AS donasi_id,
                d.qty_donasi,
                d.status,
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
        http_response_code(403);
        echo json_encode(['error' => 'Role tidak dikenali: ' . $role]);
        exit;
    }

} catch (Throwable $e) {
    // Tangkap semua error PHP — jangan tampilkan HTML, kembalikan JSON
    http_response_code(500);
    echo json_encode([
        'error'   => 'Server error: ' . $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine(),
    ]);
    exit;
}

$koneksi->close();
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
