<?php
session_start();
include '../../../Auth/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: ../../../Auth/login.php"); exit(); }

// --- HITUNG BADGES SIDEBAR ---
$pending_prod_c = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM products WHERE status = 'review' OR status = 'pending'"))['total'];
$pending_rep_c = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'"))['total'];

// --- LOGIKA UTAMA: UPDATE LAPORAN, NOTIFIKASI & BAN SELLER ---
if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $report_id = mysqli_real_escape_string($koneksi, $_POST['report_id']);
    $new_status = $_POST['action_type']; // 'resolved' atau 'rejected'

    // 1. Ambil Data Pelapor (Buyer) dan Terlapor (Seller)
    // Kita join ke tabel shops untuk dapat user_id pemilik toko (Seller)
    $q_data = mysqli_query($koneksi, "
        SELECT r.user_id as pelapor_id, r.shop_id, s.user_id as seller_id, r.reason, s.shop_name
        FROM reports r 
        JOIN shops s ON r.shop_id = s.shop_id 
        WHERE r.report_id = '$report_id'
    ");
    $d_report = mysqli_fetch_assoc($q_data);
    
    $pelapor_id = $d_report['pelapor_id']; // ID Buyer
    $seller_id = $d_report['seller_id'];   // ID Seller (untuk di-ban)
    $reason = $d_report['reason'];

    if (in_array($new_status, ['resolved', 'rejected'])) {
        // Gunakan Transaksi Database agar semua proses berhasil atau gagal bersamaan
        mysqli_begin_transaction($koneksi);
        try {
            // A. Update Status Laporan
            mysqli_query($koneksi, "UPDATE reports SET status = '$new_status' WHERE report_id = '$report_id'");

            if ($new_status == 'resolved') {
                // --- KASUS DISETUJUI (VALID) ---
                
                // 1. Notifikasi ke Pembeli (Pelapor)
                $msg_buyer = "Laporan Anda terhadap toko {$d_report['shop_name']} telah disetujui. Pihak kami telah mengambil tindakan tegas.";
                mysqli_query($koneksi, "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ('$pelapor_id', 'Laporan Disetujui', '$msg_buyer', 0, NOW())");

                // 2. Notifikasi ke Seller (Terlapor) - Peringatan Banned
                $msg_seller = "Peringatan Keras: Toko Anda dilaporkan valid atas pelanggaran: '$reason'. Akun Anda telah DIBEKUKAN sementara/permanen sesuai kebijakan.";
                mysqli_query($koneksi, "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ('$seller_id', 'AKUN DIBEKUKAN', '$msg_seller', 0, NOW())");

                // 3. EKSEKUSI BAN SELLER (Update status user jadi banned)
                mysqli_query($koneksi, "UPDATE users SET status = 'banned' WHERE user_id = '$seller_id'");
                
                $alert_msg = "Laporan disetujui. Notifikasi terkirim dan Akun Seller telah DIBANNED.";

            } else {
                // --- KASUS DITOLAK (TIDAK VALID) ---
                
                // 1. Notifikasi ke Pembeli (Pelapor)
                $msg_buyer = "Laporan Anda ditolak karena bukti yang dilampirkan kurang kuat atau alasan tidak sesuai ketentuan.";
                mysqli_query($koneksi, "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES ('$pelapor_id', 'Laporan Ditolak', '$msg_buyer', 0, NOW())");
                
                $alert_msg = "Laporan ditolak. Status diperbarui.";
            }

            mysqli_commit($koneksi);
            echo "<script>alert('$alert_msg'); window.location.href='laporan.php';</script>";

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo "<script>alert('Terjadi kesalahan sistem saat memproses data.');</script>";
        }
    }
}

