<?php
session_start();
// Koneksi Database
include '../../../Auth/koneksi.php';

// Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../auth/login.php");
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

// 2. Produk Pending
$pending_products = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM products WHERE status = 'pending'"))['total'];

// 3. Laporan Pending
$active_reports = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'"))['total'];

// 4. Hitung Pendapatan Murni Admin (Hanya Fee Aplikasi)
// Hitung jumlah transaksi 'completed' lalu kalikan dengan fee per transaksi
$q_sales = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM orders WHERE status = 'completed'");
$d_sales = mysqli_fetch_assoc($q_sales);
$total_sales_count = $d_sales['total'];

$admin_revenue = $total_sales_count * $fee_per_trx;

// --- NOTIFIKASI BARU ---
$pending_count = $pending_products;
$report_count = $active_reports;
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
    <title>Admin Dashboard - EcoSwap</title>
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
            <div class="logo"><i class="fas fa-recycle"></i> ECO<span>SWAP</span></div>
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
            <div class="stat-card"><div class="stat-icon gray-bg"><i class="fas fa-wallet"></i></div><div class="stat-details"><h3>Pendapatan</h3><p class="number">Rp <?php echo number_format($admin_revenue, 0, ',', '.'); ?></p></div></div>
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
                            <th width="25%">Penjual (Toko)</th>
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

</body>
</html>