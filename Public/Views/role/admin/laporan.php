<?php
session_start();
include '../../../Auth/koneksi.php';

// Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: ../../../Auth/login.php"); 
    exit(); 
}

// --- HITUNG BADGES SIDEBAR ---
$pending_prod_c = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM products WHERE status = 'review' OR status = 'pending'"))['total'];
$pending_rep_c = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'"))['total'];

// --- LOGIKA UTAMA: UPDATE KEPUTUSAN & NOTIFIKASI ---
if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $report_id = mysqli_real_escape_string($koneksi, $_POST['report_id']);
    $new_status = $_POST['action_type']; // 'resolved' atau 'rejected'

    // 1. Ambil Data Detail untuk Notifikasi
    $q_data = mysqli_query($koneksi, "
        SELECT r.user_id as pelapor_id, r.shop_id, s.user_id as seller_id, r.reason, o.invoice_code, s.shop_name
        FROM reports r 
        JOIN shops s ON r.shop_id = s.shop_id 
        JOIN orders o ON r.order_id = o.order_id
        WHERE r.report_id = '$report_id'
    ");
    $d_report = mysqli_fetch_assoc($q_data);
    
    $pelapor_id = $d_report['pelapor_id']; // ID Buyer
    $seller_id = $d_report['seller_id'];   // ID Seller
    $invoice = $d_report['invoice_code'];
    $shop_name = $d_report['shop_name'];

    if (in_array($new_status, ['resolved', 'rejected'])) {
        mysqli_begin_transaction($koneksi);
        try {
            // A. Update Status Laporan di Database
            mysqli_query($koneksi, "UPDATE reports SET status = '$new_status' WHERE report_id = '$report_id'");

            if ($new_status == 'resolved') {
                // --- KASUS DISETUJUI (VALID) ---
                
                // 1. Notifikasi ke Buyer (Pelapor) - PENTING: Buyer diberitahu keputusan
                $msg_buyer = "Laporan Anda untuk pesanan #$invoice (Toko: $shop_name) telah DISETUJUI. Kami telah mengambil tindakan tegas terhadap penjual.";
                mysqli_query($koneksi, "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ('$pelapor_id', 'Laporan Disetujui', '$msg_buyer', 0, NOW())");

                // 2. Notifikasi ke Seller (Terlapor)
                $msg_seller = "PELANGGARAN: Toko Anda dilaporkan valid pada pesanan #$invoice. Akun Anda telah DIBEKUKAN.";
                mysqli_query($koneksi, "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ('$seller_id', 'AKUN DIBEKUKAN', '$msg_seller', 0, NOW())");

                // 3. Ban Seller
                mysqli_query($koneksi, "UPDATE users SET status = 'banned' WHERE user_id = '$seller_id'");
                
                $alert_msg = "Laporan disetujui. Notifikasi terkirim ke Buyer & Seller. Akun Seller dibanned.";

            } else {
                // --- KASUS DITOLAK (TIDAK VALID) ---
                
                // 1. Notifikasi ke Buyer (Pelapor) - PENTING: Buyer diberitahu penolakan
                $msg_buyer = "Laporan Anda untuk pesanan #$invoice DITOLAK. Setelah peninjauan, bukti yang dilampirkan tidak cukup kuat untuk sanksi.";
                mysqli_query($koneksi, "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ('$pelapor_id', 'Laporan Ditolak', '$msg_buyer', 0, NOW())");
                
                $alert_msg = "Laporan ditolak. Notifikasi penolakan dikirim ke Buyer.";
            }

            mysqli_commit($koneksi);
            echo "<script>alert('$alert_msg'); window.location.href='laporan.php';</script>";

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo "<script>alert('Terjadi kesalahan sistem.');</script>";
        }
    }
}

