<?php
session_start();
include 'koneksi.php';

if(!isset($_GET['id'])){
    echo "Tryout tidak ditemukan!";
    exit;
}

$tryout_id = intval($_GET['id']);

// Ambil soal dari database
$soal_query = mysqli_query($conn, "SELECT * FROM soal WHERE tryout_id='$tryout_id' ORDER BY id ASC");
$soal = [];
while($row = mysqli_fetch_assoc($soal_query)){
    $soal[] = $row;
}

// Ambil jawaban dari session
$jawaban_session = $_SESSION['jawaban'][$tryout_id] ?? [];

// Tampilkan semua
echo "<h2>DEBUG TRYOUT ID: $tryout_id</h2>";

// Jawaban user yang dikirim POST (jika ada)
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    echo "<h3>Jawaban dari form POST</h3>";
    echo "<pre>";
    print_r($_POST['jawaban'] ?? []);
    echo "</pre>";
}

// Jawaban di session
echo "<h3>Jawaban di SESSION</h3>";
echo "<pre>";
print_r($jawaban_session);
echo "</pre>";

// Jawaban benar di database
echo "<h3>Jawaban BENAR dari database</h3>";
$db_jawaban = [];
foreach($soal as $i => $s){
    $db_jawaban[$i] = strtoupper(trim($s['jawaban_benar'] ?? ''));
}
echo "<pre>";
print_r($db_jawaban);
echo "</pre>";

// Cocokkan jawaban user vs database
echo "<h3>Perbandingan</h3>";
foreach($soal as $i => $s){
    $user = strtoupper(trim($jawaban_session[$i] ?? ''));
    $benar = strtoupper(trim($s['jawaban'] ?? ''));
    echo "Soal ".($i+1).": User = '$user', Benar = '$benar' -> ".($user === $benar ? "BENAR" : ($user==''?"KOSONG":"SALAH"))."<br>";
}
?>
<hr>
<form method="POST">
<button type="submit" name="refresh">Refresh Debug</button>
</form>
