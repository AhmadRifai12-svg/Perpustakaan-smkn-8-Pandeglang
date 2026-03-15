<?php
$server = "localhost";
$pengguna = "root";
$password = "";
$database = "perpustakaan_baru";
$koneksi = mysqli_connect($server, $pengguna, $password, $database);
if (!$koneksi) {
    echo"Koneksi error" . mysqli_error();
}