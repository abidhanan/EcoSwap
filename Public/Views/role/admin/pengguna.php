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
    // Hapus User (Pastikan constraint FK di DB aman atau gunakan cascade delete)
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

// --- AMBIL DATA USER (BUYER & SELLER) ---
$users = [];
$q_usr = mysqli_query($koneksi, "SELECT * FROM users WHERE role IN ('buyer', 'seller') ORDER BY created_at DESC");
while($row = mysqli_fetch_assoc($q_usr)) { $users[] = $row; }
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
        /* CSS Khusus Halaman Pengguna */
        .user-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-add-admin { background-color: var(--primary); color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; cursor: pointer; border: none; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(78, 115, 223, 0.2); }
        .btn-add-admin:hover { background-color: #375a7f; }

        .role-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .role-admin { background: #4e73df; color: white; }
        .role-seller { background: #f6c23e; color: white; }
        .role-buyer { background: #1cc88a; color: white; }

        .user-avatar-small { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; margin-right: 10px; border: 1px solid #eee; }
        .user-cell { display: flex; align-items: center; }

        /* Modal Form Styles */
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem; }
        
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed #eee; }
        .detail-label { color: #888; }
        .detail-value { font-weight: 600; color: #333; }
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
            <li class="active"><a href="pengguna.php"><i class="fas fa-users"></i> <span>Pengguna</span></a></li>
            <li><a href="transaksi.php"><i class="fas fa-exchange-alt"></i> <span>Transaksi</span></a></li>
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
                <h2>Manajemen Pengguna</h2>
                <p>Kelola akun admin, penjual, dan pembeli.</p>
            </div>
            <div class="user-profile">
                <div class="profile-info"><img src="https://ui-avatars.com/api/?name=Admin" alt="Admin"></div>
            </div>
        </header>

        <section class="card-panel" style="margin-bottom: 30px;">
            <div class="user-header">
                <h3>Daftar Admin</h3>
                <button class="btn-add-admin" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Tambah Admin
                </button>
            </div>
            <table class="product-table"> <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>No. HP</th>
                        <th>Bergabung</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($admins as $adm): ?>
                    <tr>
                        <td>
                            <div class="user-cell">
                                <img src="<?php echo !empty($adm['profile_picture']) ? $adm['profile_picture'] : 'https://ui-avatars.com/api/?name='.$adm['name']; ?>" class="user-avatar-small">
                                <b><?php echo $adm['name']; ?></b>
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

        <section class="card-panel">
            <div class="user-header">
                <h3>Daftar Pengguna (Buyer & Seller)</h3>
            </div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?><tr><td colspan="5" align="center">Belum ada pengguna terdaftar.</td></tr><?php endif; ?>
                    <?php foreach($users as $usr): ?>
                    <tr>
                        <td>#<?php echo $usr['user_id']; ?></td>
                        <td>
                            <div class="user-cell">
                                <img src="<?php echo !empty($usr['profile_picture']) ? $usr['profile_picture'] : 'https://ui-avatars.com/api/?name='.$usr['name']; ?>" class="user-avatar-small">
                                <?php echo $usr['name']; ?>
                            </div>
                        </td>
                        <td><?php echo $usr['email']; ?></td>
                        <td>
                            <span class="role-badge <?php echo ($usr['role'] == 'seller') ? 'role-seller' : 'role-buyer'; ?>">
                                <?php echo ucfirst($usr['role']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-action btn-view" onclick='openDetail(<?php echo json_encode($usr); ?>)'>Detail</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus pengguna ini? Data tidak bisa dikembalikan.')">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo $usr['user_id']; ?>">
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
                <i class="fas fa-times" onclick="closeAddModal()" style="cursor:pointer;"></i>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_admin">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="phone" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-delete" onclick="closeAddModal()" style="background:#eee; color:#333;">Batal</button>
                    <button type="submit" class="btn-verify">Simpan Admin</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="detailModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Detail Pengguna</h3>
                <i class="fas fa-times" onclick="closeDetailModal()" style="cursor:pointer;"></i>
            </div>
            <div class="modal-body" id="detailContent">
                </div>
            <div class="modal-actions">
                <button type="button" class="btn-verify" style="background:#333;" onclick="closeDetailModal()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        // Modal Tambah Admin
        const addModal = document.getElementById('addAdminModal');
        function openAddModal() { addModal.classList.add('open'); }
        function closeAddModal() { addModal.classList.remove('open'); }

        // Modal Detail User
        const detailModal = document.getElementById('detailModal');
        function openDetail(data) {
            const content = document.getElementById('detailContent');
            content.innerHTML = `
                <div style="text-align:center; margin-bottom:20px;">
                    <img src="${data.profile_picture || 'https://ui-avatars.com/api/?name='+data.name}" style="width:80px; height:80px; border-radius:50%; border:2px solid #eee;">
                    <h3 style="margin-top:10px;">${data.name}</h3>
                    <span class="role-badge role-${data.role}">${data.role.toUpperCase()}</span>
                </div>
                <div class="detail-row"><span class="detail-label">User ID</span><span class="detail-value">#${data.user_id}</span></div>
                <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${data.email}</span></div>
                <div class="detail-row"><span class="detail-label">No. HP</span><span class="detail-value">${data.phone_number}</span></div>
                <div class="detail-row"><span class="detail-label">Alamat</span><span class="detail-value">${data.address || '-'}</span></div>
                <div class="detail-row"><span class="detail-label">Bergabung</span><span class="detail-value">${data.created_at}</span></div>
            `;
            detailModal.classList.add('open');
        }
        function closeDetailModal() { detailModal.classList.remove('open'); }

        // Tutup modal jika klik luar
        window.onclick = function(event) {
            if (event.target == addModal) closeAddModal();
            if (event.target == detailModal) closeDetailModal();
        }
    </script>
</body>
</html>