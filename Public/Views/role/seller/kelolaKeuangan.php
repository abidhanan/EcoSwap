<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Keuangan - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/kelolaKeuangan.css">
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
                <a href="../../../../index.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content-wrapper">
            <div class="header">
                <div class="page-title">Kelola Keuangan</div>
            </div>

            <div class="content">
                <div class="manage-container">
                    
                    <form onsubmit="saveSettings(event)">
                        
                        <!-- 1. BIAYA LAYANAN -->
                        <div class="manage-card">
                            <div class="card-title">
                                <i class="fas fa-concierge-bell" style="color:var(--primary);"></i> Biaya Layanan Toko
                            </div>

                            <div class="fee-row">
                                <div class="fee-label">
                                    <span class="fee-icon"><i class="fas fa-user-shield"></i></span> Biaya Admin / Transaksi
                                </div>
                                <div class="fee-input-wrapper">
                                    <span class="currency-prefix">Rp</span>
                                    <input type="number" class="fee-input" value="2500">
                                </div>
                            </div>

                            <div class="fee-row">
                                <div class="fee-label">
                                    <span class="fee-icon"><i class="fas fa-box-open"></i></span> Biaya Kemasan (Packing)
                                </div>
                                <div class="fee-input-wrapper">
                                    <span class="currency-prefix">Rp</span>
                                    <input type="number" class="fee-input" value="1000">
                                </div>
                            </div>
                        </div>

                        <!-- 2. BIAYA PENGIRIMAN -->
                        <div class="manage-card">
                            <div class="card-title">
                                <i class="fas fa-truck" style="color:var(--primary);"></i> Atur Nominal Pengiriman
                            </div>
                            
                            <!-- JNE -->
                            <div class="fee-row">
                                <div class="fee-label">
                                    <span class="fee-icon"><i class="fas fa-shipping-fast"></i></span> JNE Reguler
                                </div>
                                <div class="fee-input-wrapper">
                                    <span class="currency-prefix">Rp</span>
                                    <input type="number" class="fee-input" value="15000">
                                </div>
                            </div>

                            <!-- J&T -->
                            <div class="fee-row">
                                <div class="fee-label">
                                    <span class="fee-icon" style="color:#e60012;"><i class="fas fa-plane"></i></span> J&T Express
                                </div>
                                <div class="fee-input-wrapper">
                                    <span class="currency-prefix">Rp</span>
                                    <input type="number" class="fee-input" value="18000">
                                </div>
                            </div>

                            <!-- SiCepat -->
                            <div class="fee-row">
                                <div class="fee-label">
                                    <span class="fee-icon" style="color:#d32f2f;"><i class="fas fa-bolt"></i></span> SiCepat
                                </div>
                                <div class="fee-input-wrapper">
                                    <span class="currency-prefix">Rp</span>
                                    <input type="number" class="fee-input" value="12000">
                                </div>
                            </div>

                            <!-- GoSend -->
                            <div class="fee-row">
                                <div class="fee-label">
                                    <span class="fee-icon" style="color:#00aa13;"><i class="fas fa-motorcycle"></i></span> GoSend Instant
                                </div>
                                <div class="fee-input-wrapper">
                                    <span class="currency-prefix">Rp</span>
                                    <input type="number" class="fee-input" value="25000">
                                </div>
                            </div>

                            <!-- GrabExpress -->
                            <div class="fee-row">
                                <div class="fee-label">
                                    <span class="fee-icon" style="color:#00b14f;"><i class="fas fa-biking"></i></span> GrabExpress
                                </div>
                                <div class="fee-input-wrapper">
                                    <span class="currency-prefix">Rp</span>
                                    <input type="number" class="fee-input" value="24000">
                                </div>
                            </div>

                            <!-- AnterAja -->
                            <div class="fee-row">
                                <div class="fee-label">
                                    <span class="fee-icon" style="color:#500095;"><i class="fas fa-paper-plane"></i></span> AnterAja
                                </div>
                                <div class="fee-input-wrapper">
                                    <span class="currency-prefix">Rp</span>
                                    <input type="number" class="fee-input" value="11000">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-save">Simpan Perubahan</button>

                    </form>

                </div>
            </div>
        </main>
    </div>

    <script>
        function goToDashboard() {
            // Sesuaikan path ini dengan struktur folder Anda sebenarnya
            window.location.href = '../buyer/dashboard.php';
        }
        
        function safeNavigate(url) {
            try {
                window.location.href = url;
            } catch (e) {
                console.warn("Navigasi simulasi:", url);
                alert("Navigasi ke: " + url);
            }
        }

        function saveSettings(e) {
            e.preventDefault();
            // Disini logika penyimpanan data (Database/LocalStorage)
            alert("Pengaturan keuangan berhasil disimpan!");
            safeNavigate('keuangan.php'); // Kembali ke halaman utama keuangan
        }
    </script>
</body>
</html>