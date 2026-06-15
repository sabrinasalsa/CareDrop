<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'penerima') {
    header('Location: ../index.php'); exit;
}

$id         = (int)($_GET['id'] ?? 0);
$yayasan_id = (int)$_SESSION['id'];

if ($id <= 0) {
    header('Location: kelola_katalog.php?err=invalid'); exit;
}

try {
    // Pastikan item milik yayasan yang login
    $chk = $pdo->prepare("SELECT id, nama_barang FROM katalog_kebutuhan WHERE id = ? AND yayasan_id = ?");
    $chk->execute([$id, $yayasan_id]);
    $item = $chk->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        header('Location: kelola_katalog.php?err=notfound'); exit;
    }

    // Cek apakah ada donasi aktif yang terhubung
    $cekDonasi = $pdo->prepare(
        "SELECT COUNT(*) AS cnt FROM donasi WHERE katalog_id = ? AND status_donasi NOT IN ('selesai','dibatalkan')"
    );
    $cekDonasi->execute([$id]);
    $aktif = $cekDonasi->fetch(PDO::FETCH_ASSOC);

    if ($aktif['cnt'] > 0) {
        // Ada donasi aktif — tidak bisa dihapus
        header('Location: kelola_katalog.php?err=ada_donasi_aktif'); exit;
    }

    // Hapus
    $del = $pdo->prepare("DELETE FROM katalog_kebutuhan WHERE id = ? AND yayasan_id = ?");
    $del->execute([$id, $yayasan_id]);
    $pdo = null;

    header('Location: kelola_katalog.php?deleted=1'); exit;

} catch (Throwable $e) {
    header('Location: kelola_katalog.php?err=' . urlencode($e->getMessage())); exit;
}
