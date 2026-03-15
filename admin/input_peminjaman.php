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

error_reporting(E_ALL);
ini_set('display_errors', 1);

$anggota = mysqli_query($koneksi, "SELECT * FROM anggota");
$buku = mysqli_query($koneksi, "SELECT * FROM buku");
?>

<!-- FORM PEMINJAMAN -->
<form method="POST" action="">

    <select name="id_anggota" class="form-control mb-2" required>
        <option value=""> === Pilih Anggota === </option>
        <?php foreach ($anggota as $a) { ?>
            <option value="<?= $a['id_anggota']; ?>">
                <?= $a['nama_anggota']; ?>
            </option>
        <?php } ?>
    </select>

    <select name="id_buku" class="form-control mb-2" required>
        <option value=""> === Pilih Buku === </option>
        <?php
        foreach ($buku as $b) {
            if ($b['stok'] > 0) {
                echo "<option value='$b[id_buku]'>
                    $b[judul_buku] (Stok: $b[stok])
                  </option>";
            } else {
                echo "<option disabled>
                    $b[judul_buku] (Stok Habis)
                  </option>";
            }
        }
        ?>
    </select>

    <input type="datetime-local" name="tgl_pinjam" class="form-control mb-2" required>

    <button type="submit" name="tombol" class="btn btn-primary">
        💾 SIMPAN
    </button>

</form>

<?php
if (isset($_POST['tombol'])) {

    $id_anggota = $_POST['id_anggota'];
    $id_buku    = $_POST['id_buku'];
    $tgl_pinjam = $_POST['tgl_pinjam'];

    $status_transaksi = "peminjaman";

    // ==============================
    // CEK STOK SEBELUM PINJAM
    // ==============================
    $cek = mysqli_query($koneksi, "SELECT stok FROM buku WHERE id_buku='$id_buku'");
    $cek_data = mysqli_fetch_assoc($cek);

    if ($cek_data['stok'] <= 0) {
        echo '<script src="../js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'error',title:'Stok Habis',text:'❌ Stok buku habis'}).then(function(){ window.location.assign('?halaman=input_peminjaman'); });</script>";
        exit;
    }

    // ==============================
    // INSERT TRANSAKSI
    // ==============================
    $insert = mysqli_query($koneksi, "
        INSERT INTO transaksi (id_anggota, id_buku, tgl_pinjam, status_transaksi)
        VALUES ('$id_anggota', '$id_buku', '$tgl_pinjam', '$status_transaksi')
    ");

    if ($insert) {

        // ==============================
        // KURANGI STOK & UPDATE STATUS
        // ==============================
        
        // Ambil stok sekarang SEBELUM dikurangi
        $stok_sekarang = $cek_data['stok'];
        
        // Kurangi stok
        $stok_baru = $stok_sekarang - 1;
        
        // Tentukan status berdasarkan stok BARU
        $status_baru = ($stok_baru > 0) ? 'tersedia' : 'tidak';
        
        // Update tabel buku
        mysqli_query($koneksi, "
            UPDATE buku 
            SET stok = '$stok_baru',
                status = '$status_baru'
            WHERE id_buku='$id_buku'
        ");

        echo '<script src="../js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'success',title:'Sukses',text:'✅ Data Berhasil Disimpan',timer:1400,showConfirmButton:false}).then(function(){ window.location.assign('?halaman=data_peminjaman'); });</script>";
    } else {

        echo '<script src="../js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'error',title:'Gagal',text:'❌ Data Gagal Disimpan'}).then(function(){ window.location.assign('?halaman=input_peminjaman'); });</script>";
    }
}
?>