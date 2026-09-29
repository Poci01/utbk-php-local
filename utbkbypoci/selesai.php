<?php
session_start();
include 'koneksi.php';

if(!isset($_GET['id'])){
    echo "Tryout tidak ditemukan!";
    exit;
}

$tryout_id = intval($_GET['id']);
$tryout = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tryout WHERE id='$tryout_id'"));
if(!$tryout){
    echo "Tryout tidak ditemukan!";
    exit;
}

// Ambil soal
$soal_query = mysqli_query($conn, "SELECT * FROM soal WHERE tryout_id='$tryout_id' ORDER BY fase ASC, urutan ASC");
$soal = [];
while($row = mysqli_fetch_assoc($soal_query)){
    $soal[$row['fase']][] = $row;
}

if(empty($soal)){
    echo "Soal belum tersedia!";
    exit;
}

// Ambil jawaban dari session
$jawaban_per_fase = $_SESSION['jawaban'][$tryout_id] ?? [];

// Fungsi skor sederhana
function irt_score($jawaban, $soal_fase){
    $theta = 0;
    $total = count($soal_fase);
    if($total==0) return 0;

    foreach($soal_fase as $i=>$s){
        $jawaban_soal = strtoupper(trim($jawaban[$i] ?? ''));
        $benar = strtoupper(trim($s['jawaban_benar'] ?? ''));
        if($jawaban_soal === $benar){
            $theta += 1;
        }
    }
    return round($theta/$total*100,0);
}

// Hitung hasil per fase
$hasil_fase = [];
foreach($soal as $fase => $soal_fase){
    $jawaban_fase = $jawaban_per_fase[$fase] ?? [];
    $total = count($soal_fase);
    $benar = 0;
    $salah = 0;
    $tidak_dijawab = [];

    foreach($soal_fase as $i => $s){
        $jawaban_soal = strtoupper(trim($jawaban_fase[$i] ?? ''));
        $jawaban_benar = strtoupper(trim($s['jawaban_benar'] ?? ''));
        if($jawaban_soal === ''){
            $tidak_dijawab[] = $i+1;
        } elseif($jawaban_soal === $jawaban_benar){
            $benar++;
        } else {
            $salah++;
        }
    }

    $nilai_irt = irt_score($jawaban_fase, $soal_fase);

    $hasil_fase[$fase] = [
        'total' => $total,
        'benar' => $benar,
        'salah' => $salah,
        'tidak_dijawab' => $tidak_dijawab,
        'nilai_irt' => $nilai_irt
    ];
}

// Total / rata-rata
$total_nilai = array_sum(array_column($hasil_fase,'nilai_irt'));
$rata_rata = count($hasil_fase) ? round($total_nilai / count($hasil_fase), 0) : 0;

// === Simpan ke session untuk dipakai selesai_history.php ===
$_SESSION['hasil_tryout'] = [
    'tryout_id'   => $tryout_id,
    'nama_tryout' => $tryout['nama_tryout'],
    'total_nilai' => $total_nilai,
    'rata_rata'   => $rata_rata,
    'detail'      => []   // simpan detail nilai tiap fase
];

// Simpan nilai per fase ke session (agar selesai_history.php bisa insert ke DB)
foreach($hasil_fase as $fase => $h){
    $_SESSION['hasil_tryout']['detail'][$fase] = $h['nilai_irt'];
}

// Hapus session jawaban agar bisa ulang
unset($_SESSION['jawaban'][$tryout_id]);
unset($_SESSION['ragu'][$tryout_id]);
unset($_SESSION['start_time'][$tryout_id]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hasil Tryout <?= htmlspecialchars($tryout['nama_tryout']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Hasil Tryout: <?= htmlspecialchars($tryout['nama_tryout']) ?></h2>

    <div class="row">
    <?php foreach($hasil_fase as $fase => $h): ?>
        <div class="col-md-4 mb-3">
            <div class="card p-3 shadow-sm">
                <h5 class="text-center">Fase <?= $fase ?></h5>
                <p>Benar: <?= $h['benar'] ?> / <?= $h['total'] ?></p>
                <p>Salah: <?= $h['salah'] ?></p>
                <p>Belum: <?= count($h['tidak_dijawab']) ? implode(', ',$h['tidak_dijawab']) : '-' ?></p>
                <div class="progress mb-2">
                    <div class="progress-bar bg-success" style="width:<?= $h['nilai_irt'] ?>%">
                        <?= $h['nilai_irt'] ?>%
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="card mt-4 p-3 shadow">
        <h4 class="text-center">Rekap Nilai</h4>
        <p class="text-center"><b>Total Skor:</b> <?= $total_nilai ?> | <b>Rata-rata:</b> <?= $rata_rata ?></p>
    </div>

    <div class="text-center mt-4">
        <a href="selesai_history.php" class="btn btn-info">Simpan ke Histori</a>
        <a href="index_user.php" class="btn btn-secondary">Kembali ke Menu</a>
    </div>
</div>
</body>
</html>
