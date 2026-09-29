<?php
session_start();
include 'koneksi.php';

if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

$notice = null;

// Pastikan ada parameter id
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    die("ID Tryout tidak valid.");
}

$id = intval($_GET['id']);

// Ambil data tryout
$stmt = $conn->prepare("SELECT * FROM tryout WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows !== 1){
    die("Tryout tidak ditemukan.");
}

$tryout = $res->fetch_assoc();

// Update tryout
if(isset($_POST['update'])){
    $nama_tryout = trim($_POST['nama_tryout']);
    $tanggal = trim($_POST['tanggal']);

    if(empty($nama_tryout) || empty($tanggal)){
        $notice = "Semua field harus diisi!";
    } else {
        $stmt2 = $conn->prepare("UPDATE tryout SET nama_tryout=?, tanggal=? WHERE id=?");
        $stmt2->bind_param("ssi", $nama_tryout, $tanggal, $id);
        if($stmt2->execute()){
            $notice = "Tryout berhasil diperbarui!";
            // refresh data
            $tryout['nama_tryout'] = $nama_tryout;
            $tryout['tanggal'] = $tanggal;
        } else {
            $notice = "Gagal memperbarui tryout: " . $stmt2->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Tryout</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Arial; background:#f7f7f7; }
.container { max-width: 500px; margin-top:50px; }
</style>
</head>
<body>
<div class="container">
    <div class="card shadow-sm p-4">
        <h3 class="mb-3 text-center">Edit Tryout</h3>

        <?php if($notice): ?>
            <div class="alert alert-info"><?= htmlspecialchars($notice) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label>Nama Tryout</label>
                <input type="text" name="nama_tryout" class="form-control" value="<?= htmlspecialchars($tryout['nama_tryout']) ?>" required>
            </div>
            <div class="mb-3">
                <label>Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($tryout['tanggal']) ?>" required>
            </div>
            <button type="submit" name="update" class="btn btn-primary w-100">Perbarui Tryout</button>
            <a href="index_admin.php" class="btn btn-secondary w-100 mt-2">Kembali</a>
        </form>
    </div>
</div>
</body>
</html>
