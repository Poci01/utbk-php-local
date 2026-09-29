<?php
session_start();
include 'koneksi.php';

if(!isset($_GET['id']) || !isset($_GET['fase'])) die("Data tidak valid!");
$tryout_id = intval($_GET['id']);
$current_fase = intval($_GET['fase']);

$tryout = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tryout WHERE id='$tryout_id'"));
if(!$tryout) die("Tryout tidak ditemukan!");

// ======================
// Konfigurasi Waktu Pause
// ======================
$pause_duration = 3; // <<<< Ubah ini kalau mau durasi jeda berbeda (menit)

// Inisialisasi waktu mulai pause
if(!isset($_SESSION['pause_start'][$tryout_id][$current_fase])){
    $_SESSION['pause_start'][$tryout_id][$current_fase] = time();
}
$start_time = $_SESSION['pause_start'][$tryout_id][$current_fase];

// Cari fase selanjutnya
$soal_query = mysqli_query($conn, "SELECT DISTINCT fase FROM soal WHERE tryout_id='$tryout_id' ORDER BY fase ASC");
$fases = [];
while($row=mysqli_fetch_assoc($soal_query)){ $fases[]=$row['fase']; }
$next_fase = null;
foreach($fases as $f){ if($f > $current_fase){ $next_fase=$f; break; } }

// Kalau tidak ada fase berikutnya → langsung ke selesai
$redirect_target = $next_fase ? "ujian.php?id=$tryout_id&fase=$next_fase" : "selesai.php?id=$tryout_id";
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pause - <?= htmlspecialchars($tryout['nama_tryout']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f7f7f7;}
.pause-box{
    max-width:600px;
    margin:auto;
    margin-top:80px;
    background:#fff;
    padding:30px;
    border-radius:12px;
    box-shadow:0 0 15px rgba(0,0,0,0.1);
    text-align:center;
}
#timer{font-size:1.5rem;font-weight:bold;color:#d9534f;}
.motivasi{
    font-style:italic;
    color:#444;
    margin-top:20px;
    font-size:1.1rem;
}
</style>
</head>
<body class="container py-4">

<div class="pause-box">
    <h3>Jeda Fase</h3>
    <div id="timer" class="mb-3"></div>
    <div class="motivasi">
        🌟 "Tetap semangat! Istirahat sebentar, lalu tunjukkan kemampuan terbaikmu!" 🌟
    </div>
    <div class="mt-4">
        <a href="<?= $redirect_target ?>" id="lanjutBtn" class="btn btn-success btn-lg">Lanjut Sekarang</a>
    </div>
</div>

<script>
let duration = <?= $pause_duration ?> * 60;
let start = <?= $start_time ?>;
let redirectTarget = "<?= $redirect_target ?>";

function countdown(){
    let now = Math.floor(Date.now()/1000);
    let elapsed = now - start;
    let remaining = duration - elapsed;

    if(remaining <= 0){
        window.location.href = redirectTarget;
        return;
    }

    let m = Math.floor(remaining / 60);
    let s = remaining % 60;
    document.getElementById('timer').innerText = `Sisa Waktu Jeda: ${m} menit ${s} detik`;
}
setInterval(countdown,1000);
countdown();
</script>

</body>
</html>
