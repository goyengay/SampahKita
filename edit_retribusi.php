<?php
session_start();
require 'functions.php';

// Cek apakah user sudah login dan memiliki akses
if (!isset($_SESSION['login']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'petugas')) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: retribusi.php");
    exit;
}

$id = $_GET['id'];
$retribusi = getRetribusiById($id);

if (!$retribusi) {
    header("Location: retribusi.php");
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id_warga' => $_POST['id_warga'],
        'bulan_tahun' => $_POST['bulan_tahun'],
        'jumlah' => $_POST['jumlah'],
        'status' => $_POST['status']
    ];
    
    if (editRetribusi($id, $data)) {
        $success = "Data retribusi berhasil diupdate!";
        $retribusi = getRetribusiById($id); // Refresh data
    } else {
        $error = "Gagal mengupdate data retribusi!";
    }
}

// Ambil data warga untuk dropdown
$warga = getWarga();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Retribusi - SampahKita</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-success {
            background-color: #27ae60;
            color: white;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="form-container">
        <h1 style="color: #2c3e50; margin-bottom: 20px;">
            <i class="fas fa-edit"></i> Edit Data Retribusi
        </h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="id_warga">Nama Warga</label>
                <select class="form-control" id="id_warga" name="id_warga" required>
                    <option value="">Pilih Warga</option>
                    <?php foreach ($warga as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= $w['id'] == $retribusi['id_warga'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($w['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="bulan_tahun">Bulan Tahun</label>
                <input type="month" class="form-control" id="bulan_tahun" name="bulan_tahun" 
                       value="<?= htmlspecialchars($retribusi['bulan_tahun']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="jumlah">Jumlah Retribusi</label>
                <input type="number" class="form-control" id="jumlah" name="jumlah" 
                       value="<?= htmlspecialchars($retribusi['jumlah']) ?>" placeholder="Masukkan jumlah retribusi" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Status Pembayaran</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="lunas" <?= $retribusi['status'] == 'lunas' ? 'selected' : '' ?>>Lunas</option>
                    <option value="tunggak" <?= $retribusi['status'] == 'tunggak' ? 'selected' : '' ?>>Tunggak</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Update
                </button>
                <a href="retribusi.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </form>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>