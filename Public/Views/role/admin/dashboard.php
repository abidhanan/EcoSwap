<?php
session_start();
// Koneksi Database
include '../../../Auth/koneksi.php';

// Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../auth/login.php");
    exit();
}

// AJAX Endpoint untuk Mark Notification as Read
if (isset($_GET['ajax']) && $_GET['ajax'] == 'mark_notif_read') {
    $notif_id = isset($_GET['notif_id']) ? (int)$_GET['notif_id'] : 0;
    if ($notif_id > 0) {
        mysqli_query($koneksi, "UPDATE notifications SET is_read = 1 WHERE notif_id = '$notif_id'");
    }
    exit();
}

// ==========================================
// LOGIKA TARIK SALDO ADMIN (WITHDRAW)
// ==========================================
if (isset($_POST['action']) && $_POST['action'] == 'withdraw') {
    $amount = (int)$_POST['amount'];
    $method = mysqli_real_escape_string($koneksi, $_POST['destination_method']); 
    $account = mysqli_real_escape_string($koneksi, $_POST['account_number']);
    
    // Ambil saldo admin saat ini
    $q_balance = mysqli_query($koneksi, "SELECT setting_value FROM system_settings WHERE setting_key = 'admin_balance'");
    $d_balance = mysqli_fetch_assoc($q_balance);
    $current_balance = isset($d_balance['setting_value']) ? (int)$d_balance['setting_value'] : 0;
    
    // Validasi Saldo
    if ($amount > $current_balance) {
        echo "<script>alert('Saldo tidak mencukupi untuk penarikan ini.'); window.location.href='dashboard.php';</script>";
    } elseif ($amount <= 0) {
        echo "<script>alert('Nominal penarikan tidak valid.'); window.location.href='dashboard.php';</script>";
    } else {
        // Mulai Transaksi Database (agar aman)
        mysqli_begin_transaction($koneksi);
        
        try {
            // 1. Kurangi Saldo Admin
            $new_balance = $current_balance - $amount;
            
            // Cek apakah admin_balance sudah ada
            if (mysqli_num_rows($q_balance) > 0) {
                $update_saldo = mysqli_query($koneksi, "UPDATE system_settings SET setting_value = '$new_balance' WHERE setting_key = 'admin_balance'");
            } else {
                $update_saldo = mysqli_query($koneksi, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('admin_balance', '$new_balance')");
            }
            
            // 2. Catat di Riwayat Transaksi (shop_id = 0 untuk admin)
            $desc = "Penarikan Admin ke $method ($account)";
            $insert_trans = mysqli_query($koneksi, "INSERT INTO transactions (shop_id, type, amount, description, created_at) 
                                                    VALUES (0, 'out', '$amount', '$desc', NOW())");
            
            if ($update_saldo && $insert_trans) {
                mysqli_commit($koneksi);
                echo "<script>alert('Permintaan penarikan berhasil diproses!'); window.location.href='dashboard.php';</script>";
            } else {
                throw new Exception("Gagal update database");
            }
            
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo "<script>alert('Terjadi kesalahan sistem. Silakan coba lagi.'); window.location.href='dashboard.php';</script>";
        }
    }
    exit();
}

// AJAX Endpoint untuk Real-Time Update
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_stats') {
    header('Content-Type: application/json');
    
    $admin_id = $_SESSION['user_id'];
    
    // Ambil statistik terbaru
    $total_users = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role != 'admin'"))['total'];
    $pending_products = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM products WHERE status = 'pending' OR status = 'review'"))['total'];
    $transaction_reports = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'"))['total'];
    $chat_reports = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM notifications WHERE user_id = '$admin_id' AND title = 'Laporan Chat' AND is_read = 0"))['total'];
    $active_reports = $transaction_reports + $chat_reports;
    
    // Ambil saldo admin dari database (bukan hitung ulang)
    $q_admin_bal = mysqli_query($koneksi, "SELECT setting_value FROM system_settings WHERE setting_key = 'admin_balance'");
    $d_admin_bal = mysqli_fetch_assoc($q_admin_bal);
    $admin_balance_ajax = isset($d_admin_bal['setting_value']) ? (int)$d_admin_bal['setting_value'] : 0;
    
    // Transaksi terbaru
    $recent_orders = [];
    $q_recent = mysqli_query($koneksi, "
        SELECT o.invoice_code, o.total_price, o.status, o.shipping_method,
               u1.name as buyer_name, u1.profile_picture as buyer_pic,
               s.shop_name, s.shop_image as seller_pic 
        FROM orders o 
        JOIN users u1 ON o.buyer_id = u1.user_id 
        JOIN shops s ON o.shop_id = s.shop_id 
        ORDER BY o.created_at DESC LIMIT 5
    ");
    
    while($row = mysqli_fetch_assoc($q_recent)) {
        $row['buyer_pic'] = !empty($row['buyer_pic']) ? $row['buyer_pic'] : "https://ui-avatars.com/api/?name=" . urlencode($row['buyer_name']);
        $row['seller_pic'] = !empty($row['seller_pic']) ? $row['seller_pic'] : "https://ui-avatars.com/api/?name=" . urlencode($row['shop_name']);
        
        $parts = explode(' | ', $row['shipping_method']);
        $ship_lbl_full = $parts[0]; 
        $shipping_cost = 0;
        if (preg_match('/\(Rp ([\d\.]+)\)/', $ship_lbl_full, $matches)) {
            $shipping_cost = (int)str_replace('.', '', $matches[1]);
        }
        
        $product_price = (int)$row['total_price'];
        $grand_total = $product_price + $shipping_cost + $fee_per_trx;
        $row['grand_total'] = $grand_total;
        $recent_orders[] = $row;
    }
    
    echo json_encode([
        'total_users' => $total_users,
        'pending_products' => $pending_products,
        'active_reports' => $active_reports,
        'admin_balance' => $admin_balance_ajax,
        'recent_orders' => $recent_orders
    ]);
    exit();
}

$admin_id = $_SESSION['user_id'];

// Ambil Data Admin Terbaru
$q_admin = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id = '$admin_id'");
$d_admin = mysqli_fetch_assoc($q_admin);
$admin_foto = !empty($d_admin['profile_picture']) ? $d_admin['profile_picture'] : "https://ui-avatars.com/api/?name=" . urlencode($d_admin['name']);

// --- AMBIL BIAYA ADMIN DARI DB (Untuk perhitungan akurat) ---
// Jika belum ada tabel system_settings, fallback ke 1000
$q_fee = mysqli_query($koneksi, "SELECT setting_value FROM system_settings WHERE setting_key = 'admin_fee'");
$d_fee = mysqli_fetch_assoc($q_fee);
$fee_per_trx = isset($d_fee['setting_value']) ? (int)$d_fee['setting_value'] : 1000;

// --- STATISTIK UTAMA ---
// 1. Total User (Buyer & Seller)
$total_users = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role != 'admin'"))['total'];

// 2. Produk Pending (termasuk review)
$pending_products = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM products WHERE status = 'pending' OR status = 'review'"))['total'];

// 3. Laporan Pending (Transaksi + Chat)
$transaction_reports = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'"))['total'];
$chat_reports = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM notifications WHERE user_id = '$admin_id' AND title = 'Laporan Chat' AND is_read = 0"))['total'];
$active_reports = $transaction_reports + $chat_reports;

// 4. Ambil Saldo Admin (Pendapatan) Langsung dari Database
// Saldo ini sudah otomatis bertambah saat ada transaksi selesai (di histori.php)
// dan berkurang saat admin melakukan penarikan
$q_admin_balance = mysqli_query($koneksi, "SELECT setting_value FROM system_settings WHERE setting_key = 'admin_balance'");
$d_admin_balance = mysqli_fetch_assoc($q_admin_balance);
$admin_balance = isset($d_admin_balance['setting_value']) ? (int)$d_admin_balance['setting_value'] : 0;

// Jika admin_balance belum ada di database, inisialisasi dengan 0
if (!isset($d_admin_balance['setting_value'])) {
    mysqli_query($koneksi, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('admin_balance', '0')");
    $admin_balance = 0;
}

// --- NOTIFIKASI BARU ---
$pending_count = $pending_products;
$report_count = $active_reports; // Sudah termasuk laporan transaksi + chat
$has_notif = ($pending_count > 0 || $report_count > 0);

// --- PESANAN TERBARU ---
$recent_orders = [];
$q_recent = mysqli_query($koneksi, "
    SELECT o.invoice_code, o.total_price, o.status, o.shipping_method,
           u1.name as buyer_name, u1.profile_picture as buyer_pic,
           s.shop_name, s.shop_image as seller_pic 
    FROM orders o 
    JOIN users u1 ON o.buyer_id = u1.user_id 
    JOIN shops s ON o.shop_id = s.shop_id 
    ORDER BY o.created_at DESC LIMIT 5
");

while($row = mysqli_fetch_assoc($q_recent)) {
    // Foto Profil
    $row['buyer_pic'] = !empty($row['buyer_pic']) ? $row['buyer_pic'] : "https://ui-avatars.com/api/?name=" . urlencode($row['buyer_name']);
    $row['seller_pic'] = !empty($row['seller_pic']) ? $row['seller_pic'] : "https://ui-avatars.com/api/?name=" . urlencode($row['shop_name']);

    // Hitung Grand Total (Produk + Ongkir + Admin Fee) untuk tampilan tabel
    $parts = explode(' | ', $row['shipping_method']);
    $ship_lbl_full = $parts[0]; 
    $shipping_cost = 0;
    if (preg_match('/\(Rp ([\d\.]+)\)/', $ship_lbl_full, $matches)) {
        $shipping_cost = (int)str_replace('.', '', $matches[1]);
    }

    $product_price = (int)$row['total_price'];
    // Gunakan fee yang berlaku saat ini (atau idealnya fee yang tersimpan di order history jika ada kolom admin_fee di tabel orders)
    // Untuk simplifikasi dashboard, kita pakai fee saat ini.
    $grand_total = $product_price + $shipping_cost + $fee_per_trx;

    $row['grand_total'] = $grand_total;
    $recent_orders[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoSwap Admin - Dashboard</title>
    <link rel="icon" type="image/png" href="../../../Assets/img/auth/logo.png">
    <link rel="stylesheet" href="../../../Assets/css/role/admin/dashboard.css">
    <link rel="stylesheet" href="../../../Assets/css/role/admin/notifikasi.css">
    <link rel="stylesheet" href="../../../Assets/css/role/admin/profil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS Khusus Tabel User & Alignment */
        .simple-table th { padding: 15px; font-size: 0.85rem; color: #888; font-weight: 600; text-align: left; border-bottom: 1px solid #eee; }
        .simple-table td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f9f9f9; }
        .simple-table td:last-child, .simple-table th:last-child { text-align: right; }
        
        .user-flex { display: flex; align-items: center; gap: 10px; }
        .user-avatar-sm { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid #eee; }
        .user-text { display: flex; flex-direction: column; line-height: 1.2; }
        .user-name { font-size: 0.9rem; font-weight: 600; color: #333; }
        .user-role { font-size: 0.75rem; color: #888; }

        .content-grid { display: block; } 
        .recent-orders { margin-top: 0; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">ECO<span>SWAP</span></div>
        </div>
        <ul class="nav-links">
            <li class="active"><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
            <li><a href="produk.php"><i class="fas fa-box"></i> <span>Produk</span> <?php if($pending_products > 0): ?><span class="badge-count"><?php echo $pending_products; ?></span><?php endif; ?></a></li>
            <li><a href="pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a></li>
            <li><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
            <li><a href="laporan.php"><i class="fas fa-headset"></i> <span>Laporan</span> <?php if($active_reports > 0): ?><span class="badge-count danger"><?php echo $active_reports; ?></span><?php endif; ?></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../../../../index.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h2>Dashboard Admin</h2>
                <p>Selamat datang, <strong><?php echo htmlspecialchars($d_admin['name']); ?></strong> 👋</p>
            </div>
            <div class="user-profile">
                <?php include 'notifikasi.php'; ?>
                
                <div class="profile-info" onclick="openProfileModal()" style="cursor: pointer;">
                    <img src="<?php echo $admin_foto; ?>" alt="Admin">
                </div>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card"><div class="stat-icon black-bg"><i class="fas fa-box-open"></i></div><div class="stat-details"><h3>Perlu Persetujuan</h3><p class="number"><?php echo number_format($pending_products); ?></p></div></div>
            <div class="stat-card"><div class="stat-icon yellow-bg"><i class="fas fa-users"></i></div><div class="stat-details"><h3>Total Pengguna</h3><p class="number"><?php echo number_format($total_users); ?></p></div></div>
            <div class="stat-card stat-card-clickable" onclick="openWithdrawModal()" style="cursor: pointer;" title="Klik untuk tarik saldo">
                <div class="stat-icon gray-bg"><i class="fas fa-wallet"></i></div>
                <div class="stat-details">
                    <h3>Pendapatan <i class="fas fa-hand-pointer" style="font-size: 0.7rem; opacity: 0.6;"></i></h3>
                    <p class="number">Rp <?php echo number_format($admin_balance, 0, ',', '.'); ?></p>
                    <small style="font-size: 0.75rem; color: #888; margin-top: 5px; display: block;">Klik untuk tarik saldo</small>
                </div>
            </div>
            <div class="stat-card"><div class="stat-icon red-bg"><i class="fas fa-exclamation-circle"></i></div><div class="stat-details"><h3>Laporan</h3><p class="number"><?php echo number_format($active_reports); ?></p></div></div>
        </section>

        <section class="content-grid">
            <div class="recent-orders card-panel" style="width: 100%;">
                <div class="card-header">
                    <h3>Transaksi Terbaru</h3>
                    <a href="transaksi.php" class="view-all">Lihat Semua</a>
                </div>
                <table class="simple-table">
                    <thead>
                        <tr>
                            <th width="20%">Invoice</th>
                            <th width="25%">Pembeli</th>
                            <th width="25%">Penjual</th>
                            <th width="15%">Status</th>
                            <th width="15%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $ro): ?>
                        <tr>
                            <td class="font-bold" style="color:#555;"><?php echo $ro['invoice_code']; ?></td>
                            <td>
                                <div class="user-flex">
                                    <img src="<?php echo $ro['buyer_pic']; ?>" class="user-avatar-sm">
                                    <div class="user-text">
                                        <span class="user-name"><?php echo $ro['buyer_name']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="user-flex">
                                    <img src="<?php echo $ro['seller_pic']; ?>" class="user-avatar-sm">
                                    <div class="user-text">
                                        <span class="user-name"><?php echo $ro['shop_name']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php 
                                    $st = $ro['status']; 
                                    $badge = ($st == 'completed' || $st == 'reviewed') ? 'badge-green' : (($st == 'pending') ? 'badge-yellow' : 'badge-blue'); 
                                ?>
                                <span class="status-badge <?php echo $badge; ?>"><?php echo ucfirst($st); ?></span>
                            </td>
                            <td class="text-right font-bold">Rp <?php echo number_format($ro['grand_total'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_orders)): ?>
                            <tr><td colspan="5" style="text-align:center; padding:20px; color:#999;">Belum ada transaksi terbaru.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php include 'profil.php'; ?>

    <!-- Modal Tarik Saldo Admin -->
    <div class="modal-overlay" id="withdrawModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">Tarik Saldo Admin</div>
                <span class="close-modal" onclick="closeWithdrawModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="withdraw">
                
                <div class="form-group">
                    <label class="form-label">Pilih Metode Tujuan</label>
                    <div class="method-toggle">
                        <button type="button" class="method-btn active" onclick="toggleMethod('ewallet', this)"><i class="fas fa-wallet"></i> E-Wallet</button>
                        <button type="button" class="method-btn" onclick="toggleMethod('bank', this)"><i class="fas fa-university"></i> Bank</button>
                    </div>
                </div>

                <div id="ewalletOptions" class="bank-options">
                    <label>
                        <input type="radio" name="destination_method" value="Dana" class="bank-radio" checked>
                        <div class="bank-card"><img src="https://placehold.co/100x50/118EEA/ffffff?text=DANA" class="payment-logo"><span>DANA</span></div>
                    </label>
                    <label>
                        <input type="radio" name="destination_method" value="OVO" class="bank-radio">
                        <div class="bank-card"><img src="https://placehold.co/100x50/4C2A86/ffffff?text=OVO" class="payment-logo"><span>OVO</span></div>
                    </label>
                    <label>
                        <input type="radio" name="destination_method" value="GoPay" class="bank-radio">
                        <div class="bank-card"><img src="https://placehold.co/100x50/00A5CF/ffffff?text=GoPay" class="payment-logo"><span>GoPay</span></div>
                    </label>
                </div>

                <div id="bankOptions" class="bank-options" style="display:none;">
                    <label>
                        <input type="radio" name="destination_method" value="BCA" class="bank-radio">
                        <div class="bank-card"><img src="https://placehold.co/100x50/003399/ffffff?text=BCA" class="payment-logo"><span>BCA</span></div>
                    </label>
                    <label>
                        <input type="radio" name="destination_method" value="Mandiri" class="bank-radio">
                        <div class="bank-card"><img src="https://placehold.co/100x50/FFB700/000000?text=Mandiri" class="payment-logo"><span>Mandiri</span></div>
                    </label>
                    <label>
                        <input type="radio" name="destination_method" value="BRI" class="bank-radio">
                        <div class="bank-card"><img src="https://placehold.co/100x50/00529C/ffffff?text=BRI" class="payment-logo"><span>BRI</span></div>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor Rekening / HP</label>
                    <input type="text" name="account_number" class="form-input" placeholder="Contoh: 08123456789" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nominal Penarikan</label>
                    <div class="input-wrapper">
                        <span class="input-prefix">Rp</span>
                        <input type="number" name="amount" class="form-input has-prefix" placeholder="0" min="10000" max="<?php echo $admin_balance; ?>" required>
                    </div>
                    <div style="font-size:0.8rem; color:#666; margin-top:5px;">Saldo tersedia: Rp <?php echo number_format($admin_balance, 0, ',', '.'); ?> | Min. Penarikan Rp 10.000</div>
                </div>

                <div class="withdraw-note"><i class="fas fa-info-circle"></i> Penarikan akan diproses maksimal 1x24 jam kerja.</div>
                
                <button type="submit" class="btn-submit">Konfirmasi Penarikan</button>
            </form>
        </div>
    </div>

    <style>
        /* Modal Withdraw Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 200; display: none; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal-container { background: #fff; padding: 25px; border-radius: 12px; width: 100%; max-width: 480px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: fadeIn 0.3s ease-out; max-height: 85vh; overflow-y: auto; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 1.3rem; font-weight: bold; }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: #888; transition: 0.2s; }
        .close-modal:hover { color: #dc3545; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #444; }
        .method-toggle { display: flex; background-color: #f0f0f0; padding: 5px; border-radius: 8px; gap: 5px; }
        .method-btn { flex: 1; padding: 10px; border: none; background: transparent; font-weight: 600; color: #666; border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .method-btn.active { background-color: #fff; color: #1e1e1e; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .method-btn:hover:not(.active) { background-color: #e0e0e0; }
        .input-wrapper { position: relative; }
        .input-prefix { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #888; font-weight: bold; }
        .form-input { width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 1rem; outline: none; transition: border 0.2s; }
        .form-input.has-prefix { padding-left: 45px; }
        .form-input:focus { border-color: #ffd700; }
        .bank-options { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .bank-radio { display: none; }
        .bank-card { border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px; text-align: center; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; height: 80px; background-color: #fff; }
        .payment-logo { height: 30px; width: auto; object-fit: contain; margin-bottom: 2px; }
        .bank-card span { font-size: 0.9rem; font-weight: 500; }
        .bank-card:hover { border-color: #ffd700; background-color: #fffde7; }
        .bank-radio:checked + .bank-card { border-color: #ffd700; background-color: #ffd700; color: #1e1e1e; font-weight: bold; }
        .bank-card .payment-logo { filter: grayscale(100%); opacity: 0.7; transition: all 0.2s; }
        .bank-card:hover .payment-logo, .bank-radio:checked + .bank-card .payment-logo { filter: grayscale(0%); opacity: 1; }
        .withdraw-note { font-size: 0.85rem; color: #666; background: #f9f9f9; padding: 10px; border-radius: 6px; margin-bottom: 20px; }
        .btn-submit { width: 100%; padding: 15px; background: #1e1e1e; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; color: #ffd700; font-size: 1rem; transition: opacity 0.2s; }
        .btn-submit:hover { opacity: 0.9; }
        
        /* Stat Card Clickable Hover Effect */
        .stat-card-clickable { transition: all 0.3s ease; }
        .stat-card-clickable:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
    </style>

    <script>
        // Real-Time Update Function
        function updateDashboard() {
            // Console log untuk debugging
            console.log('[Dashboard] Updating stats...', new Date().toLocaleTimeString());
            
            fetch('dashboard.php?ajax=get_stats')
                .then(response => response.json())
                .then(data => {
                    console.log('[Dashboard] Data received:', data);
                    
                    // Update statistik dengan animasi smooth
                    const stat1 = document.querySelector('.stat-card:nth-child(1) .number');
                    const stat2 = document.querySelector('.stat-card:nth-child(2) .number');
                    const stat3 = document.querySelector('.stat-card:nth-child(3) .number');
                    const stat4 = document.querySelector('.stat-card:nth-child(4) .number');
                    
                    // Update dengan check perubahan
                    if (stat1.textContent !== data.pending_products.toLocaleString('id-ID')) {
                        stat1.textContent = data.pending_products.toLocaleString('id-ID');
                        stat1.style.animation = 'pulse 0.5s';
                        setTimeout(() => stat1.style.animation = '', 500);
                    }
                    
                    if (stat2.textContent !== data.total_users.toLocaleString('id-ID')) {
                        stat2.textContent = data.total_users.toLocaleString('id-ID');
                        stat2.style.animation = 'pulse 0.5s';
                        setTimeout(() => stat2.style.animation = '', 500);
                    }
                    
                    const newBalance = 'Rp ' + data.admin_balance.toLocaleString('id-ID');
                    if (stat3.textContent !== newBalance) {
                        stat3.textContent = newBalance;
                        stat3.style.animation = 'pulse 0.5s';
                        setTimeout(() => stat3.style.animation = '', 500);
                    }
                    
                    if (stat4.textContent !== data.active_reports.toLocaleString('id-ID')) {
                        stat4.textContent = data.active_reports.toLocaleString('id-ID');
                        stat4.style.animation = 'pulse 0.5s';
                        setTimeout(() => stat4.style.animation = '', 500);
                    }
                    
                    // Update badge di sidebar
                    const produkBadge = document.querySelector('.nav-links li:nth-child(2) .badge-count');
                    const laporanBadge = document.querySelector('.nav-links li:nth-child(5) .badge-count');
                    
                    if (data.pending_products > 0) {
                        if (produkBadge) {
                            produkBadge.textContent = data.pending_products;
                        } else {
                            document.querySelector('.nav-links li:nth-child(2) a').insertAdjacentHTML('beforeend', '<span class="badge-count">' + data.pending_products + '</span>');
                        }
                    } else if (produkBadge) {
                        produkBadge.remove();
                    }
                    
                    if (data.active_reports > 0) {
                        if (laporanBadge) {
                            laporanBadge.textContent = data.active_reports;
                        } else {
                            document.querySelector('.nav-links li:nth-child(5) a').insertAdjacentHTML('beforeend', '<span class="badge-count danger">' + data.active_reports + '</span>');
                        }
                    } else if (laporanBadge) {
                        laporanBadge.remove();
                    }
                    
                    // Update tabel transaksi terbaru
                    const tbody = document.querySelector('.simple-table tbody');
                    if (data.recent_orders.length > 0) {
                        let html = '';
                        data.recent_orders.forEach(order => {
                            let badgeClass = 'badge-blue';
                            if (order.status == 'completed' || order.status == 'reviewed') {
                                badgeClass = 'badge-green';
                            } else if (order.status == 'pending') {
                                badgeClass = 'badge-yellow';
                            }
                            
                            html += `
                                <tr>
                                    <td class="font-bold" style="color:#555;">${order.invoice_code}</td>
                                    <td>
                                        <div class="user-flex">
                                            <img src="${order.buyer_pic}" class="user-avatar-sm">
                                            <div class="user-text">
                                                <span class="user-name">${order.buyer_name}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-flex">
                                            <img src="${order.seller_pic}" class="user-avatar-sm">
                                            <div class="user-text">
                                                <span class="user-name">${order.shop_name}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge ${badgeClass}">${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span>
                                    </td>
                                    <td class="text-right font-bold">Rp ${order.grand_total.toLocaleString('id-ID')}</td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#999;">Belum ada transaksi terbaru.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('[Dashboard] Error updating:', error);
                });
        }
        
        // Update setiap 5 detik
        const updateInterval = setInterval(updateDashboard, 5000);
        console.log('[Dashboard] Real-time update started. Interval: 5 seconds');
        
        // Update pertama kali setelah 2 detik
        setTimeout(() => {
            console.log('[Dashboard] Initial update...');
            updateDashboard();
        }, 2000);
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            clearInterval(updateInterval);
            console.log('[Dashboard] Real-time update stopped');
        });
        
        // Animasi pulse untuk update
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
        `;
        document.head.appendChild(style);
        
        // ==========================================
        // MODAL WITHDRAW FUNCTIONS
        // ==========================================
        const modal = document.getElementById('withdrawModal');
        
        function openWithdrawModal() { 
            modal.classList.add('open'); 
        }
        
        function closeWithdrawModal() { 
            modal.classList.remove('open'); 
        }
        
        // Close modal when clicking outside
        window.onclick = function(e) { 
            if (e.target == modal) closeWithdrawModal(); 
        }

        // Toggle Metode Penarikan (Bank vs E-Wallet)
        function toggleMethod(type, btnElement) {
            document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('active')); 
            btnElement.classList.add('active');
            
            const ewalletDiv = document.getElementById('ewalletOptions'); 
            const bankDiv = document.getElementById('bankOptions');
            
            // Reset radio buttons saat switch
            const radios = document.querySelectorAll('input[name="destination_method"]');
            radios.forEach(r => r.checked = false);

            if (type === 'bank') { 
                ewalletDiv.style.display = 'none'; 
                bankDiv.style.display = 'grid'; 
                bankDiv.querySelector('input').checked = true; // Auto select first option
            } else { 
                bankDiv.style.display = 'none'; 
                ewalletDiv.style.display = 'grid'; 
                ewalletDiv.querySelector('input').checked = true; 
            }
        }
    </script>

</body>
</html>
