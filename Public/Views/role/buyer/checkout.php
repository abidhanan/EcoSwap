<div class="modal-overlay" id="checkoutModal" style="z-index: 1200;">
    <div class="checkout-modal-box">
        <div class="checkout-header-modal">
            <div class="header-title">Checkout</div>
            <button class="close-modal-btn" onclick="closeCheckoutModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="checkout-body-modal">
            
            <div class="section-card-checkout">
                <div class="section-title-checkout"><span><i class="fas fa-map-marker-alt" style="color:#e74c3c"></i> Alamat Pengiriman</span></div>
                <div class="address-box" onclick="openAddressModal()">
                    <div class="addr-change">Ubah</div>
                    <?php if($default_addr): ?>
                        <div class="addr-name" id="displayAddrName"><?php echo $default_addr['recipient_name']; ?> | <?php echo $default_addr['phone_number']; ?></div>
                        <div class="addr-detail" id="displayAddrDetail"><?php echo $default_addr['full_address']; ?></div>
                    <?php else: ?>
                        <div class="addr-name" id="displayAddrName">Belum ada alamat</div>
                        <div class="addr-detail" id="displayAddrDetail">Klik untuk menambahkan</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-card-checkout">
                <div class="section-title-checkout">Produk Dipesan</div>
                <div class="product-list-container" id="checkoutProductList"></div>
            </div>

            <div class="section-card-checkout">
                <div class="section-title-checkout">Opsi Pengiriman</div>
                <select class="shipping-select" id="shippingSelect" onchange="calculateCheckoutTotal()">
                    <option value="0" disabled selected>-- Pilih Kurir --</option>
                    <option value="15000">JNE Reguler (Rp 15.000)</option>
                    <option value="18000">J&T Express (Rp 18.000)</option>
                    <option value="25000">GoSend Instant (Rp 25.000)</option>
                    <option value="12000">SiCepat Halu (Rp 12.000)</option>
                </select>
            </div>

            <div class="section-card-checkout">
                <div class="section-title-checkout">Metode Pembayaran</div>
                <div class="payment-category" id="cat-bank" onclick="selectPaymentCategory('bank')">
                    <div class="payment-header">
                        <div class="ph-left"><i class="far fa-circle check-circle" id="check-bank"></i><span class="ph-title"><i class="fas fa-university"></i> Transfer Bank</span></div>
                        <div class="dropdown-toggle" onclick="togglePaymentDropdown(event, 'list-bank')"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="payment-options-list" id="list-bank">
                        <div class="sub-option" onclick="selectPaymentSubOption('bank', 'BCA', 'Bank BCA')"><img src="https://placehold.co/40x25/003399/ffffff?text=BCA" class="sub-icon"> Bank BCA</div>
                        <div class="sub-option" onclick="selectPaymentSubOption('bank', 'BRI', 'Bank BRI')"><img src="https://placehold.co/40x25/00529C/ffffff?text=BRI" class="sub-icon"> Bank BRI</div>
                        <div class="sub-option" onclick="selectPaymentSubOption('bank', 'MDR', 'Bank Mandiri')"><img src="https://placehold.co/40x25/FFB700/000000?text=MDR" class="sub-icon"> Bank Mandiri</div>
                    </div>
                </div>
                <div class="payment-category" id="cat-ewallet" onclick="selectPaymentCategory('ewallet')">
                    <div class="payment-header">
                        <div class="ph-left"><i class="far fa-circle check-circle" id="check-ewallet"></i><span class="ph-title"><i class="fas fa-wallet"></i> E-Wallet</span></div>
                        <div class="dropdown-toggle" onclick="togglePaymentDropdown(event, 'list-ewallet')"><i class="fas fa-chevron-down"></i></div>
                    </div>
                    <div class="payment-options-list" id="list-ewallet">
                        <div class="sub-option" onclick="selectPaymentSubOption('ewallet', 'Gopay', 'GoPay')"><img src="https://placehold.co/40x25/00A5CF/ffffff?text=GoPay" class="sub-icon"> GoPay</div>
                        <div class="sub-option" onclick="selectPaymentSubOption('ewallet', 'OVO', 'OVO')"><img src="https://placehold.co/40x25/4C2A86/ffffff?text=OVO" class="sub-icon"> OVO</div>
                        <div class="sub-option" onclick="selectPaymentSubOption('ewallet', 'Dana', 'Dana')"><img src="https://placehold.co/40x25/118EEA/ffffff?text=Dana" class="sub-icon"> Dana</div>
                    </div>
                </div>
                <div class="payment-category" id="cat-cod" onclick="selectPaymentCategory('cod')">
                    <div class="payment-header">
                        <div class="ph-left"><i class="far fa-circle check-circle" id="check-cod"></i><span class="ph-title"><i class="fas fa-hand-holding-usd"></i> COD</span></div>
                    </div>
                </div>
            </div>

            <div class="section-card-checkout" style="margin-bottom: 20px;">
                <div class="section-title-checkout">Rincian Pembayaran</div>
                <div class="summary-row"><span>Subtotal</span><span class="price-val" id="summaryProdPrice">Rp 0</span></div>
                <div class="summary-row"><span>Pengiriman</span><span class="price-val" id="summaryShipPrice">Rp 0</span></div>
                <div class="summary-row"><span>Biaya Layanan</span><span class="price-val">Rp 1.000</span></div>
                <div class="summary-row total"><span>Total</span><span class="price-val" id="summaryTotal" style="color:var(--primary)">Rp 0</span></div>
            </div>
        </div>
        <div class="checkout-footer-modal">
            <div class="total-display"><div class="total-label">Total Tagihan</div><div class="total-final" id="bottomTotal">Rp 0</div></div>
            <button class="btn-order" onclick="processOrder()">Buat Pesanan</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="addressModal" style="z-index: 1300;">
    <div class="modal-box-address">
        <div class="modal-header-address"><span>Pilih Alamat</span><i class="fas fa-times" onclick="closeAddressModal()" style="cursor:pointer;"></i></div>
        <?php foreach($addresses as $addr): ?>
        <div class="address-option <?php echo ($addr['is_primary']) ? 'selected' : ''; ?>" onclick="selectAddress(this, '<?php echo $addr['recipient_name']; ?>', '<?php echo $addr['full_address']; ?>', '<?php echo $addr['phone_number']; ?>')">
            <div style="font-weight:bold;"><?php echo $addr['label']; ?></div>
            <div style="font-size:0.85rem;"><?php echo $addr['full_address']; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>