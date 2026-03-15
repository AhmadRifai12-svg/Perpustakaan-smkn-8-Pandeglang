<?php
// Session check untuk keamanan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['id_admin'])) {
    header("location:../login-admin.php");
    exit;
}

$id = $_GET['id'];
$buku = $_GET['buku'];
include "../koneksi.php";
$data = mysqli_query($koneksi, "DELETE FROM transaksi WHERE id_transaksi='$id'");
if ($data) {
    mysqli_query($koneksi, "UPDATE buku SET status='tersedia' WHERE id_buku='$buku'");
    echo '<script src="../js/sweetalert2.all.min.js"></script>';
    echo "<script>Swal.fire({icon:'success',title:'Terhapus',text:'✅ data peminjaman berhasil dihapus',timer:1300,showConfirmButton:false}).then(function(){ window.location.assign('?halaman=data_peminjaman'); });</script>";
}
