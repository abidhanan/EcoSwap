<?php
session_start();
include '../../../Auth/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../../../Auth/login.php"); 
    exit(); 
}

// AJAX Endpoint untuk Real-Time Update
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_products') {
    header('Content-Type: application/json');
    
    $pending_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM products WHERE status = 'review' OR status = 'pending'"))['total'];
    $report_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'"))['total'];
    
    // Ambil produk pending
    $pending_prods = [];
    $q_pen = mysqli_query($koneksi, "SELECT p.*, s.shop_name FROM products p JOIN shops s ON p.shop_id = s.shop_id WHERE p.status = 'pending' OR p.status = 'review' ORDER BY p.created_at DESC");
    while($row = mysqli_fetch_assoc($q_pen)) { $pending_prods[] = $row; }
    
    echo json_encode([
        'pending_count' => $pending_count,
        'report_count' => $report_count,
        'pending_products' => $pending_prods
    ]);
    exit();
}

// --- HITUNG BADGES SIDEBAR ---
$pending_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM products WHERE status = 'review' OR status = 'pending'"))['total'];
$report_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'"))['total'];

// --- LOGIKA ACTION (Updated) ---
if (isset($_POST['action'])) {
    $product_id = mysqli_real_escape_string($koneksi, $_POST['product_id']);
    $action = $_POST['action'];
    
    // VERIFY: Pending/Rejected/Deleted -> Active
    if ($action == 'verify' || $action == 'restore') {
        mysqli_query($koneksi, "UPDATE products SET status='active' WHERE product_id='$product_id'");
        echo "<script>alert('Produk berhasil diaktifkan!'); window.location.href='produk.php';</script>";
    } 
    // REJECT: Pending -> Rejected
    elseif ($action == 'reject') {
        mysqli_query($koneksi, "UPDATE products SET status='rejected' WHERE product_id='$product_id'");
        echo "<script>alert('Produk ditolak.'); window.location.href='produk.php';</script>";
    } 
    // DELETE: Active/Rejected -> Deleted (Soft Delete)
    elseif ($action == 'delete') {
        mysqli_query($koneksi, "UPDATE products SET status='deleted' WHERE product_id='$product_id'");
        echo "<script>alert('Produk dipindahkan ke sampah.'); window.location.href='produk.php';</script>";
    }
}

// --- QUERY DATA PER KATEGORI STATUS ---

// 1. Pending / Review
$pending_prods = [];
$q_pen = mysqli_query($koneksi, "SELECT p.*, s.shop_name FROM products p JOIN shops s ON p.shop_id = s.shop_id WHERE p.status = 'pending' OR p.status = 'review' ORDER BY p.created_at DESC");
while($row = mysqli_fetch_assoc($q_pen)) { $pending_prods[] = $row; }

// 2. Active (Live)
$active_prods = [];
$q_act = mysqli_query($koneksi, "SELECT p.*, s.shop_name, (SELECT COUNT(*) FROM orders o WHERE o.product_id = p.product_id AND (o.status = 'completed' OR o.status = 'reviewed')) as sold_count FROM products p JOIN shops s ON p.shop_id = s.shop_id WHERE p.status = 'active' ORDER BY p.created_at DESC");
while($row = mysqli_fetch_assoc($q_act)) { $active_prods[] = $row; }

// 3. Sold (Terjual)
$sold_prods = [];
$q_sold = mysqli_query($koneksi, "SELECT p.*, s.shop_name FROM products p JOIN shops s ON p.shop_id = s.shop_id WHERE p.status = 'sold' ORDER BY p.created_at DESC");
while($row = mysqli_fetch_assoc($q_sold)) { $sold_prods[] = $row; }

// 4. Rejected (Ditolak)
$rejected_prods = [];
$q_rej = mysqli_query($koneksi, "SELECT p.*, s.shop_name FROM products p JOIN shops s ON p.shop_id = s.shop_id WHERE p.status = 'rejected' ORDER BY p.created_at DESC");
while($row = mysqli_fetch_assoc($q_rej)) { $rejected_prods[] = $row; }

