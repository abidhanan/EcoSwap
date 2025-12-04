<?php
echo '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Pengguna</title>
    <link rel="stylesheet" href="../dashboard/dashboard.css"> 
    <link rel="stylesheet" href="../pengguna/pengguna.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo">Admin Ecoswap</div>
        <nav>
            <a href="../dashboard/dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="../produk&stok/produk&stok.html"><i class="fas fa-box"></i> Produk & Stok</a>
            <a href="../pengguna/pengguna.html" class="active"><i class="fas fa-users"></i> Pengguna</a>
            <a href="../transaksi/transaksi.html"><i class="fas fa-exchange-alt"></i> Transaksi</a>
            <a href="../support/support.html"><i class="fas fa-comments"></i> Laporan & Support</a>
            <a href="../pengaturan/pengaturan.html"><i class="fas fa-cog"></i> Pengaturan</a>
        </nav>
    </div>

    <div class="main-content">
        <header>
            <h1>Manajemen Pengguna & Admin</h1>
            <div class="user-info">
                <i class="fas fa-bell notification-icon open-modal-btn"></i>
                
                <a href="../pengaturan/pengaturan.html" class="profile-btn-header">
                    <img src="../gambar/dimas.jpg" alt="Profil" class="profile-img-header">
                </a>
            </div>
        </header>
        
        <section class="filter-controls user-filter">
            <input type="text" placeholder="Cari berdasarkan Nama atau Email..." class="search-input">
            
            <select class="filter-select">
                <option value="all">Semua Tipe</option>
                <option value="admin">Admin</option>
                <option value="seller">Penjual</option>
                <option value="buyer">Pembeli</option>
            </select>
            
            <select class="filter-select">
                <option value="all">Semua Status Verifikasi</option>
                <option value="verified">Terverifikasi</option>
                <option value="pending">Menunggu KYC</option>
                <option value="normal">Normal/Belum Verifikasi</option>
            </select>

            <button id="addAdminButton" class="action-btn new-user-btn"><i class="fas fa-user-plus"></i> Tambah Admin</button>
        </section>

        <section class="table-section">
            <div class="card user-list-card">
                <h3>Daftar Admin & Staff</h3>
                <table class="data-table user-table admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="admin-row" data-user-id="#U001" data-user-name="Super Admin (Anda)" data-email="admin@lelang.com" data-role="Super Admin" data-status="Aktif">
                            <td>#U001</td>
                            <td>Super Admin (Anda)</td>
                            <td>admin@lelang.com</td>
                            <td>Super Admin</td>
                            <td><span class="status status-success">Aktif</span></td>
                            <td>
                                <button class="action-btn view-btn open-access-modal"
                                        data-user-id="#U001"
                                        data-user-name="Super Admin (Anda)"
                                        data-current-role="Super Admin">
                                    <i class="fas fa-lock"></i> Hak Akses
                                </button>
                            </td>
                        </tr>
                        <tr class="admin-row" data-user-id="#U002" data-user-name="Dinda Konten" data-email="dinda.k@lelang.com" data-role="Konten & Verifikasi" data-status="Aktif">
                            <td>#U002</td>
                            <td>Dinda Konten</td>
                            <td>dinda.k@lelang.com</td>
                            <td>Konten & Verifikasi</td>
                            <td><span class="status status-success">Aktif</span></td>
                            <td>
                                <button class="action-btn edit-btn open-edit-modal"
                                        data-user-id="#U002"
                                        data-user-name="Dinda Konten"
                                        data-email="dinda.k@lelang.com"
                                        data-role="Konten & Verifikasi"
                                        data-status="Aktif">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn delete-btn block-btn" 
                                        onclick="openDeactivateModal(\'#U002\', \'Dinda Konten\')">
                                    <i class="fas fa-power-off"></i> Nonaktifkan
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card user-list-card" style="margin-top: 30px;">
                <h3>Daftar Pengguna (Pembeli & Penjual)</h3>
                <table class="data-table user-table member-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Rating</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr data-id="#U1050" data-name="Toko Vintage" data-email="vintage@jual.com" data-type="Penjual" data-status="Terverifikasi" data-rating="4.8 (89)">
                            <td>#U1050</td>
                            <td>Toko Vintage</td>
                            <td>vintage@jual.com</td>
                            <td><span class="status status-success">★ 4.8</span></td>
                            <td>
                                <button class="action-btn view-btn open-detail-user"><i class="fas fa-eye"></i> Detail</button>
                                <button class="action-btn history-btn open-history-modal"><i class="fas fa-history"></i> Riwayat</button>
                                <button class="action-btn block-btn open-block-modal" data-user-name="Toko Vintage"><i class="fas fa-ban"></i> Blokir</button>
                            </td>
                        </tr>
                        <tr data-id="#U1051" data-name="Budi Pembeli" data-email="budi@mail.com" data-type="Pembeli" data-status="Normal" data-rating="4.2 (12)">
                            <td>#U1051</td>
                            <td>Budi Pembeli</td>
                            <td>budi@mail.com</td>
                            <td><span class="status status-info">★ 4.2</span></td>
                            <td>
                                <button class="action-btn view-btn open-detail-user"><i class="fas fa-eye"></i> Detail</button>
                                <button class="action-btn history-btn open-history-modal"><i class="fas fa-history"></i> Riwayat</button>
                                <button class="action-btn block-btn open-block-modal" data-user-name="Budi Pembeli"><i class="fas fa-ban"></i> Blokir</button>
                            </td>
                        </tr>
                        <tr data-id="#U1052" data-name="Mega Furniture" data-email="mega@store.com" data-type="Penjual" data-status="Menunggu KYC" data-rating="N/A">
                            <td>#U1052</td>
                            <td>Mega Furniture</td>
                            <td>mega@store.com</td>
                            <td><span class="status status-warning">N/A</span></td>
                            <td>
                                <button class="action-btn view-btn open-detail-user"><i class="fas fa-eye"></i> Detail</button>
                                <button class="action-btn history-btn open-history-modal"><i class="fas fa-history"></i> Riwayat</button>
                                <button class="action-btn block-btn open-block-modal" data-user-name="Mega Furniture"><i class="fas fa-ban"></i> Blokir</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <span class="close-btn notif-close-btn">&times;</span>
            <h2>Notifikasi</h2>
            <p>Tidak ada notifikasi yang siap ditampilkan.</p>
        </div>
    </div>
    
    <div id="addAdminModal" class="modal">
        <div class="modal-content add-admin-size">
            <span class="close-btn add-admin-close-btn">&times;</span>
            <h2>Tambah Pengguna Admin Baru</h2>
            <form class="admin-form-grid" id="addAdminForm">
                
                <div class="form-group">
                    <label for="adminNama">Nama Lengkap</label>
                    <input type="text" id="adminNama" placeholder="Contoh: Budi Santoso" required>
                </div>
                
                <div class="form-group">
                    <label for="adminEmail">Email Utama</label>
                    <input type="email" id="adminEmail" placeholder="contoh@ecoswap.com" required>
                </div>

                <div class="form-group">
                    <label for="adminTelepon">Nomor Telepon</label>
                    <input type="tel" id="adminTelepon" placeholder="+62 8xx xxxx xxxx">
                </div>

                <div class="form-group">
                    <label for="adminPassword">Kata Sandi (Default)</label>
                    <input type="password" id="adminPassword" placeholder="Buat kata sandi sementara" required>
                </div>

                <div class="form-group full-width">
                    <label for="adminRole">Jabatan / Hak Akses</label>
                    <select id="adminRole" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <option value="Super Admin">Super Admin</option>
                        <option value="Verifikator">Verifikator Produk</option>
                        <option value="CS">Customer Support</option>
                        <option value="Finance">Keuangan</option>
                        <option value="Content">Konten</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <button type="submit" class="action-btn success-btn submit-admin-btn"><i class="fas fa-user-plus"></i> Simpan Admin Baru</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="editStaffModal" class="modal">
        <div class="modal-content add-admin-size">
            <span class="close-btn edit-staff-close-btn">&times;</span>
            <h2>Edit Detail Akun <span id="editUserNameDisplay"></span></h2>
            
            <form id="editStaffForm" class="admin-form-grid">
                <input type="hidden" id="editStaffUserId" value="">
                
                <div class="form-group full-width">
                    <label for="editNama">Nama Lengkap</label>
                    <input type="text" id="editNama" placeholder="Nama Lengkap" required>
                </div>
                
                <div class="form-group full-width">
                    <label for="editEmail">Email Utama</label>
                    <input type="email" id="editEmail" placeholder="Email" required>
                </div>

                <div class="form-group">
                    <label for="editRole">Jabatan / Role</label>
                    <select id="editRole" required>
                        <option value="Super Admin">Super Admin</option>
                        <option value="Konten & Verifikasi">Konten & Verifikasi</option>
                        <option value="CS">Customer Support</option>
                        <option value="Finance">Keuangan</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editStatus">Status Akun</label>
                    <select id="editStatus" required>
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
                
                <div class="form-group full-width">
                    <label for="editPassword">Ubah Kata Sandi (Kosongkan jika tidak diubah)</label>
                    <input type="password" id="editPassword" placeholder="Masukkan kata sandi baru">
                </div>

                <div class="form-group full-width">
                    <button type="submit" class="action-btn primary-btn submit-edit-btn"><i class="fas fa-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="accessModal" class="modal">
        <div class="modal-content access-modal-size">
            <span class="close-btn access-close-btn">&times;</span>
            <h2>Kelola Hak Akses <span id="accessUserName"></span></h2>
            
            <div class="access-form-container">
                <p class="current-role-info">ID: <strong id="accessUserId"></strong> | Role Saat Ini: <strong id="currentRoleDisplay"></strong></p>
                
                <hr>
                
                <form id="roleUpdateForm">
                    <div class="form-group full-width">
                        <label for="newRole">Ubah Jabatan/Role</label>
                        <select id="newRole" required>
                            <option value="">-- Pilih Jabatan Baru --</option>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Verifikator">Verifikator Produk (Produk & Stok)</option>
                            <option value="CS">Customer Support (Sengketa & Support)</option>
                            <option value="Finance">Keuangan (Transaksi)</option>
                            <option value="Content">Konten (Produk, Pengaturan)</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Akses Modul Spesifik (Permissions)</label>
                        <div class="permissions-grid">
                            <label><input type="checkbox" checked disabled> Dashboard (View Only)</label>
                            <label><input type="checkbox" class="perm-checkbox" value="products_edit"> Produk & Stok (Edit)</label>
                            <label><input type="checkbox" class="perm-checkbox" value="auctions_manage"> Manajemen Lelang</label>
                            <label><input type="checkbox" class="perm-checkbox" value="users_manage"> Pengguna (Manage)</label>
                            <label><input type="checkbox" class="perm-checkbox" value="finance_payout"> Transaksi (Payout)</label>
                            <label><input type="checkbox" class="perm-checkbox" value="dispute_resolve"> Sengketa (Resolve)</label>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <button type="submit" class="action-btn success-btn"><i class="fas fa-save"></i> Simpan Perubahan Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="detailUserModal" class="modal">
        <div class="modal-content add-admin-size">
            <span class="close-btn detail-user-close-btn">&times;</span>
            <h2>Detail Akun <span id="detailUserNameDisplay"></span></h2>
            <hr>
            <div class="user-detail-info-simple">
                <div class="info-group-detail">
                    <label>ID Pengguna</label>
                    <p><strong id="detailUserId"></strong></p>
                </div>
                <div class="info-group-detail">
                    <label>Email</label>
                    <p><strong id="detailUserEmail"></strong></p>
                </div>
                <div class="info-group-detail">
                    <label>Tipe Akun</label>
                    <p><strong id="detailUserType"></strong></p>
                </div>
                <div class="info-group-detail">
                    <label>Status Verifikasi</label>
                    <p><strong id="detailUserStatus"></strong></p>
                </div>
                <div class="info-group-detail">
                    <label>Rating Rata-rata</label>
                    <p><strong id="detailUserRating"></strong></p>
                </div>
            </div>
            <div class="full-width modal-footer-actions-single"> 
                <button class="action-btn info-btn full-width-btn" id="viewHistoryBtnBottom" 
                        onclick="document.getElementById(\'detailUserModal\').style.display=\'none\'; document.getElementById(\'historyModal\').style.display=\'block\';">
                    <i class="fas fa-history"></i> Lihat Riwayat Lengkap
                </button>
            </div>
        </div>
    </div>

    <div id="historyModal" class="modal">
        <div class="modal-content history-modal-size">
            <span class="close-btn history-close-btn">&times;</span>
            <h2>Riwayat Akun <span id="historyUserNameDisplay"></span></h2>
            
            <div class="tabs-container">
                <div class="tab-header">
                    <button class="tab-link active" data-tab="transaksi">Riwayat Transaksi</button>
                    <button class="tab-link" data-tab="rating">Ulasan & Rating</button>
                </div>
                <div id="transaksi" class="tab-content active">
                    <table class="data-table">
                        <thead>
                            <tr><th>ID Barang</th><th>Nama Produk</th><th>Pihak Lain</th><th>Harga</th></tr>
                        </thead>
                        <tbody id="transactionHistoryBody">
                            </tbody>
                    </table>
                </div>
                <div id="rating" class="tab-content">
                    <table class="data-table">
                        <thead>
                            <tr><th>Akun Pengirim</th><th>Rating</th><th>Deskripsi Barang</th></tr>
                        </thead>
                        <tbody id="ratingHistoryBody">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="blockModal" class="modal">
        <div class="modal-content add-admin-size">
            <span class="close-btn block-close-btn">&times;</span>
            <h2 class="modal-title-center" style="color: var(--color-danger);">Blokir Akun</h2>
            
            <p class="modal-text-center">Apakah Anda yakin akan memblokir akun <strong id="blockUserNameDisplay"></strong> ini?</p>
            
            <form id="blockForm">
                <div class="form-group full-width">
                    <label for="blockReason">Alasan Pemblokiran</label>
                    <select id="blockReason" required>
                        <option value="">-- Pilih Alasan --</option>
                        <option value="Penipuan">Penipuan Transaksi</option>
                        <option value="Pelanggaran">Pelanggaran Syarat & Ketentuan</option>
                        <option value="Spam">Spam / Aktivitas Mencurigakan</option>
                        <option value="Lainnya">Lainnya (Sebutkan di deskripsi)</option>
                    </select>
                </div>
                <div class="form-group full-width">
                    <label for="blockNotes">Catatan Tambahan (Opsional)</label>
                    <textarea id="blockNotes" rows="3" placeholder="Detail kasus pemblokiran..."></textarea>
                </div>
                <div class="modal-action-footer">
                    <button type="button" class="action-btn secondary-btn" onclick="document.getElementById(\'blockModal\').style.display=\'none\';">Batal</button>
                    <button type="submit" class="action-btn danger-btn">Yakin, Blokir Permanen</button>
                </div>
            </form>
        </div>
    </div>

    <div id="confirmDeactivateModal" class="modal">
        <div class="modal-content add-admin-size">
            <span class="close-btn" onclick="closeDeactivateModal()">&times;</span>
            <h2 style="text-align: center; margin-bottom: 20px;">Konfirmasi Penonaktifan</h2>
            
            <p style="text-align: center; margin-bottom: 30px; font-size: 16px;">
                Apakah Anda yakin ingin menonaktifkan akun <strong id="deactivateUserName"></strong> ini?
            </p>
            
            <div style="display: flex; justify-content: space-around; gap: 15px;">
                <button class="action-btn danger-btn confirm-action-btn" id="confirmDeactivateBtn" style="width: 100%; font-size: 15px;">Ya, Nonaktifkan</button>
                <button class="action-btn secondary-btn confirm-action-btn" onclick="closeDeactivateModal()" style="width: 100%; background-color: #6c757d; font-size: 15px;">Batal</button>
            </div>
            
            <input type="hidden" id="userIdToDeactivate">
        </div>
    </div>


    <script>
    // Data Dummy (ditempatkan di luar DOMContentLoaded agar global)
    const dummyTransactionHistory = {
        "#U1050": [
            { id: \'#B001\', name: \'Jam Tangan A\', role: \'Pembeli\', price: \'1.500.000\' },
            { id: \'#B002\', name: \'Sepatu Vintage\', role: \'Pembeli\', price: \'400.000\' },
        ],
        "#U1051": [
            { id: \'#J005\', name: \'Kamera Digital\', role: \'Penjual\', price: \'2.500.000\' },
            { id: \'#J006\', name: \'Lensa Nikon\', role: \'Penjual\', price: \'1.000.000\' },
        ],
        "#U1052": []
    };

    const dummyRatingHistory = {
        "#U1050": [
            { sender: \'Budi P.\', rating: \'★★★★★\', item: \'Jam Tangan A\' },
            { sender: \'Adi W.\', rating: \'★★★★☆\', item: \'Sepatu Vintage\' },
        ],
        "#U1051": [
            { sender: \'Penjual X\', rating: \'★★★★★\', item: \'Kamera Digital\' },
        ],
        "#U1052": []
    };
    
    // --- Fungsi Pembantu (Harus di luar DOMContentLoaded jika dipanggil global) ---
    function openDeactivateModal(userId, userName) {
        document.getElementById(\'deactivateUserName\').innerText = userName;
        document.getElementById(\'userIdToDeactivate\').value = userId;
        document.getElementById(\'confirmDeactivateModal\').style.display = \'flex\';
    }

    function closeDeactivateModal() {
        document.getElementById(\'confirmDeactivateModal\').style.display = \'none\';
    }

    function fillHistoryTable(tbodyId, data) {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        tbody.innerHTML = \'\';
        
        if (tbodyId === \'transactionHistoryBody\') {
            data.forEach(item => {
                tbody.innerHTML += `
                    <tr>
                        <td>${item.id}</td>
                        <td>${item.name}</td>
                        <td>${item.role}</td>
                        <td>Rp ${item.price}</td>
                    </tr>
                `;
            });
        } else if (tbodyId === \'ratingHistoryBody\') {
            data.forEach(item => {
                tbody.innerHTML += `
                    <tr>
                        <td>${item.sender}</td>
                        <td>${item.rating}</td>
                        <td>${item.item}</td>
                    </tr>
                `;
            });
        } else {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align: center; color: var(--color-text-muted);">Tidak ada data riwayat.</td></tr>`;
        }
    }


    document.addEventListener(\'DOMContentLoaded\', function() {
        const notificationModal = document.getElementById("notificationModal");
        const notificationBtn = document.querySelector(".notification-icon");

        const addAdminModal = document.getElementById("addAdminModal");
        const addAdminButton = document.getElementById("addAdminButton"); 
        const accessModal = document.getElementById("accessModal"); 
        const editStaffModal = document.getElementById("editStaffModal"); 
        const detailUserModal = document.getElementById("detailUserModal"); 
        const historyModal = document.getElementById("historyModal");
        const blockModal = document.getElementById("blockModal"); 
        
        const notifCloseBtn = notificationModal ? notificationModal.querySelector(".close-btn") : null;
        const addAdminCloseBtn = addAdminModal ? addAdminModal.querySelector(".add-admin-close-btn") : null;
        const accessCloseBtn = accessModal ? accessModal.querySelector(".access-close-btn") : null;
        const editStaffCloseBtn = editStaffModal ? editStaffModal.querySelector(".edit-staff-close-btn") : null;
        const detailUserCloseBtn = detailUserModal ? detailUserModal.querySelector(".detail-user-close-btn") : null;
        const historyCloseBtn = historyModal ? historyModal.querySelector(".close-btn") : null;
        const blockCloseBtn = blockModal ? blockModal.querySelector(".close-btn") : null;

        // Elemen Modal
        const openAccessButtons = document.querySelectorAll(".open-access-modal");
        const openEditButtons = document.querySelectorAll(".open-edit-modal");
        const openDetailUserButtons = document.querySelectorAll(".open-detail-user");
        const openHistoryButtons = document.querySelectorAll(".open-history-modal");
        const openBlockButtons = document.querySelectorAll(".open-block-modal");


        // --- LOGIC BUKA MODAL TAMBAH ADMIN ---
        if (addAdminButton && addAdminModal) {
            addAdminButton.onclick = function() {
                addAdminModal.style.display = "block";
            }
        }
        if (addAdminCloseBtn) {
            addAdminCloseBtn.onclick = function() {
                addAdminModal.style.display = "none";
            }
        }
        
        // --- LOGIC NOTIFIKASI ---
        if (notificationBtn && notificationModal) {
            notificationBtn.onclick = function() { notificationModal.style.display = "block"; }
        }
        if (notifCloseBtn) {
            notifCloseBtn.onclick = function() { notificationModal.style.display = "none"; }
        }
        
        // --- LOGIC BUKA MODAL HAK AKSES ---
        openAccessButtons.forEach(button => {
            button.addEventListener(\'click\', function() {
                const row = this.closest(\'tr\');
                const data = row.dataset;
                
                document.getElementById("accessUserName").textContent = data.userName;
                document.getElementById("accessUserId").textContent = data.userId;
                document.getElementById("currentRoleDisplay").textContent = data.role;
                
                document.getElementById("newRole").value = data.role; 
                
                if (accessModal) { accessModal.style.display = "block"; }
            });
        });
        
        // --- LOGIC TUTUP MODAL HAK AKSES ---
        if (accessCloseBtn) {
            accessCloseBtn.onclick = function() {
                accessModal.style.display = "none";
            }
        }
        
        // --- LOGIC BUKA MODAL EDIT STAFF ---
        openEditButtons.forEach(button => {
            button.addEventListener(\'click\', function() {
                const row = this.closest(\'tr\');
                const data = row.dataset;
                
                document.getElementById("editUserNameDisplay").textContent = data.userName;
                document.getElementById("editStaffUserId").value = data.userId;
                document.getElementById("editNama").value = data.userName;
                document.getElementById("editEmail").value = data.email;
                document.getElementById("editRole").value = data.role;
                document.getElementById("editStatus").value = data.status;
                
                if (editStaffModal) { editStaffModal.style.display = "block"; }
            });
        });

        // --- LOGIC TUTUP MODAL EDIT STAFF ---
        if (editStaffCloseBtn) {
            editStaffCloseBtn.onclick = function() {
                editStaffModal.style.display = "none";
            }
        }
        
        // --- LOGIC BUKA MODAL DETAIL PENGGUNA ---
        openDetailUserButtons.forEach(button => {
            button.addEventListener(\'click\', function() {
                const row = this.closest(\'tr\');
                const data = row.dataset;
                
                document.getElementById(\'detailUserNameDisplay\').textContent = data.name;
                document.getElementById(\'detailUserId\').textContent = data.id;
                document.getElementById(\'detailUserEmail\').textContent = data.email;
                document.getElementById(\'detailUserType\').textContent = data.type;
                document.getElementById(\'detailUserStatus\').textContent = data.status;
                document.getElementById(\'detailUserRating\').textContent = data.rating;
                
                if (detailUserModal) { detailUserModal.style.display = "block"; }
            });
        });

        // --- LOGIC BUKA MODAL RIWAYAT ---
        openHistoryButtons.forEach(button => {
            button.addEventListener(\'click\', function() {
                const row = this.closest(\'tr\');
                const userId = row.getAttribute(\'data-id\');
                const userName = row.getAttribute(\'data-name\');

                document.getElementById(\'historyUserNameDisplay\').textContent = userName;

                // Ambil dan isi data dummy (Jika tidak ada, tampilkan pesan kosong)
                fillHistoryTable(\'transactionHistoryBody\', dummyTransactionHistory[userId] || []);
                fillHistoryTable(\'ratingHistoryBody\', dummyRatingHistory[userId] || []);
                
                if (historyModal) { historyModal.style.display = "block"; }
                
                // Pindahkan fokus ke tab Transaksi saat modal dibuka
                const firstTab = document.querySelector(\'.tab-link\');
                if (firstTab) firstTab.click();
            });
        });
        
        // --- LOGIC BUKA MODAL BLOKIR ---
        openBlockButtons.forEach(button => {
            button.addEventListener(\'click\', function() {
                const userName = button.getAttribute(\'data-user-name\');
                document.getElementById(\'blockUserNameDisplay\').textContent = userName;
                if (blockModal) { blockModal.style.display = "block"; }
            });
        });

        // --- LOGIC TUTUP MODAL X / SUBMIT FORM ---
        document.querySelectorAll(\'.close-btn\').forEach(btn => {
            btn.addEventListener(\'click\', function() {
                if (btn.closest(\'#addAdminModal\')) document.getElementById(\'addAdminModal\').style.display = \'none\';
                if (btn.closest(\'#accessModal\')) document.getElementById(\'accessModal\').style.display = \'none\';
                if (btn.closest(\'#editStaffModal\')) document.getElementById(\'editStaffModal\').style.display = \'none\';
                if (btn.closest(\'#detailUserModal\')) document.getElementById(\'detailUserModal\').style.display = \'none\';
                if (btn.closest(\'#historyModal\')) document.getElementById(\'historyModal\').style.display = \'none\';
                if (btn.closest(\'#blockModal\')) document.getElementById(\'blockModal\').style.display = \'none\';
                if (btn.closest(\'#notificationModal\')) document.getElementById(\'notificationModal\').style.display = \'none\';
            });
        });

        // Logika Submit (Tetap Sama)
        // ... (Logika Submit) ...
        
        // Logika Tabs Riwayat (Perbaikan: Event listener ini harus berjalan)
        document.querySelectorAll(\'.tab-link\').forEach(tab => {
            tab.addEventListener(\'click\', function() {
                document.querySelectorAll(\'.tab-link\').forEach(link => link.classList.remove(\'active\'));
                document.querySelectorAll(\'.tab-content\').forEach(content => content.classList.remove(\'active\'));
                
                this.classList.add(\'active\');
                document.getElementById(this.getAttribute(\'data-tab\')).classList.add(\'active\');
            });
        });

        // --- LOGIC TUTUP MODAL UMUM (Klik di luar area) ---
        window.onclick = function(event) {
            if (event.target.classList.contains(\'modal\')) {
                event.target.style.display = \'none\';
            }
        }
        
        // Tambahkan kembali Logic submit untuk kelengkapan
        // ... (Logika Submit Hak Akses, Tambah Admin, Blokir) ...
    });
</script>
</body>
</html>
';
?>