<?php
echo '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin Barang Bekas & Lelang</title>
    <link rel="stylesheet" href="../dashboard/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo">Admin Ecoswap</div>
        <nav>
            <a href="../dashboard/dashboard.html" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="../produk&stok/produk&stok.html"><i class="fas fa-box"></i> Produk & Stok</a>
            <a href="../pengguna/pengguna.html"><i class="fas fa-users"></i> Pengguna</a>
            <a href="../transaksi/transaksi.html"><i class="fas fa-exchange-alt"></i> Transaksi</a>
            <a href="../support/support.html"><i class="fas fa-comments"></i> Laporan & Support</a>
            <a href="../pengaturan/pengaturan.html"><i class="fas fa-cog"></i> Pengaturan</a>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <h1>Selamat Datang, Admin Utama</h1>
            <div class="user-info">
                <i class="fas fa-bell notification-icon open-modal-btn"></i>
                
                <a href="../pengaturan/pengaturan.html" class="profile-btn-header">
                    <img src="../gambar/dimas.jpg" alt="Profil" class="profile-img-header">
                </a>
            </div>
        </header>

        <section class="kpi-grid">
            <div class="kpi-card">
                <i class="fas fa-users icon-color-1"></i>
                <p>Total Pengguna</p>
                <h2>12,450</h2>
            </div>
            <div class="kpi-card">
                <i class="fas fa-shopping-bag icon-color-2"></i>
                <p>Produk Aktif</p>
                <h2>4,210</h2>
            </div>
            <div class="kpi-card">
                <i class="fas fa-chart-line icon-color-3"></i> 
                <p>Laporan</p>
                <h2>85</h2>
            </div>
            </section>

        <section class="main-stats-container">
            <div class="card chart-card">
                <h3>Tren Pendapatan</h3>
                <p>Data penjualan</p>
                <div class="placeholder-chart"></div>
            </div>

            <div class="card latest-card">
                <h3>Produk Menunggu Verifikasi</h3>
                <ul class="list-item-container">
                    <li>
                        <span class="item-name">Kamera DSLR Bekas</span>
                        <span class="item-status status-new">Baru</span>
                    </li>
                    <li>
                        <span class="item-name">Jam Tangan Vintage</span>
                        <span class="item-status status-lelang">Baru</span>
                    </li>
                    <li>
                        <span class="item-name">Sepeda Lipat</span>
                        <span class="item-status status-pending">Edit</span>
                    </li>
                </ul>
                <a href="../produk&stok/produk&stok.html" class="view-all-btn">Lihat Semua</a>
            </div>
        </section>

        <div id="notificationModal" class="modal">
            <div class="modal-content">
                <span class="close-btn notif-close-btn">&times;</span>
                <h2>Produk Menunggu Verifikasi</h2>
                
                <table class="data-table verification-table">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nama Barang</th>
                            <th>Kondisi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><div class="img-mini-thumbnail"><img src="../gambar/kamera_dslr.jpg" alt="Kamera" class="product-img-thumb"></div></td>
                            <td>Kamera DSLR Bekas</td>
                            <td>Sangat Baik</td>
                            <td>
                                <button class="action-btn verify-view-btn" 
                                        data-img-url="../gambar/kamera_dslr.jpg"
                                        data-item-name="Kamera DSLR Bekas - Canon XYZ"
                                        data-penjual="Toko Budi Gadget"
                                        data-harga="Rp 7.500.000"
                                        data-kondisi="9/10, Lensa Bersih"
                                        data-deskripsi="Kamera jarang pakai, beli tahun lalu. Kelengkapan full box, baterai 2, memory 32GB. Siap pakai.">
                                    Lihat
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><div class="img-mini-thumbnail"><img src="../gambar/jam_tangan_vintage.jpg" alt="Jam" class="product-img-thumb"></div></td>
                            <td>Jam Tangan Vintage</td>
                            <td>Baik</td>
                            <td>
                                <button class="action-btn verify-view-btn" 
                                        data-img-url="../gambar/jam_tangan_vintage.jpg"
                                        data-item-name="Jam Tangan Vintage RADO Original"
                                        data-penjual="Kolektor Jam Jakarta"
                                        data-harga="Rp 1.800.000"
                                        data-kondisi="Body 8/10, Mesin Normal"
                                        data-deskripsi="Jam tangan Rado vintage. Tali kulit baru, box tidak ada. Cocok untuk kolektor.">
                                    Lihat
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="detailModal" class="modal">
            <div class="modal-content detail-modal-size">
                <span class="close-btn detail-close-btn">&times;</span>
                <h2>Detail Verifikasi Produk</h2>
                <div class="product-detail-view">
                    <div class="detail-img-area">
                        <img id="detailProductImage" src="https://via.placeholder.com/250/FF5722/FFFFFF?text=FOTO+PRODUK" alt="Foto Produk" class="product-img-detail">
                    </div>
                    <div class="detail-info-area">
                        <h3 id="detailProductName"></h3>
                        
                        <p><strong>Penjual:</strong> <span id="detailProductPenjual"></span> | <strong>Harga:</strong> <span id="detailProductHarga"></span></p>
                        <p><strong>Kondisi:</strong> <span id="detailProductKondisi"></span></p>
                        <p><strong>Deskripsi:</strong> <span id="detailProductDeskripsi"></span></p>
                        
                        <div class="verification-actions">
                            <button class="action-btn verify-accept-btn"><i class="fas fa-check"></i> Verifikasi & Setujui</button>
                            <button class="action-btn verify-reject-btn"><i class="fas fa-times"></i> Tolak Produk</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
    <script>
        document.addEventListener(\'DOMContentLoaded\', function() {
            const notificationModal = document.getElementById("notificationModal");
            const detailModal = document.getElementById("detailModal");
            const notificationBtn = document.querySelector(".notification-icon");

            const detailImage = document.getElementById("detailProductImage");
            const detailName = document.getElementById("detailProductName");
            const detailPenjual = document.getElementById("detailProductPenjual");
            const detailHarga = document.getElementById("detailProductHarga");
            const detailKondisi = document.getElementById("detailProductKondisi");
            const detailDeskripsi = document.getElementById("detailProductDeskripsi");
            
            if (!notificationModal || !detailModal) return;

            const notifCloseBtn = notificationModal.querySelector(".close-btn");
            const detailCloseBtn = detailModal.querySelector(".close-btn");

            if (notificationBtn) {
                notificationBtn.onclick = function() { notificationModal.style.display = "block"; }
            }
            if (notifCloseBtn) {
                notifCloseBtn.onclick = function() { notificationModal.style.display = "none"; }
            }
            if (detailCloseBtn) {
                detailCloseBtn.onclick = function() { detailModal.style.display = "none"; }
            }
            
            document.querySelectorAll(\'.verify-view-btn\').forEach(button => {
                button.addEventListener(\'click\', function() {
                    const imgUrl = this.getAttribute(\'data-img-url\');
                    const itemName = this.getAttribute(\'data-item-name\');
                    const itemPenjual = this.getAttribute(\'data-penjual\');
                    const itemHarga = this.getAttribute(\'data-harga\');
                    const itemKondisi = this.getAttribute(\'data-kondisi\');
                    const itemDeskripsi = this.getAttribute(\'data-deskripsi\');

                    if (detailModal) {
                        if (detailImage) detailImage.src = imgUrl;
                        if (detailName) detailName.textContent = itemName; 
                        
                        if (detailPenjual) detailPenjual.textContent = itemPenjual; 
                        if (detailHarga) detailHarga.textContent = itemHarga; 
                        if (detailKondisi) detailKondisi.textContent = itemKondisi;
                        if (detailDeskripsi) detailDeskripsi.textContent = itemDeskripsi;

                        notificationModal.style.display = "none"; 
                        detailModal.style.display = "block";
                    }
                });
            });

            const verifyAcceptBtn = document.querySelector(".verify-accept-btn");
            const verifyRejectBtn = document.querySelector(".verify-reject-btn");
            
            if (verifyAcceptBtn) {
                verifyAcceptBtn.addEventListener(\'click\', function() {
                    const itemName = document.getElementById("detailProductName").textContent;
                    alert(`Produk "${itemName}" berhasil diverifikasi dan disetujui!`);
                    detailModal.style.display = "none";
                });
            }

            if (verifyRejectBtn) {
                verifyRejectBtn.addEventListener(\'click\', function() {
                    const itemName = document.getElementById("detailProductName").textContent;
                    alert(`Produk "${itemName}" telah DITOLAK. Status dikembalikan ke penjual.`);
                    detailModal.style.display = "none";
                });
            }

            window.onclick = function(event) {
                if (event.target == notificationModal) { notificationModal.style.display = "none"; }
                if (event.target == detailModal) { detailModal.style.display = "none"; }
            }
        });
    </script>
</body>
</html>
';
?>