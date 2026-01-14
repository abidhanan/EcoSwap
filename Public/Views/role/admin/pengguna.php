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
    
    // Hash Password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Cek Email Kembar
    $cek_email = mysqli_query($koneksi, "SELECT email FROM users WHERE email = '$email'");
    if(mysqli_num_rows($cek_email) > 0) {
        echo "<script>alert('Email sudah terdaftar!');</script>";
    } else {
        $query = "INSERT INTO users (name, email, password, phone_number, role, created_at) 
                  VALUES ('$name', '$email', '$hashed_password', '$phone', 'admin', NOW())";
        
        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Admin baru berhasil ditambahkan!'); window.location.href='pengguna.php';</script>";
        } else {
            echo "<script>alert('Gagal menambah admin: " . mysqli_error($koneksi) . "');</script>";
        }
    }
}

// --- LOGIKA HAPUS USER ---
if (isset($_POST['action']) && $_POST['action'] == 'delete_user') {
    $user_id = $_POST['user_id'];
    if(mysqli_query($koneksi, "DELETE FROM users WHERE user_id='$user_id'")) {
        echo "<script>alert('Pengguna berhasil dihapus.'); window.location.href='pengguna.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus pengguna.');</script>";
    }
}

// --- AMBIL DATA ADMIN ---
$admins = [];
$q_adm = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC");
while($row = mysqli_fetch_assoc($q_adm)) { $admins[] = $row; }

// --- AMBIL DATA USER (PEMISAHAN BUYER & SELLER) ---
// Mengambil semua user non-admin, lalu dipisahkan saat rendering atau logic query
// Jika di database satu user punya satu row dengan role 'buyer' atau 'seller', query ini sudah cukup.
// Jika satu email bisa punya dua akun (row berbeda), query ini juga aman.

// Ambil Pembeli (Buyer)
$buyers = [];
$q_buyer = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'buyer' ORDER BY created_at DESC");
while($row = mysqli_fetch_assoc($q_buyer)) { $buyers[] = $row; }

