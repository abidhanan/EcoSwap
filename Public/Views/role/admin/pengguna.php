<?php
session_start();
include '../../../Auth/koneksi.php';

// Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../Auth/login.php");
    exit();
}

// --- LOGIKA TAMBAH ADMIN ---
if (isset($_POST['action']) && $_POST['action'] == 'add_admin') {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone']);
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $cek_email = mysqli_query($koneksi, "SELECT email FROM users WHERE email = '$email'");
    if(mysqli_num_rows($cek_email) > 0) {
        echo "<script>alert('Email sudah terdaftar!');</script>";
    } else {
        $query = "INSERT INTO users (name, email, password, phone_number, role, created_at) 
                  VALUES ('$name', '$email', '$hashed_password', '$phone', 'admin', NOW())";
        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Admin berhasil ditambahkan!'); window.location.href='pengguna.php';</script>";
        }
    }
}

// --- LOGIKA HAPUS USER ---
if (isset($_POST['action']) && $_POST['action'] == 'delete_user') {
    $user_id = $_POST['user_id'];
    mysqli_query($koneksi, "DELETE FROM users WHERE user_id='$user_id'");
    echo "<script>alert('Pengguna dihapus.'); window.location.href='pengguna.php';</script>";
}

// --- 1. AMBIL DATA ADMIN ---
$admins = [];
$q_adm = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC");
while($row = mysqli_fetch_assoc($q_adm)) { 
    $row['img_src'] = !empty($row['profile_picture']) ? $row['profile_picture'] : "https://ui-avatars.com/api/?name=".urlencode($row['name']);
    $admins[] = $row; 
}

