<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Alamat - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/alamat.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="app-layout">
        
        <!-- SIDEBAR (Baru) -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo" onclick="window.location.href='dashboard.php'" style="cursor: pointer;">ECO<span>SWAP</span></div>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="profil.php" class="menu-link">Biodata Diri</a>
                </li>
                <li class="menu-item active"> <!-- Aktif di halaman Alamat -->
                    <a href="alamat.php" class="menu-link">Alamat</a>
                </li>
                <li class="menu-item">
                    <a href="histori.php" class="menu-link">Histori</a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">Toko Saya</a>
                </li>
            </ul>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <main class="main-content-wrapper">
            <!-- HEADER KONTEN -->
            <div class="header">
                <div class="page-title">Alamat</div>
                <!-- Spacer atau User Profile Icon bisa ditaruh di sini -->
                <div style="width: 20px;"></div> 
            </div>

            <!-- SCROLLABLE CONTENT -->
            <div class="content">
                <!-- Tombol Tambah -->
                <button class="add-btn" onclick="tambahAlamatBaru()">
                    <i class="fas fa-plus"></i> Tambah Alamat Baru
                </button>

                <!-- List Alamat -->
                <div class="address-list" id="addressList">
                    
                    <!-- DATA DUMMY 1 (Active) -->
                    <div class="address-card active" onclick="pilihAlamat(this)">
                        <div class="check-icon"><i class="fas fa-check"></i></div>
                        <div class="card-header">
                            <span class="address-label">Rumah</span>
                            <i class="fas fa-pen edit-btn" onclick="editAlamat(event, this)"></i>
                        </div>
                        <div class="card-body">
                            <div class="receiver-name">Sondy Naufal</div>
                            <span class="receiver-phone">0812-3456-7890</span>
                            <div class="address-detail">Jl. Merpati No. 45, RT 02 RW 05, Kelurahan Sukamaju, Kecamatan Sukajaya, Jakarta Selatan.</div>
                            <div class="address-landmark">
                                <i class="fas fa-map-marker-alt"></i> Pagar Hitam, Depan Indomaret.
                            </div>
                        </div>
                    </div>

                    <!-- DATA DUMMY 2 -->
                    <div class="address-card" onclick="pilihAlamat(this)">
                        <div class="check-icon"><i class="fas fa-check"></i></div>
                        <div class="card-header">
                            <span class="address-label">Kantor</span>
                            <i class="fas fa-pen edit-btn" onclick="editAlamat(event, this)"></i>
                        </div>
                        <div class="card-body">
                            <div class="receiver-name">Sondy Naufal (Kantor)</div>
                            <span class="receiver-phone">0812-3456-7890</span>
                            <div class="address-detail">Gedung Cyber 2, Lantai 15, Jl. HR Rasuna Said X5, Kuningan, Jakarta Selatan.</div>
                            <div class="address-landmark">
                                <i class="fas fa-map-marker-alt"></i> Titip di resepsionis lobi utama.
                            </div>
                        </div>
                    </div>

                    <!-- DATA DUMMY 3 -->
                    <div class="address-card" onclick="pilihAlamat(this)">
                        <div class="check-icon"><i class="fas fa-check"></i></div>
                        <div class="card-header">
                            <span class="address-label">Lainnya</span>
                            <i class="fas fa-pen edit-btn" onclick="editAlamat(event, this)"></i>
                        </div>
                        <div class="card-body">
                            <div class="receiver-name">Ibu Kost</div>
                            <span class="receiver-phone">0856-7890-1234</span>
                            <div class="address-detail">Jl. Kenanga Gang 3 No. 10B (Kost Putri Melati), Depok, Jawa Barat.</div>
                            <div class="address-landmark">
                                <i class="fas fa-map-marker-alt"></i> Rumah cat hijau 2 lantai.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- MODAL FORM -->
    <div class="modal-overlay" id="addressModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">Tambah Alamat</div>
                <button class="close-modal" onclick="tutupModal()">&times;</button>
            </div>
            
            <form onsubmit="simpanAlamat(event)">
                <div class="form-group">
                    <label class="form-label">Simpan Sebagai</label>
                    <div class="label-options">
                        <label>
                            <input type="radio" name="label" class="label-option-input" value="Rumah" checked>
                            <span class="label-option-pill">Rumah</span>
                        </label>
                        <label>
                            <input type="radio" name="label" class="label-option-input" value="Kantor">
                            <span class="label-option-pill">Kantor</span>
                        </label>
                        <label>
                            <input type="radio" name="label" class="label-option-input" value="Lainnya">
                            <span class="label-option-pill">Lainnya</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Penerima</label>
                    <input type="text" id="inputName" class="form-input" placeholder="Contoh: Sondy Naufal" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor HP</label>
                    <input type="tel" id="inputPhone" class="form-input" placeholder="Contoh: 0812xxxxxxxx" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea id="inputAddress" class="form-textarea" rows="3" placeholder="Jalan, No. Rumah, RT/RW, Kelurahan, Kecamatan..." required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Patokan (Opsional)</label>
                    <input type="text" id="inputLandmark" class="form-input" placeholder="Contoh: Depan Masjid Al-Ikhlas">
                </div>

                <div class="modal-actions">
                    <button type="button" id="deleteBtn" class="delete-btn" onclick="hapusAlamat()">
                        <i class="fas fa-trash-alt"></i> Hapus
                    </button>
                    <button type="submit" id="submitBtn" class="submit-btn">Simpan Alamat</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let editingCard = null;

        function kembaliKeProfil() {
            // Simulasi navigasi
            alert("Kembali ke Dashboard/Profil");
        }

        function pilihAlamat(element) {
            const cards = document.querySelectorAll('.address-card');
            cards.forEach(card => card.classList.remove('active'));
            element.classList.add('active');
        }

        const modal = document.getElementById('addressModal');
        const modalTitle = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('submitBtn');
        const deleteBtn = document.getElementById('deleteBtn');

        function bukaModal() { modal.classList.add('open'); }
        function tutupModal() { modal.classList.remove('open'); editingCard = null; }
        modal.addEventListener('click', function(e) { if (e.target === modal) tutupModal(); });

        function tambahAlamatBaru() {
            editingCard = null;
            document.querySelector('form').reset();
            // Default select Rumah
            const rumahRadio = document.querySelector('input[value="Rumah"]');
            if(rumahRadio) rumahRadio.checked = true;

            modalTitle.textContent = "Tambah Alamat";
            submitBtn.textContent = "Simpan Alamat";
            deleteBtn.style.display = 'none';
            bukaModal();
        }

        function editAlamat(event, btnElement) {
            event.stopPropagation();
            editingCard = btnElement.closest('.address-card');

            const currentLabel = editingCard.querySelector('.address-label').textContent.trim();
            const currentName = editingCard.querySelector('.receiver-name').textContent.trim();
            const currentPhone = editingCard.querySelector('.receiver-phone').textContent.trim();
            const currentAddress = editingCard.querySelector('.address-detail').textContent.trim();
            const rawLandmark = editingCard.querySelector('.address-landmark').textContent.trim();
            const currentLandmark = rawLandmark === 'Tidak ada patokan' ? '' : rawLandmark;

            document.getElementById('inputName').value = currentName;
            document.getElementById('inputPhone').value = currentPhone;
            document.getElementById('inputAddress').value = currentAddress;
            document.getElementById('inputLandmark').value = currentLandmark;

            const radios = document.getElementsByName('label');
            for(let r of radios) {
                if(r.value === currentLabel) { r.checked = true; break; }
            }

            modalTitle.textContent = "Ubah Alamat";
            submitBtn.textContent = "Simpan Perubahan";
            deleteBtn.style.display = 'block';
            bukaModal();
        }

        function hapusAlamat() {
            if(editingCard && confirm("Apakah Anda yakin ingin menghapus alamat ini?")) {
                editingCard.remove();
                tutupModal();
                alert("Alamat berhasil dihapus.");
            }
        }

        function simpanAlamat(event) {
            event.preventDefault();
            
            const label = document.querySelector('input[name="label"]:checked').value;
            const name = document.getElementById('inputName').value;
            const phone = document.getElementById('inputPhone').value;
            const address = document.getElementById('inputAddress').value;
            const landmarkVal = document.getElementById('inputLandmark').value;
            const landmarkText = landmarkVal ? landmarkVal : 'Tidak ada patokan';

            if (editingCard) {
                editingCard.querySelector('.address-label').textContent = label;
                editingCard.querySelector('.receiver-name').textContent = name;
                editingCard.querySelector('.receiver-phone').textContent = phone;
                editingCard.querySelector('.address-detail').textContent = address;
                editingCard.querySelector('.address-landmark').innerHTML = `<i class="fas fa-map-marker-alt"></i> ${landmarkText}`;
                alert("Perubahan berhasil disimpan!");
            } else {
                const list = document.getElementById('addressList');
                const newCard = document.createElement('div');
                newCard.className = 'address-card';
                newCard.onclick = function() { pilihAlamat(this) };
                newCard.innerHTML = `
                    <div class="check-icon"><i class="fas fa-check"></i></div>
                    <div class="card-header">
                        <span class="address-label">${label}</span>
                        <i class="fas fa-pen edit-btn" onclick="editAlamat(event, this)"></i>
                    </div>
                    <div class="card-body">
                        <div class="receiver-name">${name}</div>
                        <span class="receiver-phone">${phone}</span>
                        <div class="address-detail">${address}</div>
                        <div class="address-landmark">
                            <i class="fas fa-map-marker-alt"></i> ${landmarkText}
                        </div>
                    </div>
                `;
                list.prepend(newCard);
                pilihAlamat(newCard);
                alert("Alamat baru berhasil ditambahkan!");
            }
            tutupModal();
        }
    </script>
</body>
</html>