<?php
require_once 'koneksi.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $nama = htmlspecialchars($_POST['nama_lengkap']);
    $email = htmlspecialchars($_POST['email']);
    $no_telp = htmlspecialchars($_POST['no_telp']);
    $alamat = htmlspecialchars($_POST['alamat']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Tangkap pilihan role dari form
    $role = $_POST['role']; 
    
    // Logika verifikasi: Penerima (yayasan/posko) harus menunggu ACC Admin, Donatur langsung aktif
    $status_verifikasi = ($role === 'penerima') ? 'pending' : 'verified';

    if (empty($nama) || empty($email) || empty($_POST['password']) || empty($no_telp) || empty($role)) {
        echo "<script>alert('ERROR: ISI SEMUA DATA TERLEBIH DAHULU!'); window.location.href='../index.php';</script>";
    } else {
        $query = "INSERT INTO users (nama_lengkap, email, password, no_telp, alamat, role, status_verifikasi) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("sssssss", $nama, $email, $password, $no_telp, $alamat, $role, $status_verifikasi);
        
        try {
            if($stmt->execute()){
                if ($role === 'penerima') {
                    echo "<script>alert('Registrasi Berhasil! Akun Penerima Anda sedang menunggu verifikasi Admin.'); window.location.href='../index.php';</script>";
                } else {
                    echo "<script>alert('Registrasi CareDrop Berhasil! Selamat datang, " . $nama . "'); window.location.href='../index.php';</script>";
                }
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { 
                echo "<script>alert('ERROR: EMAIL SUDAH TERDAFTAR!'); window.location.href='../index.php';</script>";
            } else {
                echo "Error saat registrasi: " . $e->getMessage();
            }
        }
        $stmt->close();
    }
} else {
    header('Location: ../index.php');
    exit();
}
$koneksi->close();
?>