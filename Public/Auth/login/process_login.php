<?php
session_start();

include '../koneksi.php';
$email = $_POST['email'];
$password = $_POST['password'];

//Ambil user dari database
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE email='$email' LIMIT 1");
$user = mysqli_fetch_assoc($query);

if ($user) {
    //Cek password
    if (password_verify($password, $user['password'])) {
        //Password benar, set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        //Redirect berdasarkan role
        if ($user['role'] === 'admin') {
            header('Location: ../../Views/role/admin/dashboard.php');
        } else {
            header('Location: ../../Views/role/buyer/dashboard.php');
        }
        exit();
    } else {
        //Password salah
        echo "<script>alert('Password salah. Silakan coba lagi.'); window.location.href='../../Views/auth/login.php';</script>";
        exit();
    }
} else {
    //User tidak ditemukan
    echo "<script>alert('Email tidak ditemukan. Silakan coba lagi.'); window.location.href='../../Views/auth/login.php';</script>";
    exit();
}

?>