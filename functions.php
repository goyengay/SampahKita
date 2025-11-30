<?php
// functions.php

$conn = mysqli_connect("localhost", "root", "", "db_sampah");

function query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function tambah_retribusi($data) {
    global $conn;
    
    $id_warga = htmlspecialchars($data["id_warga"]);
    $bulan_tahun = htmlspecialchars($data["bulan_tahun"]);
    $jumlah = htmlspecialchars($data["jumlah"]);
    $status = htmlspecialchars($data["status"]);
    $id_petugas = $_SESSION['user_id'];

    $query = "INSERT INTO retribusi VALUES ('', '$id_warga', '$bulan_tahun', '$jumlah', '$status', '$id_petugas', NOW())";
    
    mysqli_query($conn, $query);
    return mysqli_affected_rows($conn);
}

function hapus_retribusi($id) {
    global $conn;
    mysqli_query($conn, "DELETE FROM retribusi WHERE id = $id");
    return mysqli_affected_rows($conn);
}

function ubah_retribusi($data) {
    global $conn;
    
    $id = $data["id"];
    $id_warga = htmlspecialchars($data["id_warga"]);
    $bulan_tahun = htmlspecialchars($data["bulan_tahun"]);
    $jumlah = htmlspecialchars($data["jumlah"]);
    $status = htmlspecialchars($data["status"]);

    $query = "UPDATE retribusi SET
                id_warga = '$id_warga',
                bulan_tahun = '$bulan_tahun', 
                jumlah = '$jumlah',
                status = '$status'
              WHERE id = $id";
    
    mysqli_query($conn, $query);
    return mysqli_affected_rows($conn);
}

function registrasi($data) {
    global $conn;

    $username = strtolower(stripslashes($data["username"]));
    $password = mysqli_real_escape_string($conn, $data["password"]);
    $password2 = mysqli_real_escape_string($conn, $data["password2"]);
    $nama = htmlspecialchars($data["nama"]);
    $user_type = $data["user_type"];

    // cek username sudah ada atau belum
    $result = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");

    if(mysqli_fetch_assoc($result)) {
        echo "<script>
                alert('username sudah terdaftar!')
              </script>";
        return false;
    }

    // cek konfirmasi password
    if($password !== $password2) {
        echo "<script>
                alert('konfirmasi password tidak sesuai!');
              </script>";
        return false;
    }

    // enkripsi password
    $password = password_hash($password, PASSWORD_DEFAULT);

    // tambahkan userbaru ke database
    mysqli_query($conn, "INSERT INTO users VALUES('', '$username', '$password', '$nama', '$user_type')");

    return mysqli_affected_rows($conn);
}
?>