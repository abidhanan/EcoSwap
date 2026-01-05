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
                    <option value="0" disabled selected>-- Memuat --</option>
                </select>
            </div>

            <div class="section-card-checkout">
                <div class="section-title-checkout">Metode Pembayaran</div>
                <div id="paymentContainer"></div>
            </div>

            <div class="section-card-checkout" style="margin-bottom: 20px;">
                <div class="section-title-checkout">Rincian Pembayaran</div>
                <div class="summary-row"><span>Subtotal</span><span class="price-val" id="summaryProdPrice">Rp 0</span></div>
                <div class="summary-row"><span>Pengiriman</span><span class="price-val" id="summaryShipPrice">Rp 0</span></div>
                <div class="summary-row"><span>Biaya Admin</span><span class="price-val">Rp 1.000</span></div>
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