<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin'){
    header("Location: /login.php");
    exit;
}

include 'koneksi.php';

// Ambil semua tryout
$tryouts = mysqli_query($conn, "SELECT * FROM tryout ORDER BY tanggal ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel UTBK</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Arial; background:#f7f7f7; }
.container { margin-top:50px; }
h1 { color:#333; margin-bottom:30px; }
#tryout-table { margin:20px auto; border-collapse: collapse; width:90%; }
#tryout-table th, #tryout-table td { border:1px solid #ccc; padding:10px; text-align:center; }
#tryout-table th { background:#333; color:#fff; }
.btn { padding:8px 20px; border:none; border-radius:6px; cursor:pointer; transition:0.3s; margin-right:5px; }
.btn-add { background:#28a745; color:white; }
.btn-add:hover { background:#218838; }
.btn-edit { background:#007bff; color:white; }
.btn-edit:hover { background:#0069d9; }
.btn-delete { background:#dc3545; color:white; }
.btn-delete:hover { background:#c82333; }
.notice { margin-bottom:20px; padding:10px; border-radius:6px; background:#ffeb3b; color:#333; font-weight:bold; }
</style>
</head>
<body>
<div class="container">
<h1>📘 Admin Panel - Kelola Tryout</h1>

<?php if(isset($_GET['notice'])): ?>
    <div class="notice"><?= htmlspecialchars($_GET['notice']) ?></div>
<?php endif; ?>

<div class="mb-3">
    <a href="tambah_tryout.php" class="btn btn-add">➕ Tambah Tryout</a>
    <a href="tambah_soal.php" class="btn btn-add">➕ Tambah Soal Manual</a>
    <!-- Ditaruh di area tombol dashboard admin -->
<div class="d-flex gap-2 my-3">
    <a href="tambah_soal.php" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Tambah Soal Manual
    </a>
    
    <!-- TOMBOL AI YANG SUDAH AKTIF -->
    <a href="add_soal_ai.php" class="btn btn-success">
        <i class="fa-solid fa-robot"></i> Import Soal via AI (PDF)
    </a>

    <a href="kelola_soal.php" class="btn btn-secondary">
        <i class="fa-solid fa-list-check"></i> Kelola Soal
    </a>
</div>
    <a href="kelola_soal.php" class="btn btn-edit">📝 Kelola Soal</a>
    <a href="logout.php" class="btn btn-delete">🚪 Logout</a>
</div>

<table id="tryout-table">
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Tryout</th>
            <th>Tanggal</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php $i=1; while($row = mysqli_fetch_assoc($tryouts)) { ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['nama_tryout']) ?></td>
            <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
            <td>
                <a href="edit_tryout.php?id=<?= $row['id'] ?>" class="btn btn-edit">Edit</a>
                <a href="hapus_tryout.php?id=<?= $row['id'] ?>" class="btn btn-delete" onclick="return confirm('Yakin mau hapus tryout ini?')">Hapus</a>
            </td>
        </tr>
    <?php } ?>
    <?php if(mysqli_num_rows($tryouts) === 0): ?>
        <tr>
            <td colspan="4">Belum ada tryout.</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</body>
</html>
