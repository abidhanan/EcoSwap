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

// --- A. LOGIKA UBAH FOTO PROFIL ---
if (isset($_FILES['profile_pic'])) {
    $target_dir = "../../../Assets/img/profiles/";
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $file_name = time() . "_" . basename($_FILES["profile_pic"]["name"]);
    $target_file = $target_dir . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Validasi sederhana
    $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
    if($check !== false) {
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            // Update Database
            $query_pic = "UPDATE users SET profile_picture = '$target_file' WHERE user_id = '$user_id'";
            if(mysqli_query($koneksi, $query_pic)){
                echo "<script>alert('Foto profil berhasil diperbarui!'); window.location.href='profil.php';</script>";
            }
        } else {
            echo "<script>alert('Gagal mengupload gambar.');</script>";
        }
    } else {
        echo "<script>alert('File bukan gambar.');</script>";
    }
}

// --- B. LOGIKA UBAH BIODATA ---
if (isset($_POST['action']) && $_POST['action'] == 'update_bio') {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);

    $update = mysqli_query($koneksi, "UPDATE users SET name='$name', phone_number='$phone', email='$email' WHERE user_id='$user_id'");
    
    if($update) {
        // Update session email jika berubah
        $_SESSION['email'] = $email;
        echo "<script>alert('Biodata berhasil disimpan!'); window.location.href='profil.php';</script>";
    } else {
        echo "<script>alert('Gagal update: ".mysqli_error($koneksi)."');</script>";
    }
}

// --- C. LOGIKA UBAH PASSWORD ---
if (isset($_POST['action']) && $_POST['action'] == 'update_pass') {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // 1. Ambil password lama dari DB
    $cek_user = mysqli_query($koneksi, "SELECT password FROM users WHERE user_id='$user_id'");
    $data_user = mysqli_fetch_assoc($cek_user);

    // 2. Verifikasi password lama
    if(password_verify($old_pass, $data_user['password'])) {
        // 3. Cek konfirmasi password baru
        if($new_pass === $confirm_pass) {
            // 4. Hash password baru dan update
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_pass = mysqli_query($koneksi, "UPDATE users SET password='$new_hash' WHERE user_id='$user_id'");
            if($update_pass){
                echo "<script>alert('Password berhasil diubah!'); window.location.href='profil.php';</script>";
            }
        } else {
            echo "<script>alert('Konfirmasi password baru tidak cocok!');</script>";
        }
    } else {
        echo "<script>alert('Password lama salah!');</script>";
    }
}

// --- AMBIL DATA USER TERBARU ---
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id = '$user_id'");
$user = mysqli_fetch_assoc($query);

// Tentukan Foto Profil (Upload atau Default Dicebear)
$display_pic = !empty($user['profile_picture']) ? $user['profile_picture'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=" . $user['name'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoSwap - Profil</title>
    <link rel="icon" type="image/png" href="../../../Assets/img/auth/logo.png">
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/profil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-layout">

        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo" onclick="goToDashboard()" style="cursor:pointer;">
                    ECO<span>SWAP</span>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="menu-item active">
                    <a href="profil.php" class="menu-link">
                        <i class="fas fa-user"></i>
                        <span>Biodata Diri</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="alamat.php" class="menu-link">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Alamat</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="histori.php" class="menu-link">
                        <i class="fas fa-history"></i>
                        <span>Histori</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="../seller/dashboard.php" class="menu-link">
                        <i class="fas fa-store"></i>
                        <span>Toko Saya</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="../../../../index.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <header class="header">
                <h1 class="page-title">Profil Pengguna</h1>
            </header>

            <section class="content">
                <div class="profile-container">
                    <div class="profile-card">

                        <div class="profile-left">
                            <div class="photo-area">
                                <img id="profile-picture" src="<?php echo $display_pic; ?>" alt="Foto Profil">
                            </div>

                            <form id="form-upload-foto" method="POST" enctype="multipart/form-data" action="">
                                <input type="file" id="file-upload" name="profile_pic" accept="image/*" hidden onchange="document.getElementById('form-upload-foto').submit()">
                            </form>

                            <button type="button" class="btn-select-photo" onclick="document.getElementById('file-upload').click()">
                                <i class="fas fa-camera"></i> Ubah Foto
                            </button>
                        </div>

                        <div class="profile-right">
                            <div class="biodata-section">
                                <div class="data-row">
                                    <span class="data-label">Nama Lengkap</span>
                                    <span class="data-value"><?php echo !empty($user['name']) ? $user['name'] : '-'; ?></span>
                                </div>

                                <div class="data-row">
                                    <span class="data-label">Nomor Hp</span>
                                    <span class="data-value"><?php echo !empty($user['phone_number']) ? $user['phone_number'] : '-'; ?></span>
                                </div>

                                <div class="data-row">
                                    <span class="data-label">Email</span>
                                    <span class="data-value"><?php echo !empty($user['email']) ? $user['email'] : '-'; ?></span>
                                </div>

                                <div class="action-buttons">
                                    <button class="btn-action" onclick="openBioModal()">
                                        <i class="fas fa-user-edit"></i> Ubah Biodata
                                    </button>
                                    <button class="btn-action" onclick="openPassModal()">
                                        <i class="fas fa-lock"></i> Ubah Password
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div id="ubah-password-modal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">Ubah Password</h2>
                <button class="close-modal" onclick="closeModal('ubah-password-modal')">&times;</button>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update_pass">
                <div class="form-group">
                    <label>Password Lama</label>
                    <div class="password-input-container">
                        <input type="password" name="old_password" id="old-password" class="form-input" required>
                        <i class="far fa-eye toggle-password" onclick="togglePasswordVisibility('old-password', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="password-input-container">
                        <input type="password" name="new_password" id="new-password" class="form-input" required>
                        <i class="far fa-eye toggle-password" onclick="togglePasswordVisibility('new-password', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <div class="password-input-container">
                        <input type="password" name="confirm_password" id="confirm-password" class="form-input" required>
                        <i class="far fa-eye toggle-password" onclick="togglePasswordVisibility('confirm-password', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn-save-changes">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <div id="ubah-biodata-modal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">Ubah Biodata</h2>
                <button class="close-modal" onclick="closeModal('ubah-biodata-modal')">&times;</button>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update_bio">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" class="form-input" value="<?php echo $user['name']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Nomor Hp</label>
                    <input type="tel" name="phone" class="form-input" value="<?php echo $user['phone_number']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-input" value="<?php echo $user['email']; ?>" required>
                </div>

                <button type="submit" class="btn-save-changes">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        const goToDashboard = () => window.location.href = 'dashboard.php';

        /* Modal Logic */
        const openBioModal = () => document.getElementById('ubah-biodata-modal').classList.add('open');
        const openPassModal = () => document.getElementById('ubah-password-modal').classList.add('open');
        const closeModal = id => document.getElementById(id).classList.remove('open');

        window.addEventListener('click', e => {
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                if (e.target === modal) modal.classList.remove('open');
            });
        });

        /* Toggle Password */
        function togglePasswordVisibility(id, icon) {
            const input = document.getElementById(id);
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    </script>
</body>
</html>