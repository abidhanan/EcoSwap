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

// --- 1. HITUNG STATISTIK ---
$q_sold = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM orders WHERE shop_id='$shop_id' AND status='completed'");
$d_sold = mysqli_fetch_assoc($q_sold);
$total_sold = $d_sold['total'];

$q_rating = mysqli_query($koneksi, "
    SELECT AVG(r.rating) as avg_rating 
    FROM reviews r 
    JOIN products p ON r.product_id = p.product_id 
    WHERE p.shop_id = '$shop_id'
");
$d_rating = mysqli_fetch_assoc($q_rating);
$rating_toko = number_format((float)$d_rating['avg_rating'], 1);

// --- 2. AMBIL RIWAYAT PENJUALAN ---
$sold_items = [];
$q_history = mysqli_query($koneksi, "
    SELECT o.order_id, p.name as product_name, o.total_price, 
           u.name as buyer_name, o.shipping_method,
           (SELECT rating FROM reviews r WHERE r.order_id = o.order_id LIMIT 1) as review_rating
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN users u ON o.buyer_id = u.user_id
    WHERE o.shop_id = '$shop_id' AND o.status = 'completed'
    ORDER BY o.created_at DESC LIMIT 10
");

while($row = mysqli_fetch_assoc($q_history)) {
    $sold_items[] = $row;
}

// --- 3. AMBIL ULASAN PEMBELI (Dengan Foto) ---
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
    $date = date('d M Y', strtotime($row['created_at']));
    
    $reviews[] = [
        'name' => $row['buyer_name'],
        'date' => $date,
        'product' => $row['product_name'],
        'rating' => (int)$row['rating'],
        'text' => $row['comment'],
        'review_photo' => $row['photo'], // Menambahkan Path Foto Review
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
        /* CSS untuk thumbnail foto review */
        .review-img-thumb {
            width: 80px; height: 80px; 
            object-fit: cover; 
            border-radius: 8px; 
            margin-top: 10px; 
            border: 1px solid #eee;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .review-img-thumb:hover { transform: scale(1.05); }
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
                <li class="menu-item"><a href="../buyer/profil.php" class="menu-link"><i class="fas fa-user"></i><span>Biodata Diri</span></a></li>
                <li class="menu-item"><a href="../buyer/alamat.php" class="menu-link"><i class="fas fa-map-marker-alt"></i><span>Alamat</span></a></li>
                <li class="menu-item"><a href="../buyer/histori.php" class="menu-link"><i class="fas fa-history"></i><span>Histori</span></a></li>
                <li class="menu-item active"><a href="dashboard.php" class="menu-link"><i class="fas fa-store"></i><span>Toko Saya</span></a></li>
            </ul>

            <div class="sidebar-footer">
                <a href="../../../../index.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <div class="header">
                <div class="page-title">Performa Toko</div>
            </div>

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
                            <div class="stat-value"><?php echo $rating_toko; ?> <span style="font-size:1rem; color:#888; font-weight:normal;"></span></div>
                        </div>
                    </div>

                    <div id="sectionSold" class="data-section active">
                        <div class="section-header">
                            <div><i class="fas fa-list-ul"></i> Riwayat Penjualan </div>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nama Barang</th>
                                        <th>Harga</th>
                                        <th>Pembeli</th>
                                        <th>Pengiriman</th>
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
                                            </td>
                                            <td>Rp <?php echo number_format($item['total_price'], 0, ',', '.'); ?></td>
                                            <td><?php echo $item['buyer_name']; ?></td>
                                            <td><span class="badge-shipping"><?php echo $item['shipping_method']; ?></span></td>
                                            <td>
                                                <?php if($item['review_rating']): ?>
                                                    <div class="star-rating"><i class="fas fa-star"></i> <?php echo number_format($item['review_rating'], 1); ?></div>
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
                        <div class="section-header">
                            <div><i class="fas fa-comments"></i> Ulasan Pembeli</div>
                        </div>
                        
                        <div class="rating-filters">
                            <button class="filter-pill active" onclick="filterReviews('all', this)">Semua</button>
                            <button class="filter-pill" onclick="filterReviews('5', this)">5 Bintang</button>
                            <button class="filter-pill" onclick="filterReviews('4', this)">4 Bintang</button>
                            <button class="filter-pill" onclick="filterReviews('3', this)">3 Bintang</button>
                            <button class="filter-pill" onclick="filterReviews('2', this)">2 Bintang</button>
                            <button class="filter-pill" onclick="filterReviews('1', this)">1 Bintang</button>
                        </div>

                        <div class="review-list" id="reviewListContainer">
                            </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        function goToDashboard() {
            window.location.href = '../buyer/dashboard.php';
        }
        
        // Data Ulasan (JSON)
        const reviews = <?php echo json_encode($reviews); ?>;

        function renderReviews(filterType) {
            const container = document.getElementById('reviewListContainer');
            container.innerHTML = '';

            const filteredReviews = reviews.filter(r => {
                if (filterType === 'all') return true;
                return r.rating == filterType;
            });

            if (filteredReviews.length === 0) {
                container.innerHTML = `<div style="text-align:center; padding:20px; color:#888;">Tidak ada ulasan di kategori ini.</div>`;
                return;
            }

            filteredReviews.forEach(r => {
                // Generate Bintang
                let starsHtml = '';
                for (let i = 1; i <= 5; i++) {
                    if (i <= r.rating) {
                        starsHtml += '<i class="fas fa-star"></i>';
                    } else {
                        starsHtml += '<i class="far fa-star"></i>';
                    }
                }

                // Cek Foto
                let photoHtml = '';
                if(r.review_photo && r.review_photo !== "") {
                    photoHtml = `<div onclick="window.open('${r.review_photo}', '_blank')"><img src="${r.review_photo}" class="review-img-thumb" alt="Foto Produk"></div>`;
                }

                const item = document.createElement('div');
                item.className = 'review-item';
                item.innerHTML = `
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=${r.img}" class="buyer-avatar" alt="User">
                    <div class="review-content">
                        <div class="review-header">
                            <span class="buyer-name">${r.name}</span>
                            <span class="review-date">${r.date}</span>
                        </div>
                        <span class="review-product"><i class="fas fa-box"></i> ${r.product}</span>
                        <div class="review-stars">${starsHtml}</div>
                        <p class="review-text">${r.text}</p>
                        ${photoHtml}
                    </div>
                `;
                container.appendChild(item);
            });
        }

        function filterReviews(type, btn) {
            document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderReviews(type);
        }

        function showSection(type) {
            document.getElementById('cardSold').classList.remove('active');
            document.getElementById('cardRating').classList.remove('active');
            document.getElementById('sectionSold').classList.remove('active');
            document.getElementById('sectionRating').classList.remove('active');

            if (type === 'sold') {
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