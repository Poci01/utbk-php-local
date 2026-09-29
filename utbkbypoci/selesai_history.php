<?php
session_start();
include 'koneksi.php';

// Cek apakah user sudah login dan ada hasil tryout
if(!isset($_SESSION['username']) || !isset($_SESSION['hasil_tryout'])){
    header("Location: index_user.php");
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

// Kalau user_id tidak ditemukan, paksa logout
if(!$user_id){
    session_destroy();
    header("Location: login.php");
    exit;
}

// Ambil data hasil tryout dari session
$hasil = $_SESSION['hasil_tryout'];
$tryout_name = $hasil['nama_tryout'];
$score = $hasil['total_nilai'];

// --- Mapping fase ke subtes ---
$faseToSubtes = [
    1 => 'Penalaran Umum',
    2 => 'Pengetahuan dan Pemahaman Umum',
    3 => 'Pemahaman Bacaan dan Menulis',
    4 => 'Pengetahuan Kuantitatif',
    5 => 'Literasi Bahasa Indonesia',
    6 => 'Literasi Bahasa Inggris',
    7 => 'Penalaran Matematika',
];

// Simpan ke tabel history (total nilai)
$stmt = $conn->prepare("INSERT INTO history (user_id, tryout_name, score, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("isi", $user_id, $tryout_name, $score);
$stmt->execute();

// Ambil ID history terakhir untuk relasi ke detail
$history_id = $stmt->insert_id;

// Simpan detail nilai per fase ke tabel history_detail
if(isset($hasil['detail']) && is_array($hasil['detail'])){
    $stmtDetail = $conn->prepare("INSERT INTO history_detail (history_id, fase, subtes, nilai) VALUES (?, ?, ?, ?)");
    foreach($hasil['detail'] as $fase => $nilai){
        $subtes = $faseToSubtes[$fase] ?? '';
        $stmtDetail->bind_param("iisi", $history_id, $fase, $subtes, $nilai);
        $stmtDetail->execute();
    }
}

// Bersihkan session hasil_tryout agar tidak double insert
unset($_SESSION['hasil_tryout']);

// Arahkan ke halaman histori
header("Location: history.php");
exit;
?>
