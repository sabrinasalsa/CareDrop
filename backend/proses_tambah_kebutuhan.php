<?php

session_start();
require_once __DIR__ . '/koneksi.php';

// ── Autentikasi & otorisasi ──
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../yayasan/kelola_katalog.php'); exit;
}


$yayasan_id  = (int)$_SESSION['id'];
$nama_barang = htmlspecialchars(trim($_POST['nama_barang'] ?? ''), ENT_QUOTES, 'UTF-8');
$target      = (int)($_POST['target_butuh'] ?? $_POST['target'] ?? 0);
$deskripsi   = htmlspecialchars(trim($_POST['deskripsi'] ?? ''), ENT_QUOTES, 'UTF-8');

// ── Whitelist kategori & urgensi ──
$allowed_kat = ['pakaian', 'buku', 'elektronik', 'perabot', 'alat tulis', 'perlengkapan', 'sembako', 'lainnya'];
$allowed_urg = ['high', 'med', 'low'];
$kategori    = strtolower(trim($_POST['kategori'] ?? ''));
$urgensi     = trim($_POST['urgensi'] ?? '');
if (!in_array($kategori, $allowed_kat, true)) $kategori = 'lainnya';
if (!in_array($urgensi,  $allowed_urg, true))  $urgensi  = 'med';

// ── Validasi ──
if (empty($nama_barang)) {
    header('Location: ../yayasan/kelola_katalog.php?err=empty_nama'); exit;
}
if (strlen($nama_barang) > 200) {
    header('Location: ../yayasan/kelola_katalog.php?err=nama_long'); exit;
}
if ($target < 1) {
    header('Location: ../yayasan/kelola_katalog.php?err=target_invalid'); exit;
}
if ($target > 10000) {
    header('Location: ../yayasan/kelola_katalog.php?err=target_too_large'); exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO katalog_kebutuhan (yayasan_id, nama_barang, kategori, urgensi, target_butuh, jumlah_terkumpul, deskripsi)
         VALUES (?, ?, ?, ?, ?, 0, ?)"
    );
    $stmt->execute([$yayasan_id, $nama_barang, $kategori, $urgensi, $target, $deskripsi]);

    $pdo = null;
    header('Location: ../yayasan/kelola_katalog.php?added=1'); exit;

} catch (PDOException $e) {
    $pdo = null;
    header('Location: ../yayasan/kelola_katalog.php?err=' . urlencode('Gagal menyimpan. Silakan coba lagi.')); exit;
}
