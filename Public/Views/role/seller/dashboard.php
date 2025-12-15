<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Saya - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/dashboard.css">
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
                    <a href="../buyer/profil.php" class="menu-link">
                        <i class="fas fa-user"></i>
                        <span>Biodata Diri</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="../buyer/alamat.php" class="menu-link">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Alamat</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="../buyer/histori.php" class="menu-link">
                        <i class="fas fa-history"></i>
                        <span>Histori</span>
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="dashboard.php" class="menu-link">
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
                <div class="page-title">Portal Penjual</div>
            </div>

            <!-- SCROLLABLE CONTENT -->
            <div class="content">
                <div class="shop-container">
                    
                    <!-- SHOP PROFILE HEADER (Ikon Profil Pojok Kiri Atas Konten) -->
                    <div class="shop-header-card">
                        <div class="shop-avatar-container">
                            <img src="../../../Assets/img/role/seller/foto_profil.jpg" alt="Shop Logo" class="shop-avatar">
                            <div class="shop-status-dot" title="Online"></div>
                        </div>
                        <div class="shop-info">
                            <h2>Dimas Store</h2>
                            <div class="shop-rating">
                                <span><i class="fas fa-star"></i> Rating</span>
                                <span>4.8</span>
                            </div>
                        </div>
                        <a href="toko.php" class="btn-visit-shop">Lihat Toko <i class="fas fa-external-link-alt"></i></a>
                    </div>

                    <!-- MENU GRID -->
                    <div class="shop-menu-grid">
                        
                        <!-- 1. Produk -->
                        <a href="produkSaya.php" class="shop-menu-item">
                            <div class="menu-icon-circle icon-products">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <span class="menu-label">Produk Saya</span>
                        </a>

                        <!-- 2. Pesanan -->
                        <a href="pesanan.php" class="shop-menu-item">
                            <div class="menu-icon-circle icon-orders">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <span class="menu-label">Pesanan</span>
                        </a>

                        <!-- 3. Keuangan -->
                        <a href="keuangan.php" class="shop-menu-item">
                            <div class="menu-icon-circle icon-finance">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <span class="menu-label">Keuangan</span>
                        </a>

                        <!-- 4. Performa Toko -->
                        <a href="performa.php" class="shop-menu-item">
                            <div class="menu-icon-circle icon-performance">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <span class="menu-label">Performa Toko</span>
                        </a>

                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        function goToDashboard() {
            // Sesuaikan path ini dengan struktur folder Anda sebenarnya
            window.location.href = '../buyer/dashboard.php';
        }
    </script>
</body>
</html>