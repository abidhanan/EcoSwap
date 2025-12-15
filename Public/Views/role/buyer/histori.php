<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histori Aktivitas - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/histori.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <div class="app-layout">
        
        <!-- ========== SIDEBAR ========== -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo" onclick="goToDashboard()" style="cursor:pointer;">
                    ECO<span>SWAP</span>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="profil.php" class="menu-link">
                        <i class="fas fa-user"></i>
                        <span>Biodata Diri</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="alamat.php" class="menu-link">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Alamat</span>
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="histori.php" class="menu-link">
                        <i class="fas fa-history"></i>
                        <span>Histori</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="../seller/dashboard.php" class="menu-link">
                        <i class="fas fa-store"></i>
                        <span>Toko Saya</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="../../guest/login.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <main class="main-content-wrapper">
            <!-- HEADER -->
            <div class="header">
                <div class="page-title">Histori Aktivitas</div>
            </div>

            <!-- SCROLLABLE CONTENT -->
            <div class="content">
                <div class="history-list">
                    
                    <!-- ITEM 1: JUAL -->
                    <div class="history-card">
                        <div class="card-left">
                            <div class="item-icon">
                                <i class="fas fa-bicycle"></i>
                            </div>
                            <div class="item-info">
                                <div class="badge-container">
                                    <span class="type-badge type-jual">Jual</span>
                                    <span class="status-badge status-selesai">Selesai</span>
                                </div>
                                <div class="item-title">Sepeda Fixie Bekas</div>
                                <div class="item-date">24 Nov 2024</div>
                            </div>
                        </div>
                        <div class="card-right">
                            <div class="item-price">Rp 1.200.000</div>
                            <button class="btn-detail" onclick="openDetail(1)">Detail</button>
                        </div>
                    </div>

                    <!-- ITEM 2: BELI -->
                    <div class="history-card">
                        <div class="card-left">
                            <div class="item-icon">
                                <i class="fas fa-laptop"></i>
                            </div>
                            <div class="item-info">
                                <div class="badge-container">
                                    <span class="type-badge type-beli">Beli</span>
                                    <span class="status-badge status-proses">Sedang Dikirim</span>
                                </div>
                                <div class="item-title">Laptop Asus ROG</div>
                                <div class="item-date">22 Nov 2024</div>
                            </div>
                        </div>
                        <div class="card-right">
                            <div class="item-price">Rp 8.500.000</div>
                            <button class="btn-detail" onclick="openDetail(2)">Detail</button>
                        </div>
                    </div>

                    <!-- ITEM 3: JUAL -->
                    <div class="history-card">
                        <div class="card-left">
                            <div class="item-icon">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="item-info">
                                <div class="badge-container">
                                    <span class="type-badge type-jual">Jual</span>
                                    <span class="status-badge status-selesai">Selesai</span>
                                </div>
                                <div class="item-title">Kamera Canon DSLR</div>
                                <div class="item-date">10 Nov 2024</div>
                            </div>
                        </div>
                        <div class="card-right">
                            <div class="item-price">Rp 3.100.000</div>
                            <button class="btn-detail" onclick="openDetail(3)">Detail</button>
                        </div>
                    </div>

                    <!-- ITEM 4: BELI -->
                    <div class="history-card">
                        <div class="card-left">
                            <div class="item-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="item-info">
                                <div class="badge-container">
                                    <span class="type-badge type-beli">Beli</span>
                                    <span class="status-badge status-selesai">Selesai</span>
                                </div>
                                <div class="item-title">Novel Harry Potter</div>
                                <div class="item-date">01 Nov 2024</div>
                            </div>
                        </div>
                        <div class="card-right">
                            <div class="item-price">Rp 150.000</div>
                            <button class="btn-detail" onclick="openDetail(4)">Detail</button>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- MODAL DETAIL POP-UP -->
    <div id="detailModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">Detail Transaksi</div>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <div id="modalContent">
                <!-- Konten akan diisi oleh JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // DATA HISTORI (Simulasi Database)
        const historyData = [
            {
                id: 1,
                type: 'jual',
                item: 'Sepeda Fixie Bekas',
                price: 'Rp 1.200.000',
                desc: 'Sepeda fixie warna hitam doff, kondisi mulus 90%, ban baru diganti bulan lalu. Rem torpedo berfungsi baik.',
                shipping: 'Ambil Sendiri (COD)',
                counterparty: 'Budi Santoso' // Nama Pembeli
            },
            {
                id: 2,
                type: 'beli',
                item: 'Laptop Asus ROG',
                price: 'Rp 8.500.000',
                desc: 'Laptop gaming bekas pemakaian wajar, RAM 16GB, SSD 512GB. Kelengkapan fullset dus dan charger ori.',
                shipping: 'JNE Reguler',
                counterparty: 'Toko Komputer Jaya' // Nama Penjual
            },
            {
                id: 3,
                type: 'jual',
                item: 'Kamera Canon DSLR',
                price: 'Rp 3.100.000',
                desc: 'Kamera Canon 600D Lensa Kit 18-55mm. Shutter count rendah. Bonus tas kamera.',
                shipping: 'J&T Express',
                counterparty: 'Siti Aminah' // Nama Pembeli
            },
            {
                id: 4,
                type: 'beli',
                item: 'Novel Harry Potter',
                price: 'Rp 150.000',
                desc: 'Novel Harry Potter and the Philosophers Stone edisi hardcover bahasa Inggris.',
                shipping: 'SiCepat',
                counterparty: 'BookStore ID' // Nama Penjual
            }
        ];

        // Navigasi Sidebar
        function goToDashboard() {
            // alert("Navigasi ke Dashboard");
            window.location.href = 'dashboard.php';
        }

        // Logic Modal
        const modal = document.getElementById('detailModal');
        const modalContent = document.getElementById('modalContent');

        function openDetail(id) {
            // 1. Cari data berdasarkan ID
            const data = historyData.find(item => item.id === id);

            if (data) {
                // 2. Tentukan Label (Pembeli vs Penjual) berdasarkan tipe
                let counterpartyLabel = "";
                let typeText = "";
                
                if (data.type === 'jual') {
                    counterpartyLabel = "Nama Pembeli";
                    typeText = "Penjualan";
                } else {
                    counterpartyLabel = "Nama Penjual";
                    typeText = "Pembelian";
                }

                // 3. Render HTML ke dalam modal
                modalContent.innerHTML = `
                    <div class="detail-row">
                        <span class="detail-label">Tipe Transaksi</span>
                        <span class="detail-value" style="text-transform:uppercase;">${typeText}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Nama Barang</span>
                        <span class="detail-value">${data.item}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Metode Pengiriman</span>
                        <span class="detail-value">${data.shipping}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">${counterpartyLabel}</span>
                        <span class="detail-value">${data.counterparty}</span>
                    </div>
                    
                    <div style="margin-top: 15px; font-weight: 600; color: #666;">Deskripsi Barang:</div>
                    <div class="detail-desc">
                        ${data.desc}
                    </div>

                    <div class="detail-total">
                        <span>Total Harga</span>
                        <span>${data.price}</span>
                    </div>
                `;
                

                // 4. Tampilkan Modal
                modal.classList.add('open');
            }
        }

        function closeModal() {
            modal.classList.remove('open');
        }

        // Tutup modal jika klik di luar area konten
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>