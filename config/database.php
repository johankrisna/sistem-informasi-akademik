<?php
class Database {
    private $host = "localhost";
    private $db_name = "sistem_akademik";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // Log error instead of displaying to user
            error_log("Database connection error: " . $exception->getMessage());
            // Display generic error message
            die("Terjadi kesalahan sistem. Silakan coba lagi nanti.");
        }
        return $this->conn;
    }
}
?>