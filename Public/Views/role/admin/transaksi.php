<?php
session_start();
include '../../../Auth/koneksi.php';

// Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../Auth/login.php");
    exit();
}

// FILTER LOGIKA
$where_clause = "WHERE 1=1"; // Default semua data
if (isset($_GET['status']) && $_GET['status'] != 'all') {
    $status = mysqli_real_escape_string($koneksi, $_GET['status']);
    // Mapping status frontend ke database
    if ($status == 'dikemas') {
        $where_clause .= " AND o.status = 'processed'";
    } elseif ($status == 'dikirim') {
        $where_clause .= " AND (o.status = 'shipping' OR o.status = 'delivered')";
    } elseif ($status == 'selesai') {
        $where_clause .= " AND (o.status = 'completed' OR o.status = 'reviewed')";
    }
} else {
    // Default: Tampilkan yang sedang berjalan atau selesai (exclude pending/cancelled agar lebih bersih, atau sesuaikan kebutuhan)
    $where_clause .= " AND o.status != 'pending' AND o.status != 'cancelled'";
}

// AMBIL DATA TRANSAKSI (LOGISTIK)
$logistics = [];
$query = "SELECT o.*, p.name as item_name, p.image, p.description, 
                 s.shop_name as seller_name, u.name as buyer_name 
          FROM orders o 
          JOIN products p ON o.product_id = p.product_id 
          JOIN shops s ON o.shop_id = s.shop_id 
          JOIN users u ON o.buyer_id = u.user_id 
          $where_clause
          ORDER BY o.created_at DESC";

