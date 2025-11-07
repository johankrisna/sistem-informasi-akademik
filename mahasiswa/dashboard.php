<?php
include_once '../includes/auth.php';
include_once '../config/database.php';
redirectIfNotMahasiswa();

$database = new Database();
$db = $database->getConnection();

$mahasiswa_id = $_SESSION['user_id'];

// Ambil data mahasiswa
$query = "SELECT * FROM mahasiswa WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $mahasiswa_id);
$stmt->execute();
$mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);

// Hitung jumlah mata kuliah yang diambil
$query_matkul = "SELECT COUNT(DISTINCT mata_kuliah_id) as total FROM nilai WHERE mahasiswa_id = :id";
$stmt_matkul = $db->prepare($query_matkul);
$stmt_matkul->bindParam(':id', $mahasiswa_id);
$stmt_matkul->execute();
$total_matkul = $stmt_matkul->fetch(PDO::FETCH_ASSOC)['total'];

// Hitung IPK
$query_ipk = "SELECT AVG(nilai_angka) as ipk FROM nilai WHERE mahasiswa_id = :id";
$stmt_ipk = $db->prepare($query_ipk);
$stmt_ipk->bindParam(':id', $mahasiswa_id);
$stmt_ipk->execute();
$ipk_result = $stmt_ipk->fetch(PDO::FETCH_ASSOC)['ipk'];
$ipk = $ipk_result ? number_format($ipk_result, 2) : '0.00';

// Hitung total SKS
$query_sks = "SELECT SUM(mk.sks) as total_sks 
              FROM nilai n 
              JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id 
              WHERE n.mahasiswa_id = :id";
$stmt_sks = $db->prepare($query_sks);
$stmt_sks->bindParam(':id', $mahasiswa_id);
$stmt_sks->execute();
$total_sks = $stmt_sks->fetch(PDO::FETCH_ASSOC)['total_sks'] ?? 0;

// Ambil nilai terbaru
$query_nilai_terbaru = "SELECT n.*, mk.nama_mk, mk.sks 
                        FROM nilai n 
                        JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id 
                        WHERE n.mahasiswa_id = :id 
                        ORDER BY n.id DESC LIMIT 3";
$stmt_nilai_terbaru = $db->prepare($query_nilai_terbaru);
$stmt_nilai_terbaru->bindParam(':id', $mahasiswa_id);
$stmt_nilai_terbaru->execute();

// Waktu sekarang untuk greeting
$waktu = date('H');
if ($waktu < 12) {
    $salam = 'Selamat Pagi';
} elseif ($waktu < 15) {
    $salam = 'Selamat Siang';
} elseif ($waktu < 19) {
    $salam = 'Selamat Sore';
} else {
    $salam = 'Selamat Malam';
}

