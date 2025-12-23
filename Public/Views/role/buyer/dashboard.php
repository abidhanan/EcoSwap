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

// 1. AMBIL DATA USER (Termasuk Foto Profil)
$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id = '$user_id'");
$d_user = mysqli_fetch_assoc($q_user);

// Nama user (prioritas nama asli, fallback ke email)
$user_name = !empty($d_user['name']) ? $d_user['name'] : explode('@', $d_user['email'])[0];

// LOGIKA FOTO PROFIL NAV BAR
if (!empty($d_user['profile_picture'])) {
    // Gunakan foto dari database
    $user_avatar = $d_user['profile_picture'];
} else {
    // Gunakan avatar default jika belum upload
    $user_avatar = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($user_name);
}

// 2. AMBIL PRODUK (FEED)
$all_products = [];
$query_prod = mysqli_query($koneksi, "SELECT p.*, s.shop_name, a.full_address 
                                 FROM products p 
                                 JOIN shops s ON p.shop_id = s.shop_id 
                                 LEFT JOIN addresses a ON s.user_id = a.user_id AND a.is_primary = 1
                                 WHERE p.status = 'active' 
                                 ORDER BY p.created_at DESC");
while($row = mysqli_fetch_assoc($query_prod)) {
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

// 3. AMBIL KERANJANG (CART)
$cart_items = [];
$cart_total = 0;
$q_cart = mysqli_query($koneksi, "SELECT c.cart_id, p.product_id, p.name, p.price, p.image 
                                  FROM cart c 
                                  JOIN products p ON c.product_id = p.product_id 
                                  WHERE c.user_id = '$user_id' ORDER BY c.created_at DESC");
while($row = mysqli_fetch_assoc($q_cart)){
    $cart_items[] = $row;
    $cart_total += $row['price'];
}
$cart_count = count($cart_items);

// 4. AMBIL NOTIFIKASI
$notif_items = [];
$q_notif = mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 10");
while($row = mysqli_fetch_assoc($q_notif)){
    $notif_items[] = $row;
}
$notif_count = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id='$user_id' AND is_read=0"));

// 5. AMBIL ALAMAT (UNTUK CHECKOUT)
$addresses = [];
$q_addr = mysqli_query($koneksi, "SELECT * FROM addresses WHERE user_id = '$user_id' ORDER BY is_primary DESC");
while($row = mysqli_fetch_assoc($q_addr)){
    $addresses[] = $row;
}
// Set alamat default tampilan (primary atau index 0)
$default_addr = !empty($addresses) ? $addresses[0] : null;

// 6. LOGIKA CHAT (Grouping Pesan)
// Kita perlu mengambil daftar orang yang pernah chat dengan user ini
$chat_partners = [];
$chat_messages_grouped = [];

// Query ambil pesan masuk dan keluar
$q_chat = mysqli_query($koneksi, "
    SELECT c.*, 
           sender.email as sender_name, 
           sender.name as sender_real_name,
           receiver.email as receiver_name,
           receiver.name as receiver_real_name
    FROM chats c
    JOIN users sender ON c.sender_id = sender.user_id
    JOIN users receiver ON c.receiver_id = receiver.user_id
    WHERE c.sender_id = '$user_id' OR c.receiver_id = '$user_id'
    ORDER BY c.created_at ASC
");

while($row = mysqli_fetch_assoc($q_chat)){
    // Tentukan siapa lawannya
    if($row['sender_id'] == $user_id){
        $partner_id = $row['receiver_id'];
        // Prioritaskan nama asli, jika tidak ada pakai email
        $partner_display = !empty($row['receiver_real_name']) ? $row['receiver_real_name'] : explode('@', $row['receiver_name'])[0];
        $type = 'outgoing';
    } else {
        $partner_id = $row['sender_id'];
        $partner_display = !empty($row['sender_real_name']) ? $row['sender_real_name'] : explode('@', $row['sender_name'])[0];
        $type = 'incoming';
    }

    // Masukkan ke grouping messages
    $chat_messages_grouped[$partner_display][] = [
        'id' => $row['chat_id'],
        'type' => $type,
        'text' => $row['message'],
        'time' => date('H:i', strtotime($row['created_at']))
    ];

    // Masukkan ke list sidebar (unique)
    if(!isset($chat_partners[$partner_id])) {
        $chat_partners[$partner_id] = [
            'name' => $partner_display,
            'last_msg' => $row['message'],
            'time' => date('H:i', strtotime($row['created_at']))
        ];
    } else {
        // Update pesan terakhir
        $chat_partners[$partner_id]['last_msg'] = $row['message'];
        $chat_partners[$partner_id]['time'] = date('H:i', strtotime($row['created_at']));
    }
}
$chat_badge = 0; // Bisa dihitung dari is_read di database jika mau
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
        /* CSS Logika Chat */
        .message-wrapper { display: flex; align-items: center; gap: 8px; margin-bottom: 15px; position: relative; }
        .message-actions { display: none; background-color: #ffffff; border-radius: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); padding: 2px 8px; gap: 8px; flex-shrink: 0; border: 1px solid #ddd; }
        .message-wrapper.actions-visible .message-actions { display: flex; }
        .message-wrapper.outgoing .message-actions { order: -1; }
        .message-wrapper.incoming .message-actions { order: 1; }
        .action-icon-btn { background: none; border: none; cursor: pointer; font-size: 0.85rem; color: #666; padding: 5px; transition: color 0.2s; }
        .action-icon-btn.report:hover { color: #f39c12; }
        .action-icon-btn.delete:hover { color: #e74c3c; }
        .message-bubble { cursor: pointer; transition: opacity 0.2s; }
        .message-bubble:active { opacity: 0.7; }
        
        /* Tambahan style avatar navbar */
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
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
                <?php if($cart_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </button>
            
            <button class="nav-icon-btn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <?php if($notif_count > 0): ?>
                    <span class="notif-badge"><?php echo $notif_count; ?></span>
                <?php endif; ?>
            </button>
            
            <button class="nav-icon-btn" onclick="toggleChat()">
                <i class="fas fa-comment-dots"></i>
                </button>
            
            <div class="user-avatar" onclick="window.location.href='profil.php'">
                <img src="<?php echo $user_avatar; ?>" alt="User">
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
                        <?php if($default_addr): ?>
                            <div class="addr-name" id="displayAddrName"><?php echo $default_addr['recipient_name']; ?> | <?php echo $default_addr['phone_number']; ?></div>
                            <div class="addr-detail" id="displayAddrDetail"><?php echo $default_addr['full_address']; ?></div>
                        <?php else: ?>
                            <div class="addr-name" id="displayAddrName">Belum ada alamat</div>
                            <div class="addr-detail" id="displayAddrDetail">Klik untuk menambahkan alamat</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="section-card-checkout">
                    <div class="section-title-checkout">Produk Dipesan</div>
                    <div class="product-list-container" id="checkoutProductList"></div>
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
            <?php foreach($addresses as $addr): ?>
            <div class="address-option <?php echo ($addr['is_primary']) ? 'selected' : ''; ?>" 
                 onclick="selectAddress(this, '<?php echo $addr['recipient_name']; ?>', '<?php echo $addr['full_address']; ?>', '<?php echo $addr['phone_number']; ?>')">
                <div style="font-weight:bold;"><?php echo $addr['label']; ?></div>
                <div style="font-size:0.85rem; color:#666;"><?php echo $addr['recipient_name']; ?> | <?php echo $addr['phone_number']; ?></div>
                <div style="font-size:0.85rem;"><?php echo $addr['full_address']; ?></div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($addresses)): ?>
                <div style="padding:15px; text-align:center; color:#666;">
                    Belum ada alamat. <a href="alamat.php" style="color:var(--primary);">Tambah Alamat</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="cart-overlay-bg" id="cartOverlay" onclick="toggleCart()"></div>
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3><i class="fas fa-shopping-bag"></i> Keranjang Saya</h3>
            <button class="close-cart-btn" onclick="toggleCart()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="cart-items" id="cartItemsContainer">
            <?php if(empty($cart_items)): ?>
                <div style="text-align:center; padding:20px; color:#666;">Keranjang kosong</div>
            <?php else: ?>
                <?php foreach($cart_items as $item): ?>
                <div class="cart-item" data-id="<?php echo $item['cart_id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo $item['name']; ?>" data-img="<?php echo $item['image']; ?>">
                    <div class="cart-check-wrapper">
                        <input type="checkbox" class="cart-checkbox" onchange="updateCartTotal()">
                    </div>
                    <img src="<?php echo $item['image']; ?>" class="cart-item-img" alt="Item">
                    <div class="cart-item-info">
                        <div class="cart-item-title"><?php echo $item['name']; ?></div>
                        <div class="cart-item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
            <?php if(empty($notif_items)): ?>
                <div style="text-align:center; padding:20px; color:#666;">Tidak ada notifikasi</div>
            <?php else: ?>
                <?php foreach($notif_items as $notif): ?>
                <div class="notif-item" style="<?php echo ($notif['is_read'] == 0) ? 'background:#f0f8ff;' : ''; ?>">
                    <div class="notif-icon"><i class="fas fa-info-circle"></i></div>
                    <div class="notif-content">
                        <div class="notif-title"><?php echo $notif['title']; ?></div>
                        <div class="notif-message"><?php echo $notif['message']; ?></div>
                        <div class="notif-time"><?php echo date('d M H:i', strtotime($notif['created_at'])); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="chat-overlay-bg" id="chatOverlay" onclick="toggleChat()"></div>
    <div class="chat-sidebar" id="chatSidebar">
        <div class="chat-header">
            <h3><i class="fas fa-comment-dots"></i> Chat</h3>
            <button class="close-chat-btn" onclick="toggleChat()"><i class="fas fa-times"></i></button>
        </div>

        <div class="chat-items" id="chatItemsContainer">
            <?php if(empty($chat_partners)): ?>
                <div style="text-align:center; padding:20px; color:#666;">Belum ada percakapan</div>
            <?php else: ?>
                <?php foreach($chat_partners as $partner_name => $chat_info): ?>
                <div class="chat-item" onclick="selectChat('<?php echo $chat_info['name']; ?>')">
                    <div class="chat-avatar"><img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $chat_info['name']; ?>" alt="User"></div>
                    <div class="chat-content">
                        <div class="chat-name"><?php echo $chat_info['name']; ?></div>
                        <div class="chat-message"><?php echo $chat_info['last_msg']; ?></div>
                        <div class="chat-time"><?php echo $chat_info['time']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
        
        // DATA PRODUK DARI DATABASE (Inject PHP ke JS)
        const products = <?php echo json_encode($all_products); ?>;

        // DATA CHAT DARI DATABASE (Inject PHP ke JS)
        // Format: {'NamaUser': [{msg}, {msg}]}
        const chatData = <?php echo json_encode($chat_messages_grouped); ?>;

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
        
        function selectAddress(element, name, detail, phone) {
            document.querySelectorAll('.address-option').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('displayAddrName').innerText = name + ' | ' + phone;
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

        // Logic Chat Dinamis
        let messages = [];
        let currentChatSeller = '';

        function selectChat(sellerName) {
            currentChatSeller = sellerName;
            // Ambil pesan dari variabel PHP yang sudah diinject
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

        function renderMessagesSidebar() {
            const chatMessagesSidebar = document.getElementById('chatMessagesSidebar');
            chatMessagesSidebar.innerHTML = '';
            
            if(messages.length === 0){
                chatMessagesSidebar.innerHTML = '<div style="text-align:center; padding:20px; color:#888;">Belum ada pesan.</div>';
                return;
            }

            messages.forEach((msg, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = `message-wrapper ${msg.type}`;
                wrapper.id = `msg-${index}`;
                
                // Logika Tombol Aksi
                let actionButtons = '';
                if (msg.type === 'outgoing') {
                    actionButtons = `<button class="action-icon-btn delete" onclick="deleteMessage(${index})"><i class="fas fa-trash-alt"></i></button>`;
                } else {
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

        // Toggle munculnya tombol chat
        function toggleMessageActions(index) {
            const allWrappers = document.querySelectorAll('.message-wrapper');
            const target = document.getElementById(`msg-${index}`);
            allWrappers.forEach(w => {
                if (w !== target) w.classList.remove('actions-visible');
            });
            target.classList.toggle('actions-visible');
        }

        function deleteMessage(index) {
            if (confirm("Hapus pesan ini?")) {
                messages.splice(index, 1); 
                renderMessagesSidebar();   
            }
        }

        function reportMessage(index) {
            const msgText = messages[index].text;
            alert(`Pesan: "${msgText}" telah dilaporkan ke admin Ecoswap.`);
            document.getElementById(`msg-${index}`).classList.remove('actions-visible');
        }

        function sendMessageSidebar() {
            const input = document.getElementById('messageInputSidebar');
            if(input.value.trim()) {
                // Di sini harusnya AJAX ke database, tapi sementara simulasi UI dulu
                const now = new Date();
                const timeString = now.getHours() + ':' + String(now.getMinutes()).padStart(2, '0');
                
                messages.push({id: Date.now(), type:'outgoing', text: input.value, time: timeString});
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