// 5. Deleted (Sampah)
$deleted_prods = [];
$q_del = mysqli_query($koneksi, "SELECT p.*, s.shop_name FROM products p JOIN shops s ON p.shop_id = s.shop_id WHERE p.status = 'deleted' ORDER BY p.created_at DESC");
while($row = mysqli_fetch_assoc($q_del)) { $deleted_prods[] = $row; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoSwap - Produk</title>
    <link rel="icon" type="image/png" href="../../../Assets/img/auth/logo.png">
    <link rel="stylesheet" href="../../../Assets/css/role/admin/produk.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-count { background: #e74a3b; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 5px; font-weight: bold; }
        .badge-count.warn { background: #f6c23e; color: black; }
        .sold-badge-status { background: #1cc88a; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .tab-btn.active { border-bottom: 3px solid #FFD700; color: #000; font-weight: 700; }
        
        /* Modal Styles */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: white; width: 500px; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .modal-actions { display: flex; gap: 10px; margin-top: 25px; }
        
        /* Buttons */
        .btn-verify { flex: 1; background: #28a745; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-verify:hover { background: #218838; }
        .btn-reject { flex: 1; background: #dc3545; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-reject:hover { background: #c82333; }
        .btn-restore { flex: 1; background: #36b9cc; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-restore:hover { background: #2c9faf; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">ECO<span>SWAP</span></div>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
            <li class="active">
                <a href="produk.php"><i class="fas fa-box"></i> <span>Produk</span> 
                <?php if($pending_count > 0): ?><span class="badge-count warn"><?php echo $pending_count; ?></span><?php endif; ?>
                </a>
            </li>
            <li><a href="pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a></li>
            <li><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
            <li>
                <a href="laporan.php"><i class="fas fa-headset"></i> <span>Laporan</span> 
                <?php if($report_count > 0): ?><span class="badge-count"><?php echo $report_count; ?></span><?php endif; ?>
                </a>
            </li>
        </ul>
        <div class="sidebar-footer"><a href="../../../../index.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text"><h2>Manajemen Produk</h2></div>
        </header>

        <section class="card-panel">
            <div class="tabs-container">
                <button class="tab-btn active" onclick="switchTab('pending')">
                    Menunggu Verifikasi 
                    <?php if($pending_count > 0): ?>
                        <span style="background:#f6c23e; padding:2px 6px; border-radius:4px; font-size:0.8rem; margin-left:5px; color:#fff;"><?php echo $pending_count;?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn" onclick="switchTab('active')">Produk Aktif</button>
                <button class="tab-btn" onclick="switchTab('sold')">Terjual</button>
                <button class="tab-btn" onclick="switchTab('rejected')">Ditolak</button>
                <button class="tab-btn" onclick="switchTab('deleted')">Dihapus</button>
            </div>

            <div id="tab-pending" class="tab-content active">
                <table class="product-table">
                    <thead><tr><th>Foto</th><th>Nama Produk</th><th>Penjual</th><th>Harga</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if(empty($pending_prods)): ?><tr><td colspan="5" align="center" style="padding:40px; color:#888;">Tidak ada produk baru.</td></tr><?php else: ?>
                        <?php foreach($pending_prods as $p): ?>
                        <tr>
                            <td><img src="<?php echo $p['image']; ?>" class="img-thumb"></td>
                            <td><div class="product-name"><?php echo $p['name']; ?></div><small style="color:#888;"><?php echo $p['category']; ?></small></td>
                            <td><?php echo $p['shop_name']; ?></td>
                            <td>Rp <?php echo number_format($p['price'],0,',','.'); ?></td>
                            <td><button class="btn-action btn-view" onclick='openModal(<?php echo json_encode($p); ?>, "pending")'><i class="fas fa-search"></i> Tinjau</button></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="tab-active" class="tab-content">
                <table class="product-table">
                    <thead><tr><th>Foto</th><th>Nama Produk</th><th>Penjual</th><th>Harga</th><th>Terjual</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if(empty($active_prods)): ?><tr><td colspan="7" align="center">Belum ada produk aktif.</td></tr><?php else: ?>
                        <?php foreach($active_prods as $p): ?>
                        <tr>
                            <td><img src="<?php echo $p['image']; ?>" class="img-thumb"></td>
                            <td><div class="product-name"><?php echo $p['name']; ?></div></td>
                            <td><?php echo $p['shop_name']; ?></td>
                            <td>Rp <?php echo number_format($p['price'],0,',','.'); ?></td>
                            <td><span class="sold-badge"><?php echo $p['sold_count']; ?></span></td>
                            <td><span style="color:green; font-weight:bold;">Active</span></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Pindahkan produk ke sampah?')">
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                    <button class="btn-action btn-delete"><i class="fas fa-trash"></i> Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="tab-sold" class="tab-content">
                <table class="product-table">
                    <thead><tr><th>Foto</th><th>Nama Produk</th><th>Penjual</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if(empty($sold_prods)): ?><tr><td colspan="6" align="center">Belum ada produk terjual.</td></tr><?php else: ?>
                        <?php foreach($sold_prods as $p): ?>
                        <tr>
                            <td><img src="<?php echo $p['image']; ?>" class="img-thumb"></td>
                            <td><div class="product-name"><?php echo $p['name']; ?></div></td>
                            <td><?php echo $p['shop_name']; ?></td>
                            <td>Rp <?php echo number_format($p['price'],0,',','.'); ?></td>
                            <td><span class="sold-badge-status">Terjual</span></td>
                            <td><span style="color:#888; font-size:0.8rem;">-</span></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="tab-rejected" class="tab-content">
                <table class="product-table">
                    <thead><tr><th>Foto</th><th>Nama Produk</th><th>Penjual</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if(empty($rejected_prods)): ?><tr><td colspan="6" align="center">Tidak ada produk ditolak.</td></tr><?php else: ?>
                        <?php foreach($rejected_prods as $p): ?>
                        <tr>
                            <td><img src="<?php echo $p['image']; ?>" class="img-thumb"></td>
                            <td><div class="product-name"><?php echo $p['name']; ?></div></td>
                            <td><?php echo $p['shop_name']; ?></td>
                            <td>Rp <?php echo number_format($p['price'],0,',','.'); ?></td>
                            <td><span style="color:red; font-weight:bold;">Ditolak</span></td>
                            <td>
                                <button class="btn-action btn-view" onclick='openModal(<?php echo json_encode($p); ?>, "rejected")'>Opsi</button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="tab-deleted" class="tab-content">
                <table class="product-table">
                    <thead><tr><th>Foto</th><th>Nama Produk</th><th>Penjual</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php if(empty($deleted_prods)): ?><tr><td colspan="6" align="center">Tidak ada produk dihapus.</td></tr><?php else: ?>
                        <?php foreach($deleted_prods as $p): ?>
                        <tr>
                            <td><img src="<?php echo $p['image']; ?>" class="img-thumb"></td>
                            <td><div class="product-name"><?php echo $p['name']; ?></div></td>
                            <td><?php echo $p['shop_name']; ?></td>
                            <td>Rp <?php echo number_format($p['price'],0,',','.'); ?></td>
                            <td><span style="color:#888; font-weight:bold;">Deleted</span></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Pulihkan produk ini?')">
                                    <input type="hidden" name="action" value="restore"><input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                    <button class="btn-action" style="background:#36b9cc; color:white;"><i class="fas fa-undo"></i> Pulihkan</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
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
                <img id="mImg" src="" style="width:100%; height:250px; object-fit:cover; border-radius:8px; margin-bottom:15px;">
                <h2 id="mName" style="font-size:1.4rem; margin-bottom:5px;"></h2>
                <div style="font-size:0.9rem; color:#666; margin-bottom:15px;">Penjual: <strong id="mSeller"></strong></div>
                <div style="background:#f9f9f9; padding:15px; border-radius:8px; font-size:0.9rem; line-height:1.5; margin-bottom:15px;">
                    <strong>Deskripsi:</strong><br><span id="mDesc"></span>
                </div>
                <div style="display:flex; justify-content:space-between; font-weight:bold; font-size:1.1rem;">
                    <span id="mPrice" style="color:#28a745;"></span>
                    <span id="mCondition" style="color:#555; font-size:0.9rem; font-weight:normal; background:#eee; padding:2px 8px; border-radius:4px;"></span>
                </div>
            </div>
            
            <form method="POST" class="modal-actions" id="modalActions">
                <input type="hidden" name="product_id" id="mId">
            </form>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById('tab-' + tab).classList.add('active');
            event.target.classList.add('active');
        }

        function openModal(data, statusType) {
            console.log('Opening modal with data:', data, 'Status:', statusType);
            
            document.getElementById('mId').value = data.product_id;
            document.getElementById('mImg').src = data.image;
            document.getElementById('mName').innerText = data.name;
            document.getElementById('mSeller').innerText = data.shop_name;
            document.getElementById('mDesc').innerText = data.description;
            document.getElementById('mPrice').innerText = 'Rp ' + parseInt(data.price).toLocaleString('id-ID');
            document.getElementById('mCondition').innerText = data.condition;

            const actionsDiv = document.getElementById('modalActions');
            actionsDiv.innerHTML = '<input type="hidden" name="product_id" value="' + data.product_id + '">';

            if (statusType === 'pending') {
                actionsDiv.innerHTML += '<button type="submit" name="action" value="reject" class="btn-reject" onclick="return confirm(\'Tolak produk ini?\')"><i class="fas fa-times"></i> Tolak</button>' +
                    '<button type="submit" name="action" value="verify" class="btn-verify" onclick="return confirm(\'Setujui produk ini?\')"><i class="fas fa-check"></i> Setujui & Publish</button>';
            } else if (statusType === 'rejected') {
                actionsDiv.innerHTML += '<button type="submit" name="action" value="delete" class="btn-reject" onclick="return confirm(\'Hapus permanen?\')"><i class="fas fa-trash"></i> Hapus</button>' +
                    '<button type="submit" name="action" value="verify" class="btn-verify" onclick="return confirm(\'Aktifkan kembali produk ini?\')"><i class="fas fa-check"></i> Terima Kembali</button>';
            }

            document.getElementById('productModal').classList.add('open');
        }

        function closeModal() { 
            document.getElementById('productModal').classList.remove('open'); 
        }
        
        window.onclick = function(e) { 
            if(e.target.classList.contains('modal-overlay')) closeModal(); 
        };
        
        // Real-Time Update Function
        function updateProducts() {
            fetch('produk.php?ajax=get_products')
                .then(response => response.json())
                .then(data => {
                    // Update badge di sidebar
                    const produkBadge = document.querySelector('.nav-links li:nth-child(2) .badge-count');
                    const laporanBadge = document.querySelector('.nav-links li:nth-child(5) .badge-count');
                    
                    if (data.pending_count > 0) {
                        if (produkBadge) {
                            produkBadge.textContent = data.pending_count;
                        } else {
                            document.querySelector('.nav-links li:nth-child(2) a').insertAdjacentHTML('beforeend', '<span class="badge-count warn">' + data.pending_count + '</span>');
                        }
                    } else if (produkBadge) {
                        produkBadge.remove();
                    }
                    
                    if (data.report_count > 0) {
                        if (laporanBadge) {
                            laporanBadge.textContent = data.report_count;
                        } else {
                            document.querySelector('.nav-links li:nth-child(5) a').insertAdjacentHTML('beforeend', '<span class="badge-count">' + data.report_count + '</span>');
                        }
                    } else if (laporanBadge) {
                        laporanBadge.remove();
                    }
                    
                    // Update badge di tab
                    const tabBadge = document.querySelector('.tab-btn span');
                    if (data.pending_count > 0) {
                        if (tabBadge) {
                            tabBadge.textContent = data.pending_count;
                        } else {
                            document.querySelector('.tab-btn').insertAdjacentHTML('beforeend', '<span style="background:#f6c23e; padding:2px 6px; border-radius:4px; font-size:0.8rem; margin-left:5px; color:#fff;">' + data.pending_count + '</span>');
                        }
                    } else if (tabBadge) {
                        tabBadge.remove();
                    }
                    
                    // Update tabel produk pending (hanya jika tab pending aktif)
                    const pendingTab = document.getElementById('tab-pending');
                    if (pendingTab && pendingTab.classList.contains('active')) {
                        const tbody = pendingTab.querySelector('tbody');
                        if (data.pending_products.length > 0) {
                            let html = '';
                            data.pending_products.forEach(p => {
                                const pJson = JSON.stringify(p).replace(/'/g, '&apos;');
                                html += '<tr>' +
                                    '<td><img src="' + p.image + '" class="img-thumb"></td>' +
                                    '<td><div class="product-name">' + p.name + '</div><small style="color:#888;">' + p.category + '</small></td>' +
                                    '<td>' + p.shop_name + '</td>' +
                                    '<td>Rp ' + parseInt(p.price).toLocaleString('id-ID') + '</td>' +
                                    '<td><button class="btn-action btn-view" onclick=\'openModal(' + pJson + ', "pending")\'><i class="fas fa-search"></i> Tinjau</button></td>' +
                                    '</tr>';
                            });
                            tbody.innerHTML = html;
                        } else {
                            tbody.innerHTML = '<tr><td colspan="5" align="center" style="padding:40px; color:#888;">Tidak ada produk baru.</td></tr>';
                        }
                    }
                })
                .catch(error => console.error('Error updating products:', error));
        }
        
        // Update setiap 5 detik
        setInterval(updateProducts, 5000);
        
        // Update pertama kali setelah 2 detik
        setTimeout(updateProducts, 2000);
    </script>
</body>
</html>
