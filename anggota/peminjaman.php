<?php
include "../koneksi.php";

$anggota = mysqli_query($koneksi, "SELECT * FROM anggota");
$buku = mysqli_query($koneksi, "SELECT * FROM buku");

if (isset($_POST['tombol'])) {

    $id_anggota = $_POST['id_anggota'];
    $id_buku    = $_POST['id_buku'];
    $tgl_pinjam = date('Y-m-d H:i:s');
    $status_transaksi = "peminjaman";
    $cek = mysqli_query($koneksi, "SELECT stok FROM buku WHERE id_buku='$id_buku'");
    $cek_data = mysqli_fetch_assoc($cek);

    if ($cek_data['stok'] <= 0) {
        echo '<script src="../js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'error',title:'Stok Habis',text:'❌ Stok buku habis'}).then(function(){ window.location.assign('?halaman=input_peminjaman'); });</script>";
        exit;
    }

    $insert = mysqli_query($koneksi, "
        INSERT INTO transaksi (id_anggota, id_buku, tgl_pinjam, status_transaksi)
        VALUES ('$id_anggota', '$id_buku', '$tgl_pinjam', '$status_transaksi')
    ");

    if ($insert) {
        mysqli_query($koneksi, "
            UPDATE buku 
            SET stok = stok - 1,
                status = IF(stok - 1 > 0, 'tersedia', 'tidak')
            WHERE id_buku='$id_buku'
        ");

        echo '<script src="../js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'success',title:'Sukses',text:'✅ Data Berhasil Disimpan',timer:1400,showConfirmButton:false}).then(function(){ window.location.assign('?halaman=input_peminjaman'); });</script>";
    } else {
        echo '<script src="../js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'error',title:'Gagal',text:'❌ Data Gagal Disimpan'}).then(function(){ window.location.assign('?halaman=input_peminjaman'); });</script>";
    }
}
