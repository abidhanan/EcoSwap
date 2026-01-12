<?php
session_start();
// Koneksi mundur 3 folder (admin -> role -> Views -> Public -> Auth)
include '../../../Auth/koneksi.php';

// Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../auth/login.php");
    exit();
}

// Data Admin
$admin_id = $_SESSION['user_id'];
$q_admin = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id = '$admin_id'");
$d_admin = mysqli_fetch_assoc($q_admin);

// --- STATISTIK UTAMA ---

// 1. Total Pengguna (Buyer & Seller)
$total_users = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role != 'admin'"))['total'];

// 2. Produk Menunggu Verifikasi (Penting untuk Admin)
$pending_products = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM products WHERE status = 'pending'"))['total'];

// 3. Laporan / Sengketa Aktif
$active_reports = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'"))['total'];

// 4. Total Transaksi Sukses
$total_sales_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM orders WHERE status = 'completed'"))['total'];

// 5. Estimasi Pendapatan Admin (Misal Rp 1.000 per transaksi sukses)
$admin_revenue = $total_sales_count * 1000;

// --- DATA GRAFIK (6 Bulan Terakhir) ---
$chart_labels = [];
$chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i months"));
    $month_end   = date('Y-m-t', strtotime("-$i months"));
    $month_name  = date('M', strtotime("-$i months"));
    
    // Hitung jumlah order completed bulan tersebut
    $q_chart = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM orders WHERE status='completed' AND created_at BETWEEN '$month_start 00:00:00' AND '$month_end 23:59:59'");
    $d_chart = mysqli_fetch_assoc($q_chart);
    
    $chart_labels[] = $month_name;
    $chart_data[] = $d_chart['total'];
}

// --- TABEL: PESANAN TERBARU ---
$recent_orders = [];
$q_recent = mysqli_query($koneksi, "
    SELECT o.invoice_code, o.total_price, o.status, u.name as buyer_name, s.shop_name
    FROM orders o
    JOIN users u ON o.buyer_id = u.user_id
    JOIN shops s ON o.shop_id = s.shop_id
    ORDER BY o.created_at DESC LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EcoSwap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/admin/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-recycle"></i> ECO<span>SWAP</span>
            </div>
        </div>
        <ul class="nav-links">
            <li class="active">
                <a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
            </li>
            <li>
                <a href="../produk&stok/produk&stok.php">
                    <i class="fas fa-box"></i> <span>Verifikasi Produk</span>
                    <?php if($pending_products > 0): ?><span class="badge-count"><?php echo $pending_products; ?></span><?php endif; ?>
                </a>
            </li>
            <li>
                <a href="../pengguna/pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a>
            </li>
            <li>
                <a href="../transaksi/transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a>
            </li>
            <li>
                <a href="../support/support.php">
                    <i class="fas fa-headset"></i> <span>Laporan</span>
                    <?php if($active_reports > 0): ?><span class="badge-count danger"><?php echo $active_reports; ?></span><?php endif; ?>
                </a>
            </li>
            <li>
                <a href="../pengaturan/pengaturan.php"><i class="fas fa-cog"></i> <span>Pengaturan</span></a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <a href="../../../../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h2>Dashboard Overview</h2>
                <p>Selamat datang kembali, <strong><?php echo htmlspecialchars($d_admin['name']); ?></strong> 👋</p>
            </div>
            <div class="user-profile">
                <div class="notif-icon">
                    <i class="far fa-bell"></i>
                    <span class="dot"></span>
                </div>
                <div class="profile-info">
                    <img src="<?php echo !empty($d_admin['profile_picture']) ? $d_admin['profile_picture'] : 'https://ui-avatars.com/api/?name=Admin'; ?>" alt="Admin">
                </div>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon yellow-bg"><i class="fas fa-users"></i></div>
                <div class="stat-details">
                    <h3>Total Pengguna</h3>
                    <p class="number"><?php echo number_format($total_users); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon black-bg"><i class="fas fa-box-open"></i></div>
                <div class="stat-details">
                    <h3>Produk Pending</h3>
                    <p class="number"><?php echo number_format($pending_products); ?></p>
                    <span class="sub-text">Perlu Verifikasi</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gray-bg"><i class="fas fa-wallet"></i></div>
                <div class="stat-details">
                    <h3>Pendapatan Admin</h3>
                    <p class="number">Rp <?php echo number_format($admin_revenue, 0, ',', '.'); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red-bg"><i class="fas fa-exclamation-circle"></i></div>
                <div class="stat-details">
                    <h3>Laporan Aktif</h3>
                    <p class="number"><?php echo number_format($active_reports); ?></p>
                    <span class="sub-text">Perlu Tindakan</span>
                </div>
            </div>
        </section>

        <section class="content-grid">
            <div class="chart-container card-panel">
                <div class="card-header">
                    <h3>Statistik Transaksi (6 Bulan)</h3>
                </div>
                <div class="chart-wrapper">
                    <canvas id="transactionChart"></canvas>
                </div>
            </div>

            <div class="recent-orders card-panel">
                <div class="card-header">
                    <h3>Transaksi Terbaru</h3>
                    <a href="../transaksi/transaksi.php" class="view-all">Lihat Semua</a>
                </div>
                <table class="simple-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Pembeli</th>
                            <th>Status</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $ro): ?>
                        <tr>
                            <td class="font-bold"><?php echo $ro['invoice_code']; ?></td>
                            <td>
                                <div class="user-cell">
                                    <span><?php echo $ro['buyer_name']; ?></span>
                                    <small><?php echo $ro['shop_name']; ?></small>
                                </div>
                            </td>
                            <td>
                                <?php 
                                    $st = $ro['status'];
                                    $badge = 'badge-gray';
                                    if($st == 'completed' || $st == 'reviewed') $badge = 'badge-green';
                                    if($st == 'pending') $badge = 'badge-yellow';
                                    if($st == 'shipping' || $st == 'delivered') $badge = 'badge-blue';
                                ?>
                                <span class="status-badge <?php echo $badge; ?>"><?php echo ucfirst($st); ?></span>
                            </td>
                            <td class="text-right font-bold">Rp <?php echo number_format($ro['total_price'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_orders)): ?>
                            <tr><td colspan="4" class="text-center">Belum ada transaksi.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        const ctx = document.getElementById('transactionChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(255, 215, 0, 0.5)'); // Gold Transparan
        gradient.addColorStop(1, 'rgba(255, 215, 0, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: '#FFD700',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointBackgroundColor: '#000',
                    pointBorderColor: '#FFD700',
                    pointRadius: 4,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4], color: '#eee' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>