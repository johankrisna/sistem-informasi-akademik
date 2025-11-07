<?php
include_once '../includes/auth.php';
include_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Ambil data mahasiswa dan mata kuliah untuk dropdown
$query_mahasiswa = "SELECT id, npm, nama FROM mahasiswa ORDER BY nama";
$stmt_mahasiswa = $db->prepare($query_mahasiswa);
$stmt_mahasiswa->execute();

$query_matkul = "SELECT id, kode_mk, nama_mk FROM mata_kuliah ORDER BY nama_mk";
$stmt_matkul = $db->prepare($query_matkul);
$stmt_matkul->execute();

if ($_POST) {
    $mahasiswa_id = $_POST['mahasiswa_id'];
    $mata_kuliah_id = $_POST['mata_kuliah_id'];
    $nilai_angka = $_POST['nilai_angka'];
    $semester = $_POST['semester'];
    $tahun_akademik = $_POST['tahun_akademik'];
    
    // Konversi nilai angka ke huruf
    if ($nilai_angka >= 85) $nilai_huruf = 'A';
    elseif ($nilai_angka >= 75) $nilai_huruf = 'B';
    elseif ($nilai_angka >= 65) $nilai_huruf = 'C';
    elseif ($nilai_angka >= 55) $nilai_huruf = 'D';
    else $nilai_huruf = 'E';
    
    try {
        $query = "INSERT INTO nilai (mahasiswa_id, mata_kuliah_id, nilai_huruf, nilai_angka, semester, tahun_akademik) 
                  VALUES (:mahasiswa_id, :mata_kuliah_id, :nilai_huruf, :nilai_angka, :semester, :tahun_akademik)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':mahasiswa_id', $mahasiswa_id);
        $stmt->bindParam(':mata_kuliah_id', $mata_kuliah_id);
        $stmt->bindParam(':nilai_huruf', $nilai_huruf);
        $stmt->bindParam(':nilai_angka', $nilai_angka);
        $stmt->bindParam(':semester', $semester);
        $stmt->bindParam(':tahun_akademik', $tahun_akademik);
        
        if ($stmt->execute()) {
            $success = "Nilai berhasil disimpan!";
        }
    } catch (PDOException $exception) {
        $error = "Error: " . $exception->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai - Sistem Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            font-weight: 600;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card .card-body {
            padding: 25px;
        }
        
        .badge-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            padding: 12px 30px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }
        
        .page-header {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 30px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .grade-preview {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            border-left: 4px solid var(--primary);
        }
        
        .grade-badge {
            font-size: 1.2rem;
            font-weight: bold;
            padding: 8px 16px;
            border-radius: 8px;
        }
        
        .input-group-icon {
            position: relative;
        }
        
        .input-group-icon .form-control {
            padding-left: 45px;
        }
        
        .input-group-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 5;
        }
        
        .info-card {
            background: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-card i {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h3 fw-bold text-primary">
                    <i class="fas fa-graduation-cap me-2"></i>Sistem Akademik
                </span>
                <div class="d-flex align-items-center">
                    <span class="me-3 text-muted">
                        <i class="fas fa-user-circle me-1"></i><?php echo $_SESSION['nama']; ?>
                    </span>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            Admin
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <div class="text-white p-3">
                            <i class="fas fa-user-shield fa-2x mb-3"></i>
                            <h5 class="mb-0">Admin Panel</h5>
                            <small class="opacity-75">Sistem Informasi Akademik</small>
                        </div>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="data_mahasiswa.php">
                                <i class="fas fa-users"></i>
                                Data Mahasiswa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="data_matkul.php">
                                <i class="fas fa-book"></i>
                                Mata Kuliah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="input_nilai.php">
                                <i class="fas fa-edit"></i>
                                Input Nilai
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-0">
                                <i class="fas fa-edit me-2 text-primary"></i>Input Nilai Mahasiswa
                            </h1>
                            <p class="text-muted mb-0">Input nilai mahasiswa untuk mata kuliah tertentu</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="info-card">
                                <i class="fas fa-info-circle"></i>
                                <h6 class="mb-1">Konversi Nilai Otomatis</h6>
                                <small class="opacity-75">Sistem akan mengkonversi nilai angka ke huruf</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <div><?php echo $success; ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <!-- Input Form -->
                <div class="form-container">
                    <form method="POST" id="nilaiForm">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mahasiswa_id" class="form-label">
                                        <i class="fas fa-user me-1 text-primary"></i>Mahasiswa
                                    </label>
                                    <select class="form-select" id="mahasiswa_id" name="mahasiswa_id" required>
                                        <option value="">Pilih Mahasiswa</option>
                                        <?php while ($row = $stmt_mahasiswa->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?php echo $row['id']; ?>">
                                                <?php echo $row['npm'] . ' - ' . $row['nama']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mata_kuliah_id" class="form-label">
                                        <i class="fas fa-book me-1 text-primary"></i>Mata Kuliah
                                    </label>
                                    <select class="form-select" id="mata_kuliah_id" name="mata_kuliah_id" required>
                                        <option value="">Pilih Mata Kuliah</option>
                                        <?php while ($row = $stmt_matkul->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?php echo $row['id']; ?>">
                                                <?php echo $row['kode_mk'] . ' - ' . $row['nama_mk']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nilai_angka" class="form-label">
                                        <i class="fas fa-chart-line me-1 text-primary"></i>Nilai Angka (0-100)
                                    </label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-percentage"></i>
                                        <input type="number" class="form-control" id="nilai_angka" name="nilai_angka" 
                                               min="0" max="100" step="0.01" required placeholder="0-100">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="semester" class="form-label">
                                        <i class="fas fa-calendar-alt me-1 text-primary"></i>Semester
                                    </label>
                                    <select class="form-select" id="semester" name="semester" required>
                                        <option value="">Pilih Semester</option>
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="tahun_akademik" class="form-label">
                                        <i class="fas fa-calendar me-1 text-primary"></i>Tahun Akademik
                                    </label>
                                    <div class="input-group-icon">
                                        <i class="fas fa-calendar-week"></i>
                                        <input type="text" class="form-control" id="tahun_akademik" name="tahun_akademik" 
                                               placeholder="2023/2024" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Grade Preview -->
                        <div class="grade-preview" id="gradePreview" style="display: none;">
                            <h6 class="mb-3">
                                <i class="fas fa-eye me-2 text-primary"></i>Pratinjau Nilai
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <span class="text-muted">Nilai Angka:</span>
                                        <span class="fw-bold ms-2" id="previewNilaiAngka">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted">Nilai Huruf:</span>
                                        <span class="fw-bold ms-2">
                                            <span class="grade-badge" id="previewNilaiHuruf">-</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <span class="text-muted">Keterangan:</span>
                                        <span class="fw-bold ms-2" id="previewKeterangan">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted">Bobot:</span>
                                        <span class="fw-bold ms-2" id="previewBobot">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted small">
                                <i class="fas fa-lightbulb me-1 text-warning"></i>
                                Pastikan data yang dimasukkan sudah benar
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Nilai
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Grade Legend -->
                <div class="stat-card mt-4">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-list-alt me-2 text-primary"></i>Keterangan Nilai
                        </h6>
                        <div class="row text-center">
                            <div class="col-md-2 col-6 mb-3">
                                <div class="grade-badge bg-success text-white">A</div>
                                <div class="small text-muted mt-1">85 - 100</div>
                                <div class="small text-muted">Sangat Baik</div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="grade-badge bg-primary text-white">B</div>
                                <div class="small text-muted mt-1">75 - 84.99</div>
                                <div class="small text-muted">Baik</div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="grade-badge bg-warning text-white">C</div>
                                <div class="small text-muted mt-1">65 - 74.99</div>
                                <div class="small text-muted">Cukup</div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="grade-badge bg-danger text-white">D</div>
                                <div class="small text-muted mt-1">55 - 64.99</div>
                                <div class="small text-muted">Kurang</div>
                            </div>
                            <div class="col-md-2 col-6 mb-3">
                                <div class="grade-badge bg-dark text-white">E</div>
                                <div class="small text-muted mt-1">0 - 54.99</div>
                                <div class="small text-muted">Gagal</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nilaiInput = document.getElementById('nilai_angka');
            const gradePreview = document.getElementById('gradePreview');
            const previewNilaiAngka = document.getElementById('previewNilaiAngka');
            const previewNilaiHuruf = document.getElementById('previewNilaiHuruf');
            const previewKeterangan = document.getElementById('previewKeterangan');
            const previewBobot = document.getElementById('previewBobot');
            
            // Fungsi untuk mengkonversi nilai
            function konversiNilai(nilai) {
                let nilaiHuruf, keterangan, bobot;
                
                if (nilai >= 85) {
                    nilaiHuruf = 'A';
                    keterangan = 'Sangat Baik';
                    bobot = '4.00';
                } else if (nilai >= 75) {
                    nilaiHuruf = 'B';
                    keterangan = 'Baik';
                    bobot = '3.00';
                } else if (nilai >= 65) {
                    nilaiHuruf = 'C';
                    keterangan = 'Cukup';
                    bobot = '2.00';
                } else if (nilai >= 55) {
                    nilaiHuruf = 'D';
                    keterangan = 'Kurang';
                    bobot = '1.00';
                } else {
                    nilaiHuruf = 'E';
                    keterangan = 'Gagal';
                    bobot = '0.00';
                }
                
                return { nilaiHuruf, keterangan, bobot };
            }
            
            // Fungsi untuk memperbarui preview
            function updatePreview() {
                const nilai = parseFloat(nilaiInput.value);
                
                if (!isNaN(nilai) && nilai >= 0 && nilai <= 100) {
                    const { nilaiHuruf, keterangan, bobot } = konversiNilai(nilai);
                    
                    previewNilaiAngka.textContent = nilai.toFixed(2);
                    previewNilaiHuruf.textContent = nilaiHuruf;
                    previewKeterangan.textContent = keterangan;
                    previewBobot.textContent = bobot;
                    
                    // Set warna badge berdasarkan nilai huruf
                    previewNilaiHuruf.className = 'grade-badge';
                    if (nilaiHuruf === 'A') previewNilaiHuruf.classList.add('bg-success', 'text-white');
                    else if (nilaiHuruf === 'B') previewNilaiHuruf.classList.add('bg-primary', 'text-white');
                    else if (nilaiHuruf === 'C') previewNilaiHuruf.classList.add('bg-warning', 'text-white');
                    else if (nilaiHuruf === 'D') previewNilaiHuruf.classList.add('bg-danger', 'text-white');
                    else previewNilaiHuruf.classList.add('bg-dark', 'text-white');
                    
                    gradePreview.style.display = 'block';
                } else {
                    gradePreview.style.display = 'none';
                }
            }
            
            // Event listener untuk input nilai
            nilaiInput.addEventListener('input', updatePreview);
            
            // Set tahun akademik default
            const tahunSekarang = new Date().getFullYear();
            document.getElementById('tahun_akademik').value = `${tahunSekarang}/${tahunSekarang + 1}`;
        });
    </script>
</body>
</html>