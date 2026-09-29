<?php
session_start();
include 'koneksi.php';

// Fungsi untuk membaca file .env secara manual tanpa dependensi eksternal
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as$line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=',$line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        putenv(sprintf('%s=%s', $name, $value));$_ENV[$name] =$value;
    }
}

// Membaca file .env yang berada satu level di luar folder utbkbypoci
loadEnv(__DIR__ . '/../.env');

// Cek akses admin
if (!isset($_SESSION['username']) \vert{}\vert{}$_SESSION['role'] !== 'admin') {
    die("<div style='color:red; padding:20px; font-family:sans-serif;'>Akses ditolak. Silakan login sebagai admin terlebih dahulu.</div>");
}

$tryout_id = isset($_GET['tryout']) ? intval($_GET['tryout']) : (isset($_POST['tryout_id']) ? intval($_POST['tryout_id']) : 0);

// Mapping Subtes berdasarkan Fase
$faseToSubtes = [
    1 => 'Penalaran Umum',
    2 => 'Pengetahuan dan Pemahaman Umum',
    3 => 'Pemahaman Bacaan dan Menulis',
    4 => 'Pengetahuan Kuantitatif',
    5 => 'Literasi Bahasa Indonesia',
    6 => 'Literasi Bahasa Inggris',
    7 => 'Penalaran Matematika',
];

$errors = [];$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {$tryout_id = intval($_POST['tryout_id'] ?? 0);$fase      = intval($_POST['fase'] ?? 1);$subtes    = $faseToSubtes[$fase] ?? 'Penalaran Umum';

    if (!$tryout_id) {$errors[] = "Silakan pilih Target Tryout terlebih dahulu!";
    } else if ($_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {$errors[] = "Gagal mengunggah file PDF.";
    } else {
        $pdfPath =$_FILES['pdf_file']['tmp_name'];
        $pdfData = base64_encode(file_get_contents($pdfPath));

        // Mengambil API Key dari environment variable .env
        $apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');

        if ($apiKey === "" || empty($apiKey)) {$errors[] = "API Key Gemini belum diatur di file .env!";
        } else {
            $prompt = "Kamu adalah AI pemroses dokumen soal ujian UTBK/SNBT. Ekstrak seluruh soal pilihan ganda dari file PDF ini. "
                    . "Kembalikan hasilnya HANYA dalam format JSON Array murni tanpa format markdown (seperti ```json). "
                    . "Format JSON untuk setiap soal:\n"
                    . "[\n"
                    . "  {\n"
                    . "    \"urutan\": 1,\n"
                    . "    \"pertanyaan\": \"isi pertanyaan\",\n"
                    . "    \"opsi_a\": \"isi opsi a\",\n"
                    . "    \"opsi_b\": \"isi opsi b\",\n"
                    . "    \"opsi_c\": \"isi opsi c\",\n"
                    . "    \"opsi_d\": \"isi opsi d\",\n"
                    . "    \"opsi_e\": \"isi opsi e\",\n"
                    . "    \"jawaban_benar\": \"A\"\n"
                    . "  }\n"
                    . "]\n"
                    . "Catatan: jawaban_benar harus satu huruf (A/B/C/D/E). Jika tidak ada di PDF, tebak opsi yang paling tepat.";

            $payload = [
                "contents" => [
                    [
                        "parts" => [
                            ["text" => $prompt],
                            [
                                "inline_data" => [
                                    "mime_type" => "application/pdf",
                                    "data"      => $pdfData
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $ch = curl_init("[https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=](https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=)" . $apiKey);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            
            $response = curl_exec($ch);
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpStatus !== 200) {
                $errors[] = "Gagal menghubungi AI API (Status $httpStatus). Pastikan API Key valid dan koneksi internet aktif.";
            } else {
                $resData = json_decode($response, true);
                $rawText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                $cleanJson = trim(preg_replace('/^```json|```$/m', '', $rawText));
                $parsed = json_decode($cleanJson, true);

                if (!is_array($parsed)) {
                    $errors[] = "Gagal memproses struktur JSON dari AI. Pastikan file PDF berisi soal pilihan ganda.";
                } else {
                    $inserted = 0;
                    $stmt = $conn->prepare("INSERT INTO soal (tryout_id, fase, subtes, urutan, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, jawaban_benar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    foreach ($parsed as $q) {
                        $pertanyaan = trim($q['pertanyaan'] ?? '');
                        $opsi_a     = trim($q['opsi_a'] ?? '');
                        $opsi_b     = trim($q['opsi_b'] ?? '');
                        $opsi_c     = trim($q['opsi_c'] ?? '');
                        $opsi_d     = trim($q['opsi_d'] ?? '');
                        $opsi_e     = trim($q['opsi_e'] ?? '');
                        $jawaban    = strtoupper(trim($q['jawaban_benar'] ?? 'A'));
                        $urutan     = intval($q['urutan'] ?? ($inserted + 1));

                        if (!empty($pertanyaan)) {
                            $stmt->bind_param("iisisssssss", $tryout_id, $fase, $subtes, $urutan, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e, $jawaban);
                            if ($stmt->execute()) {
                                $inserted++;
                            }
                        }
                    }
                    $stmt->close();

                    if ($inserted > 0) {
                        $success[] = "Berhasil mengimpor $inserted soal secara otomatis ke Database!";
                    } else {
                        $errors[] = "Tidak ada soal yang berhasil disimpan.";
                    }
                }
            }
        }
    }
}

// Ambil list Tryout untuk pilihan dropdown
$tryout_query = mysqli_query($conn, "SELECT id, nama_tryout FROM tryout ORDER BY id DESC");
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Import Soal AI - Admin UTBK</title>
    <link href="[https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css](https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css)" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width: 650px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white p-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">🤖 Auto Import Soal PDF via AI</h5>
            <a href="index_admin.php" class="btn btn-sm btn-light">Dashboard</a>
        </div>
        <div class="card-body p-4">

            <?php if(!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ms-3"><?php foreach($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?></ul>
                </div>
            <?php endif; ?>

            <?php if(!empty($success)): ?>
                <div class="alert alert-success">
                    <ul class="mb-0 ms-3"><?php foreach($success as $s) echo "<li>" . htmlspecialchars($s) . "</li>"; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-bold">1. Pilih Target Tryout:</label>
                    <select name="tryout_id" class="form-select" required>
                        <option value="">-- Pilih Tryout --</option>
                        <?php while($t = mysqli_fetch_assoc($tryout_query)): ?>
                            <option value="<?= $t['id'] ?>" <?= ($tryout_id == $t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nama_tryout']) ?> (ID: <?= $t['id'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">2. Pilih Fase & Subtes:</label>
                    <select name="fase" class="form-select" required>
                        <?php foreach($faseToSubtes as $f => $s): ?>
                            <option value="<?= $f ?>">Fase <?= $f ?> - <?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">3. Upload File PDF Soal:</label>
                    <input type="file" name="pdf_file" accept="application/pdf" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
                    Proses & Import Otomatis
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>