$q_log = mysqli_query($koneksi, $query);
while($row = mysqli_fetch_assoc($q_log)) {
    $logistics[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Transaksi</title>
    <link rel="stylesheet" href="../../../Assets/css/role/admin/transaksi.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS Khusus Halaman Transaksi */
        .filter-controls { display: flex; gap: 15px; margin-bottom: 20px; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .search-input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .filter-select { padding: 10px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; }
        
        .transaction-table th, .transaction-table td { padding: 15px; font-size: 0.9rem; }
        .status-badge { padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-processed { background: #fff3cd; color: #856404; } /* Kuning */
        .status-shipping { background: #cce5ff; color: #004085; } /* Biru */
        .status-delivered { background: #d1ecf1; color: #0c5460; } /* Cyan */
        .status-completed, .status-reviewed { background: #d4edda; color: #155724; } /* Hijau */
        
        /* Modal Styles */
        .modal-body-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 20px; }
        .modal-img { width: 100%; height: 200px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }
        .detail-item { margin-bottom: 10px; border-bottom: 1px dashed #eee; padding-bottom: 5px; }
        .detail-label { font-weight: 600; color: #666; font-size: 0.85rem; display: block; }
        .detail-val { color: #333; font-weight: 500; }
        .resi-box { background: #f8f9fa; padding: 5px 10px; border-radius: 4px; font-family: monospace; letter-spacing: 1px; border: 1px solid #ddd; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fas fa-recycle"></i> ECO<span>SWAP</span></div>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
            <li><a href="produk.php"><i class="fas fa-box"></i> <span>Verifikasi Produk</span></a></li>
            <li><a href="pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a></li>
            <li class="active"><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
            <li><a href="laporan.php"><i class="fas fa-headset"></i> <span>Laporan</span></a></li>
            <li><a href="pengaturan.php"><i class="fas fa-cog"></i> <span>Pengaturan</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../../../Auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h2>Manajemen Transaksi</h2>
                <p>Pantau status pengiriman dan riwayat pesanan.</p>
            </div>
            <div class="user-profile">
                <div class="profile-info"><img src="https://ui-avatars.com/api/?name=Admin" alt="Admin"></div>
            </div>
        </header>
        
        <section class="filter-controls">
            <input type="text" id="searchInput" placeholder="Cari ID Transaksi, Barang, atau Resi..." class="search-input" onkeyup="searchTable()">
            <select class="filter-select" onchange="window.location.href='transaksi.php?status='+this.value">
                <option value="all" <?php echo (!isset($_GET['status']) || $_GET['status']=='all') ? 'selected' : ''; ?>>Semua Status</option>
                <option value="dikemas" <?php echo (isset($_GET['status']) && $_GET['status']=='dikemas') ? 'selected' : ''; ?>>Dikemas (Processed)</option>
                <option value="dikirim" <?php echo (isset($_GET['status']) && $_GET['status']=='dikirim') ? 'selected' : ''; ?>>Dikirim (Shipping)</option>
                <option value="selesai" <?php echo (isset($_GET['status']) && $_GET['status']=='selesai') ? 'selected' : ''; ?>>Selesai</option>
            </select>
        </section>

        <section class="card-panel">
            <div class="transaction-list-card">
                <h3>Daftar Barang dalam Pengiriman</h3>
                <table class="data-table transaction-table" id="trxTable">
                    <thead>
                        <tr>
                            <th>ID Transaksi</th>
                            <th>Nama Item</th>
                            <th>Penjual</th>
                            <th>Pembeli</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($logistics)): ?>
                            <tr><td colspan="6" align="center">Tidak ada data transaksi.</td></tr>
                        <?php else: ?>
                            <?php foreach($logistics as $log): 
                                // Parse kurir dari string "JNE (15k) | Dana"
                                $parts = explode(' | ', $log['shipping_method']);
                                $kurir_full = $parts[0]; // JNE (Rp 15.000)
                                $kurir_name = explode(' (', $kurir_full)[0];
                            ?>
                            <tr data-id="<?php echo $log['invoice_code']; ?>" 
                                data-item="<?php echo $log['item_name']; ?>" 
                                data-resi="<?php echo $log['tracking_number'] ? $log['tracking_number'] : '-'; ?>" 
                                data-kurir="<?php echo $kurir_name; ?>" 
                                data-deskripsi="<?php echo htmlspecialchars($log['description']); ?>" 
                                data-foto="<?php echo $log['image']; ?>" 
                                data-penerima="<?php echo $log['buyer_name']; ?>"
                                data-penjual="<?php echo $log['seller_name']; ?>"
                                data-status="<?php echo ucfirst($log['status']); ?>">
                                
                                <td><?php echo $log['invoice_code']; ?></td>
                                <td><?php echo $log['item_name']; ?></td>
                                <td><?php echo $log['seller_name']; ?></td>
                                <td><?php echo $log['buyer_name']; ?></td>
                                <td><span class="status-badge status-<?php echo $log['status']; ?>"><?php echo ucfirst($log['status']); ?></span></td>
                                <td>
                                    <button class="btn-action btn-view" onclick='openModal(this)'><i class="fas fa-eye"></i> Detail</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    
    <div class="modal-overlay" id="logisticDetailModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Detail Transaksi Logistik</h3>
                <i class="fas fa-times" onclick="closeModal()" style="cursor:pointer;"></i>
            </div>
            
            <div class="modal-body modal-body-grid">
                <div class="detail-photo-column">
                    <img id="modal-item-photo" src="" alt="Foto Barang" class="modal-img">
                    <p style="margin-top:10px; font-size:0.9rem; text-align:center;">
                        Penjual: <strong id="modal-seller-name"></strong>
                    </p>
                </div>

                <div class="detail-info-column">
                    <h3 id="modal-item-name" style="margin-bottom:15px; color:#333;"></h3>
                    
                    <div class="detail-item">
                        <span class="detail-label">ID Transaksi (Invoice)</span>
                        <span class="detail-val" id="modal-trx-id"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Penerima</span>
                        <span class="detail-val" id="modal-penerima"></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status Saat Ini</span>
                        <span class="detail-val" id="modal-status" style="font-weight:bold; color:var(--primary);"></span>
                    </div>

                    <div style="margin-top:20px;">
                        <h4 style="font-size:0.95rem; margin-bottom:5px;">Info Logistik</h4>
                        <div class="detail-item">
                            <span class="detail-label">Jasa Kirim</span>
                            <span class="detail-val" id="modal-kurir"></span>
                        </div>
                        <div class="detail-item" style="border:none;">
                            <span class="detail-label">Nomor Resi</span>
                            <span class="resi-box" id="modal-resi"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions" style="margin-top:20px;">
                <button type="button" class="btn-verify" style="width:100%; background:#333;" onclick="closeModal()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById("logisticDetailModal");

        function openModal(btn) {
            const tr = btn.closest('tr');
            const data = tr.dataset;

            document.getElementById("modal-trx-id").innerText = data.id;
            document.getElementById("modal-item-name").innerText = data.item;
            document.getElementById("modal-seller-name").innerText = data.penjual;
            document.getElementById("modal-penerima").innerText = data.penerima;
            document.getElementById("modal-resi").innerText = data.resi;
            document.getElementById("modal-kurir").innerText = data.kurir;
            document.getElementById("modal-item-photo").src = data.foto;
            document.getElementById("modal-status").innerText = data.status;

            modal.classList.add("open");
        }

        function closeModal() {
            modal.classList.remove("open");
        }

        // Fitur Pencarian Sederhana JS
        function searchTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("trxTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) { // Mulai dari 1 (skip header)
                // Cari di kolom ID (0), Item (1), Resi (di data attribute)
                const tdId = tr[i].getElementsByTagName("td")[0];
                const tdItem = tr[i].getElementsByTagName("td")[1];
                const resi = tr[i].getAttribute("data-resi");
                
                if (tdId || tdItem) {
                    const txtId = tdId.textContent || tdId.innerText;
                    const txtItem = tdItem.textContent || tdItem.innerText;
                    
                    if (txtId.toUpperCase().indexOf(filter) > -1 || 
                        txtItem.toUpperCase().indexOf(filter) > -1 || 
                        resi.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
        }
    </script>
</body>
</html>