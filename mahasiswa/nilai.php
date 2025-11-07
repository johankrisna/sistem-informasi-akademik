<?php
include_once '../includes/auth.php';
include_once '../config/database.php';
redirectIfNotMahasiswa();

$database = new Database();
$db = $database->getConnection();

$mahasiswa_id = $_SESSION['user_id'];

// Ambil data mahasiswa untuk header
$query_mahasiswa = "SELECT nama, npm, jurusan FROM mahasiswa WHERE id = :id";
$stmt_mahasiswa = $db->prepare($query_mahasiswa);
$stmt_mahasiswa->bindParam(':id', $mahasiswa_id);
$stmt_mahasiswa->execute();
$mahasiswa = $stmt_mahasiswa->fetch(PDO::FETCH_ASSOC);

// Ambil data nilai mahasiswa
$query = "SELECT n.*, mk.kode_mk, mk.nama_mk, mk.sks 
          FROM nilai n 
          JOIN mata_kuliah mk ON n.mata_kuliah_id = mk.id 
          WHERE n.mahasiswa_id = :id 
          ORDER BY n.tahun_akademik DESC, n.semester DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $mahasiswa_id);
$stmt->execute();

// Hitung statistik
$total_sks = 0;
$total_matkul = 0;
$total_bobot = 0;
$nilai_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($nilai_data as $nilai) {
    $total_sks += $nilai['sks'];
    $total_matkul++;
    
    // Konversi nilai huruf ke bobot nilai
    switch($nilai['nilai_huruf']) {
        case 'A': $bobot = 4.0; break;
        case 'B': $bobot = 3.0; break;
        case 'C': $bobot = 2.0; break;
        case 'D': $bobot = 1.0; break;
        case 'E': $bobot = 0.0; break;
        default: $bobot = 0.0;
    }
    
    $total_bobot += ($bobot * $nilai['sks']);
}

// Hitung IPK yang benar (bobot x SKS)
$ipk = $total_sks > 0 ? number_format($total_bobot / $total_sks, 2) : '0.00';

// Hitung distribusi nilai
$distribusi_nilai = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
foreach ($nilai_data as $nilai) {
    if (isset($distribusi_nilai[$nilai['nilai_huruf']])) {
        $distribusi_nilai[$nilai['nilai_huruf']]++;
    }
}

// Hitung IPS per semester
$semester_data = [];
foreach ($nilai_data as $nilai) {
    $key = $nilai['semester'] . '_' . $nilai['tahun_akademik'];
    if (!isset($semester_data[$key])) {
        $semester_data[$key] = [
            'semester' => $nilai['semester'],
            'tahun_akademik' => $nilai['tahun_akademik'],
            'total_sks' => 0,
            'total_bobot' => 0,
            'jumlah_matkul' => 0
        ];
    }
    
    // Konversi nilai huruf ke bobot untuk IPS
    switch($nilai['nilai_huruf']) {
        case 'A': $bobot = 4.0; break;
        case 'B': $bobot = 3.0; break;
        case 'C': $bobot = 2.0; break;
        case 'D': $bobot = 1.0; break;
        case 'E': $bobot = 0.0; break;
        default: $bobot = 0.0;
    }
    
    $semester_data[$key]['total_sks'] += $nilai['sks'];
    $semester_data[$key]['total_bobot'] += ($bobot * $nilai['sks']);
    $semester_data[$key]['jumlah_matkul']++;
}

