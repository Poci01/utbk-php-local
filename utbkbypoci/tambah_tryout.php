<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$notice = null;

// Proses submit form
if(isset($_POST['submit'])){
    $nama_tryout = trim($_POST['nama_tryout']);
    $tanggal = trim($_POST['tanggal']);

    if($nama_tryout === "" || $tanggal === ""){
        $notice = "Semua field harus diisi!";
    } else {
        // Insert ke database
        $stmt = $conn->prepare("INSERT INTO tryout (nama_tryout, tanggal) VALUES (?, ?)");
        $stmt->bind_param("ss", $nama_tryout, $tanggal);
        if($stmt->execute()){
            header("Location: index_admin.php?notice=Tryout berhasil ditambahkan!");
            exit;
        } else {
            $notice = "Gagal menambahkan tryout: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Tryout - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Arial; background:#f7f7f7; }
.container { max-width:500px; margin-top:80px; }
.btn { padding:10px 20px; border:none; border-radius:6px; cursor:pointer; transition:0.3s; }
.btn-submit { background:#28a745; color:white; }
.btn-submit:hover { background:#218838; }
.btn-back { background:#6c757d; color:white; margin-left:5px; }
.btn-back:hover { background:#5a6268; }
.notice { margin-bottom:15px; padding:10px; border-radius:6px; background:#ffeb3b; color:#333; font-weight:bold; }
</style>
</head>
<body>
<div class="container">
    <h2 class="mb-4 text-center">➕ Tambah Tryout Baru</h2>
    <?php if($notice): ?>
        <div class="notice"><?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label>Nama Tryout</label>
            <input type="text" name="nama_tryout" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Tanggal</label>
            <input type="date" name="tanggal" class="form-control" required>
        </div>
        <button type="submit" name="submit" class="btn btn-submit">Simpan</button>
        <a href="index_admin.php" class="btn btn-back">Kembali</a>
    </form>
</div>
</body>
</html>
