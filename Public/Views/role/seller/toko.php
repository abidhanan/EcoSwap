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

// AMBIL DATA TOKO
$q_shop = mysqli_query($koneksi, "SELECT * FROM shops WHERE user_id = '$user_id'");
if(mysqli_num_rows($q_shop) == 0){
    header("Location: dashboard.php");
    exit();
}
$shop = mysqli_fetch_assoc($q_shop);
$shop_id = $shop['shop_id'];

// AMBIL DATA RATING DARI DATABASE
$q_rating = mysqli_query($koneksi, "SELECT AVG(r.rating) as avg FROM reviews r JOIN products p ON r.product_id=p.product_id WHERE p.shop_id='$shop_id'");
$d_rating = mysqli_fetch_assoc($q_rating);
$rating_val = (float)$d_rating['avg']; // Nilai float asli (misal 4.5)
$rating_toko = number_format($rating_val, 1); // Format string (misal "4.5")

if($rating_val == 0) {
    $rating_toko = "Baru";
}

// --- LOGIKA UPDATE PROFIL TOKO ---
if(isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $name = mysqli_real_escape_string($koneksi, $_POST['shop_name']);
    $desc = mysqli_real_escape_string($koneksi, $_POST['shop_desc']);
    $phone = mysqli_real_escape_string($koneksi, $_POST['shop_phone']);
    $addr = mysqli_real_escape_string($koneksi, $_POST['shop_address']);
    
    $query = "UPDATE shops SET shop_name='$name', shop_description='$desc', shop_phone='$phone', shop_address='$addr' WHERE shop_id='$shop_id'";
    mysqli_query($koneksi, $query);
    
    // Upload Foto
    if(!empty($_FILES['shop_image']['name'])){
        $target_dir = "../../../Assets/img/shops/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_name = time() . "_" . basename($_FILES["shop_image"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["shop_image"]["tmp_name"], $target_file)) {
            mysqli_query($koneksi, "UPDATE shops SET shop_image='$target_file' WHERE shop_id='$shop_id'");
        }
    }
    
    echo "<script>alert('Profil toko berhasil diperbarui!'); window.location.href='toko.php';</script>";
}

// --- LOGIKA UPDATE JASA PENGANTARAN ---
$available_couriers = ["COD", "JNE Reguler", "J&T Express", "SiCepat", "GoSend Instant", "GrabExpress", "AnterAja"];
$saved_couriers = !empty($shop['shipping_options']) ? json_decode($shop['shipping_options'], true) : [];
if(!is_array($saved_couriers)) $saved_couriers = [];

