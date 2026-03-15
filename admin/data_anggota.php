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

<h4>👥 Data Anggota</h4>
<a href="?halaman=input_anggota" class="btn btn-secondary">
    ➕ Tambah Data Anggota
</a>
<table class="table table-bordered mt-3">
    <tr class="fw-bold">
        <td>No</td>
        <td>NIS</td>
        <td>Nama Anggota</td>
        <td>Username</td>
        <td>Password</td>
        <td>Kelas</td>
        <td>Kelola</td>
    </tr>
    <?php
    include "../koneksi.php";
    $no = 1;
    $query = "SELECT * FROM anggota ORDER BY id_anggota DESC";
    $data = mysqli_query($koneksi, $query);
    foreach ($data as $anggota) { ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= $anggota['nis']; ?></td>
            <td><?= $anggota['nama_anggota']; ?></td>
            <td><?= $anggota['username']; ?></td>
            <td><?= $anggota['password']; ?></td>
            <td><?= $anggota['kelas']; ?></td>
            <td>
                <a class="btn btn-warning" href="?halaman=edit_anggota&id=<?= $anggota['id_anggota'] ?>">✏️ Edit</a>
                <a onclick="return confirm('Yakin menghapus data')" class="btn btn-danger" href="?halaman=hapus_anggota&id=<?= $anggota['id_anggota'] ?>">🗑️ Hapus</a>
            </td>
        </tr>


    <?php } ?>
</table>