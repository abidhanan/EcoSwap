<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/checkout.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <header class="checkout-header">
        <div class="header-title">Checkout</div>
    </header>

    <div class="container">
        
        <!-- 1. ALAMAT -->
        <div class="section-card">
            <div class="section-title">
                <span><i class="fas fa-map-marker-alt" style="color:var(--danger)"></i> Alamat Pengiriman</span>
            </div>
            <div class="address-box" onclick="openAddressModal()">
                <div class="addr-change">Ubah</div>
                <div class="addr-name" id="displayAddrName">Dimas Sudarmono | 08123456789</div>
                <div class="addr-detail" id="displayAddrDetail">Jl. Merpati No. 45, RT 02 RW 05, Kelurahan Sukamaju, Kecamatan Sukajaya, Jakarta Selatan.</div>
            </div>
        </div>

        <!-- 2. DAFTAR PRODUK (DINAMIS DARI LOCALSTORAGE) -->
        <div class="section-card">
            <div class="section-title">Produk Dipesan</div>
            <!-- Container untuk daftar produk -->
            <div class="product-list-container" id="checkoutProductList">
                <!-- Item produk akan dimasukkan di sini oleh JavaScript -->
            </div>
        </div>

        <!-- 3. PENGIRIMAN -->
        <div class="section-card">
            <div class="section-title">Opsi Pengiriman</div>
            <select class="shipping-select" id="shippingSelect" onchange="calculateTotal()">
                <option value="0" disabled selected>Pilih Kurir</option>
                <option value="15000">JNE Reguler (Rp 15.000)</option>
                <option value="18000">J&T Express (Rp 18.000)</option>
                <option value="25000">GoSend Instant (Rp 25.000)</option>
                <option value="12000">SiCepat Halu (Rp 12.000)</option>
            </select>
        </div>

        <!-- 4. METODE PEMBAYARAN -->
        <div class="section-card">
            <div class="section-title">Metode Pembayaran</div>
            
            <!-- Transfer Bank -->
            <div class="payment-category" id="cat-bank">
                <div class="payment-header" onclick="selectCategory('bank')">
                    <div class="ph-left">
                        <i class="far fa-circle check-circle" id="check-bank"></i>
                        <span class="ph-title"><i class="fas fa-university"></i> Transfer Bank <span id="selected-bank-text" style="color:#666;font-weight:normal;margin-left:5px;font-size:0.9rem;"></span></span>
                    </div>
                    <div class="dropdown-toggle" onclick="toggleDropdown(event, 'list-bank')"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="payment-options-list" id="list-bank">
                    <div class="sub-option" onclick="selectSubOption('bank', 'BCA', 'Bank BCA')"><img src="https://placehold.co/40x25/003399/ffffff?text=BCA" class="sub-icon"> Bank BCA</div>
                    <div class="sub-option" onclick="selectSubOption('bank', 'BRI', 'Bank BRI')"><img src="https://placehold.co/40x25/00529C/ffffff?text=BRI" class="sub-icon"> Bank BRI</div>
                    <div class="sub-option" onclick="selectSubOption('bank', 'MDR', 'Bank Mandiri')"><img src="https://placehold.co/40x25/FFB700/000000?text=MDR" class="sub-icon"> Bank Mandiri</div>
                </div>
            </div>

            <!-- E-Wallet -->
            <div class="payment-category" id="cat-ewallet">
                <div class="payment-header" onclick="selectCategory('ewallet')">
                    <div class="ph-left">
                        <i class="far fa-circle check-circle" id="check-ewallet"></i>
                        <span class="ph-title"><i class="fas fa-wallet"></i> E-Wallet <span id="selected-ewallet-text" style="color:#666;font-weight:normal;margin-left:5px;font-size:0.9rem;"></span></span>
                    </div>
                    <div class="dropdown-toggle" onclick="toggleDropdown(event, 'list-ewallet')"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="payment-options-list" id="list-ewallet">
                    <div class="sub-option" onclick="selectSubOption('ewallet', 'Gopay', 'GoPay')"><img src="https://placehold.co/40x25/00A5CF/ffffff?text=GoPay" class="sub-icon"> GoPay</div>
                    <div class="sub-option" onclick="selectSubOption('ewallet', 'OVO', 'OVO')"><img src="https://placehold.co/40x25/4C2A86/ffffff?text=OVO" class="sub-icon"> OVO</div>
                    <div class="sub-option" onclick="selectSubOption('ewallet', 'Dana', 'Dana')"><img src="https://placehold.co/40x25/118EEA/ffffff?text=Dana" class="sub-icon"> Dana</div>
                </div>
            </div>

            <!-- COD -->
            <div class="payment-category" id="cat-cod">
                <div class="payment-header" onclick="selectCategory('cod')">
                    <div class="ph-left"><i class="far fa-circle check-circle" id="check-cod"></i> <span class="ph-title"><i class="fas fa-hand-holding-usd"></i> COD (Bayar di Tempat)</span></div>
                </div>
            </div>
        </div>

        <!-- 5. RINCIAN PEMBAYARAN -->
        <div class="section-card" style="margin-bottom: 60px;">
            <div class="section-title">Rincian Pembayaran</div>
            <div class="summary-row">
                <span>Subtotal Produk</span>
                <span class="price-val" id="summaryProdPrice">Rp 0</span>
            </div>
            <div class="summary-row">
                <span>Subtotal Pengiriman</span>
                <span class="price-val" id="summaryShipPrice">Rp 0</span>
            </div>
            <div class="summary-row">
                <span>Biaya Layanan</span>
                <span class="price-val">Rp 1.000</span>
            </div>
            <div class="summary-row total">
                <span>Total Pembayaran</span>
                <span class="price-val" id="summaryTotal" style="color:var(--primary)">Rp 0</span>
            </div>
        </div>

    </div>

    <!-- BOTTOM BAR -->
    <div class="bottom-bar">
        <div class="total-display">
            <div class="total-label">Total Tagihan</div>
            <div class="total-final" id="bottomTotal">Rp 0</div>
        </div>
        <button class="btn-order" onclick="placeOrder()">Buat Pesanan</button>
    </div>

    <!-- MODAL ALAMAT -->
    <div class="modal-overlay" id="addressModal">
        <div class="modal-box">
            <div class="modal-header">
                <span>Pilih Alamat</span>
                <i class="fas fa-times" onclick="closeAddressModal()" style="cursor:pointer;"></i>
            </div>
            <div class="address-option selected" onclick="selectAddress(this, 'Dimas (Rumah)', 'Jl. Merpati No. 45, Jakarta Selatan')">
                <div style="font-weight:bold;">Rumah</div>
                <div style="font-size:0.85rem; color:#666;">Dimas Sudarmono | 08123456789</div>
                <div style="font-size:0.85rem;">Jl. Merpati No. 45, Jakarta Selatan.</div>
            </div>
             <div class="address-option" onclick="selectAddress(this, 'Dimas (Kantor)', 'Gedung Cyber 2, Jakarta Selatan')">
                <div style="font-weight:bold;">Kantor</div>
                <div style="font-size:0.85rem; color:#666;">Dimas Sudarmono | 08123456789</div>
                <div style="font-size:0.85rem;">Gedung Cyber 2, Kuningan, Jakarta Selatan.</div>
            </div>
        </div>
    </div>

    <script>
        // VARIABLES
        let productPriceTotal = 0;
        let shippingPrice = 0;
        const serviceFee = 1000;
        let activeCategory = null;
        let activeSubOption = null;

        // INIT LOAD DATA
        window.onload = function() {
            // Ambil data dari LocalStorage (Disimpan sebagai Array)
            const storedData = localStorage.getItem('checkoutItems');
            
            if (storedData) {
                const products = JSON.parse(storedData);
                const container = document.getElementById('checkoutProductList');
                
                if (products && products.length > 0) {
                    products.forEach(item => {
                        // Hitung subtotal produk
                        productPriceTotal += item.price;

                        // Generate HTML per item
                        const row = document.createElement('div');
                        row.className = 'product-row';
                        row.innerHTML = `
                            <img src="${item.img}" class="product-img">
                            <div class="product-details">
                                <div class="prod-name">${item.title}</div>
                                <div class="prod-price">Rp ${item.price.toLocaleString('id-ID')}</div>
                            </div>
                        `;
                        container.appendChild(row);
                    });
                } else {
                    alert("Data produk kosong.");
                }
            } else {
                // Jika diakses langsung tanpa data, redirect
                // alert("Tidak ada produk yang dipilih.");
                // window.location.href = 'dashboard.php';
            }

            calculateTotal();
        };

        // HITUNG TOTAL
        function calculateTotal() {
            const shipSelect = document.getElementById('shippingSelect');
            shippingPrice = parseInt(shipSelect.value) || 0;

            const total = productPriceTotal + shippingPrice + serviceFee;

            document.getElementById('summaryProdPrice').innerText = 'Rp ' + productPriceTotal.toLocaleString('id-ID');
            document.getElementById('summaryShipPrice').innerText = 'Rp ' + shippingPrice.toLocaleString('id-ID');
            document.getElementById('summaryTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
            document.getElementById('bottomTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }

        // LOGIKA PEMBAYARAN
        function selectCategory(catId) {
            document.querySelectorAll('.payment-category').forEach(el => {
                el.classList.remove('active');
                el.querySelector('.check-circle').className = 'far fa-circle check-circle';
            });
            const activeEl = document.getElementById('cat-' + catId);
            activeEl.classList.add('active');
            activeEl.querySelector('.check-circle').className = 'fas fa-check-circle check-circle';
            activeCategory = catId;
            
            if (catId === 'cod') {
                activeSubOption = null;
                closeAllDropdowns();
            } else {
                const listEl = document.getElementById('list-' + catId);
                if (!listEl.classList.contains('show')) {
                    closeAllDropdowns();
                    listEl.classList.add('show');
                }
            }
        }

        function toggleDropdown(event, listId) {
            event.stopPropagation();
            const listEl = document.getElementById(listId);
            const isShown = listEl.classList.contains('show');
            closeAllDropdowns();
            if (!isShown) listEl.classList.add('show');
        }

        function closeAllDropdowns() {
            document.querySelectorAll('.payment-options-list').forEach(el => el.classList.remove('show'));
        }

        function selectSubOption(catId, val, displayName) {
            selectCategory(catId);
            const listContainer = document.getElementById('list-' + catId);
            listContainer.querySelectorAll('.sub-option').forEach(el => el.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            activeSubOption = val;
            
            if (catId === 'bank') {
                document.getElementById('selected-bank-text').innerText = `(${displayName})`;
                document.getElementById('selected-ewallet-text').innerText = '';
            } else if (catId === 'ewallet') {
                document.getElementById('selected-ewallet-text').innerText = `(${displayName})`;
                document.getElementById('selected-bank-text').innerText = '';
            }
            
            setTimeout(() => { document.getElementById('list-' + catId).classList.remove('show'); }, 200);
        }

        // BUAT PESANAN
        function placeOrder() {
            const shipSelect = document.getElementById('shippingSelect');
            if (shipSelect.value === "0") {
                alert("Mohon pilih jasa pengiriman terlebih dahulu.");
                return;
            }
            if (!activeCategory) {
                alert("Mohon pilih metode pembayaran.");
                return;
            }
            alert("Pesanan berhasil dibuat! Anda akan dialihkan ke dashboard.");
            window.location.href = 'dashboard.php';
        }

        // MODAL ALAMAT
        function openAddressModal() { document.getElementById('addressModal').classList.add('open'); }
        function closeAddressModal() { document.getElementById('addressModal').classList.remove('open'); }
        function selectAddress(element, name, detail) {
            document.querySelectorAll('.address-option').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('displayAddrDetail').innerText = detail;
            closeAddressModal();
        }
    </script>
</body>
</html>