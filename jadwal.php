<?php
include 'auth.php';
$auth = new Auth();
$auth->redirectIfNotLoggedIn();

include 'config.php';
$db = new Database();
$conn = $db->getConnection();

$query = "SELECT j.*, u.nama_lengkap as petugas 
          FROM jadwal_pengangkutan j 
          LEFT JOIN users u ON j.id_petugas = u.id 
          ORDER BY j.hari, j.jam";
$stmt = $conn->prepare($query);
$stmt->execute();
$jadwal = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Pengangkutan - Sistem Pengelolaan Sampah</title>
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

        .table-responsive {
            border-radius: 10px;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: #f8f9fa;
        }

        .table thead th {
            border: none;
            font-weight: 600;
            color: #1e7e34;
            border-bottom: 2px solid #dee2e6;
        }

        .table tbody tr {
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
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

        .jadwal-card {
            background: linear-gradient(135deg, #1e7e34 0%, #2d9f3d 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .jadwal-card:hover {
            transform: translateX(5px);
        }

        .jadwal-wilayah {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .jadwal-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .jadwal-detail {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: static;
                margin-bottom: 20px;
            }

            .jadwal-info {
                flex-direction: column;
                align-items: flex-start;
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
                        <a href="laporan.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle"></i> Buat Laporan
                        </a>
                        <a href="jadwal.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-calendar-alt"></i> Jadwal Pengangkutan
                        </a>
                        <a href="retribusi.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-money-bill"></i> Retribusi Sampah
                        </a>
                    <?php elseif ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'petugas'): ?>
                        <a href="kelola_laporan.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-list"></i> Kelola Laporan
                        </a>
                        <a href="kelola_jadwal.php" class="list-group-item list-group-item-action active">
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
                    <i class="fas fa-calendar-alt"></i> Jadwal Pengangkutan Sampah
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-check"></i> Daftar Jadwal Pengangkutan</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($jadwal) > 0): ?>
                            <div class="row">
                                <?php foreach ($jadwal as $j): ?>
                                    <div class="col-md-6 col-lg-6">
                                        <div class="jadwal-card">
                                            <div class="jadwal-wilayah">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($j['wilayah']); ?>
                                            </div>
                                            <div class="jadwal-info">
                                                <div class="jadwal-detail">
                                                    <strong><i class="fas fa-calendar"></i> Hari:</strong> <?php echo ucfirst($j['hari']); ?>
                                                </div>
                                                <div class="jadwal-detail">
                                                    <strong><i class="fas fa-clock"></i> Jam:</strong> <?php echo date('H:i', strtotime($j['jam'])); ?>
                                                </div>
                                            </div>
                                            <div class="jadwal-info">
                                                <div class="jadwal-detail">
                                                    <strong><i class="fas fa-user"></i> Petugas:</strong> 
                                                    <?php echo $j['petugas'] ? htmlspecialchars($j['petugas']) : '<span class="badge bg-warning">Belum ditugaskan</span>'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-alt fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
                                <p class="text-muted fs-5">Belum ada jadwal pengangkutan</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Alternative Table View -->
                <?php if (count($jadwal) > 0): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-table"></i> Tampilan Tabel</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-map-marker-alt"></i> Wilayah</th>
                                        <th><i class="fas fa-calendar"></i> Hari</th>
                                        <th><i class="fas fa-clock"></i> Jam</th>
                                        <th><i class="fas fa-user"></i> Petugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jadwal as $j): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($j['wilayah']); ?></td>
                                        <td><?php echo ucfirst($j['hari']); ?></td>
                                        <td><?php echo date('H:i', strtotime($j['jam'])); ?></td>
                                        <td>
                                            <?php echo $j['petugas'] ? htmlspecialchars($j['petugas']) : '<span class="text-muted">Belum ditugaskan</span>'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
