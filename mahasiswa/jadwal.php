<?php
include_once '../includes/auth.php';
include_once '../config/database.php';
redirectIfNotMahasiswa();

$database = new Database();
$db = $database->getConnection();

// Ambil data jadwal kuliah
$query = "SELECT jk.*, mk.kode_mk, mk.nama_mk, mk.sks, mk.dosen_pengampu 
          FROM jadwal_kuliah jk 
          JOIN mata_kuliah mk ON jk.mata_kuliah_id = mk.id 
          ORDER BY 
            FIELD(jk.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
            jk.jam_mulai";
$stmt = $db->prepare($query);
$stmt->execute();

// Group jadwal by hari untuk tampilan yang lebih baik
$jadwal_by_hari = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $jadwal_by_hari[$row['hari']][] = $row;
}

// Hari dalam urutan
$hari_order = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Hitung total SKS
$total_sks = 0;
foreach ($jadwal_by_hari as $hari_jadwal) {
    foreach ($hari_jadwal as $jadwal) {
        $total_sks += $jadwal['sks'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Kuliah - Sistem Akademik</title>
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
        
        .hari-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            font-weight: 600;
        }
        
        .mobile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            border: none;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .mobile-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .mobile-card .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .jadwal-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 15px 0;
            transition: background-color 0.3s;
        }
        
        .jadwal-item:hover {
            background-color: #f8f9fa;
        }
        
        .jadwal-item:last-child {
            border-bottom: none;
        }
        
        .info-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.9rem;
        }
        
        .time-badge {
            background: #e9ecef;
            color: #495057;
            border-radius: 10px;
            padding: 4px 10px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 20px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .stat-title {
            font-size: 0.9rem;
            color: #6c757d;
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
                            <small class="opacity-75">Portal Akademik</small>
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
                            <a class="nav-link active" href="jadwal.php">
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
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <div>
                        <h1 class="h2 mb-1">Jadwal Kuliah</h1>
                        <p class="text-muted">Lihat jadwal perkuliahan Anda secara lengkap dan terorganisir</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="info-badge">
                            <i class="fas fa-book me-1"></i>Total <?php echo $total_sks; ?> SKS
                        </span>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($jadwal_by_hari); ?></div>
                            <div class="stat-title">Hari Aktif</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stmt->rowCount(); ?></div>
                            <div class="stat-title">Mata Kuliah</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $total_sks; ?></div>
                            <div class="stat-title">Total SKS</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count(array_unique(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'dosen_pengampu'))); ?></div>
                            <div class="stat-title">Dosen</div>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table View (Hidden on Mobile) -->
                <div class="d-none d-md-block">
                    <div class="table-card mb-4">
                        <div class="card-header bg-white border-bottom-0 py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-table me-2"></i>Jadwal Perkuliahan
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($jadwal_by_hari)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">Belum ada jadwal kuliah</h4>
                                    <p class="text-muted">Jadwal perkuliahan akan tersedia setelah proses KRS selesai.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="120">Hari</th>
                                                <th>Mata Kuliah</th>
                                                <th width="100">Kode</th>
                                                <th width="150">Jam</th>
                                                <th width="120">Ruangan</th>
                                                <th>Dosen</th>
                                                <th width="80" class="text-center">SKS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($hari_order as $hari): ?>
                                                <?php if (isset($jadwal_by_hari[$hari])): ?>
                                                    <?php foreach ($jadwal_by_hari[$hari] as $index => $jadwal): ?>
                                                        <tr>
                                                            <?php if ($index === 0): ?>
                                                                <td class="hari-header align-middle" rowspan="<?php echo count($jadwal_by_hari[$hari]); ?>">
                                                                    <strong><?php echo $hari; ?></strong>
                                                                </td>
                                                            <?php endif; ?>
                                                            <td>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($jadwal['nama_mk']); ?></div>
                                                                <small class="text-muted"><?php echo htmlspecialchars($jadwal['dosen_pengampu']); ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($jadwal['kode_mk']); ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="time-badge">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    <?php 
                                                                    echo date('H:i', strtotime($jadwal['jam_mulai'])) . ' - ' . 
                                                                         date('H:i', strtotime($jadwal['jam_selesai'])); 
                                                                    ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark">
                                                                    <i class="fas fa-door-open me-1"></i><?php echo htmlspecialchars($jadwal['ruangan']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($jadwal['dosen_pengampu']); ?></td>
                                                            <td class="text-center">
                                                                <span class="badge bg-primary"><?php echo $jadwal['sks']; ?> SKS</span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Mobile Card View (Visible only on Mobile) -->
                <div class="d-block d-md-none">
                    <?php if (empty($jadwal_by_hari)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Belum ada jadwal kuliah</h4>
                            <p class="text-muted">Jadwal perkuliahan akan tersedia setelah proses KRS selesai.</p>
                        </div>
                    <?php else: ?>
                        <h4 class="mb-3">Jadwal Per Hari</h4>
                        <?php foreach ($hari_order as $hari): ?>
                            <?php if (isset($jadwal_by_hari[$hari])): ?>
                                <div class="mobile-card">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="fas fa-calendar-day me-2"></i><?php echo $hari; ?>
                                            <span class="badge bg-light text-dark float-end"><?php echo count($jadwal_by_hari[$hari]); ?> mata kuliah</span>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($jadwal_by_hari[$hari] as $jadwal): ?>
                                            <div class="jadwal-item">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($jadwal['nama_mk']); ?></h6>
                                                    <span class="badge bg-primary"><?php echo $jadwal['sks']; ?> SKS</span>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-6">
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?php 
                                                            echo date('H:i', strtotime($jadwal['jam_mulai'])) . ' - ' . 
                                                                 date('H:i', strtotime($jadwal['jam_selesai'])); 
                                                            ?>
                                                        </small>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted">
                                                            <i class="fas fa-door-open me-1"></i><?php echo htmlspecialchars($jadwal['ruangan']); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($jadwal['dosen_pengampu']); ?>
                                                    </small>
                                                </div>
                                                
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        <i class="fas fa-code me-1"></i><?php echo htmlspecialchars($jadwal['kode_mk']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Additional Information -->
                <?php if (!empty($jadwal_by_hari)): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="table-card">
                            <div class="card-header bg-white border-bottom-0 py-3">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Informasi Jadwal
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-primary rounded-circle p-2 me-3">
                                                <i class="fas fa-book text-white"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Total Mata Kuliah</h6>
                                                <p class="text-muted mb-0"><?php echo $stmt->rowCount(); ?> mata kuliah</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-success rounded-circle p-2 me-3">
                                                <i class="fas fa-calendar-check text-white"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Hari Aktif</h6>
                                                <p class="text-muted mb-0"><?php echo count($jadwal_by_hari); ?> hari</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-warning rounded-circle p-2 me-3">
                                                <i class="fas fa-graduation-cap text-white"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Total SKS</h6>
                                                <p class="text-muted mb-0"><?php echo $total_sks; ?> SKS</p>
                                            </div>
                                        </div>
                                    </div>
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
    <script>
        // Highlight current day in the schedule
        document.addEventListener('DOMContentLoaded', function() {
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const today = new Date().getDay();
            const todayName = days[today];
            
            // Highlight today in desktop view
            const hariHeaders = document.querySelectorAll('.hari-header');
            hariHeaders.forEach(header => {
                if (header.textContent.trim() === todayName) {
                    header.style.background = 'linear-gradient(135deg, #f72585 0%, #7209b7 100%)';
                }
            });
            
            // Highlight today in mobile view
            const mobileHeaders = document.querySelectorAll('.mobile-card .card-header');
            mobileHeaders.forEach(header => {
                if (header.textContent.includes(todayName)) {
                    header.style.background = 'linear-gradient(135deg, #f72585 0%, #7209b7 100%)';
                }
            });
        });
    </script>
</body>
</html>