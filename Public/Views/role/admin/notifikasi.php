<?php
// AJAX Endpoint untuk Real-Time Update Notifikasi
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_notifications') {
    header('Content-Type: application/json');
    
    $pending_products = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM products WHERE status = 'pending'"))['total'];
    $active_reports = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reports WHERE status = 'pending'"))['total'];
    
    // Ambil notifikasi chat dari database
    $admin_id = $_SESSION['user_id'];
    $chat_reports = [];
    $q_chat = mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id = '$admin_id' AND title = 'Laporan Chat' AND is_read = 0 ORDER BY created_at DESC");
    while($row = mysqli_fetch_assoc($q_chat)) {
        $chat_reports[] = $row;
    }
    
    $notif_list = [];
    if ($pending_products > 0) {
        $notif_list[] = ['icon'=>'fa-box', 'text'=>"$pending_products Produk baru menunggu verifikasi.", 'link'=>'produk.php', 'type'=>'warning'];
    }
    if ($active_reports > 0) {
        $notif_list[] = ['icon'=>'fa-exclamation-circle', 'text'=>"$active_reports Laporan baru perlu ditinjau.", 'link'=>'laporan.php', 'type'=>'danger'];
    }
    
    // Tambahkan notifikasi chat
    foreach($chat_reports as $chat) {
        $notif_list[] = [
            'icon'=>'fa-comment-dots', 
            'text'=>substr($chat['message'], 0, 60) . '...', 
            'link'=>'laporan.php', 
            'type'=>'danger',
            'notif_id'=>$chat['notif_id']
        ];
    }
    
    echo json_encode([
        'has_notif' => count($notif_list) > 0,
        'notif_list' => $notif_list,
        'pending_products' => $pending_products,
        'active_reports' => $active_reports,
        'chat_reports_count' => count($chat_reports)
    ]);
    exit();
}

// --- NOTIFIKASI LOGIC ---
$admin_id = $_SESSION['user_id'];
$notif_list = [];

if ($pending_products > 0) {
    $notif_list[] = ['icon'=>'fa-box', 'text'=>"$pending_products Produk baru menunggu verifikasi.", 'link'=>'produk.php', 'type'=>'warning'];
}
if ($active_reports > 0) {
    $notif_list[] = ['icon'=>'fa-exclamation-circle', 'text'=>"$active_reports Laporan baru perlu ditinjau.", 'link'=>'laporan.php', 'type'=>'danger'];
}

// Ambil notifikasi chat dari database
$q_chat = mysqli_query($koneksi, "SELECT * FROM notifications WHERE user_id = '$admin_id' AND title = 'Laporan Chat' AND is_read = 0 ORDER BY created_at DESC");
while($row = mysqli_fetch_assoc($q_chat)) {
    $notif_list[] = [
        'icon'=>'fa-comment-dots', 
        'text'=>substr($row['message'], 0, 60) . '...', 
        'link'=>'laporan.php', 
        'type'=>'danger',
        'notif_id'=>$row['notif_id']
    ];
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
    
    // Real-Time Update Notifikasi
    function updateNotifications() {
        fetch('notifikasi.php?ajax=get_notifications')
            .then(response => response.json())
            .then(data => {
                const notifIcon = document.querySelector('.notif-icon');
                const notifDot = notifIcon.querySelector('.dot');
                const notifList = document.querySelector('.notif-list');
                
                // Update dot indicator
                if (data.has_notif) {
                    if (!notifDot) {
                        notifIcon.insertAdjacentHTML('beforeend', '<span class="dot" style="background: red;"></span>');
                    }
                } else {
                    if (notifDot) {
                        notifDot.remove();
                    }
                }
                
                // Update notifikasi list
                if (data.notif_list.length > 0) {
                    let html = '';
                    data.notif_list.forEach(n => {
                        const clickHandler = n.notif_id ? 
                            'markChatNotifRead(' + n.notif_id + ', \'' + n.link + '\')' : 
                            'window.location.href=\'' + n.link + '\'';
                        
                        html += '<li class="notif-item" onclick="' + clickHandler + '">' +
                            '<div class="notif-icon-box bg-' + n.type + '"><i class="fas ' + n.icon + '"></i></div>' +
                            '<span class="notif-text">' + n.text + '</span>' +
                            '</li>';
                    });
                    notifList.innerHTML = html;
                } else {
                    notifList.innerHTML = '<div class="empty-notif"><i class="far fa-bell-slash"></i> Tidak ada notifikasi baru.</div>';
                }
            })
            .catch(error => console.error('Error updating notifications:', error));
    }
    
    // Mark chat notification as read
    function markChatNotifRead(notifId, link) {
        fetch('dashboard.php?ajax=mark_notif_read&notif_id=' + notifId)
            .then(() => {
                window.location.href = link;
            })
            .catch(error => console.error('Error marking notification as read:', error));
    }
    
    // Update setiap 5 detik
    setInterval(updateNotifications, 5000);
</script>
