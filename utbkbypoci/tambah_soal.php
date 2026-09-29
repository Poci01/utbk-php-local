<?php
include 'koneksi.php';
session_start();

// --- Konfigurasi fase ke subtes ---
$faseToSubtes = [
    1 => 'Penalaran Umum',
    2 => 'Pengetahuan dan Pemahaman Umum',
    3 => 'Pemahaman Bacaan dan Menulis',
    4 => 'Pengetahuan Kuantitatif',
    5 => 'Literasi Bahasa Indonesia',
    6 => 'Literasi Bahasa Inggris',
    7 => 'Penalaran Matematika',
];

// --- Ambil daftar tryout ---
$tryout_result = mysqli_query($conn, "SELECT id, nama_tryout, tanggal FROM tryout ORDER BY tanggal DESC, id DESC");

// --- Proses submit ---
$notif = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil & sanitasi input
    $pertanyaan     = trim($_POST['pertanyaan'] ?? '');
    $opsi_a         = trim($_POST['opsi_a'] ?? '');
    $opsi_b         = trim($_POST['opsi_b'] ?? '');
    $opsi_c         = trim($_POST['opsi_c'] ?? '');
    $opsi_d         = trim($_POST['opsi_d'] ?? '');
    $opsi_e         = trim($_POST['opsi_e'] ?? '');
    $jawaban_benar  = strtoupper(trim($_POST['jawaban_benar'] ?? ''));
    $tryout_id      = (int)($_POST['tryout_id'] ?? 0);
    $fase           = (int)($_POST['fase'] ?? 0);
    $urutan         = (int)($_POST['urutan'] ?? 0);

    // Validasi dasar
    if (!$tryout_id || !$fase || !$urutan || $pertanyaan === '' || $opsi_a === '' || $opsi_b === '' || $opsi_c === '' || $opsi_d === '' || $opsi_e === '') {
        $notif = "<p style='color:red;'>Harap lengkapi semua field wajib.</p>";
    } elseif (!in_array($jawaban_benar, ['A','B','C','D','E'], true)) {
        $notif = "<p style='color:red;'>Jawaban benar harus salah satu dari A/B/C/D/E.</p>";
    } elseif (!isset($faseToSubtes[$fase])) {
        $notif = "<p style='color:red;'>Fase tidak valid (harus 1–7).</p>";
    } else {

        // Tentukan subtes dari fase
        $subtes = $faseToSubtes[$fase];

        // Cek duplikasi urutan per (tryout_id, fase)
        $cekStmt = $conn->prepare("SELECT COUNT(*) FROM soal WHERE tryout_id=? AND fase=? AND urutan=?");
        $cekStmt->bind_param("iii", $tryout_id, $fase, $urutan);
        $cekStmt->execute();
        $cekStmt->bind_result($cntDupe);
        $cekStmt->fetch();
        $cekStmt->close();

        if ($cntDupe > 0) {
            $notif = "<p style='color:red;'>Nomor urutan $urutan untuk fase $fase pada tryout ini sudah dipakai.</p>";
        } else {

            // Handle upload gambar (opsional)
            $gambar = "";
            if (!empty($_FILES["gambar"]["name"])) {
                $targetDir = __DIR__ . "/image/soal/"; 
                if (!is_dir($targetDir)) { @mkdir($targetDir, 0775, true); }

                $origName   = basename($_FILES["gambar"]["name"]);
                $ext        = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $allowTypes = ['jpg','jpeg','png','gif','webp'];
                $maxSize    = 2 * 1024 * 1024; // 2MB

                if (!in_array($ext, $allowTypes, true)) {
                    $notif = "<p style='color:red;'>Format file tidak valid. (Hanya jpg, jpeg, png, gif, webp)</p>";
                } elseif ($_FILES["gambar"]["size"] > $maxSize) {
                    $notif = "<p style='color:red;'>Ukuran file terlalu besar (maks 2MB).</p>";
                } else {
                    $safeBase  = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($origName, PATHINFO_FILENAME));
                    $fileName  = time() . "_" . $safeBase . "." . $ext;
                    $targetAbs = $targetDir . $fileName;
                    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetAbs)) {
                        $gambar = $fileName;
                    } else {
                        $notif = "<p style='color:red;'>Gagal upload gambar.</p>";
                    }
                }
            }

            // Jika tidak ada error upload, lanjut insert
            if ($notif === '') {
                $sql = "INSERT INTO soal 
                    (pertanyaan, gambar, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, jawaban_benar, tryout_id, fase, subtes, urutan)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    $notif = "<p style='color:red;'>Error prepare: ". htmlspecialchars($conn->error) ."</p>";
                } else {
                    $stmt->bind_param(
                        "ssssssssiisi",
                        $pertanyaan, $gambar, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e, $jawaban_benar,
                        $tryout_id, $fase, $subtes, $urutan
                    );

                    if ($stmt->execute()) {
                        $notif = "<p style='color:green;'>Soal berhasil ditambahkan!</p>";
                    } else {
                        $notif = "<p style='color:red;'>Error insert: ". htmlspecialchars($stmt->error) ."</p>";
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tambah Soal</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f7f7f7; }
        form { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 5px #aaa; max-width: 700px; margin: auto; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type=text], textarea, select, input[type=file], input[type=number] {
            width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px;
        }
        button { margin-top: 15px; padding: 10px 15px; background: #28a745; border: none; color: white; border-radius: 5px; cursor: pointer; }
        button:hover { background: #218838; }
        .hint { color:#666; font-size:12px; margin-top:4px;}
    </style>
</head>
<body>

<h2 style="text-align:center;">Tambah Soal Tryout</h2>

<?php if (!empty($notif)) { echo $notif; } ?>

<form method="POST" enctype="multipart/form-data">
    <label>Pilih Tryout:</label>
    <select name="tryout_id" required>
        <option value="">-- Pilih Tryout --</option>
        <?php if ($tryout_result && mysqli_num_rows($tryout_result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($tryout_result)) { ?>
                <option value="<?= (int)$row['id'] ?>">
                    <?= htmlspecialchars($row['nama_tryout']) ?> (<?= htmlspecialchars($row['tanggal']) ?>)
                </option>
            <?php } ?>
        <?php else: ?>
            <option value="">(Belum ada tryout)</option>
        <?php endif; ?>
    </select>

    <label>Fase (1-7):</label>
    <input type="number" name="fase" min="1" max="7" required>
    <div class="hint">Subtes akan terisi otomatis sesuai fase.</div>

    <label>Urutan Soal (angka):</label>
    <input type="number" name="urutan" min="1" required>

    <label>Pertanyaan:</label>
    <textarea name="pertanyaan" rows="4" required></textarea>

    <label>Gambar (opsional):</label>
    <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.gif,.webp">

    <label>Opsi A:</label>
    <input type="text" name="opsi_a" required>

    <label>Opsi B:</label>
    <input type="text" name="opsi_b" required>

    <label>Opsi C:</label>
    <input type="text" name="opsi_c" required>

    <label>Opsi D:</label>
    <input type="text" name="opsi_d" required>

    <label>Opsi E:</label>
    <input type="text" name="opsi_e" required>

    <label>Jawaban Benar:</label>
    <select name="jawaban_benar" required>
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
        <option value="D">D</option>
        <option value="E">E</option>
    </select>

    <button type="submit">Simpan Soal</button>
</form>

</body>
</html>
