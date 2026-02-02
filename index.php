<?php
session_start();

// --- KONEKSI DATABASE ---
// Pastikan path ini benar sesuai struktur foldermu
include 'Public/Auth/koneksi.php'; 

// Fallback jika path di atas gagal (misal struktur foldernya beda)
if (!isset($koneksi)) {
    if (file_exists('../EcoSwap/Public/Auth/koneksi.php')) {
        include '../EcoSwap/Public/Auth/koneksi.php';
    } else {
        die("<h3>Koneksi Gagal</h3><p>Pastikan file <b>Public/Auth/koneksi.php</b> ada.</p>");
    }
}

// --- 1. STATISTIK ---
$stat_users = '0'; $stat_sold = '0'; $stat_rating = '0.0';
if ($koneksi) {
    $d_users = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role != 'admin'"));
    $stat_users = number_format($d_users['total']);
    
    $d_sold = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM orders WHERE status = 'completed' OR status = 'reviewed'"));
    $stat_sold = number_format($d_sold['total']);
    
    $d_rating = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT AVG(rating) as avg_rating FROM reviews"));
    $stat_rating = number_format((float)$d_rating['avg_rating'], 1);
}

// --- 2. AMBIL PRODUK (LOGIC GAMBAR DIPERBAIKI) ---
$featured_products = [];
$query_prod = "SELECT p.product_id, p.name, p.price, p.image, p.category, s.shop_city 
               FROM products p 
               JOIN shops s ON p.shop_id = s.shop_id 
               WHERE p.status = 'active' 
               ORDER BY p.created_at DESC 
               LIMIT 4";

$result_prod = mysqli_query($koneksi, $query_prod);

