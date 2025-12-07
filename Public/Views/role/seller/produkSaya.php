<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk Saya - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/produkSaya.css">
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
                <!-- Navigasi sesuai struktur folder user -->
                <li class="menu-item"><a href="../buyer/profil.php" class="menu-link">Biodata Diri</a></li>
                <li class="menu-item"><a href="../buyer/alamat.php" class="menu-link">Alamat</a></li>
                <li class="menu-item"><a href="../buyer/histori.php" class="menu-link">Histori</a></li>
                <li class="menu-item active"><a href="dashboard.php" class="menu-link">Toko Saya</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content-wrapper">
            <div class="header">
                <div class="page-title">Produk Saya</div>
            </div>

            <div class="content">
                <div class="product-container">
                    
                    <!-- ACTION BAR -->
                    <div class="action-bar">
                        <h2 style="font-size:1.2rem; color:#666;">Kelola Katalog</h2>
                        <button class="btn-add-product" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Tambah Produk
                        </button>
                    </div>

                    <!-- TABS KATEGORI -->
                    <div class="tabs-container">
                        <button class="tab-btn active" onclick="switchTab('active', this)">Belum Terjual (Active)</button>
                        <button class="tab-btn" onclick="switchTab('sold', this)">Terjual</button>
                    </div>

                    <!-- GRID PRODUK -->
                    <div class="my-product-grid" id="productGrid">
                        <!-- Produk akan di-render lewat JS -->
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- MODAL 1: TAMBAH PRODUK -->
    <div class="modal-overlay" id="addProductModal">
        <div class="modal-container">
            <span class="close-modal" onclick="closeModal('addProductModal')">&times;</span>
            <div class="modal-title">Tambah Produk Baru</div>
            
            <form onsubmit="addProduct(event)">
                <!-- Upload Foto -->
                <div class="form-group">
                    <label class="form-label">Foto Produk</label>
                    <div class="upload-box" onclick="document.getElementById('prodImgInput').click()">
                        <i class="fas fa-cloud-upload-alt upload-icon" id="addUploadIcon"></i>
                        <p style="font-size:0.9rem; color:#666;">Klik untuk upload foto</p>
                        <img id="previewImgAdd" class="preview-img" src="" alt="Preview">
                    </div>
                    <input type="file" id="prodImgInput" style="display: none;" accept="image/*" onchange="previewImage(this, 'previewImgAdd', 'addUploadIcon')">
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" id="prodName" class="form-input" placeholder="Contoh: Sepatu Adidas Bekas" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Harga (Rp)</label>
                    <input type="number" id="prodPrice" class="form-input" placeholder="Contoh: 150000" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kondisi</label>
                    <select id="prodCond" class="form-select">
                        <option value="Bekas - Seperti Baru">Bekas - Seperti Baru</option>
                        <option value="Bekas - Baik">Bekas - Baik</option>
                        <option value="Bekas - Layak Pakai">Bekas - Layak Pakai</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea id="prodDesc" class="form-textarea" rows="3" placeholder="Jelaskan detail barang..."></textarea>
                </div>

                <button type="submit" class="btn-submit">Upload Produk</button>
            </form>
        </div>
    </div>

    <!-- MODAL 2: DETAIL PRODUK (VIEW) -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal-container">
            <span class="close-modal" onclick="closeModal('detailModal')">&times;</span>
            <div class="modal-title">Detail Produk</div>
            
            <img id="detailImg" src="" class="detail-img">
            <h2 id="detailName" style="margin-bottom:5px;">Nama Produk</h2>
            <div id="detailPrice" class="detail-price">Rp 0</div>
            
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <div id="detailDesc" class="detail-desc-box"></div>
            </div>

            <div class="detail-actions">
                <button class="btn-submit btn-edit" onclick="openEditModalFromDetail()">Edit</button>
                <button class="btn-submit btn-danger" onclick="deleteCurrentProduct()">Hapus</button>
            </div>
        </div>
    </div>

    <!-- MODAL 3: EDIT PRODUK -->
    <div class="modal-overlay" id="editProductModal">
        <div class="modal-container">
            <span class="close-modal" onclick="closeModal('editProductModal')">&times;</span>
            <div class="modal-title">Edit Produk</div>
            
            <form onsubmit="saveEditProduct(event)">
                <!-- Upload Foto Edit -->
                <div class="form-group">
                    <label class="form-label">Foto Produk</label>
                    <div class="upload-box" onclick="document.getElementById('editImgInput').click()">
                        <p style="font-size:0.8rem; color:#666; margin-bottom:5px;">Klik untuk ganti foto</p>
                        <img id="previewImgEdit" class="preview-img" src="" alt="Preview" style="display:block;">
                    </div>
                    <input type="file" id="editImgInput" style="display: none;" accept="image/*" onchange="previewImage(this, 'previewImgEdit', null)">
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Produk</label>
                    <input type="text" id="editName" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Harga (Rp)</label>
                    <input type="number" id="editPrice" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Kondisi</label>
                    <select id="editCond" class="form-select">
                        <option value="Bekas - Seperti Baru">Bekas - Seperti Baru</option>
                        <option value="Bekas - Baik">Bekas - Baik</option>
                        <option value="Bekas - Layak Pakai">Bekas - Layak Pakai</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Deskripsi</label>
                    <textarea id="editDesc" class="form-textarea" rows="3"></textarea>
                </div>

                <button type="submit" class="btn-submit">Simpan Perubahan</button>
            </form>
        </div>
    </div>

    <script>
        // DATA PRODUK DUMMY (State Awal)
        let myProducts = [
            { id: 1, name: "Keyboard Mekanikal Bekas", price: 350000, img: "https://images.unsplash.com/photo-1595225476474-87563907a212?w=300", views: 120, status: 'active', desc: "Switch blue, masih clicky. RGB normal semua.", cond: "Bekas - Baik" },
            { id: 2, name: "Mouse Gaming Logitech", price: 150000, img: "https://images.unsplash.com/photo-1527814050087-3793815479db?w=300", views: 85, status: 'active', desc: "Sensor aman, double click jarang.", cond: "Bekas - Layak Pakai" },
            { id: 3, name: "Monitor Samsung 24 Inch", price: 900000, img: "../../Ecoswap/gambar/monitor-samsung.jpg", views: 340, status: 'sold', desc: "No dead pixel, mulus.", cond: "Bekas - Seperti Baru" }, 
            { id: 4, name: "Headset Razer Kraken", price: 400000, img: "https://images.unsplash.com/photo-1612444530582-fc66183b16f7?w=300", views: 210, status: 'active', desc: "Busa agak ngelupas dikit tapi suara mantap.", cond: "Bekas - Baik" }
        ];

        let currentTab = 'active';
        let currentSelectedId = null; // Menyimpan ID produk yang sedang dilihat/diedit

        // --- RENDER PRODUK ---
        function renderProducts() {
            const grid = document.getElementById('productGrid');
            grid.innerHTML = '';

            const filtered = myProducts.filter(p => p.status === currentTab);

            if (filtered.length === 0) {
                grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: #888; padding: 40px;">Tidak ada produk di kategori ini.</div>`;
                return;
            }

            filtered.forEach(p => {
                const isSold = p.status === 'sold';
                const cardClass = isSold ? 'my-product-card sold' : 'my-product-card';
                // Hanya tambahkan onclick jika produk Aktif (Belum Terjual)
                const clickAction = (!isSold) ? `onclick="openDetail(${p.id})"` : '';
                
                const card = document.createElement('div');
                card.className = cardClass;
                // Inject onclick attribute manually
                if(!isSold) card.setAttribute('onclick', `openDetail(${p.id})`);

                card.innerHTML = `
                    <div class="card-img-wrapper">
                        <img src="${p.img}" class="card-img" alt="${p.name}">
                        <div class="sold-overlay">TERJUAL</div>
                    </div>
                    <div class="card-body">
                        <div class="card-title">${p.name}</div>
                        <div class="card-price">Rp ${p.price.toLocaleString('id-ID')}</div>
                        <div class="card-stats">
                            <span><i class="far fa-eye"></i> ${p.views} Dilihat</span>
                            <span><i class="far fa-heart"></i> ${Math.floor(p.views / 10)} Suka</span>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        // --- TABS & MODALS MANAGEMENT ---
        function switchTab(status, btn) {
            currentTab = status;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderProducts();
        }

        function openAddModal() {
            document.getElementById('addProductModal').classList.add('open');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('open');
        }

        // --- DETAIL PRODUCT LOGIC ---
        function openDetail(id) {
            const product = myProducts.find(p => p.id === id);
            if (!product) return;

            currentSelectedId = id; // Simpan ID untuk edit/hapus

            document.getElementById('detailImg').src = product.img;
            document.getElementById('detailName').textContent = product.name;
            document.getElementById('detailPrice').textContent = 'Rp ' + product.price.toLocaleString('id-ID');
            document.getElementById('detailDesc').textContent = product.desc || "Tidak ada deskripsi.";
            
            document.getElementById('detailModal').classList.add('open');
        }

        // --- DELETE PRODUCT LOGIC ---
        function deleteCurrentProduct() {
            if (confirm("Apakah Anda yakin ingin menghapus produk ini?")) {
                myProducts = myProducts.filter(p => p.id !== currentSelectedId);
                renderProducts();
                closeModal('detailModal');
                alert("Produk berhasil dihapus.");
            }
        }

        // --- EDIT PRODUCT LOGIC ---
        function openEditModalFromDetail() {
            closeModal('detailModal'); // Tutup detail dulu
            
            const product = myProducts.find(p => p.id === currentSelectedId);
            if (!product) return;

            // Isi form edit dengan data lama
            document.getElementById('editName').value = product.name;
            document.getElementById('editPrice').value = product.price;
            document.getElementById('editCond').value = product.cond || "Bekas - Baik";
            document.getElementById('editDesc').value = product.desc || "";
            document.getElementById('previewImgEdit').src = product.img;
            document.getElementById('editProductModal').classList.add('open');
        }

        function saveEditProduct(e) {
            e.preventDefault();
            
            const name = document.getElementById('editName').value;
            const price = parseInt(document.getElementById('editPrice').value);
            const cond = document.getElementById('editCond').value;
            const desc = document.getElementById('editDesc').value;
            const img = document.getElementById('previewImgEdit').src;
            // Update array
            const index = myProducts.findIndex(p => p.id === currentSelectedId);
            if (index !== -1) {
                myProducts[index].name = name;
                myProducts[index].price = price;
                myProducts[index].cond = cond;
                myProducts[index].desc = desc;
                myProducts[index].img = img;
            }

            renderProducts();
            closeModal('editProductModal');
            alert("Produk berhasil diperbarui!");
        }

        // --- GENERAL IMAGE PREVIEW ---
        function previewImage(input, imgId, iconId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById(imgId);
                    img.src = e.target.result;
                    img.style.display = 'block';
                    if(iconId) document.getElementById(iconId).style.display = 'none';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // --- ADD PRODUCT LOGIC ---
        function addProduct(e) {
            e.preventDefault();
            const name = document.getElementById('prodName').value;
            const price = parseInt(document.getElementById('prodPrice').value);
            const cond = document.getElementById('prodCond').value;
            const desc = document.getElementById('prodDesc').value;
            const img = document.getElementById('previewImgAdd').src || "https://images.unsplash.com/photo-1557804506-669a67965ba0?w=300";
            myProducts.unshift({
                id: Date.now(),
                name: name,
                price: price,
                img: img,
                views: 0,
                status: 'active',
                desc: desc,
                cond: cond
            });

            if (currentTab !== 'active') {
                const activeBtn = document.querySelectorAll('.tab-btn')[0];
                switchTab('active', activeBtn);
            } else {
                renderProducts();
            }
            
            closeModal('addProductModal');
            document.querySelector('form').reset();
            document.getElementById('previewImgAdd').style.display = 'none';
            document.getElementById('addUploadIcon').style.display = 'block';
            alert("Produk berhasil ditambahkan!");
        }

        // Handle click outside modals
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('open');
            }
        }

        // Init
        document.addEventListener('DOMContentLoaded', renderProducts);

    </script>
</body>
</html>