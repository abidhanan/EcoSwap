<?php
session_start();
include '../../../Auth/koneksi.php';

// Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../Auth/login.php");
    exit();
}

// --- LOGIKA ACTION (TAMBAH, HAPUS, BLOKIR) ---
if (isset($_POST['action'])) {
    $act = $_POST['action'];
    
    // 1. Tambah Admin
    if ($act == 'add_admin') {
        $name = mysqli_real_escape_string($koneksi, $_POST['name']);
        $email = mysqli_real_escape_string($koneksi, $_POST['email']);
        $phone = mysqli_real_escape_string($koneksi, $_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $cek = mysqli_query($koneksi, "SELECT email FROM users WHERE email='$email'");
        if(mysqli_num_rows($cek) > 0) {
            echo "<script>alert('Email sudah terdaftar!');</script>";
        } else {
            $q = "INSERT INTO users (name, email, password, phone_number, role, status, created_at) VALUES ('$name', '$email', '$password', '$phone', 'admin', 'active', NOW())";
            if (mysqli_query($koneksi, $q)) {
                echo "<script>alert('Admin berhasil ditambahkan!'); window.location.href='pengguna.php';</script>";
            }
        }
    }
    
    // 2. Hapus User
    elseif ($act == 'delete_user') {
        $uid = $_POST['user_id'];
        mysqli_query($koneksi, "DELETE FROM users WHERE user_id='$uid'");
        echo "<script>alert('Pengguna dihapus.'); window.location.href='pengguna.php';</script>";
    }
    
    // 3. Blokir / Buka Blokir
    elseif ($act == 'toggle_status') {
        $uid = $_POST['user_id'];
        $new_status = $_POST['new_status'];
        mysqli_query($koneksi, "UPDATE users SET status='$new_status' WHERE user_id='$uid'");
        $msg = ($new_status == 'banned') ? "Akun berhasil diblokir." : "Blokir akun dibuka.";
        echo "<script>alert('$msg'); window.location.href='pengguna.php';</script>";
    }
}

// --- QUERY DATA ---
// 1. Admin
$admins = [];
$q_adm = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC");
while($row = mysqli_fetch_assoc($q_adm)) { 
    $row['img_src'] = !empty($row['profile_picture']) ? $row['profile_picture'] : "https://ui-avatars.com/api/?name=".urlencode($row['name']);
    $row['status'] = isset($row['status']) ? $row['status'] : 'active';
    $admins[] = $row; 
}

// 2. Seller (Toko) - Ambil Data User & Toko
$sellers = [];
$q_seller = mysqli_query($koneksi, "
    SELECT u.*, s.shop_id, s.shop_name, s.shop_image, s.shop_city, s.created_at as shop_created 
    FROM users u 
    JOIN shops s ON u.user_id = s.user_id 
    ORDER BY s.created_at DESC
");
while($row = mysqli_fetch_assoc($q_seller)) {
    // img_src untuk tabel (Foto Toko)
    $row['img_src'] = !empty($row['shop_image']) ? $row['shop_image'] : "https://ui-avatars.com/api/?name=".urlencode($row['shop_name'])."&background=random";
    // user_pic untuk modal (Foto Profil Asli User)
    $row['user_pic'] = !empty($row['profile_picture']) ? $row['profile_picture'] : "https://ui-avatars.com/api/?name=".urlencode($row['name']);
    
    $row['status'] = isset($row['status']) ? $row['status'] : 'active';
    $sellers[] = $row;
}

// 3. Buyer (User Biasa)
$buyers = [];
$q_buyer = mysqli_query($koneksi, "SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC");
while($row = mysqli_fetch_assoc($q_buyer)) {
    $row['img_src'] = !empty($row['profile_picture']) ? $row['profile_picture'] : "https://ui-avatars.com/api/?name=".urlencode($row['name']);
    $row['status'] = isset($row['status']) ? $row['status'] : 'active';
    
    // Cek Toko
    $uid = $row['user_id'];
    $cek_toko = mysqli_query($koneksi, "SELECT * FROM shops WHERE user_id='$uid'");
    $toko_data = mysqli_fetch_assoc($cek_toko);
    
    $row['has_shop'] = $toko_data ? true : false;
    $row['shop_data'] = $toko_data; 
    
    $buyers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoSwap Admin - Pengguna</title>
    <link rel="icon" type="image/png" href="../../../Assets/img/auth/logo.png">
    <link rel="stylesheet" href="../../../Assets/css/role/admin/pengguna.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styling Modal Overlay agar Rapi & Kompak */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.open { display: flex; }
        
        .modal-box { 
            background: white; width: 450px; /* Ukuran pas, tidak terlalu besar */
            border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
            overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;
        }
        
        .modal-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #fff; }
        .modal-header h3 { margin: 0; font-size: 1.1rem; color: #333; }
        .close-modal { cursor: pointer; color: #999; font-size: 1.2rem; transition: 0.2s; }
        .close-modal:hover { color: #333; }
        
        .modal-body { padding: 20px; overflow-y: auto; }
        
        .profile-header { text-align: center; margin-bottom: 20px; }
        .profile-img-lg { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #f8f9fa; object-fit: cover; margin-bottom: 8px; }
        .profile-name { font-size: 1.1rem; font-weight: 700; color: #333; margin: 0; }
        .profile-role { font-size: 0.8rem; color: #777; margin-top: 4px; }

        .info-grid { display: flex; flex-direction: column; gap: 10px; }
        .info-item { background: #fcfcfc; padding: 10px 15px; border-radius: 8px; border: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .info-label { font-size: 0.75rem; color: #888; font-weight: 600; text-transform: uppercase; }
        .info-value { font-size: 0.9rem; color: #333; font-weight: 600; }

        .modal-tabs { display: flex; border-bottom: 1px solid #eee; padding: 0 20px; background: #fafafa; }
        .m-tab-btn { flex: 1; padding: 12px; background: none; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-weight: 600; color: #777; font-size: 0.9rem; transition: 0.2s; }
        .m-tab-btn:hover { background: #f0f0f0; }
        .m-tab-btn.active { color: #333; border-bottom-color: var(--primary, #FFD700); background: #fff; }
        .m-tab-content { display: none; }
        .m-tab-content.active { display: block; animation: fadeIn 0.2s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .modal-actions { padding: 15px 20px; background: #f8f9fa; border-top: 1px solid #eee; display: flex; justify-content: flex-end; }
        .btn-cancel { padding: 8px 16px; background: white; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; font-weight: 600; color: #555; }
        .btn-cancel:hover { background: #eee; }

        /* Form Add Admin */
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.85rem; color: #555; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box; }
        .btn-verify { padding: 10px 20px; background: #000; color: #FFD700; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        
        /* Table Styles (Tetap sama) */
        .product-table th { background-color: #f8f9fa; color: #555; padding: 15px; text-align: left; font-size: 0.85rem; border-bottom: 2px solid #eee; }
        .product-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; color: #333; font-size: 0.9rem; }
        .user-cell { display: flex; align-items: center; gap: 12px; font-weight: 600; }
        .user-avatar-small { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd; }
        .status-badge { padding: 4px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-active { background: #d1e7dd; color: #0f5132; }
        .status-banned { background: #f8d7da; color: #721c24; }
        .role-admin { background: #e2e6ea; color: #004085; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: bold; }
        
        .btn-group { display: flex; gap: 5px; }
        .btn-sm { padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: 0.2s; }
        .btn-view:hover { background: #e2e6ea; }
        .btn-ban:hover { background: #ffe5e5; }
        .btn-unban:hover { background: #d1f2eb; }
        .btn-delete:hover { background: #ffe5e5; }
        .btn-add-admin { background: var(--primary, #FFD700); color: #000; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; }
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
            <li class="active"><a href="pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a></li>
            <li><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
            <li><a href="laporan.php"><i class="fas fa-headset"></i> <span>Laporan</span></a></li>
        </ul>
        <div class="sidebar-footer"><a href="../../../../index.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text"><h2>Manajemen Pengguna</h2></div>
        </header>

        <section class="card-panel" style="margin-bottom: 30px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
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
                        <td><span class="role-admin">Admin</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="card-panel" style="margin-bottom: 30px;">
            <h3>Daftar Penjual (Toko)</h3>
            <table class="product-table">
                <thead><tr><th>Nama Toko</th><th>Pemilik</th><th>Kota</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if(empty($sellers)): ?><tr><td colspan="5" align="center" style="padding:20px; color:#888;">Belum ada toko.</td></tr><?php endif; ?>
                    <?php foreach($sellers as $slr): ?>
                    <tr>
                        <td><div class="user-cell"><img src="<?php echo $slr['img_src']; ?>" class="user-avatar-small"><?php echo $slr['shop_name']; ?></div></td>
                        <td><?php echo $slr['name']; ?></td>
                        <td><?php echo !empty($slr['shop_city']) ? $slr['shop_city'] : '-'; ?></td>
                        <td>
                            <?php if($slr['status'] == 'banned'): ?>
                                <span class="status-badge status-banned">Diblokir</span>
                            <?php else: ?>
                                <span class="status-badge status-active">Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn-sm btn-view" onclick='openDetailSeller(<?php echo json_encode($slr); ?>)'>Detail</button>
                                
                                <form method="POST" onsubmit="return confirm('Ubah status akun ini?')" style="margin:0;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $slr['user_id']; ?>">
                                    <input type="hidden" name="new_status" value="<?php echo ($slr['status'] == 'banned' ? 'active' : 'banned'); ?>">
                                    
                                    <?php if($slr['status'] == 'banned'): ?>
                                        <button type="submit" class="btn-sm btn-unban" title="Buka Blokir"><i class="fas fa-unlock"></i></button>
                                    <?php else: ?>
                                        <button type="submit" class="btn-sm btn-ban" title="Blokir Akun"><i class="fas fa-ban"></i></button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="card-panel">
            <h3>Daftar Pembeli</h3>
            <table class="product-table">
                <thead><tr><th>Nama</th><th>Email</th><th>No. HP</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach($buyers as $byr): ?>
                    <tr>
                        <td><div class="user-cell"><img src="<?php echo $byr['img_src']; ?>" class="user-avatar-small"><?php echo $byr['name']; ?></div></td>
                        <td><?php echo $byr['email']; ?></td>
                        <td><?php echo $byr['phone_number']; ?></td>
                        <td>
                            <?php if($byr['status'] == 'banned'): ?>
                                <span class="status-badge status-banned">Diblokir</span>
                            <?php else: ?>
                                <span class="status-badge status-active">Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn-sm btn-view" onclick='openDetailUser(<?php echo json_encode($byr); ?>)'>Detail</button>
                                
                                <form method="POST" onsubmit="return confirm('Ubah status akun ini?')" style="margin:0;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $byr['user_id']; ?>">
                                    <input type="hidden" name="new_status" value="<?php echo ($byr['status'] == 'banned' ? 'active' : 'banned'); ?>">
                                    
                                    <?php if($byr['status'] == 'banned'): ?>
                                        <button type="submit" class="btn-sm btn-unban" title="Buka Blokir"><i class="fas fa-unlock"></i></button>
                                    <?php else: ?>
                                        <button type="submit" class="btn-sm btn-ban" title="Blokir Akun"><i class="fas fa-ban"></i></button>
                                    <?php endif; ?>
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
        <div class="modal-box" style="width: 400px;">
            <div class="modal-header"><h3>Tambah Admin</h3><i class="fas fa-times close-modal" onclick="closeAddModal()"></i></div>
            <form method="POST">
                <input type="hidden" name="action" value="add_admin">
                <div class="modal-body">
                    <div class="form-group"><label class="form-label">Nama Lengkap</label><input type="text" name="name" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">No. Handphone</label><input type="text" name="phone" class="form-input" required></div>
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
            <div class="modal-header"><h3>Detail Pengguna</h3><i class="fas fa-times close-modal" onclick="closeDetailModal()"></i></div>
            
            <div class="modal-tabs" id="modalTabs" style="display:none;">
                <button class="m-tab-btn active" onclick="switchModalTab('personal')">Profil Pribadi</button>
                <button class="m-tab-btn" onclick="switchModalTab('shop')">Info Toko</button>
            </div>

            <div class="modal-body">
                <div id="tabPersonal" class="m-tab-content active">
                    <div class="profile-header">
                        <img id="dImg" src="" class="profile-img-lg">
                        <h3 id="dName" class="profile-name"></h3>
                        <div id="dRoleBadge" class="profile-role"></div>
                    </div>
                    <div class="info-grid">
                        <div class="info-item"><span class="info-label">Email</span><span class="info-value" id="dEmail"></span></div>
                        <div class="info-item"><span class="info-label">No. HP</span><span class="info-value" id="dPhone"></span></div>
                        <div class="info-item"><span class="info-label">Alamat</span><span class="info-value" id="dAddress"></span></div>
                        <div class="info-item"><span class="info-label">Bergabung</span><span class="info-value" id="dJoin"></span></div>
                    </div>
                </div>

                <div id="tabShop" class="m-tab-content">
                    <div class="profile-header">
                        <img id="sImg" src="" class="profile-img-lg" style="border-color:#fff3cd;">
                        <h3 id="sName" class="profile-name"></h3>
                        <span class="role-seller" style="font-size:0.8rem; padding:4px 8px; border-radius:6px; background:#fff3cd; color:#856404; font-weight:600;">Seller / Merchant</span>
                    </div>
                    <div class="info-grid">
                        <div class="info-item"><span class="info-label">ID Toko</span><span class="info-value" id="sId"></span></div>
                        <div class="info-item"><span class="info-label">Kota Asal</span><span class="info-value" id="sCity"></span></div>
                        <div class="info-item"><span class="info-label">Dibuat Pada</span><span class="info-value" id="sJoin"></span></div>
                    </div>
                </div>
            </div>

            <div class="modal-actions"><button type="button" class="btn-cancel" onclick="closeDetailModal()">Tutup</button></div>
        </div>
    </div>

    <script>
        const addModal = document.getElementById('addAdminModal');
        const detailModal = document.getElementById('detailModal');
        const modalTabs = document.getElementById('modalTabs');

        function openAddModal() { addModal.classList.add('open'); }
        function closeAddModal() { addModal.classList.remove('open'); }
        function closeDetailModal() { detailModal.classList.remove('open'); }

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

        function openDetailUser(data) {
            resetModal();
            fillPersonalData(data); // Foto & Data dari User
            
            if (data.has_shop) {
                modalTabs.style.display = 'flex';
                fillShopData(data.shop_data); // Foto & Data dari Toko
            } else {
                modalTabs.style.display = 'none';
            }
            detailModal.classList.add('open');
        }

        function openDetailSeller(data) {
            resetModal();
            fillPersonalData(data); // Foto User Asli
            modalTabs.style.display = 'flex';
            
            // Data toko dari baris tabel seller
            fillShopData({
                shop_image: data.img_src, // Foto Toko
                shop_name: data.shop_name,
                shop_id: data.shop_id,
                shop_city: data.shop_city,
                created_at: data.shop_created
            });
            switchModalTab('shop');
            detailModal.classList.add('open');
        }

        function fillPersonalData(data) {
            // Gunakan 'user_pic' jika ada (dari seller query), atau 'img_src' (dari buyer query)
            const photo = data.user_pic ? data.user_pic : (data.img_src || data.profile_picture);
            document.getElementById('dImg').src = photo;
            document.getElementById('dName').innerText = data.name;
            document.getElementById('dRoleBadge').innerHTML = data.status === 'banned' ? '<span style="color:red; font-weight:bold;">Akun Diblokir</span>' : '<span style="color:green; font-weight:bold;">Akun Aktif</span>';
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

        function resetModal() { switchModalTab('personal'); }
        window.onclick = function(e) { if (e.target == addModal) closeAddModal(); if (e.target == detailModal) closeDetailModal(); }
    </script>
</body>
</html>