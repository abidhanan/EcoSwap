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

// --- AJAX HANDLER: FOLLOW TOKO ---
if (isset($_POST['action']) && $_POST['action'] == 'toggle_follow') {
    $target_shop_id = $_POST['shop_id'];
    $check = mysqli_query($koneksi, "SELECT * FROM shop_followers WHERE shop_id='$target_shop_id' AND user_id='$user_id'");
    
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($koneksi, "DELETE FROM shop_followers WHERE shop_id='$target_shop_id' AND user_id='$user_id'");
        echo json_encode(['status' => 'unfollowed']);
    } else {
        mysqli_query($koneksi, "INSERT INTO shop_followers (shop_id, user_id) VALUES ('$target_shop_id', '$user_id')");
        echo json_encode(['status' => 'followed']);
    }
    exit; 
}

// --- AJAX HANDLER: KIRIM PESAN ---
if (isset($_POST['action']) && $_POST['action'] == 'send_message') {
    $receiver_id = $_POST['receiver_id'];
    $message = mysqli_real_escape_string($koneksi, $_POST['message']);

    if(!empty($message) && !empty($receiver_id)) {
        $insert = mysqli_query($koneksi, "INSERT INTO chats (sender_id, receiver_id, message) VALUES ('$user_id', '$receiver_id', '$message')");
        if($insert) echo json_encode(['status' => 'success']);
        else echo json_encode(['status' => 'error', 'message' => mysqli_error($koneksi)]);
    } else {
        echo json_encode(['status' => 'empty']);
    }
    exit;
}

// 1. AMBIL DATA USER
$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id = '$user_id'");
$d_user = mysqli_fetch_assoc($q_user);
$user_name = !empty($d_user['name']) ? $d_user['name'] : explode('@', $d_user['email'])[0];

if (!empty($d_user['profile_picture'])) {
    $user_avatar = $d_user['profile_picture'];
} else {
    $user_avatar = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($user_name);
}

// 2. FILTER & PRODUK
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'Semua';
$where_clause = "WHERE p.status = 'active'";

if ($category_filter != 'Semua') {
    $safe_cat = mysqli_real_escape_string($koneksi, $category_filter);
    if ($safe_cat == 'Fashion') $where_clause .= " AND (p.category = 'Fashion Pria' OR p.category = 'Fashion Wanita')";
    elseif ($safe_cat == 'Hobi') $where_clause .= " AND p.category = 'Hobi & Koleksi'";
    else $where_clause .= " AND p.category = '$safe_cat'";
}

