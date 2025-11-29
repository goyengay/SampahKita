<?php
include 'auth.php';
$auth = new Auth();
$auth->redirectIfNotLoggedIn();

include 'config.php';
$db = new Database();
$conn = $db->getConnection();

if ($_POST && $_SESSION['role'] == 'masyarakat') {
    $lokasi = $_POST['lokasi'];
    $deskripsi = $_POST['deskripsi'];
    $foto = '';

    // Handle file upload
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_extension = pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
            $foto = $filename;
        }
    }

    $query = "INSERT INTO laporan_sampah (id_pelapor, lokasi, deskripsi, foto) 
              VALUES (:id_pelapor, :lokasi, :deskripsi, :foto)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id_pelapor", $_SESSION['user_id']);
    $stmt->bindParam(":lokasi", $lokasi);
    $stmt->bindParam(":deskripsi", $deskripsi);
    $stmt->bindParam(":foto", $foto);
    
    if ($stmt->execute()) {
        $success = "Laporan berhasil dikirim!";
    } else {
        $error = "Gagal mengirim laporan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Laporan - Sistem Pengelolaan Sampah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(90deg, #1e7e34 0%, #2d9f3d 100%) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 1.4rem;
            letter-spacing: 1px;
        }

        .sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: sticky;
            top: 20px;
        }

        .list-group-item {
            border: none;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #333;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
            border-left-color: #1e7e34;
            padding-left: 1.5rem;
        }

        .list-group-item.active {
            background: linear-gradient(90deg, #1e7e34 0%, #2d9f3d 100%);
            border-left-color: #fff;
            color: white;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }

        .content-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(90deg, #1e7e34 0%, #2d9f3d 100%);
            color: white;
            padding: 20px;
            border: none;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-body {
            padding: 25px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #1e7e34;
            box-shadow: 0 0 0 0.2rem rgba(30, 126, 52, 0.15);
        }

        .btn-primary {
            background: linear-gradient(90deg, #1e7e34 0%, #2d9f3d 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 126, 52, 0.3);
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1e7e34;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title i {
            font-size: 2rem;
        }

        .container-main {
            padding: 30px 0;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            color: white;
        }

        .alert-danger {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: block;
            padding: 20px;
            background: linear-gradient(135deg, #e7f5f0 0%, #f0f9f5 100%);
            border: 2px dashed #1e7e34;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            background: linear-gradient(135deg, #d0ebe3 0%, #e0f3eb 100%);
            border-color: #2d9f3d;
        }

        .info-box {
            background: linear-gradient(135deg, #e7f5f0 0%, #f0f9f5 100%);
            border-left: 4px solid #1e7e34;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box i {
            color: #1e7e34;
            margin-right: 10px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: static;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-trash-alt"></i> SAMPAH KITA
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3" style="color: white; font-weight: 500;">
                        <i class="fas fa-user-circle"></i> <?php echo $_SESSION['nama_lengkap']; ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 col-md-4">
                <div class="sidebar list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <?php if ($_SESSION['role'] == 'masyarakat'): ?>
                        <a href="laporan.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-plus-circle"></i> Buat Laporan
                        </a>
                        <a href="jadwal.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-alt"></i> Jadwal Pengangkutan
                        </a>
                        <a href="retribusi.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-money-bill"></i> Retribusi Sampah
                        </a>
                    <?php elseif ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'petugas'): ?>
                        <a href="kelola_laporan.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-list"></i> Kelola Laporan
                        </a>
                        <a href="kelola_jadwal.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar"></i> Kelola Jadwal
                        </a>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a href="kelola_user.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-users"></i> Kelola User
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
                <!-- Page Title -->
                <div class="page-title">
                    <i class="fas fa-file-upload"></i> Buat Laporan Sampah
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-clipboard-list"></i> Form Laporan Pengaduan Sampah</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <div class="info-box">
                            <i class="fas fa-info-circle"></i>
                            <strong>Petunjuk:</strong> Silakan isi form berikut untuk melaporkan lokasi sampah yang bermasalah. Sertakan foto untuk membantu kami merespons lebih cepat.
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label" for="lokasi">
                                    <i class="fas fa-map-marker-alt"></i> Lokasi <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="lokasi" name="lokasi" class="form-control form-control-lg" 
                                       required placeholder="Contoh: Jl. Merdeka No. 123, RT 01/RW 02, Kelurahan...">
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="deskripsi">
                                    <i class="fas fa-pen"></i> Deskripsi Masalah <span class="text-danger">*</span>
                                </label>
                                <textarea id="deskripsi" name="deskripsi" class="form-control form-control-lg" 
                                          rows="5" required 
                                          placeholder="Jelaskan kondisi sampah di lokasi tersebut. Contoh: Sampah menumpuk di pinggir jalan, tidak ada kontainer sampah, bau tidak sedap, dll..."></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="foto">
                                    <i class="fas fa-image"></i> Upload Foto (Opsional)
                                </label>
                                <div class="file-input-wrapper">
                                    <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                                    <label for="foto" class="file-input-label">
                                        <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                        <div>Klik atau drag foto ke sini</div>
                                        <small class="text-muted d-block mt-2">Format: JPG, PNG, JPEG | Maksimal 2MB</small>
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-2">Foto akan membantu kami memproses laporan Anda lebih cepat</small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane"></i> Kirim Laporan
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Recent Reports Section -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Laporan Saya Terakhir</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $query = "SELECT * FROM laporan_sampah WHERE id_pelapor = :user_id ORDER BY created_at DESC LIMIT 5";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(":user_id", $_SESSION['user_id']);
                        $stmt->execute();
                        
                        if ($stmt->rowCount() > 0) {
                            echo '<div class="table-responsive">';
                            echo '<table class="table table-hover">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th><i class="fas fa-map-marker-alt"></i> Lokasi</th>';
                            echo '<th><i class="fas fa-calendar"></i> Tanggal</th>';
                            echo '<th><i class="fas fa-tag"></i> Status</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                            
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $badge_class = $row['status'] == 'selesai' ? 'badge bg-success' : 
                                             ($row['status'] == 'diproses' ? 'badge bg-info' : 'badge bg-warning text-dark');
                                $tanggal = date('d/m/Y H:i', strtotime($row['created_at']));
                                echo "<tr>";
                                echo "<td><strong>" . htmlspecialchars(substr($row['lokasi'], 0, 30)) . "</strong></td>";
                                echo "<td>$tanggal</td>";
                                echo "<td><span class='$badge_class'>" . ucfirst($row['status']) . "</span></td>";
                                echo "</tr>";
                            }
                            echo '</tbody>';
                            echo '</table>';
                            echo '</div>';
                        } else {
                            echo '<div class="text-center py-5">';
                            echo '<i class="fas fa-inbox fa-4x text-muted mb-3" style="opacity: 0.3;"></i>';
                            echo '<p class="text-muted fs-5">Anda belum membuat laporan apapun</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File input drag and drop
        const fileInput = document.getElementById('foto');
        const fileLabel = document.querySelector('.file-input-label');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileLabel.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileLabel.addEventListener(eventName, () => {
                fileLabel.style.background = 'linear-gradient(135deg, #c3e9dc 0%, #d9ede7 100%)';
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileLabel.addEventListener(eventName, () => {
                fileLabel.style.background = 'linear-gradient(135deg, #e7f5f0 0%, #f0f9f5 100%)';
            }, false);
        });

        fileLabel.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
        }, false);
    </script>
</body>
</html>