// --- QUERY DATA UNTUK TABEL (TIDAK BERUBAH) ---
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
    <title>Admin - Laporan</title>
    <link rel="stylesheet" href="../../../Assets/css/role/admin/laporan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .badge-count { background: #e74a3b; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 5px; font-weight: bold; }
        .badge-count.warn { background: #f6c23e; color: black; }
        
        /* Table & Modal Styles Keep As Is */
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th { text-align: left; padding: 15px; color: #888; font-size: 0.85rem; border-bottom: 1px solid #eee; }
        .report-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; color: #555; font-size: 0.9rem; }
        
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-resolved { background: #d1e7dd; color: #0f5132; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .btn-view { padding: 6px 12px; background: #f8f9fa; border: 1px solid #ddd; color: #333; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; }
        
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: white; width: 700px; border-radius: 12px; padding: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .detail-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 20px; margin-top: 20px; }
        .evidence-img { width: 100%; height: 250px; object-fit: contain; background: #f9f9f9; border: 1px solid #eee; }
        
        .btn-resolve { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-reject { width: 100%; padding: 12px; background: #dc3545; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; }
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
            <div class="filter-controls">
                <select class="filter-select" onchange="window.location.href='laporan.php?status='+this.value" style="padding:10px; border-radius:6px; border:1px solid #ddd;">
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
                        <td><?php echo $r['invoice_code']; ?></td>
                        <td><?php echo htmlspecialchars(substr($r['reason'], 0, 30)) . '...'; ?></td>
                        <td><?php echo $r['pelapor']; ?></td>
                        <td><?php echo $r['penjual']; ?></td>
                        <td>
                            <?php $cls = ($r['status']=='resolved')?'status-resolved':(($r['status']=='rejected')?'status-rejected':'status-pending'); ?>
                            <span class="status-badge <?php echo $cls; ?>"><?php echo ucfirst($r['status']); ?></span>
                        </td>
                        <td><button class="btn-view" onclick="openModal(this)"><i class="fas fa-eye"></i> Detail</button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <div class="modal-overlay" id="reportModal">
        <div class="modal-box">
            <div class="modal-header" style="display:flex; justify-content:space-between; border-bottom:1px solid #eee; padding-bottom:10px;">
                <h3 style="margin:0;">Detail Sengketa #<span id="mId"></span></h3>
                <i class="fas fa-times" onclick="closeModal()" style="cursor:pointer; font-size:1.2rem;"></i>
            </div>
            <div class="detail-grid">
                <div>
                    <label style="font-weight:600; display:block; margin-bottom:8px;">Bukti Foto</label>
                    <img id="mProof" src="" class="evidence-img">
                    <span id="mNoProof" style="display:none; color:#999; font-style:italic;">Tidak ada bukti.</span>
                </div>
                <div>
                    <div style="margin-bottom:15px;">
                        <strong>Invoice:</strong> <span id="mInvoice"></span><br>
                        <strong>Pelapor:</strong> <span id="mPelapor"></span><br>
                        <strong>Terlapor:</strong> <span id="mPenjual"></span>
                    </div>
                    <div style="background:#f9f9f9; padding:15px; border-radius:8px; border:1px solid #eee; margin-bottom:20px;">
                        <strong>Masalah:</strong><br><span id="mReason"></span>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="report_id" id="formReportId">
                        
                        <div style="display:flex; gap:10px;">
                            <button type="submit" name="action_type" value="resolved" class="btn-resolve" id="btnResolve" onclick="return confirm('Peringatan: Menyetujui laporan ini akan mem-BANNED akun Seller. Lanjutkan?')">
                                <i class="fas fa-check-circle"></i> Setujui & Ban Seller
                            </button>
                            <button type="submit" name="action_type" value="rejected" class="btn-reject" id="btnReject" onclick="return confirm('Tolak laporan?')">
                                <i class="fas fa-times-circle"></i> Tolak
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById("reportModal");
        function openModal(btn) {
            const data = JSON.parse(btn.closest('tr').getAttribute('data-json'));
            document.getElementById("mId").innerText = data.report_id;
            document.getElementById("formReportId").value = data.report_id;
            document.getElementById("mInvoice").innerText = data.invoice_code;
            document.getElementById("mPelapor").innerText = data.pelapor;
            document.getElementById("mPenjual").innerText = data.penjual;
            document.getElementById("mReason").innerText = data.reason;

            const imgEl = document.getElementById("mProof");
            if(data.proof_image) {
                imgEl.src = data.proof_image; imgEl.style.display="block"; document.getElementById("mNoProof").style.display="none";
            } else {
                imgEl.style.display="none"; document.getElementById("mNoProof").style.display="block";
            }

            const btnRes = document.getElementById("btnResolve");
            const btnRej = document.getElementById("btnReject");
            if(data.status !== 'pending') {
                btnRes.disabled = true; btnRej.disabled = true;
                btnRes.style.opacity = "0.5"; btnRej.style.opacity = "0.5";
                btnRes.innerHTML = (data.status === 'resolved') ? "Disetujui (Seller Banned)" : "Setujui & Ban Seller";
            } else {
                btnRes.disabled = false; btnRej.disabled = false;
                btnRes.style.opacity = "1"; btnRej.style.opacity = "1";
                btnRes.innerHTML = "<i class='fas fa-check-circle'></i> Setujui & Ban Seller";
            }
            modal.classList.add("open");
        }
        function closeModal() { modal.classList.remove("open"); }
        window.onclick = function(e) { if (e.target == modal) closeModal(); }
    </script>
</body>
</html>