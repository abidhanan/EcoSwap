<?php
// --- LOGIKA UPDATE PROFIL ---
if (isset($_POST['update_profile'])) {
    // Pastikan koneksi sudah ada dari file induk (dashboard.php)
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
    
    // Redirect untuk refresh
    echo "<script>window.location.href='dashboard.php';</script>";
    exit();
}
?>

<div class="modal-overlay" id="profileModal">
    <div class="modal-box">
        <i class="fas fa-times close-modal-btn" onclick="closeProfileModal()"></i>
        
        <div id="profileSummary" class="profile-summary">
            <img src="<?php echo $admin_foto; ?>" class="profile-img-lg">
            <h3 class="profile-name"><?php echo htmlspecialchars($d_admin['name']); ?></h3>
            <span class="profile-role">Administrator</span>
            <div class="profile-details-text">
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($d_admin['email']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($d_admin['phone_number']); ?></p>
            </div>
            <button class="btn-edit-profile" onclick="showEditForm()"><i class="fas fa-pen"></i> Edit Profil</button>
        </div>

        <div id="profileEdit" class="profile-edit-form">
            <button class="btn-back" onclick="hideEditForm()"><i class="fas fa-arrow-left"></i> Kembali</button>
            <h3 class="modal-title">Edit Profil</h3>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="profile-upload-section">
                    <img src="<?php echo $admin_foto; ?>" id="previewImg" class="preview-img-small">
                    <label for="fileInput" class="upload-label">Ganti Foto</label>
                    <input type="file" name="profile_pic" id="fileInput" accept="image/*" onchange="previewFile(this)">
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($d_admin['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($d_admin['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="text" name="phone" class="form-input" value="<?php echo htmlspecialchars($d_admin['phone_number']); ?>">
                </div>
                
                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>

<script>
    const profileModal = document.getElementById('profileModal');
    const summaryView = document.getElementById('profileSummary');
    const editView = document.getElementById('profileEdit');

    function openProfileModal() { profileModal.classList.add('open'); hideEditForm(); }
    function closeProfileModal() { profileModal.classList.remove('open'); }
    function showEditForm() { summaryView.style.display = 'none'; editView.style.display = 'block'; }
    function hideEditForm() { editView.style.display = 'none'; summaryView.style.display = 'block'; }

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