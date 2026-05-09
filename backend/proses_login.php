<?php
session_start();
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])) {
            // Simpan data user ke session
            $_SESSION['id']    = $row['id'];
            $_SESSION['nama']  = $row['nama_lengkap'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role']  = $row['role'];

            // Cek status verifikasi untuk penerima
            if ($row['role'] === 'penerima' && isset($row['status_verifikasi']) && $row['status_verifikasi'] === 'pending') {
                echo "<script>alert('Akun Anda masih menunggu verifikasi Admin. Silakan tunggu konfirmasi.'); window.location.href='../index.php';</script>";
                exit;
            }

            // Redirect ke index — JS akan auto-login lewat PHP_SESSION
            header("Location: ../index.php");
            exit;

        } else {
            echo "<script>alert('Kata sandi salah!'); window.location.href='../index.php';</script>";
        }
    } else {
        echo "<script>alert('Email tidak ditemukan!'); window.location.href='../index.php';</script>";
    }
    $stmt->close();
}
$koneksi->close();
?>