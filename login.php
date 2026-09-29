<?php
session_start();

// arahkan koneksi ke folder webutbkbypoci
include __DIR__ . '/utbkbypoci/koneksi.php';

$notice = null;

if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // === LOGIN ADMIN (hardcode) ===
    if($username === "ADMINPOCI" && $password === "ADMINPOCI123"){
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'admin';
        header("Location: utbkbypoci/index_admin.php");
        exit;
    }

    // === LOGIN USER BIASA ===
    $stmt = $conn->prepare("SELECT * FROM user WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows === 1){
        $row = $res->fetch_assoc();

        if(password_verify($password, $row['password'])){
            // Simpan ke session
            $_SESSION['user_id']  = $row['id'];         // <<< FIX: wajib ada buat history.php
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = 'user';

            header("Location: utbkbypoci/index_user.php");
            exit;
        } else {
            $notice = "Username atau password salah!";
        }
    } else {
        $notice = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login UTBK</title>
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
        <h3 class="mb-3 text-center">Login UTBK</h3>
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
                <button type="button" class="btn-show-pass" onclick="togglePassword()">👁</button>
            </div>
            <button type="submit" name="login" class="btn btn-success w-100">Login</button>
        </form>
        <div class="mt-3 text-center">
            <small>Belum punya akun? <a href="utbkbypoci/register.php">Daftar di sini</a></small>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passInput = document.getElementById('password');
    passInput.type = (passInput.type === 'password') ? 'text' : 'password';
}
</script>
</body>
</html>
