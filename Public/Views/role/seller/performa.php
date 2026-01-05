<?php
session_start();
include '../../../Auth/koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) { header("Location: ../../auth/login.php"); exit(); }
$user_id = $_SESSION['user_id'];

// Cek Toko
$q_shop = mysqli_query($koneksi, "SELECT shop_id FROM shops WHERE user_id = '$user_id'");
if(mysqli_num_rows($q_shop) == 0){ header("Location: dashboard.php"); exit(); }
$shop = mysqli_fetch_assoc($q_shop);
$shop_id = $shop['shop_id'];

// --- 1. HITUNG STATISTIK ---
// Menghitung 'completed' ATAU 'reviewed' (karena reviewed artinya juga sudah selesai)
$q_sold = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM orders WHERE shop_id='$shop_id' AND (status='completed' OR status='reviewed')");
$d_sold = mysqli_fetch_assoc($q_sold);
$total_sold = $d_sold['total'];

$q_rating = mysqli_query($koneksi, "SELECT AVG(r.rating) as avg_rating FROM reviews r JOIN products p ON r.product_id = p.product_id WHERE p.shop_id = '$shop_id'");
$d_rating = mysqli_fetch_assoc($q_rating);
$rating_toko = number_format((float)$d_rating['avg_rating'], 1);

