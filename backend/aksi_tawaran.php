<?php
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

        //Yayasan: Setujui tawaran
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
            if ($affected > 0)
                echo json_encode(['ok'=>true,'msg'=>'Tawaran disetujui. Donatur akan segera mengirim barang dan memasukkan nomor resi.']);
            else
                echo json_encode(['ok'=>false,'error'=>'Donasi tidak ditemukan atau sudah diproses.']);
            break;

        // Yayasan: Tolak tawaran
        case 'tolak':
            if ($role !== 'penerima') { echo json_encode(['ok'=>false,'error'=>'Akses ditolak']); exit; }
            $alasan = htmlspecialchars(trim($_POST['alasan'] ?? 'Tidak sesuai kebutuhan kami saat ini.'));
            $stmt = $koneksi->prepare(
                "UPDATE donasi d
                 JOIN katalog_kebutuhan k ON k.id = d.katalog_id
                 SET d.status_donasi = 'ditolak', d.alasan_tolak = ?
                 WHERE d.id = ? AND k.yayasan_id = ? AND d.status_donasi = 'menunggu'"
            );
            $stmt->bind_param("ssi", $alasan, $donasi_id, $user_id);
            $stmt->execute();
            $affected = $stmt->affected_rows; $stmt->close();
            if ($affected > 0)
                echo json_encode(['ok'=>true,'msg'=>'Tawaran ditolak.']);
            else
                echo json_encode(['ok'=>false,'error'=>'Donasi tidak ditemukan atau sudah diproses.']);
            break;

        // Donatur: Input resi setelah barang dikirim
        case 'input_resi':
            if ($role !== 'donatur') { echo json_encode(['ok'=>false,'error'=>'Akses ditolak']); exit; }

            $kurir   = htmlspecialchars(trim($_POST['kurir']   ?? ''));
            $no_resi = htmlspecialchars(trim($_POST['no_resi'] ?? ''));

            if (empty($kurir) || empty($no_resi)) {
                echo json_encode(['ok'=>false,'error'=>'Nama ekspedisi dan nomor resi wajib diisi']); exit;
            }

            // Pastikan donasi milik donatur ini dan sudah disetujui
            $chk = $koneksi->prepare(
                "SELECT id FROM donasi WHERE id = ? AND donatur_id = ? AND status_donasi = 'disetujui'"
            );
            $chk->bind_param("si", $donasi_id, $user_id);
            $chk->execute();
            $found = $chk->get_result()->fetch_assoc(); $chk->close();

            if (!$found) {
                echo json_encode(['ok'=>false,'error'=>'Donasi tidak ditemukan atau belum disetujui yayasan']); exit;
            }

            // Update status donasi  dikirim
            $upd = $koneksi->prepare("UPDATE donasi SET status_donasi = 'dikirim' WHERE id = ?");
            $upd->bind_param("s", $donasi_id); $upd->execute(); $upd->close();

            // Cek apakah sudah ada record pengiriman
            $cekP = $koneksi->prepare("SELECT id FROM pengiriman WHERE donasi_id = ?");
            $cekP->bind_param("s", $donasi_id); $cekP->execute();
            $existP = $cekP->get_result()->fetch_assoc(); $cekP->close();

            if ($existP) {
                // Update record pengiriman yang ada
                $updP = $koneksi->prepare(
                    "UPDATE pengiriman SET kurir = ?, no_resi = ?, tipe_layanan = 'mandiri' WHERE donasi_id = ?"
                );
                $updP->bind_param("sss", $kurir, $no_resi, $donasi_id); $updP->execute(); $updP->close();
            } else {
                // Insert record pengiriman baru
                $insP = $koneksi->prepare(
                    "INSERT INTO pengiriman (donasi_id, kurir, no_resi, tipe_layanan) VALUES (?, ?, ?, 'mandiri')"
                );
                $insP->bind_param("sss", $donasi_id, $kurir, $no_resi); $insP->execute(); $insP->close();
            }

            $koneksi->close();
            echo json_encode([
                'ok'   => true,
                'resi' => $no_resi,
                'msg'  => "Resi berhasil disimpan! Yayasan akan melacak paket via $kurir dengan resi $no_resi."
            ]);
            break;

        default:
            echo json_encode(['ok'=>false,'error'=>'Aksi tidak dikenali']);
    }

    if (isset($koneksi) && !$koneksi->connect_error) {
        try { $koneksi->close(); } catch(Throwable $e) {}
    }

} catch(Throwable $e) {
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
