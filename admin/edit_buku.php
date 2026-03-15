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
$query_buku = mysqli_query($koneksi, "SELECT*FROM buku WHERE id_buku='$id'");
$data_buku = mysqli_fetch_array($query_buku);
?>
<h4>📚 Edit Data Buku</h4>
<form method="post" action="" class="mt-3">
    <input value="<?php echo $data_buku['judul_buku']; ?>" type="text" name="judul_buku" class="form-control mb-2" placeholder="Masukan Judul Buku" required>

    <input value="<?php echo $data_buku['pengarang']; ?>" type="text" name="pengarang" class="form-control mb-2" placeholder="Masukan Pengarang" required>

    <input value="<?php echo $data_buku['penerbit']; ?>" type="text" name="penerbit" class="form-control mb-2" placeholder="Masukan Penerbit" required>

    <input value="<?php echo $data_buku['tahun_terbit']; ?>" type="number" name="tahun_terbit" class="form-control mb-2" placeholder="Masukan Tahun Terbit" required>

    <input value="<?php echo $data_buku['stok']; ?>" type="number" name="stok" class="form-control mb-2" placeholder="Masukan Stok Buku" required>

    <button type="submit" name="tombol" class="btn btn-primary"> 💾 SIMPAN</button>
</form>
<?php
if (isset($_POST['tombol'])) {
    include "../koneksi.php";
    $judul_buku = $_POST['judul_buku'];
    $pengarang = $_POST['pengarang'];
    $penerbit = $_POST['penerbit'];
    $tahun_terbit = $_POST['tahun_terbit'];
    $stok = $_POST['stok'];
    include "../koneksi.php";
    $query = "UPDATE buku SET judul_buku='$judul_buku', pengarang='$pengarang',penerbit='$penerbit', tahun_terbit='$tahun_terbit',stok='$stok' WHERE id_buku='$id'";
    $data = mysqli_query($koneksi, $query);
    if ($data) {
        echo '<script src="../js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'success',title:'Sukses',text:'✅ Data Berhasil Disimpan',timer:1200,showConfirmButton:false}).then(function(){ window.location.assign('?halaman=data_buku'); });</script>";
    } else {
        echo '<script src="../js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'error',title:'Gagal',text:'❌ Data Gagal Disimpan'}).then(function(){ window.location.assign('?halaman=data_buku'); });</script>";
    }
}

?>