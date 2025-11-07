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

// Handle update data
if (isset($_POST['edit']) && validateCsrfToken($_POST['csrf_token'])) {
    $id = $_POST['id'];
    $npm = trim($_POST['npm']);
    $nama = trim($_POST['nama']);
    $jurusan = $_POST['jurusan'];
    $angkatan = $_POST['angkatan'];
    $email = trim($_POST['email']);
    $no_hp = trim($_POST['no_hp']);
    $password = $_POST['password'];

    // Validasi input
    if (empty($npm) || empty($nama) || empty($jurusan) || empty($angkatan) || empty($email)) {
        $error = "Semua field wajib diisi!";
    } elseif (!preg_match('/^\d{9}$/', $npm)) {
        $error = "NPM harus terdiri dari 9 digit angka!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (!empty($no_hp) && !preg_match('/^\+?[\d\s\-\(\)]{10,}$/', $no_hp)) {
        $error = "Format nomor HP tidak valid!";
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        try {
            // Cek apakah NPM sudah digunakan oleh mahasiswa lain
            $query_check = "SELECT id FROM mahasiswa WHERE npm = :npm AND id != :id";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->bindParam(':npm', $npm);
            $stmt_check->bindParam(':id', $id);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                $error = "NPM sudah digunakan oleh mahasiswa lain!";
            } else {
                if (!empty($password)) {
                    // Hash password baru
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE mahasiswa SET npm = :npm, nama = :nama, jurusan = :jurusan, 
                             angkatan = :angkatan, email = :email, no_hp = :no_hp, password = :password 
                             WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $hashed_password);
                } else {
                    $query = "UPDATE mahasiswa SET npm = :npm, nama = :nama, jurusan = :jurusan, 
                             angkatan = :angkatan, email = :email, no_hp = :no_hp 
                             WHERE id = :id";
                    $stmt = $db->prepare($query);
                }
                
                $stmt->bindParam(':id', $id);
                $stmt->bindParam(':npm', $npm);
                $stmt->bindParam(':nama', $nama);
                $stmt->bindParam(':jurusan', $jurusan);
                $stmt->bindParam(':angkatan', $angkatan);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':no_hp', $no_hp);
                
                if ($stmt->execute()) {
                    $success = "Data mahasiswa berhasil diperbarui!";
                    // Update data yang ditampilkan
                    $mahasiswa['npm'] = $npm;
                    $mahasiswa['nama'] = $nama;
                    $mahasiswa['jurusan'] = $jurusan;
                    $mahasiswa['angkatan'] = $angkatan;
                    $mahasiswa['email'] = $email;
                    $mahasiswa['no_hp'] = $no_hp;
                }
            }
        } catch (PDOException $exception) {
            if ($exception->getCode() == 23000) {
                $error = "NPM sudah digunakan oleh mahasiswa lain!";
            } else {
                $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
            }
        }
    }
}

// Ambil data untuk edit
$id = isset($_GET['id']) ? $_GET['id'] : '';
$mahasiswa = null;

if (empty($id)) {
    $error = "ID mahasiswa tidak tersedia!";
} else {
    try {
        $query_edit = "SELECT * FROM mahasiswa WHERE id = :id";
        $stmt_edit = $db->prepare($query_edit);
        $stmt_edit->bindParam(':id', $id);
        $stmt_edit->execute();
        
        if ($stmt_edit->rowCount() > 0) {
            $mahasiswa = $stmt_edit->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Data mahasiswa tidak ditemukan!";
        }
    } catch (PDOException $e) {
        $error = "Error mengambil data: " . $e->getMessage();
    }
}

