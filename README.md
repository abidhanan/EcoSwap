# 🌱 EcoSwap

**EcoSwap** adalah platform marketplace untuk jual beli barang bekas yang bertujuan mendukung gaya hidup ramah lingkungan.

## 🛠️ Teknologi yang Digunakan
* **Bahasa Pemrograman:** PHP
* **Database:** MySQL
* **Frontend:** HTML, CSS, JavaScript
* **Server Local:** XAMPP / Laragon

---

## 📋 Prasyarat (Requirements)
Sebelum menjalankan proyek ini, pastikan Anda telah menginstal:
1.  **XAMPP** (PHP 8.x disarankan) atau **Laragon**.
2.  **Web Browser** (Chrome, Edge, atau Firefox).
3.  **Text Editor** (VS Code, Sublime Text, dll) - *Opsional untuk melihat kode.*

---

## 🚀 Cara Menjalankan Project

Pilih salah satu metode di bawah ini sesuai aplikasi yang Anda gunakan.

### 🅰️ Menggunakan XAMPP

1.  **Download & Ekstrak**
    * Download source code project ini (ZIP) atau clone menggunakan Git.
    * Pindahkan folder `EcoSwap` ke dalam folder `htdocs` di instalasi XAMPP Anda.
    * *(Biasanya di: `C:\xampp\htdocs\EcoSwap`)*

2.  **Siapkan Database**
    * Buka aplikasi **XAMPP Control Panel**.
    * Klik **Start** pada modul **Apache** dan **MySQL**.
    * Buka browser dan akses: `http://localhost/phpmyadmin`.
    * Buat database baru dengan nama: **`ecoswap`** (atau sesuaikan dengan file config).
    * Pilih tab **Import**, lalu pilih file database (biasanya berekstensi `.sql` di dalam folder project, misal: `db_ecoswap.sql`) dan klik **Import**.

3.  **Konfigurasi Koneksi**
    * Cek file koneksi database di folder project (biasanya bernama `koneksi.php`, `config.php`, atau `db.php`).
    * Pastikan settingan berikut sesuai:
        ```php
        $host = "localhost";
        $user = "root";
        $pass = ""; // Kosongkan jika default XAMPP
        $db   = "ecoswap"; // Sesuaikan dengan nama database yang dibuat tadi
        ```

4.  **Jalankan Project**
    * Buka browser dan ketik: `http://localhost/EcoSwap`

---

### 🅱️ Menggunakan Laragon (Lebih Mudah)

1.  **Simpan Project**
    * Pindahkan folder `EcoSwap` ke dalam folder `www` di instalasi Laragon Anda.
    * *(Biasanya di: `C:\laragon\www\EcoSwap`)*

2.  **Jalankan Laragon**
    * Buka aplikasi **Laragon**.
    * Klik tombol **Start All**.
    * Laragon biasanya akan mendeteksi folder baru dan membuat "Pretty URL" secara otomatis (misal: `http://ecoswap.test`).

3.  **Siapkan Database**
    * Klik tombol **Database** di Laragon (akan membuka HeidiSQL atau phpMyAdmin).
    * Buat database baru dengan nama: **`ecoswap`**.
    * Jalankan file `.sql` yang ada di folder project untuk mengisi tabel (Import file SQL).

4.  **Konfigurasi & Run**
    * Pastikan konfigurasi di file koneksi PHP (`koneksi.php` atau sejenisnya) sudah sesuai dengan username/password MySQL di Laragon (Default Laragon: User=`root`, Password=`` (kosong)).
    * Akses project melalui browser di: `http://ecoswap.test` (atau `http://localhost/EcoSwap`).