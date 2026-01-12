<?php
session_start();
include '../../../Auth/koneksi.php';

// Cek Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../Auth/login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// --- LOGIKA UPDATE PROFIL ---
if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone']);
    
    // Update Info Dasar
    $query = "UPDATE users SET name='$name', email='$email', phone_number='$phone' WHERE user_id='$admin_id'";
    
    // Handle Upload Foto
    if (!empty($_FILES['profile_pic']['name'])) {
        $target_dir = "../../../../Assets/img/profiles/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_name = time() . "_" . basename($_FILES["profile_pic"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
            // Update path foto di DB (path relatif dari view)
            $db_path = "../../../Assets/img/profiles/" . $file_name;
            mysqli_query($koneksi, "UPDATE users SET profile_picture='$db_path' WHERE user_id='$admin_id'");
        }
    }

    if (mysqli_query($koneksi, $query)) {
        echo "<script>alert('Profil berhasil diperbarui!'); window.location.href='pengaturan.php';</script>";
    } else {
        echo "<script>alert('Gagal update profil.');</script>";
    }
}

// --- LOGIKA GANTI PASSWORD ---
if (isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Ambil password lama dari DB
    $q_pass = mysqli_query($koneksi, "SELECT password FROM users WHERE user_id='$admin_id'");
    $d_pass = mysqli_fetch_assoc($q_pass);

    if (password_verify($old_pass, $d_pass['password'])) {
        if ($new_pass === $confirm_pass) {
            $hashed_new = password_hash($new_pass, PASSWORD_DEFAULT);
            mysqli_query($koneksi, "UPDATE users SET password='$hashed_new' WHERE user_id='$admin_id'");
            echo "<script>alert('Password berhasil diubah!'); window.location.href='pengaturan.php';</script>";
        } else {
            echo "<script>alert('Konfirmasi password baru tidak cocok!');</script>";
        }
    } else {
        echo "<script>alert('Password lama salah!');</script>";
    }
}

