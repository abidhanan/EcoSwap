<?php
session_start();
include '../../../Auth/koneksi.php';

// Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../Auth/login.php");
    exit();
}

// --- LOGIKA UPDATE STATUS LAPORAN ---
if (isset($_POST['action'])) {
    $report_id = mysqli_real_escape_string($koneksi, $_POST['report_id']);
    $new_status = $_POST['action_type']; // 'resolved' atau 'rejected'

    if (in_array($new_status, ['resolved', 'rejected'])) {
        $update = mysqli_query($koneksi, "UPDATE reports SET status = '$new_status' WHERE report_id = '$report_id'");
        
        if ($update) {
            $msg = ($new_status == 'resolved') ? 'Laporan disetujui (Masalah Selesai).' : 'Laporan ditolak (Tidak Valid).';
            echo "<script>alert('$msg'); window.location.href='laporan.php';</script>";
        } else {
            echo "<script>alert('Gagal mengupdate status.');</script>";
        }
    }
}

// --- HITUNG STATISTIK ---
$total_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'"))['total'];
$total_resolved = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reports WHERE status = 'resolved'"))['total'];

// --- FILTER & DATA ---
$where_clause = "WHERE 1=1";
if (isset($_GET['status']) && $_GET['status'] != 'all') {
    $s = mysqli_real_escape_string($koneksi, $_GET['status']);
    $where_clause .= " AND r.status = '$s'";
}

// Query Laporan (Join dengan User, Shop, dan Order)
$query = "SELECT r.*, u.name as pelapor, s.shop_name as penjual, o.invoice_code 
          FROM reports r
          JOIN users u ON r.user_id = u.user_id
          JOIN shops s ON r.shop_id = s.shop_id
          JOIN orders o ON r.order_id = o.order_id
          $where_clause
          ORDER BY r.created_at DESC";

