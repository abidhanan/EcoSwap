<?php
session_start();
include '../../../Auth/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id']; // seller user_id

/* =========================================================
   AJAX (seller side)
========================================================= */

function get_admin_id($koneksi){
    $q_adm = mysqli_query($koneksi, "SELECT user_id FROM users WHERE role='admin' LIMIT 1");
    $d_adm = mysqli_fetch_assoc($q_adm);
    return $d_adm ? (int)$d_adm['user_id'] : 0;
}

function fetch_messages_seller($koneksi, $seller_id, $buyer_id){
    $seller_id = (int)$seller_id;
    $buyer_id = (int)$buyer_id;

    $rows = [];
    $q = mysqli_query($koneksi, "
        SELECT chat_id, sender_id, receiver_id, message, image_path, created_at
        FROM chats
        WHERE (sender_id='$seller_id' AND receiver_id='$buyer_id')
           OR (sender_id='$buyer_id' AND receiver_id='$seller_id')
        ORDER BY created_at ASC
    ");
    while($r = mysqli_fetch_assoc($q)){
        $rows[] = [
            'id' => (int)$r['chat_id'],
            'type' => ((int)$r['sender_id'] === $seller_id) ? 'outgoing' : 'incoming',
            'text' => $r['message'],
            'img'  => $r['image_path'],
            'time' => date('H:i', strtotime($r['created_at']))
        ];
    }
    return $rows;
}

// GET CHAT
if(isset($_GET['action']) && $_GET['action']==='get_chat'){
    header('Content-Type: application/json');
    $buyer_id = (int)($_GET['buyer_id'] ?? 0);
    if($buyer_id<=0){ echo json_encode(['status'=>'error']); exit; }

    $q_b = mysqli_query($koneksi,"SELECT name, profile_picture FROM users WHERE user_id='$buyer_id' LIMIT 1");
    $b = mysqli_fetch_assoc($q_b);
    $name = $b ? $b['name'] : 'Buyer';
    $img  = (!empty($b['profile_picture'])) ? $b['profile_picture'] : "https://ui-avatars.com/api/?name=".urlencode($name)."&background=random";

    $messages = fetch_messages_seller($koneksi, $user_id, $buyer_id);
    echo json_encode(['status'=>'success','partner'=>['id'=>$buyer_id,'name'=>$name,'img'=>$img],'messages'=>$messages]);
    exit;
}

// SEND MESSAGE (text + image)
if(isset($_POST['action']) && $_POST['action']==='send_message'){
    header('Content-Type: application/json');

    $receiver_id = (int)($_POST['receiver_id'] ?? 0);
    $message = mysqli_real_escape_string($koneksi, trim($_POST['message'] ?? ''));

    if($receiver_id<=0){ echo json_encode(['status'=>'error']); exit; }

    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../../../Assets/img/chats/";
        if (!file_exists($target_dir)) { @mkdir($target_dir, 0777, true); }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if(!in_array($ext,$allowed)){ echo json_encode(['status'=>'error','message'=>'format tidak didukung']); exit; }

        $file_name = time() . "_" . bin2hex(random_bytes(5)) . "." . $ext;
        $target_file = $target_dir . $file_name;

        if(!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)){
            echo json_encode(['status'=>'error','message'=>'gagal upload']); exit;
        }
        $image_path = $target_file;
    }

    if($message==='' && empty($image_path)){ echo json_encode(['status'=>'empty']); exit; }

    $img_sql = $image_path ? "'" . mysqli_real_escape_string($koneksi,$image_path) . "'" : "NULL";
    $ins = mysqli_query($koneksi,"
        INSERT INTO chats (sender_id, receiver_id, message, image_path, created_at)
        VALUES ('$user_id','$receiver_id','$message',$img_sql,NOW())
    ");

    if($ins){
        echo json_encode(['status'=>'success','chat_id'=>mysqli_insert_id($koneksi),'time'=>date('H:i'),'image'=>$image_path]);
    } else {
        echo json_encode(['status'=>'error']);
    }
    exit;
}

// DELETE
if(isset($_POST['action']) && $_POST['action']==='delete_chat'){
    header('Content-Type: application/json');
    $chat_id = (int)($_POST['chat_id'] ?? 0);
    if($chat_id<=0){ echo json_encode(['status'=>'error']); exit; }
    $del = mysqli_query($koneksi,"DELETE FROM chats WHERE chat_id='$chat_id' AND sender_id='$user_id'");
    echo json_encode(['status'=>$del?'success':'error']);
    exit;
}

