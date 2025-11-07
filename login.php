<?php
session_start();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: mahasiswa/dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Gunakan path yang benar
    include_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validasi input
    if (empty($username) || empty($password) || empty($role)) {
        $error = "Semua field harus diisi!";
    } else {
        if ($role == 'admin') {
            $query = "SELECT * FROM admin WHERE username = :username";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verifikasi password dengan password_verify
                if (password_verify($password, $admin['password'])) {
                    // Regenerate session ID untuk mencegah session fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $admin['id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['nama'] = $admin['nama'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['login_time'] = time();
                    
                    // Log aktivitas login (opsional)
                    // $this->logLoginActivity($admin['id'], 'admin', $_SERVER['REMOTE_ADDR']);
                    
                    header("Location: admin/dashboard.php");
                    exit();
                } else {
                    $error = "Username atau password salah!";
                }
            } else {
                $error = "Username atau password salah!";
            }
        } else if ($role == 'mahasiswa') {
            $query = "SELECT * FROM mahasiswa WHERE npm = :username";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verifikasi password dengan password_verify
                if (password_verify($password, $mahasiswa['password'])) {
                    // Regenerate session ID untuk mencegah session fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $mahasiswa['id'];
                    $_SESSION['username'] = $mahasiswa['npm'];
                    $_SESSION['nama'] = $mahasiswa['nama'];
                    $_SESSION['role'] = 'mahasiswa';
                    $_SESSION['login_time'] = time();
                    
                    header("Location: mahasiswa/dashboard.php");
                    exit();
                } else {
                    $error = "NPM atau password salah!";
                }
            } else {
                $error = "NPM atau password salah!";
            }
        }
    }
    
    // Delay untuk mencegah brute force (opsional)
    sleep(1);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Informasi Akademik</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }
        .demo-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <div class="login-header">
                        <h2 class="mb-2"><i class="fas fa-graduation-cap"></i></h2>
                        <h4 class="mb-0">Sistem Informasi Akademik</h4>
                        <p class="mb-0 opacity-75">Masuk ke akun Anda</p>
                    </div>
                    
                    <div class="login-body">
                        <?php if(isset($error) && !empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="loginForm">
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold">Username / NPM</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-user text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="username" name="username" 
                                           required autocomplete="username" autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control border-start-0" id="password" name="password" 
                                           required autocomplete="current-password">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="role" class="form-label fw-semibold">Login Sebagai</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Pilih Role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="mahasiswa">Mahasiswa</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-login text-white w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Masuk
                            </button>
                            
                            <div class="text-center">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>Sistem terjamin keamanannya
                                </small>
                            </div>
                        </form>
                        
                        <div class="demo-info">
                            <h6 class="fw-semibold mb-3"><i class="fas fa-info-circle me-2"></i>Informasi Demo</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small class="d-block fw-semibold">Admin</small>
                                    <small class="text-muted">Username: admin</small><br>
                                    <small class="text-muted">Password: password</small>
                                </div>
                                <div class="col-6">
                                    <small class="d-block fw-semibold">Mahasiswa</small>
                                    <small class="text-muted">NPM: 202101001</small><br>
                                    <small class="text-muted">Password: password</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validasi form client-side
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const role = document.getElementById('role').value;
            
            if (!username || !password || !role) {
                e.preventDefault();
                alert('Semua field harus diisi!');
                return false;
            }
        });

        // Auto-focus pada field yang sesuai berdasarkan role
        document.getElementById('role').addEventListener('change', function() {
            const usernameField = document.getElementById('username');
            if (this.value === 'admin') {
                usernameField.placeholder = 'Masukkan username admin';
            } else if (this.value === 'mahasiswa') {
                usernameField.placeholder = 'Masukkan NPM';
            }
        });
    </script>
</body>
</html>