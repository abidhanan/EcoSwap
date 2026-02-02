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

// --- PERBAIKAN: HAPUS 'service_fees' DARI QUERY ---
// Ambil Data Toko (Saldo & Ongkir)
$q_shop = mysqli_query($koneksi, "SELECT shop_id, balance, shipping_costs FROM shops WHERE user_id = '$user_id'");

if(mysqli_num_rows($q_shop) == 0){ 
    header("Location: dashboard.php"); 
    exit(); 
}

$shop = mysqli_fetch_assoc($q_shop);
$shop_id = $shop['shop_id'];
$balance = $shop['balance'];
// Decode JSON ongkir (jika ada) ke array
$shipping_costs = !empty($shop['shipping_costs']) ? json_decode($shop['shipping_costs'], true) : [];

// ==========================================
// 1. LOGIKA SIMPAN PENGATURAN ONGKIR
// ==========================================
if (isset($_POST['action']) && $_POST['action'] == 'save_settings') {
    if (isset($_POST['ship_type'])) { 
        $shipping_data = [];
        // Daftar kurir yang didukung sistem
        $couriers = ['JNE', 'JNT', 'SiCepat', 'GoSend', 'Grab', 'AnterAja'];
        
        foreach($couriers as $c) {
            // Jika input dikirim, simpan nilainya. Jika kosong/tidak diisi, abaikan atau set default.
            if(isset($_POST['ship_'.$c])) {
                $shipping_data[$c] = $_POST['ship_'.$c];
            }
        }
        
        // Encode array kembali ke JSON untuk disimpan di database
        $shipping_json = json_encode($shipping_data);
        
        // Update tabel shops
        $update = mysqli_query($koneksi, "UPDATE shops SET shipping_costs='$shipping_json' WHERE shop_id='$shop_id'");
        
        if($update) {
            echo "<script>alert('Pengaturan ongkos kirim berhasil disimpan!'); window.location.href='keuangan.php?tab=settings';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan pengaturan.');</script>";
        }
    }
    exit();
}

