<?php
session_start();
$conn = new mysqli ("localhost", "root", "", "ecoswap");

if (isset($_POST['submit'])) {
  $uname = $_POST['username'];
  $pass = md5($_POST['password']);

$q = mysqli_query($conn, "SELECT * FROM users WHERE username = '$uname' AND password = '$pass' ");

if ($q->num_rows > 0) {
  $d = $q->fetch_assoc();
  if ($d['role'] == 'admin') {
    header('Location: Buyer/newDashboard/dashboard.php');
    $_SESSION['admin_name'] = $d['name'];
  } else if ($d['role'] == 'buyer') {
    header('Location: buyer/dashboard.php');
    $_SESSION['buyer_name'] = $d['name'];
  } else if ($d['role'] == 'seller') {
    header('Location: seller/dashboard.php');
    $_SESSION['seller_name'] = $d['name'];
  }
} else {
  $_SESSION['error'] = "Username atau Password tidak valid!";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Buyer/daftar&login/daftar&login.css">
    <title>Masuk ke Akun</title>
    
</head>
<body>
    <div class="auth-container">
        <h2>Masuk ke Akun</h2>
        <form action="/login" method="POST">
            <div class="form-group">
                <label for="email_login">Email</label>
                <input type="email" id="email_login" name="email" required>
            </div>
            <div class="form-group">
                <label for="password_login">Password</label>
                <input type="password" id="password_login" name="password" required>
            </div>
            <button type="submit" class="submit-btn">Masuk</button>
        </form>
        <p class="alt-action">
            Belum punya akun? <a href="../daftar&login/daftar.html">Daftar</a>
        </p>
    </div>
</body>
</html>