<?php
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h3>Debug Data Mahasiswa</h3>";

// Cek semua mahasiswa
$query = "SELECT * FROM mahasiswa";
$stmt = $db->prepare($query);
$stmt->execute();

echo "<h4>Data Mahasiswa:</h4>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>NPM</th><th>Nama</th><th>Jurusan</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['npm'] . "</td>";
    echo "<td>" . $row['nama'] . "</td>";
    echo "<td>" . $row['jurusan'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Cek apakah ada data dengan ID tertentu
if (isset($_GET['check_id'])) {
    $id = $_GET['check_id'];
    $query = "SELECT * FROM mahasiswa WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    echo "<h4>Check ID: " . $id . "</h4>";
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Data ditemukan: " . $row['nama'];
    } else {
        echo "Data TIDAK ditemukan untuk ID: " . $id;
    }
}
?>

<br><br>
<form method="GET">
    Check ID: <input type="number" name="check_id">
    <button type="submit">Check</button>
</form>