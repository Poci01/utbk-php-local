<?php
session_start();
include 'koneksi.php';

if(!isset($_GET['id'])) die("Tryout tidak ditemukan!");
$tryout_id = intval($_GET['id']);
$tryout = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tryout WHERE id='$tryout_id'"));
if(!$tryout) die("Tryout tidak ditemukan!");

// Durasi tiap fase (menit)
$phase_durations = [1=>20,2=>20,3=>20,4=>20,5=>20,6=>20,7=>20];

// Ambil semua soal per fase
$soal_query = mysqli_query($conn, "SELECT * FROM soal WHERE tryout_id='$tryout_id' ORDER BY fase ASC, urutan ASC");
$soal = [];
while($row = mysqli_fetch_assoc($soal_query)){
    $soal[$row['fase']][] = $row;
}

if(empty($soal)) die("Soal untuk tryout ini belum tersedia!");

// Inisialisasi jawaban & ragu-ragu
if(!isset($_SESSION['jawaban'][$tryout_id])) $_SESSION['jawaban'][$tryout_id] = [];
if(!isset($_SESSION['ragu'][$tryout_id])) $_SESSION['ragu'][$tryout_id] = [];

// Fase aktif
$current_fase = isset($_GET['fase']) ? intval($_GET['fase']) : min(array_keys($soal));
if(!isset($soal[$current_fase])) die("Fase tidak tersedia!");
$total_soal = count($soal[$current_fase]);

// Inisialisasi waktu mulai fase ini jika belum ada
if(!isset($_SESSION['start_time'][$tryout_id])) $_SESSION['start_time'][$tryout_id] = [];
if(!isset($_SESSION['start_time'][$tryout_id][$current_fase])){
    $_SESSION['start_time'][$tryout_id][$current_fase] = time();
}
$start_time = $_SESSION['start_time'][$tryout_id][$current_fase];
$duration = $phase_durations[$current_fase];

// Hitung fase berikutnya
$available_fases = array_keys($soal);
$next_fase = null;
foreach($available_fases as $f){
    if($f > $current_fase){ $next_fase = $f; break; }
}

// Submit jawaban
if(isset($_POST['submit'])){
    foreach($_POST['jawaban'] as $fase => $jawaban_fase){
        foreach($jawaban_fase as $i => $val){
            $_SESSION['jawaban'][$tryout_id][$fase][$i] = $val;
        }
    }
    $_SESSION['ragu'][$tryout_id] = $_POST['ragu'] ?? [];

    // Redirect ke pause.php untuk jeda
    header("Location: pause.php?id=$tryout_id&fase=$current_fase");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ujian <?= htmlspecialchars($tryout['nama_tryout']) ?> - Fase <?= $current_fase ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f7f7f7;}
.soal{display:none;}
.soal.active{display:block;}

/* ===== Navigator Sidebar ===== */
.navigator-sidebar{
    position:absolute;
    top:75px;
    right:10px;
    width:220px;
    background:#fff;
    padding:10px;
    box-shadow:-2px 0 5px rgba(0,0,0,0.2);
    max-height:calc(100% - 100px);
    overflow-y:auto;
    border-radius:12px;
    display:none; /* default tertutup */
    z-index:1050;
}
.navigator-sidebar .back-btn{
    position:absolute;
    top:5px;
    left:5px;
    background:none;
    border:none;
    font-size:20px;
    cursor:pointer;
}
.navigator-grid{
    display:grid;
    grid-template-columns:repeat(5, 1fr);
    gap:5px;
    margin-top:40px; /* beri jarak karena ada tombol back */
}
.navigator-grid button{
    min-width:35px;
    border-radius:10px; /* agak bulat */
}
.navigator-grid button.num-blank{background:#ddd;}
.navigator-grid button.num-answered{background:#4caf50;color:white;}
.navigator-grid button.num-ragu{background:#ffc107;}
.toggle-btn{
    position:absolute;
    top:75px;
    right:10px;
    z-index:1100;
}

/* ===== Modal Konfirmasi ===== */
#confirmModal{
    display:none;
    position:fixed;
    top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.5);
    justify-content:center;align-items:center;
    z-index:2000;
}
#confirmModal .modal-content{
    background:#fff;
    padding:20px;
    border-radius:8px;
    max-width:400px;
    text-align:center;
}
#confirmModal button{margin:5px;}
</style>
</head>
<body class="container py-4">

<h2 class="mb-4 text-center">Ujian: <?= htmlspecialchars($tryout['nama_tryout']) ?></h2>
<div id="fase-info" class="text-center mb-3 fw-bold">
Tes <?= $current_fase<=4?'Potensi Skolastik':'Literasi' ?> - Subtes: <?= htmlspecialchars($soal[$current_fase][0]['subtes']) ?>
</div>
<div id="timer" class="text-center mb-3 text-danger"></div>

