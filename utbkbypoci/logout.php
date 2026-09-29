<?php
session_start();

// Hapus semua session
$_SESSION = [];
session_unset();
session_destroy();

// Hapus cookie session (opsional tapi lebih aman)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect ke login.php di root htdocs
header("Location: /login.php");  
exit;
