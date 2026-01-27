<?php

session_start();
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

if($_SESSION['role'] !='admin'){
    header('location:../index.php');
    exit();
}

if($_POST['tambah']) {
}
?>