<?php
include_once '../includes/auth.php';
include_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Hitung jumlah data
$query_mahasiswa = "SELECT COUNT(*) as total FROM mahasiswa";
$stmt_mahasiswa = $db->prepare($query_mahasiswa);
$stmt_mahasiswa->execute();
$total_mahasiswa = $stmt_mahasiswa->fetch(PDO::FETCH_ASSOC)['total'];

$query_matkul = "SELECT COUNT(*) as total FROM mata_kuliah";
$stmt_matkul = $db->prepare($query_matkul);
$stmt_matkul->execute();
$total_matkul = $stmt_matkul->fetch(PDO::FETCH_ASSOC)['total'];

$query_nilai = "SELECT COUNT(*) as total FROM nilai";
$stmt_nilai = $db->prepare($query_nilai);
$stmt_nilai->execute();
$total_nilai = $stmt_nilai->fetch(PDO::FETCH_ASSOC)['total'];

$query_admin = "SELECT COUNT(*) as total FROM admin";
$stmt_admin = $db->prepare($query_admin);
$stmt_admin->execute();
$total_admin = $stmt_admin->fetch(PDO::FETCH_ASSOC)['total'];

// Data untuk chart distribusi jurusan
$query_jurusan = "SELECT jurusan, COUNT(*) as jumlah FROM mahasiswa GROUP BY jurusan";
$stmt_jurusan = $db->prepare($query_jurusan);
$stmt_jurusan->execute();
$data_jurusan = $stmt_jurusan->fetchAll(PDO::FETCH_ASSOC);

// PERBAIKAN: Query untuk statistik jurusan yang benar
$query_stat_jurusan = "SELECT 
                        m.jurusan,
                        COUNT(DISTINCT m.id) as total_mahasiswa,
                        COUNT(n.id) as total_nilai
                    FROM mahasiswa m
                    LEFT JOIN nilai n ON m.id = n.mahasiswa_id
                    GROUP BY m.jurusan";
$stmt_stat_jurusan = $db->prepare($query_stat_jurusan);
$stmt_stat_jurusan->execute();
$stat_jurusan = $stmt_stat_jurusan->fetchAll(PDO::FETCH_ASSOC);

// PERBAIKAN: Hitung rata-rata IPK yang benar (skala 0-4)
$query_ipk = "SELECT 
                AVG(CASE 
                    WHEN nilai_huruf = 'A' THEN 4.0
                    WHEN nilai_huruf = 'B' THEN 3.0
                    WHEN nilai_huruf = 'C' THEN 2.0
                    WHEN nilai_huruf = 'D' THEN 1.0
                    WHEN nilai_huruf = 'E' THEN 0.0
                END) as ipk 
              FROM nilai";
$stmt_ipk = $db->prepare($query_ipk);
$stmt_ipk->execute();
$result_ipk = $stmt_ipk->fetch(PDO::FETCH_ASSOC);
$avg_ipk = $result_ipk['ipk'] ? number_format($result_ipk['ipk'], 2) : '0.00';

// Hitung distribusi nilai untuk chart
$query_distribusi_nilai = "SELECT 
                            nilai_huruf,
                            COUNT(*) as jumlah,
                            CASE 
                                WHEN nilai_huruf = 'A' THEN 4.0
                                WHEN nilai_huruf = 'B' THEN 3.0
                                WHEN nilai_huruf = 'C' THEN 2.0
                                WHEN nilai_huruf = 'D' THEN 1.0
                                WHEN nilai_huruf = 'E' THEN 0.0
                            END as bobot
                           FROM nilai 
                           GROUP BY nilai_huruf 
                           ORDER BY bobot DESC";
$stmt_distribusi_nilai = $db->prepare($query_distribusi_nilai);
$stmt_distribusi_nilai->execute();
$distribusi_nilai = $stmt_distribusi_nilai->fetchAll(PDO::FETCH_ASSOC);

// Hitung mahasiswa dengan IPK tertinggi
$query_top_ipk = "SELECT 
                    m.nama,
                    m.npm,
                    m.jurusan,
                    AVG(CASE 
                        WHEN n.nilai_huruf = 'A' THEN 4.0
                        WHEN n.nilai_huruf = 'B' THEN 3.0
                        WHEN n.nilai_huruf = 'C' THEN 2.0
                        WHEN n.nilai_huruf = 'D' THEN 1.0
                        WHEN n.nilai_huruf = 'E' THEN 0.0
                    END) as ipk
                  FROM mahasiswa m
                  JOIN nilai n ON m.id = n.mahasiswa_id
                  GROUP BY m.id, m.nama, m.npm, m.jurusan
                  HAVING COUNT(n.id) >= 3  -- Minimal 3 mata kuliah
                  ORDER BY ipk DESC 
                  LIMIT 3";
