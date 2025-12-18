<?php
session_start();

// Menggunakan path koneksi sesuai struktur folder Anda
include '../../../Auth/koneksi.php';

// Cek apakah file koneksi berhasil di-load
if (!isset($koneksi)) {
    die("Error: Gagal memuat koneksi database. Pastikan path file benar.");
}

// Ambil semua produk aktif dari semua toko
$all_products = [];
$query = mysqli_query($koneksi, "SELECT p.*, s.shop_name, a.full_address 
                                 FROM products p 
                                 JOIN shops s ON p.shop_id = s.shop_id 
                                 LEFT JOIN addresses a ON s.user_id = a.user_id AND a.is_primary = 1
                                 WHERE p.status = 'active' 
                                 ORDER BY p.created_at DESC");

// Cek jika query error
if (!$query) {
    die("Query Error: " . mysqli_error($koneksi));
}

while($row = mysqli_fetch_assoc($query)) {
    // Ambil kota dari alamat (simple extract) atau gunakan default
    $loc = !empty($row['full_address']) ? explode(',', $row['full_address'])[0] : 'Indonesia';
    
    $all_products[] = [
        'id' => $row['product_id'],
        'title' => $row['name'],
        'price' => (int)$row['price'],
        'loc' => $loc, 
        'img' => $row['image'], 
        'cond' => $row['condition'],
        'desc' => $row['description']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS untuk logika chat bubble */
.message-wrapper { 
    display: flex; 
    align-items: center; 
    gap: 8px; 
    margin-bottom: 15px; 
    position: relative; 
}
.message-actions { 
    display: none; 
    background-color: #ffffff; 
    border-radius: 15px; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.2); 
    padding: 2px 8px; 
    gap: 8px; 
    flex-shrink: 0; 
    border: 1px solid #ddd;
}
/* Munculkan aksi saat pesan diklik (class ditambahkan via JS) */
.message-wrapper.actions-visible .message-actions { 
    display: flex; 
}
.message-wrapper.outgoing .message-actions { order: -1; } /* Tombol di kiri untuk pesan kita */
.message-wrapper.incoming .message-actions { order: 1; }  /* Tombol di kanan untuk pesan orang */

.action-icon-btn { 
    background: none; 
    border: none; 
    cursor: pointer; 
    font-size: 0.85rem; 
    color: #666; 
    padding: 5px; 
    transition: color 0.2s;
}
.action-icon-btn.report:hover { color: #f39c12; }
.action-icon-btn.delete:hover { color: #e74c3c; }

/* Mencegah bubble chat menutup aksi saat di klik */
.message-bubble { cursor: pointer; transition: opacity 0.2s; }
.message-bubble:active { opacity: 0.7; }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-left">
            <div class="logo" onclick="goToDashboard()" style="cursor:pointer;">
                ECO<span>SWAP</span>
            </div>
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Cari barang bekas berkualitas...">
                <i class="fas fa-search search-icon"></i>
            </div>
        </div>
        <div class="nav-right">
            <button class="nav-icon-btn" onclick="toggleCart()">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-badge">3</span>
            </button>
            <button class="nav-icon-btn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <span class="notif-badge">5</span>
            </button>
            <button class="nav-icon-btn" onclick="toggleChat()">
                <i class="fas fa-comment-dots"></i>
                <span class="chat-badge">2</span>
            </button>
            <div class="user-avatar" onclick="window.location.href='profil.php'">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Dimas" alt="User">
            </div>
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
                    <button class="btn btn-outline" onclick="toggleChat()"><i class="fas fa-comment"></i> Chat</button>
                    <button class="btn btn-dark" onclick="addToCart()"><i class="fas fa-cart-plus"></i> Tambah</button>
                    <button class="btn btn-primary" onclick="buyNow()">Beli Sekarang</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="checkoutModal" style="z-index: 1200;">
        <div class="checkout-modal-box">
            <div class="checkout-header-modal">
                <div class="header-title">Checkout</div>
                <button class="close-modal-btn" onclick="closeCheckoutModal()" style="position:static; width:30px; height:30px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="checkout-body-modal">
                <div class="section-card-checkout">
                    <div class="section-title-checkout">
                        <span><i class="fas fa-map-marker-alt" style="color:var(--danger)"></i> Alamat Pengiriman</span>
                    </div>
                    <div class="address-box" onclick="openAddressModal()">
                        <div class="addr-change">Ubah</div>
                        <div class="addr-name" id="displayAddrName">Dimas Sudarmono | 08123456789</div>
                        <div class="addr-detail" id="displayAddrDetail">Jl. Merpati No. 45, RT 02 RW 05, Kelurahan Sukamaju, Kecamatan Sukajaya, Jakarta Selatan.</div>
                    </div>
                </div>

                <div class="section-card-checkout">
                    <div class="section-title-checkout">Produk Dipesan</div>
                    <div class="product-list-container" id="checkoutProductList">
                        </div>
                </div>

                <div class="section-card-checkout">
                    <div class="section-title-checkout">Opsi Pengiriman</div>
                    <select class="shipping-select" id="shippingSelect" onchange="calculateCheckoutTotal()">
                        <option value="0" disabled selected>Pilih Kurir</option>
                        <option value="15000">JNE Reguler (Rp 15.000)</option>
                        <option value="18000">J&T Express (Rp 18.000)</option>
                        <option value="25000">GoSend Instant (Rp 25.000)</option>
                        <option value="12000">SiCepat Halu (Rp 12.000)</option>
                    </select>
                </div>

                <div class="section-card-checkout">
                    <div class="section-title-checkout">Metode Pembayaran</div>
                    <div class="payment-category" id="cat-bank">
                        <div class="payment-header" onclick="selectPaymentCategory('bank')">
                            <div class="ph-left">
                                <i class="far fa-circle check-circle" id="check-bank"></i>
                                <span class="ph-title"><i class="fas fa-university"></i> Transfer Bank <span id="selected-bank-text" style="color:#666;font-weight:normal;margin-left:5px;font-size:0.9rem;"></span></span>
                            </div>
                            <div class="dropdown-toggle" onclick="togglePaymentDropdown(event, 'list-bank')"><i class="fas fa-chevron-down"></i></div>
                        </div>
                        <div class="payment-options-list" id="list-bank">
                            <div class="sub-option" onclick="selectPaymentSubOption('bank', 'BCA', 'Bank BCA')"><img src="https://placehold.co/40x25/003399/ffffff?text=BCA" class="sub-icon"> Bank BCA</div>
                            <div class="sub-option" onclick="selectPaymentSubOption('bank', 'BRI', 'Bank BRI')"><img src="https://placehold.co/40x25/00529C/ffffff?text=BRI" class="sub-icon"> Bank BRI</div>
                            <div class="sub-option" onclick="selectPaymentSubOption('bank', 'MDR', 'Bank Mandiri')"><img src="https://placehold.co/40x25/FFB700/000000?text=MDR" class="sub-icon"> Bank Mandiri</div>
                        </div>
                    </div>
                    <div class="payment-category" id="cat-ewallet">
                        <div class="payment-header" onclick="selectPaymentCategory('ewallet')">
                            <div class="ph-left">
                                <i class="far fa-circle check-circle" id="check-ewallet"></i>
                                <span class="ph-title"><i class="fas fa-wallet"></i> E-Wallet <span id="selected-ewallet-text" style="color:#666;font-weight:normal;margin-left:5px;font-size:0.9rem;"></span></span>
                            </div>
                            <div class="dropdown-toggle" onclick="togglePaymentDropdown(event, 'list-ewallet')"><i class="fas fa-chevron-down"></i></div>
                        </div>
                        <div class="payment-options-list" id="list-ewallet">
                            <div class="sub-option" onclick="selectPaymentSubOption('ewallet', 'Gopay', 'GoPay')"><img src="https://placehold.co/40x25/00A5CF/ffffff?text=GoPay" class="sub-icon"> GoPay</div>
                            <div class="sub-option" onclick="selectPaymentSubOption('ewallet', 'OVO', 'OVO')"><img src="https://placehold.co/40x25/4C2A86/ffffff?text=OVO" class="sub-icon"> OVO</div>
                            <div class="sub-option" onclick="selectPaymentSubOption('ewallet', 'Dana', 'Dana')"><img src="https://placehold.co/40x25/118EEA/ffffff?text=Dana" class="sub-icon"> Dana</div>
                        </div>
                    </div>
                    <div class="payment-category" id="cat-cod">
                        <div class="payment-header" onclick="selectPaymentCategory('cod')">
                            <div class="ph-left"><i class="far fa-circle check-circle" id="check-cod"></i> <span class="ph-title"><i class="fas fa-hand-holding-usd"></i> COD (Bayar di Tempat)</span></div>
                        </div>
                    </div>
                </div>

                <div class="section-card-checkout" style="margin-bottom: 20px;">
                    <div class="section-title-checkout">Rincian Pembayaran</div>
                    <div class="summary-row">
                        <span>Subtotal Produk</span>
                        <span class="price-val" id="summaryProdPrice">Rp 0</span>
                    </div>
                    <div class="summary-row">
                        <span>Subtotal Pengiriman</span>
                        <span class="price-val" id="summaryShipPrice">Rp 0</span>
                    </div>
                    <div class="summary-row">
                        <span>Biaya Layanan</span>
                        <span class="price-val">Rp 1.000</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Pembayaran</span>
                        <span class="price-val" id="summaryTotal" style="color:var(--primary)">Rp 0</span>
                    </div>
                </div>
            </div>

            <div class="checkout-footer-modal">
                <div class="total-display">
                    <div class="total-label">Total Tagihan</div>
                    <div class="total-final" id="bottomTotal">Rp 0</div>
                </div>
                <button class="btn-order" onclick="processOrder()">Buat Pesanan</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="addressModal" style="z-index: 1300;">
        <div class="modal-box-address">
            <div class="modal-header-address">
                <span>Pilih Alamat</span>
                <i class="fas fa-times" onclick="closeAddressModal()" style="cursor:pointer;"></i>
            </div>
            <div class="address-option selected" onclick="selectAddress(this, 'Dimas (Rumah)', 'Jl. Merpati No. 45, Jakarta Selatan')">
                <div style="font-weight:bold;">Rumah</div>
                <div style="font-size:0.85rem; color:#666;">Dimas Sudarmono | 08123456789</div>
                <div style="font-size:0.85rem;">Jl. Merpati No. 45, Jakarta Selatan.</div>
            </div>
             <div class="address-option" onclick="selectAddress(this, 'Dimas (Kantor)', 'Gedung Cyber 2, Jakarta Selatan')">
                <div style="font-weight:bold;">Kantor</div>
                <div style="font-size:0.85rem; color:#666;">Dimas Sudarmono | 08123456789</div>
                <div style="font-size:0.85rem;">Gedung Cyber 2, Kuningan, Jakarta Selatan.</div>
            </div>
        </div>
    </div>

    <div class="cart-overlay-bg" id="cartOverlay" onclick="toggleCart()"></div>
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-bag"></i> Keranjang Saya</h3>
            <button class="close-cart-btn" onclick="toggleCart()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="cart-items" id="cartItemsContainer">
            <div class="cart-item" data-id="c1" data-price="450000" data-name="Sepatu Kalcer Adidas Bekas Size 42" data-img="../../../Assets/img/role/buyer/sepatu_adidas.jpg">
                <div class="cart-check-wrapper">
                    <input type="checkbox" class="cart-checkbox" onchange="updateCartTotal()">
                </div>
                <img src="../../../Assets/img/role/buyer/sepatu_adidas.jpg" class="cart-item-img" alt="Item">
                <div class="cart-item-info">
                    <div class="cart-item-title">Sepatu Kalcer Adidas Bekas Size 42</div>
                    <div class="cart-item-price">Rp 450.000</div>
                </div>
            </div>

            <div class="cart-item" data-id="c2" data-price="8500000" data-name="Laptop Asus ROG Bekas Gaming Murah" data-img="../../../Assets/img/role/buyer/laptop-rog.jpeg">
                <div class="cart-check-wrapper">
                    <input type="checkbox" class="cart-checkbox" onchange="updateCartTotal()">
                </div>
                <img src="../../../Assets/img/role/buyer/laptop-rog.jpeg" class="cart-item-img" alt="Item">
                <div class="cart-item-info">
                    <div class="cart-item-title">Laptop Asus ROG Bekas Gaming Murah</div>
                    <div class="cart-item-price">Rp 8.500.000</div>
                </div>
            </div>
            
            <div class="cart-item" data-id="c3" data-price="500000" data-name="Koleksi Komik One Piece Vol 1-50" data-img="../../../Assets/img/role/buyer/komik-onePiece.jpeg">
                <div class="cart-check-wrapper">
                    <input type="checkbox" class="cart-checkbox" onchange="updateCartTotal()">
                </div>
                <img src="../../../Assets/img/role/buyer/komik-onePiece.jpeg" class="cart-item-img" alt="Item">
                <div class="cart-item-info">
                    <div class="cart-item-title">Koleksi Komik One Piece Vol 1-50</div>
                    <div class="cart-item-price">Rp 500.000</div>
                </div>
            </div>
        </div>

        <div class="cart-footer">
            <div class="cart-total">
                <span>Total Dipilih</span>
                <span style="color: var(--primary);" id="cartTotalPrice">Rp 0</span>
            </div>
            <button class="btn btn-primary" style="width: 100%;" onclick="checkoutFromCart()">Checkout</button>
        </div>
    </div>

    <div class="notif-overlay-bg" id="notifOverlay" onclick="toggleNotifications()"></div>
    <div class="notif-sidebar" id="notifSidebar">
        <div class="notif-header">
            <h3><i class="fas fa-bell"></i> Notifikasi</h3>
            <button class="close-notif-btn" onclick="toggleNotifications()"><i class="fas fa-times"></i></button>
        </div>

        <div class="notif-items" id="notifItemsContainer">
            <div class="notif-item">
                <div class="notif-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="notif-content">
                    <div class="notif-title">Pesanan Baru</div>
                    <div class="notif-message">Anda memiliki pesanan baru dari Dimas.</div>
                    <div class="notif-time">2 jam yang lalu</div>
                </div>
            </div>
            </div>
    </div>

    <div class="chat-overlay-bg" id="chatOverlay" onclick="toggleChat()"></div>
    <div class="chat-sidebar" id="chatSidebar">
        <div class="chat-header">
            <h3><i class="fas fa-comment-dots"></i> Chat</h3>
            <button class="close-chat-btn" onclick="toggleChat()"><i class="fas fa-times"></i></button>
        </div>

        <div class="chat-items" id="chatItemsContainer">
            <div class="chat-item" onclick="selectChat('Dimas')">
                <div class="chat-avatar"><img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Dimas" alt="User"></div>
                <div class="chat-content">
                    <div class="chat-name">Dimas</div>
                    <div class="chat-message">Halo, apakah barangnya masih ada?</div>
                    <div class="chat-time">2 jam yang lalu</div>
                </div>
            </div>
            </div>

        <div class="chat-area-sidebar" id="chatAreaSidebar" style="display: none;">
            <div class="chat-header-sidebar">
                <button class="back-to-list-btn" onclick="backToChatList()"><i class="fas fa-arrow-left"></i></button>
                <div class="seller-info-sidebar">
                    <img src="" alt="Seller" class="seller-avatar-sidebar" id="chatSellerAvatarSidebar">
                    <div class="seller-details-sidebar">
                        <h4 id="chatSellerNameSidebar">Nama Penjual</h4>
                        <span>Online</span>
                    </div>
                </div>
            </div>
            <div class="chat-messages" id="chatMessagesSidebar"></div>
            <div class="input-area-sidebar">
                <button class="add-file-btn-sidebar" onclick="openFileInput()"><i class="fas fa-plus"></i></button>
                <input type="file" id="fileInput" accept="image/*" style="display: none;" onchange="sendImage()">
                <input type="text" class="chat-input-sidebar" id="messageInputSidebar" placeholder="Tulis pesan..." onkeypress="handleEnterSidebar(event)">
                <button class="send-btn-sidebar" onclick="sendMessageSidebar()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script>
        const goToDashboard = () => window.location.href = 'dashboard.php';
        
        // DATA PRODUK DARI DATABASE
        const products = <?php echo json_encode($all_products); ?>;

        // RENDER PRODUK GRID
        const productGrid = document.getElementById('productGrid');

        products.forEach(p => {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.onclick = () => openModal(p);
            card.innerHTML = `
                <div class="product-img-wrapper">
                    <img src="${p.img}" alt="${p.title}">
                </div>
                <div class="product-info">
                    <div class="product-title">${p.title}</div>
                    <div class="product-price">Rp ${p.price.toLocaleString('id-ID')}</div>
                    <div class="product-meta">
                        <i class="fas fa-map-marker-alt"></i> ${p.loc}
                    </div>
                </div>
            `;
            productGrid.appendChild(card);
        });

        // MODAL LOGIC (Detail Produk)
        const modalOverlay = document.getElementById('productModal');

        function openModal(product) {
            document.getElementById('modalImg').src = product.img;
            document.getElementById('modalTitle').textContent = product.title;
            document.getElementById('modalPrice').textContent = 'Rp ' + product.price.toLocaleString('id-ID');
            document.getElementById('modalLoc').textContent = product.loc;
            document.getElementById('modalCond').textContent = product.cond;
            document.getElementById('modalDesc').textContent = product.desc;
            
            modalOverlay.classList.add('open');
            document.body.style.overflow = 'hidden'; 
        }

        function closeModal() {
            modalOverlay.classList.remove('open');
            document.body.style.overflow = 'auto'; 
        }

        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeModal();
        });

        // --- CART LOGIC ---
        const cartSidebar = document.getElementById('cartSidebar');
        const cartOverlay = document.getElementById('cartOverlay');

        function toggleCart() {
            cartSidebar.classList.toggle('open');
            cartOverlay.classList.toggle('open');
            if(cartSidebar.classList.contains('open')) {
                document.body.style.overflow = 'hidden';
                updateCartTotal(); 
            } else {
                document.body.style.overflow = 'auto';
            }
        }

        function updateCartTotal() {
            let total = 0;
            const items = document.querySelectorAll('.cart-item');
            items.forEach(item => {
                const checkbox = item.querySelector('.cart-checkbox');
                if (checkbox.checked) {
                    const price = parseInt(item.getAttribute('data-price'));
                    total += price;
                }
            });
            document.getElementById('cartTotalPrice').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }

        function addToCart() {
            closeModal();
            setTimeout(toggleCart, 300);
            alert("Barang berhasil ditambahkan ke keranjang!");
        }

        // --- CHECKOUT LOGIC (MERGED FROM CHECKOUT.PHP) ---
        
        let checkoutProductPriceTotal = 0;
        let checkoutShippingPrice = 0;
        const checkoutServiceFee = 1000;
        let activePaymentCategory = null;
        let activePaymentSubOption = null;

        // FUNGSI: Buka Modal Checkout dari Keranjang
        function checkoutFromCart() {
            const items = document.querySelectorAll('.cart-item');
            let selectedItems = [];

            items.forEach(item => {
                const checkbox = item.querySelector('.cart-checkbox');
                if (checkbox.checked) {
                    selectedItems.push({
                        title: item.getAttribute('data-name'),
                        price: parseInt(item.getAttribute('data-price')),
                        img: item.getAttribute('data-img')
                    });
                }
            });

            if (selectedItems.length === 0) {
                alert("Silakan pilih minimal satu barang untuk di-checkout.");
                return;
            }

            // Simpan ke LocalStorage
            localStorage.setItem('checkoutItems', JSON.stringify(selectedItems));
            
            // Tutup Sidebar Keranjang
            toggleCart();
            
            // Buka Modal Checkout
            initCheckoutModal();
        }

        // FUNGSI: Beli Langsung (Single Item)
        function buyNow() {
            const title = document.getElementById('modalTitle').textContent;
            const priceStr = document.getElementById('modalPrice').textContent.replace('Rp ', '').replace(/\./g, '');
            const price = parseInt(priceStr);
            const img = document.getElementById('modalImg').src;

            const productData = [{
                title: title,
                price: price,
                img: img
            }];
            
            localStorage.setItem('checkoutItems', JSON.stringify(productData));
            
            closeModal(); // Tutup modal detail produk
            initCheckoutModal(); // Buka modal checkout
        }

        // FUNGSI: Inisialisasi Modal Checkout (Render Items)
        function initCheckoutModal() {
            const storedData = localStorage.getItem('checkoutItems');
            const container = document.getElementById('checkoutProductList');
            container.innerHTML = ''; // Clear previous
            checkoutProductPriceTotal = 0;

            if (storedData) {
                const products = JSON.parse(storedData);
                if (products && products.length > 0) {
                    products.forEach(item => {
                        checkoutProductPriceTotal += item.price;
                        const row = document.createElement('div');
                        row.className = 'product-row-checkout';
                        row.innerHTML = `
                            <img src="${item.img}" class="product-img-checkout">
                            <div class="product-details-checkout">
                                <div class="prod-name-checkout">${item.title}</div>
                                <div class="prod-price-checkout">Rp ${item.price.toLocaleString('id-ID')}</div>
                            </div>
                        `;
                        container.appendChild(row);
                    });
                }
            }
            
            // Reset Pilihan
            document.getElementById('shippingSelect').value = "0";
            document.querySelectorAll('.payment-category').forEach(el => {
                el.classList.remove('active');
                el.querySelector('.check-circle').className = 'far fa-circle check-circle';
            });
            activePaymentCategory = null;
            document.getElementById('selected-bank-text').innerText = '';
            document.getElementById('selected-ewallet-text').innerText = '';

            calculateCheckoutTotal();
            document.getElementById('checkoutModal').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.remove('open');
            document.body.style.overflow = 'auto';
        }

        // Hitung Total di Checkout
        function calculateCheckoutTotal() {
            const shipSelect = document.getElementById('shippingSelect');
            checkoutShippingPrice = parseInt(shipSelect.value) || 0;

            const total = checkoutProductPriceTotal + checkoutShippingPrice + checkoutServiceFee;

            document.getElementById('summaryProdPrice').innerText = 'Rp ' + checkoutProductPriceTotal.toLocaleString('id-ID');
            document.getElementById('summaryShipPrice').innerText = 'Rp ' + checkoutShippingPrice.toLocaleString('id-ID');
            document.getElementById('summaryTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
            document.getElementById('bottomTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }

        // Logika Pembayaran Checkout
        function selectPaymentCategory(catId) {
            document.querySelectorAll('.payment-category').forEach(el => {
                el.classList.remove('active');
                el.querySelector('.check-circle').className = 'far fa-circle check-circle';
            });
            const activeEl = document.getElementById('cat-' + catId);
            activeEl.classList.add('active');
            activeEl.querySelector('.check-circle').className = 'fas fa-check-circle check-circle';
            activePaymentCategory = catId;
            
            if (catId === 'cod') {
                activePaymentSubOption = null;
                closeAllPaymentDropdowns();
            } else {
                const listEl = document.getElementById('list-' + catId);
                if (!listEl.classList.contains('show')) {
                    closeAllPaymentDropdowns();
                    listEl.classList.add('show');
                }
            }
        }

        function togglePaymentDropdown(event, listId) {
            event.stopPropagation();
            const listEl = document.getElementById(listId);
            const isShown = listEl.classList.contains('show');
            closeAllPaymentDropdowns();
            if (!isShown) listEl.classList.add('show');
        }

        function closeAllPaymentDropdowns() {
            document.querySelectorAll('.payment-options-list').forEach(el => el.classList.remove('show'));
        }

        function selectPaymentSubOption(catId, val, displayName) {
            selectPaymentCategory(catId);
            const listContainer = document.getElementById('list-' + catId);
            listContainer.querySelectorAll('.sub-option').forEach(el => el.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            activePaymentSubOption = val;
            
            if (catId === 'bank') {
                document.getElementById('selected-bank-text').innerText = `(${displayName})`;
                document.getElementById('selected-ewallet-text').innerText = '';
            } else if (catId === 'ewallet') {
                document.getElementById('selected-ewallet-text').innerText = `(${displayName})`;
                document.getElementById('selected-bank-text').innerText = '';
            }
            setTimeout(() => { document.getElementById('list-' + catId).classList.remove('show'); }, 200);
        }

        // Proses Pesanan
        function processOrder() {
            const shipSelect = document.getElementById('shippingSelect');
            if (shipSelect.value === "0") {
                alert("Mohon pilih jasa pengiriman terlebih dahulu.");
                return;
            }
            if (!activePaymentCategory) {
                alert("Mohon pilih metode pembayaran.");
                return;
            }
            alert("Pesanan berhasil dibuat! Anda akan dialihkan ke dashboard.");
            closeCheckoutModal();
        }

        // Modal Alamat (Checkout)
        function openAddressModal() { document.getElementById('addressModal').classList.add('open'); }
        function closeAddressModal() { document.getElementById('addressModal').classList.remove('open'); }
        function selectAddress(element, name, detail) {
            document.querySelectorAll('.address-option').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('displayAddrDetail').innerText = detail;
            closeAddressModal();
        }

        // --- CAROUSEL ---
        const track = document.getElementById('carouselTrack');
        let index = 0;
        setInterval(() => {
            index = (index + 1) % 2;
            track.style.transform = `translateX(-${index * 100}%)`;
        }, 5000);

        // --- NOTIFICATIONS & CHAT (EXISTING) ---
        const notifSidebar = document.getElementById('notifSidebar');
        const notifOverlay = document.getElementById('notifOverlay');
        function toggleNotifications() {
            notifSidebar.classList.toggle('open');
            notifOverlay.classList.toggle('open');
            if(notifSidebar.classList.contains('open')) document.body.style.overflow = 'hidden';
            else document.body.style.overflow = 'auto';
        }

        const chatSidebar = document.getElementById('chatSidebar');
        const chatOverlay = document.getElementById('chatOverlay');
        function toggleChat() {
            chatSidebar.classList.toggle('open');
            chatOverlay.classList.toggle('open');
            if(chatSidebar.classList.contains('open')) document.body.style.overflow = 'hidden';
            else document.body.style.overflow = 'auto';
        }

        // Dummy Logic untuk Chat
        const chatData = {
            'Dimas': [{ id: 1, type: 'incoming', text: 'Halo kak, barang ready?', time: '09:00' }],
            'Sari': [{ id: 1, type: 'incoming', text: 'Barangnya bagus!', time: '10:00' }]
        };
        let messages = [];
        let currentChatSeller = '';

        function selectChat(sellerName) {
            currentChatSeller = sellerName;
            messages = chatData[sellerName] ? [...chatData[sellerName]] : [];
            document.getElementById('chatSellerNameSidebar').textContent = sellerName;
            document.getElementById('chatSellerAvatarSidebar').src = `https://api.dicebear.com/7.x/avataaars/svg?seed=${sellerName}`;
            renderMessagesSidebar();
            document.getElementById('chatItemsContainer').style.display = 'none';
            document.getElementById('chatAreaSidebar').style.display = 'flex';
        }

        function backToChatList() {
            document.getElementById('chatItemsContainer').style.display = 'block';
            document.getElementById('chatAreaSidebar').style.display = 'none';
        }

        // Ganti fungsi renderMessagesSidebar lama dengan ini
function renderMessagesSidebar() {
    const chatMessagesSidebar = document.getElementById('chatMessagesSidebar');
    chatMessagesSidebar.innerHTML = '';
    
    messages.forEach((msg, index) => {
        const wrapper = document.createElement('div');
        wrapper.className = `message-wrapper ${msg.type}`;
        wrapper.id = `msg-${index}`;
        
        // Logika Tombol Aksi
        let actionButtons = '';
        if (msg.type === 'outgoing') {
            // Jika pesan sendiri -> Tombol Hapus
            actionButtons = `<button class="action-icon-btn delete" onclick="deleteMessage(${index})"><i class="fas fa-trash-alt"></i></button>`;
        } else {
            // Jika pesan orang lain -> Tombol Lapor
            actionButtons = `<button class="action-icon-btn report" onclick="reportMessage(${index})"><i class="fas fa-exclamation-triangle"></i></button>`;
        }

        wrapper.innerHTML = `
            <div class="message-actions">${actionButtons}</div>
            <div class="message-bubble" onclick="toggleMessageActions(${index})">${msg.text}</div>
            <span class="message-time">${msg.time}</span>
        `;
        chatMessagesSidebar.appendChild(wrapper);
    });
    chatMessagesSidebar.scrollTop = chatMessagesSidebar.scrollHeight;
}

// FUNGSI BARU: Toggle munculnya tombol
function toggleMessageActions(index) {
    const allWrappers = document.querySelectorAll('.message-wrapper');
    const target = document.getElementById(`msg-${index}`);
    
    // Sembunyikan aksi lain yang sedang terbuka
    allWrappers.forEach(w => {
        if (w !== target) w.classList.remove('actions-visible');
    });
    
    // Toggle milik pesan yang diklik
    target.classList.toggle('actions-visible');
}

// FUNGSI BARU: Hapus Pesan
function deleteMessage(index) {
    if (confirm("Hapus pesan ini?")) {
        messages.splice(index, 1); // Hapus dari array
        renderMessagesSidebar();   // Gambar ulang
    }
}

// FUNGSI BARU: Lapor Pesan
function reportMessage(index) {
    const msgText = messages[index].text;
    alert(`Pesan: "${msgText}" telah dilaporkan ke admin Ecoswap.`);
    // Tutup aksi setelah lapor
    document.getElementById(`msg-${index}`).classList.remove('actions-visible');
}

        function sendMessageSidebar() {
            const input = document.getElementById('messageInputSidebar');
            if(input.value.trim()) {
                messages.push({id: Date.now(), type:'outgoing', text: input.value, time: 'Now'});
                renderMessagesSidebar();
                input.value = '';
            }
        }
        function handleEnterSidebar(e) { if(e.key === 'Enter') sendMessageSidebar(); }
        function openFileInput() { document.getElementById('fileInput').click(); }
        function sendImage() { alert("Fitur kirim gambar dummy"); }

    </script>
</body>
</html>