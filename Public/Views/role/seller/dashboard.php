<?php
session_start();

// Koneksi Database
include '../../../Auth/koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// --- CEK STATUS AKUN (ACTIVE / BANNED) ---
$q_status = mysqli_query($koneksi, "SELECT status FROM users WHERE user_id = '$user_id'");
$d_status = mysqli_fetch_assoc($q_status);
$is_banned = ($d_status['status'] == 'banned');

// Variabel Default
$has_shop = false;
$shop_data = [];
$rating_toko = "0"; 
$total_followers = 0;

// JIKA TIDAK DIBANNED, JALANKAN LOGIKA TOKO
if (!$is_banned) {

    // --- LOGIKA BUAT TOKO BARU ---
    if (isset($_POST['create_shop'])) {
        $shop_name = mysqli_real_escape_string($koneksi, $_POST['shop_name']);
        $shop_desc = mysqli_real_escape_string($koneksi, $_POST['shop_description']);
        $shop_img = "https://placehold.co/400x400/2ecc71/ffffff?text=" . urlencode($shop_name);

        if (!empty($_FILES['shop_image']['name'])) {
            $target_dir = "../../../Assets/img/shops/";
            if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
            $file_name = time() . "_" . basename($_FILES["shop_image"]["name"]);
            $target_file = $target_dir . $file_name;
            if (move_uploaded_file($_FILES["shop_image"]["tmp_name"], $target_file)) {
                $shop_img = $target_file;
            }
        }

        $query_insert = "INSERT INTO shops (user_id, shop_name, shop_description, shop_image, balance, created_at) 
                         VALUES ('$user_id', '$shop_name', '$shop_desc', '$shop_img', 0, NOW())";
        
        if (mysqli_query($koneksi, $query_insert)) {
            mysqli_query($koneksi, "UPDATE users SET role='seller' WHERE user_id='$user_id'");
            $_SESSION['role'] = 'seller'; 
            echo "<script>alert('Toko berhasil dibuat!'); window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Gagal membuat toko.');</script>";
        }
    }

    // --- CEK DATA TOKO ---
    $query_shop = mysqli_query($koneksi, "SELECT * FROM shops WHERE user_id = '$user_id'");
    if (mysqli_num_rows($query_shop) > 0) {
        $has_shop = true;
        $shop_data = mysqli_fetch_assoc($query_shop);
        $shop_id = $shop_data['shop_id'];

        // Rating
        try {
            $query_rating = mysqli_query($koneksi, "SELECT AVG(r.rating) as avg_rating FROM reviews r JOIN products p ON r.product_id = p.product_id WHERE p.shop_id = '$shop_id'");
            if($query_rating) {
                $data_rating = mysqli_fetch_assoc($query_rating);
                $rating_val = (float)$data_rating['avg_rating'];
                if($rating_val > 0) $rating_toko = number_format($rating_val, 1);
            }
        } catch (Exception $e) { $rating_toko = "0"; }

        // Followers
        try {
            $query_follow = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM shop_followers WHERE shop_id = '$shop_id'");
            if ($query_follow) {
                $data_follow = mysqli_fetch_assoc($query_follow);
                $total_followers = $data_follow['total'];
            }
        } catch (Exception $e) { $total_followers = 0; }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Saya - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Dashboard */
        .create-shop-card { background-color: #fff; border-radius: 12px; padding: 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #e0e0e0; text-align: center; }
        .file-input-wrapper { border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 8px; cursor: pointer; margin-bottom: 15px; background: #fafafa; transition: 0.3s; }
        .file-input-wrapper:hover { border-color: var(--primary); background: #fffdf0; }
        #preview-shop-img { max-width: 120px; max-height: 120px; border-radius: 50%; margin-top: 15px; display: none; object-fit: cover; border: 2px solid var(--primary); }
        .illustration-icon { font-size: 4rem; color: var(--primary); margin-bottom: 20px; }
        .shop-stats-container { display: flex; align-items: center; gap: 15px; color: #666; font-size: 0.95rem; margin-top: 5px; }
        .stat-divider { width: 1px; height: 15px; background-color: #ccc; }
        
        /* Tombol Header Actions */
        .header-actions { margin-left: auto; display: flex; align-items: center; gap: 10px; }
        .btn-visit-shop { margin-left: 0 !important; }
        
        .btn-chat-link {
            width: 42px; height: 42px; border-radius: 50%; background-color: #f5f5f5;
            display: flex; align-items: center; justify-content: center; color: #333;
            text-decoration: none; border: 1px solid #e0e0e0; transition: all 0.2s; font-size: 1.1rem;
        }
        .btn-chat-link:hover {
            background-color: var(--primary); border-color: var(--primary); color: #000; transform: translateY(-2px);
        }

        /* --- STYLING KHUSUS OVERLAY BANNED --- */
        .banned-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85); z-index: 99999;
            display: flex; align-items: center; justify-content: center;
            backdrop-filter: blur(5px);
        }
        .banned-card {
            background: white; width: 500px; padding: 40px; border-radius: 16px;
            text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            animation: popIn 0.3s ease-out;
        }
        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .banned-icon { 
            width: 80px; height: 80px; background: #fee2e2; color: #dc2626; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; margin: 0 auto 20px auto; 
        }
        .banned-title { font-size: 1.5rem; font-weight: 800; color: #333; margin-bottom: 10px; }
        .banned-text { color: #666; line-height: 1.6; margin-bottom: 25px; }
        .btn-back-buyer {
            display: inline-block; padding: 12px 30px; background: #333; color: white;
            text-decoration: none; border-radius: 8px; font-weight: 600; transition: 0.2s;
        }
        .btn-back-buyer:hover { background: #000; transform: translateY(-2px); }
    </style>
</head>

<body>

    <?php if ($is_banned): ?>
        <div class="banned-overlay">
            <div class="banned-card">
                <div class="banned-icon"><i class="fas fa-ban"></i></div>
                <h2 class="banned-title">Akses Toko Dibekukan</h2>
                <p class="banned-text">
                    Mohon maaf, akun toko Anda telah dinonaktifkan karena pelanggaran kebijakan komunitas atau laporan pengguna. 
                    <br><br>
                    Anda tetap dapat menggunakan akun ini untuk berbelanja sebagai pembeli.
                </p>
                <a href="../buyer/dashboard.php" class="btn-back-buyer">
                    <i class="fas fa-arrow-left"></i> Kembali ke Menu
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="app-layout">
            
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

            <main class="main-content-wrapper">
                <div class="header">
                    <div class="page-title">
                        <?php echo $has_shop ? 'Portal Penjual' : 'Mulai Berjualan'; ?>
                    </div>
                </div>

                <div class="content">
                    
                    <?php if($has_shop): ?>
                        <div class="shop-container">
                            
                            <div class="shop-header-card">
                                <div class="shop-avatar-container">
                                    <img src="<?php echo $shop_data['shop_image']; ?>" alt="Shop Logo" class="shop-avatar">
                                    <div class="shop-status-dot" title="Online"></div>
                                </div>
                                
                                <div class="shop-info">
                                    <h2><?php echo $shop_data['shop_name']; ?></h2>
                                    <div class="shop-stats-container">
                                        <span><i class="fas fa-users" style="color:var(--primary);"></i> <strong><?php echo $total_followers; ?></strong> Pengikut</span>
                                        <div class="stat-divider"></div>
                                        <span><i class="fas fa-star" style="color:var(--primary);"></i> <strong><?php echo $rating_toko; ?></strong> Rating</span>
                                    </div>
                                </div>

                                <div class="header-actions">
                                    <a href="chat.php" class="btn-chat-link" title="Pesan Masuk">
                                        <i class="fas fa-comment-dots"></i>
                                    </a>
                                    <a href="toko.php" class="btn-visit-shop">
                                        Lihat Toko <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="shop-menu-grid">
                                <a href="produkSaya.php" class="shop-menu-item">
                                    <div class="menu-icon-circle icon-products"><i class="fas fa-box-open"></i></div>
                                    <span class="menu-label">Produk Saya</span>
                                </a>
                                <a href="pesanan.php" class="shop-menu-item">
                                    <div class="menu-icon-circle icon-orders"><i class="fas fa-clipboard-list"></i></div>
                                    <span class="menu-label">Pesanan</span>
                                </a>
                                <a href="keuangan.php" class="shop-menu-item">
                                    <div class="menu-icon-circle icon-finance"><i class="fas fa-wallet"></i></div>
                                    <span class="menu-label">Keuangan</span>
                                </a>
                                <a href="performa.php" class="shop-menu-item">
                                    <div class="menu-icon-circle icon-performance"><i class="fas fa-chart-line"></i></div>
                                    <span class="menu-label">Performa Toko</span>
                                </a>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="create-shop-card">
                            <i class="fas fa-store illustration-icon"></i>
                            <h2 style="margin-bottom: 10px;">Yuk Mulai Berjualan!</h2>
                            <p style="color:#666; margin-bottom:30px;">Untuk mulai berjualan, silahkan lengkapi profil toko Anda terlebih dahulu.</p>
                            <form method="POST" enctype="multipart/form-data" style="text-align: left;">
                                <input type="hidden" name="create_shop" value="1">
                                <div class="form-group">
                                    <label class="form-label">Foto Profil Toko</label>
                                    <div class="file-input-wrapper" onclick="document.getElementById('shopImageInput').click()">
                                        <i class="fas fa-cloud-upload-alt" style="font-size:1.5rem; color:#888;"></i>
                                        <div style="font-size:0.9rem; margin-top:5px; color:#555;">Klik untuk upload logo toko</div>
                                        <img id="preview-shop-img" src="">
                                    </div>
                                    <input type="file" name="shop_image" id="shopImageInput" accept="image/*" style="display:none;" onchange="previewShopImage(this)" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Nama Toko</label>
                                    <input type="text" name="shop_name" class="form-input" placeholder="Contoh: Dimas Store" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Deskripsi Singkat</label>
                                    <textarea name="shop_description" class="form-textarea" rows="3" placeholder="Jelaskan apa yang Anda jual..." required></textarea>
                                </div>
                                <button type="submit" class="btn-submit" style="width:100%; margin-top:10px;">Buka Toko Sekarang</button>
                            </form>
                        </div>
                    <?php endif; ?>

                </div>
            </main>
        </div>

        <script>
            function goToDashboard() { window.location.href = '../buyer/dashboard.php'; }
            function previewShopImage(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.getElementById('preview-shop-img');
                        img.src = e.target.result;
                        img.style.display = 'inline-block';
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }
        </script>
    <?php endif; ?>
</body>
</html>