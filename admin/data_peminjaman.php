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

<h4>🛒 Data Peminjaman</h4>
<a href="?halaman=input_peminjaman" class="btn btn-secondary">
    ➕ Tambah Data Peminjaman
</a>

<table class="table table-bordered mt-3">
    <tr class="fw-bold">
        <td>No</td>
        <td>NIS</td>
        <td>Nama Anggota</td>
        <td>Judul Buku</td>
        <td>Tanggal Pinjam</td>
        <td>Kelola</td>
    </tr>

    <?php
    include "../koneksi.php";
    $no = 1;

    $query = "SELECT * FROM transaksi,buku,anggota 
              WHERE buku.id_buku=transaksi.id_buku 
              AND anggota.id_anggota=transaksi.id_anggota
              AND transaksi.status_transaksi='peminjaman'
              ORDER BY transaksi.id_transaksi DESC";

    $data = mysqli_query($koneksi, $query);
    foreach ($data as $peminjaman) {
    ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= $peminjaman['nis']; ?></td>
            <td><?= $peminjaman['nama_anggota']; ?></td>
            <td><?= $peminjaman['judul_buku']; ?></td>
            <td><?= date('Y-m-d H:i:s', strtotime($peminjaman['tgl_pinjam'])) ?></td>

            <td>
                <?php
                $pesan = " ✅ Pengembalian buku oleh $peminjaman[nama_anggota], buku $peminjaman[judul_buku]";
                $isi = "'$pesan',$peminjaman[id_transaksi],$peminjaman[id_buku]";
                ?>
                <a onclick="pengembalian(<?= $isi ?>)" class="btn btn-success">✅ Pengembalian</a>

                <?php
                $pesan = " 🗑️Anda yakin ingin menghapus buku oleh $peminjaman[nama_anggota], buku $peminjaman[judul_buku]";
                $isi = "'$pesan',$peminjaman[id_transaksi],$peminjaman[id_buku]";
                ?>
                <a onclick="hapus(<?= $isi ?>)" class="btn btn-danger">🗑️ Hapus</a>
            </td>
        </tr>
    <?php } ?>
</table>

<h4>✅ Data Pengembalian</h4>

<table class="table table-bordered mt-3">
    <tr class="fw-bold">
        <td>No</td>
        <td>NIS</td>
        <td>Nama Anggota</td>
        <td>Judul Buku</td>
        <td>Tanggal Pinjam</td>
        <td>Tanggal Pengembalian</td>

        <td>Terlambat</td> <!-- ===== DENDA ===== -->
        <td>Denda</td> <!-- ===== DENDA ===== -->
        <td>Status Pembayaran</td> <!-- ===== STATUS PEMBAYARAN ===== -->

        <td>Kelola</td>
    </tr>

    <?php
    $no = 1;
    $query_kembali = "SELECT * FROM transaksi,buku,anggota 
                      WHERE buku.id_buku=transaksi.id_buku 
                      AND anggota.id_anggota=transaksi.id_anggota
                      AND transaksi.status_transaksi='pengembalian'
                      ORDER BY transaksi.id_transaksi DESC";

    $data_kembali = mysqli_query($koneksi, $query_kembali);
    foreach ($data_kembali as $pengembalian) {
    ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= $pengembalian['nis']; ?></td>
            <td><?= $pengembalian['nama_anggota']; ?></td>
            <td><?= $pengembalian['judul_buku']; ?></td>
            <td><?= date('Y-m-d H:i:s', strtotime($pengembalian['tgl_pinjam'])) ?></td>
            <td><?= date('Y-m-d H:i:s', strtotime($pengembalian['tgl_kembali'])) ?></td>

            <!-- ===== DENDA ===== -->
            <td>
                <?= $pengembalian['terlambat']; ?> Hari
            </td>

            <!-- ===== DENDA ===== -->
            <td style="color:<?= ($pengembalian['denda'] > 0) ? 'red' : 'green' ?>; font-weight:bold;">
                Rp <?= number_format($pengembalian['denda'], 0, ',', '.'); ?>
            </td>

            <!-- ===== STATUS PEMBAYARAN ===== -->
            <td>
                <?php
                // Cek status pembayaran
                if ($pengembalian['denda'] > 0) {
                    $check_stmt = mysqli_query($koneksi, "SELECT SUM(jumlah_dibayar) as total_dibayar, status_pembayaran FROM pembayaran WHERE id_transaksi={$pengembalian['id_transaksi']} AND status_pembayaran='berhasil' GROUP BY id_transaksi");
                    $check_data = mysqli_fetch_assoc($check_stmt);
                    $total_dibayar = $check_data['total_dibayar'] ?? 0;

                    if ($total_dibayar >= $pengembalian['denda']) {
                        echo '<span class="badge bg-success">✅ Sudah Dibayar</span>';
                    } else {
                        $pending_stmt = mysqli_query($koneksi, "SELECT COUNT(*) as pending_count FROM pembayaran WHERE id_transaksi={$pengembalian['id_transaksi']} AND status_pembayaran='pending'");
                        $pending_data = mysqli_fetch_assoc($pending_stmt);

                        if ($pending_data['pending_count'] > 0) {
                            echo '<span class="badge bg-warning">⏳ Menunggu Verifikasi</span>';
                        } else {
                            echo '<span class="badge bg-danger">❌ Belum Dibayar</span>';
                        }
                    }
                } else {
                    echo '<span class="badge bg-secondary">-</span>';
                }
                ?>
            </td>

            <td>
                <?php
                $pesan = " 🗑️Anda yakin ingin menghapus data pengembalian oleh $pengembalian[nama_anggota]";
                $isi = "'$pesan',$pengembalian[id_transaksi],$pengembalian[id_buku]";
                ?>
                <a onclick="hapus(<?= $isi ?>)" class="btn btn-danger">🗑️ Hapus</a>
            </td>
        </tr>
    <?php } ?>
</table>

<script>
    function pengembalian(pesan, id_transaksi, id_buku) {
        if (confirm(pesan)) {
            window.location.href = 'dashboard.php?halaman=proses_pengembalian&id=' + id_transaksi + '&buku=' + id_buku;
        }
    }

    function hapus(pesan, id_transaksi, id_buku) {
        if (confirm(pesan)) {
            window.location.href = 'dashboard.php?halaman=hapus&id=' + id_transaksi + '&buku=' + id_buku;
        }
    }
</script>