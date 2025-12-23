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

// --- AMBIL DATA HISTORI TRANSAKSI (PEMBELIAN) ---
// Kita ambil data orders, join ke products (untuk info barang), dan shops (untuk nama penjual)
$history_data = [];
$query = "SELECT 
            o.order_id, 
            o.total_price, 
            o.status, 
            o.shipping_method, 
            o.created_at, 
            p.name AS product_name, 
            p.image, 
            p.description, 
            s.shop_name 
          FROM orders o
          JOIN products p ON o.product_id = p.product_id
          JOIN shops s ON o.shop_id = s.shop_id
          WHERE o.buyer_id = '$user_id'
          ORDER BY o.created_at DESC";

$result = mysqli_query($koneksi, $query);

while ($row = mysqli_fetch_assoc($result)) {
    // Format status untuk tampilan
    $badge_class = '';
    $badge_text = '';
    
    switch($row['status']) {
        case 'pending':
            $badge_class = 'status-pending'; 
            $badge_text = 'Menunggu Konfirmasi';
            break;
        case 'shipping':
            $badge_class = 'status-proses'; 
            $badge_text = 'Sedang Dikirim';
            break;
        case 'completed':
            $badge_class = 'status-selesai'; 
            $badge_text = 'Selesai';
            break;
        case 'cancelled':
            $badge_class = 'status-batal'; 
            $badge_text = 'Dibatalkan';
            break;
        default:
            $badge_class = 'status-pending'; 
            $badge_text = $row['status'];
    }

    // Masukkan ke array untuk PHP dan JS
    $history_data[] = [
        'id' => $row['order_id'],
        'type' => 'beli', // Default view buyer adalah pembelian
        'item' => $row['product_name'],
        'price_raw' => $row['total_price'],
        'price' => 'Rp ' . number_format($row['total_price'], 0, ',', '.'),
        'desc' => $row['description'],
        'shipping' => $row['shipping_method'],
        'counterparty' => $row['shop_name'], // Nama Penjual
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
        /* CSS Tambahan untuk status badge agar warna sesuai */
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; }
        .status-selesai { background-color: #d4edda; color: #155724; }
        .status-proses { background-color: #cce5ff; color: #004085; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-batal { background-color: #f8d7da; color: #721c24; }
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
                <li class="menu-item">
                    <a href="profil.php" class="menu-link">
                        <i class="fas fa-user"></i>
                        <span>Biodata Diri</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="alamat.php" class="menu-link">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Alamat</span>
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="histori.php" class="menu-link">
                        <i class="fas fa-history"></i>
                        <span>Histori</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="../seller/dashboard.php" class="menu-link">
                        <i class="fas fa-store"></i>
                        <span>Toko Saya</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="../../../../index.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <div class="header">
                <div class="page-title">Histori Aktivitas</div>
            </div>

            <div class="content">
                <div class="history-list">
                    
                    <?php if (empty($history_data)): ?>
                        <div style="text-align:center; padding:40px; color:#888;">
                            <i class="fas fa-history" style="font-size:3rem; margin-bottom:15px;"></i><br>
                            Belum ada riwayat transaksi.
                        </div>
                    <?php else: ?>
                        <?php foreach($history_data as $data): ?>
                        <div class="history-card">
                            <div class="card-left">
                                <div class="item-icon">
                                    <?php if(!empty($data['image']) && $data['image'] != 'default.jpg'): ?>
                                        <img src="<?php echo $data['image']; ?>" style="width:100%; height:100%; object-fit:cover; border-radius:8px;">
                                    <?php else: ?>
                                        <i class="fas fa-shopping-bag"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="item-info">
                                    <div class="badge-container">
                                        <span class="status-badge <?php echo $data['badge_class']; ?>">
                                            <?php echo $data['badge_text']; ?>
                                        </span>
                                    </div>
                                    <div class="item-title"><?php echo $data['item']; ?></div>
                                    <div class="item-date"><?php echo $data['date']; ?></div>
                                </div>
                            </div>
                            <div class="card-right">
                                <div class="item-price"><?php echo $data['price']; ?></div>
                                <button class="btn-detail" onclick="openDetail(<?php echo $data['id']; ?>)">Detail</button>
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
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <div id="modalContent">
                </div>
        </div>
    </div>

    <script>
        // Data dari PHP dimasukkan ke variabel JS
        const historyData = <?php echo json_encode($history_data); ?>;

        // Navigasi Sidebar
        function goToDashboard() {
            window.location.href = 'dashboard.php';
        }

        // Logic Modal
        const modal = document.getElementById('detailModal');
        const modalContent = document.getElementById('modalContent');

        function openDetail(id) {
            // 1. Cari data berdasarkan ID di array historyData
            // Pastikan tipe data id sama (integer)
            const data = historyData.find(item => parseInt(item.id) === id);

            if (data) {
                // 2. Render HTML ke dalam modal
                modalContent.innerHTML = `
                    <div class="detail-row">
                        <span class="detail-label">Nama Barang</span>
                        <span class="detail-value">${data.item}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Metode Pengiriman</span>
                        <span class="detail-value">${data.shipping}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Penjual (Toko)</span>
                        <span class="detail-value">${data.counterparty}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value" style="font-weight:bold;">${data.badge_text}</span>
                    </div>
                    
                    <div style="margin-top: 15px; font-weight: 600; color: #666;">Deskripsi Barang:</div>
                    <div class="detail-desc">
                        ${data.desc ? data.desc : 'Tidak ada deskripsi.'}
                    </div>

                    <div class="detail-total">
                        <span>Total Harga</span>
                        <span>${data.price}</span>
                    </div>
                `;

                // 3. Tampilkan Modal
                modal.classList.add('open');
            }
        }

        function closeModal() {
            modal.classList.remove('open');
        }

        // Tutup modal jika klik di luar area konten
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>