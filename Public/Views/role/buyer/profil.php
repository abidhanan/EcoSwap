<?php
// Profil Pengguna - Ecoswap
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna - Ecoswap</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/profil.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-layout">

        <!-- ========== SIDEBAR ========== -->
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
                <a href="../../guest/login.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- ========== MAIN CONTENT ========== -->
        <main class="main-content-wrapper">

            <!-- HEADER -->
            <header class="header">
                <h1 class="page-title">Profil Pengguna</h1>
            </header>

            <!-- CONTENT -->
            <section class="content">
                <div class="profile-container">
                    <div class="profile-card">

                        <!-- FOTO PROFIL -->
                        <div class="profile-left">
                            <div class="photo-area">
                                <span class="photo-placeholder-text">Foto</span>
                                <img
                                    id="profile-picture"
                                    src="https://api.dicebear.com/7.x/avataaars/svg?seed=Dimas"
                                    alt="Foto Profil"
                                >
                            </div>

                            <input type="file" id="file-upload" accept="image/*" hidden>

                            <button
                                type="button"
                                class="btn-select-photo"
                                onclick="document.getElementById('file-upload').click()"
                            >
                                <i class="fas fa-camera"></i> Ubah Foto
                            </button>
                        </div>

                        <!-- BIODATA -->
                        <div class="profile-right">
                            <div class="biodata-section">

                                <div class="data-row">
                                    <span class="data-label">Nama Lengkap</span>
                                    <span class="data-value" id="nama-lengkap-display">Dimas Sudarmono</span>
                                </div>

                                <div class="data-row">
                                    <span class="data-label">Tanggal Lahir</span>
                                    <span class="data-value" id="tgl-lahir-display">01 Januari 2001</span>
                                </div>

                                <div class="data-row">
                                    <span class="data-label">Jenis Kelamin</span>
                                    <span class="data-value" id="jenis-kelamin-display">Laki - Laki</span>
                                </div>

                                <div class="data-row">
                                    <span class="data-label">Nomor Hp</span>
                                    <span class="data-value" id="nomor-hp-display">+62 877 5931 5863</span>
                                </div>

                                <div class="data-row">
                                    <span class="data-label">Email</span>
                                    <span class="data-value" id="email-display">monotxploit@gmail.com</span>
                                </div>

                                <div class="action-buttons">
                                    <button class="btn-action" onclick="showModal('ubah-biodata-modal')">
                                        <i class="fas fa-user-edit"></i> Ubah Biodata
                                    </button>
                                    <button class="btn-action" onclick="showModal('ubah-password-modal')">
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

    <!-- ========== MODAL UBAH PASSWORD ========== -->
    <div id="ubah-password-modal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">Ubah Password</h2>
                <button class="close-modal" onclick="closeModal('ubah-password-modal')">&times;</button>
            </div>

            <form id="ubah-password-form">
                <div class="form-group">
                    <label>Password Lama</label>
                    <div class="password-input-container">
                        <input type="password" id="old-password" class="form-input" required>
                        <i class="far fa-eye toggle-password" onclick="togglePasswordVisibility('old-password', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password Baru</label>
                    <div class="password-input-container">
                        <input type="password" id="new-password" class="form-input" required>
                        <i class="far fa-eye toggle-password" onclick="togglePasswordVisibility('new-password', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <div class="password-input-container">
                        <input type="password" id="confirm-password" class="form-input" required>
                        <i class="far fa-eye toggle-password" onclick="togglePasswordVisibility('confirm-password', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn-save-changes">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <!-- ========== MODAL UBAH BIODATA ========== -->
    <div id="ubah-biodata-modal" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title">Ubah Biodata</h2>
                <button class="close-modal" onclick="closeModal('ubah-biodata-modal')">&times;</button>
            </div>

            <form id="ubah-biodata-form">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" id="edit-nama" class="form-input" value="Dimas Sudarmono" required>
                </div>

                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" id="edit-tgl-lahir" class="form-input" value="2001-01-01" required>
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select id="edit-jenis-kelamin" class="form-input" required>
                        <option value="Laki - Laki" selected>Laki - Laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nomor Hp</label>
                    <input type="tel" id="edit-nomor-hp" class="form-input" value="+62 877 5931 5863" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="edit-email" class="form-input" value="monotxploit@gmail.com" required>
                </div>

                <button type="submit" class="btn-save-changes">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <!-- ========== JAVASCRIPT ========== -->
    <script>
        /* Navigasi */
        const goToDashboard = () => window.location.href = 'dashboard.php';

        /* Upload Foto */
        document.getElementById('file-upload').addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = ev => {
                document.getElementById('profile-picture').src = ev.target.result;
                document.querySelector('.photo-placeholder-text').style.display = 'none';
                alert('Foto profil berhasil diubah (Simulasi).');
            };
            reader.readAsDataURL(file);
        });

        /* Modal */
        const showModal = id => document.getElementById(id).classList.add('open');
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

        /* Submit Password */
        document.getElementById('ubah-password-form').addEventListener('submit', e => {
            e.preventDefault();
            const newPass = newPassword.value;
            const confirmPass = confirmPassword.value;

            if (newPass !== confirmPass) {
                alert('Konfirmasi password tidak cocok!');
                return;
            }

            alert('Password berhasil diubah (Simulasi).');
            closeModal('ubah-password-modal');
            e.target.reset();
        });

        /* Submit Biodata */
        document.getElementById('ubah-biodata-form').addEventListener('submit', e => {
            e.preventDefault();

            const date = new Date(editTglLahir.value);
            const formattedDate = date.toLocaleDateString('id-ID', {
                day: 'numeric', month: 'long', year: 'numeric'
            });

            namaLengkapDisplay.textContent = editNama.value;
            tglLahirDisplay.textContent = formattedDate;
            jenisKelaminDisplay.textContent = editJenisKelamin.value;
            nomorHpDisplay.textContent = editNomorHp.value;
            emailDisplay.textContent = editEmail.value;

            alert('Biodata berhasil diubah (Simulasi).');
            closeModal('ubah-biodata-modal');
        });
    </script>
</body>
</html>