// Hitung semester berdasarkan angkatan
$tahun_sekarang = date('Y');
$semester_sekarang = (($tahun_sekarang - $mahasiswa['angkatan']) * 2) + 1;
$semester_sekarang = min($semester_sekarang, 8); // Maksimal semester 8
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - Sistem Akademik</title>
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
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);
        }
        
        .bg-gradient-info {
            background: linear-gradient(135deg, #4895ef 0%, #4cc9f0 100%);
        }
        
        .bg-gradient-warning {
            background: linear-gradient(135deg, #f72585 0%, #7209b7 100%);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .info-card .card-body {
            padding: 25px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .badge-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .progress {
            height: 8px;
            border-radius: 10px;
        }
        
        .quick-action-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: none;
        }
        
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .nilai-badge {
            font-size: 0.9rem;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 8px;
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
                        <i class="fas fa-user-graduate me-1"></i><?php echo $_SESSION['nama']; ?>
                    </span>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            Mahasiswa
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i>Profil Saya</a></li>
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
                            <i class="fas fa-user-graduate fa-2x mb-3"></i>
                            <h5 class="mb-0">Panel Mahasiswa</h5>
                            <small class="opacity-75"><?php echo $mahasiswa['npm']; ?></small>
                        </div>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profil.php">
                                <i class="fas fa-user"></i>
                                Profil Saya
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="jadwal.php">
                                <i class="fas fa-calendar"></i>
                                Jadwal Kuliah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="nilai.php">
                                <i class="fas fa-chart-bar"></i>
                                Lihat Nilai
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
                <!-- Welcome Card -->
                <div class="welcome-card p-4 mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-2"><?php echo $salam . ', ' . $_SESSION['nama']; ?>! 👋</h3>
                            <p class="mb-0 opacity-75">Selamat datang di dashboard akademik Anda. Pantau perkembangan studi dan aktivitas pembelajaran Anda di sini.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <i class="fas fa-user-graduate fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="stat-icon bg-gradient-primary text-white">
                                            <i class="fas fa-id-card"></i>
                                        </div>
                                        <div class="stat-number text-primary"><?php echo $mahasiswa['npm']; ?></div>
                                        <div class="stat-title">NPM</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check text-success"></i>
                                        <small class="text-muted">Aktif</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="stat-icon bg-gradient-success text-white">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="stat-number text-success"><?php echo $total_matkul; ?></div>
                                        <div class="stat-title">Mata Kuliah</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-arrow-up text-success"></i>
                                        <small class="text-muted">+<?php echo min($total_matkul, 5); ?>%</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="stat-icon bg-gradient-info text-white">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="stat-number text-info"><?php echo $ipk; ?></div>
                                        <div class="stat-title">IPK</div>
                                    </div>
                                    <div class="align-self-center">
                                        <?php if ($ipk >= 3.5): ?>
                                            <i class="fas fa-arrow-up text-success"></i>
                                            <small class="text-success">Excellent</small>
                                        <?php elseif ($ipk >= 3.0): ?>
                                            <i class="fas fa-minus text-warning"></i>
                                            <small class="text-warning">Good</small>
                                        <?php else: ?>
                                            <i class="fas fa-arrow-down text-danger"></i>
                                            <small class="text-danger">Need Improve</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo ($ipk/4)*100; ?>%"></div>
                                </div>
                                <small class="text-muted">Skala 4.0</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="stat-icon bg-gradient-warning text-white">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <div class="stat-number text-warning"><?php echo $total_sks; ?></div>
                                        <div class="stat-title">Total SKS</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-line text-success"></i>
                                        <small class="text-muted"><?php echo $semester_sekarang; ?> Sem</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Informasi Mahasiswa & Quick Access -->
                    <div class="col-lg-8 mb-4">
                        <div class="info-card mb-4">
                            <div class="card-header bg-white border-bottom-0 py-3">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user me-2"></i>Informasi Mahasiswa
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-borderless">
                                            <tr>
                                                <td width="40%"><strong>Nama Lengkap</strong></td>
                                                <td><?php echo htmlspecialchars($mahasiswa['nama']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Jurusan</strong></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($mahasiswa['jurusan']); ?></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Angkatan</strong></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($mahasiswa['angkatan']); ?></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-borderless">
                                            <tr>
                                                <td width="40%"><strong>Email</strong></td>
                                                <td><?php echo htmlspecialchars($mahasiswa['email']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>No. HP</strong></td>
                                                <td><?php echo !empty($mahasiswa['no_hp']) ? htmlspecialchars($mahasiswa['no_hp']) : '<span class="text-muted">-</span>'; ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Status</strong></td>
                                                <td><span class="badge bg-success">Aktif</span></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="info-card">
                            <div class="card-header bg-white border-bottom-0 py-3">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt me-2"></i>Quick Access
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <a href="profil.php" class="btn btn-outline-primary w-100 py-3 quick-action-card">
                                            <i class="fas fa-user fa-2x mb-2"></i><br>
                                            Profil Saya
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="jadwal.php" class="btn btn-outline-success w-100 py-3 quick-action-card">
                                            <i class="fas fa-calendar fa-2x mb-2"></i><br>
                                            Jadwal Kuliah
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="nilai.php" class="btn btn-outline-warning w-100 py-3 quick-action-card">
                                            <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                                            Lihat Nilai
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nilai Terbaru & Progress -->
                    <div class="col-lg-4 mb-4">
                        <!-- Nilai Terbaru -->
                        <div class="info-card mb-4">
                            <div class="card-header bg-white border-bottom-0 py-3">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-star me-2"></i>Nilai Terbaru
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if ($stmt_nilai_terbaru->rowCount() > 0): ?>
                                    <?php while ($row = $stmt_nilai_terbaru->fetch(PDO::FETCH_ASSOC)): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($row['nama_mk']); ?></h6>
                                            <small class="text-muted"><?php echo $row['sks']; ?> SKS</small>
                                        </div>
                                        <div>
                                            <span class="nilai-badge 
                                                <?php 
                                                switch($row['nilai_huruf']) {
                                                    case 'A': echo 'bg-success text-white'; break;
                                                    case 'B': echo 'bg-primary text-white'; break;
                                                    case 'C': echo 'bg-warning text-dark'; break;
                                                    case 'D': echo 'bg-danger text-white'; break;
                                                    case 'E': echo 'bg-dark text-white'; break;
                                                    default: echo 'bg-secondary text-white';
                                                }
                                                ?>">
                                                <?php echo $row['nilai_huruf']; ?> (<?php echo $row['nilai_angka']; ?>)
                                            </span>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-book-open fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">Belum ada nilai</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Progress Semester -->
                        <div class="info-card">
                            <div class="card-header bg-white border-bottom-0 py-3">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-tasks me-2"></i>Progress Semester
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Semester <?php echo $semester_sekarang; ?></span>
                                        <span class="text-primary"><?php echo round(($semester_sekarang/8)*100); ?>%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($semester_sekarang/8)*100; ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total SKS</span>
                                        <span class="text-success"><?php echo $total_sks; ?> / 144</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(($total_sks/144)*100, 100); ?>%"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Mata Kuliah</span>
                                        <span class="text-info"><?php echo $total_matkul; ?> / 40</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo min(($total_matkul/40)*100, 100); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto close alerts after 5 seconds (if any)
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>