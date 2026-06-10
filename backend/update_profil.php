<?php

session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Autentikasi ──
if (!isset($_SESSION['id'])) { json_error('Belum login', 401); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method tidak valid', 405); }


$user_id = (int)$_SESSION['id'];
$nama    = trim($_POST['nama']    ?? '');
$no_telp = trim($_POST['no_telp'] ?? '');
$alamat  = trim($_POST['alamat']  ?? '');

// ── Validasi nama ──
if (empty($nama)) { json_error('Nama tidak boleh kosong'); }
if (strlen($nama) > 150) { json_error('Nama maksimal 150 karakter'); }
if (!preg_match('/^[\p{L}\s.\'-]{2,150}$/u', $nama)) { json_error('Nama mengandung karakter tidak valid'); }

// ── Validasi no_telp (opsional, tapi kalau diisi harus valid) ──
if (!empty($no_telp) && !preg_match('/^[\d\+\-\s\(\)]{7,20}$/', $no_telp)) {
    json_error('Format nomor telepon tidak valid');
}

// ── Batas panjang alamat ──
if (strlen($alamat) > 500) { json_error('Alamat maksimal 500 karakter'); }

try {
    $stmt = $pdo->prepare(
        "UPDATE users SET nama_lengkap = ?, no_telp = ?, alamat = ? WHERE id = ?"
    );
    $stmt->execute([
        htmlspecialchars($nama, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($no_telp, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($alamat, ENT_QUOTES, 'UTF-8'),
        $user_id
    ]);

    // Update session
    $_SESSION['nama']    = $nama;
    $_SESSION['no_telp'] = $no_telp;
    $_SESSION['alamat']  = $alamat;

    $pdo = null;
    echo json_encode(['ok' => true, 'nama' => $nama, 'no_telp' => $no_telp, 'alamat' => $alamat]);

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error. Silakan coba lagi.', 500);
}
