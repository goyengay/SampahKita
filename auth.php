<?php
// ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include config dengan pengecekan
if (!class_exists('Database')) {
    require_once 'config.php';
}

class Auth {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($username, $password) {
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                return true;
            }
        }
        return false;
    }

    public function register($username, $password, $nama_lengkap, $alamat, $no_telepon) {
        $query = "INSERT INTO users SET username=:username, password=:password, 
                  nama_lengkap=:nama_lengkap, alamat=:alamat, no_telepon=:no_telepon, role='masyarakat'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":nama_lengkap", $nama_lengkap);
        $stmt->bindParam(":alamat", $alamat);
        $stmt->bindParam(":no_telepon", $no_telepon);
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bindParam(":password", $password_hash);

        return $stmt->execute();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function redirectIfNotLoggedIn() {
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit;
        }
    }

    public function logout() {
        session_destroy();
        header("Location: login.php");
        exit;
    }
}
?>