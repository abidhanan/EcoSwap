<?php
session_start();
include '../../../Auth/koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) { header("Location: ../../auth/login.php"); exit(); }
$user_id = (int)$_SESSION['user_id'];

// --- AMBIL BIAYA ADMIN ---
$q_fee = mysqli_query($koneksi, "SELECT setting_value FROM system_settings WHERE setting_key = 'admin_fee' LIMIT 1");
$d_fee = mysqli_fetch_assoc($q_fee);
$admin_fee = isset($d_fee['setting_value']) ? (int)$d_fee['setting_value'] : 1000;

/* =========================================================
   HELPERS
========================================================= */

function normalize_web_path($path){
    // Biar image_path bisa dipakai di <img src="">
    if(empty($path)) return null;

    // Jika sudah URL
    if(preg_match('~^https?://~i', $path)) return $path;

    // Hilangkan backslash windows
    $path = str_replace('\\','/',$path);

    // Kalau path disimpan "../../../Assets/..." tetap biarkan
    // Kalau disimpan "Assets/..." juga ok
    return $path;
}

function chat_partner_profile($koneksi, $partner_user_id) {
    $partner_user_id = (int)$partner_user_id;
    $q = mysqli_query($koneksi, "
        SELECT u.user_id, u.name, u.profile_picture,
               s.shop_name, s.shop_image
        FROM users u
        LEFT JOIN shops s ON s.user_id = u.user_id
        WHERE u.user_id = '$partner_user_id'
        LIMIT 1
    ");
    $d = mysqli_fetch_assoc($q);
    if(!$d) return ['id'=>$partner_user_id, 'name'=>'User', 'img'=>"https://ui-avatars.com/api/?name=User&background=random"];

    $name = !empty($d['shop_name']) ? $d['shop_name'] : $d['name'];
    $img  = !empty($d['shop_image']) ? $d['shop_image'] : $d['profile_picture'];
    if(empty($img)) $img = "https://ui-avatars.com/api/?name=".urlencode($name)."&background=random";

    return ['id'=>$partner_user_id, 'name'=>$name, 'img'=>$img];
}

function fetch_chat_messages($koneksi, $user_id, $partner_id) {
    $user_id = (int)$user_id;
    $partner_id = (int)$partner_id;

    $rows = [];
    $q = mysqli_query($koneksi, "
        SELECT chat_id, sender_id, receiver_id, message, image_path, created_at
        FROM chats
        WHERE (sender_id='$user_id' AND receiver_id='$partner_id')
           OR (sender_id='$partner_id' AND receiver_id='$user_id')
        ORDER BY created_at ASC
    ");
    while($r = mysqli_fetch_assoc($q)){
        $rows[] = [
            'id' => (int)$r['chat_id'],
            'type' => ((int)$r['sender_id'] === $user_id) ? 'outgoing' : 'incoming',
            'text' => $r['message'],
            'img'  => normalize_web_path($r['image_path']),
            'time' => date('H:i', strtotime($r['created_at']))
        ];
    }
    return $rows;
}

/* =========================================================
   AJAX CHAT
========================================================= */

// GET CHAT (messages + partner)
if (isset($_GET['action']) && $_GET['action'] === 'get_chat') {
    header('Content-Type: application/json');
    $partner_id = isset($_GET['partner_id']) ? (int)$_GET['partner_id'] : 0;
    if($partner_id <= 0) { echo json_encode(['status'=>'error']); exit; }

    $partner = chat_partner_profile($koneksi, $partner_id);
    $messages = fetch_chat_messages($koneksi, $user_id, $partner_id);

    echo json_encode(['status'=>'success', 'partner'=>$partner, 'messages'=>$messages]);
    exit;
}

// SEND MESSAGE (text + image)
if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
    header('Content-Type: application/json');

    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $message = isset($_POST['message']) ? mysqli_real_escape_string($koneksi, trim($_POST['message'])) : '';

    if($receiver_id <= 0) { echo json_encode(['status'=>'error','message'=>'receiver invalid']); exit; }

    // Upload image optional
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../../../Assets/img/chats/";
        if (!file_exists($target_dir)) { @mkdir($target_dir, 0777, true); }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['status'=>'error','message'=>'format tidak didukung']);
            exit;
        }

        $file_name = time() . "_" . bin2hex(random_bytes(5)) . "." . $ext;
        $target_file = $target_dir . $file_name;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            echo json_encode(['status'=>'error','message'=>'gagal upload']);
            exit;
        }
        // simpan path yang bisa dipakai langsung di browser
        $image_path = $target_file;
    }

    if ($message === '' && empty($image_path)) {
        echo json_encode(['status'=>'empty']);
        exit;
    }

    $img_sql = $image_path ? "'" . mysqli_real_escape_string($koneksi, $image_path) . "'" : "NULL";
    $ins = mysqli_query($koneksi, "
        INSERT INTO chats (sender_id, receiver_id, message, image_path, created_at)
        VALUES ('$user_id', '$receiver_id', '$message', $img_sql, NOW())
    ");

    if($ins){
        echo json_encode([
            'status'=>'success',
            'chat_id'=>mysqli_insert_id($koneksi),
            'time'=>date('H:i'),
            'image'=>normalize_web_path($image_path)
        ]);
    } else {
        echo json_encode(['status'=>'error','message'=>'db insert gagal']);
    }
    exit;
}

// DELETE CHAT
if (isset($_POST['action']) && $_POST['action'] === 'delete_chat') {
    header('Content-Type: application/json');
    $chat_id = (int)($_POST['chat_id'] ?? 0);
    if($chat_id<=0){ echo json_encode(['status'=>'error']); exit; }

    $del = mysqli_query($koneksi, "DELETE FROM chats WHERE chat_id='$chat_id' AND sender_id='$user_id'");
    echo json_encode(['status'=>$del ? 'success' : 'error']);
    exit;
}

