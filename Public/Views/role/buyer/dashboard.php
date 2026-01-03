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

// ==========================================
// HANDLE AJAX REQUESTS (Follow & Chat)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json'); // Wajib agar tidak error parsing JSON

    // 1. HANDLER FOLLOW
    if ($_POST['action'] == 'toggle_follow') {
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

    // 2. HANDLER KIRIM CHAT (BARU)
    if ($_POST['action'] == 'send_message') {
        $receiver_id = $_POST['receiver_id'];
        $message = mysqli_real_escape_string($koneksi, $_POST['message']);

        if(!empty($message) && !empty($receiver_id)) {
            $insert = mysqli_query($koneksi, "INSERT INTO chats (sender_id, receiver_id, message) VALUES ('$user_id', '$receiver_id', '$message')");
            if($insert) {
                echo json_encode(['status' => 'success', 'time' => date('H:i')]);
            } else {
                echo json_encode(['status' => 'error', 'message' => mysqli_error($koneksi)]);
            }
        } else {
            echo json_encode(['status' => 'empty']);
        }
        exit;
    }
}
// ==========================================

// 1. AMBIL DATA USER
$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id = '$user_id'");
$d_user = mysqli_fetch_assoc($q_user);
$user_name = !empty($d_user['name']) ? $d_user['name'] : explode('@', $d_user['email'])[0];

// FOTO PROFIL
if (!empty($d_user['profile_picture'])) {
    $user_avatar = $d_user['profile_picture'];
} else {
    $user_avatar = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($user_name);
}

// ==========================================
// 2. LOGIKA FILTER KATEGORI & AMBIL PRODUK
// ==========================================
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'Semua';
$where_clause = "WHERE p.status = 'active'";

// Mapping Kategori UI ke Database
if ($category_filter != 'Semua') {
    $safe_cat = mysqli_real_escape_string($koneksi, $category_filter);
    
    if ($safe_cat == 'Fashion') {
        // Fashion mencakup Pria & Wanita
        $where_clause .= " AND (p.category = 'Fashion Pria' OR p.category = 'Fashion Wanita')";
    } elseif ($safe_cat == 'Hobi') {
        // Mapping ke Hobi & Koleksi
        $where_clause .= " AND p.category = 'Hobi & Koleksi'";
    } else {
        // Pencarian Exact (Elektronik, Otomotif, Rumah Tangga, dll)
        $where_clause .= " AND p.category = '$safe_cat'";
    }
}

