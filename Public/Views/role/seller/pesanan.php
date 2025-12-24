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
    header("Location: dashboard.php");
    exit();
}
$shop = mysqli_fetch_assoc($q_shop);
$shop_id = $shop['shop_id'];

// --- 1. LOGIKA UPDATE STATUS PESANAN ---

// A. Konfirmasi Pesanan (Pending -> Processed)
if (isset($_POST['action']) && $_POST['action'] == 'confirm_order') {
    $oid = $_POST['order_id'];
    // Ubah status jadi 'processed'
    mysqli_query($koneksi, "UPDATE orders SET status='processed' WHERE order_id='$oid' AND shop_id='$shop_id'");
    echo "<script>alert('Pesanan dikonfirmasi dan sedang diproses!'); window.location.href='pesanan.php';</script>";
}

// B. Kirim Pesanan / Input Resi (Processed -> Shipping)
if (isset($_POST['action']) && $_POST['action'] == 'ship_order') {
    $oid = $_POST['order_id'];
    $resi = mysqli_real_escape_string($koneksi, $_POST['tracking_number']);
    
    if(empty($resi)) {
        echo "<script>alert('Harap masukkan nomor resi!');</script>";
    } else {
        mysqli_query($koneksi, "UPDATE orders SET status='shipping', tracking_number='$resi' WHERE order_id='$oid' AND shop_id='$shop_id'");
        echo "<script>alert('Pesanan telah dikirim! Resi berhasil disimpan.'); window.location.href='pesanan.php';</script>";
    }
}

// --- 2. AMBIL DATA PESANAN (READ) ---
$orders = [];

// PERBAIKAN QUERY: Menggunakan LEFT JOIN agar tidak error jika data alamat hilang
$query_orders = "SELECT o.*, p.name as product_name, p.image as product_image, 
                 u.name as buyer_name, u.email as buyer_email,
                 a.full_address, a.phone_number as buyer_phone
                 FROM orders o
                 JOIN products p ON o.product_id = p.product_id
                 JOIN users u ON o.buyer_id = u.user_id
                 LEFT JOIN addresses a ON o.address_id = a.address_id
                 WHERE o.shop_id = '$shop_id'
                 ORDER BY o.created_at DESC";

$res = mysqli_query($koneksi, $query_orders);

// Cek apakah query berhasil
if (!$res) {
    die("Query Error: " . mysqli_error($koneksi));
}

