<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'auth.php';
$auth = new Auth();
$auth->redirectIfNotLoggedIn();

// hanya admin yang bisa mengakses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

include 'config.php';
$db = new Database();
$conn = $db->getConnection();

// Ambil list petugas untuk dropdown
$petugas_stmt = $conn->prepare("SELECT id, nama_lengkap FROM users WHERE role = 'petugas' ORDER BY nama_lengkap");
$petugas_stmt->execute();
$petugas_list = $petugas_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
$alert = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $wilayah = trim($_POST['wilayah'] ?? '');
        $hari = trim($_POST['hari'] ?? '');
        $jam = trim($_POST['jam'] ?? '');
        $id_petugas = !empty($_POST['id_petugas']) ? $_POST['id_petugas'] : null;

        if ($wilayah === '' || $hari === '' || $jam === '') {
            $alert = ['type' => 'danger', 'msg' => 'Semua field wajib diisi kecuali petugas.'];
        } else {
            $q = "INSERT INTO jadwal_pengangkutan (wilayah, hari, jam, id_petugas) VALUES (:wilayah, :hari, :jam, :id_petugas)";
            $st = $conn->prepare($q);
            $st->bindParam(':wilayah', $wilayah);
            $st->bindParam(':hari', $hari);
            $st->bindParam(':jam', $jam);
            $st->bindParam(':id_petugas', $id_petugas);
            if ($st->execute()) {
                header('Location: kelola_jadwal.php?success=created');
                exit;
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal menyimpan jadwal.'];
            }
        }
    }

    if ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $wilayah = trim($_POST['wilayah'] ?? '');
        $hari = trim($_POST['hari'] ?? '');
        $jam = trim($_POST['jam'] ?? '');
        $id_petugas = !empty($_POST['id_petugas']) ? $_POST['id_petugas'] : null;

        if ($id <= 0 || $wilayah === '' || $hari === '' || $jam === '') {
            $alert = ['type' => 'danger', 'msg' => 'Field tidak valid.'];
        } else {
            $q = "UPDATE jadwal_pengangkutan SET wilayah = :wilayah, hari = :hari, jam = :jam, id_petugas = :id_petugas WHERE id = :id";
            $st = $conn->prepare($q);
            $st->bindParam(':wilayah', $wilayah);
            $st->bindParam(':hari', $hari);
            $st->bindParam(':jam', $jam);
            $st->bindParam(':id_petugas', $id_petugas);
            $st->bindParam(':id', $id, PDO::PARAM_INT);
            if ($st->execute()) {
                header('Location: kelola_jadwal.php?success=updated');
                exit;
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal memperbarui jadwal.'];
            }
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $q = "DELETE FROM jadwal_pengangkutan WHERE id = :id";
            $st = $conn->prepare($q);
            $st->bindParam(':id', $id, PDO::PARAM_INT);
            if ($st->execute()) {
                header('Location: kelola_jadwal.php?success=deleted');
                exit;
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal menghapus jadwal.'];
            }
        } else {
            $alert = ['type' => 'danger', 'msg' => 'ID tidak valid.'];
        }
    }
}

// Ambil data untuk edit jika ada
$editing = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    if ($id > 0) {
        $st = $conn->prepare("SELECT * FROM jadwal_pengangkutan WHERE id = :id");
        $st->bindParam(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $editing = $st->fetch(PDO::FETCH_ASSOC);
    }
}

// Ambil semua jadwal
$list_stmt = $conn->prepare("SELECT j.id, j.wilayah, j.hari, j.jam, u.nama_lengkap as petugas FROM jadwal_pengangkutan j LEFT JOIN users u ON j.id_petugas = u.id ORDER BY FIELD(j.hari, 'senin','selasa','rabu','kamis','jumat','sabtu','minggu'), j.jam");
$list_stmt->execute();
$jadwal_list = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Jadwal - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body style="background: #f4f6f8;">
<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(90deg,#1e7e34,#2d9f3d)">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php"><i class="fas fa-trash-alt"></i> SAMPAH KITA - Admin</a>
    <div class="d-flex ms-auto align-items-center">
      <span class="text-white me-3"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
      <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-white">
                    <strong><?php echo $editing ? 'Edit Jadwal' : 'Tambah Jadwal'; ?></strong>
                </div>
                <div class="card-body">
                    <?php if ($alert): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?>"><?php echo htmlspecialchars($alert['msg']); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?php echo intval($editing['id']); ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Wilayah</label>
                            <input type="text" name="wilayah" class="form-control" required value="<?php echo $editing ? htmlspecialchars($editing['wilayah']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Hari</label>
                            <select name="hari" class="form-select" required>
                                <?php
                                $days = ['senin','selasa','rabu','kamis','jumat','sabtu','minggu'];
                                foreach ($days as $d) {
                                    $sel = ($editing && strtolower($editing['hari']) === $d) ? 'selected' : '';
                                    echo "<option value=\"$d\" $sel>".ucfirst($d)."</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jam (HH:MM)</label>
                            <input type="time" name="jam" class="form-control" required value="<?php echo $editing ? htmlspecialchars(date('H:i', strtotime($editing['jam']))) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Petugas (opsional)</label>
                            <select name="id_petugas" class="form-select">
                                <option value="">-- Pilih Petugas --</option>
                                <?php foreach ($petugas_list as $p): ?>
                                    <?php $selected = ($editing && $editing['petugas'] && $p['nama_lengkap'] === $editing['petugas']) ? 'selected' : ''; ?>
                                    <option value="<?php echo intval($p['id']); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($p['nama_lengkap']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-success" type="submit"><?php echo $editing ? 'Simpan Perubahan' : 'Tambah Jadwal'; ?></button>
                            <?php if ($editing): ?>
                                <a href="kelola_jadwal.php" class="btn btn-secondary">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-3 text-muted small">Tip: Anda dapat mengedit jadwal dengan menekan tombol edit pada daftar.</div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <strong>Daftar Jadwal</strong>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Operasi berhasil.</div>
                    <?php endif; ?>

                    <?php if (count($jadwal_list) === 0): ?>
                        <div class="text-center text-muted py-5">Belum ada jadwal.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Wilayah</th>
                                        <th>Hari</th>
                                        <th>Jam</th>
                                        <th>Petugas</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jadwal_list as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['wilayah']); ?></td>
                                        <td><?php echo ucfirst($row['hari']); ?></td>
                                        <td><?php echo date('H:i', strtotime($row['jam'])); ?></td>
                                        <td><?php echo $row['petugas'] ? htmlspecialchars($row['petugas']) : '<span class="text-muted">-</span>'; ?></td>
                                        <td class="text-end">
                                            <a href="kelola_jadwal.php?edit=<?php echo intval($row['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <form method="POST" style="display:inline-block" onsubmit="return confirm('Hapus jadwal ini?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo intval($row['id']); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
