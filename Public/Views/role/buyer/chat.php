<div class="chat-overlay-bg" id="chatOverlay" onclick="toggleChat()"></div>
<div class="chat-sidebar" id="chatSidebar">
    <div class="chat-header">
        <h3><i class="fas fa-comment-dots"></i> Chat</h3>
        <button class="close-chat-btn" onclick="toggleChat()"><i class="fas fa-times"></i></button>
    </div>

    <div class="chat-items" id="chatItemsContainer">
        <?php if(empty($chat_partners)): ?>
            <div style="text-align:center; padding:20px; color:#666;">Belum ada percakapan</div>
        <?php else: ?>
            <?php foreach($chat_partners as $pid => $info): ?>
            <div class="chat-item" onclick="selectChat(<?php echo $pid; ?>, '<?php echo addslashes($info['name']); ?>')">
                <div class="chat-avatar"><img src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?php echo $info['name']; ?>" alt="User"></div>
                <div class="chat-content">
                    <div class="chat-name"><?php echo $info['name']; ?></div>
                    <div class="chat-message"><?php echo $info['last_msg']; ?></div>
                    <div class="chat-time"><?php echo $info['time']; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="chat-area-sidebar" id="chatAreaSidebar" style="display: none;">
        <div class="chat-header-sidebar">
            <button class="back-to-list-btn" onclick="backToChatList()"><i class="fas fa-arrow-left"></i></button>
            <div class="seller-info-sidebar">
                <img src="" alt="Seller" class="seller-avatar-sidebar" id="chatSellerAvatarSidebar">
                <div class="seller-details-sidebar">
                    <h4 id="chatSellerNameSidebar">Nama Penjual</h4>
                    <span>Online</span>
                </div>
            </div>
        </div>
        <div class="chat-messages" id="chatMessagesSidebar"></div>
        <div class="input-area-sidebar">
            <button class="add-file-btn-sidebar" onclick="openFileInput()"><i class="fas fa-plus"></i></button>
            <input type="file" id="fileInput" accept="image/*" style="display: none;" onchange="sendImage()">
            <input type="text" class="chat-input-sidebar" id="messageInputSidebar" placeholder="Tulis pesan..." onkeypress="handleEnterSidebar(event)">
            <button class="send-btn-sidebar" onclick="sendMessageSidebar()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>