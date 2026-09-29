<?php
session_start();
include 'koneksi.php';

// Pastikan user login
if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'user'){
    header("Location: /login.php");
    exit;
}

// Ambil user_id dari tabel user berdasarkan username
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM user WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$user_id = $user['id'] ?? 0;

// Kalau user_id tidak ditemukan, kembalikan ke login
if(!$user_id){
    header("Location: /login.php");
    exit;
}

// Ambil histori dari database
$stmt = $conn->prepare("SELECT id, tryout_name, score, created_at FROM history WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Histori Tryout</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4 text-center">Histori Tryout Anda</h2>

    <?php if($result->num_rows > 0): ?>
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Nama Tryout</th>
                <th>Skor</th>
                <th>Tanggal</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php $i=1; while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['tryout_name']) ?></td>
                <td><?= number_format($row['score'], 2) ?></td>
                <td><?= date("d M Y H:i", strtotime($row['created_at'])) ?></td>
                <td>
                    <a href="history_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Lihat Detail</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="text-center">Belum ada histori tryout.</p>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="index_user.php" class="btn btn-secondary">Kembali ke Menu</a>
    </div>
</div>
</body>
</html>
