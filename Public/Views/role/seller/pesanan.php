<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Masuk - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/pesanan.css">
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
                <li class="menu-item"><a href="../buyer/profil.php" class="menu-link">Biodata Diri</a></li>
                <li class="menu-item"><a href="../buyer/alamat.php" class="menu-link">Alamat</a></li>
                <li class="menu-item"><a href="../buyer/histori.php" class="menu-link">Histori</a></li>
                <li class="menu-item active"><a href="dashboard.php" class="menu-link">Toko Saya</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content-wrapper">
            <div class="header">
                <div class="page-title">Pesanan Masuk</div>
            </div>

            <div class="content">
                <div class="order-container">
                    
                    <!-- TABS -->
                    <div class="tabs-container">
                        <button class="tab-btn active" onclick="switchTab('pending', this)">Belum Dikonfirmasi</button>
                        <button class="tab-btn" onclick="switchTab('shipping', this)">Sedang Dikirim</button>
                        <button class="tab-btn" onclick="switchTab('completed', this)">Pengiriman Selesai</button>
                    </div>

                    <!-- LIST PESANAN -->
                    <div class="order-list" id="orderList">
                        <!-- Data akan di-render oleh JS -->
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- 1. MODAL KONFIRMASI -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">Konfirmasi Pesanan</div>
                <span class="close-modal" onclick="closeModal('confirmModal')">&times;</span>
            </div>
            <form onsubmit="processConfirmation(event)">
                <div class="form-group">
                    <label class="form-label">Nama Toko (Pengirim)</label>
                    <input type="text" class="form-input" value="Dimas Store" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat Toko</label>
                    <input type="text" class="form-input" value="Jl. Merpati No. 45, Jakarta Selatan" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Metode Pengiriman</label>
                    <select class="form-select" id="courierSelect" required>
                        <option value="">Pilih Kurir...</option>
                        <option value="COD">COD</option>
                        <option value="JNE Reguler">JNE Reguler</option>
                        <option value="J&T Express">J&T Express</option>
                        <option value="SiCepat">SiCepat</option>
                        <option value="GoSend">GoSend Instant</option>
                        <option value="Grab">Grab Express</option>
                        <option value="AnterAja">AnterAja</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor Resi (Opsional)</label>
                    <input type="text" class="form-input" placeholder="Masukkan nomor resi jika ada">
                </div>
                <button type="submit" class="btn-submit">Kirim Sekarang</button>
            </form>
        </div>
    </div>

    <!-- 2. MODAL DETAIL PESANAN -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">Detail Pesanan</div>
                <span class="close-modal" onclick="closeModal('detailModal')">&times;</span>
            </div>
            <div id="detailContent">
                <!-- Konten Detail Diisi JS -->
            </div>
            <button class="btn-submit" style="background:#333; color:white;" onclick="closeModal('detailModal')">Tutup</button>
        </div>
    </div>

    <script>
        // DATA PESANAN DUMMY
        const orders = [
            { 
                id: 101, status: 'pending', 
                product: 'Sepatu Adidas Bekas', price: 150000, img: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=200',
                desc: 'Sepatu ukuran 42, kondisi masih 90% bagus.',
                buyer: 'Budi Santoso', address: 'Jl. Kenanga No. 12, Bandung', shipping: 'JNE Reguler'
            },
            { 
                id: 102, status: 'shipping', 
                product: 'Monitor Samsung 24"', price: 900000, img: 'https://images.unsplash.com/photo-1547119957-632f856dd459?w=200',
                desc: 'Monitor full HD, pemakaian 6 bulan.',
                buyer: 'Siti Aminah', address: 'Jl. Melati Raya No. 5, Surabaya', shipping: 'GoSend Instant'
            },
            { 
                id: 103, status: 'completed', 
                product: 'Keyboard Mekanikal', price: 350000, img: 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=200',
                desc: 'Blue switch, RGB nyala semua.',
                buyer: 'Rizky Febian', address: 'Jl. Anggrek No. 88, Jakarta', shipping: 'J&T Express'
            }
        ];

        let currentTab = 'pending';
        let activeOrderId = null; // Menyimpan ID pesanan yang sedang diproses

        // RENDER LIST
        function renderOrders() {
            const container = document.getElementById('orderList');
            container.innerHTML = '';

            const filtered = orders.filter(o => o.status === currentTab);

            if (filtered.length === 0) {
                container.innerHTML = `<div style="text-align:center; padding:40px; color:#888;">Tidak ada pesanan di kategori ini.</div>`;
                return;
            }

            filtered.forEach(order => {
                let actionArea = '';
                let statusBadge = '';

                // LOGIKA TAMPILAN PER TAB
                if (order.status === 'pending') {
                    statusBadge = `<span class="status-label status-pending">Menunggu Konfirmasi</span>`;
                    actionArea = `<button class="btn-action btn-confirm" onclick="openConfirmModal(${order.id})">Konfirmasi</button>`;
                } else if (order.status === 'shipping') {
                    statusBadge = `<span class="status-label status-shipping">Sedang Dikirim</span>`;
                    actionArea = `<button class="btn-action btn-detail" onclick="openDetailModal(${order.id})">Detail</button>`;
                } else if (order.status === 'completed') {
                    statusBadge = `<span class="status-label status-done">Selesai</span>`;
                    actionArea = `
                        <div style="display:flex; align-items:center; gap:15px;">
                            <span class="done-text"><i class="fas fa-check-circle"></i> Pesanan Selesai</span>
                            <button class="btn-action btn-detail" onclick="openDetailModal(${order.id})">Detail</button>
                        </div>
                    `;
                }

                const card = document.createElement('div');
                card.className = 'order-card';
                card.innerHTML = `
                    <div class="order-left">
                        <img src="${order.img}" class="order-img" alt="${order.product}">
                        <div class="order-info">
                            <h3>${order.product}</h3>
                            <p>Pembeli: ${order.buyer}</p>
                            <p class="order-price">Rp ${order.price.toLocaleString('id-ID')}</p>
                            ${statusBadge}
                        </div>
                    </div>
                    <div class="order-right">
                        ${actionArea}
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function switchTab(status, btn) {
            currentTab = status;
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderOrders();
        }

        // --- KONFIRMASI LOGIC ---
        function openConfirmModal(id) {
            activeOrderId = id;
            document.getElementById('confirmModal').classList.add('open');
        }

        function processConfirmation(e) {
            e.preventDefault();
            
            // Pindahkan status pesanan ke \'shipping\'
            const orderIndex = orders.findIndex(o => o.id === activeOrderId);
            if (orderIndex > -1) {
                orders[orderIndex].status = 'shipping';
                // Update jasa kirim jika dipilih baru (simulasi)
                const selectedCourier = document.getElementById('courierSelect').value;
                if(selectedCourier) orders[orderIndex].shipping = selectedCourier;
            }

            closeModal('confirmModal');
            
            // Jika user masih di tab pending, refresh agar item hilang
            // Atau auto pindah tab? Refresh saja lebih UX friendly
            renderOrders();
            alert("Pesanan berhasil dikonfirmasi dan sedang diproses!");
        }

        // --- DETAIL LOGIC ---
        function openDetailModal(id) {
            const order = orders.find(o => o.id === id);
            if (!order) return;

            const content = document.getElementById('detailContent');
            content.innerHTML = `
                <div class="detail-row"><span class="detail-label">Nama Produk</span> <span class="detail-val">${order.product}</span></div>
                <div class="detail-row"><span class="detail-label">Harga</span> <span class="detail-val">Rp ${order.price.toLocaleString('id-ID')}</span></div>
                <div class="detail-row"><span class="detail-label">Jasa Kirim</span> <span class="detail-val">${order.shipping}</span></div>
                <div style="margin:15px 0; border-top:1px solid #eee; padding-top:10px;">
                    <strong>Info Penerima</strong>
                </div>
                <div class="detail-row"><span class="detail-label">Nama</span> <span class="detail-val">${order.buyer}</span></div>
                <div class="detail-row"><span class="detail-label">Alamat</span> <span class="detail-val" style="text-align:right; font-size:0.9rem;">${order.address}</span></div>
                
                <div style="background:#f9f9f9; padding:10px; border-radius:6px; margin-top:15px;">
                    <small style="color:#666; font-weight:bold;">Deskripsi Produk:</small><br>
                    <span style="font-size:0.9rem;">${order.desc}</span>
                </div>
            `;
            document.getElementById('detailModal').classList.add('open');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('open');
        }

        // INIT
        document.addEventListener('DOMContentLoaded', renderOrders);
    </script>
</body>
</html>