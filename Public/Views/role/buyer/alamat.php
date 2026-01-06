<?php
session_start();

// Koneksi Database
include '../../../Auth/koneksi.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// --- [BARU] LOGIKA SET ALAMAT UTAMA ---
if (isset($_GET['action']) && $_GET['action'] == 'set_primary' && isset($_GET['id'])) {
    $id_addr = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // 1. Reset semua alamat user ini menjadi bukan utama (0)
    mysqli_query($koneksi, "UPDATE addresses SET is_primary = 0 WHERE user_id = '$user_id'");
    
    // 2. Set alamat yang dipilih menjadi utama (1)
    $update = mysqli_query($koneksi, "UPDATE addresses SET is_primary = 1 WHERE address_id = '$id_addr' AND user_id = '$user_id'");
    
    if($update) {
        // Refresh halaman agar tampilan checklist berpindah
        echo "<script>window.location.href='alamat.php';</script>";
        exit();
    }
}

// --- 1. LOGIKA TAMBAH ALAMAT ---
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    // Logika Label (Radio vs Input Manual)
    $label_choice = $_POST['label_choice'];
    if ($label_choice == 'Lainnya') {
        $label = mysqli_real_escape_string($koneksi, $_POST['custom_label']);
    } else {
        $label = mysqli_real_escape_string($koneksi, $label_choice);
    }

    $name = mysqli_real_escape_string($koneksi, $_POST['recipient_name']);
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone_number']);
    
    // Detail Alamat
    $addr = mysqli_real_escape_string($koneksi, $_POST['full_address']); 
    $village = mysqli_real_escape_string($koneksi, $_POST['village']);
    $subdistrict = mysqli_real_escape_string($koneksi, $_POST['subdistrict']);
    $city = mysqli_real_escape_string($koneksi, $_POST['city']);
    $postcode = mysqli_real_escape_string($koneksi, $_POST['postal_code']);
    
    $landmark = mysqli_real_escape_string($koneksi, $_POST['landmark']);

    // Cek apakah ini alamat pertama? Jika ya, set jadi primary
    $cek_addr = mysqli_query($koneksi, "SELECT address_id FROM addresses WHERE user_id='$user_id'");
    $is_primary = (mysqli_num_rows($cek_addr) == 0) ? 1 : 0;

    $query = "INSERT INTO addresses (user_id, label, recipient_name, phone_number, full_address, village, subdistrict, city, postal_code, landmark, is_primary) 
              VALUES ('$user_id', '$label', '$name', '$phone', '$addr', '$village', '$subdistrict', '$city', '$postcode', '$landmark', '$is_primary')";
    
    if(mysqli_query($koneksi, $query)) {
        echo "<script>alert('Alamat berhasil ditambahkan!'); window.location.href='alamat.php';</script>";
    } else {
        echo "<script>alert('Gagal menambah alamat: " . mysqli_error($koneksi) . "');</script>";
    }
}

// --- 2. LOGIKA EDIT ALAMAT ---
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['address_id'];
    
    $label_choice = $_POST['label_choice'];
    if ($label_choice == 'Lainnya') {
        $label = mysqli_real_escape_string($koneksi, $_POST['custom_label']);
    } else {
        $label = mysqli_real_escape_string($koneksi, $label_choice);
    }

    $name = mysqli_real_escape_string($koneksi, $_POST['recipient_name']);
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone_number']);
    
    $addr = mysqli_real_escape_string($koneksi, $_POST['full_address']);
    $village = mysqli_real_escape_string($koneksi, $_POST['village']);
    $subdistrict = mysqli_real_escape_string($koneksi, $_POST['subdistrict']);
    $city = mysqli_real_escape_string($koneksi, $_POST['city']);
    $postcode = mysqli_real_escape_string($koneksi, $_POST['postal_code']);
    
    $landmark = mysqli_real_escape_string($koneksi, $_POST['landmark']);

    $query = "UPDATE addresses SET 
              label='$label', 
              recipient_name='$name', 
              phone_number='$phone', 
              full_address='$addr', 
              village='$village',
              subdistrict='$subdistrict',
              city='$city',
              postal_code='$postcode',
              landmark='$landmark' 
              WHERE address_id='$id' AND user_id='$user_id'";
    
    if(mysqli_query($koneksi, $query)) {
        echo "<script>alert('Alamat berhasil diperbarui!'); window.location.href='alamat.php';</script>";
    } else {
        echo "<script>alert('Gagal update: " . mysqli_error($koneksi) . "');</script>";
    }
}

