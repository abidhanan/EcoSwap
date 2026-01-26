<?php
session_start();
include '../../../Auth/koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) { header("Location: ../../auth/login.php"); exit(); }
$user_id = $_SESSION['user_id'];

// --- AMBIL BIAYA ADMIN ---
$q_fee = mysqli_query($koneksi, "SELECT setting_value FROM system_settings WHERE setting_key = 'admin_fee'");
$d_fee = mysqli_fetch_assoc($q_fee);
$admin_fee = isset($d_fee['setting_value']) ? (int)$d_fee['setting_value'] : 1000;

// --- AJAX: DELETE CHAT ---
if (isset($_POST['action']) && $_POST['action'] == 'delete_chat') {
    header('Content-Type: application/json');
    $chat_id = (int)$_POST['chat_id'];

    // Pastikan hanya bisa hapus pesan sendiri
    $del = mysqli_query($koneksi, "DELETE FROM chats WHERE chat_id='$chat_id' AND sender_id='$user_id'");
    if($del) echo json_encode(['status'=>'success']);
    else echo json_encode(['status'=>'error']);
    exit;
}

// --- AJAX: REPORT CHAT ---
if (isset($_POST['action']) && $_POST['action'] == 'report_chat') {
    header('Content-Type: application/json');
    $chat_id = (int)$_POST['chat_id'];
    $reason = mysqli_real_escape_string($koneksi, $_POST['reason']);

    // Ambil data chat untuk detail laporan
    $q_c = mysqli_query($koneksi, "SELECT * FROM chats WHERE chat_id='$chat_id'");
    $d_c = mysqli_fetch_assoc($q_c);

    if(!$d_c){
        echo json_encode(['status'=>'error']);
        exit;
    }

    $reported_user = $d_c['sender_id'];
    $msg_content = $d_c['message'];

    // Cari ID Admin (Ambil admin pertama yang dibuat)
    $q_adm = mysqli_query($koneksi, "SELECT user_id FROM users WHERE role='admin' LIMIT 1");
    $d_adm = mysqli_fetch_assoc($q_adm);
    $admin_id = $d_adm ? $d_adm['user_id'] : 0;

    // Kirim Notifikasi ke Admin sebagai Laporan
    if($admin_id){
        $report_msg = "LAPORAN CHAT dari User ID $user_id. Terlapor: ID $reported_user. Pesan: '$msg_content'. Alasan: $reason";
        mysqli_query($koneksi, "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ('$admin_id', 'Laporan Chat Kasar/Penipuan', '$report_msg', 0, NOW())");
    }

    echo json_encode(['status'=>'success']);
    exit;
}

