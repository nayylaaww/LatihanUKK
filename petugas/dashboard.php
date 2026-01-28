<!-- halll33 -->

<?php
session_start(); 

include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

checkAuth();

$total_alat = $conn->query("SELECT COUNT(*) as total FROM alat")->fetch_assoc()['total'];
$total_peminjaman_hari_ini = $conn->query("SELECT COUNT(*) as total FROM peminjaman WHERE 
DATE(tanggal_pinjam) = CURDATE()")->fetch_assoc()['total'];
$peminjaman_menunggu = $conn->query("SELECT COUNT(*) as total FROM peminjaman WHERE status =
'diajukan'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Document</title>
</head>
<body>
    
</body>
</html>