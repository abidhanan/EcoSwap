<?php
session_start();
// Koneksi Database
include '../../../Auth/koneksi.php';

// Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../Auth/login.php");
    exit();
}

// LOGIKA VERIFIKASI / TOLAK / HAPUS (POST Action)
if (isset($_POST['action'])) {
    $product_id = mysqli_real_escape_string($koneksi, $_POST['product_id']);
    $action = $_POST['action'];

    if ($action == 'verify') {
        // Ubah status jadi 'active' agar muncul di halaman publik
        $q = "UPDATE products SET status='active' WHERE product_id='$product_id'";
        if(mysqli_query($koneksi, $q)) {
            // Opsional: Kirim notifikasi ke seller
            // sendNotification($seller_id, 'Produk Disetujui', 'Produk Anda kini live.');
            echo "<script>alert('Produk berhasil disetujui dan live!'); window.location.href='produk.php';</script>";
        }
    } elseif ($action == 'reject') {
        // Ubah status jadi 'rejected'
        $q = "UPDATE products SET status='rejected' WHERE product_id='$product_id'";
        if(mysqli_query($koneksi, $q)) {
            echo "<script>alert('Produk ditolak.'); window.location.href='produk.php';</script>";
        }
    } elseif ($action == 'delete') {
        // Hapus permanen
        $q = "DELETE FROM products WHERE product_id='$product_id'";
        if(mysqli_query($koneksi, $q)) {
            echo "<script>alert('Produk dihapus permanen.'); window.location.href='produk.php';</script>";
        }
    }
}

