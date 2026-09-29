<?php
session_start();
if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'user'){
    header("Location: /login.php");
    exit;
}
include 'koneksi.php';

$tryouts = mysqli_query($conn, "SELECT * FROM tryout ORDER BY tanggal ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WEB UTBK BY POCI - User</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Arial; text-align: center; background: #f7f7f7; margin:0; padding:0; }
.container { margin-top:50px; max-width: 900px; }
h1 { color:#333; }
.btn { padding:10px 25px; background:#4CAF50; color:white; border:none; border-radius:8px; cursor:pointer; font-size:16px; transition:0.3s; text-decoration:none; }
.btn:hover { background:#45a049; color:white; }
.logout-btn { background-color:#e74c3c; }
.logout-btn:hover { background-color:#c0392b; }
.btn-secondary { background:#3498db; }
.btn-secondary:hover { background:#2980b9; }
#tryout-table { display:none; margin:20px auto; border-collapse: collapse; width:100%; }
#tryout-table th, #tryout-table td { border:1px solid #ccc; padding:10px; }
#tryout-table th { background:#eee; }
</style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-end mb-3">
        <a href="logout.php" class="btn logout-btn">Logout</a>
    </div>

    <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['username']) ?></h1>
    <p>Latihan buat UTBK cugs.</p>

    <!-- Tombol mulai -->
    <button id="startBtn" class="btn">Mulai Ujian</button>
    <br><br>

    <!-- Tombol histori -->
    <a href="history.php" class="btn btn-secondary">Lihat Histori</a>
    <br><br>

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
                <td><a href="ujian.php?id=<?= $row['id'] ?>" class="btn">Mulai</a></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<script>
// saat tombol Mulai diklik, tampilkan tabel
document.getElementById('startBtn').addEventListener('click', function() {
    document.getElementById('tryout-table').style.display = 'table';
    this.style.display = 'none'; // sembunyikan tombol Mulai utama
});
</script>
</body>
</html>
