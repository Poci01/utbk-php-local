<?php
session_start();
include 'koneksi.php';

$notice = null;

if(isset($_POST['register'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validasi password sama
    if($password !== $confirm_password){
        $notice = "Password dan konfirmasi password tidak sama!";
    } else {
        // Cek apakah username sudah ada
        $stmt = $conn->prepare("SELECT * FROM user WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if($res->num_rows > 0){
            $notice = "Username sudah digunakan!";
        } else {
            // Hash password sebelum disimpan
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user baru
            $stmt = $conn->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashedPassword);

            if($stmt->execute()){
                // Redirect ke login setelah registrasi sukses
                header("Location: /login.php");
                exit;
            } else {
                $notice = "Gagal mendaftar: " . $stmt->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register UTBK</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Arial; background:#f7f7f7; }
.container { max-width: 400px; margin-top:100px; }
.btn-show-pass { cursor:pointer; position:absolute; right:10px; top:50%; transform:translateY(-50%); border:none; background:none; }
.position-relative { position:relative; }
</style>
</head>
<body>
<div class="container">
    <div class="card shadow-sm p-4">
        <h3 class="mb-3 text-center">Daftar Akun UTBK</h3>
        <?php if($notice): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3 position-relative">
                <label>Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <button type="button" class="btn-show-pass" onclick="togglePassword('password')">👁</button>
            </div>
            <div class="mb-3 position-relative">
                <label>Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                <button type="button" class="btn-show-pass" onclick="togglePassword('confirm_password')">👁</button>
            </div>
            <button type="submit" name="register" class="btn btn-primary w-100">Daftar</button>
        </form>
        <div class="mt-3 text-center">
            <small>Sudah punya akun? <a href="/login.php">Login di sini</a></small>
        </div>
    </div>
</div>

<script>
function togglePassword(id){
    const passInput = document.getElementById(id);
    passInput.type = passInput.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
