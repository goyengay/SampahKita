<?php
session_start();

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek apakah functions.php ada dan bisa di-load
if (!file_exists('functions.php')) {
    die("File functions.php tidak ditemukan!");
}

require 'functions.php';

// Cek apakah user sudah login
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$user_type = $_SESSION['user_type'] ?? 'warga'; // Default ke warga jika tidak ada

// Inisialisasi variabel
$retribusi = [];
$total_retribusi = 0;
$total_retribusi_formatted = "Rp 0";
$keyword = '';

try {
    // Query untuk mendapatkan data retribusi - SESUAIKAN DENGAN STRUCTURE DATABASE
    $query = "SELECT r.*, u.nama as nama_petugas, w.nama as nama_warga 
              FROM retribusi r 
              LEFT JOIN users u ON r.id_petugas = u.id 
              LEFT JOIN warga w ON r.id_warga = w.id 
              ORDER BY r.created_at DESC";
    
    $retribusi = query($query);

    // Hitung total retribusi
    $total_query = "SELECT SUM(jumlah) as total_retribusi FROM retribusi WHERE status = 'lunas'";
    $total_result = query($total_query);
    $total_retribusi = $total_result[0]['total_retribusi'] ?? 0;

    // Format total retribusi
    $total_retribusi_formatted = "Rp " . number_format($total_retribusi, 0, ',', '.');

    // Handle pencarian
    if (isset($_GET['search']) && !empty($_GET['keyword'])) {
        $keyword = $_GET['keyword'];
        $search_query = "SELECT r.*, u.nama as nama_petugas, w.nama as nama_warga 
                        FROM retribusi r 
                        LEFT JOIN users u ON r.id_petugas = u.id 
                        LEFT JOIN warga w ON r.id_warga = w.id 
                        WHERE w.nama LIKE '%$keyword%' OR r.bulan_tahun LIKE '%$keyword%'
                        ORDER BY r.created_at DESC";
        $retribusi = query($search_query);
    }

    // Handle filter bulan
    if (isset($_GET['filter_bulan']) && !empty($_GET['bulan'])) {
        $bulan = $_GET['bulan'];
        $filter_query = "SELECT r.*, u.nama as nama_petugas, w.nama as nama_warga 
                        FROM retribusi r 
                        LEFT JOIN users u ON r.id_petugas = u.id 
                        LEFT JOIN warga w ON r.id_warga = w.id 
                        WHERE r.bulan_tahun LIKE '%$bulan%'
                        ORDER BY r.created_at DESC";
        $retribusi = query($filter_query);
    }
} catch (Exception $e) {
    // Tangani error dengan graceful degradation
    error_log("Error in retribusi.php: " . $e->getMessage());
    $error_message = "Terjadi kesalahan dalam memuat data. Silakan coba lagi.";
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Retribusi - SampahKita</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .retribusi-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .retribusi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .retribusi-title {
            color: #2c3e50;
            margin: 0;
        }

        .retribusi-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #27ae60;
        }

        .stat-card.total {
            border-left-color: #e74c3c;
        }

        .stat-card.pending {
            border-left-color: #f39c12;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .search-filter {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            min-width: 150px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background-color: #219a52;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .retribusi-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        .table th {
            background-color: #34495e;
            color: white;
            font-weight: 600;
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .status-lunas {
            background-color: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            display: inline-block;
        }

        .status-tunggak {
            background-color: #f8d7da;
            color: #721c24;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            display: inline-block;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-edit {
            background-color: #f39c12;
            color: white;
        }

        .btn-edit:hover {
            background-color: #e67e22;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 3em;
            margin-bottom: 10px;
            color: #bdc3c7;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }

        @media (max-width: 768px) {
            .retribusi-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-form, .filter-form {
                flex-direction: column;
            }
            
            .search-input {
                min-width: auto;
            }
            
            .table {
                font-size: 0.9em;
            }
            
            .table th, .table td {
                padding: 10px 5px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Cek apakah file header ada
    if (file_exists('templates/header.php')) {
        include 'templates/header.php'; 
    } else {
        echo "<header style='background:#34495e;color:white;padding:1rem;'><h1>SampahKita - Data Retribusi</h1></header>";
    }
    ?>

    <div class="retribusi-container">
        <!-- Error Message -->
        <?php if (isset($error_message)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <?= $error_message ?>
        </div>
        <?php endif; ?>

        <div class="retribusi-header">
            <h1 class="retribusi-title">
                <i class="fas fa-money-bill-wave"></i>
                Data Retribusi Sampah
            </h1>
            <?php if ($user_type === 'admin' || $user_type === 'petugas'): ?>
            <a href="tambah_retribusi.php" class="btn btn-success">
                <i class="fas fa-plus"></i>
                Tambah Retribusi
            </a>
            <?php endif; ?>
        </div>

        <!-- Statistik -->
        <div class="retribusi-stats">
            <div class="stat-card">
                <div class="stat-label">Total Retribusi</div>
                <div class="stat-number"><?= $total_retribusi_formatted ?></div>
                <small>Seluruh periode</small>
            </div>
            <div class="stat-card total">
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-number"><?= count($retribusi) ?></div>
                <small>Data retribusi</small>
            </div>
            <div class="stat-card pending">
                <div class="stat-label">Rata-rata per Transaksi</div>
                <div class="stat-number">
                    Rp <?= count($retribusi) > 0 ? number_format($total_retribusi / count($retribusi), 0, ',', '.') : 0 ?>
                </div>
                <small>Per transaksi</small>
            </div>
        </div>

        <!-- Pencarian dan Filter -->
        <div class="search-filter">
            <form method="GET" class="search-form">
                <input type="text" name="keyword" class="search-input" placeholder="Cari berdasarkan nama warga atau bulan..." value="<?= htmlspecialchars($keyword) ?>">
                <button type="submit" name="search" class="btn btn-primary">
                    <i class="fas fa-search"></i> Cari
                </button>
                <?php if (!empty($keyword)): ?>
                <a href="retribusi.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </a>
                <?php endif; ?>
            </form>

            <form method="GET" class="filter-form">
                <select name="bulan" class="filter-select">
                    <option value="">Pilih Bulan</option>
                    <option value="01">Januari</option>
                    <option value="02">Februari</option>
                    <option value="03">Maret</option>
                    <option value="04">April</option>
                    <option value="05">Mei</option>
                    <option value="06">Juni</option>
                    <option value="07">Juli</option>
                    <option value="08">Agustus</option>
                    <option value="09">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>
                <button type="submit" name="filter_bulan" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <?php if (isset($_GET['filter_bulan'])): ?>
                <a href="retribusi.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset Filter
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel Data Retribusi -->
        <div class="retribusi-table">
            <?php if (count($retribusi) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal Input</th>
                        <th>Nama Warga</th>
                        <th>Bulan Tahun</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        <th>Petugas</th>
                        <?php if ($user_type === 'admin' || $user_type === 'petugas'): ?>
                        <th>Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; ?>
                    <?php foreach ($retribusi as $row): ?>
                    <tr>
                        <td><?= $i ?></td>
                        <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_warga'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['bulan_tahun']) ?></td>
                        <td>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></td>
                        <td>
                            <span class="status-<?= $row['status'] === 'lunas' ? 'lunas' : 'tunggak' ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['nama_petugas'] ?? '-') ?></td>
                        <?php if ($user_type === 'admin' || $user_type === 'petugas'): ?>
                        <td class="action-buttons">
                            <a href="edit_retribusi.php?id=<?= $row['id'] ?>" class="btn btn-edit btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="hapus_retribusi.php?id=<?= $row['id'] ?>" class="btn btn-delete btn-sm" onclick="return confirm('Yakin ingin menghapus data retribusi ini?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php $i++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <h3>Tidak ada data retribusi</h3>
                <p>Data retribusi sampah belum tersedia.</p>
                <?php if ($user_type === 'admin' || $user_type === 'petugas'): ?>
                <a href="tambah_retribusi.php" class="btn btn-success">
                    <i class="fas fa-plus"></i>
                    Tambah Retribusi Pertama
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php 
    // Cek apakah file footer ada
    if (file_exists('templates/footer.php')) {
        include 'templates/footer.php'; 
    } else {
        echo "<footer style='background:#34495e;color:white;padding:1rem;text-align:center;'>SampahKita &copy; " . date('Y') . "</footer>";
    }
    ?>

    <script>
        // Highlight filter yang aktif
        const urlParams = new URLSearchParams(window.location.search);
        const bulanFilter = urlParams.get('bulan');
        if (bulanFilter) {
            document.querySelector('select[name="bulan"]').value = bulanFilter;
        }

        // Konfirmasi sebelum menghapus
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Yakin ingin menghapus data retribusi ini?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>