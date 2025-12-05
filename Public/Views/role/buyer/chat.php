<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Penjual - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/chat.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <div class="chat-app-container">
        
        <!-- HEADER -->
        <header class="chat-header">
            <button class="back-btn" onclick="kembaliKeDashboard()" title="Kembali ke Dashboard">
                <i class="fas fa-arrow-left"></i>
            </button>
            
            <div class="seller-info">
                <img src="" alt="Seller" class="seller-avatar" id="sellerAvatar">
                <div class="seller-details">
                    <h3 id="sellerName">Nama Penjual</h3>
                    <span>Online</span>
                </div>
            </div>

            <div class="header-actions">
                <button><i class="fas fa-phone-alt"></i></button>
                <button><i class="fas fa-ellipsis-v"></i></button>
            </div>
        </header>

        <!-- CHAT AREA -->
        <div class="chat-area" id="chatArea">
            <!-- Pesan akan dimuat lewat JS -->
        </div>

        <!-- INPUT AREA -->
        <div class="input-area">
            <button class="add-file-btn"><i class="fas fa-plus"></i></button>
            <input type="text" class="chat-input" id="messageInput" placeholder="Tulis pesan..." onkeypress="handleEnter(event)">
            <button class="send-btn" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>

    </div>

    <script>
        // 1. Setup Data Penjual (Simulasi dari LocalStorage atau Default)
        document.addEventListener('DOMContentLoaded', () => {
            const storedSeller = localStorage.getItem('currentChatSeller') || 'Penjual Ecoswap';
            document.getElementById('sellerName').textContent = storedSeller;
            document.getElementById('sellerAvatar').src = `https://api.dicebear.com/7.x/avataaars/svg?seed=${storedSeller}`;
            
            renderMessages();
        });

        const chatArea = document.getElementById('chatArea');
        
        // Data Dummy Awal
        let messages = [
            { id: 1, type: 'incoming', text: 'Halo kak, selamat datang di toko kami. Ada yang bisa dibantu?', time: '09:00' },
            { id: 2, type: 'outgoing', text: 'Halo gan, barang ini apakah masih ready stoknya?', time: '09:05' },
            { id: 3, type: 'incoming', text: 'Masih kak, silakan diorder ya sebelum kehabisan.', time: '09:06' }
        ];

        // 2. Render Pesan
        function renderMessages() {
            chatArea.innerHTML = '';
            
            // Tambahkan penanda hari (opsional)
            const dateSeparator = document.createElement('div');
            dateSeparator.style.textAlign = 'center';
            dateSeparator.style.fontSize = '0.75rem';
            dateSeparator.style.color = '#888';
            dateSeparator.style.margin = '10px 0';
            dateSeparator.textContent = 'Hari Ini';
            chatArea.appendChild(dateSeparator);

            messages.forEach(msg => {
                const wrapper = document.createElement('div');
                wrapper.className = `message-wrapper ${msg.type}`;
                
                // Tombol Aksi (Lapor untuk incoming, Hapus untuk outgoing)
                let actionButton = '';
                if (msg.type === 'incoming') {
                    actionButton = `
                        <div class="message-actions">
                            <button class="action-icon-btn report" title="Laporkan Chat Ini" onclick="reportMessage(${msg.id})">
                                <i class="fas fa-exclamation-triangle"></i>
                            </button>
                        </div>
                    `;
                } else {
                    actionButton = `
                        <div class="message-actions">
                            <button class="action-icon-btn delete" title="Batal Kirim / Hapus" onclick="deleteMessage(${msg.id})">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
                }

                wrapper.innerHTML = `
                    <div class="message-bubble">
                        ${msg.text}
                    </div>
                    <span class="message-time">${msg.time}</span>
                    ${actionButton}
                `;
                
                chatArea.appendChild(wrapper);
            });
            
            scrollToBottom();
        }

        // 3. Kirim Pesan
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const text = input.value.trim();
            
            if (text) {
                const now = new Date();
                const timeString = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
                
                const newMsg = {
                    id: Date.now(),
                    type: 'outgoing',
                    text: text,
                    time: timeString
                };
                
                messages.push(newMsg);
                renderMessages();
                input.value = '';

                // Simulasi Balasan Otomatis
                setTimeout(() => {
                    const replyMsg = {
                        id: Date.now() + 1,
                        type: 'incoming',
                        text: 'Terima kasih pesannya kak, kami akan segera membalas.',
                        time: timeString
                    };
                    messages.push(replyMsg);
                    renderMessages();
                }, 1500);
            }
        }

        function handleEnter(e) {
            if (e.key === 'Enter') sendMessage();
        }

        // 4. Fitur Batal Kirim (Hapus)
        function deleteMessage(id) {
            if (confirm('Batalkan kirim pesan ini? (Hapus untuk saya)')) {
                messages = messages.filter(m => m.id !== id);
                renderMessages();
            }
        }

        // 5. Fitur Laporkan
        function reportMessage(id) {
            if (confirm('Laporkan pesan ini sebagai spam atau penipuan?')) {
                alert('Laporan diterima. Tim Ecoswap akan meninjau percakapan ini.');
            }
        }

        // 6. Navigasi Kembali
        function kembaliKeDashboard() {
            window.location.href = 'dashboard.php';
        }

        function scrollToBottom() {
            chatArea.scrollTop = chatArea.scrollHeight;
        }

    </script>
</body>
</html>
