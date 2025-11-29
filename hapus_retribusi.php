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

// Hapus data
if (hapusRetribusi($id)) {
    $_SESSION['success'] = "Data retribusi berhasil dihapus!";
} else {
    $_SESSION['error'] = "Gagal menghapus data retribusi!";
}

header("Location: retribusi.php");
exit;
?>