// Ambil Penjual (Seller/Toko)
$sellers = [];
$q_seller = mysqli_query($koneksi, "SELECT * FROM users WHERE role = 'seller' ORDER BY created_at DESC");
while($row = mysqli_fetch_assoc($q_seller)) { $sellers[] = $row; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Pengguna</title>
    <link rel="stylesheet" href="../../../Assets/css/role/admin/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Block Scroll saat Modal Open */
        body.no-scroll { overflow: hidden; }

        /* CSS Khusus Halaman Pengguna */
        .user-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-add-admin { background-color: #4e73df; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-add-admin:hover { background-color: #375a7f; transform: translateY(-2px); }

        /* Role Badges */
        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .role-admin { background: #e2e6ea; color: #4e73df; border: 1px solid #b8daff; }
        .role-seller { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .role-buyer { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

        .user-avatar-small { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid #eee; background: #f8f9fa; }
        .user-cell { display: flex; align-items: center; font-weight: 500; color: #333; }

        /* Table Styles (Rapikan Simetris) */
        .product-table { width: 100%; border-collapse: collapse; table-layout: fixed; } /* table-layout: fixed agar lebar kolom konsisten */
        .product-table th { text-align: left; padding: 15px; color: #888; font-size: 0.85rem; border-bottom: 1px solid #eee; font-weight: 600; }
        .product-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; color: #555; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .btn-action { padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.8rem; margin-right: 5px; transition: 0.2s; font-weight: 600; }
        .btn-view { background: #f8f9fa; border: 1px solid #ddd; color: #333; }
        .btn-view:hover { background: #e2e6ea; border-color: #adb5bd; }
        .btn-delete { background: #fff5f5; border: 1px solid #ffcccc; color: #dc3545; }
        .btn-delete:hover { background: #ffe3e3; border-color: #ffb3b3; }

        /* Modal Styles */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(2px); }
        .modal-overlay.open { display: flex; }
        
        .modal-box { 
            background: white; width: 450px; border-radius: 12px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; 
            animation: slideUp 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .modal-header { 
            padding: 20px 25px; border-bottom: 1px solid #f0f0f0; 
            display: flex; justify-content: space-between; align-items: center; 
            background: #fff;
        }
        .modal-header h3 { margin: 0; font-size: 1.1rem; color: #333; }
        .close-modal { cursor: pointer; font-size: 1.2rem; color: #999; transition: 0.2s; }
        .close-modal:hover { color: #333; }

        .modal-body { padding: 25px; }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 600; color: #444; font-size: 0.85rem; }
        .form-input { 
            width: 100%; padding: 10px 12px; border: 1px solid #ddd; 
            border-radius: 8px; font-size: 0.9rem; transition: 0.2s; 
        }
        .form-input:focus { border-color: #4e73df; outline: none; box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1); }

        .detail-row { 
            display: flex; justify-content: space-between; padding: 12px 0; 
            border-bottom: 1px dashed #eee; font-size: 0.9rem; 
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #777; }
        .detail-value { font-weight: 600; color: #333; text-align: right; max-width: 60%; }

        .modal-actions { 
            padding: 20px 25px; background: #f8f9fa; border-top: 1px solid #eee; 
            display: flex; justify-content: flex-end; gap: 10px; 
        }
        
        .btn-verify { 
            padding: 10px 20px; background: #4e73df; color: white; border: none; 
            border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s; 
        }
        .btn-verify:hover { background: #375a7f; }
        
        .btn-cancel { 
            padding: 10px 20px; background: white; color: #555; border: 1px solid #ddd; 
            border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s; 
        }
        .btn-cancel:hover { background: #f1f1f1; border-color: #ccc; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><i class="fas fa-recycle"></i> ECO<span>SWAP</span></div>
        </div>
        <ul class="nav-links">
            <li><a href="../dashboard/dashboard.php"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
            <li><a href="../produk&stok/produk&stok.php"><i class="fas fa-box"></i> <span>Verifikasi Produk</span></a></li>
            <li class="active"><a href="pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a></li>
            <li><a href="../transaksi/transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
            <li><a href="../support/support.php"><i class="fas fa-headset"></i> <span>Laporan</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../../../../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h2>Manajemen Pengguna</h2>
                <p>Kelola akun admin, penjual, dan pembeli.</p>
            </div>
        </header>

        <section class="card-panel" style="margin-bottom: 30px;">
            <div class="user-header">
                <h3>Daftar Admin</h3>
                <button class="btn-add-admin" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Tambah Admin
                </button>
            </div>
            <table class="product-table"> 
                <thead>
                    <tr>
                        <th width="25%">Nama</th>
                        <th width="25%">Email</th>
                        <th width="15%">No. HP</th>
                        <th width="15%">Bergabung</th>
                        <th width="10%">Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($admins as $adm): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <img src="<?php echo !empty($adm['profile_picture']) ? $adm['profile_picture'] : 'https://ui-avatars.com/api/?name='.$adm['name']; ?>" class="user-avatar-small">
                                <?php echo $adm['name']; ?>
                            </div>
                        </td>
                        <td><?php echo $adm['email']; ?></td>
                        <td><?php echo $adm['phone_number']; ?></td>
                        <td><?php echo date('d M Y', strtotime($adm['created_at'])); ?></td>
                        <td><span class="role-badge role-admin">Admin</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="card-panel" style="margin-bottom: 30px;">
            <div class="user-header">
                <h3>Daftar Penjual (Toko)</h3>
            </div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th width="10%">ID</th>
                        <th width="25%">Nama Toko</th>
                        <th width="25%">Email</th>
                        <th width="10%">Role</th>
                        <th width="20%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($sellers)): ?><tr><td colspan="5" align="center" style="padding:40px; color:#888;">Belum ada penjual terdaftar.</td></tr><?php endif; ?>
                    <?php foreach($sellers as $slr): ?>
                    <tr>
                        <td>#<?php echo $slr['user_id']; ?></td>
                        <td>
                            <div class="user-cell">
                                <img src="<?php echo !empty($slr['profile_picture']) ? $slr['profile_picture'] : 'https://ui-avatars.com/api/?name='.$slr['name']; ?>" class="user-avatar-small">
                                <?php echo $slr['name']; ?>
                            </div>
                        </td>
                        <td><?php echo $slr['email']; ?></td>
                        <td><span class="role-badge role-seller">Seller</span></td>
                        <td>
                            <button class="btn-action btn-view" onclick='openDetail(<?php echo json_encode($slr); ?>)'><i class="fas fa-eye"></i> Detail</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus penjual ini?')">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo $slr['user_id']; ?>">
                                <button class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="card-panel">
            <div class="user-header">
                <h3>Daftar Pembeli</h3>
            </div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th width="10%">ID</th>
                        <th width="25%">Nama</th>
                        <th width="25%">Email</th>
                        <th width="10%">Role</th>
                        <th width="20%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($buyers)): ?><tr><td colspan="5" align="center" style="padding:40px; color:#888;">Belum ada pembeli terdaftar.</td></tr><?php endif; ?>
                    <?php foreach($buyers as $byr): ?>
                    <tr>
                        <td>#<?php echo $byr['user_id']; ?></td>
                        <td>
                            <div class="user-cell">
                                <img src="<?php echo !empty($byr['profile_picture']) ? $byr['profile_picture'] : 'https://ui-avatars.com/api/?name='.$byr['name']; ?>" class="user-avatar-small">
                                <?php echo $byr['name']; ?>
                            </div>
                        </td>
                        <td><?php echo $byr['email']; ?></td>
                        <td><span class="role-badge role-buyer">Buyer</span></td>
                        <td>
                            <button class="btn-action btn-view" onclick='openDetail(<?php echo json_encode($byr); ?>)'><i class="fas fa-eye"></i> Detail</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus pembeli ini?')">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo $byr['user_id']; ?>">
                                <button class="btn-action btn-delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

    <div class="modal-overlay" id="addAdminModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Tambah Admin Baru</h3>
                <i class="fas fa-times close-modal" onclick="closeAddModal()"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_admin">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-input" placeholder="Masukkan nama lengkap" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="contoh@ecoswap.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="phone" class="form-input" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input" placeholder="Minimal 6 karakter" required>
                    </div>
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
            <div class="modal-header">
                <h3>Detail Pengguna</h3>
                <i class="fas fa-times close-modal" onclick="closeDetailModal()"></i>
            </div>
            <div class="modal-body" id="detailContent">
                </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeDetailModal()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        const addModal = document.getElementById('addAdminModal');
        const detailModal = document.getElementById('detailModal');

        // Fungsi Buka Modal & Block Scroll
        function openAddModal() { 
            addModal.classList.add('open'); 
            document.body.classList.add('no-scroll'); 
        }
        function closeAddModal() { 
            addModal.classList.remove('open'); 
            document.body.classList.remove('no-scroll'); 
        }

        function openDetail(data) {
            const content = document.getElementById('detailContent');
            content.innerHTML = `
                <div style="text-align:center; margin-bottom:25px;">
                    <img src="${data.profile_picture || 'https://ui-avatars.com/api/?name='+data.name}" style="width:90px; height:90px; border-radius:50%; border:3px solid #f8f9fa; margin-bottom:10px;">
                    <h3 style="margin:0; font-size:1.2rem; color:#333;">${data.name}</h3>
                    <div style="margin-top:8px;">
                        <span class="role-badge role-${data.role}">${data.role.toUpperCase()}</span>
                    </div>
                </div>
                <div class="detail-row"><span class="detail-label">User ID</span><span class="detail-value">#${data.user_id}</span></div>
                <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${data.email}</span></div>
                <div class="detail-row"><span class="detail-label">No. HP</span><span class="detail-value">${data.phone_number}</span></div>
                <div class="detail-row"><span class="detail-label">Alamat</span><span class="detail-value" style="text-align:right; font-size:0.85rem;">${data.address || '-'}</span></div>
                <div class="detail-row"><span class="detail-label">Bergabung Sejak</span><span class="detail-value">${data.created_at}</span></div>
            `;
            detailModal.classList.add('open');
            document.body.classList.add('no-scroll');
        }
        function closeDetailModal() { 
            detailModal.classList.remove('open'); 
            document.body.classList.remove('no-scroll'); 
        }

        // Tutup modal jika klik di luar box
        window.onclick = function(event) {
            if (event.target == addModal) closeAddModal();
            if (event.target == detailModal) closeDetailModal();
        }
    </script>
</body>
</html>