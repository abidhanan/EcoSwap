<?php
session_start();

// Koneksi Database
include '../../../Auth/koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) { header("Location: ../../auth/login.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Cek Toko
$q_shop = mysqli_query($koneksi, "SELECT shop_id, shop_name FROM shops WHERE user_id = '$user_id'");
if(mysqli_num_rows($q_shop) == 0){ header("Location: dashboard.php"); exit(); }
$shop = mysqli_fetch_assoc($q_shop);
$shop_id = $shop['shop_id'];
$shop_name = $shop['shop_name'];

// --- AMBIL BIAYA ADMIN UNTUK KALKULASI DISPLAY ---
$q_fee = mysqli_query($koneksi, "SELECT setting_value FROM system_settings WHERE setting_key = 'admin_fee'");
$d_fee = mysqli_fetch_assoc($q_fee);
$system_admin_fee = isset($d_fee['setting_value']) ? (int)$d_fee['setting_value'] : 1000;

// ==========================================
// 1. LOGIKA UPDATE STATUS & NOTIFIKASI
// ==========================================

function sendNotification($koneksi, $user_id, $title, $message) {
    $title = mysqli_real_escape_string($koneksi, $title);
    $message = mysqli_real_escape_string($koneksi, $message);
    mysqli_query($koneksi, "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ('$user_id', '$title', '$message', 0, NOW())");
}

// A. Konfirmasi Pesanan (Pending -> Processed)
if (isset($_POST['action']) && $_POST['action'] == 'confirm_order') {
    $oid = $_POST['order_id'];
    $q_ord = mysqli_query($koneksi, "SELECT buyer_id, invoice_code FROM orders WHERE order_id='$oid'");
    $d_ord = mysqli_fetch_assoc($q_ord);
    
    if(mysqli_query($koneksi, "UPDATE orders SET status='processed' WHERE order_id='$oid' AND shop_id='$shop_id'")) {
        sendNotification($koneksi, $d_ord['buyer_id'], "Pesanan Diproses", "Pesanan #{$d_ord['invoice_code']} sedang disiapkan oleh penjual.");
        echo "<script>alert('Pesanan dikonfirmasi!'); window.location.href='pesanan.php';</script>";
    }
}

// B. Input Resi (Processed -> Shipping)
if (isset($_POST['action']) && $_POST['action'] == 'ship_order') {
    $oid = $_POST['order_id'];
    $resi = mysqli_real_escape_string($koneksi, $_POST['tracking_number']);
    
    if(empty($resi)) {
        echo "<script>alert('Harap masukkan nomor resi!');</script>";
    } else {
        $q_ord = mysqli_query($koneksi, "SELECT buyer_id, invoice_code FROM orders WHERE order_id='$oid'");
        $d_ord = mysqli_fetch_assoc($q_ord);

        if(mysqli_query($koneksi, "UPDATE orders SET status='shipping', tracking_number='$resi' WHERE order_id='$oid' AND shop_id='$shop_id'")) {
            sendNotification($koneksi, $d_ord['buyer_id'], "Pesanan Dikirim", "Pesanan #{$d_ord['invoice_code']} telah dikirim. No Resi: $resi");
            echo "<script>alert('Pesanan dikirim!'); window.location.href='pesanan.php';</script>";
        }
    }
}

// C. Konfirmasi Sampai (Shipping -> Delivered)
if (isset($_POST['action']) && $_POST['action'] == 'mark_delivered') {
    $oid = $_POST['order_id'];
    $q_ord = mysqli_query($koneksi, "SELECT buyer_id, invoice_code FROM orders WHERE order_id='$oid'");
    $d_ord = mysqli_fetch_assoc($q_ord);

    if(mysqli_query($koneksi, "UPDATE orders SET status='delivered' WHERE order_id='$oid' AND shop_id='$shop_id'")) {
        sendNotification($koneksi, $d_ord['buyer_id'], "Paket Sampai", "Paket untuk pesanan #{$d_ord['invoice_code']} telah tiba. Mohon konfirmasi penerimaan.");
        echo "<script>alert('Status diubah menjadi Sampai.'); window.location.href='pesanan.php';</script>";
    }
}

