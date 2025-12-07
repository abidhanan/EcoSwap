<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Toko - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/toko.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <div class="app-layout">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo" onclick="window.location.href='../buyer/dashboard.php'" style="cursor: pointer;">ECO<span>SWAP</span></div>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="../buyer/profil.php" class="menu-link">Biodata Diri</a>
                </li>
                <li class="menu-item">
                    <a href="../buyer/alamat.php" class="menu-link">Alamat</a>
                </li>
                <li class="menu-item">
                    <a href="../buyer/histori.php" class="menu-link">Histori</a>
                </li>
                <li class="menu-item active">
                    <a href="dashboard.php" class="menu-link">Toko Saya</a>
                </li>
            </ul>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content-wrapper">
            <div class="header">
                <div class="page-title">Pengaturan Toko</div>
            </div>

            <div class="content">
                <div class="settings-container">
                    
                    <!-- 1. IDENTITAS TOKO -->
                    <div class="shop-card">
                        <div class="shop-header-row">
                            <div class="shop-img-container">
                                <img src="../../../Assets/img/role/seller/foto_profil.jpg" id="shopAvatar" class="shop-img">
                                
                                <!-- INPUT FILE TERSEMBUNYI UNTUK UPLOAD -->
                                <input type="file" id="shopProfileInput" accept="image/*" style="display: none;">
                                
                                <!-- Tombol Kamera memicu input file -->
                                <button class="edit-img-btn" onclick="document.getElementById('shopProfileInput').click()">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <div class="shop-info-text">
                                <h2 id="shopNameDisplay">Dimas Store</h2>
                                <p id="shopDescDisplay">Menjual barang bekas berkualitas dengan harga terjangkau.</p>
                                <button class="btn-edit-bio" onclick="openBioModal()">Ubah Biodata Toko</button>
                            </div>
                        </div>

                        <!-- 2. ALAMAT TOKO (LIST) -->
                        <div class="section-label">
                            Alamat Operasional
                            <button class="btn-add-addr" onclick="addAddress()">
                                <i class="fas fa-plus"></i> Tambah
                            </button>
                        </div>
                        <div class="address-list" id="addressList">
                            <!-- Dummy Address 1 -->
                            <div class="address-item">
                                <div class="address-text">
                                    <strong>Gudang Utama</strong> <br>
                                    <span style="color:#333; font-weight:600;">Dimas (08123456789)</span>
                                    Jl. Merpati No. 45, Jakarta Selatan. <br>
                                    <span>(Pagar Hitam)</span>
                                </div>
                                <button class="btn-delete-addr" onclick="deleteAddress(this)"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </div>

                        <!-- 3. JASA PENGANTARAN (MULTI-SELECT) -->
                        <div class="delivery-section">
                            <div class="section-label">Jasa Pengantaran Aktif</div>
                            <div class="multiselect">
                                <div class="select-box" onclick="toggleDropdown()">
                                    <h4 id="deliverySummary">Pilih Jasa Pengantaran</h4>
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="checkboxes" id="deliveryCheckboxes">
                                    <label class="checkbox-label">
                                        <input type="checkbox" value="COD" onchange="updateDeliveryText()" checked> COD
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" value="JNE Reguler" onchange="updateDeliveryText()" checked> JNE Reguler
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" value="J&T Express" onchange="updateDeliveryText()" checked> J&T Express
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" value="SiCepat" onchange="updateDeliveryText()"> SiCepat
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" value="GoSend Instant" onchange="updateDeliveryText()"> GoSend Instant
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" value="GrabExpress" onchange="updateDeliveryText()"> GrabExpress
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" value="AnterAja" onchange="updateDeliveryText()"> AnterAja
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- MODAL EDIT BIODATA TOKO -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-container">
            <span class="close-modal" onclick="closeBioModal()">&times;</span>
            <div class="modal-title">Ubah Biodata Toko</div>
            <form onsubmit="saveShopBio(event)">
                <div class="form-group">
                    <label class="form-label">Nama Toko</label>
                    <input type="text" id="inputShopName" class="form-input" value="Dimas Store" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Deskripsi Singkat</label>
                    <textarea id="inputShopDesc" class="form-input" rows="3">Menjual barang bekas berkualitas dengan harga terjangkau.</textarea>
                </div>
                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <!-- MODAL TAMBAH ALAMAT (Baru) -->
    <div class="modal-overlay" id="addressModal">
        <div class="modal-container">
            <span class="close-modal" onclick="closeAddressModal()">&times;</span>
            <div class="modal-title">Tambah Alamat Baru</div>
            <form onsubmit="saveNewAddress(event)">
                <div class="form-group">
                    <label class="form-label">Label Alamat (Contoh: Gudang 1)</label>
                    <input type="text" id="addrLabel" class="form-input" placeholder="Label Alamat" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Toko</label>
                    <input type="text" id="addrName" class="form-input" placeholder="Nama Toko" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor HP</label>
                    <input type="tel" id="addrPhone" class="form-input" placeholder="08xxxxxxxxxx" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea id="addrDetail" class="form-input" rows="3" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan..." required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Patokan (Opsional)</label>
                    <input type="text" id="addrLandmark" class="form-input" placeholder="Contoh: Depan Indomaret">
                </div>
                <button type="submit" class="btn-save">Simpan Alamat</button>
            </form>
        </div>
    </div>

    <script>
        // --- LOGIKA UPLOAD FOTO TOKO ---
        document.getElementById('shopProfileInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('shopAvatar').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // --- 1. JASA PENGANTARAN LOGIC ---
        let expanded = false;

        function toggleDropdown() {
            const checkboxes = document.getElementById("deliveryCheckboxes");
            if (!expanded) {
                checkboxes.classList.add("expanded");
                expanded = true;
            } else {
                checkboxes.classList.remove("expanded");
                expanded = false;
            }
        }

        document.addEventListener('click', function(e) {
            const multiselect = document.querySelector('.multiselect');
            if (!multiselect.contains(e.target)) {
                document.getElementById("deliveryCheckboxes").classList.remove("expanded");
                expanded = false;
            }
        });

        function updateDeliveryText() {
            const checkboxes = document.querySelectorAll('#deliveryCheckboxes input[type="checkbox"]:checked');
            const summary = document.getElementById("deliverySummary");
            
            if (checkboxes.length > 0) {
                summary.textContent = `${checkboxes.length} Jasa Pengantaran Dipilih`;
                summary.style.color = "var(--primary)";
                summary.style.fontWeight = "bold";
            } else {
                summary.textContent = "Pilih Jasa Pengantaran";
                summary.style.color = "#333";
                summary.style.fontWeight = "normal";
            }
        }
        updateDeliveryText();


        // --- 2. ALAMAT LOGIC (MODAL) ---
        function addAddress() {
            // Reset form
            document.getElementById('addrLabel').value = '';
            document.getElementById('addrName').value = '';
            document.getElementById('addrPhone').value = '';
            document.getElementById('addrDetail').value = '';
            document.getElementById('addrLandmark').value = '';
            // Buka Modal
            document.getElementById('addressModal').classList.add('open');
        }

        function closeAddressModal() {
            document.getElementById('addressModal').classList.remove('open');
        }

        function saveNewAddress(e) {
            e.preventDefault();
            const label = document.getElementById('addrLabel').value;
            const name = document.getElementById('addrName').value;
            const phone = document.getElementById('addrPhone').value;
            const detail = document.getElementById('addrDetail').value;
            const landmark = document.getElementById('addrLandmark').value;
            const list = document.getElementById('addressList');
            const newItem = document.createElement('div');
            newItem.className = 'address-item';
            newItem.innerHTML = `
                <div class="address-text">
                    <strong>${label}</strong> <br>
                    <span style="color:#333; font-weight:600;">${name} (${phone})</span>
                    ${detail} <br>
                    <span>(${landmark || '-'})</span>
                </div>
                <button class="btn-delete-addr" onclick="deleteAddress(this)"><i class="fas fa-trash-alt"></i></button>
            `;
            list.appendChild(newItem);
            
            closeAddressModal();
            alert("Alamat baru berhasil ditambahkan!");
        }

        function deleteAddress(btn) {
            if(confirm("Hapus alamat ini?")) {
                btn.parentElement.remove();
            }
        }


        // --- 3. EDIT BIODATA LOGIC ---
        const bioModal = document.getElementById('editModal');

        function openBioModal() {
            document.getElementById('inputShopName').value = document.getElementById('shopNameDisplay').innerText;
            document.getElementById('inputShopDesc').value = document.getElementById('shopDescDisplay').innerText;
            bioModal.classList.add('open');
        }

        function closeBioModal() {
            bioModal.classList.remove('open');
        }

        function saveShopBio(e) {
            e.preventDefault();
            const name = document.getElementById('inputShopName').value;
            const desc = document.getElementById('inputShopDesc').value;

            document.getElementById('shopNameDisplay').innerText = name;
            document.getElementById('shopDescDisplay').innerText = desc;
            
            closeBioModal();
            alert("Biodata toko berhasil diperbarui!");
        }

        // Close modals on outside click
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeBioModal();
            }
            if (event.target == document.getElementById('addressModal')) {
                closeAddressModal();
            }
        }

    </script>
</body>
</html>