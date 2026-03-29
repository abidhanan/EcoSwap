<?php
$hostname = "localhost";
$username = "root";
$password = "";
$dbname   = "ecoswap";

$koneksi = mysqli_connect($hostname, $username, $password, $dbname);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>