<?php
/**
 * CareDrop – backend/aksi_tawaran.php
 * Yayasan: setujui atau tolak tawaran donasi
 * Donatur: input nomor resi setelah tawaran disetujui
 */
ob_start(); ini_set('display_errors',0); error_reporting(0);
session_start(); require_once __DIR__.'/koneksi.php'; ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) { echo json_encode(['ok'=>false,'error'=>'Belum login']); exit; }

$aksi      = $_POST['aksi']      ?? '';
$donasi_id = htmlspecialchars(trim($_POST['donasi_id'] ?? ''));
$user_id   = (int)$_SESSION['id'];
$role      = $_SESSION['role'];

if (empty($donasi_id)) { echo json_encode(['ok'=>false,'error'=>'ID donasi tidak valid']); exit; }

try {
    switch ($aksi) {

        // ── Yayasan: setujui tawaran ──
        case 'setujui':
            if ($role !== 'penerima') { echo json_encode(['ok'=>false,'error'=>'Akses ditolak']); exit; }
            $stmt = $koneksi->prepare(
                "UPDATE donasi d
                 JOIN katalog_kebutuhan k ON k.id = d.katalog_id
                 SET d.status_donasi = 'disetujui'
                 WHERE d.id = ? AND k.yayasan_id = ? AND d.status_donasi = 'menunggu'"
            );
            $stmt->bind_param("si", $donasi_id, $user_id);
            $stmt->execute();
            $affected = $stmt->affected_rows; $stmt->close();
            if ($affected > 0) echo json_encode(['ok'=>true,'msg'=>'Tawaran berhasil disetujui! Donatur akan memasukkan resi pengiriman.']);
            else echo json_encode(['ok'=>false,'error'=>'Donasi tidak ditemukan atau bukan milik yayasan ini']);
            break;

        // ── Yayasan: tolak tawaran ──
        case 'tolak':
            if ($role !== 'penerima') { echo json_encode(['ok'=>false,'error'=>'Akses ditolak']); exit; }
            $alasan = htmlspecialchars(trim($_POST['alasan'] ?? 'Tidak sesuai kebutuhan'));
            $stmt = $koneksi->prepare(
                "UPDATE donasi d
                 JOIN katalog_kebutuhan k ON k.id = d.katalog_id
                 SET d.status_donasi = 'ditolak', d.alasan_tolak = ?
                 WHERE d.id = ? AND k.yayasan_id = ? AND d.status_donasi = 'menunggu'"
            );
            $stmt->bind_param("ssi", $alasan, $donasi_id, $user_id);
            $stmt->execute();
            $affected = $stmt->affected_rows; $stmt->close();
            if ($affected > 0) echo json_encode(['ok'=>true,'msg'=>'Tawaran ditolak.']);
            else echo json_encode(['ok'=>false,'error'=>'Donasi tidak ditemukan']);
            break;

        // ── Donatur: input nomor resi ──
        case 'input_resi':
            if ($role !== 'donatur') { echo json_encode(['ok'=>false,'error'=>'Akses ditolak']); exit; }
            $no_resi = htmlspecialchars(trim($_POST['no_resi'] ?? ''));
            $kurir   = htmlspecialchars(trim($_POST['kurir']   ?? ''));
            if (empty($no_resi)) { echo json_encode(['ok'=>false,'error'=>'Nomor resi tidak boleh kosong']); exit; }

            // Cek donasi milik donatur & status disetujui
            $chk = $koneksi->prepare(
                "SELECT id FROM donasi WHERE id=? AND donatur_id=? AND status_donasi='disetujui'"
            );
            $chk->bind_param("si", $donasi_id, $user_id);
            $chk->execute();
            $found = $chk->get_result()->fetch_assoc(); $chk->close();
            if (!$found) { echo json_encode(['ok'=>false,'error'=>'Donasi tidak ditemukan atau belum disetujui']); exit; }

            // Update status donasi → dikirim
            $upd = $koneksi->prepare("UPDATE donasi SET status_donasi='dikirim' WHERE id=?");
            $upd->bind_param("s", $donasi_id); $upd->execute(); $upd->close();

            // Update atau insert resi di tabel pengiriman
            $cekP = $koneksi->prepare("SELECT id FROM pengiriman WHERE donasi_id=?");
            $cekP->bind_param("s",$donasi_id); $cekP->execute();
            $existP = $cekP->get_result()->fetch_assoc(); $cekP->close();

            if ($existP) {
                $updP = $koneksi->prepare("UPDATE pengiriman SET no_resi=?, kurir=? WHERE donasi_id=?");
                $updP->bind_param("sss",$no_resi,$kurir,$donasi_id); $updP->execute(); $updP->close();
            } else {
                $insP = $koneksi->prepare(
                    "INSERT INTO pengiriman (donasi_id, no_resi, kurir, tipe_layanan) VALUES (?,?,?,'reguler')"
                );
                $insP->bind_param("sss",$donasi_id,$no_resi,$kurir); $insP->execute(); $insP->close();
            }
            echo json_encode(['ok'=>true,'msg'=>'Nomor resi berhasil disimpan! Status donasi: Dikirim.', 'resi'=>$no_resi]);
            break;

        default:
            echo json_encode(['ok'=>false,'error'=>'Aksi tidak dikenali']);
    }
    $koneksi->close();
} catch(Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