$stmt_top_ipk = $db->prepare($query_top_ipk);
$stmt_top_ipk->execute();
$top_ipk = $stmt_top_ipk->fetchAll(PDO::FETCH_ASSOC);

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistem Akademik</title>
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
        
        .chart-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 25px;
        }
        
        .recent-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .recent-table .table {
            margin-bottom: 0;
        }
        
        .recent-table .table th {
            border-top: none;
            background: var(--light);
            font-weight: 600;
            padding: 15px;
        }
        
        .recent-table .table td {
            padding: 12px 15px;
            vertical-align: middle;
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
            border: 1px solid #e9ecef;
            text-decoration: none;
            color: inherit;
        }
        
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            color: inherit;
        }
        
        .distribution-card {
            height: 100%;
        }
        
        .distribution-badge {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .department-card {
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }
        
        .department-card:hover {
            border-left-color: var(--secondary);
            transform: translateX(5px);
        }
        
        .activity-progress {
            height: 10px;
            border-radius: 10px;
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
                            <a class="nav-link active" href="dashboard.php">
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
                            <a class="nav-link" href="input_nilai.php">
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
                <!-- Welcome Card -->
                <div class="welcome-card p-4 mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-2"><?php echo $salam . ', ' . $_SESSION['nama']; ?>! 👋</h3>
                            <p class="mb-0 opacity-75">Selamat datang di dashboard Sistem Informasi Akademik. Berikut ringkasan aktivitas sistem terkini.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
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
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="stat-number text-primary"><?php echo $total_mahasiswa; ?></div>
                                        <div class="stat-title">Total Mahasiswa</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-arrow-up text-success"></i>
                                        <small class="text-muted">+<?php echo min($total_mahasiswa, 12); ?>%</small>
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
                                        <small class="text-muted">+<?php echo min($total_matkul, 8); ?>%</small>
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
                                            <i class="fas fa-chart-bar"></i>
                                        </div>
                                        <div class="stat-number text-info"><?php echo $total_nilai; ?></div>
                                        <div class="stat-title">Data Nilai</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-arrow-up text-success"></i>
                                        <small class="text-muted">+<?php echo min($total_nilai, 15); ?>%</small>
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
                                            <i class="fas fa-user-shield"></i>
                                        </div>
                                        <div class="stat-number text-warning"><?php echo $total_admin; ?></div>
                                        <div class="stat-title">Administrator</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-minus text-muted"></i>
                                        <small class="text-muted">0%</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Column - Stats and Distribution -->
                    <div class="col-lg-12 mb-4">
                        <!-- Performance Cards -->
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="stat-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Rata-rata IPK Sistem</h6>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="display-4 fw-bold text-primary"><?php echo $avg_ipk; ?></div>
                                            <div class="ms-3">
                                                <span class="badge bg-success">Skala 4.0</span>
                                                <div class="text-muted small"><?php echo $total_nilai; ?> nilai tercatat</div>
                                            </div>
                                        </div>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo ($avg_ipk/4)*100; ?>%" 
                                                 aria-valuenow="<?php echo $avg_ipk; ?>" aria-valuemin="0" aria-valuemax="4">
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?php 
                                            if ($avg_ipk >= 3.5) {
                                                echo "📈 Excellent Performance";
                                            } elseif ($avg_ipk >= 3.0) {
                                                echo "👍 Good Performance"; 
                                            } elseif ($avg_ipk >= 2.5) {
                                                echo "✅ Average Performance";
                                            } else {
                                                echo "📝 Needs Improvement";
                                            }
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="stat-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Distribusi Nilai</h6>
                                        <?php if (count($distribusi_nilai) > 0): ?>
                                            <?php foreach ($distribusi_nilai as $nilai): ?>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span>
                                                        <span class="badge 
                                                            <?php 
                                                            switch($nilai['nilai_huruf']) {
                                                                case 'A': echo 'bg-success'; break;
                                                                case 'B': echo 'bg-primary'; break;
                                                                case 'C': echo 'bg-warning'; break;
                                                                case 'D': echo 'bg-danger'; break;
                                                                case 'E': echo 'bg-dark'; break;
                                                            }
                                                            ?> me-2">
                                                            <?php echo $nilai['nilai_huruf']; ?>
                                                        </span>
                                                        <small><?php echo $nilai['jumlah']; ?> mahasiswa</small>
                                                    </span>
                                                    <small class="text-muted"><?php echo number_format(($nilai['jumlah']/$total_nilai)*100, 1); ?>%</small>
                                                </div>
                                                <div class="progress" style="height: 6px;">
                                                    <div class="progress-bar 
                                                        <?php 
                                                        switch($nilai['nilai_huruf']) {
                                                            case 'A': echo 'bg-success'; break;
                                                            case 'B': echo 'bg-primary'; break;
                                                            case 'C': echo 'bg-warning'; break;
                                                            case 'D': echo 'bg-danger'; break;
                                                            case 'E': echo 'bg-dark'; break;
                                                        }
                                                        ?>" 
                                                        role="progressbar" 
                                                        style="width: <?php echo ($nilai['jumlah']/$total_nilai)*100; ?>%">
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted text-center py-3">Belum ada data nilai</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PERBAIKAN: Department Summary dengan data yang benar -->
                        <div class="stat-card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-university me-2"></i>Ringkasan per Jurusan
                                </h5>
                                <div class="row">
                                    <?php if (count($stat_jurusan) > 0): ?>
                                        <?php foreach($stat_jurusan as $jurusan): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="department-card border rounded p-3 h-100">
                                                <h6 class="fw-bold text-primary mb-2"><?php echo htmlspecialchars($jurusan['jurusan']); ?></h6>
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted">Mahasiswa:</span>
                                                    <span class="fw-bold text-primary"><?php echo $jurusan['total_mahasiswa']; ?></span>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-muted">Data Nilai:</span>
                                                    <span class="fw-bold text-info"><?php echo $jurusan['total_nilai']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <p class="text-muted text-center py-3">Belum ada data jurusan</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Top IPK Mahasiswa -->
                        <div class="stat-card mt-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-trophy me-2 text-warning"></i>Mahasiswa dengan IPK Tertinggi
                                </h5>
                                <?php if (count($top_ipk) > 0): ?>
                                    <div class="row">
                                        <?php foreach($top_ipk as $index => $mahasiswa): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="border rounded p-3 text-center">
                                                <div class="mb-3">
                                                    <div class="distribution-badge mx-auto 
                                                        <?php 
                                                        if ($index == 0) echo 'bg-warning text-white';
                                                        elseif ($index == 1) echo 'bg-secondary text-white'; 
                                                        else echo 'bg-light text-dark';
                                                        ?>">
                                                        <?php echo $index + 1; ?>
                                                    </div>
                                                </div>
                                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($mahasiswa['nama']); ?></h6>
                                                <small class="text-muted d-block mb-1"><?php echo htmlspecialchars($mahasiswa['npm']); ?></small>
                                                <small class="text-muted d-block mb-2"><?php echo htmlspecialchars($mahasiswa['jurusan']); ?></small>
                                                <div class="fw-bold text-primary fs-5"><?php echo number_format($mahasiswa['ipk'], 2); ?></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-3">Belum ada data IPK yang cukup</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="stat-card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="fas fa-bolt me-2 text-warning"></i>Quick Actions
                                </h5>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="data_mahasiswa.php" class="d-block p-4 quick-action-card text-center">
                                            <i class="fas fa-users fa-2x mb-3 text-primary"></i>
                                            <h6 class="fw-bold">Kelola Mahasiswa</h6>
                                            <small class="text-muted">Kelola data mahasiswa</small>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="data_matkul.php" class="d-block p-4 quick-action-card text-center">
                                            <i class="fas fa-book fa-2x mb-3 text-success"></i>
                                            <h6 class="fw-bold">Mata Kuliah</h6>
                                            <small class="text-muted">Kelola mata kuliah</small>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="input_nilai.php" class="d-block p-4 quick-action-card text-center">
                                            <i class="fas fa-edit fa-2x mb-3 text-warning"></i>
                                            <h6 class="fw-bold">Input Nilai</h6>
                                            <small class="text-muted">Input nilai mahasiswa</small>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="#" class="d-block p-4 quick-action-card text-center">
                                            <i class="fas fa-chart-pie fa-2x mb-3 text-info"></i>
                                            <h6 class="fw-bold">Laporan</h6>
                                            <small class="text-muted">Lihat laporan akademik</small>
                                        </a>
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
</body>
</html>