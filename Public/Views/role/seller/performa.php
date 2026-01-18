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

// --- 0. AMBIL BIAYA ADMIN (Penting untuk hitung total) ---
$q_fee = mysqli_query($koneksi, "SELECT setting_value FROM system_settings WHERE setting_key = 'admin_fee'");
$d_fee = mysqli_fetch_assoc($q_fee);
$system_admin_fee = isset($d_fee['setting_value']) ? (int)$d_fee['setting_value'] : 1000;

// --- 1. HITUNG STATISTIK ---
$q_sold = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM orders WHERE shop_id='$shop_id' AND (status='completed' OR status='reviewed')");
$d_sold = mysqli_fetch_assoc($q_sold);
$total_sold = $d_sold['total'];

$q_rating = mysqli_query($koneksi, "SELECT AVG(r.rating) as avg_rating FROM reviews r JOIN products p ON r.product_id = p.product_id WHERE p.shop_id = '$shop_id'");
$d_rating = mysqli_fetch_assoc($q_rating);
$rating_toko = number_format((float)$d_rating['avg_rating'], 1);

// --- 2. AMBIL RIWAYAT PENJUALAN ---
// Query diperbarui untuk mengambil foto produk (p.image) dan foto user (u.profile_picture)
$sold_items = [];
$q_history = mysqli_query($koneksi, "
    SELECT o.order_id, p.name as product_name, p.image as product_image, o.total_price, 
           u.name as buyer_name, u.profile_picture as buyer_avatar, 
           o.shipping_method, o.status,
           (SELECT rating FROM reviews r WHERE r.order_id = o.order_id LIMIT 1) as review_rating
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN users u ON o.buyer_id = u.user_id
    WHERE o.shop_id = '$shop_id' 
    AND (o.status = 'completed' OR o.status = 'reviewed')
    ORDER BY o.created_at DESC
");

while($row = mysqli_fetch_assoc($q_history)) {
    // 1. Parse Info Pengiriman & Pembayaran
    $parts = explode(' | ', $row['shipping_method']);
    $raw_ship = isset($parts[0]) ? $parts[0] : $row['shipping_method'];
    
    // Bersihkan Nama Kurir (Hapus harga di dalam kurung)
    $row['ship_clean'] = preg_replace('/\s*\(Rp.*?\)/', '', $raw_ship);
    $row['pay_method'] = isset($parts[1]) ? $parts[1] : '-';

    // 2. Kalkulasi Total Keseluruhan
    // Ambil ongkir dari string "JNE (Rp 15.000)"
    $shipping_cost = 0;
    if (preg_match('/\(Rp ([\d\.]+)\)/', $raw_ship, $matches)) {
        $shipping_cost = (int)str_replace('.', '', $matches[1]);
    }
    
    // Rumus: Harga Produk + Ongkir + Admin
    $row['grand_total'] = $row['total_price'] + $shipping_cost + $system_admin_fee;

    // 3. Avatar Handler
    if (empty($row['buyer_avatar'])) {
        $row['buyer_avatar'] = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($row['buyer_name']);
    }

    $sold_items[] = $row;
}

// --- 3. AMBIL ULASAN PEMBELI ---
$reviews = [];
$q_rev = mysqli_query($koneksi, "
    SELECT r.*, p.name as product_name, u.name as buyer_name, u.profile_picture 
    FROM reviews r 
    JOIN products p ON r.product_id = p.product_id 
    JOIN users u ON r.user_id = u.user_id 
    WHERE p.shop_id = '$shop_id' 
    ORDER BY r.created_at DESC
");

while($row = mysqli_fetch_assoc($q_rev)) {
    $avatar = !empty($row['profile_picture']) ? $row['profile_picture'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($row['buyer_name']);
    
    $reviews[] = [
        'name' => $row['buyer_name'],
        'date' => date('d M Y', strtotime($row['created_at'])),
        'product' => $row['product_name'],
        'rating' => (int)$row['rating'],
        'text' => $row['comment'],
        'review_photo' => $row['photo'],
        'avatar_url' => $avatar,
        'rating_filter' => (string)$row['rating']
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
        /* CSS Tambahan untuk Tabel yang Lebih Rapi */
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .data-table th { 
            background-color: #f8f9fa; color: #555; font-weight: 600; padding: 15px 10px; 
            border-bottom: 2px solid #eee; text-align: left; font-size: 0.85rem; white-space: nowrap;
        }
        .data-table td { 
            padding: 15px 10px; vertical-align: middle; border-bottom: 1px solid #f0f0f0; 
            color: #333; font-size: 0.9rem; 
        }
        .data-table tr:hover { background-color: #fafafa; }

        /* Komponen Tabel */
        .product-cell { display: flex; align-items: center; gap: 12px; }
        .table-prod-img { width: 45px; height: 45px; border-radius: 6px; object-fit: cover; border: 1px solid #eee; }
        .table-prod-name { font-weight: 600; font-size: 0.9rem; color: #333; line-height: 1.2; }
        .table-ord-id { font-size: 0.75rem; color: #888; margin-top: 2px; }

        .buyer-cell { display: flex; align-items: center; gap: 8px; }
        .table-buyer-img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd; }
        
        .badge-pill { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .bg-completed { background: #d1e7dd; color: #0f5132; }
        .bg-reviewed { background: #fff3cd; color: #856404; }
        .bg-ship { background: #eef2f7; color: #333; }

        .price-bold { font-weight: 700; color: var(--primary); }
        .star-gold { color: #ffc107; font-size: 0.85rem; }

        /* Filter & Reviews */
        .review-img-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-top: 10px; border: 1px solid #eee; cursor: pointer; transition: transform 0.2s; }
        .review-img-thumb:hover { transform: scale(1.05); }
        .filter-pill { padding: 8px 16px; border: 1px solid #ddd; background: #fff; border-radius: 20px; cursor: pointer; font-size: 0.9rem; transition: 0.2s; color: #555; }
        .filter-pill:hover { background: #f0f0f0; }
        .filter-pill.active { background: var(--primary); border-color: var(--primary); color: #000; font-weight: 600; }
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
                                        <th width="25%">Produk</th>
                                        <th width="10%">Harga Produk</th>
                                        <th width="15%">Pembeli</th>
                                        <th width="10%">Status</th>
                                        <th width="10%">Pembayaran</th>
                                        <th width="10%">Pengiriman</th>
                                        <th width="12%">Total Order</th>
                                        <th width="8%">Rating</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($sold_items)): ?> 
                                        <tr><td colspan="8" style="text-align:center; padding:40px; color:#888;">Belum ada penjualan selesai.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($sold_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="product-cell">
                                                    <img src="<?php echo $item['product_image']; ?>" class="table-prod-img" alt="img">
                                                    <div>
                                                        <div class="table-prod-name"><?php echo $item['product_name']; ?></div>
                                                        <div class="table-ord-id">#ORD-<?php echo $item['order_id']; ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td>Rp <?php echo number_format($item['total_price'], 0, ',', '.'); ?></td>
                                            
                                            <td>
                                                <div class="buyer-cell">
                                                    <img src="<?php echo $item['buyer_avatar']; ?>" class="table-buyer-img">
                                                    <span><?php echo $item['buyer_name']; ?></span>
                                                </div>
                                            </td>

                                            <td>
                                                <?php if($item['status'] == 'reviewed'): ?>
                                                    <span class="badge-pill bg-reviewed">Direview</span>
                                                <?php else: ?>
                                                    <span class="badge-pill bg-completed">Selesai</span>
                                                <?php endif; ?>
                                            </td>

                                            <td><span style="font-weight:500; color:#555;"><?php echo $item['pay_method']; ?></span></td>

                                            <td>
                                                <span class="badge-pill bg-ship">
                                                    <i class="fas fa-truck" style="font-size:0.6rem; margin-right:3px;"></i> 
                                                    <?php echo $item['ship_clean']; ?>
                                                </span>
                                            </td>

                                            <td>
                                                <span class="price-bold">Rp <?php echo number_format($item['grand_total'], 0, ',', '.'); ?></span>
                                            </td>

                                            <td>
                                                <?php if($item['review_rating']): ?>
                                                    <div class="star-rating"><i class="fas fa-star star-gold"></i> <?php echo number_format($item['review_rating'], 1); ?></div>
                                                <?php else: ?>
                                                    <span style="color:#ccc; font-size:0.8rem;">-</span>
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
        
        const reviews = <?php echo json_encode($reviews); ?>;

        function renderReviews(filterType) {
            const container = document.getElementById('reviewListContainer');
            container.innerHTML = '';

            const filteredReviews = reviews.filter(r => {
                if (filterType === 'all') return true;
                return r.rating_filter == filterType;
            });

            if (filteredReviews.length === 0) {
                container.innerHTML = `<div style="text-align:center; padding:40px; color:#888; border:1px dashed #ddd; border-radius:8px;">Tidak ada ulasan untuk filter ini.</div>`;
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
                    <div class="review-item" style="border-bottom:1px solid #f0f0f0; padding:20px 0; display:flex; gap:15px;">
                        <img src="${r.avatar_url}" class="buyer-avatar" style="width:50px; height:50px; border-radius:50%; background:#eee; object-fit:cover; border:1px solid #ddd;">
                        <div class="review-content" style="flex:1;">
                            <div class="review-header" style="display:flex; justify-content:space-between; margin-bottom:5px;">
                                <span class="buyer-name" style="font-weight:700; font-size:0.95rem; color:#333;">${r.name}</span>
                                <span class="review-date" style="font-size:0.8rem; color:#999;">${r.date}</span>
                            </div>
                            <span class="review-product" style="font-size:0.8rem; color:#555; background:#f5f5f5; padding:3px 8px; border-radius:4px; display:inline-block; margin-bottom:5px;">
                                <i class="fas fa-box" style="margin-right:4px;"></i> ${r.product}
                            </span>
                            <div class="review-stars" style="color:#ffc107; margin:5px 0; font-size:0.9rem;">${stars}</div>
                            <p class="review-text" style="color:#444; line-height:1.5; margin:5px 0 0 0; font-size:0.95rem;">${r.text}</p>
                            ${photoHtml}
                        </div>
                    </div>`;
            });
        }

        function filterReviews(type, btn) {
            document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderReviews(type);
        }

        function showSection(type) {
            document.querySelectorAll('.stat-card').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.data-section').forEach(s => s.classList.remove('active'));
            
            if(type === 'sold'){
                document.getElementById('cardSold').classList.add('active');
                document.getElementById('sectionSold').classList.add('active');
            } else {
                document.getElementById('cardRating').classList.add('active');
                document.getElementById('sectionRating').classList.add('active');
                renderReviews('all');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderReviews('all');
        });
    </script>
</body>
</html>