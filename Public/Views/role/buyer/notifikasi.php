<div class="notif-overlay-bg" id="notifOverlay" onclick="toggleNotifications()"></div>
<div class="notif-sidebar" id="notifSidebar">
    <div class="notif-header">
        <h3><i class="fas fa-bell"></i> Notifikasi</h3>
        <button class="close-notif-btn" onclick="toggleNotifications()"><i class="fas fa-times"></i></button>
    </div>

    <div class="notif-items" id="notifItemsContainer">
        <?php if(empty($notif_items)): ?>
            <div style="text-align:center; padding:20px; color:#666;">Tidak ada notifikasi</div>
        <?php else: ?>
            <?php foreach($notif_items as $notif): ?>
            <div class="notif-item" style="<?php echo ($notif['is_read'] == 0) ? 'background:#f0f8ff;' : ''; ?>">
                <div class="notif-icon"><i class="fas fa-info-circle"></i></div>
                <div class="notif-content">
                    <div class="notif-title"><?php echo $notif['title']; ?></div>
                    <div class="notif-message"><?php echo $notif['message']; ?></div>
                    <div class="notif-time"><?php echo date('d M H:i', strtotime($notif['created_at'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>