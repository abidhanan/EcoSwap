<style>
    /* CSS tambahan untuk tombol hapus di keranjang */
    .btn-delete {
        background: none;
        border: none;
        color: #ff4d4d;
        font-size: 1rem;
        cursor: pointer;
        padding: 5px;
        margin-left: 10px;
        transition: transform 0.2s;
    }
    .btn-delete:hover {
        transform: scale(1.1);
        color: #d93636;
    }
</style>

<div class="cart-overlay-bg" id="cartOverlay" onclick="toggleCart()"></div>
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <h3><i class="fas fa-shopping-bag"></i> Keranjang Saya</h3>
        <button class="close-cart-btn" onclick="toggleCart()"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="cart-items" id="cartItemsContainer">
        <?php if(empty($cart_items)): ?>
            <div style="text-align:center; padding:20px; color:#666;">Keranjang kosong</div>
        <?php else: ?>
            <?php foreach($cart_items as $item): ?>
            <div class="cart-item" onclick="toggleCartItem(this)" data-id="<?php echo $item['cart_id']; ?>" data-price="<?php echo $item['price']; ?>" data-name="<?php echo $item['name']; ?>" data-img="<?php echo $item['image']; ?>" style="cursor:pointer;">
                <div class="cart-check-wrapper">
                    <input type="checkbox" class="cart-checkbox" onclick="event.stopPropagation(); updateCartTotal()">
                </div>
                <img src="<?php echo $item['image']; ?>" class="cart-item-img" alt="Item">
                <div class="cart-item-info">
                    <div class="cart-item-title"><?php echo $item['name']; ?></div>
                    <div class="cart-item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
                </div>
                <button class="btn-delete" onclick="deleteCartItem(event, <?php echo $item['cart_id']; ?>)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="cart-footer">
        <div class="cart-total">
            <span>Total Dipilih</span>
            <span style="color: var(--primary);" id="cartTotalPrice">Rp 0</span>
        </div>
        <button class="btn btn-primary" style="width: 100%;" onclick="checkoutFromCart()">Checkout</button>
    </div>
</div>