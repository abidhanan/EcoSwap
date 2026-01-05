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
                    <?php if($default_addr): ?>
                        <div class="addr-header-row">
                            <span class="addr-recipient"><?php echo $default_addr['recipient_name']; ?></span>
                            <span class="addr-divider">|</span>
                            <span class="addr-phone"><?php echo $default_addr['phone_number']; ?></span>
                            <span class="addr-label-tag"><?php echo $default_addr['label']; ?></span>
                        </div>
                        <div class="addr-body-text">
                            <?php echo $default_addr['full_address']; ?><br>
                            <?php echo $default_addr['formatted_details']; ?>
                        </div>
                        <div class="addr-change-text">Ubah Alamat <i class="fas fa-chevron-right"></i></div>
                    <?php else: ?>
                        <div class="addr-empty-state">
                            <i class="fas fa-exclamation-circle"></i> Belum ada alamat tersimpan.
                            <br><span style="font-size:0.85rem; color:var(--primary);">Silakan atur alamat di menu Profil terlebih dahulu.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-card-checkout">
                <div class="section-title-checkout">Produk Dipesan</div>
                <div class="product-list-container" id="checkoutProductList"></div>
            </div>

            <div class="section-card-checkout">
                <div class="section-title-checkout">Opsi Pengiriman</div>
                <div id="shippingContainer" style="display:flex; flex-direction:column; gap:10px;">
                    </div>
            </div>

            <div class="section-card-checkout">
                <div class="section-title-checkout">Metode Pembayaran</div>
                <div id="paymentContainer" style="display:flex; flex-direction:column; gap:10px;">
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
        </div>
</div>

<style>
    /* Styling Address Box di Checkout */
    .address-box {
        border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; cursor: pointer; background: #fff; transition: all 0.2s;
    }
    .address-box:hover { border-color: var(--primary); background-color: #fffdf0; }
    .addr-header-row { display: flex; align-items: center; gap: 8px; font-size: 0.95rem; font-weight: 600; color: #333; margin-bottom: 6px; }
    .addr-divider { color: #ccc; font-weight: normal; }
    .addr-label-tag { font-size: 0.7rem; background: #eee; padding: 2px 6px; border-radius: 4px; color: #555; font-weight: normal; margin-left: auto; }
    .addr-body-text { font-size: 0.9rem; color: #666; line-height: 1.5; padding-right: 20px; }
    .addr-change-text { margin-top: 10px; font-size: 0.85rem; color: var(--primary); font-weight: 600; text-align: right; }
    .addr-empty-state { text-align: center; padding: 15px; color: #888; font-size: 0.9rem; }

    /* CSS Shipping Option Card (Baru) */
    .shipping-option-card {
        border: 1px solid #e0e0e0; border-radius: 8px; padding: 12px 15px; background: #fff; cursor: pointer; transition: 0.2s;
        display: flex; justify-content: space-between; align-items: center;
    }
    .shipping-option-card:hover { background: #f9f9f9; }
    .shipping-option-card.active { border-color: var(--primary); background: #fffdf0; }
    .ship-info { display: flex; flex-direction: column; gap: 3px; }
    .ship-name { font-weight: bold; color: #333; font-size: 0.95rem; }
    .ship-price { color: #666; font-size: 0.9rem; }

    /* CSS Payment Dropdown */
    .payment-category { border: 1px solid #eee; border-radius: 8px; margin-bottom: 8px; overflow: hidden; background: #fff; transition: 0.3s; }
    .payment-category.active { border-color: var(--primary); background: #fffdf0; }
    .payment-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; cursor: pointer; }
    .ph-left { display: flex; align-items: center; gap: 10px; font-weight: 600; color: #333; }
    .payment-options-list { display: none; background: #fafafa; border-top: 1px solid #eee; }
    .payment-options-list.show { display: block; animation: slideDown 0.3s ease; }
    .sub-option { padding: 10px 15px 10px 45px; cursor: pointer; color: #555; font-size: 0.9rem; display: flex; align-items: center; }
    .sub-option:hover { background: #eee; color: #000; }
    .sub-option.selected { color: var(--primary); font-weight: bold; background: #fff; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>