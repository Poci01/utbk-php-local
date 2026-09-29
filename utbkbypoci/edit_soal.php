<?php
include 'koneksi.php';

// Ambil id soal
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = mysqli_query($conn, "SELECT * FROM soal WHERE id = $id");
    $soal = mysqli_fetch_assoc($query);
}

// Update soal
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pertanyaan = mysqli_real_escape_string($conn, $_POST['pertanyaan']);
    $opsi_a = mysqli_real_escape_string($conn, $_POST['opsi_a']);
    $opsi_b = mysqli_real_escape_string($conn, $_POST['opsi_b']);
    $opsi_c = mysqli_real_escape_string($conn, $_POST['opsi_c']);
    $opsi_d = mysqli_real_escape_string($conn, $_POST['opsi_d']);
    $jawaban = mysqli_real_escape_string($conn, $_POST['jawaban']);

    $update = "UPDATE soal SET pertanyaan='$pertanyaan', opsi_a='$opsi_a', opsi_b='$opsi_b', opsi_c='$opsi_c', opsi_d='$opsi_d', jawaban='$jawaban' WHERE id=$id";
    mysqli_query($conn, $update);

    header("Location: kelola_soal.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Soal</title>
</head>
<body>
    <h2>Edit Soal</h2>
    <form method="POST">
        <label>Pertanyaan:</label><br>
        <textarea name="pertanyaan" required><?= $soal['pertanyaan'] ?></textarea><br><br>

        <label>Opsi A:</label><br>
        <input type="text" name="opsi_a" value="<?= $soal['opsi_a'] ?>" required><br><br>

        <label>Opsi B:</label><br>
        <input type="text" name="opsi_b" value="<?= $soal['opsi_b'] ?>" required><br><br>

        <label>Opsi C:</label><br>
        <input type="text" name="opsi_c" value="<?= $soal['opsi_c'] ?>" required><br><br>

        <label>Opsi D:</label><br>
        <input type="text" name="opsi_d" value="<?= $soal['opsi_d'] ?>" required><br><br>

        <label>Jawaban Benar:</label><br>
        <select name="jawaban" required>
            <option value="A" <?= ($soal['jawaban']=='A')?'selected':'' ?>>A</option>
            <option value="B" <?= ($soal['jawaban']=='B')?'selected':'' ?>>B</option>
            <option value="C" <?= ($soal['jawaban']=='C')?'selected':'' ?>>C</option>
            <option value="D" <?= ($soal['jawaban']=='D')?'selected':'' ?>>D</option>
        </select><br><br>

        <button type="submit">Update</button>
    </form>
</body>
</html>
