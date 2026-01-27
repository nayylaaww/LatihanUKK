<?php
session_start();

include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

checkAuth('admin');

if (isset($_GET['setujui'])) {
    $id = $_GET['setujui'];
    $conn->query("UPDATE peminjaman SET status = 'disetujui' WHERE id = $id");
    logActivity($_SESSION['user_id'], "Menyetujui peminjaman ID: $id");
    $message = showAlert('success', 'Peminjaman berhasil disetujui!');
}

if (isset($_GET['tolak'])) {
    $id = $_GET['tolak'];
    $conn->query("UPDATE peminjaman SET status = 'ditolak' WHERE id = $id");
    logActivity($_SESSION['user_id'], "Menolak peminjaman ID: $id");
    $message = showAlert('success', 'Peminjaman berhasil ditolak!');
}

if (isset($_POST['kembalikan'])) {
    $id = $_POST['id'];
    $denda = $_POST['denda'];
    $tanggal_kembali = $_POST['tanggal_kembali'];

    $stmt = $conn->prepare(
        "UPDATE peminjaman 
         SET status = 'dikembalikan', denda = ?, tanggal_kembali = ? 
         WHERE id = ?"
    );
    $stmt->bind_param("dsi", $denda, $tanggal_kembali, $id);
    $stmt->execute();

    logActivity($_SESSION['user_id'], "Mengupdate pengembalian ID: $id");
    $message = showAlert('success', 'Pengembalian berhasil dicatat!');
}

$peminjaman = $conn->query("
    SELECT p.*, a.nama_alat, u.username, u.nama
    FROM peminjaman p
    JOIN alat a ON p.alat_id = a.id
    JOIN user u ON p.user_id = u.id
    ORDER BY p.tanggal_pinjam DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Peminjaman - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            color: white;
        }
        .status-diajukan { background: #6c757d; }
        .status-disetujui { background: #28a745; }
        .status-ditolak { background: #dc3545; }
        .status-dikembalikan { background: #007bff; }
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
    <h2>Kelola Peminjaman</h2>

    <?php if(isset($message)) echo $message; ?>

    <div class="card">
        <h3>Daftar Peminjaman</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Alat</th>
                    <th>Peminjam</th>
                    <th>Jumlah</th>
                    <th>Tanggal Pinjam</th>
                    <th>Tanggal Kembali</th>
                    <th>Status</th>
                    <th>Denda</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = $peminjaman->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id']; ?></td>
                    <td><?= $row['nama_alat']; ?></td>
                    <td><?= $row['nama']; ?> (<?= $row['username']; ?>)</td>
                    <td><?= $row['jumlah']; ?></td>
                    <td><?= formatDate($row['tanggal_pinjam']); ?></td>
                    <td><?= $row['tanggal_kembali'] ? formatDate($row['tanggal_kembali']) : '-'; ?></td>
                    <td>
                        <span class="status-badge status-<?= $row['status']; ?>">
                            <?= ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td>Rp <?= number_format($row['denda'],0,',','.'); ?></td>
                    <td>
                        <?php if($row['status']=='diajukan'): ?>
                            <a href="?setujui=<?= $row['id']; ?>" class="btn btn-sm btn-success"
                               onclick="return confirm('Setujui peminjaman?')">Setujui</a>

                            <a href="?tolak=<?= $row['id']; ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Tolak peminjaman?')">Tolak</a>

                        <?php elseif($row['status']=='disetujui' && !$row['tanggal_kembali']): ?>
                            <button class="btn btn-sm btn-primary"
                                    onclick="showKembalikanModal(<?= $row['id']; ?>)">
                                Kembalikan
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL -->
<div id="kembalikanModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Catat Pengembalian</h3>
        <form method="POST">
            <input type="hidden" name="id" id="kembalikanId">

            <div class="form-group">
                <label>Tanggal Pengembalian</label>
                <input type="date" name="tanggal_kembali"
                       value="<?= date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label>Denda (Rp)</label>
                <input type="number" name="denda" value="0" min="0">
            </div>

            <button type="submit" name="kembalikan" class="btn btn-primary">
                Simpan Pengembalian
            </button>
        </form>
    </div>
</div>

<script>
function showKembalikanModal(id) {
    document.getElementById('kembalikanId').value = id;
    document.getElementById('kembalikanModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('kembalikanModal').style.display = 'none';
}

window.onclick = function(event) {
    var modal = document.getElementById('kembalikanModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

</body>
</html>
