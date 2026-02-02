<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../Assets/css/auth/register.css">
    <title>EcoSwap - Daftar</title>
    <link rel="icon" type="image/png" href="../../Assets/img/auth/logo.png">
    
<body>
    <div class="auth-container">
        <h2>Daftar Akun Baru</h2>
        <form action="../../Auth/login/process_register.php" method="POST">
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" placeholder="Contoh: Budi Santoso" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="phone_number">Nomor HP</label>
                <input type="tel" id="phone_number" name="phone_number" placeholder="Contoh: 0812xxxxxx" required>
            </div>
            <div class="form-group">
                <label for="address">Alamat</label>
                <input type="text" id="address" name="address" placeholder="Contoh: Jl. Merdeka No. 123" required>
            </div>
            <button type="submit" class="submit-btn">Daftar</button>
        </form>
        <p class="alt-action">
            Sudah punya akun? <a href="../../Views/guest/login.php">Masuk</a>
        </p>
    </div>
</body>
</html>