// --- 3. LOGIKA HAPUS ALAMAT ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Cek apakah yang dihapus adalah primary? Jika ya, set alamat lain jadi primary (opsional)
    $cek_prim = mysqli_query($koneksi, "SELECT is_primary FROM addresses WHERE address_id='$id'");
    $d_prim = mysqli_fetch_assoc($cek_prim);
    
    $del = mysqli_query($koneksi, "DELETE FROM addresses WHERE address_id='$id' AND user_id='$user_id'");
    
    if($del) {
        // Jika yang dihapus primary, set alamat tersisa terbaru jadi primary
        if($d_prim['is_primary'] == 1) {
            $next_addr = mysqli_query($koneksi, "SELECT address_id FROM addresses WHERE user_id='$user_id' ORDER BY address_id DESC LIMIT 1");
            if(mysqli_num_rows($next_addr) > 0) {
                $da = mysqli_fetch_assoc($next_addr);
                $nid = $da['address_id'];
                mysqli_query($koneksi, "UPDATE addresses SET is_primary=1 WHERE address_id='$nid'");
            }
        }
        echo "<script>alert('Alamat berhasil dihapus!'); window.location.href='alamat.php';</script>";
    }
}

// --- 4. AMBIL DATA ALAMAT ---
$addresses = [];
$q_addr = mysqli_query($koneksi, "SELECT * FROM addresses WHERE user_id='$user_id' ORDER BY is_primary DESC, address_id DESC");
while($row = mysqli_fetch_assoc($q_addr)) {
    $addresses[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Alamat - Ecoswap</title>
    <link rel="stylesheet" href="../../../Assets/css/role/buyer/alamat.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="app-layout">
        
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo" onclick="goToDashboard()" style="cursor:pointer;">
                    ECO<span>SWAP</span>
                </div>
            </div>

            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="profil.php" class="menu-link">
                        <i class="fas fa-user"></i>
                        <span>Biodata Diri</span>
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="alamat.php" class="menu-link">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Alamat</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="histori.php" class="menu-link">
                        <i class="fas fa-history"></i>
                        <span>Histori</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="../seller/dashboard.php" class="menu-link">
                        <i class="fas fa-store"></i>
                        <span>Toko Saya</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="../../../../index.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="main-content-wrapper">
            <header class="header">
                <h1 class="page-title">Alamat</h1>
            </header>

            <section class="content">
                <button class="add-btn" onclick="tambahAlamatBaru()">
                    <i class="fas fa-plus"></i> Tambah Alamat Baru
                </button>

                <div class="address-list" id="addressList">
                    
                    <?php if (empty($addresses)): ?>
                        <div style="text-align:center; padding:40px; color:#888;">
                            <i class="fas fa-map-marked-alt" style="font-size:3rem; margin-bottom:10px;"></i><br>
                            Belum ada alamat tersimpan.
                        </div>
                    <?php else: ?>
                        <?php foreach($addresses as $addr): ?>
                            <div class="address-card <?php echo ($addr['is_primary'] == 1) ? 'active' : ''; ?>" 
                                 onclick="setPrimaryAddress(<?php echo $addr['address_id']; ?>, <?php echo $addr['is_primary']; ?>)">
                                
                                <div class="check-icon"><i class="fas fa-check"></i></div>
                                
                                <div class="card-header">
                                    <span class="address-label">
                                        <?php echo $addr['label']; ?>
                                        <?php if($addr['is_primary'] == 1): ?>
                                            <span style="font-size:0.75rem; background-color:#eee; padding:2px 6px; border-radius:4px; margin-left:5px; color:#555;">Utama</span>
                                        <?php endif; ?>
                                    </span>
                                    <i class="fas fa-pen edit-btn" 
                                       onclick='editAlamat(event, <?php echo json_encode($addr); ?>)'></i>
                                </div>
                                <div class="card-body">
                                    <div class="receiver-name"><?php echo $addr['recipient_name']; ?></div>
                                    <span class="receiver-phone"><?php echo $addr['phone_number']; ?></span>
                                    
                                    <div class="address-detail">
                                        <?php echo $addr['full_address']; ?><br>
                                        <?php 
                                            $details = [];
                                            if(!empty($addr['village'])) $details[] = "Kel. " . $addr['village'];
                                            if(!empty($addr['subdistrict'])) $details[] = "Kec. " . $addr['subdistrict'];
                                            if(!empty($addr['city'])) $details[] = $addr['city'];
                                            if(!empty($addr['postal_code'])) $details[] = $addr['postal_code'];
                                            echo implode(", ", $details);
                                        ?>
                                    </div>

                                    <div class="address-landmark">
                                        <i class="fas fa-map-marker-alt"></i> 
                                        <?php echo !empty($addr['landmark']) ? $addr['landmark'] : 'Tidak ada patokan'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </section>
        </main>
    </div>

    <div class="modal-overlay" id="addressModal">
        <div class="modal-container">
            <div class="modal-header">
                <div class="modal-title" id="modalTitle">Tambah Alamat</div>
                <button class="close-modal" onclick="tutupModal()">&times;</button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="address_id" id="addressId" value="">

                <div class="form-group">
                    <label class="form-label">Simpan Sebagai</label>
                    <div class="label-options">
                        <label>
                            <input type="radio" name="label_choice" class="label-option-input" value="Rumah" checked onchange="toggleCustomLabel()">
                            <span class="label-option-pill">Rumah</span>
                        </label>
                        <label>
                            <input type="radio" name="label_choice" class="label-option-input" value="Kantor" onchange="toggleCustomLabel()">
                            <span class="label-option-pill">Kantor</span>
                        </label>
                        <label>
                            <input type="radio" name="label_choice" class="label-option-input" value="Lainnya" onchange="toggleCustomLabel()">
                            <span class="label-option-pill">Lainnya</span>
                        </label>
                    </div>
                    <input type="text" name="custom_label" id="customLabelInput" class="form-input custom-label-input" placeholder="Isi nama label (contoh: Kost, Apartemen)">
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Penerima</label>
                    <input type="text" name="recipient_name" id="inputName" class="form-input" placeholder="Contoh: Sondy Naufal" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nomor HP</label>
                    <input type="tel" name="phone_number" id="inputPhone" class="form-input" placeholder="Contoh: 0812xxxxxxxx" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Jalan / Nama Gedung / No. Rumah</label>
                    <textarea name="full_address" id="inputAddress" class="form-textarea" rows="2" placeholder="Contoh: Jl. Merdeka No. 10, RT 01/RW 02" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kelurahan / Desa</label>
                        <input type="text" name="village" id="inputVillage" class="form-input" placeholder="Nama Kelurahan" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kecamatan</label>
                        <input type="text" name="subdistrict" id="inputSubdistrict" class="form-input" placeholder="Nama Kecamatan" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kota / Kabupaten</label>
                        <input type="text" name="city" id="inputCity" class="form-input" placeholder="Nama Kota" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kode Pos</label>
                        <input type="number" name="postal_code" id="inputPostcode" class="form-input" placeholder="Contoh: 57123" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Patokan (Opsional)</label>
                    <input type="text" name="landmark" id="inputLandmark" class="form-input" placeholder="Contoh: Depan Masjid Al-Ikhlas">
                </div>

                <div class="modal-actions">
                    <button type="button" id="deleteBtn" class="delete-btn" onclick="hapusAlamat()" style="display:none;">
                        <i class="fas fa-trash-alt"></i> Hapus
                    </button>
                    <button type="submit" id="submitBtn" class="submit-btn">Simpan Alamat</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const goToDashboard = () => window.location.href = 'dashboard.php';

        function setPrimaryAddress(id, currentStatus) {
            if (currentStatus == 1) return;
            window.location.href = `alamat.php?action=set_primary&id=${id}`;
        }

        const modal = document.getElementById('addressModal');
        const modalTitle = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('submitBtn');
        const deleteBtn = document.getElementById('deleteBtn');
        const customLabelInput = document.getElementById('customLabelInput');
        
        const formAction = document.getElementById('formAction');
        const addressId = document.getElementById('addressId');
        const inputName = document.getElementById('inputName');
        const inputPhone = document.getElementById('inputPhone');
        const inputAddress = document.getElementById('inputAddress');
        const inputVillage = document.getElementById('inputVillage');
        const inputSubdistrict = document.getElementById('inputSubdistrict');
        const inputCity = document.getElementById('inputCity');
        const inputPostcode = document.getElementById('inputPostcode');
        const inputLandmark = document.getElementById('inputLandmark');

        function bukaModal() { modal.classList.add('open'); }
        function tutupModal() { modal.classList.remove('open'); }
        modal.addEventListener('click', function(e) { if (e.target === modal) tutupModal(); });

        function toggleCustomLabel() {
            const radios = document.getElementsByName('label_choice');
            let selectedValue;
            for (const radio of radios) {
                if (radio.checked) {
                    selectedValue = radio.value;
                    break;
                }
            }
            if (selectedValue === 'Lainnya') {
                customLabelInput.style.display = 'block';
                customLabelInput.required = true;
            } else {
                customLabelInput.style.display = 'none';
                customLabelInput.required = false;
                customLabelInput.value = ''; 
            }
        }

        function tambahAlamatBaru() {
            document.querySelector('form').reset();
            formAction.value = 'add';
            addressId.value = '';
            
            const rumahRadio = document.querySelector('input[value="Rumah"]');
            if(rumahRadio) rumahRadio.checked = true;
            toggleCustomLabel(); 

            modalTitle.textContent = "Tambah Alamat";
            submitBtn.textContent = "Simpan Alamat";
            deleteBtn.style.display = 'none';
            bukaModal();
        }

        function editAlamat(event, data) {
            event.stopPropagation();
            formAction.value = 'edit';
            addressId.value = data.address_id;
            inputName.value = data.recipient_name;
            inputPhone.value = data.phone_number;
            
            inputAddress.value = data.full_address;
            inputVillage.value = data.village || '';
            inputSubdistrict.value = data.subdistrict || '';
            inputCity.value = data.city || '';
            inputPostcode.value = data.postal_code || '';
            
            inputLandmark.value = data.landmark;

            const radios = document.getElementsByName('label_choice');
            let isStandard = false;
            for(let r of radios) {
                if(r.value === data.label) { 
                    r.checked = true; 
                    isStandard = true; 
                    break; 
                }
            }
            if (!isStandard) {
                const lainnyaRadio = document.querySelector('input[value="Lainnya"]');
                if(lainnyaRadio) lainnyaRadio.checked = true;
                customLabelInput.value = data.label;
            }
            toggleCustomLabel();

            modalTitle.textContent = "Ubah Alamat";
            submitBtn.textContent = "Simpan Perubahan";
            deleteBtn.style.display = 'block';
            bukaModal();
        }

        function hapusAlamat() {
            const id = addressId.value;
            if(id && confirm("Apakah Anda yakin ingin menghapus alamat ini?")) {
                window.location.href = `alamat.php?action=delete&id=${id}`;
            }
        }
    </script>
</body>
</html>