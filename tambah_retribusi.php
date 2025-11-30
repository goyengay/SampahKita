<?php
session_start();
require 'functions.php';

if(!isset($_SESSION["login"]) || ($_SESSION['user_type'] != 'admin' && $_SESSION['user_type'] != 'petugas')) {
    header("Location: login.php");
    exit;
}

if(isset($_POST["submit"])) {
    if(tambah_retribusi($_POST) > 0) {
        echo "
            <script>
                alert('data berhasil ditambahkan!');
                document.location.href = 'retribusi.php';
            </script>
        ";
    } else {
        echo "
            <script>
                alert('data gagal ditambahkan!');
                document.location.href = 'retribusi.php';
            </script>
        ";
    }
}

$warga = query("SELECT * FROM warga ORDER BY nama");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Retribusi</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <div class="card">
            <h1 style="margin-bottom: 20px;">Tambah Data Retribusi</h1>

            <form action="" method="post">
                <div class="form-group">
                    <label for="id_warga">Nama Warga</label>
                    <select name="id_warga" id="id_warga" required>
                        <option value="">Pilih Warga</option>
                        <?php foreach($warga as $w) : ?>
                        <option value="<?= $w['id']; ?>"><?= $w['nama']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="bulan_tahun">Bulan Tahun</label>
                    <input type="month" name="bulan_tahun" id="bulan_tahun" required>
                </div>

                <div class="form-group">
                    <label for="jumlah">Jumlah Retribusi</label>
                    <input type="number" name="jumlah" id="jumlah" placeholder="Masukkan jumlah" required>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" required>
                        <option value="lunas">Lunas</option>
                        <option value="tunggak">Tunggak</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="submit" class="btn btn-success">Tambah Data</button>
                    <a href="retribusi.php" class="btn btn-secondary">Kembali</a>
                </div>
            </form>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>