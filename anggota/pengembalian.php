<?php
// Session check untuk keamanan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['id_anggota'])) {
    header("location:../login-anggota.php");
    exit;
}

include "../koneksi.php";
date_default_timezone_set("Asia/Jakarta");

$id_transaksi = $_GET['id'];
$id_buku      = $_GET['buku'];
$tgl_kembali  = date('Y-m-d H:i:s');

// ===== CEK AGAR TIDAK BISA KEMBALI 2X =====
$cek = mysqli_query($koneksi, "
    SELECT status_transaksi, tgl_pinjam
    FROM transaksi 
    WHERE id_transaksi='$id_transaksi'
");
$data = mysqli_fetch_assoc($cek);

if ($data['status_transaksi'] == 'pengembalian') {
    die("❌ Buku sudah dikembalikan");
}

// ===== CEK DENDA LAIN BELUM TERBAYAR =====
$id_anggota = $data['id_anggota'];
$cek_denda = mysqli_query($koneksi, "
    SELECT SUM(t.denda - COALESCE(p.jumlah_dibayar, 0)) as denda_sisa
    FROM transaksi t
    LEFT JOIN pembayaran p ON t.id_transaksi = p.id_transaksi AND p.status_pembayaran = 'berhasil'
    WHERE t.id_anggota = '$id_anggota' AND t.denda > 0 AND t.id_transaksi != '$id_transaksi'
");
$denda_data = mysqli_fetch_assoc($cek_denda);
$denda_sisa = $denda_data['denda_sisa'] ?? 0;

if ($denda_sisa > 0) {
    echo '<script src="../js/sweetalert2.all.min.js"></script>';
    echo "<script>Swal.fire({icon:'error',title:'Denda Belum Terbayar',text:'❌ Anda memiliki denda dari buku lain sebesar Rp " . number_format($denda_sisa, 0, ',', '.') . " yang harus dibayar terlebih dahulu!',confirmButtonText:'Lihat Pembayaran'}).then(function(result){ if(result.isConfirmed){ window.location.href='pembayaran.php'; } else { window.location.href='dashboard.php'; }});</script>";
    exit;
}

//// ===== DENDA : HITUNG SELISIH HARI =====
$tgl_pinjam = strtotime($data['tgl_pinjam']);
$sekarang   = strtotime($tgl_kembali);

$selisih = floor(($sekarang - $tgl_pinjam) / (60 * 60 * 24));

//// ===== DENDA : ATURAN =====
$batas_hari = 3;        // maksimal tanpa denda
$denda_per_hari = 2000; // denda per hari

//// ===== DENDA : PERHITUNGAN =====
if ($selisih > $batas_hari) {
    $terlambat   = $selisih - $batas_hari;
    $total_denda = $terlambat * $denda_per_hari;
} else {
    $terlambat   = 0;
    $total_denda = 0;
}

// update transaksi + simpan denda
mysqli_query($koneksi, "
    UPDATE transaksi
    SET status_transaksi='pengembalian',
        tgl_kembali='$tgl_kembali',
        terlambat='$terlambat',     /* ===== DENDA ===== */
        denda='$total_denda'        /* ===== DENDA ===== */
    WHERE id_transaksi='$id_transaksi'
");

// tambah stok
mysqli_query($koneksi, "
    UPDATE buku
    SET stok = stok + 1
    WHERE id_buku='$id_buku'
");

// update status buku
mysqli_query($koneksi, "
    UPDATE buku
    SET status='tersedia'
    WHERE id_buku='$id_buku'
");

//// ===== DENDA : NOTIFIKASI =====
if ($total_denda > 0) {
    $denda_text = number_format($total_denda, 0, ',', '.');
    echo '<script src="../js/sweetalert2.all.min.js"></script>';
    echo "<script>Swal.fire({icon:'warning',title:'Denda',text:'Buku terlambat $terlambat hari. Denda: Rp $denda_text',confirmButtonText:'OK'}).then(function(){ window.location='dashboard.php'; });</script>";
} else {
    echo '<script src="../js/sweetalert2.all.min.js"></script>';
    echo "<script>Swal.fire({icon:'success',title:'Berhasil',text:'✅ Buku berhasil dikembalikan',timer:1400,showConfirmButton:false}).then(function(){ window.location='dashboard.php'; });</script>";
}
