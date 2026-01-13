<?php
// --- LOGIKA UPDATE PROFIL ---
if (isset($_POST['update_profile'])) {
    // Pastikan koneksi sudah ada dari file induk
    $name_p = mysqli_real_escape_string($koneksi, $_POST['name']);
    $email_p = mysqli_real_escape_string($koneksi, $_POST['email']);
    $phone_p = mysqli_real_escape_string($koneksi, $_POST['phone']);
    
    // Update Info Dasar
    $query_p = "UPDATE users SET name='$name_p', email='$email_p', phone_number='$phone_p' WHERE user_id='$admin_id'";
    mysqli_query($koneksi, $query_p);

    // Handle Upload Foto
    if (!empty($_FILES['profile_pic']['name'])) {
        $target_dir_p = "../../../Assets/img/profiles/";
        if (!file_exists($target_dir_p)) { mkdir($target_dir_p, 0777, true); }
        
        $file_name_p = time() . "_" . basename($_FILES["profile_pic"]["name"]);
        $target_file_p = $target_dir_p . $file_name_p;
        
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file_p)) {
            $db_path_p = "../../../Assets/img/profiles/" . $file_name_p;
            mysqli_query($koneksi, "UPDATE users SET profile_picture='$db_path_p' WHERE user_id='$admin_id'");
        }
    }
    
    // Redirect untuk refresh data
    echo "<script>window.location.href='dashboard.php';</script>";
    exit();
}
?>

<div class="modal-overlay" id="profileModal">
    <div class="modal-box">
        <div class="modal-header-profil">
            <h3>Profil Admin</h3>
            <i class="fas fa-times close-modal-btn" onclick="closeProfileModal()"></i>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="formProfile">
            <input type="hidden" name="update_profile" value="1">
            
            <div class="profile-upload-section">
                <div class="img-wrapper">
                    <img src="<?php echo $admin_foto; ?>" id="previewImg" class="profile-img-lg">
                    <label for="fileInput" class="upload-overlay" id="uploadOverlay" style="display: none;">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>
                <input type="file" name="profile_pic" id="fileInput" accept="image/*" style="display:none;" onchange="previewFile(this)">
                <div class="admin-badge">Administrator</div>
            </div>

            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" id="inputName" class="form-input" value="<?php echo htmlspecialchars($d_admin['name']); ?>" disabled required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="inputEmail" class="form-input" value="<?php echo htmlspecialchars($d_admin['email']); ?>" disabled required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nomor Telepon</label>
                <input type="text" name="phone" id="inputPhone" class="form-input" value="<?php echo htmlspecialchars($d_admin['phone_number']); ?>" disabled>
            </div>
            
            <div class="profile-actions">
                <button type="button" class="btn-edit-profile" id="btnEdit" onclick="enableEditMode()">
                    <i class="fas fa-pen"></i> Edit Profil
                </button>

                <div id="actionButtons" style="display: none; gap: 10px; width: 100%;">
                    <button type="button" class="btn-cancel" onclick="disableEditMode()">Batal</button>
                    <button type="submit" class="btn-save">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    /* Styling Khusus agar tampilan konsisten */
    .modal-header-profil { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-header-profil h3 { margin: 0; font-size: 1.2rem; color: #333; }
    
    .profile-upload-section { text-align: center; margin-bottom: 25px; position: relative; }
    .img-wrapper { position: relative; width: 100px; height: 100px; margin: 0 auto 10px; }
    .profile-img-lg { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid #f0f0f0; }
    
    /* Overlay Kamera saat Edit */
    .upload-overlay { 
        position: absolute; inset: 0; background: rgba(0,0,0,0.5); border-radius: 50%; 
        display: flex; align-items: center; justify-content: center; color: white; 
        font-size: 1.5rem; cursor: pointer; transition: 0.2s;
    }
    .upload-overlay:hover { background: rgba(0,0,0,0.7); }

    .admin-badge { background: #333; color: #FFD700; display: inline-block; padding: 4px 12px; border-radius: 15px; font-size: 0.8rem; font-weight: 600; }

    /* Input Styles */
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; color: #555; }
    
    /* Input Disabled (Tampilan Normal) */
    .form-input:disabled { 
        background-color: transparent; 
        border: 1px solid transparent; 
        padding: 0; 
        font-weight: 600; 
        color: #333; 
        font-size: 1rem;
    }
    
    /* Input Enabled (Tampilan Edit) */
    .form-input { 
        width: 100%; 
        padding: 10px; 
        border: 1px solid #ddd; 
        border-radius: 6px; 
        font-size: 0.95rem; 
        background-color: #fff;
        transition: 0.3s;
    }

    /* Buttons */
    .profile-actions { margin-top: 25px; display: flex; justify-content: center; }
    .btn-edit-profile { width: 100%; padding: 12px; background: #333; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-edit-profile:hover { background: #000; }

    .btn-save { flex: 1; padding: 12px; background: #FFD700; color: #000; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
    .btn-cancel { flex: 1; padding: 12px; background: #eee; color: #333; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
    .btn-cancel:hover { background: #ddd; }
</style>

<script>
    const profileModal = document.getElementById('profileModal');
    
    // Elemen-elemen form
    const inputs = document.querySelectorAll('.form-input');
    const uploadOverlay = document.getElementById('uploadOverlay');
    const btnEdit = document.getElementById('btnEdit');
    const actionButtons = document.getElementById('actionButtons');
    const formProfile = document.getElementById('formProfile');

    function openProfileModal() { 
        profileModal.classList.add('open'); 
        disableEditMode(); // Pastikan selalu mulai dalam mode view
    }

    function closeProfileModal() { 
        profileModal.classList.remove('open'); 
    }

    function enableEditMode() {
        // 1. Aktifkan Input
        inputs.forEach(input => {
            input.disabled = false;
            input.style.padding = "10px"; // Balik ke style input normal
            input.style.border = "1px solid #ddd";
        });
        // 2. Tampilkan Overlay Upload & Tombol Simpan
        uploadOverlay.style.display = 'flex';
        btnEdit.style.display = 'none';
        actionButtons.style.display = 'flex';
        
        // Fokus ke nama
        document.getElementById('inputName').focus();
    }

    function disableEditMode() {
        // 1. Matikan Input
        inputs.forEach(input => {
            input.disabled = true;
            input.style.padding = "0"; // Balik ke style text only
            input.style.border = "1px solid transparent";
        });
        // 2. Sembunyikan Overlay & Tombol Simpan
        uploadOverlay.style.display = 'none';
        btnEdit.style.display = 'block';
        actionButtons.style.display = 'none';
        
        // Reset form ke nilai awal (opsional, agar jika batal kembali ke data DB)
        formProfile.reset();
        // Reset preview gambar jika ada perubahan tapi dibatalkan (perlu reload src asli jika mau perfect, tapi reset form cukup untuk input text)
    }

    function previewFile(input) {
        const file = input.files[0];
        if(file){
            const reader = new FileReader();
            reader.onload = function(){ document.getElementById('previewImg').src = reader.result; }
            reader.readAsDataURL(file);
        }
    }

    window.addEventListener('click', function(e) {
        if(e.target == profileModal) closeProfileModal();
    });
</script>