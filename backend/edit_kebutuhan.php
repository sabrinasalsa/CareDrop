<?php

session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Autentikasi & otorisasi ──
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    json_error('Akses ditolak', 403);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method tidak valid', 405); }


$yayasan_id  = (int)$_SESSION['id'];
$id          = (int)($_POST['id'] ?? 0);
$nama_barang = htmlspecialchars(trim($_POST['nama_barang'] ?? ''), ENT_QUOTES, 'UTF-8');
$kategori    = trim($_POST['kategori'] ?? '');
$urgensi     = trim($_POST['urgensi']  ?? '');
$target      = (int)($_POST['target_butuh'] ?? 0);
$deskripsi   = htmlspecialchars(trim($_POST['deskripsi'] ?? ''), ENT_QUOTES, 'UTF-8');

// ── Whitelist kategori & urgensi ──
$allowKat = ['pakaian', 'buku', 'elektronik', 'perabot', 'alat tulis', 'perlengkapan', 'sembako', 'lainnya'];
$allowUrg = ['high', 'med', 'low'];

if (!in_array(strtolower($kategori), $allowKat, true)) $kategori = 'lainnya';
if (!in_array($urgensi, $allowUrg, true))               $urgensi  = 'med';

// ── Validasi ──
if ($id < 1)                  { json_error('ID tidak valid'); }
if (empty($nama_barang))      { json_error('Nama barang wajib diisi'); }
if (strlen($nama_barang) > 200) { json_error('Nama barang maksimal 200 karakter'); }
if ($target < 1)              { json_error('Target minimal 1'); }
if (strlen($deskripsi) > 1000) { json_error('Deskripsi maksimal 1000 karakter'); }

try {
    $stmt = $pdo->prepare(
        "UPDATE katalog_kebutuhan
         SET nama_barang = ?, kategori = ?, urgensi = ?, target_butuh = ?, deskripsi = ?
         WHERE id = ? AND yayasan_id = ?"
    );
    $stmt->execute([$nama_barang, $kategori, $urgensi, $target, $deskripsi, $id, $yayasan_id]);

    $pdo = null;
    // rowCount() = 0 jika data tidak berubah tapi query sukses → tetap OK
    echo json_encode(['ok' => true, 'msg' => 'Kebutuhan berhasil diperbarui.']);

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error. Silakan coba lagi.', 500);
}
