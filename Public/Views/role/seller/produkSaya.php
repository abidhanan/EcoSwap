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

// Cek Toko
$q_shop = mysqli_query($koneksi, "SELECT shop_id FROM shops WHERE user_id = '$user_id'");
if(mysqli_num_rows($q_shop) == 0){
    header("Location: dashboard.php"); // Belum punya toko
    exit();
}
$shop = mysqli_fetch_assoc($q_shop);
$shop_id = $shop['shop_id'];

// ==========================================
// 1. LOGIKA TAMBAH PRODUK
// ==========================================
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $price = $_POST['price'];
    $cond = $_POST['condition'];
    $desc = mysqli_real_escape_string($koneksi, $_POST['description']);
    
    // Upload Gambar
    $db_img_path = "https://placehold.co/300?text=No+Image"; // Default
    
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../../../Assets/img/products/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $db_img_path = $target_file; 
        }
    }
    
    $query = "INSERT INTO products (shop_id, name, price, `condition`, description, image, status, created_at) 
              VALUES ('$shop_id', '$name', '$price', '$cond', '$desc', '$db_img_path', 'active', NOW())";
    
    if(mysqli_query($koneksi, $query)){
        echo "<script>alert('Produk berhasil ditambahkan!'); window.location.href='produkSaya.php';</script>";
    }
}

// ==========================================
// 2. LOGIKA UPDATE PRODUK
// ==========================================
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $pid = $_POST['product_id'];
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $price = $_POST['price'];
    $cond = $_POST['condition'];
    $desc = mysqli_real_escape_string($koneksi, $_POST['description']);
    
    // Update data teks
    $query = "UPDATE products SET name='$name', price='$price', `condition`='$cond', description='$desc' 
              WHERE product_id='$pid' AND shop_id='$shop_id'";
    mysqli_query($koneksi, $query);

    // Cek jika ada upload gambar baru
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../../../Assets/img/products/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            mysqli_query($koneksi, "UPDATE products SET image='$target_file' WHERE product_id='$pid'");
        }
    }

    echo "<script>alert('Produk berhasil diperbarui!'); window.location.href='produkSaya.php';</script>";
}

// ==========================================
// 3. LOGIKA HAPUS PRODUK
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $pid = $_GET['id'];
    $query = "DELETE FROM products WHERE product_id='$pid' AND shop_id='$shop_id'";
    
    if(mysqli_query($koneksi, $query)){
        echo "<script>alert('Produk berhasil dihapus!'); window.location.href='produkSaya.php';</script>";
    }
}

// ==========================================
// 4. AMBIL DATA PRODUK (READ) + HITUNG FAVORIT
// ==========================================
$products = [];

// Query: Hitung jumlah produk di tabel 'cart' sebagai indikator favorit
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM cart c WHERE c.product_id = p.product_id) as total_favorites
        FROM products p 
        WHERE p.shop_id = '$shop_id' AND p.status != 'deleted' 
        ORDER BY p.created_at DESC";

$query_prod = mysqli_query($koneksi, $sql);

