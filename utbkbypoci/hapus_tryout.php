<?php
session_start();
include 'koneksi.php';

// Cek admin
if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

// Pastikan ada parameter id
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    die("ID Tryout tidak valid.");
}

$id = intval($_GET['id']);

// Hapus tryout
$stmt = $conn->prepare("DELETE FROM tryout WHERE id=?");
$stmt->bind_param("i", $id);

if($stmt->execute()){
    header("Location: index_admin.php?notice=Tryout berhasil dihapus!");
    exit;
} else {
    die("Gagal menghapus tryout: " . $stmt->error);
}
?>
