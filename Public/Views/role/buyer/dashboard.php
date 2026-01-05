<?php
session_start();
include '../../../Auth/koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) { header("Location: ../../auth/login.php"); exit(); }
$user_id = $_SESSION['user_id'];

// AJAX Handler: Follow
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

// AJAX Handler: Chat
if (isset($_POST['action']) && $_POST['action'] == 'send_message') {
    header('Content-Type: application/json');
    $receiver_id = $_POST['receiver_id'];
    $message = mysqli_real_escape_string($koneksi, $_POST['message']);
    if(!empty($message) && !empty($receiver_id)) {
        $insert = mysqli_query($koneksi, "INSERT INTO chats (sender_id, receiver_id, message) VALUES ('$user_id', '$receiver_id', '$message')");
        if($insert) echo json_encode(['status' => 'success']);
        else echo json_encode(['status' => 'error', 'message' => mysqli_error($koneksi)]);
    } else { echo json_encode(['status' => 'empty']); }
    exit;
}

// 1. Data User
$q_user = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id = '$user_id'");
$d_user = mysqli_fetch_assoc($q_user);
$user_name = !empty($d_user['name']) ? $d_user['name'] : explode('@', $d_user['email'])[0];
$user_avatar = !empty($d_user['profile_picture']) ? $d_user['profile_picture'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($user_name);

// 2. Filter & Produk
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'Semua';
$where_clause = "WHERE p.status = 'active'";
if ($category_filter != 'Semua') {
    $safe_cat = mysqli_real_escape_string($koneksi, $category_filter);
    if ($safe_cat == 'Fashion') $where_clause .= " AND (p.category = 'Fashion Pria' OR p.category = 'Fashion Wanita')";
    elseif ($safe_cat == 'Hobi') $where_clause .= " AND p.category = 'Hobi & Koleksi'";
    else $where_clause .= " AND p.category = '$safe_cat'";
}

$all_products = [];
// UPDATE QUERY: Mengambil shop_city dari tabel shops
$query_prod = mysqli_query($koneksi, "SELECT p.*, s.shop_name, s.shop_image, s.shop_id, s.shop_city, s.shop_address FROM products p JOIN shops s ON p.shop_id = s.shop_id $where_clause ORDER BY p.created_at DESC");

while($row = mysqli_fetch_assoc($query_prod)) {
    $shop_id_prod = $row['shop_id'];
    $is_following = false;
    $q_check = mysqli_query($koneksi, "SELECT 1 FROM shop_followers WHERE shop_id='$shop_id_prod' AND user_id='$user_id'");
    if($q_check && mysqli_num_rows($q_check) > 0) $is_following = true;

    // LOGIKA ALAMAT: Prioritaskan shop_city
    if (!empty($row['shop_city'])) {
        $loc_display = $row['shop_city'];
    } else {
        // Fallback jika kota belum disetting (misal data lama), ambil potongan pertama dari alamat lengkap
        $loc_display = !empty($row['shop_address']) ? explode(',', $row['shop_address'])[0] : 'Indonesia';
    }
    
    $all_products[] = [
        'id' => $row['product_id'], 
        'title' => $row['name'], 
        'price' => (int)$row['price'], 
        'loc' => $loc_display, // Hanya Kota
        'img' => $row['image'], 
        'cond' => $row['condition'], 
        'desc' => $row['description'], 
        'category' => $row['category'], 
        'shop_name' => $row['shop_name'], 
        'shop_img' => $row['shop_image'], 
        'shop_id' => $row['shop_id'], 
        'shop_address' => $loc_display, // Hanya Kota untuk di modal juga
        'is_following' => $is_following
    ];
}

// 3. Keranjang
$cart_items = []; $cart_total = 0;
$q_cart = mysqli_query($koneksi, "SELECT c.cart_id, p.product_id, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = '$user_id' ORDER BY c.created_at DESC");
while($row = mysqli_fetch_assoc($q_cart)){ $cart_items[] = $row; $cart_total += $row['price']; }
$cart_count = count($cart_items);

// 4. Notifikasi
$notif_items = [];
$q_notif = mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 10");
while($row = mysqli_fetch_assoc($q_notif)){ $notif_items[] = $row; }
$notif_count = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id='$user_id' AND is_read=0"));

// 5. Alamat
$addresses = [];
$q_addr = mysqli_query($koneksi, "SELECT * FROM addresses WHERE user_id = '$user_id' ORDER BY is_primary DESC");
while($row = mysqli_fetch_assoc($q_addr)){ $addresses[] = $row; }
$default_addr = !empty($addresses) ? $addresses[0] : null;

// 6. Chat
$chat_partners = []; $chat_messages_grouped = [];
$q_chat = mysqli_query($koneksi, "SELECT c.*, sender.user_id as s_id, sender.name as s_name, receiver.user_id as r_id, receiver.name as r_name FROM chats c JOIN users sender ON c.sender_id = sender.user_id JOIN users receiver ON c.receiver_id = receiver.user_id WHERE c.sender_id = '$user_id' OR c.receiver_id = '$user_id' ORDER BY c.created_at ASC");

while($row = mysqli_fetch_assoc($q_chat)){
    if($row['sender_id'] == $user_id){ $pid = $row['receiver_id']; $pname = $row['r_name']; $type = 'outgoing'; } 
    else { $pid = $row['sender_id']; $pname = $row['s_name']; $type = 'incoming'; }
    $chat_messages_grouped[$pid][] = ['id'=>$row['chat_id'], 'type'=>$type, 'text'=>$row['message'], 'time'=>date('H:i', strtotime($row['created_at']))];
    if(!isset($chat_partners[$pid])) { $chat_partners[$pid] = ['id'=>$pid, 'name'=>$pname, 'last_msg'=>$row['message'], 'time'=>date('H:i', strtotime($row['created_at']))]; } 
    else { $chat_partners[$pid]['last_msg'] = $row['message']; $chat_partners[$pid]['time'] = date('H:i', strtotime($row['created_at'])); }
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
                <div class="carousel-slide"><img src="https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&q=80&w=1200"><div class="hero-text"><h1>Barang Bekas <br><span>Berkualitas</span></h1><p>Hemat uang dan selamatkan bumi.</p></div></div>
                <div class="carousel-slide"><img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&q=80&w=1200"><div class="hero-text"><h1>Elektronik <br><span>Murah</span></h1><p>Upgrade gadget tanpa bikin kantong bolong.</p></div></div>
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

    <?php include 'checkout.php'; ?>
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
            // Menampilkan hanya kota di card
            card.innerHTML = `<div class="product-img-wrapper"><img src="${p.img}"></div><div class="product-info"><div class="product-title">${p.title}</div><div class="product-price">Rp ${p.price.toLocaleString('id-ID')}</div><div class="product-meta"><i class="fas fa-map-marker-alt"></i> ${p.loc}</div></div>`;
            productGrid.appendChild(card);
        });

        // Modal Logic
        const modalOverlay = document.getElementById('productModal');
        function openModal(product) {
            document.getElementById('modalImg').src = product.img;
            document.getElementById('modalTitle').textContent = product.title;
            document.getElementById('modalPrice').textContent = 'Rp ' + product.price.toLocaleString('id-ID');
            document.getElementById('modalDesc').textContent = product.desc;
            
            const metaRow = document.querySelector('.modal-meta-row');
            if(metaRow) metaRow.innerHTML = `<span style="color:#555; font-weight:600;">Kondisi: <span id="modalCond" style="font-weight:normal; margin-left:4px; color:#333;">${product.cond}</span></span>`;
            
            const catContainer = document.getElementById('modalCategoryBadge');
            if(catContainer) catContainer.innerHTML = `<span class="modal-category-badge">${product.category || 'Umum'}</span>`;

            const shopContainer = document.getElementById('modalShopContainer');
            
            // LOGIKA BUTTON FOLLOW (Icon + Text)
            const followIcon = product.is_following ? '<i class="fas fa-check"></i>' : '<i class="fas fa-plus"></i>';
            const followText = product.is_following ? ' Mengikuti' : ' Ikuti';
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
                <button class="${followClass}" onclick="toggleFollow(${product.shop_id}, this, '${product.shop_name}')">${followIcon}${followText}</button>
            `;

            const btnChat = document.getElementById('btnModalChat'); 
            const newBtnChat = btnChat.cloneNode(true); 
            btnChat.parentNode.replaceChild(newBtnChat, btnChat);
            newBtnChat.onclick = function() { closeModal(); toggleChat(); selectChat(product.shop_id, product.shop_name, shopImg); };

            modalOverlay.classList.add('open');
            document.body.style.overflow = 'hidden'; 
        }

        function closeModal() { modalOverlay.classList.remove('open'); document.body.style.overflow = 'auto'; }
        modalOverlay.addEventListener('click', (e) => { if (e.target === modalOverlay) closeModal(); });

        function toggleFollow(shopId, btn, shopName) {
            btn.disabled = true; 
            // Simpan icon lama sementara loading
            const oldHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; 
            
            const formData = new FormData(); formData.append('action', 'toggle_follow'); formData.append('shop_id', shopId);
            fetch('dashboard.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
                if (data.status === 'followed') { 
                    btn.classList.add('following'); 
                    // Update Icon dan Text ke Mengikuti
                    btn.innerHTML = '<i class="fas fa-check"></i> Mengikuti'; 
                    alert(`Berhasil mengikuti ${shopName}! Anda akan mendapatkan notifikasi produk baru.`); 
                } 
                else if (data.status === 'unfollowed') { 
                    btn.classList.remove('following'); 
                    // Update Icon dan Text ke Ikuti
                    btn.innerHTML = '<i class="fas fa-plus"></i> Ikuti'; 
                }
            }).catch(err => {
                console.error(err);
                btn.innerHTML = oldHtml; // Kembalikan jika error
            }).finally(() => { btn.disabled = false; });
        }

        // --- CHECKOUT LOGIC ---
        const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n);
        let checkoutProductPriceTotal = 0, checkoutShippingPrice = 0; const checkoutServiceFee = 1000; let activePaymentCategory = null;

        function checkoutFromCart() {
            const items = [];
            document.querySelectorAll('.cart-item').forEach(el => {
                if(el.querySelector('.cart-checkbox').checked) {
                    items.push({
                        title: el.getAttribute('data-name'),
                        price: parseInt(el.getAttribute('data-price')),
                        img: el.getAttribute('data-img')
                    });
                }
            });
            if(items.length===0) { alert("Pilih minimal satu barang."); return; }
            localStorage.setItem('checkoutItems', JSON.stringify(items));
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
            container.innerHTML = ''; checkoutProductPriceTotal = 0;
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
            // Reset UI
            document.getElementById('shippingSelect').selectedIndex = 0;
            activePaymentCategory = null;
            document.querySelectorAll('.payment-category').forEach(el => {
                 el.classList.remove('active');
                 el.querySelector('.check-circle').className = 'far fa-circle check-circle';
            });
            calculateCheckoutTotal();
            document.getElementById('checkoutModal').classList.add('open');
        }

        function closeCheckoutModal() { document.getElementById('checkoutModal').classList.remove('open'); }

        function calculateCheckoutTotal() {
            const ship = parseInt(document.getElementById('shippingSelect').value)||0; 
            const total = checkoutProductPriceTotal + ship + checkoutServiceFee;
            document.getElementById('summaryProdPrice').innerText = formatRupiah(checkoutProductPriceTotal);
            document.getElementById('summaryShipPrice').innerText = formatRupiah(ship);
            document.getElementById('summaryTotal').innerText = formatRupiah(total);
            document.getElementById('bottomTotal').innerText = formatRupiah(total);
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

        function togglePaymentDropdown(e, id) { e.stopPropagation(); document.getElementById(id).classList.toggle('show'); }
        function selectPaymentSubOption(cat, val, name) { selectPaymentCategory(cat); document.getElementById('list-'+cat).classList.remove('show'); }

        function processOrder() { 
            if (document.getElementById('shippingSelect').value === "0") { alert("Pilih pengiriman."); return; }
            if (!activePaymentCategory) { alert("Pilih metode pembayaran."); return; }
            alert('Pesanan berhasil dibuat!'); closeCheckoutModal(); 
        }

        // --- CART ROW CLICK ---
        function toggleCartItem(el) {
            const cb = el.querySelector('.cart-checkbox');
            cb.checked = !cb.checked;
            updateCartTotal();
        }

        // --- SIDEBARS ---
        function toggleCart() { document.getElementById('cartSidebar').classList.toggle('open'); document.getElementById('cartOverlay').classList.toggle('open'); updateCartTotal(); }
        function toggleNotifications() { document.getElementById('notifSidebar').classList.toggle('open'); document.getElementById('notifOverlay').classList.toggle('open'); }
        function toggleChat() { document.getElementById('chatSidebar').classList.toggle('open'); document.getElementById('chatOverlay').classList.toggle('open'); }
        
        function updateCartTotal() { let t=0; document.querySelectorAll('.cart-item').forEach(el=>{ if(el.querySelector('.cart-checkbox').checked) t+=parseInt(el.getAttribute('data-price')) }); document.getElementById('cartTotalPrice').innerText=formatRupiah(t); }
        function addToCart() { closeModal(); setTimeout(toggleCart, 300); alert("Barang ditambahkan!"); }

        // --- CHAT ---
        let currentChatPartnerId = null; let messages = [];
        function selectChat(pid, name, img=null) {
            currentChatPartnerId = pid; 
            messages = (chatData && chatData[pid]) ? [...chatData[pid]] : [];
            document.getElementById('chatSellerNameSidebar').textContent = name;
            const avatarEl = document.getElementById('chatSellerAvatarSidebar');
            avatarEl.src = img || `https://api.dicebear.com/7.x/avataaars/svg?seed=${name}`;
            renderMessagesSidebar();
            document.getElementById('chatItemsContainer').style.display = 'none';
            document.getElementById('chatAreaSidebar').style.display = 'flex';
        }
        function renderMessagesSidebar() {
            const c = document.getElementById('chatMessagesSidebar'); c.innerHTML = '';
            if(messages.length===0){ c.innerHTML = '<div style="text-align:center; padding:20px; color:#888;">Belum ada pesan.</div>'; return; }
            messages.forEach(m => {
                c.innerHTML += `<div class="message-wrapper ${m.type}"><div class="message-bubble">${m.text}</div><span class="message-time">${m.time}</span></div>`;
            });
            c.scrollTop = c.scrollHeight;
        }
        function sendMessageSidebar() {
            const inp = document.getElementById('messageInputSidebar'); const txt = inp.value.trim();
            if(txt && currentChatPartnerId) {
                messages.push({type:'outgoing', text:txt, time:'Now'}); renderMessagesSidebar(); inp.value='';
                const fd = new FormData(); fd.append('action', 'send_message'); fd.append('receiver_id', currentChatPartnerId); fd.append('message', txt);
                fetch('dashboard.php', { method: 'POST', body: fd });
            }
        }
        function handleEnterSidebar(e) { if(e.key === 'Enter') sendMessageSidebar(); }
        function backToChatList() { document.getElementById('chatItemsContainer').style.display = 'block'; document.getElementById('chatAreaSidebar').style.display = 'none'; }
        
        // --- OTHER ---
        function openAddressModal() { document.getElementById('addressModal').classList.add('open'); }
        function closeAddressModal() { document.getElementById('addressModal').classList.remove('open'); }
        function selectAddress(el, name, detail, phone) { document.getElementById('displayAddrName').innerText=name+' | '+phone; document.getElementById('displayAddrDetail').innerText=detail; closeAddressModal(); }
        const track = document.getElementById('carouselTrack'); let ci = 0;
        setInterval(() => { ci = (ci + 1) % 2; track.style.transform = `translateX(-${ci * 100}%)`; }, 5000);
    </script>
</body>
</html>