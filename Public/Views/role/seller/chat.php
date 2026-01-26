<?php
session_start();

// Koneksi Database
include '../../../Auth/koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id']; // ID Seller

// --- 1. HANDLE KIRIM PESAN ---
if (isset($_POST['send_message']) && !empty($_POST['message'])) {
    $receiver_id = mysqli_real_escape_string($koneksi, $_POST['receiver_id']);
    $msg = mysqli_real_escape_string($koneksi, $_POST['message']);
    
    // Insert Pesan
    $q_insert = "INSERT INTO chats (sender_id, receiver_id, message, created_at) VALUES ('$user_id', '$receiver_id', '$msg', NOW())";
    
    if(mysqli_query($koneksi, $q_insert)){
        // Redirect agar tidak resubmit saat refresh
        header("Location: chat.php?chat_with=$receiver_id");
        exit();
    }
}

// --- 2. AMBIL DAFTAR USER YANG PERNAH CHAT (SIDEBAR) ---
$chat_list = [];
$query_chat_list = "
    SELECT u.user_id, u.name, u.profile_picture,
           (SELECT message FROM chats WHERE (sender_id=u.user_id AND receiver_id='$user_id') OR (sender_id='$user_id' AND receiver_id=u.user_id) ORDER BY created_at DESC LIMIT 1) as last_msg,
           (SELECT created_at FROM chats WHERE (sender_id=u.user_id AND receiver_id='$user_id') OR (sender_id='$user_id' AND receiver_id=u.user_id) ORDER BY created_at DESC LIMIT 1) as last_time
    FROM users u
    WHERE u.user_id IN (
        SELECT DISTINCT CASE 
            WHEN sender_id = '$user_id' THEN receiver_id 
            ELSE sender_id 
        END 
        FROM chats 
        WHERE sender_id = '$user_id' OR receiver_id = '$user_id'
    )
    ORDER BY last_time DESC
";

$res_list = mysqli_query($koneksi, $query_chat_list);
if ($res_list) {
    while ($row = mysqli_fetch_assoc($res_list)) {
        // Fallback Avatar
        if(empty($row['profile_picture'])){
            $row['profile_picture'] = "https://ui-avatars.com/api/?name=".urlencode($row['name'])."&background=random";
        }
        $chat_list[] = $row;
    }
}

// --- 3. AMBIL ISI PERCAKAPAN AKTIF ---
$chat_partner_id = isset($_GET['chat_with']) ? $_GET['chat_with'] : null;
$active_chats = [];
$partner_name = "";
$partner_avatar = "";

