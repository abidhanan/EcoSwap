<?php
include '../koneksi.php';
$email = $_POST['email'];
$password = $_POST['password'];
$phone_number = $_POST['phone_number'];
$address = $_POST['address'];

//Cek email sudah terdaftar atau belum
$cek_email = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email'");
if(mysqli_num_rows($cek_email) > 0){
    echo "<script>alert('Email sudah terdaftar. Silakan gunakan email lain.'); window.location.href='../../Public/Views/guest/register.php';</script>";
    exit();
}

//Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

//Simpan data user baru
$simpan = mysqli_query($koneksi, "INSERT INTO users (email, password, phone_number, address, role) VALUES ('$email', '$hashed_password', '$phone_number', '$address', 'buyer')");
if(mysqli_query($koneksi, $simpan)) {
    echo "<script>alert('Registrasi berhasil. Silakan login.'); window.location.href='../../Public/Views/auth/login.php';</script>";
} else {
    echo 'error: ' . mysqli_error($koneksi);}
?>