while($row = mysqli_fetch_assoc($query_prod)) {
    $products[] = [
        'id' => $row['product_id'],
        'name' => $row['name'],
        'price' => (int)$row['price'],
        'img' => $row['image'],
        'favorites' => $row['total_favorites'], // Jumlah favorit dari cart
        'status' => $row['status'], // 'active' atau 'sold'
        'desc' => $row['description'],
        'cond' => $row['condition']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk Saya - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/produkSaya.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Overwrite: Posisi Ikon Favorit di Kanan Bawah */
        .card-stats {
            display: flex;
            justify-content: flex-end; /* Geser ke kanan */
            margin-top: 10px;
            color: #888;
            font-size: 0.85rem;
        }
        .card-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .card-stats i {
            color: #e74c3c; /* Merah Hati */
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
                <div class="page-title">Produk Saya</div>
            </div>

            <div class="content">
                <div class="product-container">
                    
                    <div class="action-bar">
                        <h2 style="font-size:1.2rem; color:#666;">Kelola Katalog</h2>
                        <button class="btn-add-product" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Tambah Produk
                        </button>
                    </div>

                    <div class="tabs-container">
                        <button class="tab-btn active" onclick="switchTab('active', this)">Semua Produk</button>
                        <button class="tab-btn" onclick="switchTab('sold', this)">Terjual</button>
                    </div>

                    <div class="my-product-grid" id="productGrid">
                        </div>

                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="addProductModal">
        <div class="modal-container">
            <span class="close-modal" onclick="closeModal('addProductModal')">&times;</span>
            <div class="modal-title">Tambah Produk Baru</div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label class="form-label">Foto Produk</label>
                    <div class="upload-box" onclick="document.getElementById('prodImgInput').click()">
                        <i class="fas fa-cloud-upload-alt upload-icon" id="addUploadIcon"></i>
                        <p style="font-size:0.9rem; color:#666;">Klik untuk upload foto</p>
                        <img id="previewImgAdd" class="preview-img" src="" alt="Preview">
                    </div>
                    <input type="file" name="image" id="prodImgInput" style="display: none;" accept="image/*" onchange="previewImage(this, 'previewImgAdd', 'addUploadIcon')" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="name" id="prodName" class="form-input" placeholder="Contoh: Sepatu Adidas Bekas" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Harga (Rp)</label>
                    <input type="number" name="price" id="prodPrice" class="form-input" placeholder="Contoh: 150000" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kondisi</label>
                    <select name="condition" id="prodCond" class="form-select">
                        <option value="Bekas - Seperti Baru">Bekas - Seperti Baru</option>
                        <option value="Bekas - Baik">Bekas - Baik</option>
                        <option value="Bekas - Layak Pakai">Bekas - Layak Pakai</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" id="prodDesc" class="form-textarea" rows="3" placeholder="Jelaskan detail barang..."></textarea>
                </div>

                <button type="submit" class="btn-submit">Upload Produk</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal-container">
            <span class="close-modal" onclick="closeModal('detailModal')">&times;</span>
            <div class="modal-title">Detail Produk</div>
            
            <img id="detailImg" src="" class="detail-img">
            <h2 id="detailName" style="margin-bottom:5px;">Nama Produk</h2>
            <div id="detailPrice" class="detail-price">Rp 0</div>
            
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <div id="detailDesc" class="detail-desc-box"></div>
            </div>

            <div class="detail-actions">
                <button class="btn-submit btn-edit" onclick="openEditModalFromDetail()">Edit</button>
                <button class="btn-submit btn-danger" onclick="deleteCurrentProduct()">Hapus</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="editProductModal">
        <div class="modal-container">
            <span class="close-modal" onclick="closeModal('editProductModal')">&times;</span>
            <div class="modal-title">Edit Produk</div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="product_id" id="editProductId">

                <div class="form-group">
                    <label class="form-label">Foto Produk</label>
                    <div class="upload-box" onclick="document.getElementById('editImgInput').click()">
                        <p style="font-size:0.8rem; color:#666; margin-bottom:5px;">Klik untuk ganti foto</p>
                        <img id="previewImgEdit" class="preview-img" src="" alt="Preview" style="display:block;">
                    </div>
                    <input type="file" name="image" id="editImgInput" style="display: none;" accept="image/*" onchange="previewImage(this, 'previewImgEdit', null)">
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" name="name" id="editName" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Harga (Rp)</label>
                    <input type="number" name="price" id="editPrice" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kondisi</label>
                    <select name="condition" id="editCond" class="form-select">
                        <option value="Bekas - Seperti Baru">Bekas - Seperti Baru</option>
                        <option value="Bekas - Baik">Bekas - Baik</option>
                        <option value="Bekas - Layak Pakai">Bekas - Layak Pakai</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" id="editDesc" class="form-textarea" rows="3"></textarea>
                </div>

                <button type="submit" class="btn-submit">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        function goToDashboard() {
            window.location.href = '../buyer/dashboard.php';
        }
        
        // DATA PRODUK (Inject PHP ke JS)
        let myProducts = <?php echo json_encode($products); ?>;
        if (!myProducts) { myProducts = []; }

        let currentTab = 'active';
        let currentSelectedId = null;

        // --- RENDER PRODUK ---
        function renderProducts() {
            const grid = document.getElementById('productGrid');
            grid.innerHTML = '';

            const filtered = myProducts.filter(p => p.status === currentTab);

            if (filtered.length === 0) {
                grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: #888; padding: 40px;">Tidak ada produk di kategori ini.</div>`;
                return;
            }

            filtered.forEach(p => {
                const isSold = p.status === 'sold';
                const cardClass = isSold ? 'my-product-card sold' : 'my-product-card';
                // Jika sold, tidak bisa diklik (opsional, di sini kita biarkan bisa diklik untuk lihat detail)
                
                const card = document.createElement('div');
                card.className = cardClass;
                card.onclick = () => openDetail(p.id);

                card.innerHTML = `
                    <div class="card-img-wrapper">
                        <img src="${p.img}" class="card-img" alt="${p.name}">
                        <div class="sold-overlay">TERJUAL</div>
                    </div>
                    <div class="card-body">
                        <div class="card-title">${p.name}</div>
                        <div class="card-price">Rp ${p.price.toLocaleString('id-ID')}</div>
                        <div class="card-stats">
                            <span><i class="fas fa-heart"></i> ${p.favorites}</span>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        // --- TABS & MODALS MANAGEMENT ---
        function switchTab(status, btn) {
            currentTab = status;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderProducts();
        }

        function openAddModal() {
            document.getElementById('addProductModal').classList.add('open');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('open');
        }

        // --- DETAIL PRODUCT LOGIC ---
        function openDetail(id) {
            const product = myProducts.find(p => p.id == id); 
            if (!product) return;

            currentSelectedId = id; 

            document.getElementById('detailImg').src = product.img;
            document.getElementById('detailName').textContent = product.name;
            document.getElementById('detailPrice').textContent = 'Rp ' + product.price.toLocaleString('id-ID');
            document.getElementById('detailDesc').textContent = product.desc || "Tidak ada deskripsi.";
            
            document.getElementById('detailModal').classList.add('open');
        }

        // --- DELETE PRODUCT LOGIC ---
        function deleteCurrentProduct() {
            if (confirm("Apakah Anda yakin ingin menghapus produk ini?")) {
                window.location.href = `produkSaya.php?action=delete&id=${currentSelectedId}`;
            }
        }

        // --- EDIT PRODUCT LOGIC ---
        function openEditModalFromDetail() {
            closeModal('detailModal'); 
            
            const product = myProducts.find(p => p.id == currentSelectedId);
            if (!product) return;

            document.getElementById('editProductId').value = product.id;
            document.getElementById('editName').value = product.name;
            document.getElementById('editPrice').value = product.price;
            document.getElementById('editCond').value = product.cond;
            document.getElementById('editDesc').value = product.desc;
            document.getElementById('previewImgEdit').src = product.img;
            
            document.getElementById('editProductModal').classList.add('open');
        }

        // --- GENERAL IMAGE PREVIEW ---
        function previewImage(input, imgId, iconId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById(imgId);
                    img.src = e.target.result;
                    img.style.display = 'block';
                    if(iconId) document.getElementById(iconId).style.display = 'none';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('open');
            }
        }

        document.addEventListener('DOMContentLoaded', renderProducts);

    </script>
</body>
</html>