<div class="modal-overlay" id="checkoutModal" style="z-index: 1200;">
    <div class="checkout-modal-box">
        <div class="checkout-header-modal">
            <div class="header-title">Checkout</div>
            <button class="close-modal-btn" onclick="closeCheckoutModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="checkout-body-modal">
            
            <div class="section-card-checkout">
                <div class="section-title-checkout">
                    <span><i class="fas fa-map-marker-alt" style="color:#e74c3c"></i> Alamat Pengiriman</span>
                </div>
                <div class="address-box" onclick="openAddressModal()">
                    <div class="addr-change">Ubah</div>
                    <?php if($default_addr): ?>
                        <div class="addr-name" id="displayAddrName">
                            <?php echo $default_addr['recipient_name']; ?> <span style="color:#888;">|</span> <?php echo $default_addr['phone_number']; ?>
                        </div>
                        <div class="addr-detail" id="displayAddrDetail" style="margin-top:5px; line-height:1.4;">
                            <?php echo $default_addr['full_address']; ?><br>
                            <?php echo $default_addr['formatted_details']; ?>
                        </div>
                    <?php else: ?>
                        <div class="addr-name" id="displayAddrName">Belum ada alamat</div>
                        <div class="addr-detail" id="displayAddrDetail">Klik untuk menambahkan alamat baru</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-card-checkout">
                <div class="section-title-checkout">Produk Dipesan</div>
                <div class="product-list-container" id="checkoutProductList"></div>
            </div>

            <div class="section-card-checkout">
                <div class="section-title-checkout">Opsi Pengiriman</div>
                <div class="custom-select-wrapper">
                    <select class="shipping-select" id="shippingSelect" onchange="calculateCheckoutTotal()">
                        <option value="0" disabled selected>-- Memuat opsi pengiriman --</option>
                    </select>
                    <i class="fas fa-chevron-down select-arrow"></i>
                </div>
            </div>

            <div class="section-card-checkout">
                <div class="section-title-checkout">Metode Pembayaran</div>
                <div id="paymentContainer">
                    </div>
            </div>

            <div class="section-card-checkout" style="margin-bottom: 20px; background-color: #fafafa;">
                <div class="section-title-checkout">Rincian Pembayaran</div>
                <div class="summary-row"><span>Subtotal Produk</span><span class="price-val" id="summaryProdPrice">Rp 0</span></div>
                <div class="summary-row"><span>Ongkos Kirim</span><span class="price-val" id="summaryShipPrice">Rp 0</span></div>
                <div class="summary-row"><span>Biaya Admin</span><span class="price-val">Rp 1.000</span></div>
                <div class="summary-row total"><span>Total Pembayaran</span><span class="price-val" id="summaryTotal" style="color:var(--primary)">Rp 0</span></div>
            </div>
        </div>
        <div class="checkout-footer-modal">
            <div class="total-display">
                <div class="total-label">Total Tagihan</div>
                <div class="total-final" id="bottomTotal">Rp 0</div>
            </div>
            <button class="btn-order" onclick="processOrder()">Buat Pesanan</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="addressModal" style="z-index: 1300;">
    <div class="modal-box-address">
        <div class="modal-header-address">
            <span>Pilih Alamat Pengiriman</span>
            <i class="fas fa-times" onclick="closeAddressModal()" style="cursor:pointer;"></i>
        </div>
        <div style="max-height: 400px; overflow-y: auto;">
            <?php foreach($addresses as $addr): ?>
            <div class="address-option <?php echo ($addr['is_primary']) ? 'selected' : ''; ?>" 
                 onclick="selectedAddressId = <?php echo $addr['address_id']; ?>; selectAddress(this, '<?php echo $addr['recipient_name']; ?>', '<?php echo $addr['full_address'] . ', ' . $addr['formatted_details']; ?>', '<?php echo $addr['phone_number']; ?>')">
                <div style="font-weight:bold; margin-bottom:4px;">
                    <?php echo $addr['label']; ?> 
                    <?php if($addr['is_primary']): ?> <span style="background:var(--primary); font-size:0.7rem; padding:2px 6px; border-radius:4px; margin-left:5px;">Utama</span> <?php endif; ?>
                </div>
                <div style="font-size:0.9rem; color:#333;"><?php echo $addr['recipient_name']; ?></div>
                <div style="font-size:0.85rem; color:#666; margin-top:2px;">
                    <?php echo $addr['full_address']; ?><br>
                    <?php echo $addr['formatted_details']; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="btn-add-addr" onclick="window.location.href='alamat.php'">+ Tambah Alamat Baru</button>
    </div>
</div>

<style>
    /* Sedikit perbaikan CSS untuk select dropdown */
    .custom-select-wrapper { position: relative; }
    .shipping-select {
        width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px;
        background: #fff; appearance: none; font-size: 0.95rem; cursor: pointer;
    }
    .select-arrow { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #888; pointer-events: none; }
    .btn-add-addr { width: 100%; padding: 12px; border: 1px dashed #aaa; background: #fff; margin-top: 10px; border-radius: 8px; cursor: pointer; color: #555; }
    .btn-add-addr:hover { background: #f9f9f9; border-color: var(--primary); color: #000; }
</style>