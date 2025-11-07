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

// Ambil ID dari parameter URL
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Validasi ID
if (empty($id) || !is_numeric($id)) {
    header("Location: data_matkul.php?error=" . urlencode("ID mata kuliah tidak valid"));
    exit();
}

// Ambil data mata kuliah berdasarkan ID
try {
    $query = "SELECT * FROM mata_kuliah WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $matkul = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika data tidak ditemukan, redirect
    if (!$matkul) {
        header("Location: data_matkul.php?error=" . urlencode("Mata kuliah tidak ditemukan"));
        exit();
    }
} catch (PDOException $e) {
    header("Location: data_matkul.php?error=" . urlencode("Error mengambil data: " . $e->getMessage()));
    exit();
}

// Handle form submission
if (isset($_POST['edit'])) {
    // Validasi CSRF token
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $error = "Token keamanan tidak valid!";
    } else {
        $kode_mk = trim($_POST['kode_mk']);
        $nama_mk = trim($_POST['nama_mk']);
        $sks = $_POST['sks'];
        $semester = $_POST['semester'];
        $dosen_pengampu = trim($_POST['dosen_pengampu']);

        // Validasi input
        if (empty($kode_mk) || empty($nama_mk) || empty($sks) || empty($semester) || empty($dosen_pengampu)) {
            $error = "Semua field wajib diisi!";
        } elseif (!preg_match('/^[A-Z0-9]{4,10}$/', $kode_mk)) {
            $error = "Kode mata kuliah harus terdiri dari 4-10 karakter huruf/angka!";
        } elseif (strlen($nama_mk) < 3) {
            $error = "Nama mata kuliah minimal 3 karakter!";
        } elseif (strlen($dosen_pengampu) < 3) {
            $error = "Nama dosen pengampu minimal 3 karakter!";
        } else {
            try {
                // Cek apakah kode MK sudah digunakan oleh mata kuliah lain
                $query_check = "SELECT id FROM mata_kuliah WHERE kode_mk = :kode_mk AND id != :id";
                $stmt_check = $db->prepare($query_check);
                $stmt_check->bindParam(':kode_mk', $kode_mk);
                $stmt_check->bindParam(':id', $id);
                $stmt_check->execute();
                
                if ($stmt_check->rowCount() > 0) {
                    $error = "Kode mata kuliah '{$kode_mk}' sudah digunakan oleh mata kuliah lain!";
                } else {
                    // Cek apakah kolom updated_at ada di tabel
                    $check_columns = "SHOW COLUMNS FROM mata_kuliah LIKE 'updated_at'";
                    $stmt_columns = $db->query($check_columns);
                    $has_updated_at = $stmt_columns->rowCount() > 0;
                    
                    // Update data dengan atau tanpa updated_at
                    if ($has_updated_at) {
                        $query = "UPDATE mata_kuliah SET kode_mk = :kode_mk, nama_mk = :nama_mk, 
                                 sks = :sks, semester = :semester, dosen_pengampu = :dosen_pengampu,
                                 updated_at = NOW() 
                                 WHERE id = :id";
                    } else {
                        $query = "UPDATE mata_kuliah SET kode_mk = :kode_mk, nama_mk = :nama_mk, 
                                 sks = :sks, semester = :semester, dosen_pengampu = :dosen_pengampu
                                 WHERE id = :id";
                    }
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $id);
                    $stmt->bindParam(':kode_mk', $kode_mk);
                    $stmt->bindParam(':nama_mk', $nama_mk);
                    $stmt->bindParam(':sks', $sks, PDO::PARAM_INT);
                    $stmt->bindParam(':semester', $semester, PDO::PARAM_INT);
                    $stmt->bindParam(':dosen_pengampu', $dosen_pengampu);
                    
                    if ($stmt->execute()) {
                        // Redirect ke data_matkul.php dengan pesan sukses
                        header("Location: data_matkul.php?success=edit");
                        exit();
                    } else {
                        $error = "Gagal memperbarui data mata kuliah!";
                    }
                }
            } catch (PDOException $exception) {
                // Tampilkan error yang lebih spesifik untuk debugging
                if ($exception->getCode() == 23000) {
                    $error = "Kode mata kuliah '{$kode_mk}' sudah digunakan oleh mata kuliah lain!";
                } else {
                    $error = "Terjadi kesalahan database: " . $exception->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mata Kuliah - Sistem Akademik</title>
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
        
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
        }
        
        .form-card .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px;
            border: none;
        }
        
        .btn-action {
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #e1e5e9;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .info-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
        }
        
        .sks-badge {
            background: linear-gradient(135deg, #4895ef 0%, #4cc9f0 100%);
            color: white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .semester-badge {
            background: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);
            color: white;
            border-radius: 20px;
            padding: 8px 16px;
            font-weight: 600;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
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
                            <a class="nav-link active" href="data_matkul.php">
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
                        <h1 class="h2 mb-1">Edit Mata Kuliah</h1>
                        <p class="text-muted">Perbarui informasi mata kuliah</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="data_matkul.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
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

                <div class="row">
                    <!-- Form Edit -->
                    <div class="col-lg-8 mb-4">
                        <div class="form-card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-edit me-2"></i>
                                    Edit Mata Kuliah - <?php echo htmlspecialchars($matkul['nama_mk']); ?>
                                </h4>
                            </div>
                            <div class="card-body p-4">
                                <form method="POST" id="editForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label for="kode_mk" class="form-label">Kode Mata Kuliah <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="kode_mk" name="kode_mk" 
                                                       value="<?php echo htmlspecialchars($matkul['kode_mk']); ?>" 
                                                       required pattern="[A-Z0-9]{4,10}" 
                                                       title="Kode harus 4-10 karakter huruf/angka"
                                                       placeholder="Contoh: TI101">
                                                <div class="form-text">Format: 4-10 karakter huruf/angka (contoh: TI101, MAT102)</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label for="nama_mk" class="form-label">Nama Mata Kuliah <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="nama_mk" name="nama_mk" 
                                                       value="<?php echo htmlspecialchars($matkul['nama_mk']); ?>" 
                                                       required placeholder="Masukkan nama mata kuliah"
                                                       minlength="3">
                                                <div class="form-text">Minimal 3 karakter</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label for="sks" class="form-label">SKS <span class="text-danger">*</span></label>
                                                <select class="form-select" id="sks" name="sks" required>
                                                    <option value="">Pilih SKS</option>
                                                    <option value="1" <?php echo $matkul['sks'] == 1 ? 'selected' : ''; ?>>1 SKS</option>
                                                    <option value="2" <?php echo $matkul['sks'] == 2 ? 'selected' : ''; ?>>2 SKS</option>
                                                    <option value="3" <?php echo $matkul['sks'] == 3 ? 'selected' : ''; ?>>3 SKS</option>
                                                    <option value="4" <?php echo $matkul['sks'] == 4 ? 'selected' : ''; ?>>4 SKS</option>
                                                    <option value="6" <?php echo $matkul['sks'] == 6 ? 'selected' : ''; ?>>6 SKS (Praktikum)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                                                <select class="form-select" id="semester" name="semester" required>
                                                    <option value="">Pilih Semester</option>
                                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                                        <option value="<?php echo $i; ?>" <?php echo $matkul['semester'] == $i ? 'selected' : ''; ?>>
                                                            Semester <?php echo $i; ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="dosen_pengampu" class="form-label">Dosen Pengampu <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="dosen_pengampu" name="dosen_pengampu" 
                                               value="<?php echo htmlspecialchars($matkul['dosen_pengampu']); ?>" 
                                               required placeholder="Masukkan nama dosen pengampu"
                                               minlength="3">
                                        <div class="form-text">Minimal 3 karakter</div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                        <a href="data_matkul.php" class="btn btn-secondary btn-action me-md-2">
                                            <i class="fas fa-times me-1"></i> Batal
                                        </a>
                                        <button type="submit" name="edit" class="btn btn-primary btn-action">
                                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Info Panel -->
                    <div class="col-lg-4">
                        <div class="info-card mb-4">
                            <div class="card-body text-center">
                                <div class="sks-badge mx-auto mb-3">
                                    <?php echo $matkul['sks']; ?>
                                </div>
                                <h5 class="fw-bold text-primary"><?php echo htmlspecialchars($matkul['kode_mk']); ?></h5>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($matkul['nama_mk']); ?></p>
                                <span class="semester-badge">Semester <?php echo $matkul['semester']; ?></span>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="card-body">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Informasi Data
                                </h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">ID Mata Kuliah:</span>
                                    <span class="fw-bold">#<?php echo $matkul['id']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Dibuat Pada:</span>
                                    <span class="fw-bold">
                                        <?php 
                                        if (!empty($matkul['created_at'])) {
                                            echo date('d M Y', strtotime($matkul['created_at']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Terakhir Diupdate:</span>
                                    <span class="fw-bold">
                                        <?php 
                                        if (!empty($matkul['updated_at'])) {
                                            echo date('d M Y H:i', strtotime($matkul['updated_at']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="info-card mt-4">
                            <div class="card-body">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="fas fa-shield-alt me-2"></i>Panduan Edit
                                </h6>
                                <div class="alert alert-warning mb-0">
                                    <small>
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <strong>Perhatian:</strong> Perubahan kode mata kuliah akan mempengaruhi data nilai dan jadwal yang terkait.
                                    </small>
                                </div>
                                <ul class="mt-2 small text-muted">
                                    <li>Pastikan kode mata kuliah unik</li>
                                    <li>Nama mata kuliah harus deskriptif</li>
                                    <li>Pilih SKS sesuai bobot mata kuliah</li>
                                    <li>Semester harus sesuai kurikulum</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
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

            // Real-time validation for kode_mk
            const kodeMkInput = document.getElementById('kode_mk');
            if (kodeMkInput) {
                kodeMkInput.addEventListener('input', function(e) {
                    const kode_mk = e.target.value;
                    if (!/^[A-Z0-9]{0,10}$/.test(kode_mk)) {
                        e.target.setCustomValidity('Kode harus berupa huruf kapital atau angka, maksimal 10 karakter');
                    } else {
                        e.target.setCustomValidity('');
                    }
                });
            }

            // Form validation
            const form = document.getElementById('editForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const kode_mk = document.getElementById('kode_mk').value;
                    const nama_mk = document.getElementById('nama_mk').value;
                    const sks = document.getElementById('sks').value;
                    const semester = document.getElementById('semester').value;
                    const dosen_pengampu = document.getElementById('dosen_pengampu').value;
                    
                    if (!/^[A-Z0-9]{4,10}$/.test(kode_mk)) {
                        e.preventDefault();
                        alert('Kode mata kuliah harus 4-10 karakter huruf/angka!');
                        return false;
                    }
                    
                    if (nama_mk.trim().length < 3) {
                        e.preventDefault();
                        alert('Nama mata kuliah minimal 3 karakter!');
                        return false;
                    }
                    
                    if (!sks) {
                        e.preventDefault();
                        alert('Pilih SKS mata kuliah!');
                        return false;
                    }
                    
                    if (!semester) {
                        e.preventDefault();
                        alert('Pilih semester!');
                        return false;
                    }
                    
                    if (dosen_pengampu.trim().length < 3) {
                        e.preventDefault();
                        alert('Nama dosen pengampu minimal 3 karakter!');
                        return false;
                    }
                });
            }
        });

        // Auto-uppercase for kode_mk
        document.getElementById('kode_mk').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    </script>
</body>
</html>