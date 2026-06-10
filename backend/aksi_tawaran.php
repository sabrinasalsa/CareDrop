<?php

session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

// ── Autentikasi ──
if (!isset($_SESSION['id'])) { json_error('Belum login', 401); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method tidak valid', 405); }


$aksi      = trim($_POST['aksi']      ?? '');
$donasi_id = trim($_POST['donasi_id'] ?? '');
$user_id   = (int)$_SESSION['id'];
$role      = $_SESSION['role'] ?? '';

// Validasi dasar
if (empty($donasi_id) || !preg_match('/^CDR-\d{8}-[A-Z0-9]{6}$/', $donasi_id)) {
    json_error('ID donasi tidak valid');
}

try {
    switch ($aksi) {

        // ── Yayasan: Setujui tawaran ──
        case 'setujui':
            if ($role !== 'penerima') { json_error('Akses ditolak', 403); }

            $stmt = $pdo->prepare(
                "UPDATE donasi d
                 JOIN katalog_kebutuhan k ON k.id = d.katalog_id
                 SET d.status_donasi = 'disetujui'
                 WHERE d.id = ? AND k.yayasan_id = ? AND d.status_donasi = 'menunggu'"
            );
            $stmt->execute([$donasi_id, $user_id]);

            if ($stmt->rowCount() > 0)
                echo json_encode(['ok' => true, 'msg' => 'Tawaran disetujui. Donatur akan segera mengirim barang dan memasukkan nomor resi.']);
            else
                json_error('Donasi tidak ditemukan atau sudah diproses.');
            break;

        // ── Yayasan: Tolak tawaran ──
        case 'tolak':
            if ($role !== 'penerima') { json_error('Akses ditolak', 403); }

            $alasan = htmlspecialchars(trim($_POST['alasan'] ?? 'Tidak sesuai kebutuhan kami saat ini.'), ENT_QUOTES, 'UTF-8');
            if (strlen($alasan) > 500) { json_error('Alasan terlalu panjang (maks 500 karakter)'); }

            $stmt = $pdo->prepare(
                "UPDATE donasi d
                 JOIN katalog_kebutuhan k ON k.id = d.katalog_id
                 SET d.status_donasi = 'ditolak', d.alasan_tolak = ?
                 WHERE d.id = ? AND k.yayasan_id = ? AND d.status_donasi = 'menunggu'"
            );
            $stmt->execute([$alasan, $donasi_id, $user_id]);

            if ($stmt->rowCount() > 0)
                echo json_encode(['ok' => true, 'msg' => 'Tawaran ditolak.']);
            else
                json_error('Donasi tidak ditemukan atau sudah diproses.');
            break;

        // ── Donatur: Input resi setelah barang dikirim ──
        case 'input_resi':
            if ($role !== 'donatur') { json_error('Akses ditolak', 403); }

            $kurir   = htmlspecialchars(trim($_POST['kurir']   ?? ''), ENT_QUOTES, 'UTF-8');
            $no_resi = htmlspecialchars(trim($_POST['no_resi'] ?? ''), ENT_QUOTES, 'UTF-8');

            if (empty($kurir))   { json_error('Nama ekspedisi wajib diisi'); }
            if (empty($no_resi)) { json_error('Nomor resi wajib diisi'); }
            if (strlen($kurir) > 100)   { json_error('Nama ekspedisi terlalu panjang'); }
            if (strlen($no_resi) > 100) { json_error('Nomor resi terlalu panjang'); }

            // Pastikan donasi milik donatur ini dan sudah disetujui
            $chk = $pdo->prepare(
                "SELECT id FROM donasi WHERE id = ? AND donatur_id = ? AND status_donasi = 'disetujui'"
            );
            $chk->execute([$donasi_id, $user_id]);
            if (!$chk->fetch()) {
                json_error('Donasi tidak ditemukan atau belum disetujui yayasan');
            }

            // Update status donasi → dikirim
            $pdo->prepare("UPDATE donasi SET status_donasi = 'dikirim' WHERE id = ?")
                ->execute([$donasi_id]);

            // Cek apakah sudah ada record pengiriman
            $cekP = $pdo->prepare("SELECT id FROM pengiriman WHERE donasi_id = ?");
            $cekP->execute([$donasi_id]);
            $existP = $cekP->fetch();

            if ($existP) {
                $pdo->prepare("UPDATE pengiriman SET kurir = ?, no_resi = ?, tipe_layanan = 'mandiri' WHERE donasi_id = ?")
                    ->execute([$kurir, $no_resi, $donasi_id]);
            } else {
                $pdo->prepare("INSERT INTO pengiriman (donasi_id, kurir, no_resi, tipe_layanan) VALUES (?, ?, ?, 'mandiri')")
                    ->execute([$donasi_id, $kurir, $no_resi]);
            }

            $pdo = null;
            echo json_encode([
                'ok'   => true,
                'resi' => $no_resi,
                'msg'  => "Resi berhasil disimpan! Yayasan akan melacak paket via $kurir dengan resi $no_resi."
            ]);
            break;

        default:
            json_error('Aksi tidak dikenali');
    }

    $pdo = null;

} catch (PDOException $e) {
    $pdo = null;
    json_error('Server error. Silakan coba lagi.', 500);
}
