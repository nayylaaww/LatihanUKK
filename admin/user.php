<?php
session_start();

include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

checkAuth('admin');

if (isset($_POST['tambah'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama     = $_POST['nama'];
    $role     = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO user (username, password, nama, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $nama, $role);

    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], "Menambah user: $username ($role)");
        $message = showAlert('success', 'User berhasil ditambahkan!');
    } else {
        $message = showAlert('danger', 'Gagal menambah user! Username mungkin sudah ada.');
    }
}

if (isset($_POST['edit'])) {
    $id   = $_POST['id'];
    $nama = $_POST['nama'];
    $role = $_POST['role'];

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user SET nama = ?, role = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $nama, $role, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE user SET nama = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nama, $role, $id);
    }

    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], "Mengedit user ID $id");
        $message = showAlert('success', 'User berhasil diupdate!');
    } else {
        $message = showAlert('danger', 'Gagal mengupdate user!');
    }
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    if ($id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM user WHERE id = $id");
        logActivity($_SESSION['user_id'], "Menghapus user ID: $id");
        $message = showAlert('success', 'User berhasil dihapus!');
    } else {
        $message = showAlert('danger', 'Tidak bisa menghapus akun sendiri!');
    }
}

$users = $conn->query("SELECT * FROM user ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola User - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .role-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            color: white;
        }
        .role-admin { background: #dc3545; }
        .role-petugas { background: #ffc107; color: #000; }
        .role-peminjam { background: #28a745; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>Menu Admin</h3>
    <a href="dashboard.php">Dashboard</a>
    <a href="user.php">Kelola User</a>
    <a href="alat.php">Kelola Alat</a>
    <a href="kategori.php">Kelola Kategori</a>
    <a href="peminjaman.php">Kelola Peminjaman</a>
    <a href="laporan.php">Laporan</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="content">

    <!-- pindahhh kesinii -->
    <div style="margin-bottom: 20px;">
        <a href="../register.php?admin=1" class="btn btn-primary">
            Tambah User Baru
        </a>
    </div>

    <h2>Kelola User</h2>

    <?php if (isset($message)) echo $message; ?>

    <div class="card">
        <h3>Tambah User Baru</h3>
        <form method="POST" class="form">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="nama" placeholder="Nama Lengkap" required>
            <select name="role" required>
                <option value="">Pilih Role</option>
                <option value="admin">Admin</option>
                <option value="petugas">Petugas</option>
                <option value="peminjam">Peminjam</option>
            </select>
            <button type="submit" name="tambah" class="btn btn-primary">Tambah User</button>
        </form>
    </div>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Role</th>
                    <th>Tanggal Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><?= $row['username']; ?></td>
                    <td><?= $row['nama']; ?></td>
                    <td>
                        <span class="role-badge role-<?= $row['role']; ?>">
                            <?= ucfirst($row['role']); ?>
                        </span>
                    </td>
                    <td><?= formatDate($row['created_at']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="editUser(
                            <?= $row['id']; ?>,
                            '<?= $row['username']; ?>',
                            '<?= $row['nama']; ?>',
                            '<?= $row['role']; ?>'
                        )">Edit</button>

                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                        <a href="?hapus=<?= $row['id']; ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Yakin menghapus user ini?')">Hapus</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Edit User</h3>
        <form method="POST">
            <input type="hidden" name="id" id="editId">

            <label>Username</label>
            <input type="text" id="editUsername" disabled>

            <label>Nama Lengkap</label>
            <input type="text" name="nama" id="editNama" required>

            <label>Role</label>
            <select name="role" id="editRole" required>
                <option value="admin">Admin</option>
                <option value="petugas">Petugas</option>
                <option value="peminjam">Peminjam</option>
            </select>

            <label>Password Baru (opsional)</label>
            <input type="password" name="password">

            <button type="submit" name="edit" class="btn btn-primary">Update</button>
        </form>
    </div>
</div>

<script>
function editUser(id, username, nama, role) {
    document.getElementById('editId').value = id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editNama').value = nama;
    document.getElementById('editRole').value = role;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

</body>
</html>