if ($chat_partner_id) {
    // Info Partner
    $q_partner = mysqli_query($koneksi, "SELECT name, profile_picture FROM users WHERE user_id = '$chat_partner_id'");
    if($d_partner = mysqli_fetch_assoc($q_partner)){
        $partner_name = $d_partner['name'];
        $partner_avatar = !empty($d_partner['profile_picture']) ? $d_partner['profile_picture'] : "https://ui-avatars.com/api/?name=".urlencode($partner_name);
    }

    // Ambil Pesan (Urutkan dari lama ke baru)
    $query_msgs = "SELECT * FROM chats 
                   WHERE (sender_id = '$user_id' AND receiver_id = '$chat_partner_id') 
                   OR (sender_id = '$chat_partner_id' AND receiver_id = '$user_id') 
                   ORDER BY created_at ASC";
    $res_msgs = mysqli_query($koneksi, $query_msgs);
    if ($res_msgs) {
        while ($row = mysqli_fetch_assoc($res_msgs)) {
            $active_chats[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Penjual - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/dashboard.css">
    <link rel="stylesheet" href="../../../Assets/css/role/seller/chat.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Tambahan Style untuk layout chat yang lebih rapi */
        .chat-main-header { display: flex; align-items: center; gap: 10px; padding: 15px 20px; border-bottom: 1px solid #eee; background: #fff; }
        .header-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .chat-partner-name { font-weight: bold; font-size: 1.1rem; color: #333; }
        .msg-time { font-size: 0.65rem; color: #aaa; margin-top: 5px; display: block; text-align: right; }
        .msg-me .msg-time { color: #e3f2fd; text-align: right; }
    </style>
</head>

<body>

    <div class="app-layout">
        
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo" onclick="window.location.href='dashboard.php'" style="cursor:pointer;">
                    ECO<span>SWAP</span>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="menu-item"><a href="../buyer/profil.php" class="menu-link"><i class="fas fa-user"></i><span>Biodata Diri</span></a></li>
                <li class="menu-item"><a href="../buyer/alamat.php" class="menu-link"><i class="fas fa-map-marker-alt"></i><span>Alamat</span></a></li>
                <li class="menu-item"><a href="../buyer/histori.php" class="menu-link"><i class="fas fa-history"></i><span>Histori</span></a></li>
                <li class="menu-item active"><a href="dashboard.php" class="menu-link"><i class="fas fa-store"></i><span>Toko Saya</span></a></li>
            </ul>

            <div class="sidebar-footer">
                <a href="../../../../index.php" class="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <div class="header">
                <div class="page-title">Pesan Masuk</div>
            </div>

            <div class="content">
                <div class="chat-container">
                    
                    <div class="chat-sidebar-list">
                        <div class="chat-sidebar-header">Daftar Obrolan</div>
                        <div class="chat-list-items">
                            <?php if (empty($chat_list)): ?>
                                <div style="padding:20px; color:#999; text-align:center;">Belum ada pesan masuk.</div>
                            <?php else: ?>
                                <?php foreach($chat_list as $c): ?>
                                    <?php $isActive = ($chat_partner_id == $c['user_id']) ? 'active' : ''; ?>
                                    <a href="?chat_with=<?php echo $c['user_id']; ?>" class="chat-item <?php echo $isActive; ?>">
                                        <img src="<?php echo $c['profile_picture']; ?>" class="chat-avatar">
                                        <div class="chat-user-info">
                                            <h4><?php echo htmlspecialchars($c['name']); ?></h4>
                                            <p><?php echo mb_strimwidth(htmlspecialchars($c['last_msg']), 0, 30, "..."); ?></p>
                                        </div>
                                        <div style="font-size:0.7rem; color:#999; margin-left:auto;">
                                            <?php echo date('H:i', strtotime($c['last_time'])); ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="chat-main">
                        <div class="chat-main-header">
                            <?php if($chat_partner_id): ?>
                                <img src="<?php echo $partner_avatar; ?>" class="header-avatar">
                                <div class="chat-partner-name"><?php echo htmlspecialchars($partner_name); ?></div>
                            <?php else: ?>
                                <div class="chat-partner-name">Pilih Chat</div>
                            <?php endif; ?>
                        </div>

                        <div class="chat-messages" id="chatMessages">
                            <?php if (!$chat_partner_id): ?>
                                <div class="empty-state">
                                    <i class="fas fa-comments" style="font-size:4rem; margin-bottom:15px; color:#eee;"></i>
                                    <p>Pilih chat dari daftar kiri untuk memulai percakapan.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($active_chats as $msg): ?>
                                    <div class="message-bubble <?php echo ($msg['sender_id'] == $user_id) ? 'msg-me' : 'msg-other'; ?>">
                                        <?php echo htmlspecialchars($msg['message']); ?>
                                        <span class="msg-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($chat_partner_id): ?>
                        <form method="POST" class="chat-input-area">
                            <input type="hidden" name="receiver_id" value="<?php echo $chat_partner_id; ?>">
                            <input type="text" name="message" class="chat-input" placeholder="Tulis pesan..." required autocomplete="off" autofocus>
                            <button type="submit" name="send_message" class="chat-send-btn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        // Auto Scroll ke pesan terakhir
        var chatBox = document.getElementById("chatMessages");
        if(chatBox) { chatBox.scrollTop = chatBox.scrollHeight; }
    </script>
</body>
</html>