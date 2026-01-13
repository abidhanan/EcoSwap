<?php
// --- NOTIFIKASI LOGIC ---
$notif_list = [];
if ($pending_products > 0) {
    $notif_list[] = ['icon'=>'fa-box', 'text'=>"$pending_products Produk baru menunggu verifikasi.", 'link'=>'../produk&stok/produk&stok.php', 'type'=>'warning'];
}
if ($active_reports > 0) {
    $notif_list[] = ['icon'=>'fa-exclamation-circle', 'text'=>"$active_reports Laporan baru perlu ditinjau.", 'link'=>'../support/support.php', 'type'=>'danger'];
}
$has_notif = count($notif_list) > 0;
?>

<div class="notif-icon" onclick="toggleNotif(event)">
    <i class="far fa-bell"></i>
    <?php if($has_notif): ?><span class="dot" style="background: red;"></span><?php endif; ?>
</div>

<div class="notif-dropdown" id="notifDropdown">
    <div class="notif-header">
        <span>Notifikasi</span>
        <i class="fas fa-times" onclick="toggleNotif(event)" style="cursor:pointer;"></i>
    </div>
    <ul class="notif-list">
        <?php if($has_notif): ?>
            <?php foreach($notif_list as $n): ?>
                <li class="notif-item" onclick="window.location.href='<?php echo $n['link']; ?>'">
                    <div class="notif-icon-box bg-<?php echo $n['type']; ?>"><i class="fas <?php echo $n['icon']; ?>"></i></div>
                    <span class="notif-text"><?php echo $n['text']; ?></span>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-notif"><i class="far fa-bell-slash"></i> Tidak ada notifikasi baru.</div>
        <?php endif; ?>
    </ul>
</div>

<script>
    function toggleNotif(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('notifDropdown');
        dropdown.classList.toggle('show');
    }
    
    // Close dropdown on click outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notif-icon') && !e.target.closest('.notif-dropdown')) {
            document.getElementById('notifDropdown').classList.remove('show');
        }
    });
</script>