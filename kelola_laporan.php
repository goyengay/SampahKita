<?php
include 'auth.php';
$auth = new Auth();
$auth->redirectIfNotLoggedIn();

// Pastikan hanya Admin atau Petugas yang dapat mengakses halaman ini
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'petugas') {
    header("Location: dashboard.php");
    exit;
}

include 'config.php';
$db = new Database();
$conn = $db->getConnection();

// --- LOGIKA UTAMA ---

// 1. Logika Update Status Laporan (Jika ada POST request dari form detail)
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $laporan_id = $_POST['laporan_id'];
    $new_status = $_POST['status'];
    $keterangan_petugas = $_POST['keterangan_petugas'];

    if (empty($laporan_id) || empty($new_status)) {
        $message = "ID Laporan dan Status harus diisi.";
        $message_type = 'danger';
    } else {
        try {
            $query = "UPDATE laporan_sampah SET status = :status, keterangan_petugas = :keterangan, updated_at = NOW() WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(":status", $new_status);
            $stmt->bindParam(":keterangan", $keterangan_petugas);
            $stmt->bindParam(":id", $laporan_id);

            if ($stmt->execute()) {
                $message = "Status laporan #{$laporan_id} berhasil diperbarui menjadi " . ucfirst($new_status) . ".";
                $message_type = 'success';
            } else {
                $message = "Gagal memperbarui status laporan.";
                $message_type = 'danger';
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// 2. Logika Menampilkan Halaman
$view_mode = 'list'; // Default: menampilkan daftar laporan
$laporan_detail = null;
$pelapor_detail = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $laporan_id = $_GET['id'];
    $view_mode = 'detail';

    // Ambil detail laporan dan info pelapor
    $query = "SELECT l.*, u.nama_lengkap, u.username, u.alamat, u.no_telepon 
              FROM laporan_sampah l 
              JOIN users u ON l.id_pelapor = u.id 
              WHERE l.id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id", $laporan_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $laporan_detail = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $view_mode = 'list'; // Kembali ke daftar jika ID tidak ditemukan
        $message = "Laporan tidak ditemukan.";
        $message_type = 'warning';
    }
}

// 3. Logika Tampilan Daftar (Jika view_mode == 'list')
$laporan_list = array();
if ($view_mode == 'list') {
    $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
    
    $query = "SELECT l.*, u.nama_lengkap FROM laporan_sampah l 
              JOIN users u ON l.id_pelapor = u.id";
              
    $where = [];
    $params = [];
    
    if (!empty($filter_status)) {
        $where[] = "l.status = :status";
        $params[':status'] = $filter_status;
    }

    if (count($where) > 0) {
        $query .= " WHERE " . implode(" AND ", $where);
    }

    $query .= " ORDER BY l.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $laporan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan - Sistem Pengelolaan Sampah</title>
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
        .content-card { background: white; border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); margin-bottom: 25px; overflow: hidden; transition: box-shadow 0.3s ease; }
        .content-card:hover { box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15); }
        .card-header { background: linear-gradient(90deg, #1e7e34 0%, #2d9f3d 100%); color: white; padding: 20px; border: none; }
        .card-header h5 { margin: 0; font-weight: 600; font-size: 1.1rem; }
        .table-responsive { border-radius: 10px; }
        .table thead th { border: none; font-weight: 600; color: #1e7e34; border-bottom: 2px solid #dee2e6; }
        .table tbody tr { border-bottom: 1px solid #dee2e6; transition: background-color 0.2s ease; }
        .table tbody tr:hover { background-color: #f8f9fa; }
        .badge { font-weight: 600; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; }
        .badge-pending { background-color: #ffc107; color: #000; }
        .badge-diproses { background-color: #0dcaf0; color: #fff; }
        .badge-selesai { background-color: #198754; color: #fff; }
        .badge-dibatalkan { background-color: #dc3545; color: #fff; }
        /* Detail Page Styling */
        .detail-info { padding: 15px; border-radius: 10px; background-color: #f8f9fa; border: 1px solid #e9ecef; }
        .detail-info h6 { color: #1e7e34; font-weight: 600; margin-bottom: 5px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
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
            <div class="col-lg-3 col-md-4">
                <div class="sidebar list-group">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="kelola_laporan.php" class="list-group-item list-group-item-action active">
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
                </div>
            </div>

            <div class="col-lg-9 col-md-8">
                <div class="page-title">
                    <i class="fas fa-list-check"></i> Kelola Laporan
                </div>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($view_mode == 'list'): ?>
                
                <div class="content-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-table"></i> Daftar Semua Laporan</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light dropdown-toggle text-dark" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                Filter: <?php echo empty($filter_status) ? 'Semua Status' : ucfirst($filter_status); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                <li><a class="dropdown-item" href="kelola_laporan.php">Semua Status</a></li>
                                <li><a class="dropdown-item" href="kelola_laporan.php?status=pending">Pending</a></li>
                                <li><a class="dropdown-item" href="kelola_laporan.php?status=diproses">Diproses</a></li>
                                <li><a class="dropdown-item" href="kelola_laporan.php?status=selesai">Selesai</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Pelapor</th>
                                        <th>Lokasi</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (count($laporan_list) > 0) {
                                        foreach ($laporan_list as $row) {
                                            $badge_class = '';
                                            switch ($row['status']) {
                                                case 'selesai': $badge_class = 'badge-selesai'; break;
                                                case 'diproses': $badge_class = 'badge-diproses'; break;
                                                default: $badge_class = 'badge-pending';
                                            }
                                            $tanggal = date('d/m/Y H:i', strtotime($row['created_at']));
                                            echo "
                                            <tr>
                                                <td>{$row['id']}</td>
                                                <td>" . htmlspecialchars($row['nama_lengkap']) . "</td>
                                                <td>" . htmlspecialchars($row['lokasi']) . "</td>
                                                <td><small class='text-muted'>$tanggal</small></td>
                                                <td><span class='badge $badge_class'>" . ucfirst($row['status']) . "</span></td>
                                                <td><a href='kelola_laporan.php?id={$row['id']}' class='btn btn-sm btn-primary'><i class='fas fa-eye'></i> Lihat</a></td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center text-muted py-4'>Tidak ada laporan sesuai filter.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php elseif ($view_mode == 'detail' && $laporan_detail): ?>

                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-clipboard-list"></i> Detail Laporan #<?php echo $laporan_detail['id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <h4>Informasi Laporan</h4>
                                <hr>
                                <div class="mb-3 detail-info">
                                    <h6>ID Laporan</h6>
                                    <p><?php echo $laporan_detail['id']; ?></p>
                                    <h6>Tanggal Dibuat</h6>
                                    <p><?php echo date('d M Y, H:i', strtotime($laporan_detail['created_at'])); ?></p>
                                    <h6>Status Saat Ini</h6>
                                    <?php 
                                        $badge_class = '';
                                        switch ($laporan_detail['status']) {
                                            case 'selesai': $badge_class = 'badge-selesai'; break;
                                            case 'diproses': $badge_class = 'badge-diproses'; break;
                                            default: $badge_class = 'badge-pending';
                                        }
                                    ?>
                                    <p><span class='badge <?php echo $badge_class; ?>'><?php echo ucfirst($laporan_detail['status']); ?></span></p>
                                    <h6>Lokasi Sampah</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($laporan_detail['lokasi']); ?></p>
                                    <small class="text-muted"><i class="fas fa-map-marker-alt"></i> Koordinat: <?php echo htmlspecialchars($laporan_detail['koordinat']); ?></small>
                                </div>
                                <div class="mb-3 detail-info">
                                    <h6>Deskripsi Masalah</h6>
                                    <p><?php echo nl2br(htmlspecialchars($laporan_detail['deskripsi'])); ?></p>
                                    <h6>Keterangan Petugas (Terakhir)</h6>
                                    <p class="text-primary"><?php echo nl2br(htmlspecialchars($laporan_detail['keterangan_petugas'] ? $laporan_detail['keterangan_petugas'] : 'Belum ada keterangan.')); ?></p>
                                </div>
                                <?php if (!empty($laporan_detail['foto'])): ?>
                                    <h6 class="mt-4">Foto Laporan</h6>
                                    <img src="uploads/<?php echo htmlspecialchars($laporan_detail['foto']); ?>" alt="Foto Laporan" class="img-fluid rounded shadow-sm">
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-lg-6">
                                <h4>Informasi Pelapor</h4>
                                <hr>
                                <div class="mb-4 detail-info">
                                    <h6>Nama Pelapor</h6>
                                    <p><?php echo htmlspecialchars($laporan_detail['nama_lengkap']); ?></p>
                                    <h6>Username</h6>
                                    <p><?php echo htmlspecialchars($laporan_detail['username']); ?></p>
                                    <h6>Alamat</h6>
                                    <p><?php echo htmlspecialchars($laporan_detail['alamat']); ?></p>
                                    <h6>No. Telepon</h6>
                                    <p><?php echo htmlspecialchars($laporan_detail['no_telepon']); ?></p>
                                </div>
                                
                                <h4>Ubah Status Laporan</h4>
                                <hr>
                                <form method="POST" action="kelola_laporan.php?id=<?php echo $laporan_detail['id']; ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="laporan_id" value="<?php echo $laporan_detail['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Ubah Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="pending" <?php echo $laporan_detail['status'] == 'pending' ? 'selected' : ''; ?>>Pending (Menunggu)</option>
                                            <option value="diproses" <?php echo $laporan_detail['status'] == 'diproses' ? 'selected' : ''; ?>>Diproses (Sedang Ditangani)</option>
                                            <option value="selesai" <?php echo $laporan_detail['status'] == 'selesai' ? 'selected' : ''; ?>>Selesai (Sudah Diatasi)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="keterangan_petugas" class="form-label">Keterangan Petugas</label>
                                        <textarea class="form-control" id="keterangan_petugas" name="keterangan_petugas" rows="4" placeholder="Masukkan keterangan penanganan (wajib saat status 'selesai')"><?php echo htmlspecialchars($laporan_detail['keterangan_petugas']); ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100"><i class="fas fa-save"></i> Simpan Perubahan</button>
                                    <a href="kelola_laporan.php" class="btn btn-outline-secondary w-100 mt-2"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                    <div class="empty-state content-card">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h5>Terjadi kesalahan atau laporan tidak ditemukan.</h5>
                        <p>Silakan kembali ke <a href="kelola_laporan.php">Daftar Laporan</a>.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>