if(isset($_POST['action']) && $_POST['action'] == 'update_shipping') {
    $selected_couriers = isset($_POST['couriers']) ? $_POST['couriers'] : [];
    $json_couriers = json_encode($selected_couriers);
    $q_ship = "UPDATE shops SET shipping_options='$json_couriers' WHERE shop_id='$shop_id'";
    mysqli_query($koneksi, $q_ship);
    echo "<script>alert('Jasa pengantaran berhasil disimpan!'); window.location.href='toko.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Toko - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/toko.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS KHUSUS HALAMAN INI */
        .settings-container { max-width: 900px; margin: 0 auto; }
        
        .shop-card {
            background: #fff; border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
            border: 1px solid #e0e0e0; overflow: hidden; margin-bottom: 40px;
        }

        /* Header Gradient Area */
        .shop-header-row {
            display: flex; align-items: center; gap: 30px; padding: 30px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(to bottom, #fff, #f8f9fa);
        }
        
        .shop-img-container { position: relative; width: 120px; height: 120px; flex-shrink: 0; }
        .shop-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        /* Tombol Kamera (Hidden by default) */
        .edit-img-btn {
            position: absolute; bottom: 5px; right: 5px; background: var(--dark); color: #fff;
            border: none; width: 35px; height: 35px; border-radius: 50%; cursor: pointer;
            display: none; /* Disembunyikan dulu */
            align-items: center; justify-content: center; transition: 0.2s; z-index: 2;
        }
        .edit-img-btn:hover { background: var(--primary); color: #000; }
        
        .shop-info-summary h2 { font-size: 1.8rem; margin-bottom: 5px; color: var(--dark); }
        
        /* Styling Rating Bintang */
        .shop-rating { display: flex; align-items: center; gap: 5px; font-size: 0.95rem; color: #666; margin-top: 8px; }
        .star-filled { color: #ffd700; }
        .star-empty { color: #e0e0e0; }

        /* Section Content */
        .settings-section { padding: 30px; border-bottom: 1px solid #eee; }
        .settings-section:last-child { border-bottom: none; }
        
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-title { font-size: 1.2rem; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 10px; }
        .section-title i { color: var(--primary); }

        /* Form Styles */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-full { grid-column: span 2; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: #555; }
        
        .form-input, .form-textarea {
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; transition: 0.3s;
            background-color: #fff;
        }
        
        /* Disabled State Styling */
        .form-input:disabled, .form-textarea:disabled {
            background-color: #f9f9f9;
            color: #555;
            border-color: #eee;
            cursor: default;
        }
        
        .form-input:not(:disabled):focus, .form-textarea:not(:disabled):focus { 
            border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(255,215,0,0.1); 
        }

        /* Buttons */
        .btn-edit {
            background: #fff; border: 1px solid var(--dark); color: var(--dark);
            padding: 8px 16px; border-radius: 20px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 0.85rem;
        }
        .btn-edit:hover { background: var(--dark); color: #fff; }

        .action-buttons { display: none; gap: 10px; } /* Hidden by default */
        .btn-save { background: var(--primary); color: #000; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .btn-cancel { background: #eee; color: #333; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }

        /* Courier Pills */
        .courier-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .courier-label { cursor: pointer; }
        .courier-label input { display: none; }
        
        .courier-pill {
            display: inline-block; padding: 10px 20px; background: #fff; border: 1px solid #ddd; border-radius: 30px;
            font-size: 0.9rem; font-weight: 500; color: #aaa; transition: 0.2s; cursor: not-allowed;
        }
        
        /* State when checkbox is checked but disabled (View Mode) */
        .courier-label input:checked + .courier-pill {
            background: #f0f0f0; color: #333; border-color: #ccc;
        }

        /* State when Editing is Active */
        .editing .courier-pill { cursor: pointer; color: #666; }
        .editing .courier-label input:checked + .courier-pill {
            background: var(--dark); color: var(--primary); border-color: var(--dark); box-shadow: 0 4px 10px rgba(0,0,0,0.15);
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
                <div class="page-title">Pengaturan Toko</div>
            </div>

            <div class="content">
                <div class="settings-container">
                    
                    <div class="shop-card">
                        
                        <form id="formProfile" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="shop-header-row">
                                <div class="shop-img-container">
                                    <img src="<?php echo $shop['shop_image']; ?>" id="shopAvatar" class="shop-img">
                                    
                                    <button type="button" id="btnUpload" class="edit-img-btn" onclick="document.getElementById('shopProfileInput').click()">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                    <input type="file" name="shop_image" id="shopProfileInput" accept="image/*" style="display: none;" onchange="previewImage(this)">
                                </div>
                                
                                <div class="shop-info-summary">
                                    <h2><?php echo $shop['shop_name']; ?></h2>
                                    
                                    <div class="shop-rating">
                                        <?php if ($rating_toko == "Baru"): ?>
                                            <span style="background:#eee; padding:3px 10px; border-radius:12px; font-size:0.8rem; color:#666;">Belum ada rating</span>
                                        <?php else: ?>
                                            <div class="stars">
                                                <?php 
                                                // Logika Render Bintang
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating_val) {
                                                        echo '<i class="fas fa-star star-filled"></i>'; // Bintang Penuh
                                                    } elseif ($i - 0.5 <= $rating_val) {
                                                        echo '<i class="fas fa-star-half-alt star-filled"></i>'; // Setengah
                                                    } else {
                                                        echo '<i class="far fa-star star-empty"></i>'; // Kosong
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <strong><?php echo $rating_toko; ?></strong> <span>/ 5.0</span>
                                        <?php endif; ?>
                                    </div>
                                    </div>
                            </div>

                            <div class="settings-section">
                                <div class="section-header">
                                    <div class="section-title"><i class="fas fa-store"></i> Profil & Alamat Toko</div>
                                    
                                    <button type="button" id="btnEditProfile" class="btn-edit" onclick="toggleEditProfile(true)">
                                        <i class="fas fa-pen"></i> Ubah Profil
                                    </button>

                                    <div id="actionProfile" class="action-buttons">
                                        <button type="button" class="btn-cancel" onclick="toggleEditProfile(false)">Batal</button>
                                        <button type="submit" class="btn-save">Simpan</button>
                                    </div>
                                </div>

                                <div class="form-grid">
                                    <div class="form-group">
                                        <label class="form-label">Nama Toko</label>
                                        <input type="text" name="shop_name" class="form-input profile-input" value="<?php echo $shop['shop_name']; ?>" required disabled>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Nomor Telepon</label>
                                        <input type="tel" name="shop_phone" class="form-input profile-input" value="<?php echo !empty($shop['shop_phone']) ? $shop['shop_phone'] : ''; ?>" placeholder="08xxxxxxxx" required disabled>
                                    </div>

                                    <div class="form-group form-full">
                                        <label class="form-label">Deskripsi Singkat</label>
                                        <textarea name="shop_desc" class="form-textarea profile-input" rows="2" disabled><?php echo $shop['shop_description']; ?></textarea>
                                    </div>

                                    <div class="form-group form-full">
                                        <label class="form-label">Alamat Operasional Lengkap</label>
                                        <textarea name="shop_address" class="form-textarea profile-input" rows="3" placeholder="Nama Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Kode Pos" required disabled><?php echo !empty($shop['shop_address']) ? $shop['shop_address'] : ''; ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="settings-section">
                            <form id="formShipping" method="POST">
                                <input type="hidden" name="action" value="update_shipping">
                                
                                <div class="section-header">
                                    <div class="section-title"><i class="fas fa-truck"></i> Jasa Pengantaran</div>
                                    
                                    <button type="button" id="btnEditShipping" class="btn-edit" onclick="toggleEditShipping(true)">
                                        <i class="fas fa-pen"></i> Ubah Pengiriman
                                    </button>

                                    <div id="actionShipping" class="action-buttons">
                                        <button type="button" class="btn-cancel" onclick="toggleEditShipping(false)">Batal</button>
                                        <button type="submit" class="btn-save">Simpan</button>
                                    </div>
                                </div>
                                
                                <div class="courier-grid" id="courierGrid">
                                    <?php foreach($available_couriers as $courier): ?>
                                        <label class="courier-label">
                                            <input type="checkbox" name="couriers[]" class="courier-checkbox" value="<?php echo $courier; ?>" 
                                                   <?php echo in_array($courier, $saved_couriers) ? 'checked' : ''; ?> disabled> 
                                            <span class="courier-pill">
                                                <?php echo $courier; ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </form>
                        </div>

                    </div> </div>
            </div>
        </main>
    </div>

    <script>
        function goToDashboard() {
            window.location.href = '../buyer/dashboard.php';
        }

        // --- 1. LOGIKA EDIT PROFIL ---
        function toggleEditProfile(isEditing) {
            const inputs = document.querySelectorAll('.profile-input');
            const btnEdit = document.getElementById('btnEditProfile');
            const actionBtns = document.getElementById('actionProfile');
            const btnUpload = document.getElementById('btnUpload');

            if (isEditing) {
                // Mode Edit: Aktifkan input, sembunyikan tombol edit, tampilkan save/cancel
                inputs.forEach(el => el.removeAttribute('disabled'));
                btnEdit.style.display = 'none';
                actionBtns.style.display = 'flex';
                btnUpload.style.display = 'flex'; // Munculkan tombol kamera
            } else {
                // Mode Batal: Reset form, matikan input
                document.getElementById('formProfile').reset(); // Reset nilai ke awal
                // Kembalikan gambar ke asal jika batal (opsional, butuh logic complex, biarkan reset text saja)
                
                inputs.forEach(el => el.setAttribute('disabled', 'true'));
                btnEdit.style.display = 'inline-block';
                actionBtns.style.display = 'none';
                btnUpload.style.display = 'none';
            }
        }

        // Preview Image saat upload
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('shopAvatar').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // --- 2. LOGIKA EDIT PENGIRIMAN ---
        function toggleEditShipping(isEditing) {
            const checkboxes = document.querySelectorAll('.courier-checkbox');
            const grid = document.getElementById('courierGrid');
            const btnEdit = document.getElementById('btnEditShipping');
            const actionBtns = document.getElementById('actionShipping');

            if (isEditing) {
                checkboxes.forEach(el => el.removeAttribute('disabled'));
                grid.classList.add('editing'); // Tambah class untuk styling aktif
                btnEdit.style.display = 'none';
                actionBtns.style.display = 'flex';
            } else {
                document.getElementById('formShipping').reset(); // Reset ke pilihan awal
                checkboxes.forEach(el => el.setAttribute('disabled', 'true'));
                grid.classList.remove('editing');
                btnEdit.style.display = 'inline-block';
                actionBtns.style.display = 'none';
            }
        }
    </script>
</body>
</html>