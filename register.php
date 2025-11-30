<?php
// ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session start di paling atas
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include file dengan require_once
require_once 'config.php';
require_once 'auth.php';

$auth = new Auth();

// Redirect jika sudah login
if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_POST && isset($_POST['nama']) && isset($_POST['username']) && isset($_POST['password']) 
    && isset($_POST['confirm_password']) && isset($_POST['alamat']) && isset($_POST['no_telepon'])) { // Tambahkan pengecekan ini
    
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $alamat = trim($_POST['alamat']); // Ambil Alamat
    $no_telepon = trim($_POST['no_telepon']);
    
    // 1. Validasi Sederhana
    if (empty($nama) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Semua field harus diisi.";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        // 2. Lakukan Registrasi menggunakan fungsi register() di class Auth
        // Asumsi role default untuk registrasi publik adalah 'masyarakat'
        $role = 'masyarakat'; 
        
        // PASTIKAN FUNGSI register() INI ADA DAN BERFUNGSI DI DALAM AUTH.PHP
        $result = $auth->register($username, $password, $nama, $alamat, $no_telepon); 
        
        if ($result === true) {
            $success = "Pendaftaran berhasil! Silakan login.";
            // Setelah berhasil, arahkan kembali ke halaman login setelah 3 detik
            header("Refresh: 3; URL=index.php"); 
        } elseif ($result === false) {
            $error = "Terjadi kesalahan saat menyimpan data. Coba lagi.";
        } else {
            // Asumsi $result berisi pesan error spesifik dari Auth (misal: "Username sudah terdaftar")
            $error = $result; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Pengelolaan Sampah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .register-container { max-width: 450px; margin: 50px auto; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="card">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">Registrasi Akun</h3>
                    <h5 class="text-center text-muted mb-4">Pengelolaan Sampah</h5>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required value="<?php echo isset($nama) ? htmlspecialchars($nama) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <input type="text" name="alamat" class="form-control" required> </div>
                        <div class="mb-3">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="no_telepon" class="form-control" required> </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Daftar Akun</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <small>Sudah punya akun? <a href="login.php">Login di sini</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>