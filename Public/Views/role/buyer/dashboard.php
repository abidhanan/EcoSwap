<?php
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
        .message-wrapper {
            /* position: relative; */ /* Tidak lagi menggunakan position:absolute */
            align-items: center; /* Vertikalkan bubble, waktu, dan aksi */
            gap: 8px; /* Beri jarak antar elemen */
        }
        .message-actions {
            display: none; /* Sembunyikan menu aksi secara default */
            /* position: absolute; */ /* Kembali ke alur normal (flex item) */
            background-color: #f0f0f0;
            border-radius: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 2px 5px;
            gap: 5px;
            flex-shrink: 0; /* Mencegah tombol menyusut saat ruang sempit */
        }
        .message-wrapper.outgoing .message-actions {
            order: -1; /* Pindahkan ke paling kiri untuk pesan keluar */
        }
        .message-wrapper.incoming .message-actions {
            /* Biarkan di posisi default (paling kanan) untuk pesan masuk */
        }
        .message-wrapper.actions-visible .message-actions {
            display: flex; /* Tampilkan menu aksi saat wrapper memiliki kelas ini */
        }
        .action-icon-btn { background: none; border: none; cursor: pointer; font-size: 0.8rem; color: #555; padding: 4px; }
        .action-icon-btn.report:hover { color: #f39c12; }
        .action-icon-btn.delete:hover { color: #e74c3c; }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-left">
            <div class="logo">ECO<span>SWAP</span></div>
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Cari barang bekas berkualitas...">
                <i class="fas fa-search search-icon"></i>
            </div>
        </div>
        <div class="nav-right">
            <!-- Ikon Keranjang dengan Badge -->
            <button class="nav-icon-btn" onclick="toggleCart()">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-badge">3</span>
            </button>
            <!-- Ikon Notifikasi -->
            <button class="nav-icon-btn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <span class="notif-badge">5</span>
            </button>
            <!-- Ikon Chat -->
            <button class="nav-icon-btn" onclick="toggleChat()">
                <i class="fas fa-comment-dots"></i>
                <span class="chat-badge">2</span>
            </button>
            <!-- Profil Avatar -->
            <div class="user-avatar" onclick="window.location.href='profil.php'">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Dimas" alt="User">
            </div>
        </div>
    </nav>

    <!-- HERO CAROUSEL -->
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

        <!-- CATEGORIES -->
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

        <!-- PRODUCT GRID -->
        <div class="product-grid" id="productGrid">
            <!-- Produk akan di-generate oleh JS -->
        </div>

    </div>

    <!-- PRODUCT DETAIL MODAL (POP UP) -->
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
                    <button class="btn btn-outline" onclick="goToHubungi()"><i class="fas fa-comment"></i> Chat</button>
                    <button class="btn btn-dark" onclick="addToCart()"><i class="fas fa-cart-plus"></i> Tambah</button>
                    <button class="btn btn-primary" onclick="buyNow()">Beli Sekarang</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SHOPPING CART SIDEBAR -->
    <div class="cart-overlay-bg" id="cartOverlay" onclick="toggleCart()"></div>
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-bag"></i> Keranjang Saya</h3>
            <button class="close-cart-btn" onclick="toggleCart()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="cart-items" id="cartItemsContainer">
            <!-- Dummy Cart Item 1 -->
            <div class="cart-item" data-id="c1" data-price="450000" data-name="Sepatu Nike Bekas" data-img="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&q=80&w=200">
                <div class="cart-check-wrapper">
                    <input type="checkbox" class="cart-checkbox" onchange="updateCartTotal()">
                </div>
                <img src="../../../Assets/img/role/buyer/sepatu_adidas.jpg" class="cart-item-img" alt="Item">
                <div class="cart-item-info">
                    <div class="cart-item-title">Sepatu Kalcer Adidas Bekas Size 42</div>
                    <div class="cart-item-price">Rp 450.000</div>
                </div>
            </div>

            <!-- Dummy Cart Item 2 -->
            <div class="cart-item" data-id="c2" data-price="1200000" data-name="Monitor Dell 24 Inch" data-img="https://images.unsplash.com/photo-1593642702821-c8da6771f0c6?auto=format&fit=crop&q=80&w=200">
                <div class="cart-check-wrapper">
                    <input type="checkbox" class="cart-checkbox" onchange="updateCartTotal()">
                </div>
                <img src="../../../Assets/img/role/buyer/laptop-rog.jpeg" class="cart-item-img" alt="Item">
                <div class="cart-item-info">
                    <div class="cart-item-title">Laptop Asus ROG Bekas Gaming Murah"</div>
                    <div class="cart-item-price">Rp 8.500.000</div>
                </div>
            </div>
            
            <!-- Dummy Cart Item 3 -->
            <div class="cart-item" data-id="c3" data-price="150000" data-name="Novel Harry Potter" data-img="https://images.unsplash.com/photo-1588160298175-9c5957303e3a?auto=format&fit=crop&q=80&w=200">
                <div class="cart-check-wrapper">
                    <input type="checkbox" class="cart-checkbox" onchange="updateCartTotal()">
                </div>
                <img src="../../../Assets/img/role/buyer/komik-onePiece.jpeg" class="cart-item-img" alt="Item">
                <div class="cart-item-info">
                    <div class="cart-item-title">"Koleksi Komik One Piece Vol 1-50</div>
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

    <!-- NOTIFICATIONS SIDEBAR -->
    <div class="notif-overlay-bg" id="notifOverlay" onclick="toggleNotifications()"></div>
    <div class="notif-sidebar" id="notifSidebar">
        <div class="notif-header">
            <h3><i class="fas fa-bell"></i> Notifikasi</h3>
            <button class="close-notif-btn" onclick="toggleNotifications()"><i class="fas fa-times"></i></button>
        </div>

        <div class="notif-items" id="notifItemsContainer">
            <!-- Dummy Notification Items -->
            <div class="notif-item">
                <div class="notif-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-title">Pesanan Baru</div>
                    <div class="notif-message">Anda memiliki pesanan baru dari Dimas.</div>
                    <div class="notif-time">2 jam yang lalu</div>
                </div>
            </div>
            <div class="notif-item">
                <div class="notif-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-title">Ulasan Baru</div>
                    <div class="notif-message">Produk Anda mendapat ulasan 5 bintang.</div>
                    <div class="notif-time">1 hari yang lalu</div>
                </div>
            </div>
            <div class="notif-item">
                <div class="notif-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-title">Pengiriman</div>
                    <div class="notif-message">Pesanan Anda sedang dalam perjalanan.</div>
                    <div class="notif-time">3 hari yang lalu</div>
                </div>
            </div>
            <div class="notif-item">
                <div class="notif-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-title">Promo Spesial</div>
                    <div class="notif-message">Diskon 20% untuk semua elektronik bekas!</div>
                    <div class="notif-time">5 hari yang lalu</div>
                </div>
            </div>
            <div class="notif-item">
                <div class="notif-icon">
                    <i class="fas fa-comment"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-title">Pesan Baru</div>
                    <div class="notif-message">Anda mendapat pesan dari penjual.</div>
                    <div class="notif-time">1 minggu yang lalu</div>
                </div>
            </div>
        </div>
    </div>

    <!-- CHAT SIDEBAR -->
    <div class="chat-overlay-bg" id="chatOverlay" onclick="toggleChat()"></div>
    <div class="chat-sidebar" id="chatSidebar">
        <div class="chat-header">
            <h3><i class="fas fa-comment-dots"></i> Chat</h3>
            <button class="close-chat-btn" onclick="toggleChat()"><i class="fas fa-times"></i></button>
        </div>

        <div class="chat-items" id="chatItemsContainer">
            <!-- Dummy Chat Items -->
            <div class="chat-item" onclick="selectChat('Dimas')">
                <div class="chat-avatar">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Dimas" alt="User">
                </div>
                <div class="chat-content">
                    <div class="chat-name">Dimas</div>
                    <div class="chat-message">Halo, apakah barangnya masih ada?</div>
                    <div class="chat-time">2 jam yang lalu</div>
                </div>
            </div>
            <div class="chat-item" onclick="selectChat('Sari')">
                <div class="chat-avatar">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Sari" alt="User">
                </div>
                <div class="chat-content">
                    <div class="chat-name">Sari</div>
                    <div class="chat-message">Barangnya bagus sekali!</div>
                    <div class="chat-time">1 hari yang lalu</div>
                </div>
            </div>
            <div class="chat-item" onclick="selectChat('Budi')">
                <div class="chat-avatar">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Budi" alt="User">
                </div>
                <div class="chat-content">
                    <div class="chat-name">Budi</div>
                    <div class="chat-message">Bisa nego harga?</div>
                    <div class="chat-time">3 hari yang lalu</div>
                </div>
            </div>
        </div>

        <!-- CHAT AREA INSIDE SIDEBAR -->
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
            <div class="chat-messages" id="chatMessagesSidebar">
                <!-- Messages will be loaded here -->
            </div>
            <div class="input-area-sidebar">
                <button class="add-file-btn-sidebar" onclick="openFileInput()"><i class="fas fa-plus"></i></button>
                <input type="file" id="fileInput" accept="image/*" style="display: none;" onchange="sendImage()">
                <input type="text" class="chat-input-sidebar" id="messageInputSidebar" placeholder="Tulis pesan..." onkeypress="handleEnterSidebar(event)">
                <button class="send-btn-sidebar" onclick="sendMessageSidebar()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>



    <script>
        // DATA PRODUK
        const products = [
            { id: 1, title: "Sepatu Kalcer Adidas Bekas Size 42", price: 450000, loc: "Bandung", img: "../../../Assets/img/role/buyer/sepatu_adidas.jpg", cond: "Bekas Baik", desc: "Sepatu masih sangat nyaman, sol tebal. Ada sedikit lecet pemakaian wajar." },
            { id: 2, title: "Laptop Asus ROG Bekas Gaming Murah", price: 8500000, loc: "Jakarta", img: "../../../Assets/img/role/buyer/laptop-rog.jpeg", cond: "Mulus", desc: "RAM 16GB, SSD 512GB. Kelengkapan fullset dus dan charger." },
            { id: 3, title: "Kamera Canon DSLR 600D Lensa Kit", price: 3100000, loc: "Surabaya", img: "../../../Assets/img/role/buyer/camera-canon.jpeg", cond: "Lecet Pemakaian", desc: "Fungsi normal 100%, bonus tas kamera dan memory card." },
            { id: 4, title: "Headphone Sony WH-1000XM4", price: 2500000, loc: "Yogyakarta", img: "../../../Assets/img/role/buyer/headphone-sony.jpeg", cond: "Like New", desc: "Baru dipakai 2 bulan, garansi masih aktif. Suara jernih noice cancelling mantap." },
            { id: 5, title: "Sepeda Lipat Polygon United", price: 1800000, loc: "Semarang", img: "../../../Assets/img/role/buyer/sepeda-lipat.jpeg", cond: "Bekas", desc: "Lipatan aman, rem pakem, siap gowes santai." },
            { id: 6, title: "Meja Belajar Kayu Jati Minimalis", price: 350000, loc: "Jepara", img: "../../../Assets/img/role/buyer/meja-belajar.jpeg", cond: "Kokoh", desc: "Kayu jati asli, finishing varnish ulang biar kinclong." },
            { id: 7, title: "Jam Tangan Fossil Leather Original", price: 900000, loc: "Jakarta", img: "../../../Assets/img/role/buyer/jam-tangan.jpeg", cond: "Strap Aus", desc: "Mesin original normal, strap kulit agak aus perlu ganti." },
            { id: 8, title: "Koleksi Komik One Piece Vol 1-50", price: 500000, loc: "Malang", img: "../../../Assets/img/role/buyer/komik-onePiece.jpeg", cond: "Terawat", desc: "Koleksi pribadi, kertas sedikit menguning karena usia tapi tidak sobek." },
        ];

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

        // MODAL LOGIC
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

        // FUNGSI 1: Update Total di Sidebar Keranjang berdasarkan Checkbox
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

        // FUNGSI 2: Checkout Multi Item dari Keranjang
        function checkoutFromCart() {
            const items = document.querySelectorAll('.cart-item');
            let selectedItems = [];

            items.forEach(item => {
                const checkbox = item.querySelector('.cart-checkbox');
                if (checkbox.checked) {
                    // Ambil semua data produk yang dicentang
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

            // Simpan LIST PRODUK (Array) ke LocalStorage
            localStorage.setItem('checkoutItems', JSON.stringify(selectedItems));

            // Pindah Halaman
            window.location.href = 'checkout.php';
        }

        function goToHubungi() {
            window.location.href = 'hubungi.php';
        }

        function addToCart() {
            closeModal();
            setTimeout(toggleCart, 300);
            alert("Barang berhasil ditambahkan ke keranjang!");
        }

        // FUNGSI 3: Beli Langsung (Single Item dari Modal)
        function buyNow() {
            const title = document.getElementById('modalTitle').textContent;
            const priceStr = document.getElementById('modalPrice').textContent.replace('Rp ', '').replace(/./g, '');
            const price = parseInt(priceStr);
            const img = document.getElementById('modalImg').src;

            // Simpan sebagai Array (Isi 1 item) agar formatnya sama dengan checkout dari keranjang
            const productData = [{
                title: title,
                price: price,
                img: img
            }];
            
            localStorage.setItem('checkoutItems', JSON.stringify(productData));
            window.location.href = 'checkout.php'; 
        }

        // CAROUSEL
        const track = document.getElementById('carouselTrack');
        let index = 0;

        setInterval(() => {
            index = (index + 1) % 2;
            track.style.transform = `translateX(-${index * 100}%)`;
        }, 5000);

        // --- NOTIFICATIONS LOGIC ---
        const notifSidebar = document.getElementById('notifSidebar');
        const notifOverlay = document.getElementById('notifOverlay');

        function toggleNotifications() {
            notifSidebar.classList.toggle('open');
            notifOverlay.classList.toggle('open');

            if(notifSidebar.classList.contains('open')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        }

        // --- CHAT LOGIC ---
        const chatSidebar = document.getElementById('chatSidebar');
        const chatOverlay = document.getElementById('chatOverlay');

        function toggleChat() {
            chatSidebar.classList.toggle('open');
            chatOverlay.classList.toggle('open');

            if(chatSidebar.classList.contains('open')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        }

        // --- CHAT MODAL LOGIC ---
        const chatModalOverlay = document.getElementById('chatModalOverlay');
        const chatModal = document.getElementById('chatModal');
        const chatArea = document.getElementById('chatArea');

        // Data Dummy Pesan per Seller
        const chatData = {
            'Dimas': [
                { id: 1, type: 'incoming', text: 'Halo kak, apakah barangnya masih ada?', time: '09:00' },
                { id: 2, type: 'outgoing', text: 'Masih gan, silakan diorder ya sebelum kehabisan.', time: '09:05' },
                { id: 3, type: 'incoming', text: 'Terima kasih kak, saya akan order sekarang.', time: '09:06' }
            ],
            'Sari': [
                { id: 1, type: 'incoming', text: 'Barangnya bagus sekali!', time: '10:00' },
                { id: 2, type: 'outgoing', text: 'Terima kasih atas ulasannya!', time: '10:05' }
            ],
            'Budi': [
                { id: 1, type: 'incoming', text: 'Bisa nego harga?', time: '11:00' },
                { id: 2, type: 'outgoing', text: 'Maaf, harga sudah fix.', time: '11:05' }
            ]
        };

        let currentChatSeller = '';
        let messages = [];

        function openChatModal(sellerName) {
            currentChatSeller = sellerName;
            messages = chatData[sellerName] ? [...chatData[sellerName]] : [];

            // Update UI
            document.getElementById('chatSellerName').textContent = sellerName;
            document.getElementById('chatSellerAvatar').src = `https://api.dicebear.com/7.x/avataaars/svg?seed=${sellerName}`;

            renderMessages();

            // Show modal
            chatModalOverlay.classList.add('open');
            chatModal.classList.add('open');
            document.body.style.overflow = 'hidden';

            // Close sidebar if open
            toggleChat();
        }

        function closeChatModal() {
            chatModalOverlay.classList.remove('open');
            chatModal.classList.remove('open');
            document.body.style.overflow = 'auto';
        }

        // Render Pesan di Sidebar
        function renderMessagesSidebar() {
            chatMessagesSidebar.innerHTML = '';

            // Tambahkan penanda hari (opsional)
            const dateSeparator = document.createElement('div');
            dateSeparator.style.textAlign = 'center';
            dateSeparator.style.fontSize = '0.75rem';
            dateSeparator.style.color = '#888';
            dateSeparator.style.margin = '10px 0';
            dateSeparator.textContent = 'Hari Ini';
            chatMessagesSidebar.appendChild(dateSeparator);

            messages.forEach(msg => {
                const wrapper = document.createElement('div');
                wrapper.className = `message-wrapper ${msg.type}`;

                // Tombol Aksi (Lapor atau Hapus) dibuat secara kondisional
                let actionButton = '';
                if (msg.type === 'incoming') {
                    // Pesan orang lain: bisa dilaporkan
                    actionButton = `
                        <div class="message-actions">
                            <button class="action-icon-btn report" title="Laporkan Chat Ini" onclick="reportMessage(${msg.id})">
                                <i class="fas fa-exclamation-triangle"></i>
                            </button>
                        </div>
                    `;
                } else if (msg.type === 'outgoing') {
                    // Pesan sendiri: bisa dihapus
                    actionButton = `
                        <div class="message-actions">
                            <button class="action-icon-btn delete" title="Batal Kirim / Hapus" onclick="deleteMessage(${msg.id})">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
                }

                wrapper.innerHTML = `
                    <div class="message-bubble" onclick="toggleMessageActions(this)">
                        ${msg.text}
                    </div>
                    <span class="message-time">${msg.time}</span>
                    ${actionButton}
                `;

                chatMessagesSidebar.appendChild(wrapper);
            });

            scrollToBottomSidebar();
        }

        // Kirim Pesan
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const text = input.value.trim();

            if (text) {
                const now = new Date();
                const timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

                const newMsg = {
                    id: Date.now(),
                    type: 'outgoing',
                    text: text,
                    time: timeString
                };

                messages.push(newMsg);
                renderMessages();
                input.value = '';

                // Simulasi Balasan Otomatis
                setTimeout(() => {
                    const replyMsg = {
                        id: Date.now() + 1,
                        type: 'incoming',
                        text: 'Terima kasih pesannya, kami akan segera membalas.',
                        time: timeString
                    };
                    messages.push(replyMsg);
                    renderMessages();
                }, 1500);
            }
        }

        function handleEnter(e) {
            if (e.key === 'Enter') sendMessage();
        }

        // Hapus Pesan (Outgoing)
        function deleteMessage(id) {
            if (confirm('Batalkan kirim pesan ini? (Hapus untuk saya)')) {
                messages = messages.filter(m => m.id !== id);
                renderMessagesSidebar();
            }
        }

        // Laporkan Pesan (Incoming)
        function reportMessage(id) {
            if (confirm('Laporkan pesan ini sebagai spam atau penipuan?')) {
                alert('Laporan diterima. Tim Ecoswap akan meninjau percakapan ini.');
            }
        }

        function scrollToBottom() {
            chatArea.scrollTop = chatArea.scrollHeight;
        }

        // --- SIDEBAR CHAT FUNCTIONS ---
        function selectChat(sellerName) {
            currentChatSeller = sellerName;
            messages = chatData[sellerName] ? [...chatData[sellerName]] : [];

            // Update UI
            document.getElementById('chatSellerNameSidebar').textContent = sellerName;
            document.getElementById('chatSellerAvatarSidebar').src = `https://api.dicebear.com/7.x/avataaars/svg?seed=${sellerName}`;

            renderMessagesSidebar();

            // Show chat area
            document.getElementById('chatItemsContainer').style.display = 'none';
            document.getElementById('chatAreaSidebar').style.display = 'flex';
        }

        function backToChatList() {
            document.getElementById('chatItemsContainer').style.display = 'block';
            document.getElementById('chatAreaSidebar').style.display = 'none';
        }

        // Kirim Pesan di Sidebar
        function sendMessageSidebar() {
            const input = document.getElementById('messageInputSidebar');
            const text = input.value.trim();

            if (text) {
                const now = new Date();
                const timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

                const newMsg = {
                    id: Date.now(),
                    type: 'outgoing',
                    text: text,
                    time: timeString
                };

                messages.push(newMsg);
                renderMessagesSidebar();
                input.value = '';

                // Simulasi Balasan Otomatis
                setTimeout(() => {
                    const replyMsg = {
                        id: Date.now() + 1,
                        type: 'incoming',
                        text: 'Terima kasih pesannya, kami akan segera membalas.',
                        time: timeString
                    };
                    messages.push(replyMsg);
                    renderMessagesSidebar();
                }, 1500);
            }
        }

        function handleEnterSidebar(e) {
            if (e.key === 'Enter') sendMessageSidebar();
        }

        function openFileInput() {
            document.getElementById('fileInput').click();
        }

        function sendImage() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];

            if (file) {
                const now = new Date();
                const timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

                const newMsg = {
                    id: Date.now(),
                    type: 'outgoing',
                    text: '[Gambar dikirim]',
                    time: timeString
                };

                messages.push(newMsg);
                renderMessagesSidebar();
                fileInput.value = '';
            }
        }

        function scrollToBottomSidebar() {
            const chatMessagesSidebar = document.getElementById('chatMessagesSidebar');
            chatMessagesSidebar.scrollTop = chatMessagesSidebar.scrollHeight;
        }

        // Fungsi untuk menampilkan/menyembunyikan menu aksi pada pesan
        function toggleMessageActions(bubbleElement) {
            const wrapper = bubbleElement.closest('.message-wrapper');
            if (!wrapper) return;

            // Tutup semua menu aksi lain yang mungkin terbuka
            document.querySelectorAll('.message-wrapper.actions-visible').forEach(openWrapper => {
                if (openWrapper !== wrapper) {
                    openWrapper.classList.remove('actions-visible');
                }
            });

            // Toggle (buka/tutup) menu aksi untuk pesan yang diklik
            wrapper.classList.toggle('actions-visible');
        }

    </script>
</body>
</html>