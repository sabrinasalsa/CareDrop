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
    $chk = $koneksi->prepare("SELECT id, nama_barang FROM katalog_kebutuhan WHERE id = ? AND yayasan_id = ?");
    $chk->bind_param("ii", $id, $yayasan_id);
    $chk->execute();
    $item = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$item) {
        header('Location: kelola_katalog.php?err=notfound'); exit;
    }

    // Cek apakah ada donasi aktif yang terhubung
    $cekDonasi = $koneksi->prepare(
        "SELECT COUNT(*) AS cnt FROM donasi WHERE katalog_id = ? AND status_donasi NOT IN ('selesai','dibatalkan')"
    );
    $cekDonasi->bind_param("i", $id);
    $cekDonasi->execute();
    $aktif = $cekDonasi->get_result()->fetch_assoc();
    $cekDonasi->close();

    if ($aktif['cnt'] > 0) {
        // Ada donasi aktif — tidak bisa dihapus
        header('Location: kelola_katalog.php?err=ada_donasi_aktif'); exit;
    }

    // Hapus
    $del = $koneksi->prepare("DELETE FROM katalog_kebutuhan WHERE id = ? AND yayasan_id = ?");
    $del->bind_param("ii", $id, $yayasan_id);
    $del->execute();
    $del->close();
    $koneksi->close();

    header('Location: kelola_katalog.php?deleted=1'); exit;

} catch (Throwable $e) {
    header('Location: kelola_katalog.php?err=' . urlencode($e->getMessage())); exit;
}
