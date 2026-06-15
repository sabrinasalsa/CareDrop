<?php
session_start();
require_once dirname(__DIR__) . '/backend/koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php'); exit;
}

$id   = (int)($_GET['id'] ?? 0);
$aksi = $_GET['aksi'] ?? '';

if ($id <= 0) { header('Location: index.php?err=ID+tidak+valid'); exit; }

try {
    switch ($aksi) {
        case 'verif':
            $stmt = $pdo->prepare("UPDATE users SET status_verifikasi='verified' WHERE id=? AND role='penerima'");
            $stmt->execute([$id]);
            header('Location: index.php?msg=Akun+berhasil+diverifikasi'); break;

        case 'tolak':
            $stmt = $pdo->prepare("UPDATE users SET status_verifikasi='rejected' WHERE id=? AND role='penerima'");
            $stmt->execute([$id]);
            header('Location: index.php?msg=Akun+ditolak'); break;

        case 'nonaktif':
            $stmt = $pdo->prepare("UPDATE users SET status_verifikasi='rejected' WHERE id=? AND role!='admin'");
            $stmt->execute([$id]);
            header('Location: index.php?msg=Akun+dinonaktifkan'); break;

        case 'hapus':
            // Jangan hapus admin
            $chk = $pdo->prepare("SELECT role FROM users WHERE id=?");
            $chk->execute([$id]);
            $row = $chk->fetch();
            if ($row['role'] === 'admin') {
                header('Location: index.php?err=Tidak+bisa+hapus+admin'); exit;
            }
            $del = $pdo->prepare("DELETE FROM users WHERE id=? AND role!='admin'");
            $del->execute([$id]);
            header('Location: index.php?msg=Akun+berhasil+dihapus'); break;

        default:
            header('Location: index.php?err=Aksi+tidak+valid');
    }
    $pdo = null;
} catch (Throwable $e) {
    header('Location: index.php?err=' . urlencode($e->getMessage()));
}
exit;
