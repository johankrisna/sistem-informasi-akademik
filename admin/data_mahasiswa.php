<?php
include_once '../includes/auth.php';
include_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Generate CSRF token
$csrf_token = generateCsrfToken();

// Handle tambah mahasiswa
if (isset($_POST['tambah']) && validateCsrfToken($_POST['csrf_token'])) {
    $npm = trim($_POST['npm']);
    $nama = trim($_POST['nama']);
    $jurusan = $_POST['jurusan'];
    $angkatan = $_POST['angkatan'];
    $email = trim($_POST['email']);
    $no_hp = trim($_POST['no_hp']);
    $password = $_POST['password'];

    // Validasi input
    if (empty($npm) || empty($nama) || empty($jurusan) || empty($angkatan) || empty($email) || empty($password)) {
        $error = "Semua field wajib diisi!";
    } elseif (!preg_match('/^\d{9}$/', $npm)) {
        $error = "NPM harus terdiri dari 9 digit angka!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif (!empty($no_hp) && !preg_match('/^\+?[\d\s\-\(\)]{10,}$/', $no_hp)) {
        $error = "Format nomor HP tidak valid!";
    } else {
        try {
            // Cek apakah NPM sudah ada
            $query_check = "SELECT id FROM mahasiswa WHERE npm = :npm";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->bindParam(':npm', $npm);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                $error = "NPM sudah terdaftar!";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $query = "INSERT INTO mahasiswa (npm, password, nama, jurusan, angkatan, email, no_hp) 
                          VALUES (:npm, :password, :nama, :jurusan, :angkatan, :email, :no_hp)";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':npm', $npm);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':nama', $nama);
                $stmt->bindParam(':jurusan', $jurusan);
                $stmt->bindParam(':angkatan', $angkatan);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':no_hp', $no_hp);
                
                if ($stmt->execute()) {
                    $success = "Mahasiswa berhasil ditambahkan!";
                    // Clear form
                    $_POST = array();
                }
            }
        } catch (PDOException $exception) {
            if ($exception->getCode() == 23000) {
                $error = "NPM sudah terdaftar dalam sistem!";
            } else {
                $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
            }
        }
    }
}

// Handle hapus mahasiswa
if (isset($_GET['hapus']) && !empty($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Validasi ID
    if (!is_numeric($id)) {
        $error = "ID tidak valid!";
    } else {
        try {
            // Mulai transaction
            $db->beginTransaction();
            
            // Hapus dulu data nilai yang terkait
            $query_nilai = "DELETE FROM nilai WHERE mahasiswa_id = :id";
            $stmt_nilai = $db->prepare($query_nilai);
            $stmt_nilai->bindParam(':id', $id);
            $stmt_nilai->execute();
            
            // Kemudian hapus mahasiswa
            $query = "DELETE FROM mahasiswa WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $db->commit();
                $success = "Mahasiswa berhasil dihapus!";
            } else {
                $db->rollBack();
                $error = "Gagal menghapus mahasiswa!";
            }
        } catch (PDOException $exception) {
            $db->rollBack();
            $error = "Tidak dapat menghapus mahasiswa karena masih terdapat data terkait!";
        }
    }
}

// Handle success messages from URL parameters
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'edit') {
        $success = "Data mahasiswa berhasil diperbarui!";
    }
}

// Handle error messages from URL parameters
if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}

// Ambil data mahasiswa untuk ditampilkan
try {
    $query = "SELECT * FROM mahasiswa ORDER BY angkatan DESC, nama ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_mahasiswa = $stmt->rowCount();
    $mahasiswa_data = $stmt->fetchAll(PDO::FETCH_ASSOC); // Simpan data untuk digunakan berulang
} catch (PDOException $e) {
    $error = "Error mengambil data mahasiswa: " . $e->getMessage();
    $total_mahasiswa = 0;
    $mahasiswa_data = [];
}

// Hitung statistik jurusan
$stat_jurusan = [];
try {
    $query_jurusan = "SELECT jurusan, COUNT(*) as jumlah FROM mahasiswa GROUP BY jurusan";
    $stmt_jurusan = $db->prepare($query_jurusan);
    $stmt_jurusan->execute();
    $stat_jurusan = $stmt_jurusan->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Skip error untuk statistik
}

// Hitung statistik dinamis
$angkatan_aktif = 0;
$data_lengkap = 0;

