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

$alert = null;

// Handle POST actions: create, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $role = $_POST['role'] ?? 'masyarakat';

        if ($username === '' || $password === '' || $nama === '') {
            $alert = ['type' => 'danger', 'msg' => 'Username, password, dan nama lengkap wajib diisi.'];
        } else {
            // Periksa username unik
            $check = $conn->prepare("SELECT id FROM users WHERE username = :username");
            $check->bindParam(':username', $username);
            $check->execute();
            if ($check->rowCount() > 0) {
                $alert = ['type' => 'danger', 'msg' => 'Username sudah dipakai. Pilih username lain.'];
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $q = "INSERT INTO users (username, password, nama_lengkap, alamat, no_telepon, role) VALUES (:username, :password, :nama, :alamat, :no_telepon, :role)";
                $st = $conn->prepare($q);
                $st->bindParam(':username', $username);
                $st->bindParam(':password', $hash);
                $st->bindParam(':nama', $nama);
                $st->bindParam(':alamat', $alamat);
                $st->bindParam(':no_telepon', $no_telepon);
                $st->bindParam(':role', $role);
                if ($st->execute()) {
                    header('Location: kelola_user.php?success=created');
                    exit;
                } else {
                    $alert = ['type' => 'danger', 'msg' => 'Gagal menambah user.'];
                }
            }
        }
    }

    if ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $no_telepon = trim($_POST['no_telepon'] ?? '');
        $role = $_POST['role'] ?? 'masyarakat';

        if ($id <= 0 || $username === '' || $nama === '') {
            $alert = ['type' => 'danger', 'msg' => 'Field tidak valid.'];
        } else {
            // Pastikan username tidak konflik dengan user lain
            $check = $conn->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
            $check->bindParam(':username', $username);
            $check->bindParam(':id', $id, PDO::PARAM_INT);
            $check->execute();
            if ($check->rowCount() > 0) {
                $alert = ['type' => 'danger', 'msg' => 'Username sudah dipakai oleh user lain.'];
            } else {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $q = "UPDATE users SET username = :username, password = :password, nama_lengkap = :nama, alamat = :alamat, no_telepon = :no_telepon, role = :role WHERE id = :id";
                    $st = $conn->prepare($q);
                    $st->bindParam(':password', $hash);
                } else {
                    $q = "UPDATE users SET username = :username, nama_lengkap = :nama, alamat = :alamat, no_telepon = :no_telepon, role = :role WHERE id = :id";
                    $st = $conn->prepare($q);
                }
                $st->bindParam(':username', $username);
                $st->bindParam(':nama', $nama);
                $st->bindParam(':alamat', $alamat);
                $st->bindParam(':no_telepon', $no_telepon);
                $st->bindParam(':role', $role);
                $st->bindParam(':id', $id, PDO::PARAM_INT);

                if ($st->execute()) {
                    // Jika admin mengubah role atau username dirinya sendiri, update session nama dan role
                    if ($id === intval($_SESSION['user_id'])) {
                        $_SESSION['nama_lengkap'] = $nama;
                        $_SESSION['role'] = $role;
                    }
                    header('Location: kelola_user.php?success=updated');
                    exit;
                } else {
                    $alert = ['type' => 'danger', 'msg' => 'Gagal memperbarui user.'];
                }
            }
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $alert = ['type' => 'danger', 'msg' => 'ID tidak valid.'];
        } elseif ($id === intval($_SESSION['user_id'])) {
            $alert = ['type' => 'danger', 'msg' => 'Anda tidak dapat menghapus user yang sedang login.'];
        } else {
            $q = "DELETE FROM users WHERE id = :id";
            $st = $conn->prepare($q);
            $st->bindParam(':id', $id, PDO::PARAM_INT);
            if ($st->execute()) {
                header('Location: kelola_user.php?success=deleted');
                exit;
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal menghapus user.'];
            }
        }
    }
}

// Jika edit via GET
$editing = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    if ($id > 0) {
        $st = $conn->prepare("SELECT id, username, nama_lengkap, alamat, no_telepon, role FROM users WHERE id = :id");
        $st->bindParam(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $editing = $st->fetch(PDO::FETCH_ASSOC);
    }
}

// Ambil semua users
$list_stmt = $conn->prepare("SELECT id, username, nama_lengkap, role, alamat, no_telepon, created_at FROM users ORDER BY created_at DESC");
$list_stmt->execute();
$user_list = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola User - Admin</title>
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
                    <strong><?php echo $editing ? 'Edit User' : 'Tambah User'; ?></strong>
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
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required value="<?php echo $editing ? htmlspecialchars($editing['username']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password <?php echo $editing ? '<small class="text-muted">(kosongkan jika tidak mau diubah)</small>' : ''; ?></label>
                            <input type="password" name="password" class="form-control" <?php echo $editing ? '' : 'required'; ?> autocomplete="new-password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" required value="<?php echo $editing ? htmlspecialchars($editing['nama_lengkap']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <input type="text" name="alamat" class="form-control" value="<?php echo $editing ? htmlspecialchars($editing['alamat']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="no_telepon" class="form-control" value="<?php echo $editing ? htmlspecialchars($editing['no_telepon']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <?php
                                $roles = ['masyarakat', 'petugas', 'admin'];
                                foreach ($roles as $r) {
                                    $sel = ($editing && $editing['role'] === $r) ? 'selected' : '';
                                    echo "<option value=\"$r\" $sel>".ucfirst($r)."</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-success" type="submit"><?php echo $editing ? 'Simpan Perubahan' : 'Tambah User'; ?></button>
                            <?php if ($editing): ?>
                                <a href="kelola_user.php" class="btn btn-secondary">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-3 text-muted small">Tip: Jangan menghapus akun yang sedang Anda gunakan.</div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <strong>Daftar User</strong>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Operasi berhasil.</div>
                    <?php endif; ?>

                    <?php if (count($user_list) === 0): ?>
                        <div class="text-center text-muted py-5">Belum ada user.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Telepon</th>
                                        <th>Dibuat</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_list as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['nama_lengkap']); ?></td>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td><?php echo ucfirst(htmlspecialchars($u['role'])); ?></td>
                                        <td><?php echo htmlspecialchars($u['no_telepon']); ?></td>
                                        <td><?php echo $u['created_at'] ? htmlspecialchars($u['created_at']) : '-'; ?></td>
                                        <td class="text-end">
                                            <a href="kelola_user.php?edit=<?php echo intval($u['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <?php if (intval($u['id']) !== intval($_SESSION['user_id'])): ?>
                                            <form method="POST" style="display:inline-block" onsubmit="return confirm('Hapus user ini?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo intval($u['id']); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary" disabled title="Tidak bisa menghapus user yang sedang login"><i class="fas fa-trash"></i></button>
                                            <?php endif; ?>
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
