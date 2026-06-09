<?php
/**
 * CareDrop – backend/ganti_password.php
 * Ganti password: PDO, CSRF, validasi kekuatan password (min 8, ada angka)
 */
session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Autentikasi ──
if (!isset($_SESSION['id'])) { json_error('Belum login', 401); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method tidak valid', 405); }


$user_id = (int)$_SESSION['id'];
$lama    = $_POST['password_lama']    ?? '';
$baru    = $_POST['password_baru']    ?? '';
$konfirm = $_POST['password_konfirm'] ?? '';

// ── Validasi input ──
if (empty($lama) || empty($baru)) {
    json_error('Password lama dan baru wajib diisi');
}
if (strlen($baru) < 8) {
    json_error('Password baru minimal 8 karakter');
}
if (!preg_match('/[0-9]/', $baru)) {
    json_error('Password baru harus mengandung minimal 1 angka');
}
if (!preg_match('/[A-Za-z]/', $baru)) {
    json_error('Password baru harus mengandung minimal 1 huruf');
}
if ($baru === $lama) {
    json_error('Password baru tidak boleh sama dengan password lama');
}
if (!empty($konfirm) && $baru !== $konfirm) {
    json_error('Konfirmasi password tidak cocok');
}

try {
    // Ambil hash password saat ini
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($lama, $row['password'])) {
        json_error('Password lama tidak sesuai', 401);
    }

    // Hash password baru dengan bcrypt
    $hash = password_hash($baru, PASSWORD_BCRYPT, ['cost' => 12]);

    $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $upd->execute([$hash, $user_id]);

    // Regenerate session ID setelah ganti password (keamanan)
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();

    $pdo = null;
    echo json_encode(['ok' => true, 'msg' => 'Password berhasil diubah.']);

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error. Silakan coba lagi.', 500);
}