// --- 2. AMBIL DATA PENJUAL (TOKO) ---
// Join user dengan shops untuk dapat data toko
$sellers = [];
$q_seller = mysqli_query($koneksi, "
    SELECT u.*, s.shop_id, s.shop_name, s.shop_image, s.shop_city, s.created_at as shop_created 
    FROM users u 
    JOIN shops s ON u.user_id = s.user_id 
    ORDER BY s.created_at DESC
");
while($row = mysqli_fetch_assoc($q_seller)) {
    // Gunakan Foto Toko jika ada, fallback ke inisial toko
    $row['img_src'] = !empty($row['shop_image']) ? $row['shop_image'] : "https://ui-avatars.com/api/?name=".urlencode($row['shop_name'])."&background=random";
    $sellers[] = $row;
}

// --- 3. AMBIL DATA PEMBELI (USER UMUM) ---
// Semua user yang role != admin (bisa jadi dia seller juga, tapi di sini ditampilkan sisi personalnya)
$buyers = [];
$q_buyer = mysqli_query($koneksi, "SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
while($row = mysqli_fetch_assoc($q_buyer)) {
    $row['img_src'] = !empty($row['profile_picture']) ? $row['profile_picture'] : "https://ui-avatars.com/api/?name=".urlencode($row['name']);
    
    // Cek apakah dia punya toko (untuk flag di modal detail nanti)
    $uid = $row['user_id'];
    $cek_toko = mysqli_query($koneksi, "SELECT * FROM shops WHERE user_id='$uid'");
    $toko_data = mysqli_fetch_assoc($cek_toko);
    
    $row['has_shop'] = $toko_data ? true : false;
    $row['shop_data'] = $toko_data; // Simpan data toko untuk modal
    
    $buyers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Pengguna</title>
    <link rel="stylesheet" href="../../../Assets/css/role/admin/pengguna.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body.no-scroll { overflow: hidden; }
        .user-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-add-admin { background-color: #4e73df; color: #fff; padding: 10px 20px; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin { background: #e2e6ea; color: #4e73df; border: 1px solid #b8daff; }
        .role-seller { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .role-buyer { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .user-avatar-small { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid #eee; }
        .user-cell { display: flex; align-items: center; font-weight: 600; color: #333; }
        
        /* Table */
        .product-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .product-table th { text-align: left; padding: 15px; color: #888; font-size: 0.85rem; border-bottom: 1px solid #eee; font-weight: 600; }
        .product-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; color: #555; font-size: 0.9rem; }
        
        .btn-view { padding: 6px 12px; background: #f8f9fa; border: 1px solid #ddd; color: #333; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; margin-right: 5px; }
        .btn-delete { padding: 6px 12px; background: #fff5f5; border: 1px solid #ffcccc; color: #dc3545; border-radius: 6px; cursor: pointer; font-size: 0.8rem; }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: white; width: 500px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; animation: slideUp 0.3s; }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .modal-header { padding: 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 25px; }
        .modal-actions { padding: 15px 25px; background: #f8f9fa; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
        
        /* Modal Tabs */
        .modal-tabs { display: flex; border-bottom: 1px solid #eee; margin-bottom: 20px; }
        .m-tab-btn { flex: 1; padding: 10px; background: none; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-weight: 600; color: #777; transition: 0.2s; }
        .m-tab-btn.active { color: #4e73df; border-bottom-color: #4e73df; }
        .m-tab-content { display: none; }
        .m-tab-content.active { display: block; animation: fadeIn 0.3s; }
        
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #eee; font-size: 0.9rem; }
        .detail-label { color: #777; }
        .detail-value { font-weight: 600; color: #333; text-align: right; }
        
        /* Form */
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-verify { padding: 10px 20px; background: #4e73df; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-cancel { padding: 10px 20px; background: white; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><div class="logo"><i class="fas fa-recycle"></i> ECO<span>SWAP</span></div></div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
            <li><a href="produk.php"><i class="fas fa-box"></i> <span>Produk</span></a></li>
            <li class="active"><a href="pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a></li>
            <li><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
            <li><a href="laporan.php"><i class="fas fa-headset"></i> <span>Laporan</span></a></li>
        </ul>
        <div class="sidebar-footer"><a href="../../../../index.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text"><h2>Manajemen Pengguna</h2><p>Kelola akun admin, penjual, dan pembeli.</p></div>
        </header>

        <section class="card-panel" style="margin-bottom: 30px;">
            <div class="user-header">
                <h3>Daftar Admin</h3>
                <button class="btn-add-admin" onclick="openAddModal()"><i class="fas fa-plus"></i> Tambah Admin</button>
            </div>
            <table class="product-table"> 
                <thead><tr><th>Nama</th><th>Email</th><th>No. HP</th><th>Role</th></tr></thead>
                <tbody>
                    <?php foreach($admins as $adm): ?>
                    <tr>
                        <td><div class="user-cell"><img src="<?php echo $adm['img_src']; ?>" class="user-avatar-small"><?php echo $adm['name']; ?></div></td>
                        <td><?php echo $adm['email']; ?></td>
                        <td><?php echo $adm['phone_number']; ?></td>
                        <td><span class="role-badge role-admin">Admin</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="card-panel" style="margin-bottom: 30px;">
            <div class="user-header"><h3>Daftar Penjual (Toko)</h3></div>
            <table class="product-table">
                <thead><tr><th>Nama Toko</th><th>Pemilik (User)</th><th>Kota</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if(empty($sellers)): ?><tr><td colspan="4" align="center" style="padding:20px; color:#888;">Belum ada toko.</td></tr><?php endif; ?>
                    <?php foreach($sellers as $slr): ?>
                    <tr>
                        <td><div class="user-cell"><img src="<?php echo $slr['img_src']; ?>" class="user-avatar-small"><?php echo $slr['shop_name']; ?></div></td>
                        <td><?php echo $slr['name']; ?></td>
                        <td><?php echo !empty($slr['shop_city']) ? $slr['shop_city'] : '-'; ?></td>
                        <td>
                            <button class="btn-action btn-view" onclick='openDetailSeller(<?php echo json_encode($slr); ?>)'>Detail Toko</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="card-panel">
            <div class="user-header"><h3>Daftar Pembeli (User Umum)</h3></div>
            <table class="product-table">
                <thead><tr><th>Nama</th><th>Email</th><th>No. HP</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach($buyers as $byr): ?>
                    <tr>
                        <td><div class="user-cell"><img src="<?php echo $byr['img_src']; ?>" class="user-avatar-small"><?php echo $byr['name']; ?></div></td>
                        <td><?php echo $byr['email']; ?></td>
                        <td><?php echo $byr['phone_number']; ?></td>
                        <td>
                            <div style="display:flex;">
                                <button class="btn-action btn-view" onclick='openDetailUser(<?php echo json_encode($byr); ?>)'>Detail</button>
                                <form method="POST" onsubmit="return confirm('Hapus user ini?')" style="margin:0;">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $byr['user_id']; ?>">
                                    <button class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    <div class="modal-overlay" id="addAdminModal">
        <div class="modal-box">
            <div class="modal-header"><h3>Tambah Admin</h3><i class="fas fa-times close-modal" onclick="closeAddModal()"></i></div>
            <form method="POST">
                <input type="hidden" name="action" value="add_admin">
                <div class="modal-body">
                    <div class="form-group"><label class="form-label">Nama</label><input type="text" name="name" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">No. HP</label><input type="text" name="phone" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-input" required></div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">Batal</button>
                    <button type="submit" class="btn-verify">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal-box">
            <div class="modal-header"><h3>Informasi Pengguna</h3><i class="fas fa-times close-modal" onclick="closeDetailModal()"></i></div>
            
            <div class="modal-tabs" id="modalTabs" style="display:none;">
                <button class="m-tab-btn active" onclick="switchModalTab('personal')">Profil Pribadi</button>
                <button class="m-tab-btn" onclick="switchModalTab('shop')">Info Toko</button>
            </div>

            <div class="modal-body">
                <div id="tabPersonal" class="m-tab-content active">
                    <div style="text-align:center; margin-bottom:20px;">
                        <img id="dImg" src="" style="width:80px; height:80px; border-radius:50%; border:3px solid #f8f9fa;">
                        <h3 id="dName" style="margin:10px 0 5px 0;"></h3>
                        <span id="dRoleBadge"></span>
                    </div>
                    <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value" id="dEmail"></span></div>
                    <div class="detail-row"><span class="detail-label">No. HP</span><span class="detail-value" id="dPhone"></span></div>
                    <div class="detail-row"><span class="detail-label">Alamat</span><span class="detail-value" id="dAddress"></span></div>
                    <div class="detail-row"><span class="detail-label">Bergabung</span><span class="detail-value" id="dJoin"></span></div>
                </div>

                <div id="tabShop" class="m-tab-content">
                    <div style="text-align:center; margin-bottom:20px;">
                        <img id="sImg" src="" style="width:80px; height:80px; border-radius:50%; border:3px solid #fff3cd;">
                        <h3 id="sName" style="margin:10px 0 5px 0;"></h3>
                        <span class="role-badge role-seller">Seller</span>
                    </div>
                    <div class="detail-row"><span class="detail-label">ID Toko</span><span class="detail-value" id="sId"></span></div>
                    <div class="detail-row"><span class="detail-label">Kota Asal</span><span class="detail-value" id="sCity"></span></div>
                    <div class="detail-row"><span class="detail-label">Dibuat Pada</span><span class="detail-value" id="sJoin"></span></div>
                </div>
            </div>

            <div class="modal-actions"><button type="button" class="btn-cancel" onclick="closeDetailModal()">Tutup</button></div>
        </div>
    </div>

    <script>
        const addModal = document.getElementById('addAdminModal');
        const detailModal = document.getElementById('detailModal');
        const modalTabs = document.getElementById('modalTabs');

        function openAddModal() { addModal.classList.add('open'); document.body.classList.add('no-scroll'); }
        function closeAddModal() { addModal.classList.remove('open'); document.body.classList.remove('no-scroll'); }
        function closeDetailModal() { detailModal.classList.remove('open'); document.body.classList.remove('no-scroll'); }

        // Switch Tab di Modal
        function switchModalTab(tabName) {
            document.querySelectorAll('.m-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.m-tab-content').forEach(c => c.classList.remove('active'));
            
            if(tabName === 'personal') {
                document.querySelectorAll('.m-tab-btn')[0].classList.add('active');
                document.getElementById('tabPersonal').classList.add('active');
            } else {
                document.querySelectorAll('.m-tab-btn')[1].classList.add('active');
                document.getElementById('tabShop').classList.add('active');
            }
        }

        // Buka Detail Pembeli (User Biasa)
        function openDetailUser(data) {
            resetModal();
            fillPersonalData(data);
            
            // Cek jika user ini punya toko juga (dari data fetch PHP)
            if (data.has_shop) {
                modalTabs.style.display = 'flex';
                fillShopData(data.shop_data);
            } else {
                modalTabs.style.display = 'none';
            }
            
            detailModal.classList.add('open');
            document.body.classList.add('no-scroll');
        }

        // Buka Detail Penjual (Dari Tabel Penjual)
        function openDetailSeller(data) {
            resetModal();
            // Data personal (user) ada di object data juga karena join query
            fillPersonalData(data);
            
            // Data toko
            modalTabs.style.display = 'flex';
            fillShopData({
                shop_image: data.shop_image,
                shop_name: data.shop_name,
                shop_id: data.shop_id,
                shop_city: data.shop_city,
                created_at: data.shop_created
            });
            
            // Default buka tab toko
            switchModalTab('shop');
            detailModal.classList.add('open');
            document.body.classList.add('no-scroll');
        }

        function fillPersonalData(data) {
            document.getElementById('dImg').src = data.img_src || data.profile_picture;
            document.getElementById('dName').innerText = data.name;
            document.getElementById('dRoleBadge').innerHTML = '<span class="role-badge role-buyer">Buyer</span>';
            document.getElementById('dEmail').innerText = data.email;
            document.getElementById('dPhone').innerText = data.phone_number;
            document.getElementById('dAddress').innerText = data.address || '-';
            document.getElementById('dJoin').innerText = data.created_at;
        }

        function fillShopData(shop) {
            const img = shop.shop_image ? shop.shop_image : `https://ui-avatars.com/api/?name=${shop.shop_name}&background=random`;
            document.getElementById('sImg').src = img;
            document.getElementById('sName').innerText = shop.shop_name;
            document.getElementById('sId').innerText = '#' + shop.shop_id;
            document.getElementById('sCity').innerText = shop.shop_city || '-';
            document.getElementById('sJoin').innerText = shop.created_at;
        }

        function resetModal() {
            switchModalTab('personal'); // Reset ke tab personal
        }

        window.onclick = function(e) {
            if (e.target == addModal) closeAddModal();
            if (e.target == detailModal) closeDetailModal();
        }
    </script>
</body>
</html>