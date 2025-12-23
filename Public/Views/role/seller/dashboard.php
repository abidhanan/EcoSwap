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

// --- 1. LOGIKA BUAT TOKO BARU (JIKA BELUM ADA) ---
if (isset($_POST['create_shop'])) {
    $shop_name = mysqli_real_escape_string($koneksi, $_POST['shop_name']);
    $shop_desc = mysqli_real_escape_string($koneksi, $_POST['shop_description']);
    
    // Default image jika tidak upload
    $shop_img = "https://placehold.co/400x400/2ecc71/ffffff?text=" . urlencode($shop_name);

    // Cek upload foto toko
    if (!empty($_FILES['shop_image']['name'])) {
        $target_dir = "../../../Assets/img/shops/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_name = time() . "_" . basename($_FILES["shop_image"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["shop_image"]["tmp_name"], $target_file)) {
            $shop_img = $target_file;
        }
    }

    // Insert ke database
    $query_insert = "INSERT INTO shops (user_id, shop_name, shop_description, shop_image, balance, created_at) 
                     VALUES ('$user_id', '$shop_name', '$shop_desc', '$shop_img', 0, NOW())";
    
    if (mysqli_query($koneksi, $query_insert)) {
        // Update role user jadi seller jika sebelumnya buyer
        mysqli_query($koneksi, "UPDATE users SET role='seller' WHERE user_id='$user_id'");
        $_SESSION['role'] = 'seller'; 
        
        echo "<script>alert('Toko berhasil dibuat! Selamat berjualan.'); window.location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal membuat toko: " . mysqli_error($koneksi) . "');</script>";
    }
}

// --- 2. CEK DATA TOKO ---
$has_shop = false;
$shop_data = [];
$rating_toko = 0;

$query_shop = mysqli_query($koneksi, "SELECT * FROM shops WHERE user_id = '$user_id'");
if (mysqli_num_rows($query_shop) > 0) {
    $has_shop = true;
    $shop_data = mysqli_fetch_assoc($query_shop);
    $shop_id = $shop_data['shop_id'];

    // Hitung Rata-rata Rating Toko
    $query_rating = mysqli_query($koneksi, "
        SELECT AVG(r.rating) as avg_rating 
        FROM reviews r 
        JOIN products p ON r.product_id = p.product_id 
        WHERE p.shop_id = '$shop_id'
    ");
    $data_rating = mysqli_fetch_assoc($query_rating);
    $rating_toko = number_format((float)$data_rating['avg_rating'], 1);
    if($rating_toko == 0) $rating_toko = "Baru";
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
        /* Styling khusus untuk form create shop di konten utama */
        .create-shop-card {
            background-color: #fff;
            border-radius: 12px;
            padding: 40px;
            max-width: 600px;
            margin: 0 auto; /* Center horizontal */
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #e0e0e0;
            text-align: center;
        }
        .file-input-wrapper {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 15px;
            background: #fafafa;
            transition: 0.3s;
        }
        .file-input-wrapper:hover { 
            border-color: var(--primary); 
            background: #fffdf0;
        }
        #preview-shop-img {
            max-width: 120px;
            max-height: 120px;
            border-radius: 50%;
            margin-top: 15px;
            display: none;
            object-fit: cover;
            border: 2px solid var(--primary);
        }
        .illustration-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

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
                                <div class="shop-rating">
                                    <span><i class="fas fa-star"></i> Rating</span>
                                    <span><?php echo $rating_toko; ?></span>
                                </div>
                            </div>
                            <a href="toko.php" class="btn-visit-shop">Lihat Toko <i class="fas fa-external-link-alt"></i></a>
                        </div>

                        <div class="shop-menu-grid">
                            <a href="produkSaya.php" class="shop-menu-item">
                                <div class="menu-icon-circle icon-products">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <span class="menu-label">Produk Saya</span>
                            </a>

                            <a href="pesanan.php" class="shop-menu-item">
                                <div class="menu-icon-circle icon-orders">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <span class="menu-label">Pesanan</span>
                            </a>

                            <a href="keuangan.php" class="shop-menu-item">
                                <div class="menu-icon-circle icon-finance">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <span class="menu-label">Keuangan</span>
                            </a>

                            <a href="performa.php" class="shop-menu-item">
                                <div class="menu-icon-circle icon-performance">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <span class="menu-label">Performa Toko</span>
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="create-shop-card">
                        <i class="fas fa-store illustration-icon"></i>
                        <h2 style="margin-bottom: 10px;">Buka Toko Gratis</h2>
                        <p style="color:#666; margin-bottom:30px;">
                            Halo! Untuk mulai berjualan, silakan lengkapi profil toko Anda terlebih dahulu.
                        </p>

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

                            <button type="submit" class="btn-submit" style="width:100%; margin-top:10px;">
                                <i class="fas fa-check-circle"></i> Buka Toko Sekarang
                            </button>
                            
                            <div style="text-align:center; margin-top:15px;">
                                <a href="../buyer/dashboard.php" style="color:#666; font-size:0.9rem; text-decoration:none;">
                                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard Pembeli
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <script>
        function goToDashboard() {
            window.location.href = '../buyer/dashboard.php';
        }

        // Preview Image Logic
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
</body>
</html>