<button class="btn btn-primary toggle-btn" id="toggleNavigator">☰ Navigasi Soal</button>

<div class="navigator-sidebar" id="navigatorSidebar">
    <button class="back-btn" id="closeNavigator">⬅</button>
    <h5 class="text-center">Navigator Soal</h5>
    <div class="navigator-grid">
        <?php for($i=0;$i<$total_soal;$i++):
            $statusClass="num-blank";
            if(isset($_SESSION['jawaban'][$tryout_id][$current_fase][$i]) && $_SESSION['jawaban'][$tryout_id][$current_fase][$i]!="") $statusClass="num-answered";
            if(in_array($i,$_SESSION['ragu'][$tryout_id])) $statusClass="num-ragu";
        ?>
        <button type="button" class="<?= $statusClass ?> nav-btn" data-target="<?= $i ?>"><?= $i+1 ?></button>
        <?php endfor; ?>
    </div>
</div>

<form method="POST" id="ujianForm">
<?php foreach($soal[$current_fase] as $i=>$s): ?>
<div class="soal card p-3 mb-3 <?= $i==0?'active':'' ?>" id="soal-<?= $i ?>">
    <h5>Soal <?= $i+1 ?></h5>
    <p><?= htmlspecialchars($s['pertanyaan']) ?></p>
    <?php if(!empty($s['gambar'])): ?>
    <img src="image/soal/<?= htmlspecialchars($s['gambar']) ?>" class="img-fluid mb-2">
    <?php endif; ?>
    <input type="hidden" name="jawaban[<?= $current_fase ?>][<?= $i ?>]" value="">
    <?php foreach(['A','B','C','D','E'] as $opt): ?>
    <div class="form-check">
        <input class="form-check-input jawaban-radio" type="radio" name="jawaban[<?= $current_fase ?>][<?= $i ?>]" value="<?= $opt ?>" id="soal<?= $i ?><?= $opt ?>">
        <label class="form-check-label" for="soal<?= $i ?><?= $opt ?>"><?= $opt ?>. <?= htmlspecialchars($s['opsi_'.strtolower($opt)]) ?></label>
    </div>
    <?php endforeach; ?>
    <div class="d-flex justify-content-between mt-2">
        <button type="button" class="btn btn-secondary" onclick="prevSoal(<?= $i ?>)">Sebelumnya</button>
        <label><input type="checkbox" class="ragu-checkbox" value="<?= $i ?>"> Ragu-ragu</label>
        <button type="button" class="btn btn-primary" onclick="nextSoal(<?= $i ?>)">Selanjutnya</button>
    </div>
</div>
<?php endforeach; ?>

<input type="hidden" name="next_fase" value="<?= $next_fase ?? '' ?>">
<div class="mt-3 d-flex justify-content-end">
<button type="submit" name="submit" class="btn btn-success">Kirim Fase <?= $current_fase ?></button>
</div>
</form>

<!-- ===== Modal Konfirmasi ===== -->
<div id="confirmModal">
    <div class="modal-content">
        <p>Apakah Anda yakin ingin mengirim fase ini?</p>
        <button id="confirmYes" class="btn btn-success">Ya, Kirim</button>
        <button id="confirmNo" class="btn btn-secondary">Batal</button>
    </div>
</div>

<script>
let current=0;
const total=<?= $total_soal ?>;
const soalEls=document.querySelectorAll('.soal');
const navBtns=document.querySelectorAll('.nav-btn');
const sidebar=document.getElementById('navigatorSidebar');
const toggleBtn=document.getElementById('toggleNavigator');
const closeBtn=document.getElementById('closeNavigator');
const ujianForm=document.getElementById('ujianForm');
const confirmModal=document.getElementById('confirmModal');
const confirmYes=document.getElementById('confirmYes');
const confirmNo=document.getElementById('confirmNo');

// ===== Load jawaban & ragu dari localStorage =====
const storageKey = 'tryout_<?= $tryout_id ?>_fase_<?= $current_fase ?>';
let savedData = JSON.parse(localStorage.getItem(storageKey) || '{}');
soalEls.forEach((soalEl, i)=>{
    if(savedData.jawaban && savedData.jawaban[i]){
        let val = savedData.jawaban[i];
        let radio = soalEl.querySelector(`input[value="${val}"]`);
        if(radio) radio.checked = true;
        navBtns[i].classList.remove('num-blank','num-ragu');
        navBtns[i].classList.add('num-answered');
    }
    if(savedData.ragu && savedData.ragu.includes(i)){
        let checkbox = soalEl.querySelector('.ragu-checkbox');
        if(checkbox) checkbox.checked = true;
        navBtns[i].classList.remove('num-blank','num-answered');
        navBtns[i].classList.add('num-ragu');
    }
});