while($row = mysqli_fetch_assoc($res)) {
    // Mapping status DB ke Tab Frontend
    $tab_status = $row['status'];
    // Gabungkan completed & reviewed ke tab 'completed'
    if($row['status'] == 'reviewed') $tab_status = 'completed';

    // Fallback jika alamat dihapus
    $alamat_fix = !empty($row['full_address']) ? $row['full_address'] . " (" . $row['buyer_phone'] . ")" : "Alamat tidak ditemukan";

    $orders[] = [
        'id' => $row['order_id'],
        'status' => $tab_status,      // Untuk filter tab JS
        'db_status' => $row['status'], // Status asli database
        'product' => $row['product_name'],
        'price' => (int)$row['total_price'],
        'img' => $row['product_image'],
        'quantity' => isset($row['quantity']) ? $row['quantity'] : 1, // Pastikan kolom quantity ada di DB
        'buyer' => !empty($row['buyer_name']) ? $row['buyer_name'] : $row['buyer_email'],
        'address' => $alamat_fix,
        'shipping' => $row['shipping_method'],
        'tracking' => $row['tracking_number']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Masuk - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/pesanan.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Tambahan untuk Tab Baru */
        .tabs-container {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
        }
        .tab-btn { white-space: nowrap; }
        
        /* Warna Status Badge */
        .status-processed { background-color: #e2e6ea; color: #333; }
        .status-reviewed { background-color: #d1e7dd; color: #0f5132; }
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
                <div class="page-title">Pesanan Masuk</div>
            </div>

            <div class="content">
                <div class="order-container">
                    
                    <div class="tabs-container">
                        <button class="tab-btn active" onclick="switchTab('pending', this)">Menunggu</button>
                        <button class="tab-btn" onclick="switchTab('processed', this)">Diproses</button>
                        <button class="tab-btn" onclick="switchTab('shipping', this)">Dikirim</button>
                        <button class="tab-btn" onclick="switchTab('completed', this)">Selesai</button>
                    </div>

                    <div class="order-list" id="orderList">
                        </div>

                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="shipModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">Kirim Pesanan</div>
                <span class="close-modal" onclick="closeModal('shipModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="ship_order">
                <input type="hidden" name="order_id" id="shipOrderId">

                <div class="form-group">
                    <label class="form-label">Jasa Kirim</label>
                    <input type="text" class="form-input" id="shipCourier" readonly style="background:#f9f9f9;">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nomor Resi</label>
                    <input type="text" name="tracking_number" class="form-input" placeholder="Masukkan nomor resi pengiriman" required>
                    <small style="color:#666; font-size:0.8rem;">Pastikan nomor resi valid agar bisa dilacak.</small>
                </div>
                
                <button type="submit" class="btn-submit">Simpan & Kirim</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="confirmProcessModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">Proses Pesanan</div>
                <span class="close-modal" onclick="closeModal('confirmProcessModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="confirm_order">
                <input type="hidden" name="order_id" id="processOrderId">
                
                <p style="margin-bottom:20px; color:#333;">
                    Apakah Anda siap memproses pesanan ini? <br>
                    Status akan berubah menjadi <strong>Sedang Diproses</strong>.
                </p>

                <button type="submit" class="btn-submit" style="background: var(--primary); color:#000;">Ya, Proses Sekarang</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">Detail Pesanan</div>
                <span class="close-modal" onclick="closeModal('detailModal')">&times;</span>
            </div>
            <div id="detailContent">
                </div>
            <button class="btn-submit" style="background:#333; color:white; margin-top:15px;" onclick="closeModal('detailModal')">Tutup</button>
        </div>
    </div>

    <script>
        function goToDashboard() {
            window.location.href = '../buyer/dashboard.php';
        }
        
        // DATA PESANAN DARI DATABASE (Inject PHP ke JS)
        const orders = <?php echo json_encode($orders); ?>;

        let currentTab = 'pending';

        // RENDER LIST
        function renderOrders() {
            const container = document.getElementById('orderList');
            container.innerHTML = '';

            const filtered = orders.filter(o => o.status === currentTab);

            if (filtered.length === 0) {
                container.innerHTML = `<div style="text-align:center; padding:40px; color:#888;">Tidak ada pesanan di tab ini.</div>`;
                return;
            }

            filtered.forEach(order => {
                let actionArea = '';
                let statusBadge = '';

                // LOGIKA TAMPILAN PER STATUS
                if (order.status === 'pending') {
                    statusBadge = `<span class="status-label status-pending">Menunggu Konfirmasi</span>`;
                    // Tombol untuk memproses pesanan
                    actionArea = `<button class="btn-action btn-confirm" onclick="openProcessModal(${order.id})">Proses Pesanan</button>`;
                
                } else if (order.status === 'processed') {
                    statusBadge = `<span class="status-label status-processed">Sedang Diproses</span>`;
                    // Tombol untuk input resi (Kirim)
                    actionArea = `<button class="btn-action btn-detail" style="background:var(--primary); color:#000;" onclick="openShipModal(${order.id}, '${order.shipping}')">Input Resi</button>`;
                
                } else if (order.status === 'shipping') {
                    statusBadge = `<span class="status-label status-shipping">Sedang Dikirim</span>`;
                    actionArea = `<button class="btn-action btn-detail" onclick="openDetailModal(${order.id})">Lihat Detail</button>`;
                
                } else if (order.status === 'completed') {
                    // Cek sub-status apakah sudah direview
                    if(order.db_status === 'reviewed') {
                        statusBadge = `<span class="status-label status-reviewed">Sudah Direview</span>`;
                    } else {
                        statusBadge = `<span class="status-label status-done">Selesai</span>`;
                    }
                    
                    actionArea = `
                        <div style="display:flex; align-items:center; gap:15px;">
                            <span class="done-text"><i class="fas fa-check-circle"></i> Selesai</span>
                            <button class="btn-action btn-detail" onclick="openDetailModal(${order.id})">Detail</button>
                        </div>
                    `;
                }

                const card = document.createElement('div');
                card.className = 'order-card';
                card.innerHTML = `
                    <div class="order-left">
                        <img src="${order.img}" class="order-img" alt="${order.product}">
                        <div class="order-info">
                            <h3>${order.product}</h3>
                            <p>Pembeli: ${order.buyer}</p>
                            <p class="order-price">Rp ${order.price.toLocaleString('id-ID')}</p>
                            ${statusBadge}
                        </div>
                    </div>
                    <div class="order-right">
                        ${actionArea}
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function switchTab(status, btn) {
            currentTab = status;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderOrders();
        }

        // --- 1. MODAL PROSES (PENDING -> PROCESSED) ---
        function openProcessModal(id) {
            document.getElementById('processOrderId').value = id;
            document.getElementById('confirmProcessModal').classList.add('open');
        }

        // --- 2. MODAL KIRIM / RESI (PROCESSED -> SHIPPING) ---
        function openShipModal(id, courier) {
            document.getElementById('shipOrderId').value = id;
            document.getElementById('shipCourier').value = courier;
            document.getElementById('shipModal').classList.add('open');
        }

        // --- 3. MODAL DETAIL ---
        function openDetailModal(id) {
            const order = orders.find(o => o.id === id);
            if (!order) return;

            const resiDisplay = order.tracking ? order.tracking : '-';

            const content = document.getElementById('detailContent');
            content.innerHTML = `
                <div class="detail-row"><span class="detail-label">Nama Produk</span> <span class="detail-val">${order.product}</span></div>
                <div class="detail-row"><span class="detail-label">Jumlah</span> <span class="detail-val">${order.quantity} pcs</span></div>
                <div class="detail-row"><span class="detail-label">Total Harga</span> <span class="detail-val">Rp ${order.price.toLocaleString('id-ID')}</span></div>
                <div class="detail-row"><span class="detail-label">Jasa Kirim</span> <span class="detail-val">${order.shipping}</span></div>
                <div class="detail-row"><span class="detail-label">Nomor Resi</span> <span class="detail-val" style="font-weight:bold; color:var(--primary);">${resiDisplay}</span></div>
                
                <div style="margin:15px 0; border-top:1px solid #eee; padding-top:10px;">
                    <strong>Info Penerima</strong>
                </div>
                <div class="detail-row"><span class="detail-label">Nama</span> <span class="detail-val">${order.buyer}</span></div>
                <div class="detail-row"><span class="detail-label">Alamat</span> <span class="detail-val" style="text-align:right; font-size:0.9rem;">${order.address}</span></div>
            `;
            document.getElementById('detailModal').classList.add('open');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('open');
        }

        // INIT
        document.addEventListener('DOMContentLoaded', renderOrders);
    </script>
</body>
</html>