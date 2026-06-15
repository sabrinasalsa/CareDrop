<?php
session_start();
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

$tables = ['donasi','users','katalog_kebutuhan','pengiriman'];
$result = ['session' => $_SESSION, 'tables' => []];

foreach ($tables as $t) {
    $res = $pdo->query("SHOW COLUMNS FROM `$t`");
    if ($res) {
        $cols = [];
        while ($r = $res->fetch(PDO::FETCH_ASSOC)) $cols[] = $r['Field'] . ' (' . $r['Type'] . ')';
        // Ambil 3 baris contoh data
        $sample = [];
        $s = $pdo->query("SELECT * FROM `$t` LIMIT 3");
        if ($s) while ($r = $s->fetch(PDO::FETCH_ASSOC)) $sample[] = $r;
        $result['tables'][$t] = ['columns' => $cols, 'sample' => $sample];
    } else {
        $result['tables'][$t] = ['error' => 'Tabel tidak ditemukan'];
    }
}

$pdo = null;
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
