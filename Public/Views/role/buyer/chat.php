<div class="chat-overlay-bg" id="chatOverlay" onclick="toggleChat()"></div>

<div class="chat-sidebar" id="chatSidebar" style="display:flex; flex-direction:column; height:100vh;">
    <div class="chat-header">
        <h3><i class="fas fa-comment-dots"></i> Chat</h3>
        <button class="close-chat-btn" onclick="toggleChat()"><i class="fas fa-times"></i></button>
    </div>

    <div class="chat-items" id="chatItemsContainer" style="overflow-y:auto; flex:1;">
        <?php if(empty($chat_partners)): ?>
            <div style="text-align:center; padding:20px; color:#666;">Belum ada percakapan</div>
        <?php else: ?>
            <?php foreach($chat_partners as $pid => $info): ?>
            <div class="chat-item" onclick="selectChat(<?php echo (int)$pid; ?>)">
                <div class="chat-avatar"><img src="<?php echo $info['img']; ?>" alt="User"></div>
                <div class="chat-content">
                    <div class="chat-name"><?php echo htmlspecialchars($info['name']); ?></div>
                    <div class="chat-message"><?php echo htmlspecialchars($info['last_msg']); ?></div>
                    <div class="chat-time"><?php echo htmlspecialchars($info['time']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="chat-area-sidebar" id="chatAreaSidebar" style="display:none; flex-direction:column; flex:1; min-height:0;">
        <div class="chat-header-sidebar">
            <button class="back-to-list-btn" onclick="backToChatList()"><i class="fas fa-arrow-left"></i></button>
            <div class="seller-info-sidebar">
                <img src="" alt="Seller" class="seller-avatar-sidebar" id="chatSellerAvatarSidebar">
                <div class="seller-details-sidebar">
                    <h4 id="chatSellerNameSidebar">Nama Toko</h4>
                    <span>Online</span>
                </div>
            </div>
        </div>

        <!-- INI YANG PENTING: FLEX + MIN-HEIGHT 0 + OVERFLOW -->
        <div class="chat-messages" id="chatMessagesSidebar" style="flex:1; overflow-y:auto; padding-bottom:8px; min-height:0;"></div>

        <div class="input-area-sidebar">
            <button class="add-file-btn-sidebar" onclick="openFileInput()"><i class="fas fa-plus"></i></button>
            <input type="file" id="fileInput" accept="image/*" style="display:none;" onchange="sendImage()">
            <input type="text" class="chat-input-sidebar" id="messageInputSidebar" placeholder="Tulis pesan..." onkeypress="handleEnterSidebar(event)">
            <button class="send-btn-sidebar" onclick="sendMessageSidebar()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>
