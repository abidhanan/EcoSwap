<?php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keuangan Toko - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/seller/keuangan.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>

    <div class="app-layout">
        
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo" onclick="window.location.href='../buyer/dashboard.php'" style="cursor: pointer;">ECO<span>SWAP</span></div>
            </div>
            <ul class="sidebar-menu">
                <li class="menu-item"><a href="../buyer/profil.php" onclick="safeNavigate('../buyer/profil.php')" class="menu-link">Biodata Diri</a></li>
                <li class="menu-item"><a href="../buyer/alamat.php" onclick="safeNavigate('../buyer/alamat.php')" class="menu-link">Alamat</a></li>
                <li class="menu-item"><a href="../buyer/histori.php" onclick="safeNavigate('../buyer/histori.php')" class="menu-link">Histori</a></li>
                <li class="menu-item active"><a href="dashboard.php" onclick="safeNavigate('dashboard.php')" class="menu-link">Toko Saya</a></li>
            </ul>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content-wrapper">
            <div class="header">
                <div class="page-title">Keuangan</div>
            </div>

            <div class="content">
                <div class="finance-container">
                    
                    <!-- SALDO CARD -->
                    <div class="balance-card">
                        <div class="balance-label">Total Pendapatan Bersih</div>
                        <div class="balance-amount">Rp 5.450.000</div>
                        <div class="balance-actions">
                            <button class="btn-action btn-withdraw" onclick="openWithdrawModal()">
                                <i class="fas fa-money-bill-wave"></i> Tarik Tunai
                            </button>
                            <button class="btn-action btn-history" onclick="toggleHistory()">
                                <i class="fas fa-history"></i> Lihat Histori
                            </button>
                            <!-- TOMBOL KELOLA KEUANGAN -->
                            <button class="btn-action btn-manage" onclick="safeNavigate('kelolaKeuangan.php')">
                                <i class="fas fa-cog"></i> Kelola Keuangan
                            </button>
                        </div>
                    </div>

                    <!-- HISTORY SECTION (Toggleable) -->
                    <div class="history-section" id="historySection">
                        <div class="section-header">
                            <h3 class="section-title">Riwayat Transaksi</h3>
                            <button style="background:none; border:none; color:#888; cursor:pointer;" onclick="toggleHistory()"><i class="fas fa-times"></i></button>
                        </div>
                        
                        <div class="transaction-list">
                            <div class="transaction-item">
                                <div class="trans-left">
                                    <div class="trans-icon icon-in"><i class="fas fa-arrow-down"></i></div>
                                    <div class="trans-info">
                                        <h4>Penjualan #ORD-2023001</h4>
                                        <span class="trans-date">24 Nov 2024, 14:30</span>
                                    </div>
                                </div>
                                <div class="trans-amount amount-plus">+ Rp 1.200.000</div>
                            </div>
                            <div class="transaction-item">
                                <div class="trans-left">
                                    <div class="trans-icon icon-out"><i class="fas fa-arrow-up"></i></div>
                                    <div class="trans-info">
                                        <h4>Penarikan ke BCA</h4>
                                        <span class="trans-date">20 Nov 2024, 09:15</span>
                                    </div>
                                </div>
                                <div class="trans-amount amount-minus">- Rp 500.000</div>
                            </div>
                            <div class="transaction-item">
                                <div class="trans-left">
                                    <div class="trans-icon icon-in"><i class="fas fa-arrow-down"></i></div>
                                    <div class="trans-info">
                                        <h4>Penjualan #ORD-2023005</h4>
                                        <span class="trans-date">18 Nov 2024, 10:00</span>
                                    </div>
                                </div>
                                <div class="trans-amount amount-plus">+ Rp 3.100.000</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <!-- MODAL TARIK TUNAI -->
    <div class="modal-overlay" id="withdrawModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title">Tarik Tunai</div>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            
            <form onsubmit="processWithdraw(event)">
                <div class="form-group">
                    <label class="form-label">Pilih Metode</label>
                    <div class="method-toggle">
                        <button type="button" class="method-btn active" onclick="toggleMethod('ewallet', this)">
                            <i class="fas fa-wallet"></i> E-Wallet
                        </button>
                        <button type="button" class="method-btn" onclick="toggleMethod('bank', this)">
                            <i class="fas fa-university"></i> Bank
                        </button>
                    </div>
                </div>

                <div id="ewalletOptions" class="bank-options">
                    <label>
                        <input type="radio" name="dest" value="Dana" class="bank-radio" checked>
                        <div class="bank-card">
                            <img src="https://placehold.co/100x50/118EEA/ffffff?text=DANA" class="payment-logo" alt="DANA">
                            <span>DANA</span>
                        </div>
                    </label>
                    <label>
                        <input type="radio" name="dest" value="OVO" class="bank-radio">
                        <div class="bank-card">
                            <img src="https://placehold.co/100x50/4C2A86/ffffff?text=OVO" class="payment-logo" alt="OVO">
                            <span>OVO</span>
                        </div>
                    </label>
                    <label>
                        <input type="radio" name="dest" value="GoPay" class="bank-radio">
                        <div class="bank-card">
                            <img src="https://placehold.co/100x50/00A5CF/ffffff?text=GoPay" class="payment-logo" alt="GoPay">
                            <span>GoPay</span>
                        </div>
                    </label>
                    <label>
                        <input type="radio" name="dest" value="ShopeePay" class="bank-radio">
                        <div class="bank-card">
                            <img src="https://placehold.co/100x50/EE4D2D/ffffff?text=Shopee" class="payment-logo" alt="ShopeePay">
                            <span>ShopeePay</span>
                        </div>
                    </label>
                </div>

                <div id="bankOptions" class="bank-options" style="display:none;">
                    <label>
                        <input type="radio" name="dest" value="BCA" class="bank-radio">
                        <div class="bank-card">
                            <img src="https://placehold.co/100x50/003399/ffffff?text=BCA" class="payment-logo" alt="BCA">
                            <span>BCA</span>
                        </div>
                    </label>
                    <label>
                        <input type="radio" name="dest" value="Mandiri" class="bank-radio">
                        <div class="bank-card">
                            <img src="https://placehold.co/100x50/FFB700/000000?text=Mandiri" class="payment-logo" alt="Mandiri">
                            <span>Mandiri</span>
                        </div>
                    </label>
                    <label>
                        <input type="radio" name="dest" value="BRI" class="bank-radio">
                        <div class="bank-card">
                            <img src="https://placehold.co/100x50/00529C/ffffff?text=BRI" class="payment-logo" alt="BRI">
                            <span>BRI</span>
                        </div>
                    </label>
                    <label>
                        <input type="radio" name="dest" value="BNI" class="bank-radio">
                        <div class="bank-card">
                            <img src="https://placehold.co/100x50/005E6A/ffffff?text=BNI" class="payment-logo" alt="BNI">
                            <span>BNI</span>
                        </div>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor Rekening / HP</label>
                    <input type="text" class="form-input" placeholder="Contoh: 08123456789" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nominal Penarikan</label>
                    <div class="input-wrapper">
                        <span class="input-prefix">Rp</span>
                        <input type="number" class="form-input has-prefix" placeholder="0" required>
                    </div>
                </div>

                <div class="withdraw-note">
                    <i class="fas fa-info-circle"></i> Biaya admin Rp 2.500 akan dipotong dari saldo. Proses maksimal 1x24 jam.
                </div>

                <button type="submit" class="btn-submit">Konfirmasi Penarikan</button>
            </form>
        </div>
    </div>

    <script>
        function safeNavigate(url) {
            try {
                window.location.href = url;
            } catch (e) {
                console.warn("Navigasi simulasi:", url);
                alert("Simulasi: Berpindah ke halaman " + url);
            }
        }

        function toggleHistory() {
            const historySec = document.getElementById('historySection');
            if (historySec.style.display === 'none' || historySec.style.display === '') {
                historySec.style.display = 'block';
            } else {
                historySec.style.display = 'none';
            }
        }

        const modal = document.getElementById('withdrawModal');
        function openWithdrawModal() { modal.classList.add('open'); }
        function closeModal() { modal.classList.remove('open'); }
        window.onclick = function(e) { if (e.target == modal) closeModal(); }

        function toggleMethod(type, btnElement) {
            document.querySelectorAll('.method-btn').forEach(b => b.classList.remove('active'));
            btnElement.classList.add('active');
            const ewalletDiv = document.getElementById('ewalletOptions');
            const bankDiv = document.getElementById('bankOptions');
            if (type === 'bank') {
                ewalletDiv.style.display = 'none';
                bankDiv.style.display = 'grid';
                const firstBank = bankDiv.querySelector('input');
                if(firstBank) firstBank.checked = true;
            } else {
                bankDiv.style.display = 'none';
                ewalletDiv.style.display = 'grid';
                const firstWallet = ewalletDiv.querySelector('input');
                if(firstWallet) firstWallet.checked = true;
            }
        }

        function processWithdraw(e) {
            e.preventDefault();
            const method = document.querySelector('input[name="dest"]:checked').value;
            alert(`Permintaan penarikan ke ${method} berhasil dikirim!`);
            closeModal();
        }
    </script>
</body>
</html>