if ($result_prod) {
    while($row = mysqli_fetch_assoc($result_prod)) {
        
        $img_db = $row['image'];
        
        // --- PERBAIKAN JALUR GAMBAR ---
        if (strpos($img_db, 'http') === 0) {
            // Jika gambar dari internet (Google/Placeholder), biarkan saja
            $final_img = $img_db;
        } else {
            // Jika gambar upload lokal (tersimpan sebagai ../../../Assets/...)
            // Kita ubah menjadi Public/Assets/... agar bisa dibaca dari Index
            $final_img = str_replace('../../../', 'Public/', $img_db);
            
            // Cek cadangan: Jika replace gagal (misal di DB tersimpan tanpa ../../)
            if (strpos($final_img, 'Public/') === false && strpos($final_img, 'Assets/') === 0) {
                $final_img = 'Public/' . $final_img;
            }
        }

        $featured_products[] = [
            "id" => $row['product_id'],
            "title" => $row['name'],
            "price" => $row['price'],
            "loc" => !empty($row['shop_city']) ? $row['shop_city'] : 'Indonesia',
            "img" => $final_img, 
            "category" => $row['category']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoSwap - Home</title>
    <link rel="icon" type="image/png" href="Public/Assets/img/auth/logo.png">
    <link rel="stylesheet" href="index.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

    <nav class="navbar" id="navbar">
        <div class="container nav-container">
            <div class="logo">ECO<span>SWAP</span></div>
            <div class="nav-links">
                <a href="#home">Beranda</a>
                <a href="#features">Keunggulan</a>
                <a href="#products">Produk</a>
                <a href="#contact">Kontak</a>
            </div>
            <div class="nav-auth">
                <button class="btn-login" onclick="window.location.href='Public/Views/guest/login.php'">Masuk</button>
                <button class="btn-register" onclick="window.location.href='Public/Views/guest/register.php'">Daftar</button>
            </div>
            <div class="hamburger"><i class="fas fa-bars"></i></div>
        </div>
    </nav>

    <section class="hero" id="home">
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <h1>Ubah Barang Bekas<br>Menjadi <span>Cuan & Berkelas</span></h1>
            <p>Platform marketplace terpercaya untuk jual beli barang pre-loved. Hemat uang, kurangi limbah, dan temukan harta karun tersembunyi di sekitarmu.</p>
            <div class="hero-buttons">
                <button class="btn btn-primary btn-lg" onclick="scrollToProducts()">Mulai Belanja <i class="fas fa-shopping-bag"></i></button>
                <button class="btn btn-outline-light btn-lg" onclick="window.location.href='Public/Views/guest/register.php'">Jual Barang <i class="fas fa-arrow-right"></i></button>
            </div>
        </div>
    </section>

    <section class="stats-section">
        <div class="container stats-grid">
            <div class="stat-item"><div class="stat-number"><?php echo $stat_users; ?>+</div><div class="stat-label">Pengguna Aktif</div></div>
            <div class="stat-item"><div class="stat-number"><?php echo $stat_sold; ?>+</div><div class="stat-label">Barang Terjual</div></div>
            <div class="stat-item"><div class="stat-number"><?php echo $stat_rating; ?></div><div class="stat-label">Rating Rata-rata</div></div>
            <div class="stat-item"><div class="stat-number">100%</div><div class="stat-label">Aman & Terpercaya</div></div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <div class="section-header center">
                <h2>Kenapa Ecoswap?</h2>
                <p>Solusi cerdas untuk gaya hidup hemat dan ramah lingkungan.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card"><div class="feature-icon"><i class="fas fa-shield-alt"></i></div><h3>Transaksi Aman</h3><p>Sistem Rekber menjamin keamanan uang pembeli dan barang penjual.</p></div>
                <div class="feature-card"><div class="feature-icon"><i class="fas fa-leaf"></i></div><h3>Ramah Lingkungan</h3><p>Berkontribusi mengurangi limbah elektronik dan fashion.</p></div>
                <div class="feature-card"><div class="feature-icon"><i class="fas fa-wallet"></i></div><h3>Harga Terjangkau</h3><p>Dapatkan barang bermerek berkualitas dengan harga miring.</p></div>
                <div class="feature-card"><div class="feature-icon"><i class="fas fa-bolt"></i></div><h3>Proses Cepat</h3><p>Posting barang dalam hitungan detik. Chat langsung dengan penjual.</p></div>
            </div>
        </div>
    </section>

    <section class="products-section" id="products">
        <div class="container">
            <div class="section-header">
                <h2>Baru Diupload</h2>
                <a href="Public/Views/guest/login.php" class="view-all" onclick="promptLogin()">Lihat Semua <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="product-grid">
                <?php if(empty($featured_products)): ?>
                    <div class="empty-products-wrapper">
                        <i class="fas fa-box-open empty-products-icon"></i>
                        Belum ada produk yang aktif.
                    </div>
                <?php else: ?>
                    <?php foreach ($featured_products as $p): ?>
                    <div class="product-card" onclick="promptLogin()">
                        <div class="product-badge"><?= htmlspecialchars($p['category']) ?></div>
                        <div class="product-img-wrapper">
                            <img src="<?= htmlspecialchars($p['img']) ?>" 
                                 alt="<?= htmlspecialchars($p['title']) ?>"
                                 onerror="this.onerror=null; this.src='https://placehold.co/300x300?text=No+Image';">
                        </div>
                        <div class="product-info">
                            <div class="product-title"><?= htmlspecialchars($p['title']) ?></div>
                            <div class="product-price">Rp <?= number_format($p['price'], 0, ',', '.') ?></div>
                            <div class="product-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($p['loc']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="cta-banner">
        <div class="container">
            <div class="cta-content">
                <h2>Punya Barang Tak Terpakai?</h2>
                <p>Jangan biarkan menumpuk. Jual sekarang dan dapatkan uang tunai!</p>
                <button class="btn btn-dark btn-lg" onclick="window.location.href='Public/Views/guest/register.php'">Mulai Jual Sekarang</button>
            </div>
            <div class="cta-img">
                <img src="https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&q=80&w=600" alt="Selling">
            </div>
        </div>
    </section>

    <footer id="contact">
        <div class="container footer-content">
            <div class="footer-col brand-col">
                <div class="logo footer-logo">ECO<span>SWAP</span></div>
                <p>Platform jual beli barang bekas terpercaya.</p>
                <div class="social-links">
                    <a href="https://www.instagram.com/ahawi_channel?igsh=MWI3ZmlxaWFycm5z"><i class="fab fa-instagram"></i></a><a href="https://www.facebook.com/share/1KgQpzwwwF/"><i class="fab fa-facebook-f"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Navigasi</h4>
                <ul><li><a href="#home">Beranda</a></li>
                    <li><a href="#features">Keunggulan</a></li>
                    <li><a href="#products">Produk</a></li>
                    <li><a href="#contact">Kontak</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Hubungi Kami</h4>
                <ul class="contact-info">
                    <li><i class="fas fa-envelope"></i>ecoswap@gmail.com</li>
                    <li><i class="fas fa-map-marker-alt"></i>Surakarta, Indonesia</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom"><div class="container"><p>&copy; 2026 Ecoswap Indonesia.</p></div></div>
    </footer>

    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
        function scrollToProducts() { document.getElementById('products').scrollIntoView({ behavior: 'smooth' }); }
        function promptLogin() {
            if(confirm("Anda harus masuk untuk melihat detail. Masuk sekarang?")) {
                window.location.href = 'Public/Views/guest/login.php';
            }
        }
    </script>
</body>
</html>