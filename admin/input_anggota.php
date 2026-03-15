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

<h4>📚 Tambah Data Anggota</h4>
<form method="post" action="#" class="mt-3">
    <input type="text" name="nis" class="form-control mb-2" placeholder="Masukan NIS" required>
    <input type="text" name="nama_anggota" class="form-control mb-2" placeholder="Masukan Nama Anggota" required>
    <input type="text" name="username" class="form-control mb-2" placeholder="Masukan Usernamr" required>
    <input type="text" name="pass" class="form-control mb-2" placeholder="Masukan Password" required>
    <input type="text" name="kelas" class="form-control mb-2" placeholder="Masukan Kelas" required>
    <button type="submit" name="tombol" class="btn btn-primary"> 💾 SIMPAN</button>
</form>
<?php
if (isset($_POST['tombol'])) {
    include "../koneksi.php";
    $nis = $_POST['nis'];
    $nama_anggota = $_POST['nama_anggota'];
    $username = $_POST['username'];
    $pass = $_POST['pass'];
    $kelas = $_POST['kelas'];
    include "../koneksi.php";
    $query = "INSERT INTO anggota (nis, nama_anggota, username, passWORD, kelas) VALUES ('$nis', '$nama_anggota', '$username', '$pass','$kelas')";
    $data = mysqli_query($koneksi, $query);
    if ($data) {
        echo '<script src="../js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'success',title:'Sukses',text:'✅ Data Berhasil Disimpan',timer:1200,showConfirmButton:false}).then(function(){ window.location.assign('?halaman=data_anggota'); });</script>";
    } else {
        echo '<script src="../js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'error',title:'Gagal',text:'❌ Data Gagal Disimpan Silahkan Coba Lagi'}).then(function(){ window.location.assign('?halaman=input_anggota'); });</script>";
    }
}
