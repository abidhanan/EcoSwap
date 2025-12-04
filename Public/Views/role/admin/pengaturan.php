<?php
echo '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Profil Saya & Pengaturan</title>
    <link rel="stylesheet" href="../dashboard/dashboard.css"> 
    <link rel="stylesheet" href="../pengaturan/pengaturan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo">Admin Ecoswap</div>
        <nav>
            <a href="../dashboard/dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="../produk&stok/produk&stok.html"><i class="fas fa-box"></i> Produk & Stok</a>
            <a href="../pengguna/pengguna.html"><i class="fas fa-users"></i> Pengguna</a>
            <a href="../transaksi/transaksi.html"><i class="fas fa-exchange-alt"></i> Transaksi</a>
            <a href="../support/support.html"><i class="fas fa-comments"></i> Laporan & Support</a>
            <a href="../pengaturan/pengaturan.html" class="active"><i class="fas fa-cog"></i> Pengaturan</a>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <h1>Profil Admin & Pengaturan Akun</h1>
            <div class="user-info">
                <i class="fas fa-bell notification-icon open-modal-btn"></i>
                <a href="../pengaturan/pengaturan.html" class="profile-btn-header">
                    <img src="../gambar/dimas.jpg" alt="Profil" class="profile-img-header">
                </a>
            </div>
        </header>
        
        <section class="profile-section card">
            
            <div class="profile-header-info">
                
                <div class="profile-avatar-wrapper">
                    <div class="profile-image-clipper">
                        <img src="../gambar/dimas.jpg" alt="Foto Profil Admin" class="profile-avatar-img">
                    </div>
                    
                    <input type="file" id="uploadProfilePic" accept="image/*" style="display: none;">
                    <label for="uploadProfilePic" class="change-avatar-btn"><i class="fas fa-camera"></i></label>
                </div>
                
                <div class="profile-details">
                    <h3>Dimas Sudarmono</h3>
                    <p class="admin-role"><i class="fas fa-id-card-alt"></i>Jabatan: Admin Utama / Super Admin</p>
                    <button class="action-btn logout-btn" onclick="openLogoutModal(\'Dimas Sudarmono\')"><i class="fas fa-sign-out-alt"></i> Logout dari Akun</button>
                </div>
            </div>

            <hr>

            <h2>Detail Informasi Pribadi</h2>
            <div class="profile-grid">
                
                <div class="info-group"><label>Nama Lengkap</label><p>Dimas Sudarmono D.S</p></div>
                <div class="info-group"><label>Email</label><p>monotxploit@gmail.com</p></div>
                <div class="info-group"><label>Nomor Telepon</label><p>+62 877-5931-5863</p></div>
                <div class="info-group"><label>ID Karyawan</label><p>ADM-001</p></div>
                <div class="info-group"><label>Tanggal Bergabung</label><p>1 Januari 2024</p></div>
                <div class="info-group"><label>Status Akun</label><p class="status-active">Aktif & Terverifikasi</p></div>

            </div>

            <div class="profile-actions-bottom">
                <button class="action-btn edit-profile-btn" id="openEditProfileModal"><i class="fas fa-edit"></i> Edit Informasi Profil</button>
                <button class="action-btn change-password-btn" id="openChangePasswordModal"><i class="fas fa-key"></i> Ganti Kata Sandi</button>
            </div>
        </section>
        
    </div>
    
    <div id="editProfileModal" class="modal">
        <div class="modal-content add-admin-size">
            <span class="close-btn edit-close-btn">&times;</span>
            <h2>Edit Informasi Profil</h2>
            <form id="profileInfoForm">
                <div class="form-group full-width">
                    <label for="editNamaLengkap">Nama Lengkap</label>
                    <input type="text" id="editNamaLengkap" placeholder="Dimas Sudarmono D.S" value="Dimas Sudarmono D.S" required>
                </div>
                <div class="form-group full-width">
                    <label for="editEmail">Email Utama (Disabled)</label>
                    <input type="email" id="editEmail" value="monotxploit@gmail.com" disabled>
                </div>
                    <div class="form-group full-width">
                        <label for="editTelepon">Nomor Telepon</label>
                        <input type="tel" id="editTelepon" value="+62 877-5931-5863">
                    </div>
                <div class="form-group full-width">
                    <button type="submit" class="action-btn primary-btn submit-btn"><i class="fas fa-save"></i> Simpan Perubahan Info</button>
                </div>
            </form>
        </div>
    </div>

    <div id="changePasswordModal" class="modal">
        <div class="modal-content add-admin-size">
            <span class="close-btn password-close-btn">&times;</span>
            <h2>Ganti Kata Sandi</h2>
            <form id="passwordForm">
                <div class="form-group full-width">
                    <label for="oldPassword">Kata Sandi Lama</label>
                    <input type="password" id="oldPassword" required>
                </div>
                <div class="form-group full-width">
                    <label for="newPassword">Kata Sandi Baru</label>
                    <input type="password" id="newPassword" required>
                </div>
                <div class="form-group full-width">
                    <label for="confirmPassword">Konfirmasi Kata Sandi Baru</label>
                    <input type="password" id="confirmPassword" required>
                </div>
                <div class="form-group full-width">
                    <button type="submit" class="action-btn danger-btn submit-btn"><i class="fas fa-key"></i> Ubah Kata Sandi</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <span class="close-btn notif-close-btn">&times;</span>
            <h2>Notifikasi</h2>
            <p>Tidak ada notifikasi yang siap ditampilkan.</p>
        </div>
    </div>
    
    <div id="confirmLogoutModal" class="modal">
        <div class="modal-content add-admin-size">
            <span class="close-btn logout-close-btn" onclick="closeLogoutModal()">&times;</span>
            <h2 class="modal-title-center danger-text">Konfirmasi Logout</h2>
            
            <p class="modal-text-center">
                Apakah Anda yakin ingin keluar dari akun admin?
            </p>
            
            <div class="modal-action-footer">
                <button class="action-btn secondary-btn confirm-action-btn" onclick="closeLogoutModal()">Tidak</button>
                <button class="action-btn danger-btn confirm-action-btn" id="confirmLogoutBtn">Ya, Logout</button>
            </div>
        </div>
    </div>

    <script>
        function openLogoutModal(userName) {
            document.getElementById(\'confirmLogoutModal\').style.display = \'flex\';
        }

        function closeLogoutModal() {
            document.getElementById(\'confirmLogoutModal\').style.display = \'none\';
        }

        document.addEventListener(\'DOMContentLoaded\', function() {
            const editProfileModal = document.getElementById(\'editProfileModal\');
            const changePasswordModal = document.getElementById(\'changePasswordModal\');
            const notificationModal = document.getElementById("notificationModal");
            const confirmLogoutModal = document.getElementById("confirmLogoutModal");

            const openEditProfileBtn = document.getElementById(\'openEditProfileModal\');
            const openChangePasswordBtn = document.getElementById(\'openChangePasswordModal\');
            const notificationBtn = document.querySelector(".notification-icon");
            
            const editCloseBtn = editProfileModal ? editProfileModal.querySelector(\'.edit-close-btn\') : null;
            const passwordCloseBtn = changePasswordModal ? changePasswordModal.querySelector(\'.password-close-btn\') : null;
            const notifCloseBtn = notificationModal ? notificationModal.querySelector(".close-btn") : null;
            const confirmLogoutBtn = document.getElementById("confirmLogoutBtn");

            const editNamaLengkapInput = document.getElementById(\'editNamaLengkap\');
            const editTeleponInput = document.getElementById(\'editTelepon\');
            
            const displayNamaLengkap = document.querySelector(\'.profile-grid p\'); // Asumsi p pertama adalah Nama Lengkap
            const displayTelepon = document.querySelector(\'.profile-grid .info-group:nth-child(3) p\'); // Asumsi p ketiga adalah Telepon

            if (openEditProfileBtn && editProfileModal) {
                openEditProfileBtn.addEventListener(\'click\', function() {
                    if (displayNamaLengkap) editNamaLengkapInput.value = displayNamaLengkap.textContent;
                    if (displayTelepon) editTeleponInput.value = displayTelepon.textContent;
                    
                    editProfileModal.style.display = \'flex\';
                });
            }
            
            if (openChangePasswordBtn && changePasswordModal) {
                openChangePasswordBtn.addEventListener(\'click\', function() {
                    document.getElementById(\'passwordForm\').reset();
                    changePasswordModal.style.display = \'flex\';
                });
            }
            if (notificationBtn && notificationModal) {
                notificationBtn.onclick = function() { notificationModal.style.display = "block"; }
            }
            if (editCloseBtn) {
                editCloseBtn.onclick = function() { editProfileModal.style.display = \'none\'; };
            }
            if (passwordCloseBtn) {
                passwordCloseBtn.onclick = function() { changePasswordModal.style.display = \'none\'; };
            }
            if (notifCloseBtn) {
                notifCloseBtn.onclick = function() { notificationModal.style.display = "none"; }
            }

            const profileInfoForm = document.getElementById(\'profileInfoForm\');
            if (profileInfoForm) {
                profileInfoForm.addEventListener(\'submit\', function(e) {
                    e.preventDefault();
                    const newName = document.getElementById(\'editNamaLengkap\').value;
                    const newPhone = document.getElementById(\'editTelepon\').value;

                    if (document.querySelector(\'.profile-details h3\')) {
                        document.querySelector(\'.profile-details h3\').textContent = newName;
                    }
                    if (displayNamaLengkap) displayNamaLengkap.textContent = newName;
                    if (displayTelepon) displayTelepon.textContent = newPhone;
                    
                    alert(\'Simulasi: Informasi profil berhasil diperbarui!\');
                    editProfileModal.style.display = \'none\';
                });
            }

            const passwordForm = document.getElementById(\'passwordForm\');
            if (passwordForm) {
                passwordForm.addEventListener(\'submit\', function(e) {
                    e.preventDefault();
                    const newPass = document.getElementById(\'newPassword\').value;
                    const confirmPass = document.getElementById(\'confirmPassword\').value;

                    if (newPass !== confirmPass) {
                        alert(\'Gagal: Kata sandi baru tidak cocok!\');
                    } else {
                        alert(\'Simulasi: Kata sandi berhasil diubah!\');
                        changePasswordModal.style.display = \'none\';
                    }
                });
            }
            
            if (confirmLogoutBtn) {
                confirmLogoutBtn.addEventListener(\'click\', function() {
                    alert(\'Anda telah berhasil logout!\');
                    closeLogoutModal();
                });
            }
            
            window.onclick = function(event) {
                if (event.target === editProfileModal) {
                    editProfileModal.style.display = \'none\';
                }
                if (event.target === changePasswordModal) {
                    changePasswordModal.style.display = \'none\';
                }
                if (event.target === notificationModal) {
                    notificationModal.style.display = \'none\';
                }
                if (event.target === confirmLogoutModal) {
                    closeLogoutModal();
                }
            };
        });
    </script>
</body>
</html>
';
?>