// REPORT -> NOTIF ADMIN
if(isset($_POST['action']) && $_POST['action']==='report_chat'){
    header('Content-Type: application/json');
    $chat_id = (int)($_POST['chat_id'] ?? 0);
    $reason = mysqli_real_escape_string($koneksi, trim($_POST['reason'] ?? ''));
    if($chat_id<=0 || $reason===''){ echo json_encode(['status'=>'error']); exit; }

    $q_c = mysqli_query($koneksi,"
        SELECT c.chat_id, c.sender_id, c.receiver_id, c.message, c.image_path, c.created_at,
               su.name as sender_name
        FROM chats c
        JOIN users su ON su.user_id = c.sender_id
        WHERE c.chat_id='$chat_id'
        LIMIT 1
    ");
    $d_c = mysqli_fetch_assoc($q_c);
    if(!$d_c){ echo json_encode(['status'=>'error']); exit; }

    $admin_id = get_admin_id($koneksi);
    if($admin_id){
        $content = $d_c['message'];
        if(empty($content) && !empty($d_c['image_path'])) $content = '[FOTO] ' . $d_c['image_path'];

        $report_msg =
            "LAPORAN CHAT\n".
            "- Pelapor (seller) ID: $user_id\n".
            "- Terlapor ID: ".$d_c['sender_id']." (".$d_c['sender_name'].")\n".
            "- Chat ID: ".$d_c['chat_id']."\n".
            "- Waktu: ".$d_c['created_at']."\n".
            "- Isi: ".$content."\n".
            "- Alasan: ".$reason;

        mysqli_query($koneksi,"
            INSERT INTO notifications (user_id, title, message, is_read, created_at)
            VALUES ('$admin_id','Laporan Chat','".mysqli_real_escape_string($koneksi,$report_msg)."',0,NOW())
        ");
    }

    echo json_encode(['status'=>'success']);
    exit;
}

/* =========================================================
   PAGE DATA (sidebar list)
========================================================= */

$chat_list = [];
$query_chat_list = "
    SELECT u.user_id, u.name, u.profile_picture,
           (SELECT message FROM chats
            WHERE (sender_id=u.user_id AND receiver_id='$user_id') OR (sender_id='$user_id' AND receiver_id=u.user_id)
            ORDER BY created_at DESC LIMIT 1) as last_msg,
           (SELECT image_path FROM chats
            WHERE (sender_id=u.user_id AND receiver_id='$user_id') OR (sender_id='$user_id' AND receiver_id=u.user_id)
            ORDER BY created_at DESC LIMIT 1) as last_img,
           (SELECT created_at FROM chats
            WHERE (sender_id=u.user_id AND receiver_id='$user_id') OR (sender_id='$user_id' AND receiver_id=u.user_id)
            ORDER BY created_at DESC LIMIT 1) as last_time
    FROM users u
    WHERE u.user_id IN (
        SELECT DISTINCT CASE WHEN sender_id='$user_id' THEN receiver_id ELSE sender_id END
        FROM chats
        WHERE sender_id='$user_id' OR receiver_id='$user_id'
    )
    ORDER BY last_time DESC
";

$res_list = mysqli_query($koneksi, $query_chat_list);
if ($res_list) {
    while ($row = mysqli_fetch_assoc($res_list)) {
        if(empty($row['profile_picture'])){
            $row['profile_picture'] = "https://ui-avatars.com/api/?name=".urlencode($row['name'])."&background=random";
        }
        if(empty($row['last_msg']) && !empty($row['last_img'])) $row['last_msg'] = '[Foto]';
        $chat_list[] = $row;
    }
}

$chat_partner_id = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : 0;
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
        .chat-main-header { display: flex; align-items: center; gap: 10px; padding: 15px 20px; border-bottom: 1px solid #eee; background: #fff; }
        .header-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .chat-partner-name { font-weight: bold; font-size: 1.1rem; color: #333; }
        .msg-time { font-size: 0.65rem; color: #aaa; margin-top: 5px; display: block; text-align: right; }
        .msg-me .msg-time { color: #e3f2fd; text-align: right; }

        .chat-context-menu {
            position: absolute; background: white; border: 1px solid #eee;
            border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000; min-width: 160px; display: none;
        }
        .chat-context-item {
            padding: 10px 15px; cursor: pointer; font-size: 0.85rem; color: #333; transition: 0.2s;
            display:flex; align-items:center; gap:8px;
        }
        .chat-context-item:hover { background: #f8f9fa; }
        .chat-context-item.danger { color: #dc3545; }
        .chat-context-item.danger:hover { background: #fff5f5; }
    </style>
</head>

<body>
<div class="app-layout">

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo" onclick="window.location.href='dashboard.php'" style="cursor:pointer;">ECO<span>SWAP</span></div>
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
                            <div style="padding:20px; color:#999; text-align:center;">Belum ada pesan.</div>
                        <?php else: ?>
                            <?php foreach($chat_list as $c): ?>
                                <?php $isActive = ($chat_partner_id == (int)$c['user_id']) ? 'active' : ''; ?>
                                <a href="?chat_with=<?php echo (int)$c['user_id']; ?>" class="chat-item <?php echo $isActive; ?>">
                                    <img src="<?php echo $c['profile_picture']; ?>" class="chat-avatar">
                                    <div class="chat-user-info">
                                        <h4><?php echo htmlspecialchars($c['name']); ?></h4>
                                        <p><?php echo mb_strimwidth(htmlspecialchars($c['last_msg']), 0, 30, "..."); ?></p>
                                    </div>
                                    <div style="font-size:0.7rem; color:#999; margin-left:auto;">
                                        <?php echo $c['last_time'] ? date('H:i', strtotime($c['last_time'])) : ''; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="chat-main">
                    <div class="chat-main-header">
                        <?php if($chat_partner_id): ?>
                            <img src="" class="header-avatar" id="partnerAvatar">
                            <div class="chat-partner-name" id="partnerName">Loading...</div>
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
                        <?php endif; ?>
                    </div>

                    <?php if ($chat_partner_id): ?>
                    <div class="chat-input-area" style="display:flex; gap:8px; align-items:center;">
                        <!-- tombol upload foto kecil (minim ubah tampilan) -->
                        <button type="button" class="chat-send-btn" style="background:#f5f5f5;color:#333;border:1px solid #e0e0e0;" onclick="openSellerFile()">
                            <i class="fas fa-image"></i>
                        </button>
                        <input type="file" id="sellerFile" accept="image/*" style="display:none;" onchange="sendSellerImage()">

                        <input type="text" id="sellerMsg" class="chat-input" placeholder="Tulis pesan..." autocomplete="off">
                        <button type="button" class="chat-send-btn" onclick="sendSellerText()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>
</div>

<div id="sellerContextMenu" class="chat-context-menu"></div>

<script>
    const buyerId = <?php echo (int)$chat_partner_id; ?>;
    const contextMenu = document.getElementById('sellerContextMenu');

    let partner = null;
    let messages = [];
    let pollTimer = null;

    function escapeHtml(str){
        return String(str)
            .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
            .replaceAll('"','&quot;').replaceAll("'","&#039;");
    }

    async function loadChat(){
        if(!buyerId) return;
        const res = await fetch(`chat.php?action=get_chat&buyer_id=${buyerId}`);
        const data = await res.json();
        if(data.status !== 'success') return;

        partner = data.partner;
        messages = data.messages || [];

        document.getElementById('partnerName').textContent = partner.name;
        document.getElementById('partnerAvatar').src = partner.img;

        render();
    }

    function render(){
        const box = document.getElementById('chatMessages');
        box.innerHTML = '';
        if(messages.length === 0){
            box.innerHTML = '<div class="empty-state"><p>Belum ada pesan.</p></div>';
            return;
        }

        messages.forEach(m=>{
            const cls = (m.type === 'outgoing') ? 'msg-me' : 'msg-other';

            let content = '';
            if(m.img) content += `<img src="${m.img}" style="max-width:260px;border-radius:10px;display:block;margin-bottom:${m.text ? '8px' : '0'};">`;
            if(m.text) content += `<div>${escapeHtml(m.text)}</div>`;

            box.innerHTML += `
                <div class="message-bubble ${cls}" onclick="showMenu(event, ${m.id}, '${m.type}')">
                    ${content}
                    <span class="msg-time">${m.time}</span>
                </div>
            `;
        });

        box.scrollTop = box.scrollHeight;
    }

    function showMenu(e, id, type){
        e.stopPropagation();
        contextMenu.innerHTML = '';
        if(type === 'outgoing'){
            contextMenu.innerHTML = `<div class="chat-context-item danger" onclick="deleteMsg(${id})"><i class="fas fa-trash"></i> Hapus Pesan</div>`;
        } else {
            contextMenu.innerHTML = `<div class="chat-context-item danger" onclick="reportMsg(${id})"><i class="fas fa-flag"></i> Laporkan Pesan</div>`;
        }
        contextMenu.style.top = e.pageY + 'px';
        contextMenu.style.left = e.pageX + 'px';
        contextMenu.style.display = 'block';
    }

    document.addEventListener('click', (e)=>{
        if(!e.target.closest('.message-bubble')) contextMenu.style.display='none';
    });

    async function deleteMsg(id){
        if(!confirm('Hapus pesan ini?')) return;
        const fd = new FormData();
        fd.append('action','delete_chat');
        fd.append('chat_id', id);

        const res = await fetch('chat.php',{method:'POST',body:fd});
        const data = await res.json();
        if(data.status==='success'){
            messages = messages.filter(m => m.id !== id);
            render();
            contextMenu.style.display='none';
        } else alert('Gagal hapus.');
    }

    async function reportMsg(id){
        const reason = prompt('Masukkan alasan pelaporan:');
        if(!reason) return;

        const fd = new FormData();
        fd.append('action','report_chat');
        fd.append('chat_id', id);
        fd.append('reason', reason);

        const res = await fetch('chat.php',{method:'POST',body:fd});
        const data = await res.json();
        if(data.status==='success') alert('Laporan terkirim ke admin.');
        else alert('Gagal melapor.');
        contextMenu.style.display='none';
    }

    async function sendSellerText(){
        const inp = document.getElementById('sellerMsg');
        const txt = inp.value.trim();
        if(!txt) return;

        inp.value = '';

        // optimistic UI
        const temp = { id: -Date.now(), type:'outgoing', text:txt, img:null, time:'...' };
        messages.push(temp); render();

        const fd = new FormData();
        fd.append('action','send_message');
        fd.append('receiver_id', buyerId);
        fd.append('message', txt);

        const res = await fetch('chat.php',{method:'POST',body:fd});
        const data = await res.json();
        if(data.status==='success'){
            const idx = messages.findIndex(m => m.id === temp.id);
            if(idx>=0){
                messages[idx].id = data.chat_id;
                messages[idx].time = data.time;
                render();
            }
        } else alert('Gagal mengirim.');
    }

    function openSellerFile(){ document.getElementById('sellerFile').click(); }

    async function sendSellerImage(){
        const fileInput = document.getElementById('sellerFile');
        const file = fileInput.files[0];
        if(!file) return;

        const localUrl = URL.createObjectURL(file);
        const temp = { id: -Date.now(), type:'outgoing', text:'', img:localUrl, time:'...' };
        messages.push(temp); render();

        const fd = new FormData();
        fd.append('action','send_message');
        fd.append('receiver_id', buyerId);
        fd.append('message','');
        fd.append('image', file);

        const res = await fetch('chat.php',{method:'POST',body:fd});
        const data = await res.json();
        if(data.status==='success'){
            const idx = messages.findIndex(m => m.id === temp.id);
            if(idx>=0){
                messages[idx].id = data.chat_id;
                messages[idx].img = data.image || messages[idx].img;
                messages[idx].time = data.time;
                render();
            }
        } else alert('Gagal mengirim foto.');

        fileInput.value = '';
    }

    // Polling realtime
    if(buyerId){
        loadChat();
        pollTimer = setInterval(async ()=>{
            const res = await fetch(`chat.php?action=get_chat&buyer_id=${buyerId}`);
            const data = await res.json();
            if(data.status==='success'){
                const newMsgs = data.messages || [];
                if(newMsgs.length !== messages.length || (newMsgs.at(-1)?.id !== messages.at(-1)?.id)){
                    messages = newMsgs;
                    render();
                }
            }
        }, 2000);
    }
</script>
</body>
</html>