// ===== Fungsi Navigasi Soal =====
function showSoal(idx){
    soalEls.forEach((el,i)=>el.classList.toggle('active',i===idx));
    navBtns.forEach((btn,i)=>btn.classList.toggle('active',i===idx));
    current=idx;
}
function nextSoal(idx){if(idx<total-1) showSoal(idx+1);}
function prevSoal(idx){if(idx>0) showSoal(idx-1);}
navBtns.forEach(btn=>btn.addEventListener('click',()=>showSoal(parseInt(btn.getAttribute('data-target')))));

// ===== Toggle Sidebar (buka/tutup) =====
toggleBtn.addEventListener('click',()=>{
    sidebar.style.display="block"; // buka
    toggleBtn.style.display="none"; // sembunyikan tombol utama
});
closeBtn.addEventListener('click',()=>{
    sidebar.style.display="none"; // tutup
    toggleBtn.style.display="block"; // munculkan tombol utama lagi
});

// ===== Timer =====
let duration = <?= $duration ?> * 60;
let start = <?= $start_time ?>;
function countdown(){
    let now = Math.floor(Date.now()/1000);
    let elapsed = now - start;
    let remaining = duration - elapsed;
    if(remaining <= 0){
        alert("Waktu fase habis!");
        <?php if($next_fase !== null): ?>
        window.location.href="ujian.php?id=<?= $tryout_id ?>&fase=<?= $next_fase ?>";
        <?php else: ?>
        window.location.href="selesai.php?id=<?= $tryout_id ?>";
        <?php endif; ?>
        return;
    }
    let m = Math.floor(remaining / 60);
    let s = remaining % 60;
    document.getElementById('timer').innerText = `Sisa Waktu: ${m} menit ${s} detik`;
    if(remaining==300) alert("5 menit terakhir!");
    if(remaining==60) alert("1 menit terakhir!");
}
setInterval(countdown,1000);

// ===== Keyboard Shortcuts =====
document.addEventListener('keydown', function(e){
    if(e.key==='ArrowRight'){ nextSoal(current); e.preventDefault(); }
    if(e.key==='ArrowLeft'){ prevSoal(current); e.preventDefault(); }
    if(['a','b','c','d','e'].includes(e.key.toLowerCase())){
        const val = e.key.toUpperCase();
        const radios = soalEls[current].querySelectorAll('.jawaban-radio');
        radios.forEach(r=>{ if(r.value===val) r.checked=true; });
        const navBtn = navBtns[current];
        navBtn.classList.remove('num-blank','num-ragu');
        navBtn.classList.add('num-answered');
        saveToLocalStorage();
    }
});

// ===== Checkbox Ragu-ragu =====
document.querySelectorAll('.ragu-checkbox').forEach((checkbox,i)=>{
    checkbox.addEventListener('change', ()=>{
        const navBtn = navBtns[i];
        navBtn.classList.remove('num-blank','num-answered','num-ragu');
        if(checkbox.checked) navBtn.classList.add('num-ragu');
        else if(soalEls[i].querySelector('.jawaban-radio:checked')) navBtn.classList.add('num-answered');
        else navBtn.classList.add('num-blank');
        saveToLocalStorage();
    });
});

// ===== Simpan jawaban & ragu ke localStorage =====
function saveToLocalStorage(){
    let data={jawaban:[], ragu:[]};
    soalEls.forEach((el,i)=>{
        let radio = el.querySelector('.jawaban-radio:checked');
        if(radio) data.jawaban[i] = radio.value;
        let ragu = el.querySelector('.ragu-checkbox');
        if(ragu.checked) data.ragu.push(i);
    });
    localStorage.setItem(storageKey, JSON.stringify(data));
}
document.querySelectorAll('.jawaban-radio').forEach(r=>{
    r.addEventListener('change', saveToLocalStorage);
});

// ===== Konfirmasi sebelum submit =====
ujianForm.addEventListener('submit', function(e){
    e.preventDefault();
    confirmModal.style.display='flex';
});
confirmYes.addEventListener('click',()=>{
    // langsung redirect ke pause.php
    window.location.href = "pause.php?id=<?= $tryout_id ?>&fase=<?= $current_fase ?>";
});
confirmNo.addEventListener('click',()=>confirmModal.style.display='none');

// Tampilkan soal pertama
showSoal(0);
</script>

</body>
</html>
