<?php
// Session check untuk keamanan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['id_admin'])) {
    header("location:../login-admin.php");
    exit;
}
?>

<h4>📚 Tambah Data Buku</h4>
<form method="post" action="#" class="mt-3">
       <input type="text" name="judul_buku" class="form-control mb-2"
              placeholder="Masukan Judul Buku" required>

       <input type="text" name="pengarang" class="form-control mb-2"
              placeholder="Masukan Pengarang" required>

       <input type="text" name="penerbit" class="form-control mb-2"
              placeholder="Masukan Penerbit" required>

       <input maxlength="4" type="number" name="tahun_terbit"
              class="form-control mb-2"
              placeholder="Masukan Tahun Terbit" required>

       <input type="number" name="stok"
              class="form-control mb-2"
              placeholder="Masukan Stok Buku"
              min="0" required>

       <button type="submit" name="tombol" class="btn btn-primary">
              💾 SIMPAN
       </button>
</form>

<?php
if (isset($_POST['tombol'])) {
       include "../koneksi.php";

       $judul_buku   = $_POST['judul_buku'];
       $pengarang    = $_POST['pengarang'];
       $penerbit     = $_POST['penerbit'];
       $tahun_terbit = $_POST['tahun_terbit'];
       $stok         = $_POST['stok'];

       $status = ($stok > 0) ? 'tersedia' : 'tidak';

       $query = "INSERT INTO buku 
              (judul_buku, pengarang, penerbit, tahun_terbit, stok, status)
              VALUES 
              ('$judul_buku', '$pengarang', '$penerbit', '$tahun_terbit', '$stok', '$status')";

       $data = mysqli_query($koneksi, $query);

       if ($data) {
              echo '<script src="../js/sweetalert2.all.min.js"></script>';
              echo "<script>Swal.fire({icon:'success',title:'Sukses',text:'✅ Data Buku Berhasil Disimpan',timer:1200,showConfirmButton:false}).then(function(){ window.location.assign('?halaman=data_buku'); });</script>";
       } else {
              echo '<script src="../js/sweetalert2.all.min.js"></script>';
              echo "<script>Swal.fire({icon:'error',title:'Gagal',text:'❌ Data Buku Gagal Disimpan'}).then(function(){ window.location.assign('?halaman=input_buku'); });</script>";
       }
}
?>