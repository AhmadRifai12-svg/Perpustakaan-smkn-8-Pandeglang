<?php
// Session check untuk keamanan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['id_admin'])) {
    header("location:../login-admin.php");
    exit;
}

include "../koneksi.php";
$id = $_GET['id'];
$data = mysqli_query($koneksi, "DELETE FROM buku WHERE id_buku='$id'");

if ($data) {
     echo '<script src="../js/sweetalert2.all.min.js"></script>';
     echo "<script>Swal.fire({icon:'success',title:'Terhapus',text:'✅ Data Berhasil Dihapus',timer:1200,showConfirmButton:false}).then(function(){ window.location.assign('?halaman=data_buku'); });</script>";
} else {
     echo '<script src="../js/sweetalert2.all.min.js"></script>';
     echo "<script>Swal.fire({icon:'error',title:'Gagal',text:'❌ Data Gagal Dihapus'}).then(function(){ window.location.assign('?halaman=data_buku'); });</script>";
}
