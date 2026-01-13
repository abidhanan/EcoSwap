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
// 1. LOGIKA AKSI
// ==========================================

// A. Konfirmasi Pesanan Diterima (Delivered -> Completed)
if (isset($_POST['action']) && $_POST['action'] == 'confirm_received') {
    $oid = $_POST['order_id'];
    
    // Ambil Data Pesanan (Total Harga & Toko Terkait)
    $q_ord = mysqli_query($koneksi, "SELECT o.invoice_code, o.shop_id, o.total_price, o.shipping_method, s.user_id as seller_id 
                                     FROM orders o JOIN shops s ON o.shop_id = s.shop_id 
                                     WHERE o.order_id='$oid'");
    $d_ord = mysqli_fetch_assoc($q_ord);

    if ($d_ord) {
        // Mulai Transaksi Database
        mysqli_begin_transaction($koneksi);

        try {
            // 1. Update Status Pesanan
            mysqli_query($koneksi, "UPDATE orders SET status='completed' WHERE order_id='$oid' AND buyer_id='$user_id'");
            
            // 2. Kirim Notif ke Penjual
            $msg = "Pesanan #{$d_ord['invoice_code']} telah diterima pembeli. Dana masuk ke saldo Anda.";
            mysqli_query($koneksi, "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ('{$d_ord['seller_id']}', 'Pesanan Selesai', '$msg', 0, NOW())");
            
            // 3. LOGIKA SALDO SELLER (DIPERBAIKI)
            
            // a. Ambil Ongkir dari teks (contoh: "JNE (Rp 18.000)")
            $parts = explode(' | ', $d_ord['shipping_method']);
            $ship_lbl_full = $parts[0]; 
            $shipping_cost = 0;
            if (preg_match('/\(Rp ([\d\.]+)\)/', $ship_lbl_full, $matches)) {
                $shipping_cost = (int)str_replace('.', '', $matches[1]);
            }

            // b. Harga Produk (dari database)
            $product_price = $d_ord['total_price'];

            // c. Total Pendapatan Seller = Produk + Ongkir
            // (Biaya Admin 1k sudah dibayar buyer di luar ini, jadi seller terima bersih produk+ongkir)
            $net_income = $product_price + $shipping_cost;
            
            if ($net_income > 0) {
                // Update Saldo Toko
                mysqli_query($koneksi, "UPDATE shops SET balance = balance + $net_income WHERE shop_id='{$d_ord['shop_id']}'");
                
                // Catat di Riwayat Transaksi Toko
                $desc = "Pendapatan pesanan #{$d_ord['invoice_code']}";
                mysqli_query($koneksi, "INSERT INTO transactions (shop_id, type, amount, description, created_at) 
                                        VALUES ('{$d_ord['shop_id']}', 'in', '$net_income', '$desc', NOW())");
            }

            mysqli_commit($koneksi);
            echo "<script>alert('Terima kasih! Pesanan selesai.'); window.location.href='histori.php';</script>";

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo "<script>alert('Terjadi kesalahan saat memproses pesanan.');</script>";
        }
    }
}

// B. Kirim Review
if (isset($_POST['action']) && $_POST['action'] == 'submit_review') {
    $oid = $_POST['order_id'];
    $pid = $_POST['product_id'];
    $rating = $_POST['rating'];
    $comment = mysqli_real_escape_string($koneksi, $_POST['comment']);
    
    $review_img = "";
    if (!empty($_FILES['review_photo']['name'])) {
        $target_dir = "../../../Assets/img/reviews/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_name = time() . "_" . basename($_FILES["review_photo"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["review_photo"]["tmp_name"], $target_file)) {
            $review_img = $target_file;
        }
    }

    $query_review = "INSERT INTO reviews (order_id, product_id, user_id, rating, comment, photo) 
                     VALUES ('$oid', '$pid', '$user_id', '$rating', '$comment', '$review_img')";
    
    if (mysqli_query($koneksi, $query_review)) {
        mysqli_query($koneksi, "UPDATE orders SET status='reviewed' WHERE order_id='$oid'");
        echo "<script>alert('Ulasan berhasil dikirim!'); window.location.href='histori.php';</script>";
    }
}

// C. Laporkan Penjual
if (isset($_POST['action']) && $_POST['action'] == 'report_seller') {
    $oid = $_POST['order_id'];
    $sid = $_POST['shop_id'];
    $reason = mysqli_real_escape_string($koneksi, $_POST['reason']);

    $proof_img = "";
    if (!empty($_FILES['report_proof']['name'])) {
        $target_dir = "../../../Assets/img/reports/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_name = time() . "_rep_" . basename($_FILES["report_proof"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["report_proof"]["tmp_name"], $target_file)) {
            $proof_img = $target_file;
        }
    }

    $query_report = "INSERT INTO reports (shop_id, user_id, order_id, reason, proof_image, status, created_at) 
                     VALUES ('$sid', '$user_id', '$oid', '$reason', '$proof_img', 'pending', NOW())";
    
    if (mysqli_query($koneksi, $query_report)) {
        echo "<script>alert('Laporan berhasil dikirim.'); window.location.href='histori.php';</script>";
    }
}

