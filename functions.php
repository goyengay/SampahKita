<?php
// functions.php

function koneksi() {
    $host = 'localhost';
    $username = 'root';  // sesuaikan dengan username database Anda
    $password = '';      // sesuaikan dengan password database Anda  
    $database = 'db_sampah'; // sesuaikan dengan nama database Anda

    $conn = mysqli_connect($host, $username, $password, $database);

    // Cek koneksi
    if (!$conn) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }

    return $conn;
}

function query($sql) {
    $conn = koneksi();
    $result = mysqli_query($conn, $sql);
    
    // Jika query SELECT, ambil hasilnya
    if (strpos(strtoupper($sql), 'SELECT') === 0) {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);
        mysqli_close($conn);
        return $rows;
    } else {
        // Untuk query INSERT, UPDATE, DELETE
        mysqli_close($conn);
        return $result;
    }
}

function escape($string) {
    $conn = koneksi();
    $escaped = mysqli_real_escape_string($conn, $string);
    mysqli_close($conn);
    return $escaped;
}

function login($username, $password) {
    $username = escape($username);
    $password = escape($password);
    
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = query($query);
    
    if (count($result) > 0) {
        return $result[0];
    }
    
    return false;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['login']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isPetugas() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'petugas';
}

function isWarga() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'warga';
}

// Fungsi untuk mendapatkan data warga
function getWarga() {
    return query("SELECT * FROM warga ORDER BY nama ASC");
}

// Fungsi untuk mendapatkan data petugas
function getPetugas() {
    return query("SELECT * FROM users WHERE user_type = 'petugas' ORDER BY nama ASC");
}

// Fungsi untuk menambah retribusi
function tambahRetribusi($data) {
    $id_warga = escape($data['id_warga']);
    $bulan_tahun = escape($data['bulan_tahun']);
    $jumlah = escape($data['jumlah']);
    $status = escape($data['status']);
    $id_petugas = $_SESSION['user_id'] ?? 0;
    
    $query = "INSERT INTO retribusi (id_warga, bulan_tahun, jumlah, status, id_petugas, created_at) 
              VALUES ('$id_warga', '$bulan_tahun', '$jumlah', '$status', '$id_petugas', NOW())";
    
    return query($query);
}

// Fungsi untuk mengedit retribusi
function editRetribusi($id, $data) {
    $id = escape($id);
    $id_warga = escape($data['id_warga']);
    $bulan_tahun = escape($data['bulan_tahun']);
    $jumlah = escape($data['jumlah']);
    $status = escape($data['status']);
    
    $query = "UPDATE retribusi SET 
              id_warga = '$id_warga',
              bulan_tahun = '$bulan_tahun', 
              jumlah = '$jumlah', 
              status = '$status',
              updated_at = NOW()
              WHERE id = '$id'";
    
    return query($query);
}

// Fungsi untuk menghapus retribusi
function hapusRetribusi($id) {
    $id = escape($id);
    $query = "DELETE FROM retribusi WHERE id = '$id'";
    return query($query);
}

// Fungsi untuk mendapatkan data retribusi by ID
function getRetribusiById($id) {
    $id = escape($id);
    $query = "SELECT r.*, w.nama as nama_warga, u.nama as nama_petugas 
              FROM retribusi r 
              LEFT JOIN warga w ON r.id_warga = w.id 
              LEFT JOIN users u ON r.id_petugas = u.id 
              WHERE r.id = '$id'";
    $result = query($query);
    return count($result) > 0 ? $result[0] : false;
}
?>