// --- AJAX HANDLER: CREATE ORDER ---
if (isset($_POST['action']) && $_POST['action'] == 'create_order') {
    header('Content-Type: application/json');

    $address_id = $_POST['address_id'];
    $shipping_method = $_POST['shipping_method'];
    $shipping_cost = $_POST['shipping_cost'];
    $payment_method = $_POST['payment_method'];
    $items = json_decode($_POST['items'], true);

    $q_addr = mysqli_query($koneksi, "SELECT * FROM addresses WHERE address_id='$address_id'");
    $d_addr = mysqli_fetch_assoc($q_addr);
    if(!$d_addr) { echo json_encode(['status' => 'error', 'message' => 'Alamat tidak ditemukan.']); exit; }

    $full_address_snapshot = $d_addr['full_address'] . ", " . $d_addr['village'] . ", " . $d_addr['subdistrict'] . ", " . $d_addr['city'] . " " . $d_addr['postal_code'] . " (" . $d_addr['recipient_name'] . " - " . $d_addr['phone_number'] . ")";
    $invoice_code = "INV/" . date('Ymd') . "/" . strtoupper(substr(md5(time() . rand()), 0, 6));
    $success_count = 0;

    foreach ($items as $item) {
        $prod_id = $item['id'];
        $shop_id = $item['shop_id'];
        $price = $item['price'];
        $final_price = $price;

        $shipping_info_str = $shipping_method . " (Rp " . number_format($shipping_cost,0,',','.') . ")";
        $full_shipping_payment_info = $shipping_info_str . " | " . $payment_method;

        $query_order = "INSERT INTO orders (invoice_code, buyer_id, shop_id, product_id, address_id, total_price, shipping_method, shipping_address, status, tracking_number, created_at)
                        VALUES ('$invoice_code', '$user_id', '$shop_id', '$prod_id', '$address_id', '$final_price', '$full_shipping_payment_info', '$full_address_snapshot', 'pending', '', NOW())";

        if (mysqli_query($koneksi, $query_order)) {
            $success_count++;
            mysqli_query($koneksi, "UPDATE products SET status = 'sold' WHERE product_id = '$prod_id'");
            if (isset($item['cart_id'])) {
                $cid = $item['cart_id'];
                mysqli_query($koneksi, "DELETE FROM cart WHERE cart_id='$cid'");
            }

            $q_shop_owner = mysqli_query($koneksi, "SELECT user_id FROM shops WHERE shop_id='$shop_id'");
            $d_shop_owner = mysqli_fetch_assoc($q_shop_owner);
            if($d_shop_owner) {
                $seller_uid = $d_shop_owner['user_id'];
                $notif_msg = "Pesanan baru #$invoice_code masuk.";
                mysqli_query($koneksi, "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ('$seller_uid', 'Pesanan Baru', '$notif_msg', 0, NOW())");
            }
        }
    }

    if ($success_count > 0) echo json_encode(['status' => 'success']);
    else echo json_encode(['status' => 'error']);

    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'mark_read') {
    $nid = $_POST['notif_id'];
    mysqli_query($koneksi, "UPDATE notifications SET is_read=1 WHERE notif_id='$nid'");
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'get_shop_settings') {
    header('Content-Type: application/json');
    $shop_id = $_POST['shop_id'];
    $query = mysqli_query($koneksi, "SELECT shipping_options, payment_methods FROM shops WHERE shop_id='$shop_id'");
    $data = mysqli_fetch_assoc($query);
    $shipping = !empty($data['shipping_options']) ? json_decode($data['shipping_options']) : [];
    $payment = !empty($data['payment_methods']) ? json_decode($data['payment_methods']) : [];
    echo json_encode(['status' => 'success', 'shipping' => $shipping, 'payment' => $payment]);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'toggle_follow') {
    header('Content-Type: application/json');
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

/**
 * ============ SEND MESSAGE (Buyer -> Seller) ============
 * - Support TEXT + IMAGE
 * - Buyer tidak menampilkan outgoing di UI (sesuai request).
 * - Return JSON chat_id/time/image untuk kebutuhan front-end (tanpa refresh).
 */
if (isset($_POST['action']) && $_POST['action'] == 'send_message') {
    header('Content-Type: application/json');

    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $message = isset($_POST['message']) ? mysqli_real_escape_string($koneksi, $_POST['message']) : '';

    // Upload image optional
    $image_path = NULL;
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../../../Assets/img/chats/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (in_array($ext, $allowed)) {
            $file_name = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            }
        }
    }

    if (empty($message) && empty($image_path)) {
        echo json_encode(['status' => 'empty']);
        exit;
    }

    $img_sql = $image_path ? "'" . mysqli_real_escape_string($koneksi, $image_path) . "'" : "NULL";

    if ($receiver_id > 0) {
        $insert = mysqli_query(
            $koneksi,
            "INSERT INTO chats (sender_id, receiver_id, message, image_path, created_at)
             VALUES ('$user_id', '$receiver_id', '$message', $img_sql, NOW())"
        );

        if($insert){
            echo json_encode([
                'status' => 'success',
                'chat_id' => mysqli_insert_id($koneksi),
                'time' => date('H:i'),
                'image' => $image_path
            ]);
        } else {
            echo json_encode(['status' => 'error']);
        }
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'filter_products') {
    header('Content-Type: application/json');
    $category_filter = isset($_GET['category']) ? $_GET['category'] : 'Semua';

    $where_clause = "WHERE p.status = 'active'";
    if ($category_filter != 'Semua') {
        $safe_cat = mysqli_real_escape_string($koneksi, $category_filter);
        if ($safe_cat == 'Fashion') $where_clause .= " AND (p.category = 'Fashion Pria' OR p.category = 'Fashion Wanita')";
        elseif ($safe_cat == 'Hobi') $where_clause .= " AND p.category = 'Hobi & Koleksi'";
        else $where_clause .= " AND p.category = '$safe_cat'";
    }

    $query_prod = mysqli_query($koneksi, "
        SELECT p.*, s.shop_name, s.shop_image, s.shop_id, s.user_id as seller_id, s.shop_city, a.full_address
        FROM products p
        JOIN shops s ON p.shop_id = s.shop_id
        LEFT JOIN addresses a ON s.user_id = a.user_id AND a.is_primary = 1
        $where_clause
        ORDER BY p.created_at DESC
    ");

    $filtered_products = [];
    while($row = mysqli_fetch_assoc($query_prod)) {
        $shop_id_prod = $row['shop_id'];
        $is_following = false;
        $q_check = mysqli_query($koneksi, "SELECT 1 FROM shop_followers WHERE shop_id='$shop_id_prod' AND user_id='$user_id'");
        if($q_check && mysqli_num_rows($q_check) > 0) $is_following = true;

        $city = !empty($row['shop_city']) ? $row['shop_city'] : (isset($row['full_address']) ? trim(explode(',', $row['full_address'])[2] ?? 'Indonesia') : 'Indonesia');
        $city = str_replace(['Kota ', 'Kabupaten '], '', $city);

        $img_db = $row['image'];
        if (strpos($img_db, 'http') !== 0) {
            $img_db = str_replace('../../../', '../../../', $img_db);
        }

        $filtered_products[] = [
            'id' => $row['product_id'],
            'title' => $row['name'],
            'price' => (int)$row['price'],
            'loc' => $city,
            'img' => $img_db,
            'cond' => $row['condition'],
            'desc' => $row['description'],
            'category' => $row['category'],
            'shop_name' => $row['shop_name'],
            'shop_img' => $row['shop_image'],
            'shop_id' => $row['shop_id'],
            'seller_id' => $row['seller_id'],
            'shop_address' => $city,
            'is_following' => $is_following
        ];
    }
    echo json_encode($filtered_products);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'add_to_cart') {
    header('Content-Type: application/json');
    $product_id = $_POST['product_id'];

    $insert = mysqli_query($koneksi, "INSERT INTO cart (user_id, product_id) VALUES ('$user_id', '$product_id')");
    if ($insert) {
        $new_cart_id = mysqli_insert_id($koneksi);
        $q_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM cart WHERE user_id='$user_id'");
        $d_count = mysqli_fetch_assoc($q_count);
        echo json_encode(['status' => 'success', 'cart_id' => $new_cart_id, 'new_count' => $d_count['total']]);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'delete_item') {
    header('Content-Type: application/json');
    $cart_id = $_POST['cart_id'];

    $delete = mysqli_query($koneksi, "DELETE FROM cart WHERE cart_id='$cart_id' AND user_id='$user_id'");
    if ($delete) {
        $q_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM cart WHERE user_id='$user_id'");
        $d_count = mysqli_fetch_assoc($q_count);
        echo json_encode(['status' => 'success', 'new_count' => $d_count['total']]);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'view_product') {
    $pid = mysqli_real_escape_string($koneksi, $_POST['product_id']);
    mysqli_query($koneksi, "UPDATE products SET views = views + 1 WHERE product_id = '$pid'");
    exit();
}


// --- DATA FETCHING UTAMA ---
$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id = '$user_id'");
$d_user = mysqli_fetch_assoc($q_user);

$user_name = !empty($d_user['name']) ? $d_user['name'] : explode('@', $d_user['email'])[0];
$user_avatar = !empty($d_user['profile_picture']) ? $d_user['profile_picture'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($user_name);

$query_prod = mysqli_query($koneksi, "
    SELECT p.*, s.shop_name, s.shop_image, s.shop_id, s.user_id as seller_id, s.shop_city, a.full_address
    FROM products p
    JOIN shops s ON p.shop_id = s.shop_id
    LEFT JOIN addresses a ON s.user_id = a.user_id AND a.is_primary = 1
    WHERE p.status = 'active'
    ORDER BY p.created_at DESC
");

$all_products = [];
while($row = mysqli_fetch_assoc($query_prod)) {
    $shop_id_prod = $row['shop_id'];
    $is_following = false;
    $q_check = mysqli_query($koneksi, "SELECT 1 FROM shop_followers WHERE shop_id='$shop_id_prod' AND user_id='$user_id'");
    if($q_check && mysqli_num_rows($q_check) > 0) $is_following = true;

    $city = !empty($row['shop_city']) ? $row['shop_city'] : (isset($row['full_address']) ? trim(explode(',', $row['full_address'])[2] ?? 'Indonesia') : 'Indonesia');
    $city = str_replace(['Kota ', 'Kabupaten '], '', $city);

    $all_products[] = [
        'id' => $row['product_id'],
        'title' => $row['name'],
        'price' => (int)$row['price'],
        'loc' => $city,
        'img' => $row['image'],
        'cond' => $row['condition'],
        'desc' => $row['description'],
        'category' => $row['category'],
        'shop_name' => $row['shop_name'],
        'shop_img' => $row['shop_image'],
        'shop_id' => $row['shop_id'],
        'seller_id' => $row['seller_id'],
        'shop_address' => $city,
        'is_following' => $is_following
    ];
}

$cart_items = [];
$cart_total = 0;
$q_cart = mysqli_query($koneksi, "
    SELECT c.cart_id, p.product_id, p.name, p.price, p.image, p.shop_id, p.status
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = '$user_id' AND p.status = 'active'
    ORDER BY c.created_at DESC
");
while($row = mysqli_fetch_assoc($q_cart)){ $cart_items[] = $row; $cart_total += $row['price']; }
$cart_count = count($cart_items);

$notif_items = [];
$q_notif = mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 10");
while($row = mysqli_fetch_assoc($q_notif)){ $notif_items[] = $row; }
$notif_count = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id='$user_id' AND is_read=0"));

$addresses = [];
$q_addr = mysqli_query($koneksi, "SELECT * FROM addresses WHERE user_id = '$user_id' ORDER BY is_primary DESC");
while($row = mysqli_fetch_assoc($q_addr)){
    $details = [];
    if(!empty($row['village'])) $details[] = "Kel. " . $row['village'];
    if(!empty($row['subdistrict'])) $details[] = "Kec. " . $row['subdistrict'];
    if(!empty($row['city'])) $details[] = $row['city'];
    if(!empty($row['postal_code'])) $details[] = $row['postal_code'];
    $row['formatted_details'] = implode(", ", $details);
    $addresses[] = $row;
}
$default_addr = !empty($addresses) ? $addresses[0] : null;


// ==========================
// CHAT BUYER (INBOX SAJA)
// Buyer hanya melihat pesan MASUK dari seller.
// Seller ditampilkan sebagai TOKO (shop_name + shop_image) jika ada.
// ==========================
$chat_partners = [];
$chat_messages_grouped = [];

$q_chat = mysqli_query($koneksi, "
    SELECT c.*,
           sender.user_id as s_id,
           sender.name as s_name,
           sender.profile_picture as s_pic,
           sh.shop_name as shop_name,
           sh.shop_image as shop_image
    FROM chats c
    JOIN users sender ON c.sender_id = sender.user_id
    LEFT JOIN shops sh ON sh.user_id = sender.user_id
    WHERE c.receiver_id = '$user_id'
    ORDER BY c.created_at ASC
");

while($row = mysqli_fetch_assoc($q_chat)) {
    $pid = (int)$row['sender_id'];

    $pname = !empty($row['shop_name']) ? $row['shop_name'] : $row['s_name'];
    $ppic  = !empty($row['shop_image']) ? $row['shop_image'] : $row['s_pic'];
    $ppic  = !empty($ppic) ? $ppic : "https://ui-avatars.com/api/?name=".urlencode($pname)."&background=random";

    $chat_messages_grouped[$pid][] = [
        'id'   => (int)$row['chat_id'],
        'type' => 'incoming',
        'text' => $row['message'],
        'img'  => $row['image_path'],
        'time' => date('H:i', strtotime($row['created_at']))
    ];

    $lastPreview = !empty($row['message']) ? $row['message'] : (!empty($row['image_path']) ? '[Foto]' : '');

    if(!isset($chat_partners[$pid])) {
        $chat_partners[$pid] = [
            'id' => $pid,
            'name' => $pname,
            'img' => $ppic,
            'last_msg' => $lastPreview,
            'time' => date('H:i', strtotime($row['created_at']))
        ];
    } else {
        $chat_partners[$pid]['last_msg'] = $lastPreview;
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
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/checkout.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .btn-follow { display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s ease; }
        .btn-follow i { font-size: 0.9rem; }
        .btn-follow.following { background-color: #e0e0e0; color: #333; border: 1px solid #ccc; }
        .btn-follow.following:hover { background-color: #d0d0d0; }
        body.no-scroll { overflow: hidden; }

        /* --- STYLING CONTEXT MENU CHAT --- */
        .chat-context-menu {
            position: absolute; background: white; border: 1px solid #eee;
            border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000; min-width: 160px; display: none;
        }
        .chat-context-item {
            padding: 10px 15px; cursor: pointer; font-size: 0.85rem; color: #333; transition: 0.2s;
            display: flex; align-items: center; gap: 8px;
        }
        .chat-context-item:hover { background: #f8f9fa; }
        .chat-context-item.danger { color: #dc3545; }
        .chat-context-item.danger:hover { background: #fff5f5; }

        .message-bubble { cursor: pointer; position: relative; }
        .message-bubble:hover { filter: brightness(0.95); }
    </style>
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
                <span class="cart-badge" id="navCartBadge" style="<?php echo $cart_count > 0 ? '' : 'display:none;'; ?>">
                    <?php echo $cart_count; ?>
                </span>
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
                    <img src="https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&q=80&w=1200">
                    <div class="hero-text">
                        <h1>Barang Bekas <br><span>Berkualitas</span></h1>
                        <p>Hemat uang dan selamatkan bumi.</p>
                    </div>
                </div>
                <div class="carousel-slide">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&q=80&w=1200">
                    <div class="hero-text">
                        <h1>Elektronik <br><span>Murah</span></h1>
                        <p>Upgrade gadget tanpa bikin kantong bolong.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-header"><h2 class="section-title">Kategori Pilihan</h2></div>
        <div class="category-pills">
            <?php
            $categories = ['Semua', 'Elektronik', 'Fashion', 'Hobi', 'Rumah Tangga', 'Buku', 'Otomotif'];
            foreach($categories as $cat) {
                echo '<div class="category-pill '.($cat == 'Semua' ? 'active' : '').'" onclick="filterCategory(this, \''.$cat.'\')">'.$cat.'</div>';
            }
            ?>
        </div>

        <div class="product-grid" id="productGrid">
            <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #888;" id="loadingGrid">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><br>Memuat produk...
            </div>
        </div>
    </div>

    <?php include 'chat.php'; ?>

    <div id="chatContextMenu" class="chat-context-menu"></div>

    <?php include 'checkout.php'; ?>
    <?php include 'keranjang.php'; ?>
    <?php include 'notifikasi.php'; ?>

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
                    <button class="btn btn-dark" id="btnAddToCart" onclick="addToCart()"><i class="fas fa-cart-plus"></i> Tambah</button>
                    <button class="btn btn-primary" onclick="buyNow()">Beli Sekarang</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const goToDashboard = () => window.location.href = 'dashboard.php';
        let products = <?php echo json_encode($all_products); ?>;

        // Buyer hanya punya INBOX (pesan masuk dari seller)
        const chatData = <?php echo json_encode($chat_messages_grouped); ?>;

        let currentActiveProduct = null;
        let selectedAddressId = <?php echo $default_addr ? $default_addr['address_id'] : 'null'; ?>;

        document.addEventListener('DOMContentLoaded', () => {
            renderProducts(products);

            // Close context menu on click elsewhere
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.message-bubble')) {
                    document.getElementById('chatContextMenu').style.display = 'none';
                }
            });
        });

        function renderProducts(data) {
            const productGrid = document.getElementById('productGrid');
            productGrid.innerHTML = '';
            if(data.length === 0) {
                productGrid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #888;">Tidak ada produk ditemukan.</div>`;
                return;
            }
            data.forEach(p => {
                const card = document.createElement('div');
                card.className = 'product-card';
                card.onclick = () => openModal(p);
                card.innerHTML = `
                    <div class="product-img-wrapper"><img src="${p.img}"></div>
                    <div class="product-info">
                        <div class="product-title">${p.title}</div>
                        <div class="product-price">Rp ${p.price.toLocaleString('id-ID')}</div>
                        <div class="product-meta"><i class="fas fa-map-marker-alt"></i> ${p.loc}</div>
                    </div>`;
                productGrid.appendChild(card);
            });
        }

        function filterCategory(btn, cat) {
            document.querySelectorAll('.category-pill').forEach(el => el.classList.remove('active'));
            btn.classList.add('active');

            const grid = document.getElementById('productGrid');
            grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #888;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><br>Memuat...</div>';

            fetch(`dashboard.php?action=filter_products&category=${encodeURIComponent(cat)}`)
                .then(response => response.json())
                .then(data => { products = data; renderProducts(products); })
                .catch(() => { grid.innerHTML = '<div style="text-align:center;">Gagal memuat produk.</div>'; });
        }

        const modalOverlay = document.getElementById('productModal');
        function openModal(product) {
            currentActiveProduct = product;

            const fd = new FormData();
            fd.append('action', 'view_product');
            fd.append('product_id', product.id);
            fetch('dashboard.php', { method: 'POST', body: fd });

            document.getElementById('modalImg').src = product.img;
            document.getElementById('modalTitle').textContent = product.title;
            document.getElementById('modalPrice').textContent = 'Rp ' + product.price.toLocaleString('id-ID');
            document.getElementById('modalDesc').textContent = product.desc;

            const metaRow = document.querySelector('.modal-meta-row');
            if(metaRow) metaRow.innerHTML = `<span style="color:#555; font-weight:600;">Kondisi: <span id="modalCond" style="font-weight:normal; margin-left:4px; color:#333;">${product.cond}</span></span>`;

            const catContainer = document.getElementById('modalCategoryBadge');
            if(catContainer) catContainer.innerHTML = `<span class="modal-category-badge">${product.category || 'Umum'}</span>`;

            const shopContainer = document.getElementById('modalShopContainer');
            const isFollowing = product.is_following;
            const followText = isFollowing ? 'Mengikuti' : 'Ikuti';
            const followIcon = isFollowing ? '<i class="fas fa-check"></i>' : '<i class="fas fa-plus"></i>';
            const followClass = isFollowing ? 'btn-follow following' : 'btn-follow';
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
                <button class="${followClass}" onclick="toggleFollow(${product.shop_id}, this, '${product.shop_name}')">${followIcon} ${followText}</button>
            `;

            const btnChat = document.getElementById('btnModalChat');
            const newBtnChat = btnChat.cloneNode(true);
            btnChat.parentNode.replaceChild(newBtnChat, btnChat);

            newBtnChat.onclick = function() {
                closeModal();
                toggleChat();
                // Partner id = seller_id, nama = shop_name, img = shop_image
                selectChat(product.seller_id, product.shop_name, shopImg);
            };

            modalOverlay.classList.add('open');
            document.body.classList.add('no-scroll');
        }

        function closeModal() { modalOverlay.classList.remove('open'); document.body.classList.remove('no-scroll'); }
        modalOverlay.addEventListener('click', (e) => { if (e.target === modalOverlay) closeModal(); });

        function toggleFollow(shopId, btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

            const formData = new FormData();
            formData.append('action', 'toggle_follow');
            formData.append('shop_id', shopId);

            fetch('dashboard.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'followed') {
                        btn.classList.add('following');
                        btn.innerHTML = '<i class="fas fa-check"></i> Mengikuti';
                        updateLocalProductFollowStatus(shopId, true);
                    } else if (data.status === 'unfollowed') {
                        btn.classList.remove('following');
                        btn.innerHTML = '<i class="fas fa-plus"></i> Ikuti';
                        updateLocalProductFollowStatus(shopId, false);
                    }
                })
                .finally(() => { btn.disabled = false; });
        }

        function updateLocalProductFollowStatus(shopId, status) {
            products.forEach(p => { if(p.shop_id == shopId) p.is_following = status; });
        }

        function addToCart() {
            if(!currentActiveProduct) return;
            const btn = document.getElementById('btnAddToCart');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'add_to_cart');
            formData.append('product_id', currentActiveProduct.id);

            fetch('dashboard.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        const badge = document.getElementById('navCartBadge');
                        badge.style.display = 'block';
                        badge.textContent = data.new_count;

                        const container = document.getElementById('cartItemsContainer');
                        if(container.querySelector('div') && container.querySelector('div').textContent === 'Keranjang kosong') container.innerHTML = '';

                        const newItem = document.createElement('div');
                        newItem.className = 'cart-item';
                        newItem.onclick = function() { toggleCartItem(this); };
                        newItem.dataset.id = data.cart_id;
                        newItem.dataset.shopId = currentActiveProduct.shop_id;
                        newItem.dataset.productId = currentActiveProduct.id;
                        newItem.dataset.price = currentActiveProduct.price;
                        newItem.dataset.name = currentActiveProduct.title;
                        newItem.dataset.img = currentActiveProduct.img;
                        newItem.style.cursor = 'pointer';

                        newItem.innerHTML = `
                            <div class="cart-check-wrapper"><input type="checkbox" class="cart-checkbox" onclick="event.stopPropagation(); updateCartTotal()"></div>
                            <img src="${currentActiveProduct.img}" class="cart-item-img" alt="Item">
                            <div class="cart-item-info">
                                <div class="cart-item-title">${currentActiveProduct.title}</div>
                                <div class="cart-item-price">Rp ${currentActiveProduct.price.toLocaleString('id-ID')}</div>
                            </div>
                            <button class="btn-delete" onclick="deleteCartItem(event, ${data.cart_id})"><i class="fas fa-trash"></i></button>
                        `;
                        container.prepend(newItem);

                        closeModal();
                        setTimeout(toggleCart, 300);
                        alert("Barang berhasil ditambahkan ke keranjang!");
                    } else {
                        alert("Gagal menambahkan: " + (data.message || ''));
                    }
                })
                .finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
        }

        function deleteCartItem(e, cartId) {
            e.stopPropagation();
            if(!confirm("Hapus barang ini dari keranjang?")) return;

            const itemElement = document.querySelector(`.cart-item[data-id='${cartId}']`);
            const formData = new FormData();
            formData.append('action', 'delete_item');
            formData.append('cart_id', cartId);

            fetch('dashboard.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        if(itemElement) itemElement.remove();
                        const badge = document.getElementById('navCartBadge');
                        if(data.new_count > 0) {
                            badge.textContent = data.new_count;
                        } else {
                            badge.style.display = 'none';
                            document.getElementById('cartItemsContainer').innerHTML = '<div style="text-align:center; padding:20px; color:#666;">Keranjang kosong</div>';
                        }
                        updateCartTotal();
                    } else {
                        alert("Gagal menghapus barang.");
                    }
                });
        }

        // Checkout functions...
        const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n);
        let checkoutProductPriceTotal = 0;
        const checkoutAdminFee = <?php echo $admin_fee; ?>;
        let activePaymentCategory = null;
        let activePaymentName = '';
        let selectedShippingCost = 0;
        let selectedShippingName = '';
        const courierPrices = { "COD": 0, "JNE Reguler": 15000, "J&T Express": 18000, "SiCepat": 12000, "GoSend Instant": 25000, "GrabExpress": 24000, "AnterAja": 11000 };

        function checkoutFromCart() {
            const items = [];
            let shopId = null;
            document.querySelectorAll('.cart-item').forEach(el => {
                if(el.querySelector('.cart-checkbox').checked) {
                    items.push({
                        title: el.getAttribute('data-name'),
                        price: parseInt(el.getAttribute('data-price')),
                        img: el.getAttribute('data-img'),
                        id: el.getAttribute('data-product-id'),
                        shop_id: el.getAttribute('data-shop-id'),
                        cart_id: el.getAttribute('data-id')
                    });
                    shopId = el.getAttribute('data-shop-id');
                }
            });
            if(items.length===0) { alert("Pilih minimal satu barang."); return; }
            localStorage.setItem('checkoutItems', JSON.stringify(items));
            localStorage.setItem('checkoutShopId', shopId);
            toggleCart();
            initCheckoutModal();
        }

        function buyNow() {
            if(!currentActiveProduct) return;
            localStorage.setItem('checkoutItems', JSON.stringify([{
                title: currentActiveProduct.title,
                price: currentActiveProduct.price,
                img: currentActiveProduct.img,
                id: currentActiveProduct.id,
                shop_id: currentActiveProduct.shop_id
            }]));
            localStorage.setItem('checkoutShopId', currentActiveProduct.shop_id);
            closeModal();
            initCheckoutModal();
        }

        async function initCheckoutModal() {
            document.body.classList.add('no-scroll');
            const storedData = localStorage.getItem('checkoutItems');
            const shopId = localStorage.getItem('checkoutShopId');
            const container = document.getElementById('checkoutProductList');

            container.innerHTML = '';
            checkoutProductPriceTotal = 0;
            selectedShippingCost = 0;
            selectedShippingName = '';
            activePaymentCategory = null;
            activePaymentName = '';

            if (storedData) {
                const data = JSON.parse(storedData);
                data.forEach(item => {
                    checkoutProductPriceTotal += item.price;
                    container.innerHTML += `
                        <div class="product-row-checkout">
                            <img src="${item.img}" class="product-img-checkout">
                            <div class="product-details-checkout">
                                <div class="prod-name-checkout">${item.title}</div>
                                <div class="prod-price-checkout">${formatRupiah(item.price)}</div>
                            </div>
                        </div>`;
                });
            }

            document.getElementById('shippingContainer').innerHTML = '<div style="text-align:center; padding:10px; color:#888;">Memuat opsi pengiriman...</div>';
            document.getElementById('paymentContainer').innerHTML = '<div style="text-align:center; padding:10px; color:#888;">Memuat metode pembayaran...</div>';
            calculateCheckoutTotal();

            document.getElementById('checkoutModal').classList.add('open');

            if(shopId) {
                try {
                    const fd = new FormData();
                    fd.append('action', 'get_shop_settings');
                    fd.append('shop_id', shopId);

                    const response = await fetch('dashboard.php', { method: 'POST', body: fd });
                    const settings = await response.json();

                    renderShippingOptions(settings.shipping);
                    renderPaymentMethods(settings.payment);
                } catch (e) { console.error(e); }
            }
        }

        function renderShippingOptions(options) {
            const container = document.getElementById('shippingContainer');
            container.innerHTML = '';
            if(options && options.length > 0) {
                options.forEach(opt => {
                    const price = courierPrices[opt] !== undefined ? courierPrices[opt] : 15000;
                    container.innerHTML += `
                        <div class="shipping-option-card" onclick="selectShippingOption(this, '${opt}', ${price})">
                            <div class="ship-info">
                                <span class="ship-name">${opt}</span>
                                <span class="ship-price">${formatRupiah(price)}</span>
                            </div>
                            <i class="fas fa-check-circle check-circle" style="display:none; color:var(--primary);"></i>
                        </div>`;
                });
            } else {
                container.innerHTML = '<div style="color:#888; padding:10px;">Toko belum mengatur pengiriman.</div>';
            }
        }

        function selectShippingOption(el, name, price) {
            document.querySelectorAll('.shipping-option-card').forEach(c => {
                c.classList.remove('active');
                c.querySelector('.check-circle').style.display = 'none';
            });
            el.classList.add('active');
            el.querySelector('.check-circle').style.display = 'block';
            selectedShippingCost = price;
            selectedShippingName = name;
            calculateCheckoutTotal();
        }

        function renderPaymentMethods(methods) {
            const container = document.getElementById('paymentContainer');
            container.innerHTML = '';

            const cats = {
                'Transfer Bank': {id: 'bank', icon: 'fa-university', items: []},
                'E-Wallet': {id: 'ewallet', icon: 'fa-wallet', items: []},
                'COD': {id: 'cod', icon: 'fa-hand-holding-usd', items: []}
            };

            if(methods) {
                methods.forEach(pay => {
                    if(pay.includes('Bank') || pay.includes('BCA') || pay.includes('BRI') || pay.includes('Mandiri')) cats['Transfer Bank'].items.push(pay);
                    else if(pay.includes('Pay') || pay.includes('OVO') || pay.includes('Dana')) cats['E-Wallet'].items.push(pay);
                    else if(pay.includes('COD')) cats['COD'].items.push(pay);
                });
            }

            for (const [key, cat] of Object.entries(cats)) {
                if(cat.items.length > 0) {
                    let subHTML = '';
                    cat.items.forEach(i => {
                        subHTML += `<div class="sub-option" onclick="selectPaymentSubOption('${cat.id}', '${i}', '${i}')"><i class="far fa-circle" style="font-size:0.8rem; margin-right:8px; color:#bbb;"></i> ${i}</div>`;
                    });

                    const dropIcon = (key !== 'COD') ? `<i class="fas fa-chevron-down drop-icon"></i>` : '';
                    const toggleAction = (key !== 'COD') ? `onclick="togglePaymentDropdown(event, 'list-${cat.id}')"` : `onclick="selectPaymentCategory('cod', 'COD (Bayar di Tempat)')"`
                    const listDisplay = (key !== 'COD') ? `<div class="payment-options-list" id="list-${cat.id}">${subHTML}</div>` : '';

                    container.innerHTML += `
                        <div class="payment-category" id="cat-${cat.id}" ${toggleAction}>
                            <div class="payment-header">
                                <div class="ph-left">
                                    <i class="fas ${cat.icon}" style="width:20px; text-align:center; margin-right:8px;"></i>
                                    <span class="ph-title">${key}</span>
                                </div>
                                <div class="ph-right">
                                    ${dropIcon}
                                    <i class="fas fa-check-circle check-circle" id="check-${cat.id}" style="display:none; color:var(--primary);"></i>
                                </div>
                            </div>
                            ${listDisplay}
                        </div>`;
                }
            }

            if(container.innerHTML === '') container.innerHTML = '<div style="color:#888; padding:10px;">Toko belum mengatur pembayaran.</div>';
        }

        function closeCheckoutModal() { document.getElementById('checkoutModal').classList.remove('open'); document.body.classList.remove('no-scroll'); }

        function calculateCheckoutTotal() {
            const total = checkoutProductPriceTotal + selectedShippingCost + checkoutAdminFee;
            document.getElementById('summaryProdPrice').innerText = formatRupiah(checkoutProductPriceTotal);
            document.getElementById('summaryShipPrice').innerText = formatRupiah(selectedShippingCost);
            document.getElementById('summaryTotal').innerText = formatRupiah(total);
            document.getElementById('bottomTotal').innerText = formatRupiah(total);
        }

        function togglePaymentDropdown(e, listId) {
            e.stopPropagation();
            document.querySelectorAll('.payment-options-list').forEach(el => { if(el.id !== listId) el.classList.remove('show'); });
            document.getElementById(listId).classList.toggle('show');
        }

        function selectPaymentCategory(catId, name='') {
            document.querySelectorAll('.payment-category').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.payment-category .check-circle').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.payment-category .drop-icon').forEach(el => el.style.display = 'block');
            document.querySelectorAll('.sub-option').forEach(el => el.classList.remove('selected'));

            const catEl = document.getElementById('cat-' + catId);
            if(catEl) {
                catEl.classList.add('active');
                const check = catEl.querySelector('.check-circle');
                const drop = catEl.querySelector('.drop-icon');
                if(check) check.style.display = 'block';
                if(drop) drop.style.display = 'none';
                activePaymentCategory = catId;
                activePaymentName = name;
            }
        }

        function selectPaymentSubOption(catId, val, name) {
            selectPaymentCategory(catId, name);
            const list = document.getElementById('list-' + catId);

            Array.from(list.children).forEach(child => {
                if(child.textContent.includes(name)) {
                    child.classList.add('selected');
                    child.querySelector('i').className = 'fas fa-dot-circle';
                    child.querySelector('i').style.color = 'var(--primary)';
                } else {
                    child.classList.remove('selected');
                    child.querySelector('i').className = 'far fa-circle';
                }
            });
        }

        function processOrder() {
            if (!selectedShippingName) { alert("Pilih pengiriman."); return; }
            if (!activePaymentCategory) { alert("Pilih metode pembayaran."); return; }
            if (!selectedAddressId) { alert("Pilih alamat pengiriman."); return; }

            const items = localStorage.getItem('checkoutItems');
            if(confirm("Buat pesanan sekarang?")) {
                const btn = document.querySelector('.btn-order');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                btn.disabled = true;

                const fd = new FormData();
                fd.append('action', 'create_order');
                fd.append('address_id', selectedAddressId);
                fd.append('shipping_method', selectedShippingName);
                fd.append('shipping_cost', selectedShippingCost);
                fd.append('payment_method', activePaymentName);
                fd.append('items', items);

                fetch('dashboard.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            alert("Pesanan berhasil dibuat!");
                            closeCheckoutModal();
                            window.location.href = 'histori.php';
                        } else {
                            alert("Gagal: " + (data.message || ''));
                            btn.innerHTML = 'Buat Pesanan';
                            btn.disabled = false;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert("Error sistem.");
                        btn.innerHTML = 'Buat Pesanan';
                        btn.disabled = false;
                    });
            }
        }

        function openAddressModal() { document.getElementById('addressModal').classList.add('open'); }
        function closeAddressModal() { document.getElementById('addressModal').classList.remove('open'); }
        function selectAddress(el, name, detail, phone) {
            const displayBox = document.querySelector('.address-box');
            displayBox.innerHTML = `<div class="addr-header-row"><span class="addr-recipient">${name}</span><span class="addr-divider">|</span><span class="addr-phone">${phone}</span></div><div class="addr-body-text">${detail}</div><div class="addr-change-text">Ubah Alamat <i class="fas fa-chevron-right"></i></div>`;
            closeAddressModal();
        }

        function toggleCartItem(el) { const cb = el.querySelector('.cart-checkbox'); cb.checked = !cb.checked; updateCartTotal(); }
        function toggleCart() { document.getElementById('cartSidebar').classList.toggle('open'); document.getElementById('cartOverlay').classList.toggle('open'); updateCartTotal(); }
        function toggleNotifications() { document.getElementById('notifSidebar').classList.toggle('open'); document.getElementById('notifOverlay').classList.toggle('open'); }
        function toggleChat() { document.getElementById('chatSidebar').classList.toggle('open'); document.getElementById('chatOverlay').classList.toggle('open'); }
        function updateCartTotal() {
            let t=0;
            document.querySelectorAll('.cart-item').forEach(el=>{
                if(el.querySelector('.cart-checkbox').checked) t+=parseInt(el.getAttribute('data-price'))
            });
            document.getElementById('cartTotalPrice').innerText=formatRupiah(t);
        }

        // ========================
        // CHAT BUYER (INBOX ONLY)
        // ========================
        let currentChatPartnerId = null;
        let messages = [];

        function selectChat(pid, name, img=null){
            currentChatPartnerId = pid;
            messages = (chatData && chatData[pid]) ? [...chatData[pid]] : [];

            document.getElementById('chatSellerNameSidebar').textContent = name;
            document.getElementById('chatSellerAvatarSidebar').src = img || `https://api.dicebear.com/7.x/avataaars/svg?seed=${name}`;

            renderMessagesSidebar();
            document.getElementById('chatItemsContainer').style.display = 'none';
            document.getElementById('chatAreaSidebar').style.display = 'flex';
        }

        function renderMessagesSidebar(){
            const c = document.getElementById('chatMessagesSidebar');
            c.innerHTML = '';

            if(messages.length === 0){
                c.innerHTML = '<div style="text-align:center;padding:20px;color:#888;">Belum ada pesan.</div>';
                return;
            }

            messages.forEach(m=>{
                const bubbleClass = (m.type === 'outgoing') ? 'msg-me' : 'msg-other';

                let content = '';
                if (m.img) {
                    content += `<img src="${m.img}" style="max-width:220px;border-radius:10px;display:block;margin-bottom:${m.text ? '8px' : '0'};">`;
                }
                if (m.text) {
                    content += `<div>${m.text}</div>`;
                }

                c.innerHTML += `
                    <div class="message-wrapper ${m.type}">
                        <div class="message-bubble ${bubbleClass}" onclick="showChatOptions(event, ${m.id}, '${m.type}')">
                            ${content}
                        </div>
                        <span class="message-time">${m.time}</span>
                    </div>`;
            });

            c.scrollTop = c.scrollHeight;
        }

        // CONTEXT MENU
        const contextMenu = document.getElementById('chatContextMenu');

        function showChatOptions(e, id, type) {
            e.stopPropagation();

            contextMenu.innerHTML = '';

            // Buyer hanya punya incoming view -> menu report
            if (type === 'incoming') {
                contextMenu.innerHTML = `<div class="chat-context-item danger" onclick="reportMessage(${id})"><i class="fas fa-flag"></i> Laporkan Pesan</div>`;
            } else {
                contextMenu.innerHTML = `<div class="chat-context-item danger" onclick="deleteMessage(${id})"><i class="fas fa-trash"></i> Hapus Pesan</div>`;
            }

            contextMenu.style.top = e.pageY + 'px';
            contextMenu.style.left = e.pageX + 'px';
            contextMenu.style.display = 'block';
        }

        function deleteMessage(id) {
            if(!confirm("Hapus pesan ini?")) return;
            const fd = new FormData();
            fd.append('action', 'delete_chat');
            fd.append('chat_id', id);

            fetch('dashboard.php', {method:'POST', body:fd})
                .then(res=>res.json())
                .then(data=>{
                    if(data.status==='success'){
                        messages = messages.filter(m => m.id !== id);
                        renderMessagesSidebar();
                        contextMenu.style.display='none';
                    } else {
                        alert('Gagal hapus pesan.');
                    }
                });
        }

        function reportMessage(id) {
            const reason = prompt("Masukkan alasan pelaporan:");
            if(!reason) return;

            const fd = new FormData();
            fd.append('action', 'report_chat');
            fd.append('chat_id', id);
            fd.append('reason', reason);

            fetch('dashboard.php', {method:'POST', body:fd})
                .then(res=>res.json())
                .then(data=>{
                    alert("Laporan terkirim. Admin akan meninjau.");
                    contextMenu.style.display='none';
                });
        }

        // Buyer send -> tidak tampil di buyer (sesuai request)
        function sendMessageSidebar(){
            const inp = document.getElementById('messageInputSidebar');
            const txt = inp.value.trim();
            if(!txt || !currentChatPartnerId) return;

            inp.value = '';

            const fd = new FormData();
            fd.append('action','send_message');
            fd.append('receiver_id',currentChatPartnerId);
            fd.append('message',txt);

            fetch('dashboard.php',{method:'POST',body:fd})
                .then(res=>res.json())
                .then(data=>{
                    if(data.status!=='success') alert('Gagal mengirim pesan.');
                });
        }

        function handleEnterSidebar(e){ if(e.key==='Enter') sendMessageSidebar(); }
        function backToChatList(){
            document.getElementById('chatItemsContainer').style.display='block';
            document.getElementById('chatAreaSidebar').style.display='none';
        }

        // Send Image (Buyer -> Seller) tanpa tampil di buyer
        function openFileInput() {
            document.getElementById('fileInput').click();
        }

        function sendImage() {
            if(!currentChatPartnerId) return;

            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];
            if(!file) return;

            const fd = new FormData();
            fd.append('action','send_message');
            fd.append('receiver_id', currentChatPartnerId);
            fd.append('message','');
            fd.append('image', file);

            fetch('dashboard.php', {method:'POST', body: fd})
                .then(res=>res.json())
                .then(data=>{
                    if(data.status!=='success') alert('Gagal mengirim foto.');
                    fileInput.value = '';
                });
        }

        const track=document.getElementById('carouselTrack');
        let ci=0;
        setInterval(()=>{ci=(ci+1)%2;track.style.transform=`translateX(-${ci*100}%)`;},5000);
    </script>
</body>
</html>
