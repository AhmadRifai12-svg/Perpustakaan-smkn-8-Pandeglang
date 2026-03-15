<?php
session_start();
if (empty($_SESSION['id_admin'])) {
    header("location:../login-admin.php");
    exit;
}

// Sambungan database untuk statistik
require '../koneksi.php';

// Ambil statistik utama
$total_buku = 0;
$total_anggota = 0;
$total_peminjaman = 0;
$total_denda = 0;

if (isset($koneksi) && $koneksi) {
    $res = mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM buku");
    if ($res) {
        $total_buku = intval(mysqli_fetch_assoc($res)['jumlah']);
    }

    $res = mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM anggota");
    if ($res) {
        $total_anggota = intval(mysqli_fetch_assoc($res)['jumlah']);
    }

    $res = mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM transaksi");
    if ($res) {
        $total_peminjaman = intval(mysqli_fetch_assoc($res)['jumlah']);
    }

    $res = mysqli_query($koneksi, "SELECT SUM(GREATEST(t.denda - COALESCE(p.total_bayar, 0), 0)) AS total_denda
        FROM transaksi t
        LEFT JOIN (
            SELECT id_transaksi, SUM(jumlah_dibayar) AS total_bayar
            FROM pembayaran
            WHERE status_pembayaran = 'berhasil'
            GROUP BY id_transaksi
        ) p ON t.id_transaksi = p.id_transaksi");
    if ($res) {
        $total_denda = intval(mysqli_fetch_assoc($res)['total_denda']);
    }
}

// Set page title dan include header admin
$pageTitle = 'Dashboard Admin';
require '../includes/header_admin.php';
?>

<div class="mb-5">
    <h4>HALAMAN ADMIN | Aplikasi Perpustakaan SMKN 8 PANDEGLANG Digital</h4>

    <div class="card p-4 mt-4">
        <?php
        $halaman = isset($_GET['halaman']) ? $_GET['halaman'] : "";
        if (file_exists($halaman . ".php")) {
            include $halaman . ".php";
        } else { ?>
            <div class="text-center mb-4">
                <h4 class="fw-semibold">Selamat Datang <?= htmlspecialchars($_SESSION['nama_admin']); ?> 👋</h4>
                <p class="text-secondary mb-0">Ringkasan kinerja perpustakaan dalam satu tampilan.</p>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6 col-lg-3">
                            <div class="bg-white rounded-4 shadow-sm p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-secondary small">Total Buku</div>
                                        <div class="h4 mb-0"><?= number_format($total_buku); ?></div>
                                    </div>
                                    <div class="text-primary fs-3">
                                        <i class="fas fa-book"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="bg-white rounded-4 shadow-sm p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-secondary small">Total Anggota</div>
                                        <div class="h4 mb-0"><?= number_format($total_anggota); ?></div>
                                    </div>
                                    <div class="text-success fs-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="bg-white rounded-4 shadow-sm p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-secondary small">Total Peminjaman</div>
                                        <div class="h4 mb-0"><?= number_format($total_peminjaman); ?></div>
                                    </div>
                                    <div class="text-info fs-3">
                                        <i class="fas fa-book-reader"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="bg-white rounded-4 shadow-sm p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-secondary small">Denda Tertunggak</div>
                                        <div class="h4 mb-0">Rp <?= number_format($total_denda, 0, ',', '.'); ?></div>
                                    </div>
                                    <div class="text-danger fs-3">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buku Tersedia -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-book me-2"></i>Buku Tersedia (10 Terbaru)</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Judul Buku</th>
                                            <th>Pengarang</th>
                                            <th>Penerbit</th>
                                            <th>Stok</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $buku_res = mysqli_query($koneksi, "SELECT * FROM buku ORDER BY id_buku DESC LIMIT 10");
                                        $no_buku = 1;
                                        if ($buku_res && mysqli_num_rows($buku_res) > 0) {
                                            while ($buku = mysqli_fetch_assoc($buku_res)) { ?>
                                                <tr>
                                                    <td><?= $no_buku++; ?></td>
                                                    <td><?= htmlspecialchars($buku['judul_buku']); ?></td>
                                                    <td><?= htmlspecialchars($buku['pengarang']); ?></td>
                                                    <td><?= htmlspecialchars($buku['penerbit']); ?></td>
                                                    <td><span class="badge <?= $buku['stok'] > 0 ? 'bg-success' : 'bg-danger' ?>"><?= $buku['stok']; ?></span></td>
                                                    <td><span class="badge bg-info"><?= htmlspecialchars($buku['status']); ?></span></td>
                                                </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada buku tersedia</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Peminjaman Terbaru -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Peminjaman Terbaru (5 Terbaru)</h5>
                            <a href="?halaman=data_peminjaman" class="btn btn-outline-light btn-sm">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Anggota</th>
                                            <th>Judul Buku</th>
                                            <th>Tgl Pinjam</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $peminjaman_query = "SELECT t.id_transaksi, a.nama_anggota, b.judul_buku, t.tgl_pinjam 
                                                            FROM transaksi t 
                                                            JOIN buku b ON b.id_buku = t.id_buku 
                                                            JOIN anggota a ON a.id_anggota = t.id_anggota 
                                                            WHERE t.status_transaksi = 'peminjaman' 
                                                            ORDER BY t.id_transaksi DESC LIMIT 5";
                                        $peminjaman_res = mysqli_query($koneksi, $peminjaman_query);
                                        $no_pinjam = 1;
                                        if ($peminjaman_res && mysqli_num_rows($peminjaman_res) > 0) {
                                            while ($pinjam = mysqli_fetch_assoc($peminjaman_res)) { ?>
                                                <tr>
                                                    <td><?= $no_pinjam++; ?></td>
                                                    <td><?= htmlspecialchars($pinjam['nama_anggota']); ?></td>
                                                    <td><?= htmlspecialchars($pinjam['judul_buku']); ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($pinjam['tgl_pinjam'])); ?></td>
                                                </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada peminjaman aktif</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Denda Tertunggak (Conditional) -->
            <?php
            $denda_query = "SELECT a.nama_anggota, b.judul_buku, t.denda, t.tgl_kembali, p.total_bayar 
                            FROM transaksi t 
                            JOIN buku b ON b.id_buku = t.id_buku 
                            JOIN anggota a ON a.id_anggota = t.id_anggota 
                            LEFT JOIN (
                                SELECT id_transaksi, SUM(jumlah_dibayar) AS total_bayar 
                                FROM pembayaran WHERE status_pembayaran = 'berhasil' 
                                GROUP BY id_transaksi
                            ) p ON t.id_transaksi = p.id_transaksi 
                            WHERE t.status_transaksi = 'pengembalian' 
                            AND (t.denda - COALESCE(p.total_bayar, 0)) > 0 
                            ORDER BY t.id_transaksi DESC LIMIT 5";
            $denda_res = mysqli_query($koneksi, $denda_query);
            if ($denda_res && mysqli_num_rows($denda_res) > 0) { ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Denda Tertunggak (5 Teratas)</h5>
                                <a href="?halaman=data_pembayaran" class="btn btn-outline-light btn-sm">Kelola Pembayaran</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Anggota</th>
                                                <th>Judul Buku</th>
                                                <th>Tgl Kembali</th>
                                                <th>Denda</th>
                                                <th>Sudah Bayar</th>
                                                <th>Sisa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no_denda = 1;
                                            while ($denda = mysqli_fetch_assoc($denda_res)) { 
                                                $sisa = $denda['denda'] - ($denda['total_bayar'] ?? 0); ?>
                                                <tr>
                                                    <td><?= $no_denda++; ?></td>
                                                    <td><?= htmlspecialchars($denda['nama_anggota']); ?></td>
                                                    <td><?= htmlspecialchars($denda['judul_buku']); ?></td>
                                                    <td><?= date('d/m/Y', strtotime($denda['tgl_kembali'])); ?></td>
                                                    <td><strong>Rp <?= number_format($denda['denda'], 0, ',', '.'); ?></strong></td>
                                                    <td>Rp <?= number_format($denda['total_bayar'] ?? 0, 0, ',', '.'); ?></td>
                                                    <td><span class="badge bg-warning">Rp <?= number_format($sisa, 0, ',', '.'); ?></span></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

        <?php } ?>
    </div>
</div>

<?php require '../includes/footer_admin.php'; ?>