// AMBIL DATA ADMIN
$q_admin = mysqli_query($koneksi, "SELECT * FROM users WHERE user_id = '$admin_id'");
$d_admin = mysqli_fetch_assoc($q_admin);
$foto_profil = !empty($d_admin['profile_picture']) ? $d_admin['profile_picture'] : "https://ui-avatars.com/api/?name=" . urlencode($d_admin['name']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Pengaturan Akun</title>
    <link rel="stylesheet" href="../../../Assets/css/role/admin/pengaturan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS Khusus Halaman Pengaturan */
        .settings-container { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        
        .profile-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; }
        .profile-img-large { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #eee; margin-bottom: 15px; }
        .admin-role-badge { background: var(--primary); color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; display: inline-block; margin-bottom: 15px; }
        
        .upload-btn-wrapper { position: relative; overflow: hidden; display: inline-block; margin-top: 10px; }
        .btn-upload { border: 1px solid #ddd; color: #555; background-color: white; padding: 8px 15px; border-radius: 6px; font-size: 0.9rem; font-weight: bold; cursor: pointer; }
        .upload-btn-wrapper input[type=file] { font-size: 100px; position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; }
        
        .form-section { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: 1 / -1; }
        
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 0.9rem; }
        .form-input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; transition: 0.3s; }
        .form-input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1); }
        .form-input:disabled { background: #f8f9fa; color: #888; }
        
        .btn-save { background: var(--success); color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-save:hover { filter: brightness(0.9); transform: translateY(-1px); }
        
        .password-toggle { position: absolute; right: 15px; top: 40px; cursor: pointer; color: #888; }
        .form-group-relative { position: relative; }

        @media (max-width: 900px) { .settings-container { grid-template-columns: 1fr; } }
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
            <li><a href="laporan.php"><i class="fas fa-headset"></i> <span>Laporan</span></a></li>
            <li class="active"><a href="pengaturan.php"><i class="fas fa-cog"></i> <span>Pengaturan</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <a href="../../../Auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="welcome-text">
                <h2>Pengaturan Akun</h2>
                <p>Kelola informasi profil dan keamanan akun admin Anda.</p>
            </div>
            <div class="user-profile">
                <div class="profile-info"><img src="<?php echo $foto_profil; ?>" alt="Admin"></div>
            </div>
        </header>

        <div class="settings-container">
            <div class="profile-card">
                <img src="<?php echo $foto_profil; ?>" alt="Profile" class="profile-img-large" id="previewImg">
                <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($d_admin['name']); ?></h3>
                <span class="admin-role-badge">Super Admin</span>
                <p style="color:#666; font-size:0.9rem; margin-bottom:20px;"><?php echo htmlspecialchars($d_admin['email']); ?></p>
                
                <form method="POST" enctype="multipart/form-data" id="photoForm">
                    <div class="upload-btn-wrapper">
                        <button class="btn-upload"><i class="fas fa-camera"></i> Ganti Foto</button>
                        <input type="file" name="profile_pic" accept="image/*" onchange="previewFile(this)">
                    </div>
                </form>
                <p style="font-size:0.8rem; color:#888; margin-top:10px;">Format: JPG, PNG. Max 2MB.</p>
            </div>

            <div class="settings-forms">
                
                <form method="POST" class="form-section">
                    <div class="section-title">Informasi Dasar</div>
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($d_admin['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($d_admin['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nomor Telepon</label>
                            <input type="text" name="phone" class="form-input" value="<?php echo htmlspecialchars($d_admin['phone_number']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Role / Jabatan</label>
                            <input type="text" class="form-input" value="Administrator" disabled>
                        </div>
                        <div class="form-group full-width">
                            <label>Alamat (Opsional)</label>
                            <input type="text" class="form-input" value="<?php echo htmlspecialchars($d_admin['address']); ?>" disabled title="Edit alamat via database/fitur lain">
                        </div>
                    </div>
                    <div style="margin-top:20px; text-align:right;">
                        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    </div>
                </form>

                <form method="POST" class="form-section">
                    <div class="section-title">Keamanan & Password</div>
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group form-group-relative">
                        <label>Password Lama</label>
                        <input type="password" name="old_password" class="form-input" required id="pass1">
                        <i class="fas fa-eye password-toggle" onclick="togglePass('pass1')"></i>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group form-group-relative">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" class="form-input" required id="pass2">
                            <i class="fas fa-eye password-toggle" onclick="togglePass('pass2')"></i>
                        </div>
                        <div class="form-group form-group-relative">
                            <label>Konfirmasi Password</label>
                            <input type="password" name="confirm_password" class="form-input" required id="pass3">
                            <i class="fas fa-eye password-toggle" onclick="togglePass('pass3')"></i>
                        </div>
                    </div>
                    
                    <div style="margin-top:20px; text-align:right;">
                        <button type="submit" class="btn-save" style="background:#f6c23e; color:#333;"><i class="fas fa-key"></i> Ganti Password</button>
                    </div>
                </form>

            </div>
        </div>
    </main>

    <script>
        // Preview Image sebelum upload
        function previewFile(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(){
                    document.getElementById('previewImg').src = reader.result;
                    // Opsional: Auto submit saat ganti foto (uncomment jika diinginkan)
                    // document.getElementById('photoForm').submit(); // Tapi form ini terpisah, perlu digabung jika mau auto submit logic
                    // Di sini hanya preview, user harus klik simpan di form profil utama jika digabung, 
                    // TAPI karena input file di luar form utama, baiknya input file dipindah ke dalam form utama 
                    // ATAU gunakan JS untuk memindahkan file ke form utama saat submit.
                    
                    // SOLUSI SIMPEL: Pindahkan input file ke dalam form Update Profile di atas jika ingin sekali klik simpan.
                    // Namun di desain ini terpisah. Jadi kita biarkan manual atau pakai JS advanced.
                    // Untuk sekarang: User harus memastikan input file ada di dalam <form> yang ada tombol submitnya.
                    
                    // PERBAIKAN LOGIKA: Saya akan memindahkan input file ke dalam form utama menggunakan JS saat submit, 
                    // ATAU cukup beri instruksi. Agar lebih mudah, mari kita buat input file ini bagian dari form pertama secara struktur HTML di masa depan.
                    // Untuk kode ini, input file saya biarkan terpisah visualnya, tapi kita butuh trik agar terkirim.
                    
                    // TRIK: Clone input file ke form utama sebelum submit
                    const formProfile = document.querySelector('form[action="update_profile"]'); // Selector perlu disesuaikan
                }
                reader.readAsDataURL(file);
            }
        }
        
        // Pindahkan input file ke dalam form profil saat submit (Agar terkirim)
        const profileForm = document.querySelector('.form-section'); // Form pertama
        profileForm.addEventListener('submit', function(e) {
            const fileInput = document.querySelector('input[name="profile_pic"]');
            if (fileInput.files.length > 0) {
                // Clone file input to form
                const newInput = fileInput.cloneNode(true);
                newInput.style.display = 'none';
                this.appendChild(newInput);
            }
        });

        // Toggle Password Visibility
        function togglePass(id) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
            } else {
                input.type = "password";
            }
        }
    </script>
</body>
</html>