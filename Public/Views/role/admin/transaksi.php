<?php
session_start();
include '../../../Auth/koneksi.php';

// Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../Auth/login.php");
    exit();
}

// AJAX Endpoint untuk Real-Time Update
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_transactions') {
    header('Content-Type: application/json');
    
    // Filter
    $where_clause = "WHERE 1=1"; 
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    
    if ($status_filter != 'all') {
        $status = mysqli_real_escape_string($koneksi, $status_filter);
        if ($status == 'dikemas') {
            $where_clause .= " AND o.status = 'processed'";
        } elseif ($status == 'dikirim') {
            $where_clause .= " AND (o.status = 'shipping' OR o.status = 'delivered')";
        } elseif ($status == 'selesai') {
            $where_clause .= " AND (o.status = 'completed' OR o.status = 'reviewed')";
        }
    } else {
        $where_clause .= " AND o.status != 'pending' AND o.status != 'cancelled'";
    }
    
    // Ambil data transaksi
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
    
    echo json_encode([
        'transactions' => $logistics
    ]);
    exit();
}

// --- LOGIKA UPDATE BIAYA ADMIN ---
if (isset($_POST['action']) && $_POST['action'] == 'update_fee') {
    $new_fee = mysqli_real_escape_string($koneksi, $_POST['admin_fee']);
    
    // Cek apakah key sudah ada
    $check = mysqli_query($koneksi, "SELECT * FROM system_settings WHERE setting_key = 'admin_fee'");
    if (mysqli_num_rows($check) > 0) {
        $q = "UPDATE system_settings SET setting_value = '$new_fee' WHERE setting_key = 'admin_fee'";
    } else {
        $q = "INSERT INTO system_settings (setting_key, setting_value) VALUES ('admin_fee', '$new_fee')";
    }
    
    if (mysqli_query($koneksi, $q)) {
        echo "<script>alert('Biaya admin aplikasi berhasil diperbarui!'); window.location.href='transaksi.php';</script>";
    }
}

// Ambil Biaya Admin Saat Ini
$q_fee = mysqli_query($koneksi, "SELECT setting_value FROM system_settings WHERE setting_key = 'admin_fee'");
$d_fee = mysqli_fetch_assoc($q_fee);
$current_admin_fee = isset($d_fee['setting_value']) ? $d_fee['setting_value'] : 1000;

// FILTER LOGIKA
$where_clause = "WHERE 1=1"; 
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

if ($status_filter != 'all') {
    $status = mysqli_real_escape_string($koneksi, $status_filter);
    if ($status == 'dikemas') {
        $where_clause .= " AND o.status = 'processed'";
    } elseif ($status == 'dikirim') {
        $where_clause .= " AND (o.status = 'shipping' OR o.status = 'delivered')";
    } elseif ($status == 'selesai') {
        $where_clause .= " AND (o.status = 'completed' OR o.status = 'reviewed')";
    }
} else {
    // Exclude pending/cancelled agar tabel bersih
    $where_clause .= " AND o.status != 'pending' AND o.status != 'cancelled'";
}

