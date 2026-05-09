<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'caredrop';

$koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);

if($koneksi->connect_error){
    die("KONEKSI KE DATABASE GAGAL: " . $koneksi->connect_error);
}
?>