// ==========================================
// 2. AMBIL DATA
// ==========================================
$history_data = [];
$query = "SELECT 
            o.order_id, o.total_price, o.status, o.shipping_method, o.created_at, o.tracking_number,
            p.name AS product_name, p.image, p.description, p.product_id,
            s.shop_name, s.shop_id
          FROM orders o
          JOIN products p ON o.product_id = p.product_id
          JOIN shops s ON o.shop_id = s.shop_id
          WHERE o.buyer_id = '$user_id'
          ORDER BY o.created_at DESC";

$result = mysqli_query($koneksi, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $badge_class = ''; $badge_text = '';
    
    switch($row['status']) {
        case 'pending':   $badge_class = 'status-pending'; $badge_text = 'Menunggu Konfirmasi'; break;
        case 'processed': $badge_class = 'status-proses'; $badge_text = 'Sedang Diproses'; break;
        case 'shipping':  $badge_class = 'status-proses'; $badge_text = 'Sedang Dikirim'; break;
        case 'delivered': $badge_class = 'status-delivered'; $badge_text = 'Paket Sampai'; break;
        case 'completed': $badge_class = 'status-selesai'; $badge_text = 'Selesai'; break;
        case 'reviewed':  $badge_class = 'status-selesai'; $badge_text = 'Selesai & Dinilai'; break;
        case 'cancelled': $badge_class = 'status-batal'; $badge_text = 'Dibatalkan'; break;
        default:          $badge_class = 'status-pending'; $badge_text = $row['status'];
    }

    $parts = explode(' | ', $row['shipping_method']);
    $ship_lbl_full = $parts[0]; 
    $pay_lbl = isset($parts[1]) ? $parts[1] : 'Manual/COD';
    
    $shipping_cost = 0;
    if (preg_match('/\(Rp ([\d\.]+)\)/', $ship_lbl_full, $matches)) {
        $shipping_cost = (int)str_replace('.', '', $matches[1]);
    }

    $product_price = (int)$row['total_price'];
    $admin_fee = 1000;
    
    // Total yang dibayar buyer (untuk tampilan detail)
    $grand_total = $product_price + $shipping_cost + $admin_fee;

    $history_data[] = [
        'id' => $row['order_id'],
        'product_id' => $row['product_id'],
        'shop_id' => $row['shop_id'],
        'status_raw' => $row['status'],
        'item' => $row['product_name'],
        'price' => 'Rp ' . number_format($product_price, 0, ',', '.'), 
        'shipping' => $ship_lbl_full,
        'payment' => $pay_lbl,
        'tracking' => $row['tracking_number'] ? $row['tracking_number'] : '-',
        'counterparty' => $row['shop_name'],
        'date' => date('d M Y', strtotime($row['created_at'])),
        'badge_class' => $badge_class,
        'badge_text' => $badge_text,
        'image' => $row['image'],
        
        'price_product_fmt' => 'Rp ' . number_format($product_price, 0, ',', '.'),
        'price_shipping_fmt' => 'Rp ' . number_format($shipping_cost, 0, ',', '.'),
        'price_admin_fmt' => 'Rp ' . number_format($admin_fee, 0, ',', '.'),
        'price_total_fmt' => 'Rp ' . number_format($grand_total, 0, ',', '.')
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
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .status-selesai { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-proses { background-color: #cce5ff; color: #004085; border: 1px solid #b8daff; }
        .status-delivered { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; animation: pulse 2s infinite; }
        .status-pending { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }
        .status-batal { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
        .btn-action-group { display: flex; gap: 8px; justify-content: flex-end; margin-top: 10px; }
        .btn-confirm { background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition:0.2s; }
        .btn-confirm:hover { background: #218838; }
        .btn-review { background: #ffc107; color: #000; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition:0.2s; }
        .btn-review:hover { background: #e0a800; }
        .btn-report { background: #fff; color: #dc3545; border: 1px solid #dc3545; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition:0.2s; }
        .btn-report:hover { background: #dc3545; color: white; }
        .btn-detail { background: #f8f9fa; border: 1px solid #ccc; color: #333; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition:0.2s; }
        .btn-detail:hover { background: #e2e6ea; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 2000; display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        .modal-overlay.open { display: flex; opacity: 1; }
        .modal-container { background: #fff; width: 90%; max-width: 500px; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); padding: 25px; transform: scale(0.9); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); max-height: 90vh; overflow-y: auto; }
        .modal-overlay.open .modal-container { transform: scale(1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f0f0f0; padding-bottom: 15px; }
        .modal-title { font-size: 1.25rem; font-weight: 700; color: #333; }
        .close-modal { background: none; border: none; font-size: 1.5rem; color: #999; cursor: pointer; transition: 0.2s; }
        .close-modal:hover { color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.9rem; color: #555; }
        .form-input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; transition: 0.3s; }
        .form-input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(255,215,0,0.1); }
        .btn-submit { width: 100%; padding: 12px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 1rem; margin-top: 10px; transition: 0.2s; }
        .btn-submit:hover { opacity: 0.9; transform: translateY(-1px); }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; }
        .detail-label { color: #666; }
        .detail-value { font-weight: 600; color: #333; text-align: right; max-width: 60%; }
        .detail-total { display: flex; justify-content: space-between; margin-top: 15px; padding-top: 15px; border-top: 2px solid #eee; font-size: 1.1rem; font-weight: 700; color: var(--primary); }
        .rating-input { display: flex; flex-direction: row-reverse; gap: 5px; justify-content: flex-end; }
        .rating-input input { display: none; }
        .rating-input label { font-size: 2rem; color: #ddd; cursor: pointer; transition: 0.2s; }
        .rating-input input:checked ~ label, .rating-input label:hover, .rating-input label:hover ~ label { color: #ffc107; }
        .file-upload-box { border: 2px dashed #ddd; padding: 20px; text-align: center; border-radius: 8px; cursor: pointer; background: #fafafa; }
        .file-upload-box:hover { border-color: var(--primary); background: #fffdf0; }
    </style>
</head>

<body>

    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo" onclick="goToDashboard()" style="cursor:pointer;">
                    ECO<span>SWAP</span>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="menu-item"><a href="profil.php" class="menu-link"><i class="fas fa-user"></i><span>Biodata Diri</span></a></li>
                <li class="menu-item"><a href="alamat.php" class="menu-link"><i class="fas fa-map-marker-alt"></i><span>Alamat</span></a></li>
                <li class="menu-item active"><a href="histori.php" class="menu-link"><i class="fas fa-history"></i><span>Histori</span></a></li>
                <li class="menu-item"><a href="../seller/dashboard.php" class="menu-link"><i class="fas fa-store"></i><span>Toko Saya</span></a></li>
            </ul>

            <div class="sidebar-footer">
                <a href="../../../../index.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <div class="header">
                <div class="page-title">Histori Aktivitas</div>
            </div>

            <div class="content">
                <div class="history-list">
                    
                    <?php if (empty($history_data)): ?>
                        <div style="text-align:center; padding:60px 0; color:#888;">
                            <i class="fas fa-history" style="font-size:3rem; margin-bottom:15px; opacity:0.5;"></i><br>
                            Belum ada riwayat transaksi.
                        </div>
                    <?php else: ?>
                        <?php foreach($history_data as $data): ?>
                        <div class="history-card">
                            <div class="card-left">
                                <div class="item-icon">
                                    <img src="<?php echo $data['image']; ?>" style="width:100%; height:100%; object-fit:cover; border-radius:8px;">
                                </div>
                                <div class="item-info">
                                    <div class="badge-container">
                                        <span class="status-badge <?php echo $data['badge_class']; ?>">
                                            <?php echo $data['badge_text']; ?>
                                        </span>
                                    </div>
                                    <div class="item-title"><?php echo $data['item']; ?></div>
                                    <div class="item-date">
                                        <i class="far fa-calendar-alt"></i> <?php echo $data['date']; ?> 
                                        <span style="margin:0 5px;">•</span> 
                                        <i class="fas fa-store"></i> <?php echo $data['counterparty']; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-right">
                                <div class="item-price"><?php echo $data['price']; ?></div>
                                
                                <div class="btn-action-group">
                                    <?php if($data['status_raw'] == 'delivered'): ?>
                                        <form method="POST" onsubmit="return confirm('Sudah mengecek barang dan yakin ingin menyelesaikannya?')">
                                            <input type="hidden" name="action" value="confirm_received">
                                            <input type="hidden" name="order_id" value="<?php echo $data['id']; ?>">
                                            <button type="submit" class="btn-confirm"><i class="fas fa-check"></i> Terima Pesanan</button>
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
            <div class="modal-header">
                <div class="modal-title">Detail Transaksi</div>
                <button class="close-modal" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div id="modalContent"></div>
            <button class="btn-submit" style="background:#eee; color:#333;" onclick="closeModal('detailModal')">Tutup</button>
        </div>
    </div>

    <div id="reviewModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">Beri Ulasan</div>
                <button class="close-modal" onclick="closeModal('reviewModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_review">
                <input type="hidden" name="order_id" id="revOrderId">
                <input type="hidden" name="product_id" id="revProductId">

                <div class="form-group" style="text-align:center;">
                    <label class="form-label">Berikan Rating</label>
                    <div class="rating-input">
                        <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="Sempurna"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="Bagus"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="Cukup"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="Buruk"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="Sangat Buruk"><i class="fas fa-star"></i></label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Komentar</label>
                    <textarea name="comment" class="form-input" rows="3" placeholder="Bagaimana kualitas produk ini?" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Foto Produk (Opsional)</label>
                    <div class="file-upload-box" onclick="document.getElementById('reviewPhotoInput').click()">
                        <i class="fas fa-camera" style="font-size:1.2rem; color:#888;"></i><br>
                        <span style="font-size:0.85rem; color:#666;">Klik untuk upload foto</span>
                    </div>
                    <input type="file" id="reviewPhotoInput" name="review_photo" accept="image/*" style="display:none;">
                </div>

                <button type="submit" class="btn-submit" style="background:var(--primary); color:#000;">Kirim Ulasan</button>
            </form>
        </div>
    </div>

    <div id="reportModal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title" style="color:#dc3545;">Laporkan Penjual</div>
                <button class="close-modal" onclick="closeModal('reportModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="report_seller">
                <input type="hidden" name="order_id" id="repOrderId">
                <input type="hidden" name="shop_id" id="repShopId">

                <div style="background:#fff5f5; border:1px solid #feb2b2; padding:10px; border-radius:8px; margin-bottom:15px; font-size:0.85rem; color:#c53030;">
                    <i class="fas fa-exclamation-triangle"></i> Laporan palsu dapat menyebabkan akun Anda dibekukan.
                </div>

                <div class="form-group">
                    <label class="form-label">Alasan Pelaporan</label>
                    <textarea name="reason" class="form-input" rows="4" placeholder="Jelaskan masalah Anda (Barang palsu, penipuan, rusak, dll)..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Bukti Foto (Wajib)</label>
                    <div class="file-upload-box" onclick="document.getElementById('reportProofInput').click()">
                        <i class="fas fa-cloud-upload-alt" style="font-size:1.2rem; color:#888;"></i><br>
                        <span style="font-size:0.85rem; color:#666;">Upload bukti foto</span>
                    </div>
                    <input type="file" id="reportProofInput" name="report_proof" accept="image/*" style="display:none;" required>
                </div>

                <button type="submit" class="btn-submit" style="background:#dc3545; color:white;">Kirim Laporan</button>
            </form>
        </div>
    </div>

    <script>
        const historyData = <?php echo json_encode($history_data); ?>;

        function goToDashboard() { window.location.href = '../buyer/dashboard.php'; }

        function openDetail(id) {
            const data = historyData.find(item => parseInt(item.id) === id);
            if (data) {
                const content = document.getElementById('modalContent');
                content.innerHTML = `
                    <div class="detail-row">
                        <span class="detail-label">Nama Barang</span>
                        <span class="detail-value">${data.item}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Harga Barang</span>
                        <span class="detail-value">${data.price_product_fmt}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Metode Kirim</span>
                        <span class="detail-value" style="font-size:0.85rem;">${data.shipping}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Biaya Ongkir</span>
                        <span class="detail-value">${data.price_shipping_fmt}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Biaya Admin</span>
                        <span class="detail-value">${data.price_admin_fmt}</span>
                    </div>
                    
                    <div style="border-top:1px dashed #ddd; margin:15px 0;"></div>
                    
                    <div class="detail-row">
                        <span class="detail-label" style="font-weight:700;">Total Keseluruhan</span>
                        <span class="detail-value" style="font-size:1.1rem; color:var(--primary); font-weight:800;">${data.price_total_fmt}</span>
                    </div>

                    <div style="background:#f9f9f9; padding:10px; border-radius:8px; margin-top:15px;">
                        <div class="detail-row" style="margin-bottom:5px;">
                            <span class="detail-label">Pembayaran</span>
                            <span class="detail-value" style="color:var(--primary); font-weight:bold;">${data.payment}</span>
                        </div>
                        <div class="detail-row" style="margin-bottom:5px;">
                            <span class="detail-label">No. Resi</span>
                            <span class="detail-value" style="font-weight:bold; letter-spacing:1px;">${data.tracking}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Penjual</span>
                            <span class="detail-value">${data.counterparty}</span>
                        </div>
                    </div>
                `;
                document.getElementById('detailModal').classList.add('open');
            }
        }

        function openReviewModal(orderId, productId) {
            document.getElementById('revOrderId').value = orderId;
            document.getElementById('revProductId').value = productId;
            document.getElementById('reviewModal').classList.add('open');
        }

        function openReportModal(orderId, shopId) {
            document.getElementById('repOrderId').value = orderId;
            document.getElementById('repShopId').value = shopId;
            document.getElementById('reportModal').classList.add('open');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('open');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('open');
            }
        }
    </script>
</body>
</html>