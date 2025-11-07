<?php
include_once '../includes/auth.php';
include_once '../config/database.php';
redirectIfNotMahasiswa();

$database = new Database();
$db = $database->getConnection();

$mahasiswa_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Ambil data mahasiswa
$query = "SELECT * FROM mahasiswa WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $mahasiswa_id);
$stmt->execute();
$mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle update profil
if ($_POST) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $no_hp = trim($_POST['no_hp']);
    $jurusan = $_POST['jurusan'];
    
    // Handle password change jika diisi
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    if (empty($nama) || empty($email) || empty($jurusan)) {
        $error = "Field yang bertanda bintang (*) wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        try {
            if (!empty($password)) {
                // Hash password baru
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE mahasiswa SET nama = :nama, email = :email, no_hp = :no_hp, 
                         jurusan = :jurusan, password = :password WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $hashed_password);
            } else {
                // Update tanpa ganti password
                $query = "UPDATE mahasiswa SET nama = :nama, email = :email, no_hp = :no_hp, 
                         jurusan = :jurusan WHERE id = :id";
                $stmt = $db->prepare($query);
            }
            
            $stmt->bindParam(':nama', $nama);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':no_hp', $no_hp);
            $stmt->bindParam(':jurusan', $jurusan);
            $stmt->bindParam(':id', $mahasiswa_id);
            
            if ($stmt->execute()) {
                $success = "Profil berhasil diperbarui!";
                // Update session nama
                $_SESSION['nama'] = $nama;
                // Refresh data mahasiswa
                $stmt = $db->prepare("SELECT * FROM mahasiswa WHERE id = :id");
                $stmt->bindParam(':id', $mahasiswa_id);
                $stmt->execute();
                $mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $exception) {
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Sistem Akademik</title>
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
        
        .profile-card {
            background: white;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .profile-card .card-body {
            padding: 25px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 15px 15px 0 0;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .profile-avatar i {
            font-size: 50px;
            color: white;
        }
        
        .info-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }
        
        .section-title {
            border-left: 4px solid var(--primary);
            padding-left: 15px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        
        .info-item:hover {
            background-color: #f8f9fa;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary);
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
                            <a class="nav-link active" href="profil.php">
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
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <div>
                        <h1 class="h2 mb-1">Profil Saya</h1>
                        <p class="text-muted">Kelola informasi profil dan akun Anda</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="info-badge">
                            <i class="fas fa-id-card me-1"></i><?php echo $mahasiswa['npm']; ?>
                        </span>
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
                    <!-- Profile Info Card -->
                    <div class="col-lg-4 mb-4">
                        <div class="profile-card">
                            <div class="profile-header">
                                <div class="profile-avatar">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <h4 class="mb-2"><?php echo htmlspecialchars($mahasiswa['nama']); ?></h4>
                                <p class="mb-0 opacity-75"><?php echo htmlspecialchars($mahasiswa['jurusan']); ?></p>
                            </div>
                            <div class="card-body">
                                <h5 class="section-title">Informasi Pribadi</h5>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted">NPM</small>
                                        <div class="fw-bold"><?php echo htmlspecialchars($mahasiswa['npm']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted">Email</small>
                                        <div class="fw-bold"><?php echo htmlspecialchars($mahasiswa['email']); ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted">No. HP</small>
                                        <div class="fw-bold"><?php echo !empty($mahasiswa['no_hp']) ? htmlspecialchars($mahasiswa['no_hp']) : '<span class="text-muted">-</span>'; ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted">Angkatan</small>
                                        <div class="fw-bold"><?php echo htmlspecialchars($mahasiswa['angkatan']); ?></div>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h5 class="section-title">Status Akun</h5>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-shield-alt"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted">Status</small>
                                        <div><span class="badge bg-success">Aktif</span></div>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-icon">
                                        <i class="fas fa-calendar-plus"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted">Terdaftar Sejak</small>
                                        <div class="fw-bold"><?php echo date('d F Y', strtotime($mahasiswa['created_at'])); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Profile Form -->
                    <div class="col-lg-8 mb-4">
                        <div class="profile-card">
                            <div class="card-body">
                                <h4 class="card-title mb-4">
                                    <i class="fas fa-edit me-2"></i>Edit Profil
                                </h4>
                                
                                <form method="POST" id="profileForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="npm" class="form-label">NPM</label>
                                                <input type="text" class="form-control" id="npm" value="<?php echo htmlspecialchars($mahasiswa['npm']); ?>" disabled>
                                                <div class="form-text">NPM tidak dapat diubah</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="nama" name="nama" 
                                                       value="<?php echo htmlspecialchars($mahasiswa['nama']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo htmlspecialchars($mahasiswa['email']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="no_hp" class="form-label">No. HP</label>
                                                <input type="text" class="form-control" id="no_hp" name="no_hp" 
                                                       value="<?php echo htmlspecialchars($mahasiswa['no_hp']); ?>"
                                                       pattern="[0-9]{10,13}" title="Format: 081234567890">
                                                <div class="form-text">Contoh: 081234567890</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="jurusan" class="form-label">Jurusan <span class="text-danger">*</span></label>
                                                <select class="form-select" id="jurusan" name="jurusan" required>
                                                    <option value="Teknik Informatika" <?php echo $mahasiswa['jurusan'] == 'Teknik Informatika' ? 'selected' : ''; ?>>Teknik Informatika</option>
                                                    <option value="Sistem Informasi" <?php echo $mahasiswa['jurusan'] == 'Sistem Informasi' ? 'selected' : ''; ?>>Sistem Informasi</option>
                                                    <option value="Teknik Komputer" <?php echo $mahasiswa['jurusan'] == 'Teknik Komputer' ? 'selected' : ''; ?>>Teknik Komputer</option>
                                                    <option value="Manajemen Informatika" <?php echo $mahasiswa['jurusan'] == 'Manajemen Informatika' ? 'selected' : ''; ?>>Manajemen Informatika</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="angkatan" class="form-label">Angkatan</label>
                                                <input type="text" class="form-control" id="angkatan" value="<?php echo htmlspecialchars($mahasiswa['angkatan']); ?>" disabled>
                                                <div class="form-text">Angkatan tidak dapat diubah</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <h5 class="section-title">Ubah Password</h5>
                                    <p class="text-muted mb-4">Isi field di bawah hanya jika Anda ingin mengubah password.</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password" class="form-label">Password Baru</label>
                                                <input type="password" class="form-control" id="password" name="password" 
                                                       minlength="6" placeholder="Kosongkan jika tidak ingin mengubah">
                                                <div class="form-text">Minimal 6 karakter</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                       placeholder="Kosongkan jika tidak ingin mengubah">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex gap-2 mt-4">
                                        <a href="dashboard.php" class="btn btn-secondary px-4">
                                            <i class="fas fa-arrow-left me-2"></i>Kembali
                                        </a>
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('profileForm');
            
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const email = document.getElementById('email').value;
                
                // Email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('Format email tidak valid!');
                    return false;
                }
                
                // Password validation
                if (password && password.length < 6) {
                    e.preventDefault();
                    alert('Password minimal 6 karakter!');
                    return false;
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Password dan konfirmasi password tidak cocok!');
                    return false;
                }
            });

            // Auto close alerts after 5 seconds
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