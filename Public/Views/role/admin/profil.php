<?php
// --- LOGIKA UPDATE PROFIL ADMIN ---
if (isset($_POST['update_profile'])) {
    // Escape input standar
    $name_p = mysqli_real_escape_string($koneksi, $_POST['name']);
    $email_p = mysqli_real_escape_string($koneksi, $_POST['email']);
    $phone_p = mysqli_real_escape_string($koneksi, $_POST['phone']);
    
    // Update Info Dasar
    $query_p = "UPDATE users SET name='$name_p', email='$email_p', phone_number='$phone_p' WHERE user_id='$admin_id'";
    mysqli_query($koneksi, $query_p);

    // Update Password (Jika Diisi)
    if (!empty($_POST['new_password'])) {
        // Hash password baru sebelum simpan
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        mysqli_query($koneksi, "UPDATE users SET password='$new_pass' WHERE user_id='$admin_id'");
    }

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
    
    // Refresh halaman
    echo "<script>alert('Profil berhasil diperbarui!'); window.location.href='dashboard.php';</script>";
    exit();
}
?>

<div class="modal-overlay" id="profileModal">
    <div class="modal-box compact-modal">
        <div class="modal-header-profil">
            <h3>Profil Admin</h3>
            <i class="fas fa-times close-modal-btn" onclick="closeProfileModal()"></i>
        </div>
        
        <form method="POST" enctype="multipart/form-data" id="formProfile" class="modal-scroll-content">
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

            <div class="form-container">
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

                <div class="form-group" id="passGroup" style="display:none; background:#f9f9f9; padding:10px; border-radius:8px; border:1px dashed #ddd;">
                    <label class="form-label" style="color:#d9534f;">Password Baru (Opsional)</label>
                    <input type="password" name="new_password" class="form-input" placeholder="Masukkan password baru..." disabled>
                    <small style="color:#888; font-size:0.75rem;">Kosongkan jika tidak ingin mengganti password.</small>
                </div>
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
    /* Compact Modal Style */
    .compact-modal {
        width: 400px;
        max-height: 90vh; /* Agar tidak melebihi tinggi layar */
        display: flex;
        flex-direction: column;
        border-radius: 12px;
        overflow: hidden;
    }

    .modal-header-profil { 
        padding: 15px 20px; 
        border-bottom: 1px solid #eee; 
        display: flex; justify-content: space-between; align-items: center; 
        background: #fff;
    }
    
    .modal-scroll-content {
        padding: 20px;
        overflow-y: auto; /* Scroll jika konten panjang */
    }

    .profile-upload-section { text-align: center; margin-bottom: 20px; position: relative; }
    .img-wrapper { position: relative; width: 90px; height: 90px; margin: 0 auto 8px; }
    .profile-img-lg { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid #f0f0f0; }
    
    .upload-overlay { 
        position: absolute; inset: 0; background: rgba(0,0,0,0.5); border-radius: 50%; 
        display: flex; align-items: center; justify-content: center; color: white; 
        font-size: 1.2rem; cursor: pointer; transition: 0.2s;
    }
    .upload-overlay:hover { background: rgba(0,0,0,0.7); }

    .admin-badge { background: #333; color: #FFD700; display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }

    /* Input Styles */
    .form-group { margin-bottom: 12px; }
    .form-label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; color: #555; }
    
    .form-input { 
        width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; 
        font-size: 0.9rem; background-color: #fff; transition: 0.3s;
        box-sizing: border-box; /* Agar padding tidak merusak layout */
    }
    
    /* Disabled State (View Mode) */
    .form-input:disabled { 
        background-color: transparent; border: 1px solid transparent; padding: 0; 
        font-weight: 600; color: #333; 
    }

    /* Buttons */
    .profile-actions { margin-top: 20px; display: flex; justify-content: center; }
    .btn-edit-profile { width: 100%; padding: 10px; background: #333; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-edit-profile:hover { background: #000; }

    .btn-save { flex: 1; padding: 10px; background: #FFD700; color: #000; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
    .btn-cancel { flex: 1; padding: 10px; background: #eee; color: #333; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
    .btn-cancel:hover { background: #ddd; }
</style>

<script>
    const profileModal = document.getElementById('profileModal');
    const inputs = document.querySelectorAll('.form-input');
    const passGroup = document.getElementById('passGroup');
    const uploadOverlay = document.getElementById('uploadOverlay');
    const btnEdit = document.getElementById('btnEdit');
    const actionButtons = document.getElementById('actionButtons');
    const formProfile = document.getElementById('formProfile');

    function openProfileModal() { 
        profileModal.classList.add('open'); 
        disableEditMode(); 
    }

    function closeProfileModal() { 
        profileModal.classList.remove('open'); 
    }

    function enableEditMode() {
        inputs.forEach(input => {
            input.disabled = false;
            input.style.padding = "8px 10px"; // Style input normal
            input.style.border = "1px solid #ddd";
        });
        
        uploadOverlay.style.display = 'flex';
        btnEdit.style.display = 'none';
        actionButtons.style.display = 'flex';
        
        // Tampilkan Input Password
        passGroup.style.display = 'block'; 
        
        document.getElementById('inputName').focus();
    }

    function disableEditMode() {
        inputs.forEach(input => {
            input.disabled = true;
            input.style.padding = "0"; // Style text only
            input.style.border = "1px solid transparent";
        });
        
        uploadOverlay.style.display = 'none';
        btnEdit.style.display = 'block';
        actionButtons.style.display = 'none';
        
        // Sembunyikan Input Password
        passGroup.style.display = 'none'; 
        
        formProfile.reset();
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