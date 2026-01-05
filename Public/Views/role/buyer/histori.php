<?php
session_start();
include '../../../Auth/koneksi.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../../auth/login.php"); exit(); }
$user_id = $_SESSION['user_id'];

// A. Konfirmasi Pesanan Diterima (Delivered -> Completed)
if (isset($_POST['action']) && $_POST['action'] == 'confirm_received') {
    $oid = $_POST['order_id'];
    
    // Ambil Data Toko (untuk ambil user_id penjual)
    $q_ord = mysqli_query($koneksi, "SELECT o.invoice_code, o.shop_id, s.user_id as seller_id 
                                     FROM orders o JOIN shops s ON o.shop_id = s.shop_id 
                                     WHERE o.order_id='$oid'");
    $d_ord = mysqli_fetch_assoc($q_ord);

    if(mysqli_query($koneksi, "UPDATE orders SET status='completed' WHERE order_id='$oid' AND buyer_id='$user_id'")) {
        // Kirim Notif ke Penjual
        $msg = "Pesanan #{$d_ord['invoice_code']} telah diterima oleh pembeli dan selesai.";
        mysqli_query($koneksi, "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ('{$d_ord['seller_id']}', 'Pesanan Selesai', '$msg', 0, NOW())");
        
        // Update Saldo Toko (Simulasi)
        // mysqli_query($koneksi, "UPDATE shops SET balance = balance + (SELECT total_price FROM orders WHERE order_id='$oid') WHERE shop_id='{$d_ord['shop_id']}'");

        echo "<script>alert('Terima kasih! Pesanan selesai.'); window.location.href='histori.php';</script>";
    }
}

// B. Kirim Review
if (isset($_POST['action']) && $_POST['action'] == 'submit_review') {
    $oid = $_POST['order_id']; $pid = $_POST['product_id']; $rating = $_POST['rating'];
    $comment = mysqli_real_escape_string($koneksi, $_POST['comment']);
    $review_img = "";
    if (!empty($_FILES['review_photo']['name'])) {
        $target_dir = "../../../Assets/img/reviews/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_name = time() . "_" . basename($_FILES["review_photo"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["review_photo"]["tmp_name"], $target_file)) $review_img = $target_file;
    }
    $query_review = "INSERT INTO reviews (order_id, product_id, user_id, rating, comment, photo) VALUES ('$oid', '$pid', '$user_id', '$rating', '$comment', '$review_img')";
    if (mysqli_query($koneksi, $query_review)) {
        mysqli_query($koneksi, "UPDATE orders SET status='reviewed' WHERE order_id='$oid'");
        echo "<script>alert('Ulasan dikirim!'); window.location.href='histori.php';</script>";
    }
}

// C. Laporkan
if (isset($_POST['action']) && $_POST['action'] == 'report_seller') {
    $oid = $_POST['order_id']; $sid = $_POST['shop_id']; $reason = mysqli_real_escape_string($koneksi, $_POST['reason']);
    if (mysqli_query($koneksi, "INSERT INTO reports (shop_id, user_id, order_id, reason, status) VALUES ('$sid', '$user_id', '$oid', '$reason', 'pending')")) {
        echo "<script>alert('Laporan dikirim.'); window.location.href='histori.php';</script>";
    }
}

// AMBIL DATA
$history_data = [];
$query = "SELECT o.*, p.name AS product_name, p.image, p.description, s.shop_name 
          FROM orders o JOIN products p ON o.product_id = p.product_id 
          JOIN shops s ON o.shop_id = s.shop_id 
          WHERE o.buyer_id = '$user_id' ORDER BY o.created_at DESC";
$result = mysqli_query($koneksi, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $badge_class = ''; $badge_text = '';
    switch($row['status']) {
        case 'pending': $badge_class = 'status-pending'; $badge_text = 'Menunggu Konfirmasi'; break;
        case 'processed': $badge_class = 'status-proses'; $badge_text = 'Sedang Diproses'; break;
        case 'shipping': $badge_class = 'status-proses'; $badge_text = 'Sedang Dikirim'; break;
        case 'delivered': $badge_class = 'status-delivered'; $badge_text = 'Paket Sampai'; break;
        case 'completed': $badge_class = 'status-selesai'; $badge_text = 'Selesai'; break;
        case 'reviewed': $badge_class = 'status-selesai'; $badge_text = 'Selesai & Dinilai'; break;
        case 'cancelled': $badge_class = 'status-batal'; $badge_text = 'Dibatalkan'; break;
        default: $badge_class = 'status-pending'; $badge_text = $row['status'];
    }

    // Parse Payment
    $parts = explode(' | ', $row['shipping_method']);
    $ship_lbl = $parts[0];
    $pay_lbl = isset($parts[1]) ? $parts[1] : 'Manual';

    $history_data[] = [
        'id' => $row['order_id'],
        'product_id' => $row['product_id'],
        'shop_id' => $row['shop_id'],
        'status_raw' => $row['status'],
        'item' => $row['product_name'],
        'price' => 'Rp ' . number_format($row['total_price'], 0, ',', '.'),
        'desc' => $row['description'],
        'shipping' => $ship_lbl,
        'payment' => $pay_lbl,
        'tracking' => $row['tracking_number'] ? $row['tracking_number'] : '-',
        'counterparty' => $row['shop_name'],
        'date' => date('d M Y', strtotime($row['created_at'])),
        'badge_class' => $badge_class,
        'badge_text' => $badge_text,
        'image' => $row['image']
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histori Aktivitas - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/histori.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; }
        .status-selesai { background-color: #d4edda; color: #155724; }
        .status-proses { background-color: #cce5ff; color: #004085; }
        .status-delivered { background-color: #fff3cd; color: #856404; animation: pulse 2s infinite; }
        .status-pending { background-color: #e2e3e5; color: #383d41; }
        .status-batal { background-color: #f8d7da; color: #721c24; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
        .btn-action-group { display: flex; gap: 5px; justify-content: flex-end; margin-top: 5px; }
        .btn-confirm { background: #28a745; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
        .btn-review { background: #ffc107; color: #000; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
        .btn-report { background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
        .rating-input { display: flex; flex-direction: row-reverse; gap: 5px; justify-content: flex-end; }
        .rating-input input { display: none; }
        .rating-input label { font-size: 1.5rem; color: #ccc; cursor: pointer; }
        .rating-input input:checked ~ label, .rating-input label:hover, .rating-input label:hover ~ label { color: #ffc107; }
    </style>
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-header"><div class="logo" onclick="goToDashboard()" style="cursor:pointer;">ECO<span>SWAP</span></div></div>
            <ul class="sidebar-menu">
                <li class="menu-item"><a href="profil.php" class="menu-link"><i class="fas fa-user"></i><span>Biodata Diri</span></a></li>
                <li class="menu-item"><a href="alamat.php" class="menu-link"><i class="fas fa-map-marker-alt"></i><span>Alamat</span></a></li>
                <li class="menu-item active"><a href="histori.php" class="menu-link"><i class="fas fa-history"></i><span>Histori</span></a></li>
                <li class="menu-item"><a href="../seller/dashboard.php" class="menu-link"><i class="fas fa-store"></i><span>Toko Saya</span></a></li>
            </ul>
            <div class="sidebar-footer"><a href="../../../../index.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </aside>

        <main class="main-content-wrapper">
            <div class="header"><div class="page-title">Histori Aktivitas</div></div>
            <div class="content">
                <div class="history-list">
                    <?php if (empty($history_data)): ?>
                        <div style="text-align:center; padding:40px; color:#888;"><i class="fas fa-history" style="font-size:3rem; margin-bottom:15px;"></i><br>Belum ada riwayat transaksi.</div>
                    <?php else: ?>
                        <?php foreach($history_data as $data): ?>
                        <div class="history-card">
                            <div class="card-left">
                                <div class="item-icon"><img src="<?php echo $data['image']; ?>" style="width:100%; height:100%; object-fit:cover; border-radius:8px;"></div>
                                <div class="item-info">
                                    <div class="badge-container"><span class="status-badge <?php echo $data['badge_class']; ?>"><?php echo $data['badge_text']; ?></span></div>
                                    <div class="item-title"><?php echo $data['item']; ?></div>
                                    <div class="item-date"><?php echo $data['date']; ?> | <?php echo $data['counterparty']; ?></div>
                                </div>
                            </div>
                            <div class="card-right">
                                <div class="item-price"><?php echo $data['price']; ?></div>
                                <div class="btn-action-group">
                                    <?php if($data['status_raw'] == 'delivered'): ?>
                                        <form method="POST" onsubmit="return confirm('Sudah mengecek barang dan yakin ingin menyelesaikannya?')">
                                            <input type="hidden" name="action" value="confirm_received"><input type="hidden" name="order_id" value="<?php echo $data['id']; ?>">
                                            <button type="submit" class="btn-confirm">Pesanan Diterima</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if($data['status_raw'] == 'completed'): ?>
                                        <button class="btn-review" onclick="openReviewModal(<?php echo $data['id']; ?>, <?php echo $data['product_id']; ?>)">Nilai</button>
                                        <button class="btn-report" onclick="openReportModal(<?php echo $data['id']; ?>, <?php echo $data['shop_id']; ?>)">Lapor</button>
                                    <?php endif; ?>
                                    <button class="btn-detail" onclick="openDetail(<?php echo $data['id']; ?>)">Detail</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <div id="detailModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header"><div class="modal-title">Detail Transaksi</div><button class="close-modal" onclick="closeModal('detailModal')">&times;</button></div>
            <div id="modalContent"></div>
        </div>
    </div>

    <div id="reviewModal" class="modal-overlay"><div class="modal-container"><div class="modal-header"><div class="modal-title">Beri Ulasan</div><button class="close-modal" onclick="closeModal('reviewModal')">&times;</button></div><form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="submit_review"><input type="hidden" name="order_id" id="revOrderId"><input type="hidden" name="product_id" id="revProductId"><div class="form-group"><label class="form-label">Rating</label><div class="rating-input"><input type="radio" id="star5" name="rating" value="5" required><label for="star5"><i class="fas fa-star"></i></label><input type="radio" id="star4" name="rating" value="4"><label for="star4"><i class="fas fa-star"></i></label><input type="radio" id="star3" name="rating" value="3"><label for="star3"><i class="fas fa-star"></i></label><input type="radio" id="star2" name="rating" value="2"><label for="star2"><i class="fas fa-star"></i></label><input type="radio" id="star1" name="rating" value="1"><label for="star1"><i class="fas fa-star"></i></label></div></div><div class="form-group"><label class="form-label">Komentar</label><textarea name="comment" class="form-input" rows="3" required></textarea></div><div class="form-group"><label class="form-label">Foto</label><input type="file" name="review_photo" class="form-input"></div><button type="submit" class="btn-submit" style="background:var(--primary);">Kirim</button></form></div></div>
    <div id="reportModal" class="modal-overlay"><div class="modal-container"><div class="modal-header"><div class="modal-title" style="color:#dc3545;">Lapor</div><button class="close-modal" onclick="closeModal('reportModal')">&times;</button></div><form method="POST"><input type="hidden" name="action" value="report_seller"><input type="hidden" name="order_id" id="repOrderId"><input type="hidden" name="shop_id" id="repShopId"><div class="form-group"><textarea name="reason" class="form-input" rows="4" placeholder="Alasan..." required></textarea></div><button type="submit" class="btn-submit" style="background:#dc3545; color:white;">Kirim</button></form></div></div>

    <script>
        const historyData = <?php echo json_encode($history_data); ?>;
        function goToDashboard() { window.location.href = '../seller/dashboard.php'; }
        function openDetail(id) {
            const data = historyData.find(item => parseInt(item.id) === id);
            if (data) {
                const content = document.getElementById('modalContent');
                content.innerHTML = `
                    <div class="detail-row"><span class="detail-label">Barang</span><span class="detail-value">${data.item}</span></div>
                    <div class="detail-row"><span class="detail-label">Pengiriman</span><span class="detail-value">${data.shipping}</span></div>
                    <div class="detail-row"><span class="detail-label">Pembayaran</span><span class="detail-value" style="color:var(--primary); font-weight:bold;">${data.payment}</span></div>
                    <div class="detail-row"><span class="detail-label">Resi</span><span class="detail-value" style="font-weight:bold;">${data.tracking}</span></div>
                    <div class="detail-row"><span class="detail-label">Penjual</span><span class="detail-value">${data.counterparty}</span></div>
                    <div class="detail-total"><span>Total</span><span>${data.price}</span></div>
                `;
                document.getElementById('detailModal').classList.add('open');
            }
        }
        function openReviewModal(oid, pid) { document.getElementById('revOrderId').value=oid; document.getElementById('revProductId').value=pid; document.getElementById('reviewModal').classList.add('open'); }
        function openReportModal(oid, sid) { document.getElementById('repOrderId').value=oid; document.getElementById('repShopId').value=sid; document.getElementById('reportModal').classList.add('open'); }
        function closeModal(id) { document.getElementById(id).classList.remove('open'); }
        window.onclick = function(e) { if(e.target.classList.contains('modal-overlay')) e.target.classList.remove('open'); }
    </script>
</body>
</html>