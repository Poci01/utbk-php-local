<?php
session_start();
include 'koneksi.php';

// Pastikan user login
if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'user'){
    header("Location: /login.php");
    exit;
}

$history_id = $_GET['id'] ?? 0;
if(!$history_id){
    header("Location: history.php");
    exit;
}

// --- Mapping fase ke nama subtes ---
$faseToSubtes = [
    1 => 'Penalaran Umum',
    2 => 'Pengetahuan dan Pemahaman Umum',
    3 => 'Pemahaman Bacaan dan Menulis',
    4 => 'Pengetahuan Kuantitatif',
    5 => 'Literasi Bahasa Indonesia',
    6 => 'Literasi Bahasa Inggris',
    7 => 'Penalaran Matematika',
];

// Ambil data history utama
$stmt = $conn->prepare("SELECT tryout_name, score, created_at FROM history WHERE id=?");
$stmt->bind_param("i", $history_id);
$stmt->execute();
$history = $stmt->get_result()->fetch_assoc();

// Ambil detail fase (dengan subtes)
$stmtDetail = $conn->prepare("SELECT fase, nilai FROM history_detail WHERE history_id=? ORDER BY fase ASC");
$stmtDetail->bind_param("i", $history_id);
$stmtDetail->execute();
$detail = $stmtDetail->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Histori</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4 text-center">Detail Tryout: <?= htmlspecialchars($history['tryout_name']) ?></h2>
    <p class="text-center">Total Skor: <strong><?= number_format($history['score'],2) ?></strong></p>
    <p class="text-center">Tanggal: <?= date("d M Y H:i", strtotime($history['created_at'])) ?></p>

    <?php if($detail->num_rows > 0): ?>
    <table class="table table-bordered mt-4">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Fase</th>
                <th>Subtes</th>
                <th>Nilai</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $i=1; 
        $totalNilai = 0; 
        $jmlData = 0; 
        while($row = $detail->fetch_assoc()): 
            $totalNilai += $row['nilai'];
            $jmlData++;
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['fase']) ?> - <?= $faseToSubtes[$row['fase']] ?? 'Tidak diketahui' ?></td>
                <td><?= htmlspecialchars($faseToSubtes[$row['fase']] ?? '-') ?></td>
                <td><?= number_format($row['nilai'],2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php if($jmlData > 0): ?>
        <p class="text-center mt-3">
            Rata-rata Nilai: <strong><?= number_format($totalNilai / $jmlData, 2) ?></strong>
        </p>
    <?php endif; ?>

    <?php else: ?>
        <p class="text-center text-muted">Belum ada detail nilai untuk tryout ini.</p>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="history.php" class="btn btn-secondary">Kembali ke Histori</a>
    </div>
</div>
</body>
</html>