// ==========================================
// 2. LOGIKA TARIK SALDO (WITHDRAW)
// ==========================================
if (isset($_POST['action']) && $_POST['action'] == 'withdraw') {
    $amount = (int)$_POST['amount'];
    $method = mysqli_real_escape_string($koneksi, $_POST['destination_method']); 
    $account = mysqli_real_escape_string($koneksi, $_POST['account_number']);
    
    // Validasi Saldo
    if ($amount > $balance) {
        echo "<script>alert('Saldo tidak mencukupi untuk penarikan ini.'); window.location.href='keuangan.php';</script>";
    } elseif ($amount <= 0) {
        echo "<script>alert('Nominal penarikan tidak valid.'); window.location.href='keuangan.php';</script>";
    } else {
        // Mulai Transaksi Database (agar aman)
        mysqli_begin_transaction($koneksi);
        
        try {
            // 1. Kurangi Saldo Toko
            $update_saldo = mysqli_query($koneksi, "UPDATE shops SET balance = balance - $amount WHERE shop_id='$shop_id'");
            
            // 2. Catat di Riwayat Transaksi (Type: 'out')
            $desc = "Penarikan ke $method ($account)";
            $insert_trans = mysqli_query($koneksi, "INSERT INTO transactions (shop_id, type, amount, description, created_at) 
                                                    VALUES ('$shop_id', 'out', '$amount', '$desc', NOW())");
            
            if ($update_saldo && $insert_trans) {
                mysqli_commit($koneksi);
                echo "<script>alert('Permintaan penarikan berhasil diproses!'); window.location.href='keuangan.php';</script>";
            } else {
                throw new Exception("Gagal update database");
            }
            
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo "<script>alert('Terjadi kesalahan sistem. Silakan coba lagi.'); window.location.href='keuangan.php';</script>";
        }
    }
}

// ==========================================
// 3. AMBIL RIWAYAT TRANSAKSI
// ==========================================
$transactions = [];
$q_trans = mysqli_query($koneksi, "SELECT * FROM transactions WHERE shop_id = '$shop_id' ORDER BY created_at DESC LIMIT 20");
while($row = mysqli_fetch_assoc($q_trans)) {
    $transactions[] = [
        'type' => $row['type'], // 'in' atau 'out'
        'desc' => $row['description'],
        'amount' => $row['amount'],
        'date' => date('d M Y, H:i', strtotime($row['created_at']))
    ];
}

// Tab Aktif (untuk UX agar tidak kembali ke tab awal saat refresh)
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'summary';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoSwap - Keuangan</title>
    <link rel="icon" type="image/png" href="../../../Assets/img/auth/logo.png">
    <link rel="stylesheet" href="../../../Assets/css/role/seller/keuangan.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px; }
        .card-title { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .btn-edit-settings { background: transparent; border: 1px solid var(--border); padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #666; transition: 0.2s; }
        .btn-edit-settings:hover { background: #f0f0f0; color: var(--dark); }
        .action-group { display: none; gap: 10px; }
        .btn-cancel { background: #eee; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; color: #555; }
        .fee-input:disabled { background-color: #f9f9f9; color: #888; border-color: #eee; }
        /* Style tambahan untuk notifikasi kosong */
        .empty-state { text-align: center; padding: 30px; color: #888; font-style: italic; }
    </style>
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <div class="sidebar-header"><div class="logo" onclick="goToDashboard()" style="cursor:pointer;">ECO<span>SWAP</span></div></div>
            <ul class="sidebar-menu">
                <li class="menu-item"><a href="../buyer/profil.php" class="menu-link"><i class="fas fa-user"></i><span>Biodata Diri</span></a></li>
                <li class="menu-item"><a href="../buyer/alamat.php" class="menu-link"><i class="fas fa-map-marker-alt"></i><span>Alamat</span></a></li>
                <li class="menu-item"><a href="../buyer/histori.php" class="menu-link"><i class="fas fa-history"></i><span>Histori</span></a></li>
                <li class="menu-item active"><a href="dashboard.php" class="menu-link"><i class="fas fa-store"></i><span>Toko Saya</span></a></li>
            </ul>
            <div class="sidebar-footer"><a href="../../../../index.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
        </aside>

        <main class="main-content-wrapper">
            <div class="header"><div class="page-title">Keuangan</div></div>
            <div class="content">
                <div class="finance-container">
                    
                    <div class="finance-tabs">
                        <button class="tab-btn <?php echo ($active_tab == 'summary') ? 'active' : ''; ?>" onclick="switchTab('summary')">Saldo & Riwayat</button>
                        <button class="tab-btn <?php echo ($active_tab == 'settings') ? 'active' : ''; ?>" onclick="switchTab('settings')">Pengaturan Biaya</button>
                    </div>

                    <div id="tab-summary" class="tab-content" style="display: <?php echo ($active_tab == 'summary') ? 'block' : 'none'; ?>;">
                        <div class="balance-card">
                            <div class="balance-label">Total Saldo Aktif</div>
                            <div class="balance-amount">Rp <?php echo number_format($balance, 0, ',', '.'); ?></div>
                            <div class="balance-actions">
                                <button class="btn-action btn-withdraw" onclick="openWithdrawModal()">
                                    <i class="fas fa-money-bill-wave"></i> Tarik Saldo
                                </button>
                            </div>
                        </div>

                        <div class="history">
                            <div class="section-header"><h3 class="section-title">Riwayat Transaksi</h3></div>
                            <div class="transaction-list">
                                <?php if(empty($transactions)): ?>
                                    <div class="empty-state">Belum ada riwayat transaksi.</div>
                                <?php else: ?>
                                    <?php foreach($transactions as $trx): ?>
                                        <div class="transaction-item">
                                            <div class="trans-left">
                                                <?php if($trx['type'] == 'in'): ?> 
                                                    <div class="trans-icon icon-in"><i class="fas fa-arrow-down"></i></div>
                                                <?php else: ?> 
                                                    <div class="trans-icon icon-out"><i class="fas fa-arrow-up"></i></div> 
                                                <?php endif; ?>
                                                
                                                <div class="trans-info">
                                                    <h4><?php echo $trx['desc']; ?></h4>
                                                    <span class="trans-date"><?php echo $trx['date']; ?></span>
                                                </div>
                                            </div>
                                            
                                            <?php if($trx['type'] == 'in'): ?> 
                                                <div class="trans-amount amount-plus">+ Rp <?php echo number_format($trx['amount'], 0, ',', '.'); ?></div>
                                            <?php else: ?> 
                                                <div class="trans-amount amount-minus">- Rp <?php echo number_format($trx['amount'], 0, ',', '.'); ?></div> 
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div id="tab-settings" class="tab-content" style="display: <?php echo ($active_tab == 'settings') ? 'block' : 'none'; ?>;">
                        <form id="formShipping" method="POST">
                            <input type="hidden" name="action" value="save_settings">
                            <input type="hidden" name="ship_type" value="1"> 
                            
                            <div class="manage-card">
                                <div class="card-header-flex">
                                    <div class="card-title" style="border:none; padding:0; margin:0;">
                                        <i class="fas fa-truck" style="color:var(--primary);"></i> Biaya Pengiriman (Flat)
                                    </div>
                                    <button type="button" class="btn-edit-settings" id="btnEditShipping" onclick="toggleEdit('Shipping', true)">
                                        <i class="fas fa-pen"></i> Ubah
                                    </button>
                                </div>
                                
                                <div style="margin-bottom: 20px; font-size: 0.9rem; color: #666; background: #f8f9fa; padding: 10px; border-radius: 8px;">
                                    <i class="fas fa-info-circle"></i> Atur biaya ongkos kirim flat (rata) untuk setiap jasa kirim yang Anda aktifkan. Biaya ini akan dibebankan ke pembeli.
                                </div>

                                <?php 
                                    // Daftar Kurir dengan Icon & Warna Branding
                                    $c_list = [
                                        ['JNE', 'JNE Reguler', 'fa-shipping-fast', ''],
                                        ['JNT', 'J&T Express', 'fa-plane', '#e60012'],
                                        ['SiCepat', 'SiCepat', 'fa-bolt', '#d32f2f'],
                                        ['GoSend', 'GoSend Instant', 'fa-motorcycle', '#00aa13'],
                                        ['Grab', 'GrabExpress', 'fa-biking', '#00b14f'],
                                        ['AnterAja', 'AnterAja', 'fa-paper-plane', '#500095']
                                    ];
                                    
                                    foreach($c_list as $c) {
                                        // Ambil nilai dari JSON database, default 15000 jika belum diset
                                        $val = isset($shipping_costs[$c[0]]) ? $shipping_costs[$c[0]] : 15000;
                                        
                                        echo '
                                        <div class="fee-row">
                                            <div class="fee-label">
                                                <span class="fee-icon" style="color:'.$c[3].'"><i class="fas '.$c[2].'"></i></span> '.$c[1].'
                                            </div>
                                            <div class="fee-input-wrapper">
                                                <span class="currency-prefix">Rp</span>
                                                <input type="number" name="ship_'.$c[0].'" class="fee-input input-shipping" value="'.$val.'" disabled required>
                                            </div>
                                        </div>';
                                    }
                                ?>
                                
                                <div class="action-group" id="actionShipping">
                                    <button type="button" class="btn-cancel" onclick="toggleEdit('Shipping', false)">Batal</button>
                                    <button type="submit" class="btn-save" style="margin-top:0;">Simpan Perubahan</button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <div class="modal-overlay" id="withdrawModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">Tarik Tunai</div>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="withdraw">
                
                <div class="form-group">
                    <label class="form-label">Pilih Metode Tujuan</label>
                    <div class="method-toggle">
                        <button type="button" class="method-btn active" onclick="toggleMethod('ewallet', this)"><i class="fas fa-wallet"></i> E-Wallet</button>
                        <button type="button" class="method-btn" onclick="toggleMethod('bank', this)"><i class="fas fa-university"></i> Bank</button>
                    </div>
                </div>

                <div id="ewalletOptions" class="bank-options">
                    <label>
                        <input type="radio" name="destination_method" value="Dana" class="bank-radio" checked>
                        <div class="bank-card"><img src="https://placehold.co/100x50/118EEA/ffffff?text=DANA" class="payment-logo"><span>DANA</span></div>
                    </label>
                    <label>
                        <input type="radio" name="destination_method" value="OVO" class="bank-radio">
                        <div class="bank-card"><img src="https://placehold.co/100x50/4C2A86/ffffff?text=OVO" class="payment-logo"><span>OVO</span></div>
                    </label>
                    <label>
                        <input type="radio" name="destination_method" value="GoPay" class="bank-radio">
                        <div class="bank-card"><img src="https://placehold.co/100x50/00A5CF/ffffff?text=GoPay" class="payment-logo"><span>GoPay</span></div>
                    </label>
                </div>

                <div id="bankOptions" class="bank-options" style="display:none;">
                    <label>
                        <input type="radio" name="destination_method" value="BCA" class="bank-radio">
                        <div class="bank-card"><img src="https://placehold.co/100x50/003399/ffffff?text=BCA" class="payment-logo"><span>BCA</span></div>
                    </label>
                    <label>
                        <input type="radio" name="destination_method" value="Mandiri" class="bank-radio">
                        <div class="bank-card"><img src="https://placehold.co/100x50/FFB700/000000?text=Mandiri" class="payment-logo"><span>Mandiri</span></div>
                    </label>
                    <label>
                        <input type="radio" name="destination_method" value="BRI" class="bank-radio">
                        <div class="bank-card"><img src="https://placehold.co/100x50/00529C/ffffff?text=BRI" class="payment-logo"><span>BRI</span></div>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor Rekening / HP</label>
                    <input type="text" name="account_number" class="form-input" placeholder="Contoh: 08123456789" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nominal Penarikan</label>
                    <div class="input-wrapper">
                        <span class="input-prefix">Rp</span>
                        <input type="number" name="amount" class="form-input has-prefix" placeholder="0" min="10000" max="<?php echo $balance; ?>" required>
                    </div>
                    <div style="font-size:0.8rem; color:#666; margin-top:5px;">Min. Penarikan Rp 10.000</div>
                </div>

                <div class="withdraw-note"><i class="fas fa-info-circle"></i> Penarikan akan diproses maksimal 1x24 jam kerja.</div>
                
                <button type="submit" class="btn-submit">Konfirmasi Penarikan</button>
            </form>
        </div>
    </div>

    <script>
        function goToDashboard() { window.location.href = '../buyer/dashboard.php'; }
        
        // Tab Switcher
        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active')); 
            event.target.classList.add('active');
            
            document.getElementById('tab-summary').style.display = (tabName === 'summary') ? 'block' : 'none';
            document.getElementById('tab-settings').style.display = (tabName === 'settings') ? 'block' : 'none';
            
            // Update URL tanpa reload agar pas refresh tetap di tab yang sama
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
        }

        // Toggle Edit Mode untuk Form Settings
        function toggleEdit(type, isEditing) {
            const inputs = document.querySelectorAll('.input-' + type.toLowerCase());
            const btnEdit = document.getElementById('btnEdit' + type);
            const actionGroup = document.getElementById('action' + type);
            
            if (isEditing) { 
                inputs.forEach(el => el.removeAttribute('disabled')); 
                btnEdit.style.display = 'none'; 
                actionGroup.style.display = 'flex'; 
                if(inputs.length > 0) inputs[0].focus(); 
            } else { 
                document.getElementById('form' + type).reset(); 
                inputs.forEach(el => el.setAttribute('disabled', 'true')); 
                btnEdit.style.display = 'block'; 
                actionGroup.style.display = 'none'; 
            }
        }

        // Modal Logic
        const modal = document.getElementById('withdrawModal');
        function openWithdrawModal() { modal.classList.add('open'); }
        function closeModal() { modal.classList.remove('open'); }
        window.onclick = function(e) { if (e.target == modal) closeModal(); }

        // Toggle Metode Penarikan (Bank vs E-Wallet)
        function toggleMethod(type, btnElement) {
            document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('active')); 
            btnElement.classList.add('active');
            
            const ewalletDiv = document.getElementById('ewalletOptions'); 
            const bankDiv = document.getElementById('bankOptions');
            
            // Reset radio buttons saat switch
            const radios = document.querySelectorAll('input[name="destination_method"]');
            radios.forEach(r => r.checked = false);

            if (type === 'bank') { 
                ewalletDiv.style.display = 'none'; 
                bankDiv.style.display = 'grid'; 
                bankDiv.querySelector('input').checked = true; // Auto select first option
            } else { 
                bankDiv.style.display = 'none'; 
                ewalletDiv.style.display = 'grid'; 
                ewalletDiv.querySelector('input').checked = true; 
            }
        }
    </script>
</body>
</html>