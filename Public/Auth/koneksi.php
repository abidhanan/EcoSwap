<?php
/**
 * File Koneksi Database EcoSwap
 * Disesuaikan untuk Hosting InfinityFree
 */

// 1. HOSTNAME: Cek di menu "MySQL Databases" di dashboard InfinityFree. 
// Biasanya formatnya: sqlXXX.infinityfree.com (BUKAN localhost)
$hostname = "sql200.infinityfree.com"; // <-- GANTI SESUAI DATA DI DASHBOARD KAMU

// 2. USERNAME: Sesuai di screenshot kamu
$username = "if0_41502783"; 

// 3. PASSWORD: Sesuai di screenshot kamu (ecoswap123)
$password = "ecoswap123"; 

// 4. DATABASE NAME: Nama database yang kamu buat di menu "MySQL Databases"
// Biasanya formatnya: if0_41502783_xxx
$dbname   = "if0_41502783_ecoswap"; // <-- GANTI SESUAI NAMA DB YANG KAMU BUAT

$koneksi = mysqli_connect($hostname, $username, $password, $dbname);

// Cek Koneksi
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Set charset ke utf8 agar karakter khusus (seperti simbol Rupiah) tampil benar
mysqli_set_charset($koneksi, "utf8");

?>