$all_products = [];
// Modifikasi query: Tambahkan s.shop_address
$query_prod = mysqli_query($koneksi, "SELECT p.*, s.shop_name, s.shop_image, s.shop_id, s.shop_address, a.full_address 
                                 FROM products p 
                                 JOIN shops s ON p.shop_id = s.shop_id 
                                 LEFT JOIN addresses a ON s.user_id = a.user_id AND a.is_primary = 1
                                 $where_clause 
                                 ORDER BY p.created_at DESC");

// ... (Query sebelumnya tetap sama)

while($row = mysqli_fetch_assoc($query_prod)) {
    // Cek status Follow
    $shop_id_prod = $row['shop_id'];
    $is_following = false;
    $q_check = mysqli_query($koneksi, "SELECT 1 FROM shop_followers WHERE shop_id='$shop_id_prod' AND user_id='$user_id'");
    if(mysqli_num_rows($q_check) > 0) $is_following = true;

    // LOGIKA ALAMAT PENDEK
    // 1. Ambil alamat penuh (prioritas toko, fallback alamat user)
    $raw_addr = !empty($row['shop_address']) ? $row['shop_address'] : (isset($row['full_address']) ? explode(',', $row['full_address'])[0] : 'Indonesia');
    
    // 2. Potong jika terlalu panjang (> 35 karakter) agar tampilan rapi
    $short_addr = strlen($raw_addr) > 35 ? substr($raw_addr, 0, 35) . '...' : $raw_addr;

    // Lokasi (Kota/Negara) untuk display card (opsional, biarkan default)
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
        'shop_address' => $short_addr, // Gunakan alamat yang sudah dipendekkan
        'is_following' => $is_following
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

// 5. AMBIL ALAMAT
$addresses = [];
$q_addr = mysqli_query($koneksi, "SELECT * FROM addresses WHERE user_id = '$user_id' ORDER BY is_primary DESC");
while($row = mysqli_fetch_assoc($q_addr)){
    $addresses[] = $row;
}
$default_addr = !empty($addresses) ? $addresses[0] : null;

// 6. LOGIKA CHAT (Updated)
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
        $pid = $row['receiver_id']; // Partner ID (Seller)
        $pname = $row['r_name'];
        $type = 'outgoing';
    } else {
        $pid = $row['sender_id']; // Partner ID (Seller)
        $pname = $row['s_name'];
        $type = 'incoming';
    }

    // Grouping pesan
    $chat_messages_grouped[$pid][] = [ // Key pakai ID, bukan Nama (biar aman)
        'id' => $row['chat_id'],
        'type' => $type,
        'text' => $row['message'],
        'time' => date('H:i', strtotime($row['created_at']))
    ];

    // List Sidebar
    if(!isset($chat_partners[$pid])) {
        $chat_partners[$pid] = [
            'id' => $pid, // Simpan ID
            'name' => $pname,
            'last_msg' => $row['message'],
            'time' => date('H:i', strtotime($row['created_at']))
        ];
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Logika Chat & Avatar */
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
        
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        
        /* --- MODAL DETAIL (LAYOUT SIMETRIS & RAPI) --- */
        .product-modal {
            background: #fff; width: 900px; max-width: 95%; 
            border-radius: 12px; overflow: hidden; display: flex;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); position: relative;
            max-height: 90vh; /* Batasi tinggi agar tidak overflow layar */
        }
        
        .modal-left { 
            flex: 1; background: #f8f9fa; display: flex; 
            align-items: center; justify-content: center; padding: 20px;
        }
        .modal-left img { 
            max-width: 100%; max-height: 400px; object-fit: contain; 
            border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .modal-right { 
            flex: 1; padding: 30px; display: flex; flex-direction: column; 
            overflow-y: auto; /* Scroll jika konten panjang */
        }

        .modal-title { font-size: 1.5rem; font-weight: 700; color: #333; margin-bottom: 5px; line-height: 1.3; }
        .modal-price { font-size: 1.4rem; color: var(--primary); font-weight: 700; margin: 15px 0; }
        
        .modal-meta-row { 
            display: flex; gap: 15px; font-size: 0.9rem; color: #666; margin-bottom: 20px; 
            border-bottom: 1px solid #eee; padding-bottom: 15px; 
        }
        .modal-meta-row span { display: flex; align-items: center; gap: 5px; }

        .modal-category-badge {
            display: inline-block; background: #e0f7fa; color: #006064; 
            padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;
            margin-bottom: 10px; align-self: flex-start;
        }

        .modal-desc { 
            font-size: 0.95rem; line-height: 1.6; color: #555; margin-bottom: 20px; 
            flex-grow: 1; /* Isi ruang kosong */
        }

        /* Container Info Toko (Updated Layout) */
        .modal-shop-container {
            display: flex; align-items: center; justify-content: space-between;
            padding: 15px; background: #fdfdfd; border: 1px solid #eee; 
            border-radius: 10px; margin-bottom: 20px;
        }
        .modal-shop-left { display: flex; align-items: center; gap: 12px; }
        .modal-shop-img { 
            width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd; 
        }
        .modal-shop-details { display: flex; flex-direction: column; justify-content: center; }
        .modal-shop-details h4 { margin: 0; font-size: 1rem; color: #333; font-weight: 700; }
        .modal-shop-details span { font-size: 0.8rem; color: #888; margin-top: 2px; } /* Online status dll */

        /* Tombol Follow (Updated Style) */
        .btn-follow {
            background: #fff; border: 1px solid var(--primary); color: #333;
            padding: 6px 18px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-follow:hover { background: #fffde7; border-color: #fbc02d; }
        .btn-follow.following {
            background: var(--primary); color: #000; border-color: var(--primary);
        }

        /* Action Buttons Bottom */
        .modal-actions { 
            display: grid; grid-template-columns: 1fr 2fr 2fr; gap: 10px; margin-top: auto; 
        }
        .btn { border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 0.95rem; }
        .btn-outline { background: #fff; border: 1px solid #ddd; color: #333; }
        .btn-outline:hover { border-color: #aaa; background: #f9f9f9; }
        .btn-dark { background: #333; color: #fff; }
        .btn-dark:hover { background: #000; }
        .btn-primary { background: var(--primary); color: #000; }
        .btn-primary:hover { opacity: 0.9; }

        
        /* Cursor pointer untuk kategori */
        .category-pill { cursor: pointer; }

        /* --- CSS UPDATE MODAL DETAIL --- */
        .modal-category-badge {
            display: inline-block; background: #e0f2f1; padding: 4px 10px; border-radius: 12px;
            font-size: 0.8rem; color: #00695c; font-weight: 600;
        }

        /* Container Info Toko */
        .modal-shop-container {
            display: flex; align-items: center; justify-content: space-between;
            margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;
        }
        .modal-shop-left { display: flex; align-items: center; gap: 12px; }
        .modal-shop-img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd; }
        .modal-shop-details { display: flex; flex-direction: column; }
        .modal-shop-details h4 { margin: 0; font-size: 1rem; color: #333; font-weight: 700; }
        .modal-shop-details span { font-size: 0.8rem; color: #666; }

        /* Tombol Follow */
        .btn-follow {
            background: transparent; border: 1px solid var(--primary); color: var(--dark);
            padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;
            cursor: pointer; transition: 0.2s;
        }
        .btn-follow:hover { background: #fff8d6; }
        .btn-follow.following {
            background: var(--primary); color: #000; border-color: var(--primary);
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
            <button class="close-modal-btn" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
            
            <div class="modal-left">
                <img id="modalImg" src="" alt="Product Image">
            </div>
            
            <div class="modal-right">
                <h2 class="modal-title" id="modalTitle">Judul Produk</h2>
                <div id="modalCategoryBadge" style="margin-bottom: 10px;"></div> <div class="modal-price" id="modalPrice">Rp 0</div>
                
                <div class="modal-meta-row">
                    <span><i class="fas fa-map-marker-alt"></i> <span id="modalLoc">Lokasi</span></span>
                    <span><i class="fas fa-star"></i> <span id="modalCond">Kondisi</span></span>
                </div>

                <div class="modal-desc" id="modalDesc">Deskripsi...</div>

                <div id="modalShopContainer" class="modal-shop-container">
                    </div>

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
                <?php foreach($chat_partners as $pid => $info): ?>
                <div class="chat-item" onclick="selectChat(<?php echo $pid; ?>, '<?php echo addslashes($info['name']); ?>')">
                    <div class="chat-avatar"><img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $info['name']; ?>" alt="User"></div>
                    <div class="chat-content">
                        <div class="chat-name"><?php echo $info['name']; ?></div>
                        <div class="chat-message"><?php echo $info['last_msg']; ?></div>
                        <div class="chat-time"><?php echo $info['time']; ?></div>
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
        
        // --- DATA DARI PHP ---
        const products = <?php echo json_encode($all_products); ?>;
        // chatData sekarang kuncinya adalah ID User (bukan nama)
        const chatData = <?php echo json_encode($chat_messages_grouped); ?>; 

        // --- FUNGSI FILTER KATEGORI ---
        function filterCategory(category) {
            window.location.href = `dashboard.php?category=${encodeURIComponent(category)}`;
        }

        // --- RENDER PRODUK GRID ---
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

        // --- UPDATE LOGIC MODAL (CHAT & FOLLOW) ---
        const modalOverlay = document.getElementById('productModal');

        function openModal(product) {
            document.getElementById('modalImg').src = product.img;
            document.getElementById('modalTitle').textContent = product.title;
            document.getElementById('modalPrice').textContent = 'Rp ' + product.price.toLocaleString('id-ID');
            document.getElementById('modalDesc').textContent = product.desc;
            
            // Meta Row: Tampilkan Kondisi (Hapus Lokasi agar tidak duplikat)
            const metaRow = document.querySelector('.modal-meta-row');
            if(metaRow) {
                 metaRow.innerHTML = `<span style="color:#555; font-weight:600;">Kondisi: <span id="modalCond" style="font-weight:normal; margin-left:4px; color:#333;">${product.cond}</span></span>`;
            }
            
            // Kategori Badge
            const catContainer = document.getElementById('modalCategoryBadge');
            if(catContainer) {
                catContainer.innerHTML = `<span class="modal-category-badge">${product.category || 'Umum'}</span>`;
            }

            // Shop Info (Foto, Nama, Alamat, Tombol Follow)
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

            // Konfigurasi Tombol Chat (Buka Chat dengan Seller)
            const btnChat = document.querySelector('.modal-actions .btn-outline'); 
            const newBtnChat = btnChat.cloneNode(true); 
            btnChat.parentNode.replaceChild(newBtnChat, btnChat);
            
            newBtnChat.onclick = function() {
                closeModal(); 
                toggleChat();
                // Buka chat dengan Seller ID (product.shop_id)
                // Pastikan selectChat menerima ID dan Nama Toko
                selectChat(product.shop_id, product.shop_name, shopImg); 
            };

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

        // --- AJAX FOLLOW TOKO ---
        function toggleFollow(shopId, btn, shopName) {
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = '...';

            const formData = new FormData();
            formData.append('action', 'toggle_follow');
            formData.append('shop_id', shopId);

            fetch('dashboard.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'followed') {
                    btn.classList.add('following');
                    btn.textContent = 'Mengikuti';
                    alert(`Berhasil mengikuti ${shopName}! Anda akan mendapatkan notifikasi produk baru.`);
                } else if (data.status === 'unfollowed') {
                    btn.classList.remove('following');
                    btn.textContent = '+ Ikuti';
                } else {
                    alert('Gagal memproses permintaan.');
                    btn.textContent = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                btn.textContent = originalText;
            })
            .finally(() => { btn.disabled = false; });
        }

        // --- UPDATE LOGIC CHAT (KIRIM & TAMPIL) ---
        let currentChatPartnerId = null; 
        let messages = [];

        function selectChat(partnerId, partnerName, partnerImage = null) {
            currentChatPartnerId = partnerId; 
            
            // Ambil pesan history (jika ada)
            messages = (chatData && chatData[partnerId]) ? [...chatData[partnerId]] : [];
            
            document.getElementById('chatSellerNameSidebar').textContent = partnerName;
            
            const avatarEl = document.getElementById('chatSellerAvatarSidebar');
            if (partnerImage) {
                avatarEl.src = partnerImage;
            } else {
                avatarEl.src = `https://api.dicebear.com/7.x/avataaars/svg?seed=${partnerName}`;
            }
            
            renderMessagesSidebar();
            
            document.getElementById('chatItemsContainer').style.display = 'none';
            document.getElementById('chatAreaSidebar').style.display = 'flex';
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

        function sendMessageSidebar() {
            const input = document.getElementById('messageInputSidebar');
            const msgText = input.value.trim();
            
            if(msgText && currentChatPartnerId) {
                // Tampilan Optimistik
                const now = new Date();
                const timeString = now.getHours() + ':' + String(now.getMinutes()).padStart(2, '0');
                messages.push({id: Date.now(), type:'outgoing', text: msgText, time: timeString});
                renderMessagesSidebar();
                input.value = '';

                // Kirim ke Database
                const formData = new FormData();
                formData.append('action', 'send_message');
                formData.append('receiver_id', currentChatPartnerId); 
                formData.append('message', msgText);

                fetch('dashboard.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status !== 'success') {
                        alert('Gagal mengirim pesan: ' + data.message);
                    }
                })
                .catch(err => console.error(err));
            } else if (!currentChatPartnerId) {
                alert("Error: ID Penjual tidak ditemukan.");
            }
        }
        
        function handleEnterSidebar(e) { if(e.key === 'Enter') sendMessageSidebar(); }
        function toggleMessageActions(index) {
            const target = document.getElementById(`msg-${index}`);
            target.classList.toggle('actions-visible');
        }
        function deleteMessage(index) {
             if(confirm("Hapus pesan?")) { messages.splice(index, 1); renderMessagesSidebar(); }
        }
        function reportMessage(index) { alert("Pesan dilaporkan."); }

        // --- SIDEBAR TOGGLES (CART, NOTIF, CHAT) ---
        function toggleCart() { 
            document.getElementById('cartSidebar').classList.toggle('open'); 
            document.getElementById('cartOverlay').classList.toggle('open');
            if(document.getElementById('cartSidebar').classList.contains('open')) updateCartTotal();
        }
        function toggleNotifications() { 
            document.getElementById('notifSidebar').classList.toggle('open'); 
            document.getElementById('notifOverlay').classList.toggle('open');
        }
        function toggleChat() { 
            document.getElementById('chatSidebar').classList.toggle('open'); 
            document.getElementById('chatOverlay').classList.toggle('open');
        }
        function backToChatList() {
            document.getElementById('chatItemsContainer').style.display = 'block';
            document.getElementById('chatAreaSidebar').style.display = 'none';
        }

        // --- CART & CHECKOUT ---
        function updateCartTotal() {
            let total = 0;
            document.querySelectorAll('.cart-item').forEach(item => {
                if(item.querySelector('.cart-checkbox').checked) total += parseInt(item.getAttribute('data-price'));
            });
            document.getElementById('cartTotalPrice').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }
        function addToCart() { closeModal(); setTimeout(toggleCart, 300); alert("Barang ditambahkan!"); }
        
        let checkoutProductPriceTotal = 0;
        let checkoutShippingPrice = 0;
        const checkoutServiceFee = 1000;
        let activePaymentCategory = null;

        function checkoutFromCart() {
            const items = document.querySelectorAll('.cart-item');
            let selectedItems = [];
            items.forEach(item => {
                if (item.querySelector('.cart-checkbox').checked) {
                    selectedItems.push({
                        title: item.getAttribute('data-name'),
                        price: parseInt(item.getAttribute('data-price')),
                        img: item.getAttribute('data-img')
                    });
                }
            });
            if (selectedItems.length === 0) { alert("Pilih minimal satu barang."); return; }
            localStorage.setItem('checkoutItems', JSON.stringify(selectedItems));
            toggleCart(); initCheckoutModal();
        }

        function buyNow() {
            const title = document.getElementById('modalTitle').textContent;
            const price = parseInt(document.getElementById('modalPrice').textContent.replace(/\D/g, ''));
            const img = document.getElementById('modalImg').src;
            localStorage.setItem('checkoutItems', JSON.stringify([{title, price, img}]));
            closeModal(); initCheckoutModal();
        }

        function initCheckoutModal() {
            const storedData = localStorage.getItem('checkoutItems');
            const container = document.getElementById('checkoutProductList');
            container.innerHTML = ''; 
            checkoutProductPriceTotal = 0;
            if (storedData) {
                const products = JSON.parse(storedData);
                products.forEach(item => {
                    checkoutProductPriceTotal += item.price;
                    container.innerHTML += `<div class="product-row-checkout"><img src="${item.img}" class="product-img-checkout"><div class="product-details-checkout"><div class="prod-name-checkout">${item.title}</div><div class="prod-price-checkout">Rp ${item.price.toLocaleString('id-ID')}</div></div></div>`;
                });
            }
            calculateCheckoutTotal();
            document.getElementById('checkoutModal').classList.add('open');
        }

        function closeCheckoutModal() { document.getElementById('checkoutModal').classList.remove('open'); }
        
        function calculateCheckoutTotal() {
            const shipSelect = document.getElementById('shippingSelect');
            checkoutShippingPrice = parseInt(shipSelect.value) || 0;
            const total = checkoutProductPriceTotal + checkoutShippingPrice + checkoutServiceFee;
            document.getElementById('summaryProdPrice').innerText = 'Rp ' + checkoutProductPriceTotal.toLocaleString('id-ID');
            document.getElementById('summaryShipPrice').innerText = 'Rp ' + checkoutShippingPrice.toLocaleString('id-ID');
            document.getElementById('summaryTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
            document.getElementById('bottomTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }
        
        function selectPaymentCategory(catId) {
            document.querySelectorAll('.payment-category').forEach(el => {
                el.classList.remove('active');
                el.querySelector('.check-circle').className = 'far fa-circle check-circle';
            });
            const activeEl = document.getElementById('cat-' + catId);
            activeEl.classList.add('active');
            activeEl.querySelector('.check-circle').className = 'fas fa-check-circle check-circle';
            activePaymentCategory = catId;
        }

        function processOrder() {
            if (document.getElementById('shippingSelect').value === "0") { alert("Pilih pengiriman."); return; }
            if (!activePaymentCategory) { alert("Pilih pembayaran."); return; }
            alert("Pesanan berhasil!"); closeCheckoutModal();
        }

        // Address Modal
        function openAddressModal() { document.getElementById('addressModal').classList.add('open'); }
        function closeAddressModal() { document.getElementById('addressModal').classList.remove('open'); }
        function selectAddress(el, name, detail, phone) {
            document.getElementById('displayAddrName').innerText = name + ' | ' + phone;
            document.getElementById('displayAddrDetail').innerText = detail;
            closeAddressModal();
        }
        
        // Carousel
        const track = document.getElementById('carouselTrack');
        let cIndex = 0;
        setInterval(() => { cIndex = (cIndex + 1) % 2; track.style.transform = `translateX(-${cIndex * 100}%)`; }, 5000);

    </script>
</body>
</html>