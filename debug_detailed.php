<?php
session_start();
include_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>Debug Sistem Akademik</h2>";

$database = new Database();
$db = $database->getConnection();

// Test koneksi database
echo "<h3 class='info'>1. Test Koneksi Database</h3>";
try {
    if ($db) {
        echo "<p class='success'>✓ Koneksi database BERHASIL</p>";
        echo "<p>Database: sistem_akademik</p>";
    } else {
        echo "<p class='error'>✗ Koneksi database GAGAL</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error koneksi: " . $e->getMessage() . "</p>";
}

// Test tabel mahasiswa
echo "<h3 class='info'>2. Test Tabel Mahasiswa</h3>";
try {
    $query = "SHOW TABLES LIKE 'mahasiswa'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✓ Tabel 'mahasiswa' ADA</p>";
        
        // Cek struktur tabel
        $query_struct = "DESCRIBE mahasiswa";
        $stmt_struct = $db->prepare($query_struct);
        $stmt_struct->execute();
        
        echo "<h4>Struktur Tabel Mahasiswa:</h4>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $stmt_struct->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Cek data mahasiswa
        $query_data = "SELECT * FROM mahasiswa";
        $stmt_data = $db->prepare($query_data);
        $stmt_data->execute();
        
        echo "<h4>Data Mahasiswa (" . $stmt_data->rowCount() . " records):</h4>";
        if ($stmt_data->rowCount() > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>NPM</th><th>Nama</th><th>Jurusan</th><th>Angkatan</th></tr>";
            while ($row = $stmt_data->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['npm'] . "</td>";
                echo "<td>" . $row['nama'] . "</td>";
                echo "<td>" . $row['jurusan'] . "</td>";
                echo "<td>" . $row['angkatan'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>✗ Tabel mahasiswa KOSONG</p>";
        }
        
    } else {
        echo "<p class='error'>✗ Tabel 'mahasiswa' TIDAK ADA</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error tabel: " . $e->getMessage() . "</p>";
}

// Test query dengan ID tertentu
echo "<h3 class='info'>3. Test Query dengan ID</h3>";
if (isset($_GET['test_id'])) {
    $test_id = $_GET['test_id'];
    echo "<p>Mencoba ID: " . $test_id . "</p>";
    
    try {
        $query = "SELECT * FROM mahasiswa WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $test_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p class='success'>✓ Data DITEMUKAN untuk ID: " . $test_id . "</p>";
            echo "<table>";
            foreach ($data as $key => $value) {
                echo "<tr><td><strong>" . $key . "</strong></td><td>" . $value . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>✗ Data TIDAK DITEMUKAN untuk ID: " . $test_id . "</p>";
            
            // Cek ID yang tersedia
            $query_all = "SELECT id, npm, nama FROM mahasiswa LIMIT 10";
            $stmt_all = $db->prepare($query_all);
            $stmt_all->execute();
            
            echo "<p>ID yang tersedia:</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>NPM</th><th>Nama</th></tr>";
            while ($row = $stmt_all->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['npm'] . "</td>";
                echo "<td>" . $row['nama'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error query: " . $e->getMessage() . "</p>";
    }
}

// Test semua tabel
echo "<h3 class='info'>4. Test Semua Tabel</h3>";
try {
    $query = "SHOW TABLES";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<p>Tabel yang ada dalam database:</p>";
    echo "<table>";
    echo "<tr><th>Table Name</th><th>Record Count</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $table_name = $row[0];
        
        $count_query = "SELECT COUNT(*) as count FROM " . $table_name;
        $count_stmt = $db->prepare($count_query);
        $count_stmt->execute();
        $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<tr>";
        echo "<td>" . $table_name . "</td>";
        echo "<td>" . $count . " records</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>