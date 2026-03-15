<?php

// Force logout if visiting index.php, so user must login again
session_start();
if (!empty($_SESSION)) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']);
    }
    session_destroy();
}

require_once __DIR__ . '/koneksi.php';

$library = [
    'hours' => [
        'Senin - Jumat' => '07:00 - 16:00',
        'Sabtu' => '08:00 - 12:00'
    ]
];

// Ambil data statistik dari database
$total_buku = 0;
$total_anggota = 0;
$total_peminjaman = 0;
$total_denda = 0;

if (isset($koneksi) && $koneksi) {
    $resBuku = mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM buku");
    if ($resBuku) {
        $row = mysqli_fetch_assoc($resBuku);
        $total_buku = intval($row['jumlah']);
    }

    $resAnggota = mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM anggota");
    if ($resAnggota) {
        $row = mysqli_fetch_assoc($resAnggota);
        $total_anggota = intval($row['jumlah']);
    }

    $resPeminjaman = mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM transaksi");
    if ($resPeminjaman) {
        $row = mysqli_fetch_assoc($resPeminjaman);
        $total_peminjaman = intval($row['jumlah']);
    }

    $resDenda = mysqli_query($koneksi, "SELECT SUM(GREATEST(t.denda - COALESCE(p.total_bayar, 0), 0)) AS total_denda
        FROM transaksi t
        LEFT JOIN (
            SELECT id_transaksi, SUM(jumlah_dibayar) AS total_bayar
            FROM pembayaran
            WHERE status_pembayaran = 'berhasil'
            GROUP BY id_transaksi
        ) p ON t.id_transaksi = p.id_transaksi");
    if ($resDenda) {
        $row = mysqli_fetch_assoc($resDenda);
        $total_denda = intval($row['total_denda']);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan SMKN 8 PANDEGLANG</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="fas fa-book me-2"></i>Perpustakaan SMKN 8 PANDEGLANG</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#kontak">Kontak</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pendaftaran-anggota.php">Daftar</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section bg-gradient text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Selamat Datang di Perpustakaan Kami</h1>
                    <p class="lead mb-4">Temukan ribuan koleksi buku untuk menunjang pembelajaran dan pengembangan ilmu pengetahuan</p>
                    <p class="mb-4"><i class="fas fa-arrow-down me-2"></i>Silakan login untuk masuk ke perpustakaan</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="login-admin.php" class="btn btn-light btn-lg px-4"><i class="fas fa-user-shield me-2"></i>Login sebagai Admin</a>
                        <a href="login-anggota.php" class="btn btn-outline-light btn-lg px-4"><i class="fas fa-user me-2"></i>Login sebagai Anggota</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="assets/library.svg" alt="Library" class="img-fluid rounded shadow" style="max-height: 420px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <i class="fas fa-book fa-3x text-primary mb-3"></i>
                            <h2 class="card-title h1 text-primary"><?php echo number_format($total_buku); ?></h2>
                            <p class="card-text">Total Koleksi Buku</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <i class="fas fa-users fa-3x text-success mb-3"></i>
                            <h2 class="card-title h1 text-success"><?php echo number_format($total_anggota); ?></h2>
                            <p class="card-text">Total Anggota</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <i class="fas fa-calendar-alt fa-3x text-warning mb-3"></i>
                            <h2 class="card-title h1 text-warning">2018</h2>
                            <p class="card-text">Tahun Berdiri</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Info Sections -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Kontak -->
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow">
                        <div class="card-header bg-primary text-white">
                            <h3 class="card-title mb-0"><i class="fas fa-phone me-2"></i>Kontak Kami</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="text-center">
                                        <i class="fas fa-map-marker-alt fa-2x text-primary mb-2"></i>
                                        <h6>Alamat</h6>
                                        <p class="small">SMK NEGERI 8 PANDEGLANG</p>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="text-center">
                                        <i class="fas fa-phone fa-2x text-success mb-2"></i>
                                        <h6>Telepon</h6>
                                        <p class="small">0895323349735</p>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="text-center">
                                        <i class="fas fa-envelope fa-2x text-warning mb-2"></i>
                                        <h6>Email</h6>
                                        <p class="small">liblarysmkn8pandeglang@gmail.com</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jam Operasional -->
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow">
                        <div class="card-header bg-success text-white">
                            <h3 class="card-title mb-0"><i class="fas fa-clock me-2"></i>Jam Operasional</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($library['hours'] as $day => $time): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><?php echo $day; ?></strong>
                                    <span><?php echo $time; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-primary text-white py-5">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-3">Siap Menjelajahi Dunia Pengetahuan?</h2>
            <p class="lead mb-4">Kunjungi perpustakaan kami dan temukan buku favorit Anda</p>
            <a href="pendaftaran-anggota.php" class="btn btn-light btn-lg px-5"><i class="fas fa-user-plus me-2"></i>Daftar sebagai Anggota</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; 2026 Perpustakaan SMKN 8 PANDEGLANG</p>
        </div>
    </footer>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>