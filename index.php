<?php
session_start();

// --- KONEKSI DATABASE ---
// Sesuaikan path jika perlu. Karena index.php di root, akses langsung ke Auth/koneksi.php
include '../EcoSwap/Public/Auth/koneksi.php'; 

if (!isset($koneksi)) {
    die("Error: Koneksi database gagal. Pastikan file 'Auth/koneksi.php' ada.");
}

// --- 1. HITUNG STATISTIK DARI DATABASE ---

// A. Pengguna Aktif (Semua user kecuali admin)
$q_users = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
$d_users = mysqli_fetch_assoc($q_users);
$stat_users = $d_users['total'] > 0 ? number_format($d_users['total']) : '0';

// B. Barang Terjual (Status completed/reviewed)
$q_sold = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM orders WHERE status = 'completed' OR status = 'reviewed'");
$d_sold = mysqli_fetch_assoc($q_sold);
$stat_sold = $d_sold['total'] > 0 ? number_format($d_sold['total']) : '0';

// C. Rating Rata-rata (Dari semua review)
$q_rating = mysqli_query($koneksi, "SELECT AVG(rating) as avg_rating FROM reviews");
$d_rating = mysqli_fetch_assoc($q_rating);
$stat_rating = number_format((float)$d_rating['avg_rating'], 1);

// --- 2. AMBIL PRODUK TERBARU ---
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
        
        // --- LOGIKA PERBAIKAN GAMBAR (Sama seperti dashboard buyer) ---
        $img_db = $row['image'];
        // Cek apakah gambar link online (http) atau file lokal
        if (strpos($img_db, 'http') === 0) {
            $final_img = $img_db;
        } else {
            // Hapus traversing directory (../../../) agar path sesuai dari root
            $final_img = str_replace('../../../', '', $img_db);
        }

        $featured_products[] = [
            "id" => $row['product_id'],
            "title" => $row['name'],
            "price" => $row['price'],
            "loc" => !empty($row['shop_city']) ? $row['shop_city'] : 'Indonesia',
            "img" => $final_img, // Path gambar yang sudah diperbaiki
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
    <title>Ecoswap - Jual Beli Barang Bekas Berkualitas</title>
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
            
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
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
            <div class="stat-item">
                <div class="stat-number"><?php echo $stat_users; ?>+</div>
                <div class="stat-label">Pengguna Aktif</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stat_sold; ?>+</div>
                <div class="stat-label">Barang Terjual</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stat_rating; ?></div>
                <div class="stat-label">Rating Rata-rata</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">100%</div>
                <div class="stat-label">Aman & Terpercaya</div>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <div class="section-header center">
                <h2>Kenapa Ecoswap?</h2>
                <p>Solusi cerdas untuk gaya hidup hemat dan ramah lingkungan.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>Transaksi Aman</h3>
                    <p>Sistem Rekber (Rekening Bersama) menjamin keamanan uang pembeli dan barang penjual.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-leaf"></i></div>
                    <h3>Ramah Lingkungan</h3>
                    <p>Dengan membeli barang bekas, kamu berkontribusi mengurangi limbah elektronik dan fashion.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-wallet"></i></div>
                    <h3>Harga Terjangkau</h3>
                    <p>Dapatkan barang bermerek berkualitas dengan harga miring, jauh di bawah harga toko.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <h3>Proses Cepat</h3>
                    <p>Posting barang dalam hitungan detik. Chat langsung dengan penjual tanpa perantara ribet.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="products-section" id="products">
        <div class="container">
            <div class="section-header">
                <h2>Baru Diupload</h2>
                <a href="#" class="view-all" onclick="promptLogin()">Lihat Semua <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="product-grid">
                <?php if(empty($featured_products)): ?>
                    <div style="grid-column: 1/-1; text-align: center; color: #666; padding: 40px; background: #f9f9f9; border-radius: 8px;">
                        <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                        Belum ada produk yang aktif saat ini.
                    </div>
                <?php else: ?>
                    <?php foreach ($featured_products as $p): ?>
                    <div class="product-card" onclick="promptLogin()">
                        <div class="product-badge"><?= htmlspecialchars($p['category']) ?></div>
                        <div class="product-img-wrapper">
                            <img src="<?= htmlspecialchars($p['img']) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
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
                <p>Jangan biarkan menumpuk berdebu. Jual sekarang di Ecoswap dan dapatkan uang tunai dengan cepat!</p>
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
                <p>Platform jual beli barang bekas terpercaya. Wujudkan gaya hidup berkelanjutan bersama kami.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Navigasi</h4>
                <ul>
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#features">Tentang Kami</a></li>
                    <li><a href="#products">Belanja</a></li>
                    <li><a href="#">Blog</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Hubungi Kami</h4>
                <ul class="contact-info">
                    <li><i class="fas fa-envelope"></i> support@ecoswap.id</li>
                    <li><i class="fas fa-phone"></i> +62 812 3456 7890</li>
                    <li><i class="fas fa-map-marker-alt"></i> Jakarta, Indonesia</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; 2024 Ecoswap Indonesia. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Sticky Navbar Effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Scroll to Products
        function scrollToProducts() {
            document.getElementById('products').scrollIntoView({ behavior: 'smooth' });
        }

        // Prompt Login for Interaction
        function promptLogin() {
            if(confirm("Anda harus masuk untuk melihat detail produk atau membeli. Ingin masuk sekarang?")) {
                window.location.href = 'Public/Views/guest/login.php';
            }
        }
    </script>
</body>
</html>