// REPORT CHAT -> NOTIF ADMIN
if (isset($_POST['action']) && $_POST['action'] === 'report_chat') {
    header('Content-Type: application/json');
    $chat_id = (int)($_POST['chat_id'] ?? 0);
    $reason = mysqli_real_escape_string($koneksi, trim($_POST['reason'] ?? ''));

    if($chat_id<=0 || $reason===''){ echo json_encode(['status'=>'error']); exit; }

    $q_c = mysqli_query($koneksi, "
        SELECT c.chat_id, c.sender_id, c.receiver_id, c.message, c.image_path, c.created_at,
               su.name as sender_name
        FROM chats c
        JOIN users su ON su.user_id = c.sender_id
        WHERE c.chat_id='$chat_id'
        LIMIT 1
    ");
    $d_c = mysqli_fetch_assoc($q_c);
    if(!$d_c){ echo json_encode(['status'=>'error']); exit; }

    $q_adm = mysqli_query($koneksi, "SELECT user_id FROM users WHERE role='admin' LIMIT 1");
    $d_adm = mysqli_fetch_assoc($q_adm);
    $admin_id = $d_adm ? (int)$d_adm['user_id'] : 0;

    if($admin_id){
        $content = $d_c['message'];
        if(empty($content) && !empty($d_c['image_path'])) $content = '[FOTO] ' . $d_c['image_path'];

        $report_msg =
            "LAPORAN CHAT\n".
            "- Pelapor (buyer) ID: $user_id\n".
            "- Terlapor ID: ".$d_c['sender_id']." (".$d_c['sender_name'].")\n".
            "- Chat ID: ".$d_c['chat_id']."\n".
            "- Waktu: ".$d_c['created_at']."\n".
            "- Isi: ".$content."\n".
            "- Alasan: ".$reason;

        mysqli_query($koneksi, "
            INSERT INTO notifications (user_id, title, message, is_read, created_at)
            VALUES ('$admin_id', 'Laporan Chat', '".mysqli_real_escape_string($koneksi,$report_msg)."', 0, NOW())
        ");
    }

    echo json_encode(['status'=>'success']);
    exit;
}

/* =========================================================
   HANDLER LAIN (tetap sama)
========================================================= */

// CREATE ORDER
if (isset($_POST['action']) && $_POST['action'] == 'create_order') {
    header('Content-Type: application/json');
    $address_id = $_POST['address_id']; $shipping_method = $_POST['shipping_method']; $shipping_cost = $_POST['shipping_cost']; $payment_method = $_POST['payment_method']; $items = json_decode($_POST['items'], true);

    $q_addr = mysqli_query($koneksi, "SELECT * FROM addresses WHERE address_id='$address_id'");
    $d_addr = mysqli_fetch_assoc($q_addr);
    if(!$d_addr) { echo json_encode(['status' => 'error', 'message' => 'Alamat tidak ditemukan.']); exit; }

    $full_address_snapshot = $d_addr['full_address'] . ", " . $d_addr['village'] . ", " . $d_addr['subdistrict'] . ", " . $d_addr['city'] . " " . $d_addr['postal_code'] . " (" . $d_addr['recipient_name'] . " - " . $d_addr['phone_number'] . ")";
    $invoice_code = "INV/" . date('Ymd') . "/" . strtoupper(substr(md5(time() . rand()), 0, 6));
    $success_count = 0;

    foreach ($items as $item) {
        $prod_id = $item['id']; $shop_id = $item['shop_id']; $price = $item['price']; $final_price = $price;
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

if (isset($_POST['action']) && $_POST['action'] == 'mark_read') { $nid = $_POST['notif_id']; mysqli_query($koneksi, "UPDATE notifications SET is_read=1 WHERE notif_id='$nid'"); exit; }
if (isset($_POST['action']) && $_POST['action'] == 'get_shop_settings') { 
    header('Content-Type: application/json'); 
    $shop_id = $_POST['shop_id']; 
    
    $query = mysqli_query($koneksi, "SELECT shipping_options, payment_methods, shipping_costs FROM shops WHERE shop_id='$shop_id'"); 
    $data = mysqli_fetch_assoc($query); 
    
    // Parse shipping options dari array string menjadi array objek dengan name & cost
    $shipping_raw = !empty($data['shipping_options']) ? json_decode($data['shipping_options'], true) : [];
    $shipping_costs_raw = !empty($data['shipping_costs']) ? json_decode($data['shipping_costs'], true) : [];
    
    $shipping = [];
    if(is_array($shipping_raw)) {
        foreach($shipping_raw as $ship_name) {
            // Ambil cost dari shipping_costs berdasarkan mapping yang akurat
            $cost = 15000; // default
            
            // Mapping nama kurir lengkap ke key di shipping_costs
            $courier_mapping = [
                'JNE' => ['JNE', 'JNE Reguler', 'JNE Regular'],
                'JNT' => ['JNT', 'J&T', 'J&T Express', 'JNT Express'],
                'SiCepat' => ['SiCepat', 'Si Cepat', 'Sicepat'],
                'GoSend' => ['GoSend', 'Go Send', 'GoSend Instant', 'Gosend'],
                'Grab' => ['Grab', 'GrabExpress', 'Grab Express'],
                'AnterAja' => ['AnterAja', 'Anter Aja', 'AnterAja Express']
            ];
            
            // Cek apakah ada di shipping_costs
            if(is_array($shipping_costs_raw)) {
                foreach($courier_mapping as $key => $variations) {
                    // Cek apakah shipping_costs memiliki key ini
                    if(isset($shipping_costs_raw[$key])) {
                        // Cek apakah ship_name cocok dengan salah satu variasi
                        foreach($variations as $variation) {
                            if(stripos($ship_name, $variation) !== false) {
                                $cost = (int)$shipping_costs_raw[$key];
                                break 2; // Keluar dari kedua loop
                            }
                        }
                    }
                }
            }
            
            $shipping[] = ['name' => $ship_name, 'cost' => $cost];
        }
    }
    
    // Parse payment methods dari array string menjadi array objek dengan category & name
    $payment_raw = !empty($data['payment_methods']) ? json_decode($data['payment_methods'], true) : [];
    $payment = [];
    
    if(is_array($payment_raw)) {
        foreach($payment_raw as $pay_name) {
            $category = 'Lainnya';
            
            // Deteksi kategori berdasarkan nama
            if(stripos($pay_name, 'Transfer Bank') !== false || stripos($pay_name, 'BCA') !== false || stripos($pay_name, 'BRI') !== false || stripos($pay_name, 'Mandiri') !== false) {
                $category = 'Transfer Bank';
            } elseif(stripos($pay_name, 'E-Wallet') !== false || stripos($pay_name, 'GoPay') !== false || stripos($pay_name, 'OVO') !== false || stripos($pay_name, 'Dana') !== false) {
                $category = 'E-Wallet';
            } elseif(stripos($pay_name, 'COD') !== false) {
                $category = 'COD';
            }
            
            $payment[] = ['category' => $category, 'name' => $pay_name];
        }
    }
    
    echo json_encode(['status' => 'success', 'shipping' => $shipping, 'payment' => $payment]); 
    exit; 
}
if (isset($_POST['action']) && $_POST['action'] == 'toggle_follow') { header('Content-Type: application/json'); $target_shop_id = $_POST['shop_id']; $check = mysqli_query($koneksi, "SELECT * FROM shop_followers WHERE shop_id='$target_shop_id' AND user_id='$user_id'"); if (mysqli_num_rows($check) > 0) { mysqli_query($koneksi, "DELETE FROM shop_followers WHERE shop_id='$target_shop_id' AND user_id='$user_id'"); echo json_encode(['status' => 'unfollowed']); } else { mysqli_query($koneksi, "INSERT INTO shop_followers (shop_id, user_id) VALUES ('$target_shop_id', '$user_id')"); echo json_encode(['status' => 'followed']); } exit; }

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
        $filtered_products[] = [
            'id' => (int)$row['product_id'],
            'title' => $row['name'],
            'price' => (int)$row['price'],
            'loc' => $city,
            'img' => $img_db,
            'cond' => $row['condition'],
            'desc' => $row['description'],
            'category' => $row['category'],
            'shop_name' => $row['shop_name'],
            'shop_img' => $row['shop_image'],
            'shop_id' => (int)$row['shop_id'],
            'seller_id' => (int)$row['seller_id'],
            'shop_address' => $city,
            'is_following' => $is_following
        ];
    }
    echo json_encode($filtered_products); exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'add_to_cart') { header('Content-Type: application/json'); $product_id = $_POST['product_id']; $insert = mysqli_query($koneksi, "INSERT INTO cart (user_id, product_id) VALUES ('$user_id', '$product_id')"); if ($insert) { $new_cart_id = mysqli_insert_id($koneksi); $q_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM cart WHERE user_id='$user_id'"); $d_count = mysqli_fetch_assoc($q_count); echo json_encode(['status' => 'success', 'cart_id' => $new_cart_id, 'new_count' => $d_count['total']]); } else { echo json_encode(['status' => 'error']); } exit; }
if (isset($_POST['action']) && $_POST['action'] == 'delete_item') { header('Content-Type: application/json'); $cart_id = $_POST['cart_id']; $delete = mysqli_query($koneksi, "DELETE FROM cart WHERE cart_id='$cart_id' AND user_id='$user_id'"); if ($delete) { $q_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM cart WHERE user_id='$user_id'"); $d_count = mysqli_fetch_assoc($q_count); echo json_encode(['status' => 'success', 'new_count' => $d_count['total']]); } else { echo json_encode(['status' => 'error']); } exit; }
if (isset($_POST['action']) && $_POST['action'] == 'view_product') { $pid = mysqli_real_escape_string($koneksi, $_POST['product_id']); mysqli_query($koneksi, "UPDATE products SET views = views + 1 WHERE product_id = '$pid'"); exit(); }

/* =========================================================
   DATA FETCHING UTAMA
========================================================= */

$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id = '$user_id' LIMIT 1");
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
        'id' => (int)$row['product_id'],
        'title' => $row['name'],
        'price' => (int)$row['price'],
        'loc' => $city,
        'img' => $row['image'],
        'cond' => $row['condition'],
        'desc' => $row['description'],
        'category' => $row['category'],
        'shop_name' => $row['shop_name'],
        'shop_img' => $row['shop_image'],
        'shop_id' => (int)$row['shop_id'],
        'seller_id' => (int)$row['seller_id'],
        'shop_address' => $city,
        'is_following' => $is_following
    ];
}

// Cart
$cart_items = []; $cart_total = 0;
$q_cart = mysqli_query($koneksi, "
    SELECT c.cart_id, p.product_id, p.name, p.price, p.image, p.shop_id, p.status
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = '$user_id' AND p.status = 'active'
    ORDER BY c.created_at DESC
");
while($row = mysqli_fetch_assoc($q_cart)){ $cart_items[] = $row; $cart_total += (int)$row['price']; }
$cart_count = count($cart_items);

// Notif
$notif_items = [];
$q_notif = mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 10");
while($row = mysqli_fetch_assoc($q_notif)){ $notif_items[] = $row; }
$notif_count = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id='$user_id' AND is_read=0"));

// Address
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

/* =========================================================
   CHAT LIST BUYER
========================================================= */
$chat_partners = [];
$q_partner = mysqli_query($koneksi, "
    SELECT DISTINCT
        CASE WHEN sender_id='$user_id' THEN receiver_id ELSE sender_id END AS partner_id
    FROM chats
    WHERE sender_id='$user_id' OR receiver_id='$user_id'
");
while($r = mysqli_fetch_assoc($q_partner)){
    $pid = (int)$r['partner_id'];
    $p = chat_partner_profile($koneksi, $pid);

    $q_last = mysqli_query($koneksi, "
        SELECT message, image_path, created_at
        FROM chats
        WHERE (sender_id='$user_id' AND receiver_id='$pid')
           OR (sender_id='$pid' AND receiver_id='$user_id')
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $l = mysqli_fetch_assoc($q_last);

    $preview = '';
    $time = '';
    if($l){
        $preview = !empty($l['message']) ? $l['message'] : (!empty($l['image_path']) ? '[Foto]' : '');
        $time = date('H:i', strtotime($l['created_at']));
    }

    $chat_partners[$pid] = [
        'id'=>$pid,
        'name'=>$p['name'],
        'img'=>$p['img'],
        'last_msg'=>$preview,
        'time'=>$time
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
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/keranjang.css">
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/notifikasi.css">
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/chat.css">
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/checkout.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        .btn-follow { display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.3s ease; }
        .btn-follow i { font-size:0.9rem; }
        .btn-follow.following { background-color:#e0e0e0; color:#333; border:1px solid #ccc; }
        .btn-follow.following:hover { background-color:#d0d0d0; }

        /* LOCK BACKGROUND SCROLL WHEN CHAT OPEN */
        body.no-scroll-chat { overflow: hidden; }

        /* context menu */
        .chat-context-menu {
            position:absolute; background:white; border:1px solid #eee;
            border-radius:6px; box-shadow:0 4px 12px rgba(0,0,0,0.15);
            z-index:10000; min-width:160px; display:none;
        }
        .chat-context-item {
            padding:10px 15px; cursor:pointer; font-size:0.85rem; color:#333; transition:0.2s;
            display:flex; align-items:center; gap:8px;
        }
        .chat-context-item:hover { background:#f8f9fa; }
        .chat-context-item.danger { color:#dc3545; }
        .chat-context-item.danger:hover { background:#fff5f5; }
        .message-bubble { cursor:pointer; position:relative; }
        .message-bubble:hover { filter: brightness(0.97); }

        /* lightbox image */
        .img-lightbox {
            position:fixed; inset:0; background:rgba(0,0,0,0.75);
            display:none; align-items:center; justify-content:center;
            z-index: 99999;
        }
        .img-lightbox.open { display:flex; }
        .img-lightbox img{
            max-width: 92vw; max-height: 92vh; border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.45);
        }
        .img-lightbox .close-x{
            position:absolute; top:18px; right:18px;
            width:42px; height:42px; border-radius:50%;
            background:rgba(255,255,255,0.15);
            border:1px solid rgba(255,255,255,0.25);
            color:white; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            font-size: 18px;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-left">
            <div class="logo" onclick="goToDashboard()" style="cursor:pointer;">ECO<span>SWAP</span></div>
            <div class="search-container">
                <input type="text" class="search-input" id="searchInput" placeholder="Cari barang bekas berkualitas...">
                <i class="fas fa-search search-icon"></i>
            </div>
        </div>
        <div class="nav-right">
            <button class="nav-icon-btn" onclick="toggleCart()">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-badge" id="navCartBadge" style="<?php echo $cart_count > 0 ? '' : 'display:none;'; ?>"><?php echo $cart_count; ?></span>
            </button>
            <button class="nav-icon-btn" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <?php if($notif_count > 0): ?><span class="notif-badge"><?php echo $notif_count; ?></span><?php endif; ?>
            </button>
            <button class="nav-icon-btn" onclick="toggleChat()"><i class="fas fa-comment-dots"></i></button>
            <div class="user-avatar" onclick="window.location.href='profil.php'"><img src="<?php echo $user_avatar; ?>" alt="User"></div>
        </div>
    </nav>

    <div class="container">
        <div class="hero-section">
            <div class="carousel-track" id="carouselTrack">
                <div class="carousel-slide">
                    <img src="https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&q=80&w=1200">
                    <div class="hero-text"><h1>Barang Bekas <br><span>Berkualitas</span></h1><p>Hemat uang dan selamatkan bumi.</p></div>
                </div>
                <div class="carousel-slide">
                    <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&q=80&w=1200">
                    <div class="hero-text"><h1>Elektronik <br><span>Murah</span></h1><p>Upgrade gadget tanpa bikin kantong bolong.</p></div>
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

    <!-- Product Modal -->
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

    <!-- Lightbox for chat images -->
    <div class="img-lightbox" id="imgLightbox" onclick="closeLightbox(event)">
        <div class="close-x" onclick="closeLightbox(event)"><i class="fas fa-times"></i></div>
        <img id="lightboxImg" src="" alt="Preview">
    </div>

<script>
    const goToDashboard = () => window.location.href = 'dashboard.php';

    let products = <?php echo json_encode($all_products); ?>;
    const chatPartners = <?php echo json_encode($chat_partners); ?>;

    let currentActiveProduct = null;
    let selectedAddressId = <?php echo $default_addr ? (int)$default_addr['address_id'] : 'null'; ?>;

    document.addEventListener('DOMContentLoaded', () => {
        renderProducts(products);

        // close context menu if click outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.message-bubble')) {
                const cm = document.getElementById('chatContextMenu');
                if(cm) cm.style.display = 'none';
            }
        });

        // SEARCH (live)
        const searchInput = document.getElementById('searchInput');
        if(searchInput){
            let t=null;
            searchInput.addEventListener('input', () => {
                clearTimeout(t);
                t = setTimeout(() => {
                    const q = searchInput.value.trim().toLowerCase();
                    if(!q){ renderProducts(products); return; }
                    const filtered = products.filter(p => {
                        const hay = `${p.title||''} ${p.desc||''} ${p.category||''} ${p.shop_name||''} ${p.loc||''}`.toLowerCase();
                        return hay.includes(q);
                    });
                    renderProducts(filtered);
                }, 150);
            });
        }
    });

    // -------- PRODUCTS ----------
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
                    <div class="product-price">Rp ${Number(p.price||0).toLocaleString('id-ID')}</div>
                    <div class="product-meta"><i class="fas fa-map-marker-alt"></i> ${p.loc}</div>
                </div>
            `;
            productGrid.appendChild(card);
        });
    }

    function filterCategory(btn, cat) {
        document.querySelectorAll('.category-pill').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');

        const grid = document.getElementById('productGrid');
        grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #888;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><br>Memuat...</div>';

        fetch(`dashboard.php?action=filter_products&category=${encodeURIComponent(cat)}`)
            .then(r => r.json())
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
        document.getElementById('modalPrice').textContent = 'Rp ' + Number(product.price||0).toLocaleString('id-ID');
        document.getElementById('modalDesc').textContent = product.desc;

        const metaRow = document.querySelector('.modal-meta-row');
        if(metaRow) metaRow.innerHTML = `<span style="color:#555; font-weight:600;">Kondisi: <span id="modalCond" style="font-weight:normal; margin-left:4px; color:#333;">${product.cond}</span></span>`;

        const catContainer = document.getElementById('modalCategoryBadge');
        if(catContainer) catContainer.innerHTML = `<span class="modal-category-badge">${product.category || 'Umum'}</span>`;

        const shopContainer = document.getElementById('modalShopContainer');
        const isFollowing = !!product.is_following;
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
            <button class="${followClass}" onclick="toggleFollow(${product.shop_id}, this)">
                ${followIcon} ${followText}
            </button>
        `;

        const btnChat = document.getElementById('btnModalChat');
        const newBtnChat = btnChat.cloneNode(true);
        btnChat.parentNode.replaceChild(newBtnChat, btnChat);
        newBtnChat.onclick = function() {
            closeModal();
            toggleChat();
            selectChat(product.seller_id);
        };

        modalOverlay.classList.add('open');
        document.body.classList.add('no-scroll'); // existing behavior
    }

    function closeModal() {
        modalOverlay.classList.remove('open');
        document.body.classList.remove('no-scroll');
    }
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
                } else if (data.status === 'unfollowed') {
                    btn.classList.remove('following');
                    btn.innerHTML = '<i class="fas fa-plus"></i> Ikuti';
                }
            })
            .finally(() => { btn.disabled = false; });
    }

    // ------------- CART/NOTIF/CHAT TOGGLE -------------
    const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n);

    function toggleCart() {
        document.getElementById('cartSidebar').classList.toggle('open');
        document.getElementById('cartOverlay').classList.toggle('open');
        updateCartTotal();
    }
    function toggleNotifications() {
        document.getElementById('notifSidebar').classList.toggle('open');
        document.getElementById('notifOverlay').classList.toggle('open');
    }

    function toggleChat() {
        const sidebar = document.getElementById('chatSidebar');
        const overlay = document.getElementById('chatOverlay');

        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');

        // IMPORTANT: lock background scroll when chat open
        const isOpen = sidebar.classList.contains('open');
        document.body.classList.toggle('no-scroll-chat', isOpen);

        // auto scroll when open and already in chat area
        if(isOpen && document.getElementById('chatAreaSidebar').style.display === 'flex'){
            setTimeout(() => {
                const c = document.getElementById('chatMessagesSidebar');
                if(c) c.scrollTop = c.scrollHeight;
            }, 50);
        }
    }

    function updateCartTotal() {
        let t=0;
        document.querySelectorAll('.cart-item').forEach(el=>{
            if(el.querySelector('.cart-checkbox')?.checked) t += parseInt(el.getAttribute('data-price'));
        });
        const el = document.getElementById('cartTotalPrice');
        if(el) el.innerText = formatRupiah(t);
    }

    // ==========================================================
    // CHAT BUYER
    // ==========================================================
    let currentChatPartnerId = null;
    let currentPartner = null;
    let messages = [];
    let chatPollTimer = null;

    async function loadChat(partnerId){
        const res = await fetch(`dashboard.php?action=get_chat&partner_id=${partnerId}`);
        const data = await res.json();
        if(data.status !== 'success') return;

        currentChatPartnerId = partnerId;
        currentPartner = data.partner;
        messages = data.messages || [];

        document.getElementById('chatSellerNameSidebar').textContent = currentPartner.name;
        document.getElementById('chatSellerAvatarSidebar').src = currentPartner.img;

        renderMessagesSidebar(true);
    }

    function selectChat(pid){
        document.getElementById('chatItemsContainer').style.display='none';
        document.getElementById('chatAreaSidebar').style.display='flex';

        loadChat(pid);

        if(chatPollTimer) clearInterval(chatPollTimer);
        chatPollTimer = setInterval(async () => {
            if(!currentChatPartnerId) return;
            const res = await fetch(`dashboard.php?action=get_chat&partner_id=${currentChatPartnerId}`);
            const data = await res.json();
            if(data.status === 'success'){
                const newMsgs = data.messages || [];
                if(newMsgs.length !== messages.length || (newMsgs.at(-1)?.id !== messages.at(-1)?.id)){
                    messages = newMsgs;
                    renderMessagesSidebar(false);
                }
            }
        }, 2000);
    }

    function backToChatList(){
        document.getElementById('chatItemsContainer').style.display='block';
        document.getElementById('chatAreaSidebar').style.display='none';
        currentChatPartnerId = null;
        currentPartner = null;
        messages = [];
        if(chatPollTimer) clearInterval(chatPollTimer);
        chatPollTimer = null;
    }

    function escapeHtml(str){
        return String(str)
            .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
            .replaceAll('"','&quot;').replaceAll("'","&#039;");
    }

    function openLightbox(src){
        const lb = document.getElementById('imgLightbox');
        const img = document.getElementById('lightboxImg');
        img.src = src;
        lb.classList.add('open');
    }
    function closeLightbox(e){
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('imgLightbox').classList.remove('open');
        document.getElementById('lightboxImg').src = '';
    }

    function renderMessagesSidebar(forceScroll){
        const c=document.getElementById('chatMessagesSidebar');
        c.innerHTML='';

        if(messages.length===0){
            c.innerHTML='<div style="text-align:center;padding:20px;color:#888;">Belum ada pesan.</div>';
            return;
        }

        messages.forEach(m=>{
            const bubbleClass = (m.type === 'outgoing') ? 'msg-me' : 'msg-other';
            let content = '';

            if (m.img){
                const safeSrc = m.img;
                content += `<img src="${safeSrc}" class="chat-img" style="max-width:220px;border-radius:10px;display:block;margin-bottom:${m.text ? '8px' : '0'};cursor:zoom-in;" onclick="openLightbox('${safeSrc}')">`;
            }
            if (m.text) content += `<div>${escapeHtml(m.text)}</div>`;

            c.innerHTML += `
                <div class="message-wrapper ${m.type}">
                    <div class="message-bubble ${bubbleClass}" onclick="showChatOptions(event, ${m.id}, '${m.type}')">
                        ${content}
                    </div>
                    <span class="message-time">${m.time}</span>
                </div>`;
        });

        if(forceScroll){
            c.scrollTop = c.scrollHeight;
        } else {
            // auto scroll if already near bottom
            const nearBottom = (c.scrollHeight - (c.scrollTop + c.clientHeight)) < 120;
            if(nearBottom) c.scrollTop = c.scrollHeight;
        }
    }

    // CONTEXT MENU
    const contextMenu = document.getElementById('chatContextMenu');
    function showChatOptions(e, id, type) {
        e.stopPropagation();
        contextMenu.innerHTML = '';

        if (type === 'outgoing') {
            contextMenu.innerHTML = `<div class="chat-context-item danger" onclick="deleteMessage(${id})"><i class="fas fa-trash"></i> Hapus Pesan</div>`;
        } else {
            contextMenu.innerHTML = `<div class="chat-context-item danger" onclick="reportMessage(${id})"><i class="fas fa-flag"></i> Laporkan Pesan</div>`;
        }

        contextMenu.style.top = e.pageY + 'px';
        contextMenu.style.left = e.pageX + 'px';
        contextMenu.style.display = 'block';
    }

    async function deleteMessage(id) {
        if(!confirm("Hapus pesan ini?")) return;
        const fd = new FormData();
        fd.append('action','delete_chat');
        fd.append('chat_id', id);

        const res = await fetch('dashboard.php',{method:'POST',body:fd});
        const data = await res.json();
        if(data.status==='success'){
            messages = messages.filter(m => m.id !== id);
            renderMessagesSidebar(false);
            contextMenu.style.display='none';
        } else alert('Gagal hapus pesan.');
    }

    async function reportMessage(id) {
        const reason = prompt("Masukkan alasan pelaporan:");
        if(!reason) return;

        const fd = new FormData();
        fd.append('action','report_chat');
        fd.append('chat_id', id);
        fd.append('reason', reason);

        const res = await fetch('dashboard.php',{method:'POST',body:fd});
        const data = await res.json();
        if(data.status==='success') alert("Laporan terkirim. Admin akan meninjau.");
        else alert("Gagal mengirim laporan.");
        contextMenu.style.display='none';
    }

    // SEND TEXT
    async function sendMessageSidebar(){
        const inp=document.getElementById('messageInputSidebar');
        const txt=inp.value.trim();
        if(!txt || !currentChatPartnerId) return;

        inp.value = '';

        const temp = { id: -Date.now(), type:'outgoing', text:txt, img:null, time:'...' };
        messages.push(temp);
        renderMessagesSidebar(true);

        const fd = new FormData();
        fd.append('action','send_message');
        fd.append('receiver_id', currentChatPartnerId);
        fd.append('message', txt);

        const res = await fetch('dashboard.php',{method:'POST',body:fd});
        const data = await res.json();

        if(data.status==='success'){
            const idx = messages.findIndex(m => m.id === temp.id);
            if(idx >= 0){
                messages[idx].id = data.chat_id;
                messages[idx].time = data.time;
                renderMessagesSidebar(true);
            }
        } else {
            alert('Gagal mengirim pesan.');
        }
    }
    function handleEnterSidebar(e){ if(e.key==='Enter') sendMessageSidebar(); }

    // SEND IMAGE
    function openFileInput(){ document.getElementById('fileInput').click(); }

    async function sendImage(){
        if(!currentChatPartnerId) return;
        const fileInput = document.getElementById('fileInput');
        const file = fileInput.files[0];
        if(!file) return;

        const localUrl = URL.createObjectURL(file);
        const temp = { id: -Date.now(), type:'outgoing', text:'', img:localUrl, time:'...' };
        messages.push(temp);
        renderMessagesSidebar(true);

        const fd = new FormData();
        fd.append('action','send_message');
        fd.append('receiver_id', currentChatPartnerId);
        fd.append('message', '');
        fd.append('image', file);

        const res = await fetch('dashboard.php',{method:'POST',body:fd});
        const data = await res.json();

        if(data.status==='success'){
            const idx = messages.findIndex(m => m.id === temp.id);
            if(idx >= 0){
                messages[idx].id = data.chat_id;
                messages[idx].img = data.image || messages[idx].img;
                messages[idx].time = data.time;
                renderMessagesSidebar(true);
            }
        } else {
            alert('Gagal mengirim foto.');
        }

        fileInput.value = '';
    }

    // carousel
    const track=document.getElementById('carouselTrack'); let ci=0;
    setInterval(()=>{ ci=(ci+1)%2; track.style.transform=`translateX(-${ci*100}%)`; },5000);

    // ========== CART & CHECKOUT FUNCTIONS ==========
    
    function addToCart() {
        if (!currentActiveProduct) {
            alert('Produk tidak ditemukan');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'add_to_cart');
        formData.append('product_id', currentActiveProduct.id);

        fetch('dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Produk berhasil ditambahkan ke keranjang!');
                
                // Update cart badge
                const badge = document.getElementById('navCartBadge');
                if (badge) {
                    badge.textContent = data.new_count;
                    badge.style.display = data.new_count > 0 ? 'block' : 'none';
                }
                
                // Reload cart items
                location.reload();
            } else {
                alert('Gagal menambahkan ke keranjang');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Terjadi kesalahan');
        });
    }

    function buyNow() {
        if (!currentActiveProduct) {
            alert('Produk tidak ditemukan');
            return;
        }

        // Close product modal first
        closeModal();

        // Open checkout modal with single product
        openCheckoutModal([{
            id: currentActiveProduct.id,
            name: currentActiveProduct.title,
            price: currentActiveProduct.price,
            img: currentActiveProduct.img,
            shop_id: currentActiveProduct.shop_id,
            cart_id: null // not from cart
        }]);
    }

    // ========== CHECKOUT MODAL FUNCTIONS ==========
    
    function openCheckoutModal(items) {
        if (!items || items.length === 0) {
            alert('Tidak ada produk yang dipilih');
            return;
        }

        // Check if address exists
        <?php if (!$default_addr): ?>
            alert('Silakan atur alamat pengiriman terlebih dahulu di menu Profil > Alamat');
            return;
        <?php endif; ?>

        // Render product list in checkout
        const productListContainer = document.getElementById('checkoutProductList');
        productListContainer.innerHTML = '';
        
        let subtotal = 0;
        items.forEach(item => {
            subtotal += parseInt(item.price);
            productListContainer.innerHTML += `
                <div style="display:flex; gap:12px; padding:12px; border:1px solid #eee; border-radius:8px; margin-bottom:10px;">
                    <img src="${item.img}" style="width:60px; height:60px; object-fit:cover; border-radius:6px;">
                    <div style="flex:1;">
                        <div style="font-weight:600; margin-bottom:4px;">${item.name}</div>
                        <div style="color:var(--primary); font-weight:600;">Rp ${Number(item.price).toLocaleString('id-ID')}</div>
                    </div>
                </div>
            `;
        });

        // Update summary
        document.getElementById('summaryProdPrice').textContent = formatRupiah(subtotal);
        
        // Load shipping & payment options for the shop
        const shopId = items[0].shop_id;
        loadShopSettings(shopId, subtotal);

        // Store items globally for checkout
        window.checkoutItems = items;

        // Show checkout modal
        document.getElementById('checkoutModal').classList.add('open');
    }

    function closeCheckoutModal() {
        document.getElementById('checkoutModal').classList.remove('open');
        window.checkoutItems = null;
    }

    async function loadShopSettings(shopId, subtotal) {
        const fd = new FormData();
        fd.append('action', 'get_shop_settings');
        fd.append('shop_id', shopId);

        const res = await fetch('dashboard.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.status === 'success') {
            renderShippingOptions(data.shipping || [], subtotal);
            renderPaymentOptions(data.payment || []);
        }
    }

    let selectedShipping = null;
    let selectedPayment = null;

    function renderShippingOptions(options, subtotal) {
        const container = document.getElementById('shippingContainer');
        container.innerHTML = '';

        if (options.length === 0) {
            container.innerHTML = '<div style="color:#888; text-align:center; padding:15px;">Belum ada opsi pengiriman</div>';
            return;
        }

        options.forEach((opt, idx) => {
            const isActive = idx === 0;
            if (isActive) {
                selectedShipping = { name: opt.name, cost: opt.cost };
                updateCheckoutSummary(subtotal);
            }

            container.innerHTML += `
                <div class="shipping-option-card ${isActive ? 'active' : ''}" onclick="selectShipping(this, '${opt.name}', ${opt.cost}, ${subtotal})">
                    <div class="ship-info">
                        <div class="ship-name">${opt.name}</div>
                        <div class="ship-price">Rp ${Number(opt.cost).toLocaleString('id-ID')}</div>
                    </div>
                    <i class="fas fa-check-circle" style="color:var(--primary); font-size:1.2rem; display:${isActive ? 'block' : 'none'};"></i>
                </div>
            `;
        });
    }

    function selectShipping(element, name, cost, subtotal) {
        document.querySelectorAll('.shipping-option-card').forEach(el => {
            el.classList.remove('active');
            el.querySelector('i').style.display = 'none';
        });
        
        element.classList.add('active');
        element.querySelector('i').style.display = 'block';
        
        selectedShipping = { name, cost };
        updateCheckoutSummary(subtotal);
    }

    function renderPaymentOptions(options) {
        const container = document.getElementById('paymentContainer');
        container.innerHTML = '';

        if (options.length === 0) {
            container.innerHTML = '<div style="color:#888; text-align:center; padding:15px;">Belum ada metode pembayaran</div>';
            return;
        }

        const grouped = {};
        options.forEach(opt => {
            if (!grouped[opt.category]) grouped[opt.category] = [];
            grouped[opt.category].push(opt.name);
        });

        let firstSelected = false;
        Object.keys(grouped).forEach(cat => {
            const catId = cat.replace(/\s+/g, '');
            const methods = grouped[cat];
            
            container.innerHTML += `
                <div class="payment-category" id="cat-${catId}">
                    <div class="payment-header" onclick="togglePaymentCategory('${catId}')">
                        <div class="ph-left">
                            <i class="fas fa-${cat === 'E-Wallet' ? 'wallet' : cat === 'Transfer Bank' ? 'university' : 'money-bill-wave'}"></i>
                            <span>${cat}</span>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="payment-options-list" id="list-${catId}">
                        ${methods.map(m => {
                            const isFirst = !firstSelected;
                            if (isFirst) {
                                firstSelected = true;
                                selectedPayment = m;
                            }
                            return `<div class="sub-option ${isFirst ? 'selected' : ''}" onclick="selectPayment(this, '${m}')">${m}</div>`;
                        }).join('')}
                    </div>
                </div>
            `;
        });
    }

    function togglePaymentCategory(catId) {
        const list = document.getElementById('list-' + catId);
        const cat = document.getElementById('cat-' + catId);
        
        if (list.classList.contains('show')) {
            list.classList.remove('show');
            cat.classList.remove('active');
        } else {
            // Close all others
            document.querySelectorAll('.payment-options-list').forEach(el => el.classList.remove('show'));
            document.querySelectorAll('.payment-category').forEach(el => el.classList.remove('active'));
            
            list.classList.add('show');
            cat.classList.add('active');
        }
    }

    function selectPayment(element, method) {
        document.querySelectorAll('.sub-option').forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');
        selectedPayment = method;
    }

    function updateCheckoutSummary(subtotal) {
        const shippingCost = selectedShipping ? selectedShipping.cost : 0;
        const adminFee = <?php echo $admin_fee; ?>;
        const total = subtotal + shippingCost + adminFee;

        document.getElementById('summaryShipPrice').textContent = formatRupiah(shippingCost);
        document.getElementById('summaryTotal').textContent = formatRupiah(total);
        document.getElementById('bottomTotal').textContent = formatRupiah(total);
    }

    function openAddressModal() {
        document.getElementById('addressModal').classList.add('open');
    }

    function closeAddressModal() {
        document.getElementById('addressModal').classList.remove('open');
    }

    function selectAddress(element, name, address, phone, label) {
        document.querySelectorAll('.address-option').forEach(el => el.classList.remove('selected'));
        element.classList.add('selected');
        closeAddressModal();
        
        // Update display in checkout
        const addrBox = document.querySelector('.address-box');
        if (addrBox) {
            addrBox.innerHTML = `
                <div class="addr-header-row">
                    <span class="addr-recipient">${name}</span>
                    <span class="addr-divider">|</span>
                    <span class="addr-phone">${phone}</span>
                    <span class="addr-label-tag">${label || ''}</span>
                </div>
                <div class="addr-body-text">${address}</div>
                <div class="addr-change-text">Ubah Alamat <i class="fas fa-chevron-right"></i></div>
            `;
        }
    }

    async function processOrder() {
        if (!window.checkoutItems || window.checkoutItems.length === 0) {
            alert('Tidak ada produk yang dipilih');
            return;
        }

        if (!selectedShipping) {
            alert('Pilih metode pengiriman terlebih dahulu');
            return;
        }

        if (!selectedPayment) {
            alert('Pilih metode pembayaran terlebih dahulu');
            return;
        }

        if (!selectedAddressId) {
            alert('Pilih alamat pengiriman terlebih dahulu');
            return;
        }

        const fd = new FormData();
        fd.append('action', 'create_order');
        fd.append('address_id', selectedAddressId);
        fd.append('shipping_method', selectedShipping.name);
        fd.append('shipping_cost', selectedShipping.cost);
        fd.append('payment_method', selectedPayment);
        fd.append('items', JSON.stringify(window.checkoutItems));

        const res = await fetch('dashboard.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.status === 'success') {
            alert('Pesanan berhasil dibuat! Silakan cek menu Histori untuk detail pesanan.');
            closeCheckoutModal();
            location.reload();
        } else {
            alert('Gagal membuat pesanan: ' + (data.message || 'Unknown error'));
        }
    }

    // ========== CART ITEM FUNCTIONS ==========
    
    function toggleCartItem(element) {
        const checkbox = element.querySelector('.cart-checkbox');
        if (checkbox) {
            checkbox.checked = !checkbox.checked;
            updateCartTotal();
        }
    }

    function deleteCartItem(event, cartId) {
        event.stopPropagation();
        
        if (!confirm('Hapus produk dari keranjang?')) return;

        const fd = new FormData();
        fd.append('action', 'delete_item');
        fd.append('cart_id', cartId);

        fetch('dashboard.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update badge
                    const badge = document.getElementById('navCartBadge');
                    if (badge) {
                        badge.textContent = data.new_count;
                        badge.style.display = data.new_count > 0 ? 'block' : 'none';
                    }
                    
                    // Reload page
                    location.reload();
                } else {
                    alert('Gagal menghapus item');
                }
            });
    }

    function checkoutFromCart() {
        const selectedItems = [];
        
        document.querySelectorAll('.cart-item').forEach(el => {
            const checkbox = el.querySelector('.cart-checkbox');
            if (checkbox && checkbox.checked) {
                selectedItems.push({
                    id: parseInt(el.getAttribute('data-id')),
                    name: el.getAttribute('data-name'),
                    price: parseInt(el.getAttribute('data-price')),
                    img: el.getAttribute('data-img'),
                    shop_id: parseInt(el.getAttribute('data-shop-id')),
                    cart_id: parseInt(el.getAttribute('data-id'))
                });
            }
        });

        if (selectedItems.length === 0) {
            alert('Pilih minimal 1 produk untuk checkout');
            return;
        }

        // Close cart sidebar
        toggleCart();

        // Open checkout modal
        openCheckoutModal(selectedItems);
    }
</script>

</body>
</html>
