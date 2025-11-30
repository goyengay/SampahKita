<?php
// ERROR REPORTING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'auth.php';
$auth = new Auth();
$auth->redirectIfNotLoggedIn();

// Pastikan hanya role Masyarakat yang dapat mengakses halaman ini
if ($_SESSION['role'] != 'masyarakat') {
    header("Location: dashboard.php");
    exit;
}

include 'config.php';
$db = new Database();
$conn = $db->getConnection();

$current_page = basename($_SERVER['PHP_SELF']);
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// --- LOGIKA UTAMA ---

// 1. Ambil Riwayat Retribusi Warga
$query_retribusi = "SELECT * FROM retribusi WHERE id_warga = :id_warga ORDER BY bulan_tahun DESC";
$stmt = $conn->prepare($query_retribusi);
$stmt->bindParam(":id_warga", $user_id);
$stmt->execute();
$retribusi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Ambil Statistik Ringkasan (Opsional, untuk tampilan Dashboard Warga)
$query_summary = "
    SELECT 
        COUNT(CASE WHEN status = 'belum bayar' THEN 1 END) as total_belum_bayar,
        COALESCE(SUM(CASE WHEN status = 'belum bayar' THEN jumlah ELSE 0 END), 0) as total_nominal_belum_bayar,
        COUNT(CASE WHEN status = 'lunas' THEN 1 END) as total_lunas
    FROM retribusi 
    WHERE id_warga = :id_warga";
$stmt_summary = $conn->prepare($query_summary);
$stmt_summary->bindParam(":id_warga", $user_id);
$stmt_summary->execute();
$summary = $stmt_summary->fetch(PDO::FETCH_ASSOC);

// Helper function to format currency
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retribusi Sampah - Sistem Pengelolaan Sampah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background: linear-gradient(90deg, #1e7e34 0%, #2d9f3d 100%) !important; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
        .navbar-brand { font-weight: bold; font-size: 1.4rem; letter-spacing: 1px; }
        .sidebar { background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); overflow: hidden; position: sticky; top: 20px; }
        .list-group-item { border: none; border-left: 4px solid transparent; transition: all 0.3s ease; font-weight: 500; color: #333; }
        .list-group-item:hover { background-color: #f8f9fa; border-left-color: #1e7e34; padding-left: 1.5rem; }
        .list-group-item.active { background: linear-gradient(90deg, #1e7e34 0%, #2d9f3d 100%); border-left-color: #fff; color: white; }
        .page-title { font-size: 1.8rem; font-weight: bold; color: #1e7e34; margin-bottom: 30px; display: flex; align-items: center; gap: 10px; }
        .page-title i { font-size: 2rem; }
        .container-main { padding: 30px 0; }
        .content-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); margin-bottom: 25px; overflow: hidden; }
        .card-header { background: linear-gradient(90deg, #1e7e34 0%, #2d9f3d 100%); color: white; padding: 20px; border: none; }
        .card-header h5 { margin: 0; font-weight: 600; font-size: 1.1rem; }
        .table thead th { border: none; font-weight: 600; color: #1e7e34; border-bottom: 2px solid #dee2e6; }
        .table tbody tr:hover { background-color: #f8f9fa; }

        .stat-card-danger { background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%); color: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
        .stat-card-success { background: linear-gradient(135deg, #198754 0%, #2d9f3d 100%); color: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
        .stat-number { font-size: 2.0rem; font-weight: bold; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; }
        .badge-lunas { background-color: #198754; color: white; }
        .badge-belum-bayar { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-trash-alt"></i> SAMPAH KITA
            </a>
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
            <div class="col-lg-3 col-md-4">
                <div class="sidebar list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <?php if ($_SESSION['role'] == 'masyarakat'): ?>
                        <a href="laporan.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'laporan.php') ? 'active' : ''; ?>">
                            <i class="fas fa-plus-circle"></i> Buat Laporan
                        </a>
                        <a href="jadwal.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'jadwal.php') ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i> Jadwal Pengangkutan
                        </a>
                        <a href="retribusi.php" class="list-group-item list-group-item-action <?php echo ($current_page == 'retribusi.php') ? 'active' : ''; ?>">
                            <i class="fas fa-money-bill"></i> Retribusi Sampah
                        </a>
                    <?php endif; ?>
                    </div>
            </div>

            <div class="col-lg-9 col-md-8">
                <div class="page-title">
                    <i class="fas fa-money-bill"></i> Retribusi Sampah
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card stat-card-danger p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?php echo formatRupiah($summary['total_nominal_belum_bayar']); ?></div>
                                    <div class="stat-label">Total Tunggakan (<?php echo $summary['total_belum_bayar']; ?> Bulan)</div>
                                </div>
                                <i class="fas fa-exclamation-triangle stat-icon" style="font-size: 2.5rem; opacity: 0.5;"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card stat-card-success p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?php echo $summary['total_lunas']; ?></div>
                                    <div class="stat-label">Total Pembayaran Lunas</div>
                                </div>
                                <i class="fas fa-check-circle stat-icon" style="font-size: 2.5rem; opacity: 0.5;"></i>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-table"></i> Riwayat Tagihan Retribusi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Bulan/Tahun</th>
                                        <th>Jumlah (Rp)</th>
                                        <th>Status</th>
                                        <th>Tanggal Dicatat</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (count($retribusi_list) > 0) {
                                        foreach ($retribusi_list as $row) {
                                            // Format Bulan/Tahun
                                            $bulan_tahun = date('F Y', strtotime($row['bulan_tahun'] . '-01'));
                                            
                                            // Status Badge
                                            if ($row['status'] == 'lunas') {
                                                $badge = "<span class='badge badge-lunas'><i class='fas fa-check'></i> Lunas</span>";
                                                $keterangan = "Pembayaran telah dicatat.";
                                            } else {
                                                $badge = "<span class='badge badge-belum-bayar'><i class='fas fa-times'></i> Belum Bayar</span>";
                                                $keterangan = "<a href='#' class='text-danger' data-bs-toggle='modal' data-bs-target='#paymentModal'>Cara Bayar</a>";
                                            }

                                            echo "
                                            <tr>
                                                <td>{$row['id']}</td>
                                                <td><strong>" . htmlspecialchars($bulan_tahun) . "</strong></td>
                                                <td>" . formatRupiah($row['jumlah']) . "</td>
                                                <td>{$badge}</td>
                                                <td>" . date('d/m/Y', strtotime($row['created_at'])) . "</td>
                                                <td>{$keterangan}</td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center text-muted py-4'>Belum ada riwayat tagihan retribusi yang dicatat.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="paymentModalLabel"><i class="fas fa-info-circle"></i> Informasi Pembayaran</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Untuk saat ini, pembayaran retribusi sampah dilakukan melalui **koordinator RT/RW** setempat atau transfer ke rekening resmi:</p>
            <blockquote class="blockquote">
                <p class="mb-0">Bank XYZ: **123-456-789 (a/n Pengelola Sampah)**</p>
                <footer class="blockquote-footer">Mohon konfirmasi pembayaran kepada petugas setelah transfer.</footer>
            </blockquote>
            <p class="text-danger">Aplikasi ini hanya berfungsi sebagai catatan riwayat tagihan Anda.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>