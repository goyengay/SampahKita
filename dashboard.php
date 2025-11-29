<?php
include 'auth.php';
$auth = new Auth();
$auth->redirectIfNotLoggedIn();

include 'config.php';
$db = new Database();
$conn = $db->getConnection();

// Get stats
$query_laporan = "SELECT COUNT(*) as total FROM laporan_sampah";
$stmt = $conn->prepare($query_laporan);
$stmt->execute();
$total_laporan = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query_jadwal = "SELECT COUNT(*) as total FROM jadwal_pengangkutan";
$stmt = $conn->prepare($query_jadwal);
$stmt->execute();
$total_jadwal = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get laporan status counts
$query_status = "SELECT status, COUNT(*) as count FROM laporan_sampah GROUP BY status";
$stmt = $conn->prepare($query_status);
$stmt->execute();
$status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data untuk chart
$status_labels = array();
$status_counts = array();
foreach ($status_data as $data) {
    $status_labels[] = ucfirst($data['status']);
    $status_counts[] = $data['count'];
}

// Get laporan per week
$query_weekly = "SELECT WEEK(created_at) as minggu, COUNT(*) as count FROM laporan_sampah WHERE created_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK) GROUP BY WEEK(created_at) ORDER BY minggu";
$stmt = $conn->prepare($query_weekly);
$stmt->execute();
$weekly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$weekly_labels = array();
$weekly_counts = array();
foreach ($weekly_data as $data) {
    $weekly_labels[] = "Minggu " . $data['minggu'];
    $weekly_counts[] = $data['count'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Pengelolaan Sampah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
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

        .stat-card {
            border-radius: 15px;
            border: none;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            color: white;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card-success {
            background: linear-gradient(135deg, #1e7e34 0%, #2d9f3d 100%);
        }

        .stat-card-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-card-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stat-card .card-body {
            padding: 25px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 3rem;
            opacity: 0.3;
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

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
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

        .badge {
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-diproses {
            background-color: #0dcaf0;
            color: #fff;
        }

        .badge-selesai {
            background-color: #198754;
            color: #fff;
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

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: static;
                margin-bottom: 20px;
            }

            .stat-number {
                font-size: 2rem;
            }

            .stat-icon {
                font-size: 2rem;
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
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <?php if ($_SESSION['role'] == 'masyarakat'): ?>
                        <a href="laporan.php" class="list-group-item list-group-item-action">
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
                    <i class="fas fa-chart-line"></i> Dashboard
                </div>

                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card stat-card-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-number"><?php echo $total_laporan; ?></div>
                                        <div class="stat-label">Total Laporan</div>
                                    </div>
                                    <i class="fas fa-clipboard-list stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card stat-card-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-number"><?php echo $total_jadwal; ?></div>
                                        <div class="stat-label">Jadwal Aktif</div>
                                    </div>
                                    <i class="fas fa-calendar stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="card stat-card stat-card-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="stat-number">Rp 500K</div>
                                        <div class="stat-label">Retribusi Bulan Ini</div>
                                    </div>
                                    <i class="fas fa-money-bill stat-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row mt-4">
                    <!-- Status Chart -->
                    <div class="col-md-6">
                        <div class="content-card">
                            <div class="card-header">
                                <h5><i class="fas fa-pie-chart"></i> Status Laporan</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Weekly Chart -->
                    <div class="col-md-6">
                        <div class="content-card">
                            <div class="card-header">
                                <h5><i class="fas fa-bar-chart"></i> Laporan 4 Minggu Terakhir</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="weeklyChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Reports for Citizens -->
                <?php if ($_SESSION['role'] == 'masyarakat'): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Laporan Terbaru Saya</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Lokasi</th>
                                        <th>Deskripsi</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM laporan_sampah WHERE id_pelapor = :user_id ORDER BY created_at DESC LIMIT 10";
                                    $stmt = $conn->prepare($query);
                                    $stmt->bindParam(":user_id", $_SESSION['user_id']);
                                    $stmt->execute();
                                    
                                    if ($stmt->rowCount() > 0) {
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $badge_class = $row['status'] == 'selesai' ? 'badge-selesai' : 
                                                         ($row['status'] == 'diproses' ? 'badge-diproses' : 'badge-pending');
                                            $tanggal = date('d/m/Y H:i', strtotime($row['created_at']));
                                            echo "
                                            <tr>
                                                <td><strong>" . htmlspecialchars($row['lokasi']) . "</strong></td>
                                                <td><small>" . htmlspecialchars(substr($row['deskripsi'], 0, 40)) . "...</small></td>
                                                <td><span class='badge $badge_class'>" . ucfirst($row['status']) . "</span></td>
                                                <td><small class='text-muted'>$tanggal</small></td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center text-muted py-4'>Belum ada laporan</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Statistics for Admin/Staff -->
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'petugas'): ?>
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-list-check"></i> Laporan Menunggu Perhatian</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Pelapor</th>
                                        <th>Lokasi</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT l.*, u.nama_lengkap FROM laporan_sampah l 
                                              JOIN users u ON l.id_pelapor = u.id 
                                              WHERE l.status != 'selesai' 
                                              ORDER BY l.created_at DESC LIMIT 10";
                                    $stmt = $conn->prepare($query);
                                    $stmt->execute();
                                    
                                    if ($stmt->rowCount() > 0) {
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            $badge_class = $row['status'] == 'diproses' ? 'badge-diproses' : 'badge-pending';
                                            echo "
                                            <tr>
                                                <td>" . htmlspecialchars($row['nama_lengkap']) . "</td>
                                                <td>" . htmlspecialchars($row['lokasi']) . "</td>
                                                <td><span class='badge $badge_class'>" . ucfirst($row['status']) . "</span></td>
                                                <td><a href='kelola_laporan.php?id={$row['id']}' class='btn btn-sm btn-outline-primary'><i class='fas fa-edit'></i> Lihat</a></td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center text-muted py-4'>Semua laporan sudah selesai</td></tr>";
                                    }
                                    ?>
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
    <script>
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_counts); ?>,
                    backgroundColor: [
                        '#ffc107',
                        '#0dcaf0',
                        '#198754'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 12, weight: 'bold' },
                            padding: 15
                        }
                    }
                }
            }
        });

        // Weekly Chart
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        const weeklyChart = new Chart(weeklyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($weekly_labels); ?>,
                datasets: [{
                    label: 'Jumlah Laporan',
                    data: <?php echo json_encode($weekly_counts); ?>,
                    borderColor: '#1e7e34',
                    backgroundColor: 'rgba(30, 126, 52, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#1e7e34',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            font: { size: 12, weight: 'bold' }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