$reports = [];
$q_rep = mysqli_query($koneksi, $query);
while($row = mysqli_fetch_assoc($q_rep)) {
    $reports[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Laporan & Support</title>
    <link rel="stylesheet" href="../../../Assets/css/role/admin/laporan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS Khusus Halaman Support */
        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .kpi-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
        .kpi-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .bg-warn { background: #fff3cd; color: #856404; }
        .bg-success { background: #d1e7dd; color: #0f5132; }
        .bg-primary { background: #cce5ff; color: #004085; }
        
        .filter-controls { margin-bottom: 20px; }
        .filter-select { padding: 10px 15px; border-radius: 8px; border: 1px solid #ddd; cursor: pointer; background: white; }

        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th { text-align: left; padding: 15px; color: #888; font-size: 0.85rem; border-bottom: 1px solid #eee; }
        .report-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; color: #555; font-size: 0.9rem; }
        
        .reason-text { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; vertical-align: bottom; }

        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-resolved { background: #d1e7dd; color: #0f5132; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        .btn-view { padding: 6px 12px; background: #f8f9fa; border: 1px solid #ddd; color: #333; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: 0.2s; }
        .btn-view:hover { background: #e2e6ea; }

        /* Modal Styles */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(2px); }
        .modal-overlay.open { display: flex; }
        
        .modal-box { background: white; width: 700px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; animation: slideUp 0.3s; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .modal-header { padding: 20px 25px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; background: #fff; }
        .modal-header h3 { margin: 0; font-size: 1.1rem; color: #333; }
        .close-modal { cursor: pointer; font-size: 1.2rem; color: #999; }

        .detail-grid { padding: 25px; display: grid; grid-template-columns: 1fr 1.2fr; gap: 30px; }
        
        .evidence-img-container { width: 100%; height: 250px; background: #f9f9f9; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #eee; }
        .evidence-img { max-width: 100%; max-height: 100%; object-fit: contain; cursor: pointer; }
        .no-evidence { color: #999; font-style: italic; font-size: 0.9rem; }

        .info-label { font-weight: 600; color: #777; font-size: 0.85rem; display: block; margin-bottom: 3px; }
        .info-val { color: #333; font-weight: 500; font-size: 0.95rem; margin-bottom: 15px; display: block; }
        .reason-box { background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eee; color: #444; font-size: 0.9rem; line-height: 1.5; margin-bottom: 20px; }

        .modal-actions-form { margin-top: 20px; padding-top: 20px; border-top: 1px dashed #eee; }
        .actions-row { display: flex; gap: 10px; margin-top: 10px; }
        
        .btn-resolve { flex: 1; padding: 12px; background: #28a745; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-resolve:hover { background: #218838; }
        .btn-reject { flex: 1; padding: 12px; background: #dc3545; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-reject:hover { background: #c82333; }
        
        button:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fas fa-recycle"></i> ECO<span>SWAP</span></div>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
            <li><a href="produk.php"><i class="fas fa-box"></i> <span>Produk</span></a></li>
            <li><a href="pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a></li>
            <li><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
            <li class="active"><a href="laporan.php"><i class="fas fa-headset"></i> <span>Laporan</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../../../../index.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h2>Laporan & Sengketa</h2>
                <p>Tangani keluhan pengguna dan masalah transaksi.</p>
            </div>
            </header>

        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon bg-warn"><i class="fas fa-exclamation-triangle"></i></div>
                <div><h3><?php echo $total_pending; ?></h3><p>Perlu Tinjauan</p></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon bg-success"><i class="fas fa-check-circle"></i></div>
                <div><h3><?php echo $total_resolved; ?></h3><p>Selesai</p></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon bg-primary"><i class="fas fa-clipboard-list"></i></div>
                <div><h3><?php echo count($reports); ?></h3><p>Total Laporan</p></div>
            </div>
        </section>

        <section class="card-panel">
            <div class="filter-controls">
                <select class="filter-select" onchange="window.location.href='laporan.php?status='+this.value">
                    <option value="all" <?php echo (!isset($_GET['status']) || $_GET['status']=='all')?'selected':''; ?>>Semua Status</option>
                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status']=='pending')?'selected':''; ?>>Menunggu (Pending)</option>
                    <option value="resolved" <?php echo (isset($_GET['status']) && $_GET['status']=='resolved')?'selected':''; ?>>Diselesaikan</option>
                    <option value="rejected" <?php echo (isset($_GET['status']) && $_GET['status']=='rejected')?'selected':''; ?>>Ditolak</option>
                </select>
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th width="10%">ID</th>
                        <th width="15%">Invoice</th>
                        <th width="25%">Masalah</th>
                        <th width="15%">Pelapor</th>
                        <th width="15%">Terlapor</th>
                        <th width="10%">Status</th>
                        <th width="10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($reports)): ?>
                        <tr><td colspan="7" align="center" style="padding:40px; color:#888;">Tidak ada data laporan.</td></tr>
                    <?php else: ?>
                        <?php foreach($reports as $r): ?>
                        <tr data-json='<?php echo json_encode($r); ?>'>
                            <td>#R<?php echo $r['report_id']; ?></td>
                            <td><?php echo $r['invoice_code']; ?></td>
                            <td><span class="reason-text" title="<?php echo htmlspecialchars($r['reason']); ?>"><?php echo htmlspecialchars($r['reason']); ?></span></td>
                            <td><?php echo $r['pelapor']; ?></td>
                            <td><?php echo $r['penjual']; ?></td>
                            <td>
                                <?php 
                                    $st = $r['status'];
                                    $cls = 'status-pending';
                                    if($st=='resolved') $cls = 'status-resolved';
                                    if($st=='rejected') $cls = 'status-rejected';
                                ?>
                                <span class="status-badge <?php echo $cls; ?>"><?php echo ucfirst($st); ?></span>
                            </td>
                            <td>
                                <button class="btn-view" onclick="openModal(this)"><i class="fas fa-eye"></i> Detail</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <div class="modal-overlay" id="reportModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Detail Sengketa #<span id="mId"></span></h3>
                <i class="fas fa-times close-modal" onclick="closeModal()"></i>
            </div>
            
            <div class="modal-body detail-grid">
                <div>
                    <label style="font-weight:600; display:block; margin-bottom:8px; color:#333;">Bukti Foto</label>
                    <div class="evidence-img-container">
                        <img id="mProof" src="" alt="Bukti" class="evidence-img">
                        <span id="mNoProof" class="no-evidence" style="display:none;">Tidak ada bukti foto</span>
                    </div>
                    <p style="font-size:0.8rem; color:#888; text-align:center;">Klik foto untuk memperbesar</p>
                </div>

                <div>
                    <span class="info-label">Invoice Pesanan</span>
                    <strong class="info-val" id="mInvoice"></strong>

                    <span class="info-label">Pihak Terlibat</span>
                    <div class="info-val" style="font-size:0.9rem;">
                        Pelapor: <span id="mPelapor" style="font-weight:600;"></span><br>
                        Terlapor: <span id="mPenjual" style="font-weight:600;"></span>
                    </div>

                    <span class="info-label">Masalah / Keluhan</span>
                    <div id="mReason" class="reason-box"></div>
                    
                    <form method="POST" class="modal-actions-form">
                        <input type="hidden" name="action" value="update_status"> <input type="hidden" name="report_id" id="formReportId">
                        
                        <label style="font-weight:600; color:#333;">Keputusan Admin:</label>
                        <div class="actions-row">
                            <button type="submit" name="action_type" value="resolved" class="btn-resolve" id="btnResolve" onclick="return confirm('Yakin setujui laporan ini? Status akan menjadi Resolved.')">
                                <i class="fas fa-check-circle"></i> Setujui
                            </button>
                            <button type="submit" name="action_type" value="rejected" class="btn-reject" id="btnReject" onclick="return confirm('Tolak laporan ini?')">
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
            const tr = btn.closest('tr');
            // Parsing data JSON dari attribute tr
            const data = JSON.parse(tr.getAttribute('data-json'));

            document.getElementById("mId").innerText = data.report_id;
            document.getElementById("formReportId").value = data.report_id;
            document.getElementById("mInvoice").innerText = data.invoice_code;
            document.getElementById("mPelapor").innerText = data.pelapor;
            document.getElementById("mPenjual").innerText = data.penjual;
            document.getElementById("mReason").innerText = data.reason;

            // Handle Image Proof
            const imgEl = document.getElementById("mProof");
            const noImgEl = document.getElementById("mNoProof");
            
            if (data.proof_image && data.proof_image.trim() !== "") {
                imgEl.src = data.proof_image; 
                imgEl.style.display = "block";
                noImgEl.style.display = "none";
                imgEl.onclick = () => window.open(data.proof_image, '_blank');
            } else {
                imgEl.style.display = "none";
                noImgEl.style.display = "block";
            }

            // Disable buttons if already processed
            const btnRes = document.getElementById("btnResolve");
            const btnRej = document.getElementById("btnReject");
            
            if(data.status !== 'pending') {
                btnRes.disabled = true; 
                btnRej.disabled = true;
                // Optional: Kasih visual feedback
                btnRes.innerHTML = (data.status === 'resolved') ? '<i class="fas fa-check"></i> Sudah Disetujui' : '<i class="fas fa-check"></i> Setujui';
                btnRej.innerHTML = (data.status === 'rejected') ? '<i class="fas fa-times"></i> Sudah Ditolak' : '<i class="fas fa-times"></i> Tolak';
            } else {
                btnRes.disabled = false;
                btnRej.disabled = false;
                btnRes.innerHTML = '<i class="fas fa-check-circle"></i> Setujui';
                btnRej.innerHTML = '<i class="fas fa-times-circle"></i> Tolak';
            }

            modal.classList.add("open");
        }

        function closeModal() {
            modal.classList.remove("open");
        }

        // Close on outside click
        window.onclick = function(e) {
            if (e.target == modal) closeModal();
        }
    </script>
</body>
</html>