<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performa Toko - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/performa.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <div class="app-layout">
        
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo" onclick="window.location.href='../buyer/dashboard.php'" style="cursor: pointer;">ECO<span>SWAP</span></div>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-item"><a href="../buyer/profil.php" class="menu-link">Biodata Diri</a></li>
                <li class="menu-item"><a href="../buyer/alamat.php" class="menu-link">Alamat</a></li>
                <li class="menu-item"><a href="../buyer/histori.php" class="menu-link">Histori</a></li>
                <li class="menu-item active"><a href="dashboard.php" class="menu-link">Toko Saya</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content-wrapper">
            <div class="header">
                <div class="page-title">Performa Toko</div>
            </div>

            <div class="content">
                <div class="performa-container">
                    
                    <!-- 1. TOP STAT CARDS -->
                    <div class="stats-grid">
                        
                        <!-- Card Barang Terjual -->
                        <div class="stat-card active" id="cardSold" onclick="showSection('sold')">
                            <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                            <div class="stat-label">Barang Terjual</div>
                            <div class="stat-value">128</div>
                        </div>

                        <!-- Card Rating -->
                        <div class="stat-card" id="cardRating" onclick="showSection('rating')">
                            <div class="stat-icon"><i class="fas fa-star"></i></div>
                            <div class="stat-label">Rating Toko</div>
                            <div class="stat-value">4.8 <span style="font-size:1rem; color:#888; font-weight:normal;">/ 5.0</span></div>
                        </div>

                    </div>

                    <!-- 2. DATA SECTION: BARANG TERJUAL -->
                    <div id="sectionSold" class="data-section active">
                        <div class="section-header">
                            <div><i class="fas fa-list-ul"></i> Riwayat Penjualan</div>
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
                                    <tr>
                                        <td><strong>Laptop Asus ROG Bekas</strong><br><small style="color:#888;">#ORD-001</small></td>
                                        <td>Rp 8.500.000</td>
                                        <td>Budi Santoso</td>
                                        <td><span class="badge-shipping">JNE Reguler</span></td>
                                        <td><div class="star-rating"><i class="fas fa-star"></i> 5.0</div></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Keyboard Mechanical</strong><br><small style="color:#888;">#ORD-002</small></td>
                                        <td>Rp 350.000</td>
                                        <td>Siti Aminah</td>
                                        <td><span class="badge-shipping">GoSend</span></td>
                                        <td><div class="star-rating"><i class="fas fa-star"></i> 4.0</div></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Mouse Logitech G102</strong><br><small style="color:#888;">#ORD-003</small></td>
                                        <td>Rp 150.000</td>
                                        <td>Rizky Febian</td>
                                        <td><span class="badge-shipping">SiCepat</span></td>
                                        <td><div class="star-rating"><i class="fas fa-star"></i> 5.0</div></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Monitor Samsung 24"</strong><br><small style="color:#888;">#ORD-004</small></td>
                                        <td>Rp 900.000</td>
                                        <td>Dewi Persik</td>
                                        <td><span class="badge-shipping">GrabExpress</span></td>
                                        <td><div class="star-rating"><i class="fas fa-star"></i> 4.5</div></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 3. DATA SECTION: DAFTAR RATING (DENGAN FILTER) -->
                    <div id="sectionRating" class="data-section">
                        <div class="section-header">
                            <div><i class="fas fa-comments"></i> Ulasan Pembeli</div>
                        </div>
                        
                        <!-- FILTER RATING -->
                        <div class="rating-filters">
                            <button class="filter-pill active" onclick="filterReviews('all', this)">Semua</button>
                            <button class="filter-pill" onclick="filterReviews('5', this)">5 Bintang</button>
                            <button class="filter-pill" onclick="filterReviews('4', this)">4 Bintang</button>
                            <button class="filter-pill" onclick="filterReviews('3', this)">3 Bintang</button>
                            <button class="filter-pill" onclick="filterReviews('1-2', this)">1-2 Bintang</button>
                        </div>

                        <div class="review-list" id="reviewListContainer">
                            <!-- Review akan di-render oleh JS -->
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        // Data Dummy Ulasan
        const reviews = [
            { name: "Budi Santoso", date: "2 hari yang lalu", product: "Laptop Asus ROG Bekas", rating: 5, text: "Barang mantap, sesuai deskripsi. Pengiriman cepat!", img: "Budi" },
            { name: "Siti Aminah", date: "5 hari yang lalu", product: "Keyboard Mechanical", rating: 4, text: "Keyboard enak, cuma pengiriman agak lama.", img: "Siti" },
            { name: "Dewi Persik", date: "1 minggu yang lalu", product: "Monitor Samsung 24", rating: 4.5, text: "Monitor bening, no dead pixel. Recommended!", img: "Dewi" },
            { name: "Rizky Febian", date: "2 minggu yang lalu", product: "Mouse Logitech G102", rating: 5, text: "Mouse original, responsif banget buat gaming.", img: "Rizky" },
            { name: "Andi Saputra", date: "3 minggu yang lalu", product: "Headset Razer", rating: 3, text: "Suara oke, tapi busa agak tipis dari ekspektasi.", img: "Andi" },
            { name: "Joko Anwar", date: "1 bulan yang lalu", product: "Webcam Logitech", rating: 2, text: "Barang ada lecet parah yang tidak disebutkan di deskripsi.", img: "Joko" }
        ];

        // Fungsi Render Review
        function renderReviews(filterType) {
            const container = document.getElementById('reviewListContainer');
            container.innerHTML = '';

            const filteredReviews = reviews.filter(r => {
                if (filterType === 'all') return true;
                if (filterType === '5') return r.rating === 5;
                if (filterType === '4') return r.rating >= 4 && r.rating < 5;
                if (filterType === '3') return r.rating >= 3 && r.rating < 4;
                if (filterType === '1-2') return r.rating < 3;
                return true;
            });

            if (filteredReviews.length === 0) {
                container.innerHTML = `<div style="text-align:center; padding:20px; color:#888;">Tidak ada ulasan dengan rating ini.</div>`;
                return;
            }

            filteredReviews.forEach(r => {
                // Generate Bintang
                let starsHtml = '';
                for (let i = 1; i <= 5; i++) {
                    if (i <= Math.floor(r.rating)) {
                        starsHtml += '<i class="fas fa-star"></i>';
                    } else if (i === Math.ceil(r.rating) && !Number.isInteger(r.rating)) {
                        starsHtml += '<i class="fas fa-star-half-alt"></i>';
                    } else {
                        starsHtml += '<i class="far fa-star"></i>';
                    }
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
                    </div>
                `;
                container.appendChild(item);
            });
        }

        // Fungsi Filter
        function filterReviews(type, btn) {
            // Update active state tombol
            document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            renderReviews(type);
        }

        // Fungsi Navigasi Tab Utama (Sold vs Rating)
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
                renderReviews('all'); // Render ulang review saat tab dibuka
            }
        }

        // Init load
        document.addEventListener('DOMContentLoaded', () => {
            // Default load (bisa kosong atau render awal jika mau)
        });
    </script>
</body>
</html>