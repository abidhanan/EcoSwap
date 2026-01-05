<style>
    /* Styling Sidebar Notifikasi */
    .notif-sidebar {
        position: fixed; top: 0; right: -400px; width: 380px; height: 100vh;
        background: #fff; box-shadow: -5px 0 30px rgba(0,0,0,0.1);
        transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1); z-index: 10000;
        display: flex; flex-direction: column;
    }
    .notif-sidebar.open { right: 0; }
    
    .notif-overlay-bg {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.3); z-index: 9999;
        display: none; opacity: 0; transition: opacity 0.3s;
    }
    .notif-overlay-bg.open { display: block; opacity: 1; }

    .notif-header {
        padding: 20px; border-bottom: 1px solid #eee; display: flex;
        justify-content: space-between; align-items: center; background: #fff;
    }
    .notif-header h3 { margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; color: #333; }
    .close-notif-btn { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #666; transition: 0.2s; }
    .close-notif-btn:hover { color: #000; transform: rotate(90deg); }

    .notif-items { flex: 1; overflow-y: auto; padding: 0; }

    /* Item Notifikasi */
    .notif-item {
        padding: 15px 20px; border-bottom: 1px solid #f5f5f5;
        display: flex; gap: 15px; cursor: pointer; transition: background 0.2s;
        position: relative; background: #fff;
    }
    .notif-item:hover { background: #f9f9f9; }
    
    /* Unread State */
    .notif-item.unread { background: #f0f7ff; } /* Biru sangat muda */
    .notif-item.unread::after {
        content: ''; position: absolute; top: 20px; right: 20px;
        width: 8px; height: 8px; background: var(--primary); /* Warna Emas/Kuning */
        border-radius: 50%;
    }

    .notif-icon-box {
        width: 40px; height: 40px; border-radius: 50%;
        background: #eee; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; color: #555;
    }
    .notif-item.unread .notif-icon-box {
        background: #fff; color: var(--primary); border: 1px solid var(--primary);
    }

    .notif-content { flex: 1; }
    .notif-title { font-weight: 600; font-size: 0.95rem; color: #333; margin-bottom: 4px; }
    .notif-message { font-size: 0.85rem; color: #666; line-height: 1.4; margin-bottom: 6px; }
    .notif-time { font-size: 0.75rem; color: #999; display: flex; align-items: center; gap: 4px; }

    /* Empty State */
    .empty-notif {
        text-align: center; padding: 50px 20px; color: #888;
        display: flex; flex-direction: column; align-items: center; gap: 10px;
    }
</style>

<div class="notif-overlay-bg" id="notifOverlay" onclick="toggleNotifications()"></div>

<div class="notif-sidebar" id="notifSidebar">
    <div class="notif-header">
        <h3><i class="fas fa-bell"></i> Notifikasi</h3>
        <button class="close-notif-btn" onclick="toggleNotifications()"><i class="fas fa-times"></i></button>
    </div>

    <div class="notif-items" id="notifItemsContainer">
        <?php if(empty($notif_items)): ?>
            <div class="empty-notif">
                <i class="far fa-bell-slash" style="font-size: 2rem; opacity: 0.5;"></i>
                <span>Tidak ada notifikasi baru</span>
            </div>
        <?php else: ?>
            <?php foreach($notif_items as $notif): ?>
                <?php 
                    // Tentukan Icon berdasarkan Judul/Konten (Optional Logic)
                    $icon = 'fa-info';
                    if(stripos($notif['title'], 'pesanan') !== false) $icon = 'fa-box';
                    if(stripos($notif['title'], 'selesai') !== false) $icon = 'fa-check';
                    if(stripos($notif['title'], 'dikirim') !== false) $icon = 'fa-truck';
                ?>
                <div class="notif-item <?php echo ($notif['is_read'] == 0) ? 'unread' : ''; ?>" 
                     onclick="markAsRead(this, <?php echo $notif['notif_id']; ?>)">
                    
                    <div class="notif-icon-box">
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    
                    <div class="notif-content">
                        <div class="notif-title"><?php echo $notif['title']; ?></div>
                        <div class="notif-message"><?php echo $notif['message']; ?></div>
                        <div class="notif-time">
                            <i class="far fa-clock"></i> 
                            <?php echo date('d M H:i', strtotime($notif['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function markAsRead(element, notifId) {
        // 1. Cek apakah visual masih unread
        if (element.classList.contains('unread')) {
            
            // 2. Ubah Tampilan Secara Langsung (Optimistic UI)
            element.classList.remove('unread');
            
            // 3. Kurangi Badge Counter di Navbar
            const badge = document.querySelector('.notif-badge'); // Pastikan selector ini sesuai di dashboard.php
            if (badge) {
                let count = parseInt(badge.textContent);
                if (count > 1) {
                    badge.textContent = count - 1;
                } else {
                    badge.style.display = 'none';
                }
            }

            // 4. Update Database via AJAX
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('notif_id', notifId);

            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            }).catch(err => console.error("Gagal update notif:", err));
        }
    }
</script>