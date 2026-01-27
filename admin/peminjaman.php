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

    $stmt = $conn->prepare("UPDATE peminjaman SET status = 'dikembalikan', denda = ? WHERE id = ?");
    $stmt->bind_param("di", $denda, $id);
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