// Jika tidak ada data, redirect kembali
if (!$mahasiswa && !empty($error) && !isset($_POST['edit'])) {
    header("Location: data_mahasiswa.php?error=" . urlencode($error));
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mahasiswa - Sistem Akademik</title>
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
        
        .password-toggle {
            cursor: pointer;
            border-left: 0;
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
                        <h1 class="h2 mb-1">Edit Data Mahasiswa</h1>
                        <p class="text-muted">Perbarui informasi data mahasiswa</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="data_mahasiswa.php" class="btn btn-secondary">
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

                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="form-card">
                            <div class="card-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-user-edit me-2"></i>
                                    Edit Data Mahasiswa
                                    <?php if ($mahasiswa): ?>
                                    - <?php echo htmlspecialchars($mahasiswa['nama']); ?>
                                    <?php endif; ?>
                                </h4>
                            </div>
                            <div class="card-body p-4">
                                <?php if ($mahasiswa): ?>
                                <form method="POST" id="editForm">
                                    <input type="hidden" name="id" value="<?php echo $mahasiswa['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label for="npm" class="form-label">NPM <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="npm" name="npm" 
                                                       value="<?php echo htmlspecialchars($mahasiswa['npm']); ?>" 
                                                       required pattern="[0-9]{9}" title="NPM harus 9 digit angka"
                                                       placeholder="Masukkan 9 digit NPM">
                                                <div class="form-text">Contoh: 202101001 (9 digit angka)</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="nama" name="nama" 
                                                       value="<?php echo htmlspecialchars($mahasiswa['nama']); ?>" 
                                                       required placeholder="Masukkan nama lengkap">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label for="jurusan" class="form-label">Jurusan <span class="text-danger">*</span></label>
                                                <select class="form-select" id="jurusan" name="jurusan" required>
                                                    <option value="">Pilih Jurusan</option>
                                                    <option value="Teknik Informatika" <?php echo $mahasiswa['jurusan'] == 'Teknik Informatika' ? 'selected' : ''; ?>>Teknik Informatika</option>
                                                    <option value="Sistem Informasi" <?php echo $mahasiswa['jurusan'] == 'Sistem Informasi' ? 'selected' : ''; ?>>Sistem Informasi</option>
                                                    <option value="Teknik Komputer" <?php echo $mahasiswa['jurusan'] == 'Teknik Komputer' ? 'selected' : ''; ?>>Teknik Komputer</option>
                                                    <option value="Manajemen Informatika" <?php echo $mahasiswa['jurusan'] == 'Manajemen Informatika' ? 'selected' : ''; ?>>Manajemen Informatika</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label for="angkatan" class="form-label">Angkatan <span class="text-danger">*</span></label>
                                                <select class="form-select" id="angkatan" name="angkatan" required>
                                                    <option value="">Pilih Angkatan</option>
                                                    <?php for ($year = date('Y'); $year >= 2010; $year--): ?>
                                                        <option value="<?php echo $year; ?>" <?php echo $mahasiswa['angkatan'] == $year ? 'selected' : ''; ?>>
                                                            <?php echo $year; ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($mahasiswa['email']); ?>" 
                                                       required placeholder="email@example.com">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label for="no_hp" class="form-label">No. HP</label>
                                                <input type="text" class="form-control" id="no_hp" name="no_hp" 
                                                       value="<?php echo htmlspecialchars($mahasiswa['no_hp']); ?>"
                                                       pattern="^\+?[\d\s\-\(\)]{10,}$" title="Format: 081234567890"
                                                       placeholder="081234567890">
                                                <div class="form-text">Contoh: 081234567890</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="password" class="form-label">Password Baru</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   minlength="6" placeholder="Kosongkan jika tidak ingin mengubah password">
                                            <button type="button" class="btn btn-outline-secondary password-toggle" onclick="togglePassword()">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="generatePassword()">
                                                <i class="fas fa-key me-1"></i>Generate Password
                                            </button>
                                            Isi hanya jika ingin mengubah password. Minimal 6 karakter.
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                        <a href="data_mahasiswa.php" class="btn btn-secondary btn-action me-md-2">
                                            <i class="fas fa-times me-1"></i> Batal
                                        </a>
                                        <button type="submit" name="edit" class="btn btn-primary btn-action">
                                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                        <h4 class="text-muted">Data tidak dapat dimuat</h4>
                                        <p class="text-muted mb-4">Data mahasiswa tidak ditemukan atau terjadi kesalahan.</p>
                                        <a href="data_mahasiswa.php" class="btn btn-primary">
                                            <i class="fas fa-arrow-left me-1"></i> Kembali ke Data Mahasiswa
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Info Card -->
                        <?php if ($mahasiswa): ?>
                        <div class="stat-card mt-4">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fas fa-info-circle me-2"></i>Informasi Data
                                        </h6>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">ID Mahasiswa:</span>
                                            <span class="fw-bold">#<?php echo $mahasiswa['id']; ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Terdaftar Sejak:</span>
                                            <span class="fw-bold">
                                                <?php 
                                                if (!empty($mahasiswa['created_at'])) {
                                                    echo date('d M Y', strtotime($mahasiswa['created_at']));
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
                                                if (!empty($mahasiswa['updated_at'])) {
                                                    echo date('d M Y H:i', strtotime($mahasiswa['updated_at']));
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="fw-bold text-primary mb-3">
                                            <i class="fas fa-shield-alt me-2"></i>Keamanan
                                        </h6>
                                        <div class="alert alert-warning mb-0">
                                            <small>
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                Pastikan data yang diinput sudah benar. Perubahan data akan langsung tersimpan di database.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form utilities
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
        document.addEventListener('DOMContentLoaded', function() {
            const npmInput = document.getElementById('npm');
            const noHpInput = document.getElementById('no_hp');
            const passwordInput = document.getElementById('password');

            // Auto close alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });

            if (npmInput) {
                npmInput.addEventListener('input', function(e) {
                    const npm = e.target.value;
                    if (!/^\d{0,9}$/.test(npm)) {
                        e.target.setCustomValidity('NPM harus berupa angka');
                    } else {
                        e.target.setCustomValidity('');
                    }
                });
            }

            if (noHpInput) {
                noHpInput.addEventListener('input', function(e) {
                    const no_hp = e.target.value;
                    if (no_hp && !/^\+?[\d\s\-\(\)]{10,}$/.test(no_hp)) {
                        e.target.setCustomValidity('Format nomor HP tidak valid');
                    } else {
                        e.target.setCustomValidity('');
                    }
                });
            }

            // Form validation
            const form = document.getElementById('editForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const npm = document.getElementById('npm').value;
                    const password = document.getElementById('password').value;
                    
                    if (!/^\d{9}$/.test(npm)) {
                        e.preventDefault();
                        alert('NPM harus terdiri dari 9 digit angka!');
                        return false;
                    }
                    
                    if (password && password.length < 6) {
                        e.preventDefault();
                        alert('Password minimal 6 karakter!');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>