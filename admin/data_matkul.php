<?php
include_once '../includes/auth.php';
include_once '../config/database.php';
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Handle tambah mata kuliah
if (isset($_POST['tambah'])) {
    $kode_mk = $_POST['kode_mk'];
    $nama_mk = $_POST['nama_mk'];
    $sks = $_POST['sks'];
    $semester = $_POST['semester'];
    $dosen_pengampu = $_POST['dosen_pengampu'];

    try {
        $query = "INSERT INTO mata_kuliah (kode_mk, nama_mk, sks, semester, dosen_pengampu) 
                  VALUES (:kode_mk, :nama_mk, :sks, :semester, :dosen_pengampu)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':kode_mk', $kode_mk);
        $stmt->bindParam(':nama_mk', $nama_mk);
        $stmt->bindParam(':sks', $sks);
        $stmt->bindParam(':semester', $semester);
        $stmt->bindParam(':dosen_pengampu', $dosen_pengampu);
        
        if ($stmt->execute()) {
            $success = "Mata kuliah berhasil ditambahkan!";
        }
    } catch (PDOException $exception) {
        $error = "Error: " . $exception->getMessage();
    }
}

// Handle edit mata kuliah
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $kode_mk = $_POST['kode_mk'];
    $nama_mk = $_POST['nama_mk'];
    $sks = $_POST['sks'];
    $semester = $_POST['semester'];
    $dosen_pengampu = $_POST['dosen_pengampu'];

    try {
        $query = "UPDATE mata_kuliah SET kode_mk = :kode_mk, nama_mk = :nama_mk, 
                 sks = :sks, semester = :semester, dosen_pengampu = :dosen_pengampu 
                 WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':kode_mk', $kode_mk);
        $stmt->bindParam(':nama_mk', $nama_mk);
        $stmt->bindParam(':sks', $sks);
        $stmt->bindParam(':semester', $semester);
        $stmt->bindParam(':dosen_pengampu', $dosen_pengampu);
        
        if ($stmt->execute()) {
            $success = "Data mata kuliah berhasil diperbarui!";
        }
    } catch (PDOException $exception) {
        $error = "Error: " . $exception->getMessage();
    }
}
// Handle success messages from URL parameters
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'edit') {
        $success = "Data mata kuliah berhasil diperbarui!";
    }
}
// Handle hapus mata kuliah
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    try {
        // Hapus dulu data jadwal dan nilai yang terkait
        $query_jadwal = "DELETE FROM jadwal_kuliah WHERE mata_kuliah_id = :id";
        $stmt_jadwal = $db->prepare($query_jadwal);
        $stmt_jadwal->bindParam(':id', $id);
        $stmt_jadwal->execute();
        
        $query_nilai = "DELETE FROM nilai WHERE mata_kuliah_id = :id";
        $stmt_nilai = $db->prepare($query_nilai);
        $stmt_nilai->bindParam(':id', $id);
        $stmt_nilai->execute();
        
        // Kemudian hapus mata kuliah
        $query = "DELETE FROM mata_kuliah WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $success = "Mata kuliah berhasil dihapus!";
        }
    } catch (PDOException $exception) {
        $error = "Error: " . $exception->getMessage();
    }
}

// Ambil data mata kuliah untuk ditampilkan
$query = "SELECT * FROM mata_kuliah ORDER BY semester ASC, kode_mk ASC";
$stmt = $db->prepare($query);
$stmt->execute();

// Ambil data untuk edit
$edit_matkul = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $query_edit = "SELECT * FROM mata_kuliah WHERE id = :id";
    $stmt_edit = $db->prepare($query_edit);
    $stmt_edit->bindParam(':id', $id);
    $stmt_edit->execute();
    $edit_matkul = $stmt_edit->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mata Kuliah - Sistem Akademik</title>
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
            padding: 10px 20px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
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
            color: var(--primary);
        }
        
        .table-card .table td {
            padding: 12px 15px;
            vertical-align: middle;
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
        
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
        }
        
        .semester-badge {
            background: linear-gradient(135deg, #4cc9f0 0%, #4361ee 100%);
            color: white;
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .sks-badge {
            background: linear-gradient(135deg, #4895ef 0%, #4cc9f0 100%);
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="h3 mb-0">
                                <i class="fas fa-book me-2 text-primary"></i>Data Mata Kuliah
                            </h1>
                            <p class="text-muted mb-0">Kelola data mata kuliah dan dosen pengampu</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
                                <i class="fas fa-plus me-2"></i>Tambah Mata Kuliah
                            </button>
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

                <!-- Data Table -->
                <div class="table-card">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kode MK</th>
                                    <th>Nama Mata Kuliah</th>
                                    <th class="text-center">SKS</th>
                                    <th class="text-center">Semester</th>
                                    <th>Dosen Pengampu</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
<?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
<tr>
    <td>
        <span class="fw-bold text-primary"><?php echo $row['kode_mk']; ?></span>
    </td>
    <td>
        <div class="fw-bold"><?php echo $row['nama_mk']; ?></div>
        <small class="text-muted"><?php echo $row['dosen_pengampu']; ?></small>
    </td>
    <td class="text-center">
        <span class="sks-badge"><?php echo $row['sks']; ?></span>
    </td>
    <td class="text-center">
        <span class="semester-badge">Semester <?php echo $row['semester']; ?></span>
    </td>
    <td>
        <div class="d-flex align-items-center">
            <div class="bg-light rounded-circle p-2 me-3">
                <i class="fas fa-user-tie text-primary"></i>
            </div>
            <div>
                <div class="fw-bold"><?php echo $row['dosen_pengampu']; ?></div>
                <small class="text-muted"><?php echo $row['kode_mk']; ?></small>
            </div>
        </div>
    </td>
    <td class="text-center">
        <div class="btn-group btn-group-sm" role="group">
            <a href="edit_matkul.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-action" title="Edit">
                <i class="fas fa-edit"></i>
            </a>
            <a href="?hapus=<?php echo $row['id']; ?>" class="btn btn-danger btn-action" 
               onclick="return confirm('Yakin ingin menghapus mata kuliah <?php echo addslashes($row['nama_mk']); ?>? Data jadwal dan nilai yang terkait juga akan dihapus.')" 
               title="Hapus">
                <i class="fas fa-trash"></i>
            </a>
        </div>
    </td>
</tr>
<?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Tambah Mata Kuliah -->
    <div class="modal fade" id="tambahModal" tabindex="-1" aria-labelledby="tambahModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahModalLabel">
                        <i class="fas fa-plus me-2"></i>Tambah Mata Kuliah
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="kode_mk" class="form-label">Kode Mata Kuliah</label>
                            <input type="text" class="form-control" id="kode_mk" name="kode_mk" required>
                        </div>
                        <div class="mb-3">
                            <label for="nama_mk" class="form-label">Nama Mata Kuliah</label>
                            <input type="text" class="form-control" id="nama_mk" name="nama_mk" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sks" class="form-label">SKS</label>
                                    <select class="form-select" id="sks" name="sks" required>
                                        <option value="">Pilih SKS</option>
                                        <option value="1">1 SKS</option>
                                        <option value="2">2 SKS</option>
                                        <option value="3">3 SKS</option>
                                        <option value="4">4 SKS</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="semester" class="form-label">Semester</label>
                                    <select class="form-select" id="semester" name="semester" required>
                                        <option value="">Pilih Semester</option>
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                            <option value="<?php echo $i; ?>">Semester <?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="dosen_pengampu" class="form-label">Dosen Pengampu</label>
                            <input type="text" class="form-control" id="dosen_pengampu" name="dosen_pengampu" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>