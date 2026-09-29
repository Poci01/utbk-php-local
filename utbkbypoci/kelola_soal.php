<?php
include 'koneksi.php';

// Handle hapus soal langsung di sini
if (isset($_POST['hapus_id'])) {
    $hapus_id = intval($_POST['hapus_id']);
    mysqli_query($conn, "DELETE FROM soal WHERE id = '$hapus_id'");
    header("Location: kelola_soal.php");
    exit;
}

// Ambil filter dari request
$search = $_GET['search'] ?? '';
$fase   = $_GET['fase'] ?? '';
$subtes = $_GET['subtes'] ?? '';

// Query dasar
$query = "SELECT * FROM soal WHERE 1=1";

// Jika ada pencarian
if ($search != '') {
    $query .= " AND pertanyaan LIKE '%$search%'";
}

// Jika filter fase
if ($fase != '') {
    $query .= " AND fase = '$fase'";
}

// Jika filter subtes
if ($subtes != '') {
    $query .= " AND subtes = '$subtes'";
}

$query .= " ORDER BY id DESC";
$result = mysqli_query($conn, $query);

// List subtes (sesuai kategori yang kamu pakai)
$list_subtes = [
    "Penalaran Umum",
    "Pengetahuan dan Pemahaman Umum",
    "Pemahaman Bacaan dan Menulis",
    "Pengetahuan Kuantitatif",
    "Literasi Bahasa Indonesia",
    "Literasi Bahasa Inggris",
    "Penalaran Matematika"
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kelola Soal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">Kelola Soal</h2>
    <!-- TOMBOL IMPORT SOAL VIA AI (PDF) -->
<?php $current_tryout = $_GET['tryout_id'] ?? $_GET['id'] ?? ''; ?>

<div class="mb-3">
    <!-- TOMBOL AI DENGAN PARAMETER TRYOUT OLEH SISTEM -->
    <a href="add_soal_ai.php?tryout=<?= htmlspecialchars($current_tryout) ?>" class="btn btn-success btn-sm">
        <i class="fa-solid fa-robot"></i> Import PDF Soal (AI)
    </a>
</div>
    <a href="index_admin.php" class="btn btn-secondary">← Kembali</a>
  </div>

  <!-- Filter & Search -->
  <form method="GET" class="card p-3 mb-4 shadow-sm">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Cari Pertanyaan</label>
        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan kata kunci...">
      </div>
      <div class="col-md-3">
        <label class="form-label">Fase</label>
        <select name="fase" class="form-select">
          <option value="">Semua</option>
          <?php for ($i=1; $i<=7; $i++): ?>
            <option value="<?= $i ?>" <?= $fase==$i?'selected':'' ?>>Fase <?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Subtes</label>
        <select name="subtes" class="form-select">
          <option value="">Semua</option>
          <?php foreach($list_subtes as $s): ?>
            <option value="<?= $s ?>" <?= $subtes==$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Filter</button>
      </div>
    </div>
  </form>

  <!-- Tabel Soal -->
  <div class="card shadow-sm">
    <div class="card-body">
      <table class="table table-hover">
        <thead class="table-dark">
          <tr>
            <th>No</th>
            <th>Pertanyaan</th>
            <th>Fase</th>
            <th>Subtes</th>
            <th>Urutan</th>
            <th>Opsi A</th>
            <th>Opsi B</th>
            <th>Opsi C</th>
            <th>Opsi D</th>
            <th>Kunci</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no=1; while($row = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= $row['pertanyaan'] ?></td>
            <td><?= $row['fase'] ?></td>
            <td><?= $row['subtes'] ?></td>
            <td><?= $row['urutan'] ?></td>
            <td><?= $row['opsi_a'] ?></td>
            <td><?= $row['opsi_b'] ?></td>
            <td><?= $row['opsi_c'] ?></td>
            <td><?= $row['opsi_d'] ?></td>
            <td><span class="badge bg-success"><?= strtoupper($row['jawaban_benar']) ?></span></td>
            <td>
              <!-- Tombol Edit -->
              <button class="btn btn-sm btn-warning" 
                      data-bs-toggle="modal" 
                      data-bs-target="#editSoalModal<?= $row['id'] ?>">
                Edit
              </button>

              <!-- Tombol Hapus (modal) -->
              <button class="btn btn-sm btn-danger" 
                      data-bs-toggle="modal" 
                      data-bs-target="#hapusSoalModal<?= $row['id'] ?>">
                Hapus
              </button>
            </td>
          </tr>

          <!-- Modal Edit Soal -->
          <div class="modal fade" id="editSoalModal<?= $row['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <form method="POST" action="edit_soal.php">
                  <div class="modal-header">
                    <h5 class="modal-title">Edit Soal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    
                    <div class="mb-3">
                      <label class="form-label">Pertanyaan</label>
                      <textarea name="pertanyaan" class="form-control" required><?= $row['pertanyaan'] ?></textarea>
                    </div>

                    <div class="row g-2">
                      <div class="col-md-6">
                        <label class="form-label">Opsi A</label>
                        <input type="text" name="opsi_a" value="<?= $row['opsi_a'] ?>" class="form-control" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Opsi B</label>
                        <input type="text" name="opsi_b" value="<?= $row['opsi_b'] ?>" class="form-control" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Opsi C</label>
                        <input type="text" name="opsi_c" value="<?= $row['opsi_c'] ?>" class="form-control" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Opsi D</label>
                        <input type="text" name="opsi_d" value="<?= $row['opsi_d'] ?>" class="form-control" required>
                      </div>
                    </div>

                    <div class="row g-2 mt-3">
                      <div class="col-md-4">
                        <label class="form-label">Fase</label>
                        <input type="number" name="fase" value="<?= $row['fase'] ?>" class="form-control" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Subtes</label>
                        <select name="subtes" class="form-select" required>
                          <?php foreach($list_subtes as $s): ?>
                            <option value="<?= $s ?>" <?= $row['subtes']==$s?'selected':'' ?>><?= $s ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Urutan</label>
                        <input type="number" name="urutan" value="<?= $row['urutan'] ?>" class="form-control" required>
                      </div>
                    </div>

                    <div class="mt-3">
                      <label class="form-label">Jawaban Benar</label>
                      <select name="jawaban_benar" class="form-select" required>
                        <option value="a" <?= $row['jawaban_benar']=='a'?'selected':'' ?>>A</option>
                        <option value="b" <?= $row['jawaban_benar']=='b'?'selected':'' ?>>B</option>
                        <option value="c" <?= $row['jawaban_benar']=='c'?'selected':'' ?>>C</option>
                        <option value="d" <?= $row['jawaban_benar']=='d'?'selected':'' ?>>D</option>
                        <option value="e" <?= $row['jawaban_benar']=='e'?'selected':'' ?>>E</option>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- Modal Hapus Soal -->
          <div class="modal fade" id="hapusSoalModal<?= $row['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="POST">
                  <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p>Apakah kamu yakin ingin menghapus soal ini?</p>
                    <p><strong><?= htmlspecialchars($row['pertanyaan']) ?></strong></p>
                    <input type="hidden" name="hapus_id" value="<?= $row['id'] ?>">
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</body>
</html>