// ==========================================
// 2. AMBIL DATA PESANAN
// ==========================================
$orders = [];
// PERBAIKAN: Tambahkan u.profile_picture ke query
$query_orders = "SELECT o.*, p.name as product_name, p.image as product_image, 
                 u.name as buyer_name, u.email as buyer_email, u.profile_picture,
                 a.full_address, a.phone_number as buyer_phone
                 FROM orders o
                 JOIN products p ON o.product_id = p.product_id
                 JOIN users u ON o.buyer_id = u.user_id
                 LEFT JOIN addresses a ON o.address_id = a.address_id
                 WHERE o.shop_id = '$shop_id'
                 ORDER BY o.created_at DESC";
$res = mysqli_query($koneksi, $query_orders);

while($row = mysqli_fetch_assoc($res)) {
    // Logic Tab
    $tab_status = $row['status'];
    if($row['status'] == 'delivered') $tab_status = 'shipping'; 
    if($row['status'] == 'reviewed') $tab_status = 'completed';

    // Parse Shipping (ambil nama dan harga)
    $shipping_raw = $row['shipping_method'];
    $parts = explode(' | ', $shipping_raw);
    $shipping_label_full = isset($parts[0]) ? $parts[0] : $shipping_raw; // "JNE (Rp 15.000)"
    
    // Bersihkan nama kurir untuk tampilan (hapus harga dalam kurung)
    $shipping_clean = preg_replace('/\s*\(Rp.*?\)/', '', $shipping_label_full);

    // Ambil nominal ongkir dari string untuk kalkulasi total
    $shipping_cost = 0;
    if (preg_match('/\(Rp ([\d\.]+)\)/', $shipping_label_full, $matches)) {
        $shipping_cost = (int)str_replace('.', '', $matches[1]);
    }

    $payment_label = isset($parts[1]) ? $parts[1] : 'Manual / COD';
    $alamat_fix = !empty($row['full_address']) ? $row['full_address'] . " (" . $row['buyer_phone'] . ")" : "Alamat tidak ditemukan";

    // Hitung Grand Total (Produk + Ongkir + Admin)
    $product_price = (int)$row['total_price'];
    $admin_fee = $system_admin_fee;
    $grand_total = $product_price + $shipping_cost + $admin_fee;

    // Avatar Logic
    $buyer_name = !empty($row['buyer_name']) ? $row['buyer_name'] : $row['buyer_email'];
    $buyer_avatar = !empty($row['profile_picture']) ? $row['profile_picture'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($buyer_name);

    $orders[] = [
        'id' => $row['order_id'],
        'invoice' => $row['invoice_code'],
        'status' => $tab_status,
        'db_status' => $row['status'],
        'product' => $row['product_name'],
        'price_raw' => $product_price,
        'total_price_fmt' => 'Rp ' . number_format($grand_total, 0, ',', '.'), // Tampilkan Grand Total
        'shipping_cost_fmt' => 'Rp ' . number_format($shipping_cost, 0, ',', '.'),
        'admin_fee_fmt' => 'Rp ' . number_format($admin_fee, 0, ',', '.'),
        'img' => $row['product_image'],
        'quantity' => 1,
        'buyer' => $buyer_name,
        'buyer_avatar' => $buyer_avatar,
        'address' => $alamat_fix,
        'shipping' => $shipping_label_full,
        'shipping_clean' => $shipping_clean,
        'payment' => $payment_label,
        'tracking' => $row['tracking_number'],
        'date' => date('d M Y H:i', strtotime($row['created_at']))
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
        /* Base & Layout */
        .content { padding: 30px 5%; background-color: #f8f9fa; min-height: 100vh; }
        
        /* Tabs Styling */
        .tabs-container {
            display: flex; gap: 20px; border-bottom: 2px solid #eee; margin-bottom: 25px;
            overflow-x: auto; padding-bottom: 0;
        }
        .tab-btn {
            background: none; border: none; padding: 10px 15px; font-size: 1rem; color: #666;
            cursor: pointer; position: relative; font-weight: 500; transition: 0.3s;
        }
        .tab-btn.active { color: var(--primary); font-weight: 700; }
        .tab-btn.active::after {
            content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 3px;
            background-color: var(--primary); border-radius: 2px;
        }
        .tab-btn:hover { color: var(--primary); }

        /* Order Card Styling */
        .order-card {
            background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;
            display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;
            transition: transform 0.2s;
        }
        .order-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.06); }

        .order-left { display: flex; gap: 15px; flex: 1; }
        .order-img { width: 90px; height: 90px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }
        
        .order-info { display: flex; flex-direction: column; gap: 6px; }
        .order-meta { font-size: 0.85rem; color: #888; display: flex; gap: 8px; align-items: center; }
        .order-title { font-size: 1.1rem; color: #333; font-weight: 700; margin: 0; }
        .buyer-info { font-size: 0.9rem; color: #555; display: flex; align-items: center; gap: 8px; }
        .buyer-avatar-img { width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd; }
        .order-price { font-size: 1rem; font-weight: 700; color: var(--primary); }
        
        .order-right { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; min-width: 160px; }
        
        /* Status Badges */
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-processed { background: #e2e6ea; color: #383d41; border: 1px solid #d6d8db; }
        .status-shipping { background: #cce5ff; color: #004085; border: 1px solid #b8daff; }
        .status-delivered { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-done { background: #d1e7dd; color: #0f5132; }

        /* Buttons */
        .btn-action {
            padding: 9px 18px; border-radius: 6px; font-size: 0.85rem; cursor: pointer; border: none; font-weight: 600;
            transition: 0.2s; text-align: center; width: 100%; display: block; text-decoration: none;
        }
        .btn-confirm { background: var(--primary); color: #000; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn-confirm:hover { filter: brightness(0.9); transform: translateY(-1px); }
        .btn-detail { background: #fff; border: 1px solid #ddd; color: #555; }
        .btn-detail:hover { background: #f8f9fa; border-color: #bbb; color: #333; }

        /* Modal Customization */
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem; border-bottom: 1px dashed #f0f0f0; padding-bottom: 5px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #666; }
        .detail-val { font-weight: 600; color: #333; text-align: right; max-width: 65%; }

        /* Responsive */
        @media (max-width: 768px) {
            .order-card { flex-direction: column; }
            .order-right { width: 100%; flex-direction: row; justify-content: space-between; align-items: center; margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px; }
            .btn-action { width: auto; padding: 8px 14px; }
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-header"><div class="logo" onclick="goToDashboard()" style="cursor:pointer;">ECO<span>SWAP</span></div></div>
            <ul class="sidebar-menu">
                <li class="menu-item"><a href="../buyer/profil.php" class="menu-link"><i class="fas fa-user"></i><span>Biodata Diri</span></a></li>
                <li class="menu-item"><a href="../buyer/alamat.php" class="menu-link"><i class="fas fa-map-marker-alt"></i><span>Alamat</span></a></li>
                <li class="menu-item"><a href="../buyer/histori.php" class="menu-link"><i class="fas fa-history"></i><span>Histori</span></a></li>
                <li class="menu-item active"><a href="dashboard.php" class="menu-link"><i class="fas fa-store"></i><span>Toko Saya</span></a></li>
            </ul>
            <div class="sidebar-footer"><a href="../../../../index.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </aside>

        <main class="main-content-wrapper">
            <div class="header"><div class="page-title">Pesanan Masuk</div></div>
            <div class="content">
                <div class="order-container">
                    <div class="tabs-container">
                        <button class="tab-btn active" onclick="switchTab('pending', this)">Menunggu</button>
                        <button class="tab-btn" onclick="switchTab('processed', this)">Diproses</button>
                        <button class="tab-btn" onclick="switchTab('shipping', this)">Dikirim</button>
                        <button class="tab-btn" onclick="switchTab('completed', this)">Selesai</button>
                    </div>
                    <div class="order-list" id="orderList"></div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="shipModal">
        <div class="modal-container">
            <div class="modal-header"><div class="modal-title">Kirim Pesanan</div><span class="close-modal" onclick="closeModal('shipModal')">&times;</span></div>
            <form method="POST">
                <input type="hidden" name="action" value="ship_order">
                <input type="hidden" name="order_id" id="shipOrderId">
                <div class="form-group"><label class="form-label">Jasa Kirim</label><input type="text" class="form-input" id="shipCourier" readonly style="background:#f9f9f9; color:#555;"></div>
                <div class="form-group"><label class="form-label">Nomor Resi</label><input type="text" name="tracking_number" class="form-input" placeholder="Masukkan nomor resi pengiriman" required></div>
                <button type="submit" class="btn-submit" style="background:var(--primary); color:#000;">Simpan & Kirim</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="confirmProcessModal">
        <div class="modal-container">
            <div class="modal-header"><div class="modal-title">Proses Pesanan</div><span class="close-modal" onclick="closeModal('confirmProcessModal')">&times;</span></div>
            <form method="POST">
                <input type="hidden" name="action" value="confirm_order">
                <input type="hidden" name="order_id" id="processOrderId">
                <p style="margin-bottom:20px; color:#555; text-align:center;">
                    Pastikan stok barang tersedia.<br>Status pesanan akan berubah menjadi <strong>Sedang Diproses</strong>.
                </p>
                <button type="submit" class="btn-submit" style="background: var(--primary); color:#000;">Ya, Proses Sekarang</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">Rincian Pesanan</div>
                <span class="close-modal" onclick="closeModal('detailModal')">&times;</span>
            </div>
            <div id="detailContent" style="padding: 5px 0;">
                </div>
            <button class="btn-submit" style="background:#eee; color:#333; margin-top:15px; border:1px solid #ccc;" onclick="closeModal('detailModal')">Tutup</button>
        </div>
    </div>

    <form method="POST" id="formMarkDelivered" style="display:none;"><input type="hidden" name="action" value="mark_delivered"><input type="hidden" name="order_id" id="deliveredOrderId"></form>

    <script>
        function goToDashboard() { window.location.href = '../buyer/dashboard.php'; }
        const orders = <?php echo json_encode($orders); ?>;
        let currentTab = 'pending';

        function renderOrders() {
            const container = document.getElementById('orderList'); container.innerHTML = '';
            const filtered = orders.filter(o => o.status === currentTab);
            
            if (filtered.length === 0) { 
                container.innerHTML = `
                    <div style="text-align:center; padding:60px; color:#999;">
                        <i class="fas fa-box-open" style="font-size:3rem; margin-bottom:15px; opacity:0.3;"></i>
                        <br>Tidak ada pesanan di tab ini.
                    </div>`; 
                return; 
            }

            filtered.forEach(order => {
                let actionArea = '', statusBadge = '';
                
                // Status Logic & Action Buttons
                if (order.status === 'pending') {
                    statusBadge = `<span class="status-badge status-pending">Menunggu Konfirmasi</span>`;
                    actionArea = `<button class="btn-action btn-confirm" onclick="openProcessModal(${order.id})">Proses Pesanan</button>`;
                } else if (order.status === 'processed') {
                    statusBadge = `<span class="status-badge status-processed">Sedang Diproses</span>`;
                    actionArea = `<button class="btn-action btn-confirm" onclick="openShipModal(${order.id}, '${order.shipping_clean}')">Input Resi</button>`;
                } else if (order.status === 'shipping') {
                    if (order.db_status === 'shipping') {
                        statusBadge = `<span class="status-badge status-shipping">Sedang Dikirim</span>`;
                        actionArea = `<button class="btn-action btn-confirm" style="font-size:0.8rem; padding:8px 12px;" onclick="confirmArrived(${order.id})">Konfirmasi Sampai</button>`;
                    } else {
                        statusBadge = `<span class="status-badge status-delivered">Barang Sampai</span>`;
                    }
                } else if (order.status === 'completed') {
                    statusBadge = (order.db_status === 'reviewed') ? `<span class="status-badge status-done">Sudah Direview</span>` : `<span class="status-badge status-done">Selesai</span>`;
                }

                // TOMBOL DETAIL MUNCUL DI SEMUA STATUS
                const detailButton = `<button class="btn-action btn-detail" onclick="openDetailModal(${order.id})">Lihat Detail</button>`;

                container.innerHTML += `
                    <div class="order-card">
                        <div class="order-left">
                            <img src="${order.img}" class="order-img" alt="${order.product}">
                            <div class="order-info">
                                <div class="order-meta">
                                    <span>${order.invoice}</span> • <span>${order.date}</span>
                                </div>
                                <h3 class="order-title">${order.product}</h3>
                                <div class="buyer-info">
                                    <img src="${order.buyer_avatar}" class="buyer-avatar-img">
                                    <span>${order.buyer}</span>
                                </div>
                                <div style="margin-top:4px;">${statusBadge}</div>
                            </div>
                        </div>
                        <div class="order-right">
                            <div class="order-price">${order.total_price_fmt}</div>
                            <div style="margin-top:auto; width:100%; display:flex; flex-direction:column; gap:8px;">
                                ${actionArea}
                                ${detailButton}
                            </div>
                        </div>
                    </div>`;
            });
        }

        function switchTab(status, btn) { currentTab = status; document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active')); btn.classList.add('active'); renderOrders(); }
        function openProcessModal(id) { document.getElementById('processOrderId').value = id; document.getElementById('confirmProcessModal').classList.add('open'); }
        function openShipModal(id, courier) { document.getElementById('shipOrderId').value = id; document.getElementById('shipCourier').value = courier; document.getElementById('shipModal').classList.add('open'); }
        function confirmArrived(id) { if(confirm("Yakin barang sudah sampai?")) { document.getElementById('deliveredOrderId').value = id; document.getElementById('formMarkDelivered').submit(); } }
        function closeModal(modalId) { document.getElementById(modalId).classList.remove('open'); }

        // --- FUNGSI DETAIL OVERLAY ---
        function openDetailModal(id) {
            const order = orders.find(o => o.id === id); if (!order) return;
            const resi = order.tracking ? order.tracking : '-';
            const content = document.getElementById('detailContent');
            
            content.innerHTML = `
                <div style="margin-bottom:15px; padding-bottom:15px; border-bottom:1px solid #eee;">
                    <div style="font-size:0.85rem; color:#888;">No. Invoice</div>
                    <div style="font-weight:700; color:#333;">${order.invoice}</div>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Nama Produk</span>
                    <span class="detail-val">${order.product}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Harga Barang</span>
                    <span class="detail-val">Rp ${order.price_raw.toLocaleString('id-ID')}</span>
                </div>
                
                <div style="margin-top:15px; margin-bottom:10px; font-weight:700; color:#555;">Rincian Biaya</div>
                <div class="detail-row">
                    <span class="detail-label">Ongkos Kirim</span>
                    <span class="detail-val">${order.shipping_cost_fmt}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Biaya Admin</span>
                    <span class="detail-val">${order.admin_fee_fmt}</span>
                </div>
                <div class="detail-row" style="border-top: 1px dashed #ccc; padding-top: 5px; margin-top: 5px;">
                    <span class="detail-label" style="font-weight:700;">Total Keseluruhan</span>
                    <span class="detail-val" style="color:var(--primary); font-weight:800; font-size:1.1rem;">${order.total_price_fmt}</span>
                </div>
                
                <div style="margin-top:15px; margin-bottom:10px; font-weight:700; color:#555;">Pengiriman</div>
                <div class="detail-row">
                    <span class="detail-label">Jasa Kirim</span>
                    <span class="detail-val">${order.shipping_clean}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">No. Resi</span>
                    <span class="detail-val" style="letter-spacing:1px; background:#f5f5f5; padding:2px 6px; border-radius:4px;">${resi}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Metode Bayar</span>
                    <span class="detail-val">${order.payment}</span>
                </div>
                
                <div style="margin-top:15px; margin-bottom:10px; font-weight:700; color:#555;">Info Pembeli</div>
                <div class="detail-row" style="align-items: center;">
                    <span class="detail-label">Nama</span>
                    <span class="detail-val" style="display:flex; align-items:center; gap:5px;">
                        <img src="${order.buyer_avatar}" style="width:20px; height:20px; border-radius:50%; object-fit:cover;">
                        ${order.buyer}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Alamat</span>
                    <span class="detail-val" style="font-size:0.85rem; line-height:1.4;">${order.address}</span>
                </div>
            `;
            document.getElementById('detailModal').classList.add('open');
        }

        document.addEventListener('DOMContentLoaded', renderOrders);
    </script>
</body>
</html>