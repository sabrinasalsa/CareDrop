<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'caredrop';

$koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);
$koneksi->set_charset('utf8mb4');

if ($koneksi->connect_error) {
    // Jika dipanggil dari API endpoint, kembalikan JSON error
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    die("KONEKSI KE DATABASE GAGAL: " . $koneksi->connect_error);
}
?>