// AMBIL DATA TRANSAKSI
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
    <title>EcoSwap - Transaksi</title>
    <link rel="icon" type="image/png" href="../../../Assets/img/auth/logo.png">
    <link rel="stylesheet" href="../../../Assets/css/role/admin/transaksi.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- CSS Layout & Filter --- */
        .toolbar-container {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
            background: white; padding: 15px 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .search-box { flex: 1; margin-right: 15px; position: relative; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; }
        .search-input { width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 0.9rem; transition: 0.3s; }
        .search-input:focus { border-color: #4e73df; outline: none; }

        .filter-group { display: flex; gap: 10px; align-items: center; }
        .filter-select { padding: 12px 15px; border: 1px solid #e0e0e0; border-radius: 8px; cursor: pointer; background: #fff; font-size: 0.9rem; color: #555; }
        .btn-fee { background: #333; color: #fff; padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
        .btn-fee:hover { background: #000; }

        /* --- Table Styles --- */
        .transaction-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .transaction-table th { text-align: left; padding: 15px; font-size: 0.85rem; color: #888; font-weight: 600; border-bottom: 1px solid #eee; }
        .transaction-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; color: #444; font-size: 0.9rem; }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-processed { background: #fff3cd; color: #856404; }
        .status-shipping { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .status-completed, .status-reviewed { background: #d4edda; color: #155724; }

        .btn-view { padding: 8px 16px; background: #f0f4ff; color: #4e73df; border: 1px solid #d1e3ff; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: 0.2s; }
        .btn-view:hover { background: #4e73df; color: #fff; border-color: #4e73df; }

        /* --- Modal Styles --- */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(3px); }
        .modal-overlay.open { display: flex; }
        
        .modal-box { background: white; width: 650px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; animation: slideUp 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); }
        .modal-box-small { width: 400px; } /* Untuk modal fee */
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .modal-header { padding: 20px 25px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; background: #fff; }
        .modal-header h3 { margin: 0; font-size: 1.1rem; color: #333; font-weight: 700; }
        .close-modal { cursor: pointer; font-size: 1.2rem; color: #999; transition: 0.2s; }
        .close-modal:hover { color: #333; }

        .modal-body-grid { padding: 25px; display: grid; grid-template-columns: 220px 1fr; gap: 30px; }
        
        .modal-img-wrapper { text-align: center; }
        .modal-img { width: 100%; height: 220px; object-fit: cover; border-radius: 10px; border: 1px solid #eee; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .seller-badge { margin-top: 10px; font-size: 0.85rem; color: #666; background: #f8f9fa; padding: 5px 10px; border-radius: 20px; display: inline-block; }

        .info-group { margin-bottom: 15px; border-bottom: 1px dashed #eee; padding-bottom: 10px; }
        .info-group:last-child { border: none; }
        .info-label { font-size: 0.8rem; color: #888; font-weight: 600; display: block; margin-bottom: 3px; text-transform: uppercase; }
        .info-val { font-size: 1rem; color: #333; font-weight: 500; }
        .info-highlight { color: #4e73df; font-weight: 700; }

        .resi-display { background: #eef2f7; padding: 8px 12px; border-radius: 6px; font-family: 'Courier New', monospace; font-weight: 700; letter-spacing: 1px; color: #333; border: 1px solid #dce4ec; display: inline-block; margin-top: 5px; }

        .modal-actions { padding: 20px 25px; background: #f8f9fa; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
        .btn-close-modal { padding: 10px 25px; background: #333; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-save-modal { padding: 10px 25px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }

        /* Form di Modal Small */
        .form-input-fee { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1.1rem; font-weight: bold; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">ECO<span>SWAP</span></div>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
            <li><a href="produk.php"><i class="fas fa-box"></i> <span>Produk</span></a></li>
            <li><a href="pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a></li>
            <li class="active"><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
            <li><a href="laporan.php"><i class="fas fa-headset"></i> <span>Laporan</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../../../../index.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h2>Manajemen Transaksi</h2>
                <p>Pantau status logistik dan atur biaya admin aplikasi.</p>
            </div>
        </header>
        
        <div class="toolbar-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari ID Invoice, Nama Barang..." class="search-input" onkeyup="searchTable()">
            </div>
            
            <div class="filter-group">
                <button class="btn-fee" onclick="openFeeModal()">
                    <i class="fas fa-cog"></i> Fee: Rp <?php echo number_format($current_admin_fee, 0, ',', '.'); ?>
                </button>
                <select class="filter-select" onchange="window.location.href='transaksi.php?status='+this.value">
                    <option value="all" <?php echo ($status_filter=='all') ? 'selected' : ''; ?>>Semua Status</option>
                    <option value="dikemas" <?php echo ($status_filter=='dikemas') ? 'selected' : ''; ?>>Dikemas</option>
                    <option value="dikirim" <?php echo ($status_filter=='dikirim') ? 'selected' : ''; ?>>Dikirim</option>
                    <option value="selesai" <?php echo ($status_filter=='selesai') ? 'selected' : ''; ?>>Selesai</option>
                </select>
            </div>
        </div>

        <section class="card-panel">
            <table class="transaction-table" id="trxTable">
                <thead>
                    <tr>
                        <th width="15%">ID Transaksi</th>
                        <th width="25%">Nama Item</th>
                        <th width="20%">Penjual</th>
                        <th width="20%">Pembeli</th>
                        <th width="10%">Status</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($logistics)): ?>
                        <tr><td colspan="6" align="center" style="padding: 40px; color: #888;">Tidak ada data transaksi.</td></tr>
                    <?php else: ?>
                        <?php foreach($logistics as $log): 
                            // Parse kurir
                            $parts = explode(' | ', $log['shipping_method']);
                            $kurir_full = $parts[0]; 
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
                            
                            <td style="font-weight:bold; color:#555;"><?php echo $log['invoice_code']; ?></td>
                            <td><?php echo $log['item_name']; ?></td>
                            <td><?php echo $log['seller_name']; ?></td>
                            <td><?php echo $log['buyer_name']; ?></td>
                            <td><span class="status-badge status-<?php echo $log['status']; ?>"><?php echo ucfirst($log['status']); ?></span></td>
                            <td>
                                <button class="btn-view" onclick='openModal(this)'><i class="fas fa-eye"></i> Detail</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
    
    <div class="modal-overlay" id="logisticDetailModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Detail Pengiriman</h3>
                <i class="fas fa-times close-modal" onclick="closeModal()"></i>
            </div>
            
            <div class="modal-body-grid">
                <div class="modal-img-wrapper">
                    <img id="modal-item-photo" src="" alt="Foto Barang" class="modal-img">
                    <div class="seller-badge"><i class="fas fa-store"></i> <span id="modal-seller-name"></span></div>
                </div>

                <div class="detail-info-column">
                    <h2 id="modal-item-name" style="margin: 0 0 20px 0; color:#333; font-size:1.4rem; line-height:1.2;"></h2>
                    
                    <div class="info-group">
                        <span class="info-label">Invoice & Status</span>
                        <span class="info-val" id="modal-trx-id"></span>
                        <span class="info-val" style="margin: 0 5px;">•</span>
                        <span class="info-highlight" id="modal-status"></span>
                    </div>

                    <div class="info-group">
                        <span class="info-label">Penerima</span>
                        <span class="info-val" id="modal-penerima"></span>
                    </div>

                    <div class="info-group">
                        <span class="info-label">Logistik</span>
                        <span class="info-val" id="modal-kurir"></span>
                        <br>
                        <div class="resi-display" id="modal-resi"></div>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-close-modal" onclick="closeModal()">Tutup</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="feeModal">
        <div class="modal-box modal-box-small">
            <div class="modal-header">
                <h3>Atur Biaya Admin</h3>
                <i class="fas fa-times close-modal" onclick="closeFeeModal()"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_fee">
                <div class="modal-body" style="padding: 30px;">
                    <label style="display:block; margin-bottom:10px; color:#555;">Jumlah Biaya (Rp)</label>
                    <input type="number" name="admin_fee" class="form-input-fee" value="<?php echo $current_admin_fee; ?>" required>
                    <p style="margin-top:10px; font-size:0.85rem; color:#888;">Biaya ini akan dikenakan ke setiap transaksi pembeli.</p>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-close-modal" style="background:#eee; color:#333;" onclick="closeFeeModal()">Batal</button>
                    <button type="submit" class="btn-save-modal">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- MODAL DETAIL ---
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

        // --- MODAL FEE ---
        const feeModal = document.getElementById("feeModal");
        function openFeeModal() { feeModal.classList.add("open"); }
        function closeFeeModal() { feeModal.classList.remove("open"); }

        // --- SEARCH ---
        function searchTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("trxTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) { 
                const tdId = tr[i].getElementsByTagName("td")[0];
                const tdItem = tr[i].getElementsByTagName("td")[1];
                
                if (tdId || tdItem) {
                    const txtId = tdId.textContent || tdId.innerText;
                    const txtItem = tdItem.textContent || tdItem.innerText;
                    if (txtId.toUpperCase().indexOf(filter) > -1 || txtItem.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
        }

        window.onclick = function(e) {
            if (e.target == modal) closeModal();
            if (e.target == feeModal) closeFeeModal();
        }
        
        // Real-Time Update Function
        function updateTransactions() {
            const currentStatus = new URLSearchParams(window.location.search).get('status') || 'all';
            fetch('transaksi.php?ajax=get_transactions&status=' + currentStatus)
                .then(response => response.json())
                .then(data => {
                    // Update tabel transaksi
                    const tbody = document.querySelector('#trxTable tbody');
                    if (data.transactions.length > 0) {
                        let html = '';
                        data.transactions.forEach(log => {
                            const parts = log.shipping_method.split(' | ');
                            const kurirFull = parts[0];
                            const kurirName = kurirFull.split(' (')[0];
                            const resi = log.tracking_number ? log.tracking_number : '-';
                            
                            html += '<tr data-id="' + log.invoice_code + '" ' +
                                'data-item="' + log.item_name + '" ' +
                                'data-resi="' + resi + '" ' +
                                'data-kurir="' + kurirName + '" ' +
                                'data-deskripsi="' + log.description + '" ' +
                                'data-foto="' + log.image + '" ' +
                                'data-penerima="' + log.buyer_name + '" ' +
                                'data-penjual="' + log.seller_name + '" ' +
                                'data-status="' + log.status.charAt(0).toUpperCase() + log.status.slice(1) + '">' +
                                '<td style="font-weight:bold; color:#555;">' + log.invoice_code + '</td>' +
                                '<td>' + log.item_name + '</td>' +
                                '<td>' + log.seller_name + '</td>' +
                                '<td>' + log.buyer_name + '</td>' +
                                '<td><span class="status-badge status-' + log.status + '">' + log.status.charAt(0).toUpperCase() + log.status.slice(1) + '</span></td>' +
                                '<td><button class="btn-view" onclick="openModal(this)"><i class="fas fa-eye"></i> Detail</button></td>' +
                                '</tr>';
                        });
                        tbody.innerHTML = html;
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" align="center" style="padding: 40px; color: #888;">Tidak ada data transaksi.</td></tr>';
                    }
                })
                .catch(error => console.error('Error updating transactions:', error));
        }
        
        // Update setiap 5 detik
        setInterval(updateTransactions, 5000);
        
        // Update pertama kali setelah 2 detik
        setTimeout(updateTransactions, 2000);
    </script>
</body>
</html>