// Hitung IPS per semester
foreach ($semester_data as &$semester) {
    $semester['ips'] = $semester['total_sks'] > 0 ? 
        number_format($semester['total_bobot'] / $semester['total_sks'], 2) : '0.00';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilai Akademik - Sistem Akademik</title>
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
        
        .table-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table-card .table {
            margin-bottom: 0;
        }
        
        .table-card .table th {
            border-top: none;
            background: var(--light);
            font-weight: 600;
            padding: 15px;
        }
        
        .table-card .table td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .badge-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .progress {
            height: 8px;
            border-radius: 10px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .distribution-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
        }
        
        .grade-badge {
            font-size: 0.9rem;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 8px;
        }
        
        .ipk-progress {
            height: 12px;
            border-radius: 10px;
        }
        
        .ipk-indicator {
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
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
                            <h5 class="mb-0">Mahasiswa</h5>
                            <small class="opacity-75"><?php echo htmlspecialchars($mahasiswa['nama']); ?></small>
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
                            <a class="nav-link active" href="nilai.php">
                                <i class="fas fa-chart-bar"></i>
                                Nilai Akademik
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
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <div>
                        <h1 class="h2 mb-1">Nilai Akademik</h1>
                        <p class="text-muted">Transkrip nilai dan performa akademik Anda</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-star me-1"></i>IPK: <?php echo $ipk; ?>
                            </span>
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
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="stat-number text-primary"><?php echo $ipk; ?></div>
                                        <div class="stat-title">Indeks Prestasi Kumulatif</div>
                                        <div class="mt-2">
                                            <div class="progress ipk-progress">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     style="width: <?php echo ($ipk/4)*100; ?>%" 
                                                     aria-valuenow="<?php echo $ipk; ?>" aria-valuemin="0" aria-valuemax="4">
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php 
                                                if ($ipk >= 3.5) {
                                                    echo '<span class="ipk-indicator bg-success text-white">Cum Laude</span>';
                                                } elseif ($ipk >= 3.0) {
                                                    echo '<span class="ipk-indicator bg-primary text-white">Sangat Memuaskan</span>';
                                                } elseif ($ipk >= 2.5) {
                                                    echo '<span class="ipk-indicator bg-warning text-dark">Memuaskan</span>';
                                                } else {
                                                    echo '<span class="ipk-indicator bg-danger text-white">Perlu Peningkatan</span>';
                                                }
                                                ?>
                                            </small>
                                        </div>
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
                                        <div class="stat-title">Total Mata Kuliah</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle text-success"></i>
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
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <div class="stat-number text-info"><?php echo $total_sks; ?></div>
                                        <div class="stat-title">Total SKS</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-calculator text-info"></i>
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
                                        <div class="stat-icon bg-gradient-warning text-white">
                                            <i class="fas fa-trophy"></i>
                                        </div>
                                        <div class="stat-number text-warning"><?php echo $distribusi_nilai['A']; ?></div>
                                        <div class="stat-title">Nilai A</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-award text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Main Table -->
                    <div class="col-lg-8 mb-4">
                        <div class="table-card">
                            <div class="card-header bg-white border-bottom-0 py-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-list me-2"></i>Transkrip Nilai
                                        </h5>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <span class="badge badge-gradient"><?php echo $total_matkul; ?> Mata Kuliah</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (count($nilai_data) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Kode MK</th>
                                                <th>Mata Kuliah</th>
                                                <th class="text-center">SKS</th>
                                                <th class="text-center">Nilai Huruf</th>
                                                <th class="text-center">Bobot</th>
                                                <th class="text-center">Semester</th>
                                                <th class="text-center">Tahun</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($nilai_data as $row): 
                                                // Hitung bobot untuk setiap mata kuliah
                                                switch($row['nilai_huruf']) {
                                                    case 'A': $bobot = 4.0; break;
                                                    case 'B': $bobot = 3.0; break;
                                                    case 'C': $bobot = 2.0; break;
                                                    case 'D': $bobot = 1.0; break;
                                                    case 'E': $bobot = 0.0; break;
                                                    default: $bobot = 0.0;
                                                }
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['kode_mk']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['nama_mk']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?php echo $row['sks']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="grade-badge 
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
                                                        <?php echo $row['nilai_huruf']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <strong><?php echo $bobot; ?></strong>
                                                </td>
                                                <td class="text-center">
                                                    <span class="text-muted"><?php echo $row['semester']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <small class="text-muted"><?php echo $row['tahun_akademik']; ?></small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                                        <h4 class="text-muted">Belum ada data nilai</h4>
                                        <p class="text-muted">Data nilai akan muncul setelah dosen menginput nilai mata kuliah.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Distribution and Info -->
                    <div class="col-lg-4 mb-4">
                        <!-- Distribution Card -->
                        <div class="distribution-card mb-4">
                            <h5 class="mb-4">
                                <i class="fas fa-chart-pie me-2"></i>Distribusi Nilai
                            </h5>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Nilai A</span>
                                    <span class="text-success"><?php echo $distribusi_nilai['A']; ?> matkul</span>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $total_matkul > 0 ? ($distribusi_nilai['A']/$total_matkul)*100 : 0; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Nilai B</span>
                                    <span class="text-primary"><?php echo $distribusi_nilai['B']; ?> matkul</span>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?php echo $total_matkul > 0 ? ($distribusi_nilai['B']/$total_matkul)*100 : 0; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Nilai C</span>
                                    <span class="text-warning"><?php echo $distribusi_nilai['C']; ?> matkul</span>
                                </div>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-warning" role="progressbar" 
                                         style="width: <?php echo $total_matkul > 0 ? ($distribusi_nilai['C']/$total_matkul)*100 : 0; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Nilai D & E</span>
                                    <span class="text-danger"><?php echo ($distribusi_nilai['D'] + $distribusi_nilai['E']); ?> matkul</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" role="progressbar" 
                                         style="width: <?php echo $total_matkul > 0 ? (($distribusi_nilai['D'] + $distribusi_nilai['E'])/$total_matkul)*100 : 0; ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Info Card -->
                        <div class="stat-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Sistem Penilaian
                                </h5>
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="grade-badge bg-success text-white me-2">A</span>
                                        <span>4.0 (85 - 100)</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="grade-badge bg-primary text-white me-2">B</span>
                                        <span>3.0 (75 - 84)</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="grade-badge bg-warning text-dark me-2">C</span>
                                        <span>2.0 (65 - 74)</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="grade-badge bg-danger text-white me-2">D</span>
                                        <span>1.0 (55 - 64)</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="grade-badge bg-dark text-white me-2">E</span>
                                        <span>0.0 (0 - 54)</span>
                                    </div>
                                </div>
                                <hr>
                                <div class="small">
                                    <strong>Rumus IPK:</strong><br>
                                    IPK = Σ (Bobot × SKS) ÷ Σ SKS
                                </div>
                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-sync-alt me-1"></i>Data diperbarui secara real-time
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Semester Summary -->
                <?php if (count($nilai_data) > 0): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="table-card">
                            <div class="card-header bg-white border-bottom-0 py-3">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-calendar-alt me-2"></i>Ringkasan per Semester
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($semester_data as $semester): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3 text-center">
                                            <h6 class="text-primary">Semester <?php echo $semester['semester']; ?></h6>
                                            <p class="text-muted small mb-2"><?php echo $semester['tahun_akademik']; ?></p>
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <small class="text-muted">IPS</small>
                                                    <div class="fw-bold text-primary"><?php echo $semester['ips']; ?></div>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted">SKS</small>
                                                    <div class="fw-bold text-success"><?php echo $semester['total_sks']; ?></div>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted">Matkul</small>
                                                    <div class="fw-bold text-info"><?php echo $semester['jumlah_matkul']; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>