$all_products = [];
$query_prod = mysqli_query($koneksi, "SELECT p.*, s.shop_name, s.shop_image, s.shop_id, s.shop_address, a.full_address 
                                 FROM products p 
                                 JOIN shops s ON p.shop_id = s.shop_id 
                                 LEFT JOIN addresses a ON s.user_id = a.user_id AND a.is_primary = 1
                                 $where_clause 
                                 ORDER BY p.created_at DESC");

while($row = mysqli_fetch_assoc($query_prod)) {
    $shop_id_prod = $row['shop_id'];
    $is_following = false;
    $q_check = mysqli_query($koneksi, "SELECT 1 FROM shop_followers WHERE shop_id='$shop_id_prod' AND user_id='$user_id'");
    if($q_check && mysqli_num_rows($q_check) > 0) $is_following = true;

    $shop_addr = !empty($row['shop_address']) ? $row['shop_address'] : (isset($row['full_address']) ? explode(',', $row['full_address'])[0] : 'Indonesia');
    $short_addr = strlen($shop_addr) > 35 ? substr($shop_addr, 0, 35) . '...' : $shop_addr;
    $loc = !empty($row['full_address']) ? explode(',', $row['full_address'])[0] : 'Indonesia';
    
    $all_products[] = [
        'id' => $row['product_id'],
        'title' => $row['name'],
        'price' => (int)$row['price'],
        'loc' => $loc, 
        'img' => $row['image'], 
        'cond' => $row['condition'],
        'desc' => $row['description'],
        'category' => $row['category'],
        'shop_name' => $row['shop_name'],
        'shop_img' => $row['shop_image'],
        'shop_id' => $row['shop_id'],
        'shop_address' => $short_addr,
        'is_following' => $is_following
    ];
}

// 3. KERANJANG
$cart_items = [];
$cart_total = 0;
$q_cart = mysqli_query($koneksi, "SELECT c.cart_id, p.product_id, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = '$user_id' ORDER BY c.created_at DESC");
while($row = mysqli_fetch_assoc($q_cart)){
    $cart_items[] = $row;
    $cart_total += $row['price'];
}
$cart_count = count($cart_items);

// 4. NOTIFIKASI
$notif_items = [];
$q_notif = mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 10");
while($row = mysqli_fetch_assoc($q_notif)){
    $notif_items[] = $row;
}
$notif_count = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id='$user_id' AND is_read=0"));

// 5. ALAMAT
$addresses = [];
$q_addr = mysqli_query($koneksi, "SELECT * FROM addresses WHERE user_id = '$user_id' ORDER BY is_primary DESC");
while($row = mysqli_fetch_assoc($q_addr)){
    $addresses[] = $row;
}
$default_addr = !empty($addresses) ? $addresses[0] : null;

// 6. CHAT
$chat_partners = [];
$chat_messages_grouped = [];
$q_chat = mysqli_query($koneksi, "
    SELECT c.*, 
           sender.user_id as s_id, sender.name as s_name, 
           receiver.user_id as r_id, receiver.name as r_name 
    FROM chats c 
    JOIN users sender ON c.sender_id = sender.user_id 
    JOIN users receiver ON c.receiver_id = receiver.user_id 
    WHERE c.sender_id = '$user_id' OR c.receiver_id = '$user_id' 
    ORDER BY c.created_at ASC
");

while($row = mysqli_fetch_assoc($q_chat)){
    if($row['sender_id'] == $user_id){
        $pid = $row['receiver_id'];
        $pname = $row['r_name'];
        $type = 'outgoing';
    } else {
        $pid = $row['sender_id'];
        $pname = $row['s_name'];
        $type = 'incoming';
    }
    $chat_messages_grouped[$pid][] = ['id'=>$row['chat_id'], 'type'=>$type, 'text'=>$row['message'], 'time'=>date('H:i', strtotime($row['created_at']))];
    
    if(!isset($chat_partners[$pid])) {
        $chat_partners[$pid] = ['id'=>$pid, 'name'=>$pname, 'last_msg'=>$row['message'], 'time'=>date('H:i', strtotime($row['created_at']))];
    } else {
        $chat_partners[$pid]['last_msg'] = $row['message'];
        $chat_partners[$pid]['time'] = date('H:i', strtotime($row['created_at']));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/dashboard.css">
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/keranjang.css">
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/notifikasi.css">
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/chat.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar">
        <div class="nav-left">
            <div class="logo" onclick="goToDashboard()" style="cursor:pointer;">ECO<span>SWAP</span></div>
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Cari barang bekas berkualitas...">
                <i class="fas fa-search search-icon"></i>
            </div>
        </div>
        <div class="nav-right">
            <button class="nav-icon-btn" onclick="toggleCart()">
                <i class="fas fa-shopping-cart"></i>
                <?php if($cart_count > 0): ?><span class="cart-badge"><?php echo $cart_count; ?></span><?php endif; ?>
            </button>
            <button class="nav-icon-btn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <?php if($notif_count > 0): ?><span class="notif-badge"><?php echo $notif_count; ?></span><?php endif; ?>
            </button>
            <button class="nav-icon-btn" onclick="toggleChat()"><i class="fas fa-comment-dots"></i></button>
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
                    <div class="hero-text"><h1>Barang Bekas <br><span>Berkualitas</span></h1><p>Hemat uang dan selamatkan bumi.</p></div>
                </div>
                <div class="carousel-slide">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&q=80&w=1200" alt="Slide 2">
                    <div class="hero-text"><h1>Elektronik <br><span>Murah</span></h1><p>Upgrade gadget tanpa bikin kantong bolong.</p></div>
                </div>
            </div>
        </div>

        <div class="section-header"><h2 class="section-title">Kategori Pilihan</h2></div>
        <div class="category-pills">
            <?php 
                $categories = ['Semua', 'Elektronik', 'Fashion', 'Hobi', 'Rumah Tangga', 'Buku', 'Otomotif'];
                foreach($categories as $cat) {
                    $isActive = ($category_filter == $cat) ? 'active' : '';
                    echo '<div class="category-pill '.$isActive.'" onclick="filterCategory(\''.$cat.'\')">'.$cat.'</div>';
                }
            ?>
        </div>

        <div class="product-grid" id="productGrid">
            <?php if(empty($all_products)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #888;">
                    <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                    Tidak ada produk di kategori <strong><?php echo htmlspecialchars($category_filter); ?></strong>.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal-overlay" id="productModal">
        <div class="product-modal">
            <button class="close-modal-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
            <div class="modal-left"><img id="modalImg" src="" alt="Product"></div>
            <div class="modal-right">
                <h2 class="modal-title" id="modalTitle">Judul</h2>
                <div id="modalCategoryBadge" style="margin-bottom: 10px;"></div>
                <div class="modal-price" id="modalPrice">Rp 0</div>
                <div class="modal-meta-row"></div>
                <div class="modal-desc" id="modalDesc">Deskripsi...</div>
                <div id="modalShopContainer" class="modal-shop-container"></div>
                <div class="modal-actions">
                    <button class="btn btn-outline" id="btnModalChat"><i class="fas fa-comment"></i> Chat</button>
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
                <button class="close-modal-btn" onclick="closeCheckoutModal()" style="position:static;width:30px;height:30px;"><i class="fas fa-times"></i></button>
            </div>
            <div class="checkout-body-modal">
                <div class="section-card-checkout">
                    <div class="section-title-checkout"><span><i class="fas fa-map-marker-alt" style="color:var(--danger)"></i> Alamat Pengiriman</span></div>
                    <div class="address-box" onclick="openAddressModal()">
                        <div class="addr-change">Ubah</div>
                        <?php if($default_addr): ?>
                            <div class="addr-name" id="displayAddrName"><?php echo $default_addr['recipient_name']; ?> | <?php echo $default_addr['phone_number']; ?></div>
                            <div class="addr-detail" id="displayAddrDetail"><?php echo $default_addr['full_address']; ?></div>
                        <?php else: ?>
                            <div class="addr-name" id="displayAddrName">Belum ada alamat</div>
                            <div class="addr-detail" id="displayAddrDetail">Klik untuk menambahkan</div>
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
                    </select>
                </div>
                <div class="section-card-checkout">
                    <div class="section-title-checkout">Metode Pembayaran</div>
                    <div class="payment-category" id="cat-bank">
                        <div class="payment-header" onclick="selectPaymentCategory('bank')">
                            <div class="ph-left"><i class="far fa-circle check-circle" id="check-bank"></i><span class="ph-title"><i class="fas fa-university"></i> Transfer Bank <span id="selected-bank-text" style="color:#666;font-weight:normal;margin-left:5px;font-size:0.9rem;"></span></span></div>
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
                            <div class="ph-left"><i class="far fa-circle check-circle" id="check-ewallet"></i><span class="ph-title"><i class="fas fa-wallet"></i> E-Wallet <span id="selected-ewallet-text" style="color:#666;font-weight:normal;margin-left:5px;font-size:0.9rem;"></span></span></div>
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
                    <div class="summary-row"><span>Subtotal</span><span class="price-val" id="summaryProdPrice">Rp 0</span></div>
                    <div class="summary-row"><span>Pengiriman</span><span class="price-val" id="summaryShipPrice">Rp 0</span></div>
                    <div class="summary-row"><span>Biaya Layanan</span><span class="price-val">Rp 1.000</span></div>
                    <div class="summary-row total"><span>Total</span><span class="price-val" id="summaryTotal" style="color:var(--primary)">Rp 0</span></div>
                </div>
            </div>
            <div class="checkout-footer-modal">
                <div class="total-display"><div class="total-label">Total Tagihan</div><div class="total-final" id="bottomTotal">Rp 0</div></div>
                <button class="btn-order" onclick="processOrder()">Buat Pesanan</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="addressModal" style="z-index: 1300;">
        <div class="modal-box-address">
            <div class="modal-header-address"><span>Pilih Alamat</span><i class="fas fa-times" onclick="closeAddressModal()" style="cursor:pointer;"></i></div>
            <?php foreach($addresses as $addr): ?>
            <div class="address-option <?php echo ($addr['is_primary']) ? 'selected' : ''; ?>" onclick="selectAddress(this, '<?php echo $addr['recipient_name']; ?>', '<?php echo $addr['full_address']; ?>', '<?php echo $addr['phone_number']; ?>')">
                <div style="font-weight:bold;"><?php echo $addr['label']; ?></div>
                <div style="font-size:0.85rem;"><?php echo $addr['full_address']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'keranjang.php'; ?>
    <?php include 'notifikasi.php'; ?>
    <?php include 'chat.php'; ?>

    <script>
        const goToDashboard = () => window.location.href = 'dashboard.php';
        const products = <?php echo json_encode($all_products); ?>;
        const chatData = <?php echo json_encode($chat_messages_grouped); ?>;

        function filterCategory(cat) { window.location.href = `dashboard.php?category=${encodeURIComponent(cat)}`; }

        // Render Produk
        const productGrid = document.getElementById('productGrid');
        products.forEach(p => {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.onclick = () => openModal(p);
            card.innerHTML = `
                <div class="product-img-wrapper"><img src="${p.img}" alt="${p.title}"></div>
                <div class="product-info">
                    <div class="product-title">${p.title}</div>
                    <div class="product-price">Rp ${p.price.toLocaleString('id-ID')}</div>
                    <div class="product-meta"><i class="fas fa-map-marker-alt"></i> ${p.loc}</div>
                </div>`;
            productGrid.appendChild(card);
        });

        // MODAL DETAIL
        const modalOverlay = document.getElementById('productModal');
        
        function openModal(product) {
            document.getElementById('modalImg').src = product.img;
            document.getElementById('modalTitle').textContent = product.title;
            document.getElementById('modalPrice').textContent = 'Rp ' + product.price.toLocaleString('id-ID');
            document.getElementById('modalDesc').textContent = product.desc;
            
            const metaRow = document.querySelector('.modal-meta-row');
            if(metaRow) metaRow.innerHTML = `<span><i class="fas fa-star" style="color:orange"></i> <span id="modalCond">${product.cond}</span></span>`;
            
            const catContainer = document.getElementById('modalCategoryBadge');
            if(catContainer) catContainer.innerHTML = `<span class="modal-category-badge">${product.category || 'Umum'}</span>`;

            const shopContainer = document.getElementById('modalShopContainer');
            const followText = product.is_following ? 'Mengikuti' : '+ Ikuti';
            const followClass = product.is_following ? 'btn-follow following' : 'btn-follow';
            const shopImg = product.shop_img ? product.shop_img : 'https://placehold.co/50';

            shopContainer.innerHTML = `
                <div class="modal-shop-left">
                    <img src="${shopImg}" class="modal-shop-img" alt="Toko">
                    <div class="modal-shop-details">
                        <h4>${product.shop_name}</h4>
                        <span style="font-size:0.8rem; color:#666; display:flex; align-items:center; gap:4px;">
                            <i class="fas fa-map-marker-alt" style="color:#fbc02d;"></i> ${product.shop_address || 'Indonesia'}
                        </span>
                    </div>
                </div>
                <button class="${followClass}" onclick="toggleFollow(${product.shop_id}, this, '${product.shop_name}')">
                    ${followText}
                </button>
            `;

            const btnChat = document.getElementById('btnModalChat'); 
            const newBtnChat = btnChat.cloneNode(true); 
            btnChat.parentNode.replaceChild(newBtnChat, btnChat);
            newBtnChat.onclick = function() {
                closeModal(); toggleChat();
                selectChat(product.shop_id, product.shop_name, shopImg); 
            };

            modalOverlay.classList.add('open');
            document.body.style.overflow = 'hidden'; 
        }

        function closeModal() { modalOverlay.classList.remove('open'); document.body.style.overflow = 'auto'; }
        modalOverlay.addEventListener('click', (e) => { if (e.target === modalOverlay) closeModal(); });

        function toggleFollow(shopId, btn, shopName) {
            btn.disabled = true; const originalText = btn.textContent; btn.textContent = '...';
            const formData = new FormData();
            formData.append('action', 'toggle_follow'); formData.append('shop_id', shopId);

            fetch('dashboard.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'followed') {
                    btn.classList.add('following'); btn.textContent = 'Mengikuti';
                    alert(`Berhasil mengikuti ${shopName}! Anda akan mendapatkan notifikasi produk baru.`);
                } else if (data.status === 'unfollowed') {
                    btn.classList.remove('following'); btn.textContent = '+ Ikuti';
                }
            }).catch(err => { console.error(err); btn.textContent = originalText; })
            .finally(() => { btn.disabled = false; });
        }

        // CHAT LOGIC
        let currentChatPartnerId = null; let messages = [];
        function selectChat(partnerId, partnerName, partnerImage = null) {
            currentChatPartnerId = partnerId; 
            messages = (chatData && chatData[partnerId]) ? [...chatData[partnerId]] : [];
            document.getElementById('chatSellerNameSidebar').textContent = partnerName;
            const avatarEl = document.getElementById('chatSellerAvatarSidebar');
            if (partnerImage) avatarEl.src = partnerImage;
            else avatarEl.src = `https://api.dicebear.com/7.x/avataaars/svg?seed=${partnerName}`;
            renderMessagesSidebar();
            document.getElementById('chatItemsContainer').style.display = 'none';
            document.getElementById('chatAreaSidebar').style.display = 'flex';
        }
        function renderMessagesSidebar() {
            const container = document.getElementById('chatMessagesSidebar'); container.innerHTML = '';
            if(messages.length === 0){ container.innerHTML = '<div style="text-align:center; padding:20px; color:#888;">Belum ada pesan.</div>'; return; }
            messages.forEach((msg, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = `message-wrapper ${msg.type}`;
                wrapper.innerHTML = `<div class="message-bubble">${msg.text}</div><span class="message-time">${msg.time}</span>`;
                container.appendChild(wrapper);
            });
            container.scrollTop = container.scrollHeight;
        }
        function sendMessageSidebar() {
            const input = document.getElementById('messageInputSidebar'); const msgText = input.value.trim();
            if(msgText && currentChatPartnerId) {
                messages.push({id: Date.now(), type:'outgoing', text: msgText, time: 'Now'});
                renderMessagesSidebar(); input.value = '';
                const formData = new FormData();
                formData.append('action', 'send_message'); formData.append('receiver_id', currentChatPartnerId); formData.append('message', msgText);
                fetch('dashboard.php', { method: 'POST', body: formData });
            }
        }

        // Common
        const track = document.getElementById('carouselTrack'); let i = 0;
        setInterval(() => { i = (i + 1) % 2; track.style.transform = `translateX(-${i * 100}%)`; }, 5000);
        function toggleCart() { document.getElementById('cartSidebar').classList.toggle('open'); document.getElementById('cartOverlay').classList.toggle('open'); updateCartTotal(); }
        function toggleNotifications() { document.getElementById('notifSidebar').classList.toggle('open'); document.getElementById('notifOverlay').classList.toggle('open'); }
        function toggleChat() { document.getElementById('chatSidebar').classList.toggle('open'); document.getElementById('chatOverlay').classList.toggle('open'); }
        function backToChatList() { document.getElementById('chatItemsContainer').style.display = 'block'; document.getElementById('chatAreaSidebar').style.display = 'none'; }
        
        // Checkout
        let checkoutProductPriceTotal = 0, checkoutShippingPrice = 0;
        function updateCartTotal() { let t=0; document.querySelectorAll('.cart-item').forEach(i=>{if(i.querySelector('.cart-checkbox').checked)t+=parseInt(i.getAttribute('data-price'))}); document.getElementById('cartTotalPrice').innerText='Rp '+t.toLocaleString('id-ID'); }
        function addToCart() { closeModal(); setTimeout(toggleCart, 300); alert("Barang ditambahkan!"); }
        function checkoutFromCart() { toggleCart(); initCheckoutModal(); }
        function buyNow() { closeModal(); initCheckoutModal(); }
        function initCheckoutModal() { document.getElementById('checkoutModal').classList.add('open'); calculateCheckoutTotal(); }
        function closeCheckoutModal() { document.getElementById('checkoutModal').classList.remove('open'); }
        function openAddressModal() { document.getElementById('addressModal').classList.add('open'); }
        function closeAddressModal() { document.getElementById('addressModal').classList.remove('open'); }
        function calculateCheckoutTotal() { 
            const ship = parseInt(document.getElementById('shippingSelect').value)||0; 
            document.getElementById('summaryShipPrice').innerText = 'Rp '+ship.toLocaleString(); 
        }
        function selectPaymentCategory(catId) {
             document.querySelectorAll('.payment-category').forEach(el => { el.classList.remove('active'); el.querySelector('.check-circle').className = 'far fa-circle check-circle'; });
             const activeEl = document.getElementById('cat-' + catId); activeEl.classList.add('active'); activeEl.querySelector('.check-circle').className = 'fas fa-check-circle check-circle';
        }
        function togglePaymentDropdown(e, id) { e.stopPropagation(); document.getElementById(id).classList.toggle('show'); }
        function selectPaymentSubOption(cat, val, name) { selectPaymentCategory(cat); document.getElementById('list-'+cat).classList.remove('show'); }
        function processOrder() { alert('Pesanan berhasil!'); closeCheckoutModal(); }
    </script>
</body>
</html>