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
            $stmt = $koneksi->prepare("UPDATE users SET status_verifikasi='verified' WHERE id=? AND role='penerima'");
            $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
            header('Location: index.php?msg=Akun+berhasil+diverifikasi'); break;

        case 'tolak':
            $stmt = $koneksi->prepare("UPDATE users SET status_verifikasi='rejected' WHERE id=? AND role='penerima'");
            $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
            header('Location: index.php?msg=Akun+ditolak'); break;

        case 'nonaktif':
            $stmt = $koneksi->prepare("UPDATE users SET status_verifikasi='rejected' WHERE id=? AND role!='admin'");
            $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
            header('Location: index.php?msg=Akun+dinonaktifkan'); break;

        case 'hapus':
            // Jangan hapus admin
            $chk = $koneksi->prepare("SELECT role FROM users WHERE id=?");
            $chk->bind_param("i",$id); $chk->execute();
            $row = $chk->get_result()->fetch_assoc(); $chk->close();
            if ($row['role'] === 'admin') {
                header('Location: index.php?err=Tidak+bisa+hapus+admin'); exit;
            }
            $del = $koneksi->prepare("DELETE FROM users WHERE id=? AND role!='admin'");
            $del->bind_param("i",$id); $del->execute(); $del->close();
            header('Location: index.php?msg=Akun+berhasil+dihapus'); break;

        default:
            header('Location: index.php?err=Aksi+tidak+valid');
    }
    $koneksi->close();
} catch (Throwable $e) {
    header('Location: index.php?err=' . urlencode($e->getMessage()));
}
exit;
