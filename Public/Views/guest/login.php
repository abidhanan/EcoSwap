<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../Assets/css/auth/login.css">
    <title>Masuk ke Akun</title>
    
</head>
<body>
    <div class="auth-container">
        <h2>Masuk ke Akun</h2>
        <form action="../../Auth/login/process_login.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="submit-btn">Masuk</button>
        </form>
        <p class="alt-action">
            Belum punya akun? <a href="../../Views/guest/register.php">Daftar</a>
        </p>
    </div>
</body>
</html>