// --- QUERY DATA TABEL ---
$where_clause = "WHERE 1=1";
if (isset($_GET['status']) && $_GET['status'] != 'all') {
    $s = mysqli_real_escape_string($koneksi, $_GET['status']);
    $where_clause .= " AND r.status = '$s'";
}
$reports = [];
$q_rep = mysqli_query($koneksi, "SELECT r.*, u.name as pelapor, s.shop_name as penjual, o.invoice_code FROM reports r JOIN users u ON r.user_id = u.user_id JOIN shops s ON r.shop_id = s.shop_id JOIN orders o ON r.order_id = o.order_id $where_clause ORDER BY r.created_at DESC");
while($row = mysqli_fetch_assoc($q_rep)) { $reports[] = $row; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Laporan & Sengketa</title>
    <link rel="stylesheet" href="../../../Assets/css/role/admin/laporan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-count { background: #e74a3b; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 5px; font-weight: bold; }
        .badge-count.warn { background: #f6c23e; color: black; }
        
        /* Layout & Table */
        .report-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .report-table th { text-align: left; padding: 15px; color: #888; font-size: 0.85rem; border-bottom: 2px solid #eee; background: #f9f9f9; }
        .report-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; color: #444; font-size: 0.9rem; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-resolved { background: #d1e7dd; color: #0f5132; border: 1px solid #c3e6cb; }
        .status-rejected { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .btn-view { padding: 8px 14px; background: #fff; border: 1px solid #ddd; color: #555; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: 0.2s; }
        .btn-view:hover { background: #f0f0f0; border-color: #bbb; color: #333; }
        
        /* Modal Styles */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(2px); }
        .modal-overlay.open { display: flex; }
        .modal-box { background: white; width: 750px; border-radius: 12px; padding: 0; box-shadow: 0 15px 40px rgba(0,0,0,0.25); overflow: hidden; display: flex; flex-direction: column; }
        
        .modal-header { padding: 15px 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #fff; }
        .modal-body { padding: 25px; display: grid; grid-template-columns: 1fr 1.5fr; gap: 25px; }
        
        .evidence-box { background: #f8f9fa; border: 1px dashed #ccc; border-radius: 8px; height: 300px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        .evidence-img { max-width: 100%; max-height: 100%; object-fit: contain; cursor: zoom-in; }
        
        .info-group { margin-bottom: 15px; }
        .info-label { font-size: 0.8rem; color: #888; font-weight: 600; display: block; margin-bottom: 4px; text-transform: uppercase; }
        .info-val { font-size: 0.95rem; color: #333; font-weight: 500; }
        
        .reason-text-box { background: #fff3cd; padding: 12px; border-radius: 6px; border: 1px solid #ffeeba; color: #856404; font-size: 0.9rem; line-height: 1.5; }

        .modal-actions { padding: 20px 25px; background: #f8f9fa; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
        .btn-resolve { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .btn-resolve:hover { background: #218838; }
        .btn-reject { padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .btn-reject:hover { background: #c82333; }
        
        /* Filter Dropdown */
        .filter-select { padding: 10px 15px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 0.9rem; cursor: pointer; background: white; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header"><div class="logo"><i class="fas fa-recycle"></i> ECO<span>SWAP</span></div></div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
            <li>
                <a href="produk.php"><i class="fas fa-box"></i> <span>Produk</span> 
                <?php if($pending_prod_c > 0): ?><span class="badge-count warn"><?php echo $pending_prod_c; ?></span><?php endif; ?>
                </a>
            </li>
            <li><a href="pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a></li>
            <li><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
            <li class="active">
                <a href="laporan.php"><i class="fas fa-headset"></i> <span>Laporan</span> 
                <?php if($pending_rep_c > 0): ?><span class="badge-count"><?php echo $pending_rep_c; ?></span><?php endif; ?>
                </a>
            </li>
        </ul>
        <div class="sidebar-footer"><a href="../../../../index.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text"><h2>Laporan & Sengketa</h2></div>
        </header>

        <section class="card-panel">
            <div class="filter-controls" style="margin-bottom: 20px;">
                <select class="filter-select" onchange="window.location.href='laporan.php?status='+this.value">
                    <option value="all" <?php echo (!isset($_GET['status']) || $_GET['status']=='all')?'selected':''; ?>>Semua Status</option>
                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status']=='pending')?'selected':''; ?>>Menunggu (Pending)</option>
                    <option value="resolved" <?php echo (isset($_GET['status']) && $_GET['status']=='resolved')?'selected':''; ?>>Diselesaikan</option>
                    <option value="rejected" <?php echo (isset($_GET['status']) && $_GET['status']=='rejected')?'selected':''; ?>>Ditolak</option>
                </select>
            </div>

            <table class="report-table">
                <thead><tr><th>ID</th><th>Invoice</th><th>Masalah</th><th>Pelapor</th><th>Terlapor</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if(empty($reports)): ?><tr><td colspan="7" align="center" style="padding:40px; color:#888;">Tidak ada data laporan.</td></tr><?php else: ?>
                    <?php foreach($reports as $r): ?>
                    <tr data-json='<?php echo json_encode($r); ?>'>
                        <td>#R<?php echo $r['report_id']; ?></td>
                        <td><span style="font-family:monospace; font-weight:bold;"><?php echo $r['invoice_code']; ?></span></td>
                        <td><?php echo htmlspecialchars(substr($r['reason'], 0, 35)) . '...'; ?></td>
                        <td><?php echo $r['pelapor']; ?></td>
                        <td><?php echo $r['penjual']; ?></td>
                        <td>
                            <?php $cls = ($r['status']=='resolved')?'status-resolved':(($r['status']=='rejected')?'status-rejected':'status-pending'); ?>
                            <span class="status-badge <?php echo $cls; ?>"><?php echo ucfirst($r['status']); ?></span>
                        </td>
                        <td><button class="btn-view" onclick="openModal(this)"><i class="fas fa-eye"></i> Tinjau</button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <div class="modal-overlay" id="reportModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="margin:0;">Detail Sengketa #<span id="mId"></span></h3>
                <i class="fas fa-times" onclick="closeModal()" style="cursor:pointer; font-size:1.2rem; color:#888;"></i>
            </div>
            
            <form method="POST"> <div class="modal-body">
                    <div>
                        <div class="evidence-box">
                            <img id="mProof" src="" class="evidence-img">
                            <span id="mNoProof" style="display:none; color:#999; font-style:italic;">Tidak ada bukti foto.</span>
                        </div>
                        <p style="text-align:center; font-size:0.8rem; color:#888; margin-top:8px;">Klik gambar untuk melihat ukuran penuh</p>
                    </div>

                    <div>
                        <div class="info-group">
                            <span class="info-label">Invoice Pesanan</span>
                            <span class="info-val" id="mInvoice" style="font-family:monospace;"></span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Pelapor (Buyer)</span>
                            <span class="info-val" id="mPelapor"></span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Terlapor (Seller)</span>
                            <span class="info-val" id="mPenjual"></span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Alasan Pelaporan</span>
                            <div id="mReason" class="reason-text-box"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="report_id" id="formReportId">
                    
                    <button type="submit" name="action_type" value="rejected" class="btn-reject" id="btnReject" onclick="return confirm('Tolak laporan ini? Buyer akan menerima notifikasi penolakan.')">
                        <i class="fas fa-times-circle"></i> Tolak Laporan
                    </button>
                    
                    <button type="submit" name="action_type" value="resolved" class="btn-resolve" id="btnResolve" onclick="return confirm('Setujui laporan ini? Akun Seller akan dibanned dan Buyer menerima notifikasi.')">
                        <i class="fas fa-check-circle"></i> Setujui & Ban Seller
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("reportModal");
        
        function openModal(btn) {
            const data = JSON.parse(btn.closest('tr').getAttribute('data-json'));
            
            // Isi Data Modal
            document.getElementById("mId").innerText = data.report_id;
            document.getElementById("formReportId").value = data.report_id;
            document.getElementById("mInvoice").innerText = data.invoice_code;
            document.getElementById("mPelapor").innerText = data.pelapor;
            document.getElementById("mPenjual").innerText = data.penjual;
            document.getElementById("mReason").innerText = data.reason;

            // Handle Gambar Bukti
            const imgEl = document.getElementById("mProof");
            const noImgEl = document.getElementById("mNoProof");
            if(data.proof_image && data.proof_image.trim() !== "") {
                imgEl.src = data.proof_image; 
                imgEl.style.display="block"; 
                noImgEl.style.display="none";
                imgEl.onclick = () => window.open(data.proof_image, '_blank');
            } else {
                imgEl.style.display="none"; 
                noImgEl.style.display="block";
            }

            // Atur Status Tombol (Disable jika sudah selesai)
            const btnRes = document.getElementById("btnResolve");
            const btnRej = document.getElementById("btnReject");
            
            if(data.status !== 'pending') {
                btnRes.disabled = true; 
                btnRej.disabled = true;
                btnRes.style.opacity = "0.5"; 
                btnRes.style.cursor = "not-allowed";
                btnRej.style.opacity = "0.5";
                btnRej.style.cursor = "not-allowed";
                
                // Ubah teks tombol sesuai status
                if(data.status === 'resolved') {
                    btnRes.innerHTML = "<i class='fas fa-check'></i> Telah Disetujui";
                    btnRej.style.display = "none"; // Sembunyikan tombol tolak
                } else if(data.status === 'rejected') {
                    btnRej.innerHTML = "<i class='fas fa-times'></i> Telah Ditolak";
                    btnRes.style.display = "none"; // Sembunyikan tombol setujui
                }
            } else {
                // Reset State untuk Pending
                btnRes.disabled = false; 
                btnRej.disabled = false;
                btnRes.style.opacity = "1"; 
                btnRes.style.cursor = "pointer";
                btnRej.style.opacity = "1"; 
                btnRej.style.cursor = "pointer";
                btnRes.style.display = "flex";
                btnRej.style.display = "flex";
                btnRes.innerHTML = "<i class='fas fa-check-circle'></i> Setujui & Ban Seller";
                btnRej.innerHTML = "<i class='fas fa-times-circle'></i> Tolak Laporan";
            }

            modal.classList.add("open");
        }
        
        function closeModal() { modal.classList.remove("open"); }
        window.onclick = function(e) { if (e.target == modal) closeModal(); }
    </script>
</body>
</html>