// AMBIL DATA PRODUK
// 1. Pending / Review (Menunggu Verifikasi)
// Note: Kode seller Anda menggunakan status 'review', pastikan konsisten. Di sini saya pakai OR untuk jaga-jaga.
$pending_prods = [];
$q_pen = mysqli_query($koneksi, "SELECT p.*, s.shop_name 
                                 FROM products p 
                                 JOIN shops s ON p.shop_id = s.shop_id 
                                 WHERE p.status = 'pending' OR p.status = 'review' 
                                 ORDER BY p.created_at DESC");
while($row = mysqli_fetch_assoc($q_pen)) { $pending_prods[] = $row; }

// 2. Aktif (Sudah Live)
$active_prods = [];
$q_act = mysqli_query($koneksi, "SELECT p.*, s.shop_name 
                                 FROM products p 
                                 JOIN shops s ON p.shop_id = s.shop_id 
                                 WHERE p.status = 'active' 
                                 ORDER BY p.created_at DESC");
while($row = mysqli_fetch_assoc($q_act)) { $active_prods[] = $row; }

// 3. Ditolak
$rejected_prods = [];
$q_rej = mysqli_query($koneksi, "SELECT p.*, s.shop_name 
                                 FROM products p 
                                 JOIN shops s ON p.shop_id = s.shop_id 
                                 WHERE p.status = 'rejected' 
                                 ORDER BY p.created_at DESC");
while($row = mysqli_fetch_assoc($q_rej)) { $rejected_prods[] = $row; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Verifikasi Produk</title>
    <link rel="stylesheet" href="../../../Assets/css/role/admin/produk.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS Khusus Halaman Ini */
        .tabs-container { display: flex; gap: 20px; border-bottom: 2px solid #eee; margin-bottom: 20px; }
        .tab-btn { background: none; border: none; padding: 12px 20px; font-size: 1rem; color: #777; cursor: pointer; font-weight: 600; position: relative; }
        .tab-btn.active { color: #000; }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 3px; background-color: #FFD700; }
        
        .tab-content { display: none; animation: fadeIn 0.3s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .product-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .product-table th { text-align: left; padding: 15px; color: #888; font-size: 0.85rem; border-bottom: 1px solid #eee; }
        .product-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; color: #333; }
        
        .img-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
        .product-name { font-weight: 600; font-size: 0.95rem; }
        .shop-name { font-size: 0.85rem; color: #666; }
        
        .btn-action { padding: 8px 15px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: 0.2s; }
        .btn-view { background: #eee; color: #333; }
        .btn-view:hover { background: #ddd; }
        .btn-delete { background: #fff0f0; color: #d9534f; margin-left: 5px; }
        .btn-delete:hover { background: #ffe5e5; }

        /* Modal Styles */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: white; width: 500px; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .modal-body img { width: 100%; height: 250px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; border: 1px solid #eee; }
        .modal-actions { display: flex; gap: 10px; margin-top: 25px; }
        .btn-verify { flex: 1; background: #28a745; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-verify:hover { background: #218838; }
        .btn-reject { flex: 1; background: #dc3545; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-reject:hover { background: #c82333; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fas fa-recycle"></i> ECO<span>SWAP</span></div>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
            <li class="active"><a href="stok.php"><i class="fas fa-box"></i> <span>Produk</span> <?php if(count($pending_prods) > 0): ?><span class="badge-count"><?php echo count($pending_prods); ?></span><?php endif; ?></a></li>
            <li><a href="pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a></li>
            <li><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
            <li><a href="laporan.php"><i class="fas fa-headset"></i> <span>Laporan</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../../../../index.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h2>Verifikasi Produk</h2>
                <p>Tinjau produk yang diupload seller sebelum live.</p>
            </div>
            </header>

        <section class="card-panel">
            <div class="tabs-container">
                <button class="tab-btn active" onclick="switchTab('pending')">
                    Menunggu Verifikasi <span style="background:#f6c23e; padding:2px 6px; border-radius:4px; font-size:0.8rem; color:#fff;"><?php echo count($pending_prods); ?></span>
                </button>
                <button class="tab-btn" onclick="switchTab('active')">Produk Aktif</button>
                <button class="tab-btn" onclick="switchTab('rejected')">Ditolak</button>
            </div>

            <div id="tab-pending" class="tab-content active">
                <table class="product-table">
                    <thead><tr><th>Foto</th><th>Nama Produk</th><th>Penjual</th><th>Harga</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if(empty($pending_prods)): ?>
                            <tr><td colspan="5" align="center" style="padding:40px; color:#888;">Tidak ada produk baru yang perlu ditinjau.</td></tr>
                        <?php else: ?>
                            <?php foreach($pending_prods as $p): ?>
                            <tr>
                                <td><img src="<?php echo $p['image']; ?>" class="img-thumb"></td>
                                <td>
                                    <div class="product-name"><?php echo $p['name']; ?></div>
                                    <small style="color:#888;">Kategori: <?php echo $p['category']; ?></small>
                                </td>
                                <td><?php echo $p['shop_name']; ?></td>
                                <td>Rp <?php echo number_format($p['price'], 0, ',', '.'); ?></td>
                                <td>
                                    <button class="btn-action btn-view" onclick='openModal(<?php echo json_encode($p); ?>)'>
                                        <i class="fas fa-search"></i> Tinjau
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="tab-active" class="tab-content">
                <table class="product-table">
                    <thead><tr><th>Foto</th><th>Nama Produk</th><th>Penjual</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if(empty($active_prods)): ?><tr><td colspan="6" align="center">Belum ada produk aktif.</td></tr><?php endif; ?>
                        <?php foreach($active_prods as $p): ?>
                        <tr>
                            <td><img src="<?php echo $p['image']; ?>" class="img-thumb"></td>
                            <td><div class="product-name"><?php echo $p['name']; ?></div></td>
                            <td><?php echo $p['shop_name']; ?></td>
                            <td>Rp <?php echo number_format($p['price'], 0, ',', '.'); ?></td>
                            <td><span style="color:green; font-weight:bold;">Live</span></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Hapus produk ini secara permanen?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                    <button class="btn-action btn-delete"><i class="fas fa-trash"></i> Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="tab-rejected" class="tab-content">
                <table class="product-table">
                    <thead><tr><th>Foto</th><th>Nama Produk</th><th>Penjual</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if(empty($rejected_prods)): ?><tr><td colspan="6" align="center">Tidak ada produk ditolak.</td></tr><?php endif; ?>
                        <?php foreach($rejected_prods as $p): ?>
                        <tr>
                            <td><img src="<?php echo $p['image']; ?>" class="img-thumb"></td>
                            <td><div class="product-name"><?php echo $p['name']; ?></div></td>
                            <td><?php echo $p['shop_name']; ?></td>
                            <td>Rp <?php echo number_format($p['price'], 0, ',', '.'); ?></td>
                            <td><span style="color:red; font-weight:bold;">Ditolak</span></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Hapus permanen?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                    <button class="btn-action btn-delete"><i class="fas fa-trash"></i> Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <div class="modal-overlay" id="productModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="margin:0;">Detail Produk</h3>
                <i class="fas fa-times" onclick="closeModal()" style="cursor:pointer; color:#888;"></i>
            </div>
            <div class="modal-body">
                <img id="mImg" src="">
                <h2 id="mName" style="font-size:1.4rem; margin-bottom:5px;"></h2>
                <div style="font-size:0.9rem; color:#666; margin-bottom:15px;">
                    Penjual: <strong id="mSeller"></strong>
                </div>
                
                <div style="background:#f9f9f9; padding:15px; border-radius:8px; font-size:0.9rem; line-height:1.5; margin-bottom:15px;">
                    <strong>Deskripsi:</strong><br>
                    <span id="mDesc"></span>
                </div>

                <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:1.1rem;">
                    <span id="mPrice" style="color:#28a745;"></span>
                    <span id="mCondition" style="color:#555; font-size:0.9rem; font-weight:normal; background:#eee; padding:2px 8px; border-radius:4px;"></span>
                </div>
            </div>
            <form method="POST" class="modal-actions">
                <input type="hidden" name="product_id" id="mId">
                
                <button type="submit" name="action" value="reject" class="btn-reject" onclick="return confirm('Yakin ingin menolak produk ini?')">
                    <i class="fas fa-times"></i> Tolak
                </button>
                <button type="submit" name="action" value="verify" class="btn-verify" onclick="return confirm('Setujui produk ini agar tampil di publik?')">
                    <i class="fas fa-check"></i> Setujui & Publish
                </button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById('tab-' + tab).classList.add('active');
        }

        function openModal(data) {
            document.getElementById('mId').value = data.product_id;
            document.getElementById('mImg').src = data.image;
            document.getElementById('mName').innerText = data.name;
            document.getElementById('mSeller').innerText = data.shop_name;
            document.getElementById('mDesc').innerText = data.description;
            document.getElementById('mPrice').innerText = 'Rp ' + parseInt(data.price).toLocaleString('id-ID');
            document.getElementById('mCondition').innerText = data.condition;
            
            document.getElementById('productModal').classList.add('open');
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('open');
        }

        // Close modal when clicking outside
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeModal();
            }
        }
    </script>
</body>
</html>