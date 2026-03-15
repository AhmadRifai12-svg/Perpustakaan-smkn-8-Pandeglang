<?php
session_start();
if (empty($_SESSION['id_anggota'])) {
    header("location:../login-anggota.php");
    exit();
}
include "../koneksi.php";

// header konsisten
$pageTitle = 'Dashboard Anggota';
require '../includes/header_anggota.php';
?>

<div class="mb-5">
    <h4>Halaman Anggota | Aplikasi Perpustakaan SMKN 8 PANDEGLANG Digital</h4>

    <div class="card p-4 shadow-sm">
        <?php
        $halaman = isset($_GET['halaman']) ? $_GET['halaman'] : "";

        if ($halaman == 'peminjaman' && isset($_GET['id'])) {
            $id_buku = $_GET['id'];
            $id_anggota = $_SESSION['id_anggota'];
            $tgl_pinjam = date('Y-m-d H:i:s');

            // CEK DENDA BELUM TERBAYAR
            $cek_denda = mysqli_query($koneksi, "
                SELECT SUM(t.denda - COALESCE(p.jumlah_dibayar, 0)) as denda_sisa
                FROM transaksi t
                LEFT JOIN pembayaran p ON t.id_transaksi = p.id_transaksi AND p.status_pembayaran = 'berhasil'
                WHERE t.id_anggota = '$id_anggota' AND t.denda > 0
            ");
            $denda_data = mysqli_fetch_assoc($cek_denda);
            $denda_sisa = $denda_data['denda_sisa'] ?? 0;

            if ($denda_sisa > 0) {
                echo '<script src="../js/sweetalert2.all.min.js"></script>';
                echo "<script>Swal.fire({icon:'error',title:'Denda Belum Terbayar',text:'❌ Anda memiliki denda Rp " . number_format($denda_sisa, 0, ',', '.') . " yang harus dibayar terlebih dahulu! Silakan lakukan pembayaran di halaman Pembayaran Denda.',confirmButtonText:'Lihat Pembayaran'}).then(function(result){ if(result.isConfirmed){ window.location.href='pembayaran.php'; } else { window.location.href='dashboard.php'; }});</script>";
                exit;
            }

            // Cek stok buku
            $cek_stok = mysqli_query($koneksi, "SELECT stok FROM buku WHERE id_buku='$id_buku'");
            $data_stok = mysqli_fetch_assoc($cek_stok);

            if ($data_stok['stok'] <= 0) {
                echo '<script src="../js/sweetalert2.all.min.js"></script>';
                echo "<script>Swal.fire({icon:'error',title:'Stok Habis',text:'❌ Stok buku habis!'}).then(function(){ window.location.href='dashboard.php'; });</script>";
                exit;
            }

            $simpan = mysqli_query($koneksi, "INSERT INTO transaksi (id_buku, id_anggota, tgl_pinjam, status_transaksi) 
                                          VALUES ('$id_buku', '$id_anggota', '$tgl_pinjam', 'peminjaman')");

            // Kurangi stok buku sebesar 1 dan update status
            $update = mysqli_query($koneksi, "UPDATE buku 
                                          SET stok = stok - 1,
                                              status = IF(stok - 1 > 0, 'tersedia', 'tidak')
                                          WHERE id_buku = '$id_buku'");

            if ($simpan && $update) {
                echo '<script src="../js/sweetalert2.all.min.js"></script>';
                echo "<script>Swal.fire({icon:'success',title:'Berhasil',text:'✅ Buku berhasil dipinjam!',timer:1200,showConfirmButton:false}).then(function(){ window.location.href='dashboard.php'; });</script>";
            } else {
                echo '<script src="../js/sweetalert2.all.min.js"></script>';
                echo "<script>Swal.fire({icon:'error',title:'Gagal',text:'❌ Gagal meminjam buku!'}).then(function(){ window.location.href='dashboard.php'; });</script>";
            }
        } elseif (!empty($halaman) && file_exists($halaman . ".php")) {
            include $halaman . ".php";
        } else {
        ?>
            <div class="text-center mb-4">
                <h4>Selamat Datang <?= $_SESSION['nama_anggota']; ?> 👋</h4>
            </div>

            <form action="?halaman=cari" method="post" class="mb-4">
                <label class="text-muted">Yuk Cari Buku.</label>
                <input type="text" name="kunci" class="form-control mb-2" required placeholder="Masukan Judul Buku">
                <button type="submit" class="btn btn-primary">🔍 Cari</button>
            </form>

            <h4 class="mt-4">🛒 Daftar Buku Yang Sedang Dipinjam</h4>
            <table class="table table-bordered bg-white">
                <thead class="table-primary">
                    <tr class="fw-bold">
                        <td>No</td>
                        <td>Judul Buku</td>
                        <td>Tanggal Pinjam</td>
                        <td>Aksi</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $id_anggota = $_SESSION['id_anggota'];
                    $query_pinjam = "SELECT * FROM transaksi 
                                 INNER JOIN buku ON buku.id_buku = transaksi.id_buku 
                                 WHERE transaksi.id_anggota = '$id_anggota' 
                                 AND transaksi.status_transaksi = 'peminjaman'";
                    $data_pinjam = mysqli_query($koneksi, $query_pinjam);

                    if (mysqli_num_rows($data_pinjam) > 0) {
                        while ($peminjaman = mysqli_fetch_array($data_pinjam)) { ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= $peminjaman['judul_buku'] ?></td>
                                <td><?= $peminjaman['tgl_pinjam'] ?></td>
                                <td>
                                    <button onclick="pengembalian('Yakin ingin mengembalikan buku ini?', <?= $peminjaman['id_transaksi'] ?>, <?= $peminjaman['id_buku'] ?>)" class="btn btn-sm btn-warning">Kembalikan</button>
                                </td>
                            </tr>
                        <?php }
                    } else { ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">Belum Ada Buku Yang Dipinjam.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <hr>

            <h4 class="mt-4">📚 Daftar Buku Tersedia</h4>
            <div class="row">
                <?php
                $data_buku = mysqli_query($koneksi, "SELECT * FROM buku ORDER BY id_buku DESC");
                while ($buku = mysqli_fetch_array($data_buku)) {
                ?>
                    <div class="col-md-3 mb-3">
                        <div class="card shadow-sm p-3 d-flex h-100">
                            <h5><?= $buku['judul_buku'] ?></h5>
                            <p class="small mb-1"><strong>Pengarang:</strong> <?= $buku['pengarang'] ?></p>
                            <p class="small mb-1"><strong>Penerbit:</strong> <?= $buku['penerbit'] ?></p>
                            <p class="small mb-3"><strong>Tahun:</strong> <?= $buku['tahun_terbit'] ?></p>
                            <p class="small mb-3"><strong>stok:</strong><?= $buku['stok'] ?></p>

                            <div class="mt-auto">
                                <?php if ($buku['stok'] > 0) { ?>
                                    <span class="badge bg-success mb-2 d-block">✅ Tersedia</span>
                                    <a onclick="pinjam('Yakin pinjam <?= addslashes($buku['judul_buku']) ?>?', <?= $buku['id_buku'] ?>)" class="btn btn-primary btn-sm w-100">🛒 Pinjam</a>
                                <?php } else { ?>
                                    <span class="badge bg-danger mb-2 d-block">❌ Tidak Tersedia</span>
                                    <button class="btn btn-secondary btn-sm w-100" disabled>Pinjam</button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php
        }
        ?>
    </div>
</div>

<script>
    // SweetAlert2 untuk aksi pinjam, pengembalian
    function pinjam(pesan, id_buku) {
        Swal.fire({
            title: 'Konfirmasi',
            text: pesan,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, pinjam',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?halaman=peminjaman&id=' + id_buku;
            }
        });
    }

    function pengembalian(pesan, id_transaksi, id_buku) {
        Swal.fire({
            title: 'Konfirmasi',
            text: pesan,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, kembalikan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?halaman=pengembalian&id=' + id_transaksi + '&buku=' + id_buku;
            }
        });
    }
</script>

<?php require '../includes/footer.php'; ?>