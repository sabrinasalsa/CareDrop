<?php
require_once dirname(__DIR__) . '/backend/koneksi.php';
// Fix donasi dengan status kosong, set ke 'menunggu'
$stmt = $pdo->prepare("UPDATE donasi SET status_donasi = 'menunggu' WHERE status_donasi = '' OR status_donasi IS NULL");
$stmt->execute();
echo "Rows affected: " . $stmt->rowCount() . PHP_EOL;
// Cek hasilnya
$rows = $pdo->query('SELECT id, status_donasi FROM donasi')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['id'] . ' => [' . $r['status_donasi'] . ']' . PHP_EOL;
}
