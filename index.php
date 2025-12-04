<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ecoswap</title>
    <link rel="stylesheet" href="../../Public/Assets/css/auth/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar">
        <div class="nav-left">
            <div class="logo">ECO<span>SWAP</span></div>
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Cari barang bekas berkualitas...">
                <i class="fas fa-search search-icon"></i>
            </div>
        </div>
        <div class="nav-right">
            <button class="login-btn" onclick="window.location.href='../../Public/Views/guest/login.php'">Masuk</button>
            <button class="register-btn" onclick="window.location.href='../../Public/Views/guest/register.php'">Daftar</button>
        </div>
    </nav>

    <div class="container">
        <div class="hero-section">
            <div class="carousel-track" id="carouselTrack">
                <div class="carousel-slide">
                    <img src="https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&q=80&w=1200" alt="Slide 1">
                    <div class="hero-text">
                        <h1>Barang Bekas <br><span>Berkualitas</span></h1>
                        <p>Hemat uang dan selamatkan bumi.</p>
                    </div>
                </div>
                <div class="carousel-slide">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&q=80&w=1200" alt="Slide 2">
                        <div class="hero-text">
                        <h1>Elektronik <br><span>Murah</span></h1>
                        <p>Upgrade gadget tanpa bikin kantong bolong.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2 class="section-title">Kategori Pilihan</h2>
            <a href="#" style="font-size: 0.9rem; color: var(--primary); font-weight: bold;">Lihat Semua</a>
        </div>
        <div class="category-pills">
            <div class="category-pill active">Semua</div>
            <div class="category-pill">Elektronik</div>
            <div class="category-pill">Fashion</div>
            <div class="category-pill">Hobi</div>
            <div class="category-pill">Rumah Tangga</div>
            <div class="category-pill">Buku</div>
            <div class="category-pill">Otomotif</div>
        </div>

        <div class="product-grid" id="productGrid">
            </div>

    </div>

    <div class="modal-overlay" id="productModal">
        <div class="product-modal">
            <button class="close-modal-btn" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="modal-left">
                <img id="modalImg" src="" alt="Product Image">
            </div>
            
            <div class="modal-right">
                <h2 class="modal-title" id="modalTitle">Judul Produk</h2>
                <div class="modal-price" id="modalPrice">Rp 0</div>
                
                <div class="modal-meta-row">
                    <span><i class="fas fa-map-marker-alt"></i> <span id="modalLoc">Lokasi</span></span>
                    <span><i class="fas fa-star"></i> <span id="modalCond">Kondisi</span></span>
                </div>

                <div class="modal-desc" id="modalDesc">
                    Deskripsi produk akan muncul di sini...
                </div>

                <div class="modal-actions">
                    <button class="btn btn-outline" onclick="goToChat()"><i class="fas fa-comment"></i> Chat</button>
                    <button class="btn btn-dark" onclick="addToCart()"><i class="fas fa-cart-plus"></i> Tambah</button>
                    <button class="btn btn-primary">Beli Sekarang</button>
                </div>
            </div>
        </div>
    </div>

    <div class="cart-overlay-bg" id="cartOverlay" onclick="toggleCart()"></div>
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-bag"></i> Keranjang Saya</h3>
            <button class="close-cart-btn" onclick="toggleCart()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="cart-items">
            <div class="cart-item">
                <img src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&q=80&w=200" class="cart-item-img" alt="Item">
                <div class="cart-item-info">
                    <div class="cart-item-title">Sepatu Nike Bekas</div>
                    <div class="cart-item-price">Rp 450.000</div>
                    <div class="cart-qty-ctrl">
                        <button class="qty-btn">-</button>
                        <span>1</span>
                        <button class="qty-btn">+</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="cart-footer">
            <div class="cart-total">
                <span>Total</span>
                <span style="color: var(--primary);">Rp 450.000</span>
            </div>
            <button class="btn btn-primary" style="width: 100%;">Checkout</button>
        </div>
    </div>

    <script>
        // --- PERUBAHAN PENTING DI SINI ---
        // Mengambil data Array PHP dan mengubahnya menjadi Array JavaScript (JSON)
        const products = <?php echo json_encode($data_produk); ?>;

        // Note: Pastikan kolom database kamu namanya sama dengan yang dipanggil di bawah ini:
        // img, title, price, loc, cond, desc
        // Jika nama kolom di database beda (misal: 'nama_produk'), ubah di bagian render bawah (p.nama_produk)

        // RENDER PRODUK
        const productGrid = document.getElementById('productGrid');
        
        // Cek jika tidak ada produk
        if (products.length === 0) {
            productGrid.innerHTML = '<p style="text-align:center; width:100%;">Belum ada produk yang dijual.</p>';
        } else {
            products.forEach(p => {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.onclick = () => openModal(p);
                
                // Pastikan p.price dikonversi ke angka dulu sebelum toLocaleString
                const priceFormatted = parseInt(p.price).toLocaleString('id-ID');

                card.innerHTML =
                    <div class="product-img-wrapper">
                        <img src="${p.img}" alt="${p.title}" onerror="this.src='../../Assets/img/default.jpg'">
                    </div>
                    <div class="product-info">
                        <div class="product-title">${p.title}</div>
                        <div class="product-price">Rp ${priceFormatted}</div>
                        <div class="product-meta">
                            <i class="fas fa-map-marker-alt"></i> ${p.loc}
                        </div>
                    </div>
                
                productGrid.appendChild(card);
            });
        }

        // MODAL LOGIC
        const modalOverlay = document.getElementById('productModal');
        
        function openModal(product) {
            document.getElementById('modalImg').src = product.img;
            document.getElementById('modalTitle').textContent = product.title;
            document.getElementById('modalPrice').textContent = 'Rp ' + parseInt(product.price).toLocaleString('id-ID');
            document.getElementById('modalLoc').textContent = product.loc;
            document.getElementById('modalCond').textContent = product.cond;
            document.getElementById('modalDesc').textContent = product.desc;
            
            modalOverlay.classList.add('open');
            document.body.style.overflow = 'hidden'; // Stop scroll
        }

        function closeModal() {
            modalOverlay.classList.remove('open');
            document.body.style.overflow = 'auto'; // Resume scroll
        }

        // Close modal when clicking outside
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeModal();
        });

        // CART LOGIC
        const cartSidebar = document.getElementById('cartSidebar');
        const cartOverlay = document.getElementById('cartOverlay');

        function toggleCart() {
            cartSidebar.classList.toggle('open');
            cartOverlay.classList.toggle('open');
            
            if(cartSidebar.classList.contains('open')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        }
        
        function goToChat() {
            window.location.href = '../newDashboard/chat.html';
        }

        function addToCart() {
            closeModal();
            setTimeout(toggleCart, 300); 
            alert("Barang berhasil ditambahkan ke keranjang!");
        }

        // CAROUSEL LOGIC
        const track = document.getElementById('carouselTrack');
        if(track) {
            let index = 0;
            setInterval(() => {
                index = (index + 1) % 2; 
                track.style.transform = `translateX(-${index * 100}%)`;
            }, 5000);
        }
    </script>
</body>
</html>