if ($total_mahasiswa > 0) {
    try {
        // Hitung jumlah angkatan aktif (5 tahun terakhir)
        $current_year = date('Y');
        $query_angkatan = "SELECT COUNT(DISTINCT angkatan) as total 
                          FROM mahasiswa 
                          WHERE angkatan >= :min_year";
        $stmt_angkatan = $db->prepare($query_angkatan);
        $min_year = $current_year - 5;
        $stmt_angkatan->bindParam(':min_year', $min_year);
        $stmt_angkatan->execute();
        $angkatan_aktif = $stmt_angkatan->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Hitung data lengkap (memiliki email dan no_hp)
        $query_lengkap = "SELECT COUNT(*) as total 
                         FROM mahasiswa 
                         WHERE email IS NOT NULL AND email != '' 
                         AND no_hp IS NOT NULL AND no_hp != ''";
        $stmt_lengkap = $db->prepare($query_lengkap);
        $stmt_lengkap->execute();
        $data_lengkap_count = $stmt_lengkap->fetch(PDO::FETCH_ASSOC)['total'];
        $data_lengkap = $total_mahasiswa > 0 ? round(($data_lengkap_count / $total_mahasiswa) * 100) : 0;
        
    } catch (PDOException $e) {
        // Default values jika error
        $angkatan_aktif = 4;
        $data_lengkap = 100;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa - Sistem Akademik</title>
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
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .btn-action:hover {
            transform: scale(1.05);
        }

        .search-box {
            max-width: 300px;
        }

        .password-toggle {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
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
                            <a class="nav-link active" href="data_mahasiswa.php">
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
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <div>
                        <h1 class="h2 mb-1">Data Mahasiswa</h1>
                        <p class="text-muted">Kelola data mahasiswa secara lengkap dan terorganisir</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
                            <i class="fas fa-plus me-2"></i>Tambah Mahasiswa
                        </button>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

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
                                        <i class="fas fa-chart-line text-success"></i>
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
                                            <i class="fas fa-graduation-cap"></i>
                                        </div>
                                        <div class="stat-number text-success"><?php echo count($stat_jurusan); ?></div>
                                        <div class="stat-title">Program Studi</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-university text-success"></i>
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
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <div class="stat-number text-info"><?php echo $angkatan_aktif; ?></div>
                                        <div class="stat-title">Angkatan Aktif</div>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-sync-alt text-info"></i>
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
                                            <i class="fas fa-chart-pie"></i>
                                        </div>
                                        <div class="stat-number text-warning"><?php echo $data_lengkap; ?>%</div>
                                        <div class="stat-title">Data Lengkap</div>
                                    </div>
                                    <div class="align-self-center">
                                        <?php if ($data_lengkap >= 80): ?>
                                            <i class="fas fa-check text-success"></i>
                                        <?php else: ?>
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Table -->
                <div class="table-card">
                    <div class="card-header bg-white border-bottom-0 py-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list me-2"></i>Daftar Mahasiswa
                                </h5>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <span class="badge badge-gradient"><?php echo $total_mahasiswa; ?> Mahasiswa</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($total_mahasiswa > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>NPM</th>
                                        <th>Nama Mahasiswa</th>
                                        <th>Jurusan</th>
                                        <th>Angkatan</th>
                                        <th>Kontak</th>
                                        <th width="120" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php foreach ($mahasiswa_data as $row): ?>
                                    <tr>
                                        <td class="text-muted"><?php echo $no++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['npm']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-light rounded-circle p-2 me-3">
                                                    <i class="fas fa-user text-primary"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($row['nama']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($row['jurusan']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($row['angkatan']); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['no_hp'])): ?>
                                                <small><i class="fas fa-phone me-1 text-muted"></i><?php echo htmlspecialchars($row['no_hp']); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="edit_mahasiswa.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-action" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-danger btn-action" onclick="return confirm('Apakah Anda yakin ingin menghapus mahasiswa <?php echo addslashes($row['nama']); ?>? Tindakan ini tidak dapat dibatalkan.')" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">Belum ada data mahasiswa</h4>
                                <p class="text-muted mb-4">Mulai dengan menambahkan data mahasiswa pertama Anda.</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
                                    <i class="fas fa-plus me-2"></i>Tambah Mahasiswa Pertama
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Distribution by Jurusan -->
                <?php if (count($stat_jurusan) > 0): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="table-card">
                            <div class="card-header bg-white border-bottom-0 py-3">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Distribusi Mahasiswa per Jurusan
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($stat_jurusan as $jurusan): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                            <div>
                                                <h6 class="mb-1"><?php echo $jurusan['jurusan']; ?></h6>
                                                <p class="mb-0 text-muted"><?php echo $jurusan['jumlah']; ?> mahasiswa</p>
                                            </div>
                                            <div class="text-primary">
                                                <i class="fas fa-user-graduate fa-2x"></i>
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

    <!-- Modal Tambah Mahasiswa -->
    <div class="modal fade" id="tambahModal" tabindex="-1" aria-labelledby="tambahModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Tambah Mahasiswa Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="formTambah">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="npm" class="form-label">NPM <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="npm" name="npm" 
                                           value="<?php echo isset($_POST['npm']) ? htmlspecialchars($_POST['npm']) : ''; ?>" 
                                           required pattern="[0-9]{9}" title="NPM harus 9 digit angka"
                                           placeholder="Masukkan 9 digit NPM">
                                    <div class="form-text">Contoh: 202101001 (9 digit angka)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama" name="nama" 
                                           value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" 
                                           required placeholder="Masukkan nama lengkap">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="jurusan" class="form-label">Jurusan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="jurusan" name="jurusan" required>
                                        <option value="">Pilih Jurusan</option>
                                        <option value="Teknik Informatika" <?php echo (isset($_POST['jurusan']) && $_POST['jurusan'] == 'Teknik Informatika') ? 'selected' : ''; ?>>Teknik Informatika</option>
                                        <option value="Sistem Informasi" <?php echo (isset($_POST['jurusan']) && $_POST['jurusan'] == 'Sistem Informasi') ? 'selected' : ''; ?>>Sistem Informasi</option>
                                        <option value="Teknik Komputer" <?php echo (isset($_POST['jurusan']) && $_POST['jurusan'] == 'Teknik Komputer') ? 'selected' : ''; ?>>Teknik Komputer</option>
                                        <option value="Manajemen Informatika" <?php echo (isset($_POST['jurusan']) && $_POST['jurusan'] == 'Manajemen Informatika') ? 'selected' : ''; ?>>Manajemen Informatika</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="angkatan" class="form-label">Angkatan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="angkatan" name="angkatan" required>
                                        <option value="">Pilih Angkatan</option>
                                        <?php for ($year = date('Y'); $year >= 2010; $year--): ?>
                                            <option value="<?php echo $year; ?>" <?php echo (isset($_POST['angkatan']) && $_POST['angkatan'] == $year) ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                           required placeholder="email@example.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="no_hp" class="form-label">No. HP</label>
                                    <input type="text" class="form-control" id="no_hp" name="no_hp" 
                                           value="<?php echo isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : ''; ?>"
                                           pattern="^\+?[\d\s\-\(\)]{10,}$" title="Format: 081234567890"
                                           placeholder="081234567890">
                                    <div class="form-text">Contoh: 081234567890</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required minlength="6" placeholder="Minimal 6 karakter">
                                <button type="button" class="btn btn-outline-secondary password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="generatePassword()">
                                    <i class="fas fa-key me-1"></i>Generate Password
                                </button>
                                Password minimal 6 karakter
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Batal
                        </button>
                        <button type="submit" name="tambah" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation and utilities
        document.addEventListener('DOMContentLoaded', function() {
            // Auto close alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Clear form when modal is hidden
            const tambahModal = document.getElementById('tambahModal');
            if (tambahModal) {
                tambahModal.addEventListener('hidden.bs.modal', function () {
                    document.getElementById('formTambah').reset();
                });
            }
        });

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let password = '';
            for (let i = 0; i < 10; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('password').value = password;
        }

        // Real-time validation
        document.getElementById('npm').addEventListener('input', function(e) {
            const npm = e.target.value;
            if (!/^\d{0,9}$/.test(npm)) {
                e.target.setCustomValidity('NPM harus berupa angka');
            } else {
                e.target.setCustomValidity('');
            }
        });

        document.getElementById('no_hp').addEventListener('input', function(e) {
            const no_hp = e.target.value;
            if (no_hp && !/^\+?[\d\s\-\(\)]{10,}$/.test(no_hp)) {
                e.target.setCustomValidity('Format nomor HP tidak valid');
            } else {
                e.target.setCustomValidity('');
            }
        });
    </script>
</body>
</html>