// --- 2. AMBIL RIWAYAT PENJUALAN (Semua yang selesai diterima pembeli) ---
$sold_items = [];
$q_history = mysqli_query($koneksi, "
    SELECT o.order_id, p.name as product_name, o.total_price, 
           u.name as buyer_name, o.shipping_method, o.status,
           (SELECT rating FROM reviews r WHERE r.order_id = o.order_id LIMIT 1) as review_rating
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN users u ON o.buyer_id = u.user_id
    WHERE o.shop_id = '$shop_id' 
    AND (o.status = 'completed' OR o.status = 'reviewed')
    ORDER BY o.created_at DESC
");

while($row = mysqli_fetch_assoc($q_history)) {
    // Parse Info Pengiriman & Pembayaran
    $parts = explode(' | ', $row['shipping_method']);
    $row['ship_only'] = isset($parts[0]) ? $parts[0] : $row['shipping_method'];
    $row['pay_only'] = isset($parts[1]) ? $parts[1] : '-';
    $sold_items[] = $row;
}

// --- 3. AMBIL ULASAN PEMBELI ---
$reviews = [];
$q_rev = mysqli_query($koneksi, "
    SELECT r.*, p.name as product_name, u.name as buyer_name 
    FROM reviews r 
    JOIN products p ON r.product_id = p.product_id 
    JOIN users u ON r.user_id = u.user_id 
    WHERE p.shop_id = '$shop_id' 
    ORDER BY r.created_at DESC
");

while($row = mysqli_fetch_assoc($q_rev)) {
    $reviews[] = [
        'name' => $row['buyer_name'],
        'date' => date('d M Y', strtotime($row['created_at'])),
        'product' => $row['product_name'],
        'rating' => (int)$row['rating'],
        'text' => $row['comment'],
        'review_photo' => $row['photo'],
        'img' => $row['buyer_name']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performa Toko - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/performa.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .review-img-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-top: 10px; border: 1px solid #eee; cursor: pointer; transition: transform 0.2s; }
        .review-img-thumb:hover { transform: scale(1.05); }
        
        /* Filter Button Active State */
        .filter-pill {
            padding: 8px 16px; border: 1px solid #ddd; background: #fff; border-radius: 20px; cursor: pointer; font-size: 0.9rem; transition: 0.2s;
        }
        .filter-pill:hover { background: #f0f0f0; }
        .filter-pill.active { background: var(--primary); border-color: var(--primary); color: #000; font-weight: 600; }
        
        /* Badge Status */
        .badge-status-completed { background: #d1e7dd; color: #0f5132; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; }
        .badge-status-reviewed { background: #fff3cd; color: #856404; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; }
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
            <div class="header"><div class="page-title">Performa Toko</div></div>
            <div class="content">
                <div class="performa-container">
                    <div class="stats-grid">
                        <div class="stat-card active" id="cardSold" onclick="showSection('sold')">
                            <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                            <div class="stat-label">Barang Terjual</div>
                            <div class="stat-value"><?php echo $total_sold; ?></div>
                        </div>
                        <div class="stat-card" id="cardRating" onclick="showSection('rating')">
                            <div class="stat-icon"><i class="fas fa-star"></i></div>
                            <div class="stat-label">Rating Toko</div>
                            <div class="stat-value"><?php echo $rating_toko; ?></div>
                        </div>
                    </div>

                    <div id="sectionSold" class="data-section active">
                        <div class="section-header"><div><i class="fas fa-list-ul"></i> Riwayat Penjualan (Selesai)</div></div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nama Barang</th>
                                        <th>Harga</th>
                                        <th>Pembeli</th>
                                        <th>Info Pengiriman</th>
                                        <th>Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($sold_items)): ?> 
                                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#888;">Belum ada penjualan selesai.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($sold_items as $item): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $item['product_name']; ?></strong><br>
                                                <small style="color:#888;">#ORD-<?php echo $item['order_id']; ?></small>
                                                <br>
                                                <?php if($item['status'] == 'reviewed'): ?>
                                                    <span class="badge-status-reviewed">Sudah Direview</span>
                                                <?php else: ?>
                                                    <span class="badge-status-completed">Selesai</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>Rp <?php echo number_format($item['total_price'], 0, ',', '.'); ?></td>
                                            <td><?php echo $item['buyer_name']; ?></td>
                                            <td>
                                                <span class="badge-shipping"><?php echo $item['ship_only']; ?></span><br>
                                                <small style="color:var(--primary); font-weight:bold;"><?php echo $item['pay_only']; ?></small>
                                            </td>
                                            <td>
                                                <?php if($item['review_rating']): ?>
                                                    <div class="star-rating"><i class="fas fa-star" style="color:#ffc107;"></i> <?php echo number_format($item['review_rating'], 1); ?></div>
                                                <?php else: ?>
                                                    <span style="color:#ccc;">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="sectionRating" class="data-section">
                        <div class="section-header"><div><i class="fas fa-comments"></i> Ulasan Pembeli</div></div>
                        
                        <div class="rating-filters" style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
                            <button class="filter-pill active" onclick="filterReviews('all', this)">Semua</button>
                            <button class="filter-pill" onclick="filterReviews('5', this)">5 Bintang</button>
                            <button class="filter-pill" onclick="filterReviews('4', this)">4 Bintang</button>
                            <button class="filter-pill" onclick="filterReviews('3', this)">3 Bintang</button>
                            <button class="filter-pill" onclick="filterReviews('2', this)">2 Bintang</button>
                            <button class="filter-pill" onclick="filterReviews('1', this)">1 Bintang</button>
                        </div>

                        <div class="review-list" id="reviewListContainer"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function goToDashboard() { window.location.href = '../buyer/dashboard.php'; }
        
        // Data Reviews dari PHP
        const reviews = <?php echo json_encode($reviews); ?>;

        function renderReviews(filterType) {
            const container = document.getElementById('reviewListContainer');
            container.innerHTML = '';

            // Filter Logic
            const filteredReviews = reviews.filter(r => {
                if (filterType === 'all') return true;
                return r.rating == filterType;
            });

            if (filteredReviews.length === 0) {
                container.innerHTML = `<div style="text-align:center; padding:30px; color:#888;">Tidak ada ulasan untuk filter ini.</div>`;
                return;
            }

            filteredReviews.forEach(r => {
                let stars = '';
                for(let i=1; i<=5; i++) {
                    stars += (i <= r.rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                }
                
                let photoHtml = '';
                if(r.review_photo && r.review_photo !== "") {
                    photoHtml = `<div onclick="window.open('${r.review_photo}','_blank')"><img src="${r.review_photo}" class="review-img-thumb" alt="Foto Ulasan"></div>`;
                }

                container.innerHTML += `
                    <div class="review-item" style="border-bottom:1px solid #eee; padding:15px 0; display:flex; gap:15px;">
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=${r.img}" class="buyer-avatar" style="width:50px; height:50px; border-radius:50%; background:#eee;">
                        <div class="review-content" style="flex:1;">
                            <div class="review-header" style="display:flex; justify-content:space-between; margin-bottom:5px;">
                                <span class="buyer-name" style="font-weight:bold;">${r.name}</span>
                                <span class="review-date" style="font-size:0.85rem; color:#999;">${r.date}</span>
                            </div>
                            <span class="review-product" style="font-size:0.85rem; color:#666; background:#f5f5f5; padding:2px 8px; border-radius:4px;"><i class="fas fa-box"></i> ${r.product}</span>
                            <div class="review-stars" style="color:#ffc107; margin:5px 0;">${stars}</div>
                            <p class="review-text" style="color:#333; line-height:1.5;">${r.text}</p>
                            ${photoHtml}
                        </div>
                    </div>`;
            });
        }

        function filterReviews(type, btn) {
            // Update tombol active
            document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            renderReviews(type);
        }

        function showSection(type) {
            // Tab Toggle (Penjualan vs Rating)
            document.querySelectorAll('.stat-card').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.data-section').forEach(s => s.classList.remove('active'));
            
            if(type === 'sold'){
                document.getElementById('cardSold').classList.add('active');
                document.getElementById('sectionSold').classList.add('active');
            } else {
                document.getElementById('cardRating').classList.add('active');
                document.getElementById('sectionRating').classList.add('active');
                renderReviews('all'); // Render default 'all' saat tab dibuka
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Default load
            renderReviews('all');
        });
    </script>
</body>
</html>