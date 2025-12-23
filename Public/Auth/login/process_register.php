<?php
include '../koneksi.php';

$name = $_POST['name'];
$email = $_POST['email'];
$password = $_POST['password'];
$phone_number = $_POST['phone_number'];
$address = $_POST['address'];

// 1. Cek email sudah terdaftar atau belum
$cek_email = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email'");
if(mysqli_num_rows($cek_email) > 0){
    echo "<script>alert('Email sudah terdaftar. Silakan gunakan email lain.'); window.location.href='../../Views/guest/register.php';</script>";
    exit();
}

// 2. Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 3. Siapkan query insert
$query_sql = "INSERT INTO users (name, email, password, phone_number, address, role) VALUES ('$name', '$email', '$hashed_password', '$phone_number', '$address', 'buyer')";

// 4. Jalankan query sekali saja di dalam IF
if(mysqli_query($koneksi, $query_sql)) {
    echo "<script>alert('Registrasi berhasil. Silakan login.'); window.location.href='../../Views/guest/login.php';</script>";
} else {
    echo 'error: ' . mysqli_error($koneksi);
}
?>