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
    $report_id = $_POST['report_id'];
    $new_status = $_POST['action_type']; // 'resolved' atau 'rejected'

    if (in_array($new_status, ['resolved', 'rejected'])) {
        $update = mysqli_query($koneksi, "UPDATE reports SET status = '$new_status' WHERE report_id = '$report_id'");
        
        if ($update) {
            $msg = ($new_status == 'resolved') ? 'Laporan disetujui/diselesaikan.' : 'Laporan ditolak.';
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

// Query Laporan (Join dengan User, Shop, dan Order untuk info lengkap)
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
        /* CSS Tambahan Khusus Halaman Support */
        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .kpi-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
        .kpi-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .bg-warn { background: #fff3cd; color: #856404; }
        .bg-success { background: #d1e7dd; color: #0f5132; }
        .bg-primary { background: #cce5ff; color: #004085; }
        
        .filter-controls { margin-bottom: 20px; display: flex; gap: 10px; }
        .filter-select { padding: 10px; border-radius: 8px; border: 1px solid #ddd; cursor: pointer; }

        .report-table th, .report-table td { padding: 15px; font-size: 0.9rem; }
        .reason-text { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; }

        /* Modal Evidence Image */
        .evidence-img-container { width: 100%; height: 200px; background: #f9f9f9; border-radius: 8px; margin-bottom: 15px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #eee; }
        .evidence-img { max-width: 100%; max-height: 100%; object-fit: contain; cursor: pointer; }
        .no-evidence { color: #888; font-style: italic; }
        
        .detail-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 20px; }
        .status-select-modal { width: 100%; padding: 10px; margin-top: 10px; border-radius: 6px; border: 1px solid #ddd; }
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
            <li><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
            <li class="active"><a href="laporan.php"><i class="fas fa-headset"></i> <span>Laporan</span></a></li>
            <li><a href="pengaturan.php"><i class="fas fa-cog"></i> <span>Pengaturan</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../../../Auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h2>Laporan & Sengketa</h2>
                <p>Tangani keluhan pengguna dan masalah transaksi.</p>
            </div>
            <div class="user-profile">
                <div class="profile-info"><img src="https://ui-avatars.com/api/?name=Admin" alt="Admin"></div>
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
                <select class="filter-select" onchange="window.location.href='support.php?status='+this.value">
                    <option value="all" <?php echo (!isset($_GET['status']) || $_GET['status']=='all')?'selected':''; ?>>Semua Status</option>
                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status']=='pending')?'selected':''; ?>>Menunggu (Pending)</option>
                    <option value="resolved" <?php echo (isset($_GET['status']) && $_GET['status']=='resolved')?'selected':''; ?>>Diselesaikan</option>
                    <option value="rejected" <?php echo (isset($_GET['status']) && $_GET['status']=='rejected')?'selected':''; ?>>Ditolak</option>
                </select>
            </div>

            <table class="data-table report-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Invoice</th>
                        <th>Masalah</th>
                        <th>Pelapor</th>
                        <th>Penjual</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($reports)): ?>
                        <tr><td colspan="7" align="center">Tidak ada data laporan.</td></tr>
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
                                    $cls = 'status-warning';
                                    if($st=='resolved') $cls = 'status-success';
                                    if($st=='rejected') $cls = 'status-danger';
                                ?>
                                <span class="status-badge <?php echo $cls; ?>"><?php echo ucfirst($st); ?></span>
                            </td>
                            <td>
                                <button class="btn-action btn-view" onclick="openModal(this)"><i class="fas fa-eye"></i> Detail</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

    <div class="modal-overlay" id="reportModal">
        <div class="modal-box" style="width: 700px;">
            <div class="modal-header">
                <h3>Detail Sengketa #<span id="mId"></span></h3>
                <i class="fas fa-times" onclick="closeModal()" style="cursor:pointer;"></i>
            </div>
            
            <div class="modal-body detail-grid">
                <div>
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Bukti Foto:</label>
                    <div class="evidence-img-container">
                        <img id="mProof" src="" alt="Bukti" class="evidence-img">
                        <span id="mNoProof" class="no-evidence" style="display:none;">Tidak ada foto</span>
                    </div>
                    <p style="font-size:0.85rem; color:#666;">Klik foto untuk memperbesar.</p>
                </div>

                <div>
                    <div style="margin-bottom:10px;">
                        <span style="color:#888; font-size:0.9rem;">Invoice Pesanan:</span><br>
                        <strong id="mInvoice"></strong>
                    </div>
                    <div style="margin-bottom:10px;">
                        <span style="color:#888; font-size:0.9rem;">Pihak Terlibat:</span><br>
                        <strong>Pelapor:</strong> <span id="mPelapor"></span><br>
                        <strong>Terlapor:</strong> <span id="mPenjual"></span>
                    </div>
                    <div style="margin-bottom:15px;">
                        <span style="color:#888; font-size:0.9rem;">Masalah/Alasan:</span>
                        <div id="mReason" style="background:#f9f9f9; padding:10px; border-radius:6px; font-size:0.95rem; margin-top:5px; border:1px solid #eee;"></div>
                    </div>
                    
                    <hr style="border:0; border-top:1px dashed #ddd; margin:15px 0;">

                    <form method="POST">
                        <input type="hidden" name="report_id" id="formReportId">
                        <label style="font-weight:bold;">Tindakan Admin:</label>
                        <div style="display:flex; gap:10px; margin-top:10px;">
                            <button type="submit" name="action_type" value="resolved" class="btn-verify" id="btnResolve">
                                <i class="fas fa-check-circle"></i> Selesaikan (Refund/Valid)
                            </button>
                            <button type="submit" name="action_type" value="rejected" class="btn-reject" id="btnReject">
                                <i class="fas fa-times-circle"></i> Tolak Laporan
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
                // Adjust path relative to admin view if needed, assuming saved path is relative from root
                // Di database tersimpan: ../../../Assets/...
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
                btnRes.disabled = true; btnRes.style.opacity = "0.5"; btnRes.style.cursor = "not-allowed";
                btnRej.disabled = true; btnRej.style.opacity = "0.5"; btnRej.style.cursor = "not-allowed";
            } else {
                btnRes.disabled = false; btnRes.style.opacity = "1"; btnRes.style.cursor = "pointer";
                btnRej.disabled = false; btnRej.style.opacity = "1"; btnRej.style.cursor = "pointer";
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