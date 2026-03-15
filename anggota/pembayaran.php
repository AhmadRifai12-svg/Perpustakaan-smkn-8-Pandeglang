<?php
require '../koneksi.php';

// Session check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id_anggota'])) {
    header("Location: ../login-anggota.php");
    exit;
}

$id_anggota = $_SESSION['id_anggota'];
$nama_anggota = $_SESSION['nama_anggota'] ?? '';

// Ambil semua transaksi dengan denda yang belum dibayar
$query = "
    SELECT t.*, b.judul_buku, a.nama_anggota
    FROM transaksi t
    JOIN buku b ON t.id_buku = b.id_buku
    JOIN anggota a ON t.id_anggota = a.id_anggota
    WHERE t.id_anggota = ? AND t.denda > 0
    ORDER BY t.tgl_pinjam DESC
";

$stmt = $koneksi->prepare($query);
if (!$stmt) {
    die("SQL ERROR: " . $koneksi->error);
}
$stmt->bind_param('i', $id_anggota);
$stmt->execute();
$result_denda = $stmt->get_result();
$transaksi_denda = $result_denda->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Ambil data pembayaran yang pending dan berhasil
$query_pembayaran = "
    SELECT p.*, t.id_buku, b.judul_buku, t.denda as total_denda, t.status_transaksi
    FROM pembayaran p
    JOIN transaksi t ON p.id_transaksi = t.id_transaksi
    JOIN buku b ON t.id_buku = b.id_buku
    WHERE p.id_anggota = ?
    ORDER BY p.tanggal_pembayaran DESC
";

$stmt_pembayaran = $koneksi->prepare($query_pembayaran);
if (!$stmt_pembayaran) {
    die("SQL ERROR: " . $koneksi->error);
}
$stmt_pembayaran->bind_param('i', $id_anggota);
$stmt_pembayaran->execute();
$result_pembayaran = $stmt_pembayaran->get_result();
$pembayaran_list = $result_pembayaran->fetch_all(MYSQLI_ASSOC);
$stmt_pembayaran->close();

// Hitung total denda dan sisa per transaksi
$total_denda = 0;
foreach ($transaksi_denda as &$item) {
    $check_stmt = $koneksi->prepare("SELECT SUM(jumlah_dibayar) as total_dibayar FROM pembayaran WHERE id_transaksi = ? AND status_pembayaran = 'berhasil'");
    $check_stmt->bind_param('i', $item['id_transaksi']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_data = $check_result->fetch_assoc();
    $check_stmt->close();

    $total_dibayar = $check_data['total_dibayar'] ?? 0;
    $item['sisa_denda'] = $item['denda'] - $total_dibayar;

    if ($item['sisa_denda'] > 0) {
        $total_denda += $item['sisa_denda'];
    }
}
unset($item); // break reference
?>

<?php
// gunakan header konsisten untuk anggota
$pageTitle = 'Pembayaran Denda';
require '../includes/header_anggota.php';
?>

<style>
    /* Hidden class for toggling payment methods */
    .hidden {
        display: none !important;
    }

    /* Status Badge Styling */
    .status-pembayaran-box {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #4e73df;
    }

    .badge.bg-pending {
        background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%) !important;
        color: white !important;
        padding: 8px 12px !important;
        font-size: 12px !important;
    }

    .badge.bg-verified {
        background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%) !important;
        color: #1c1c1c !important;
        padding: 8px 12px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
    }

    .badge.bg-rejected {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
        color: #1c1c1c !important;
        padding: 8px 12px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
    }

    /* Total Denda Box */
    .total-denda-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        text-align: center;
    }

    .total-denda-amount {
        font-size: 36px;
        font-weight: 700;
        margin-top: 10px;
    }

    /* Alert styling untuk status tunai pending */
    .alert.alert-tunai-pending {
        background: linear-gradient(135deg, #fff5e6 0%, #ffe8cc 100%);
        border-left: 4px solid #ff9800;
        border-radius: 8px;
    }

    /* Denda Card */
    .denda-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        margin-bottom: 15px;
    }

    .denda-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .denda-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
    }
</style>

<div class="mt-3 mb-5">
    <h4>Halaman Anggota | Aplikasi Perpustakaan SMKN 8 PANDEGLANG Digital</h4>
    <div class="mb-3">
        <a href="dashboard.php" class="btn btn-success text-white">Dashboard</a>
        <a href="dashboard.php?halaman=history" class="btn btn-success text-white">History Peminjaman</a>
        <a href="pembayaran.php" class="btn btn-warning text-white">💰 Pembayaran Denda</a>
        <a href="logout.php" id="btnLogout" class="btn btn-danger text-white">Logout</a>
    </div>

    <div class="card p-3 shadow-sm">
        <h2 class="mb-4">💰 Pembayaran Denda</h2>

        <?php if ($total_denda > 0): ?>
            <div class="total-denda-box">
                <h3>Total Denda yang Harus Dibayar</h3>
                <div class="total-denda-amount">Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></div>
            </div>

            <!-- Status Pembayaran Pending/Berhasil -->
            <?php if (!empty($pembayaran_list)): ?>
                <div class="status-pembayaran-box">
                    <h5 class="mb-3">📋 Status Pembayaran Anda</h5>
                    <?php foreach ($pembayaran_list as $bayar): ?>
                        <div class="row mb-3 align-items-start">
                            <div class="col-md-7">
                                <strong><?php echo htmlspecialchars($bayar['judul_buku']); ?></strong><br>
                                <small class="text-muted">
                                    Jumlah: <strong>Rp <?php echo number_format($bayar['jumlah_dibayar'], 0, ',', '.'); ?></strong><br>
                                    Metode: <?php echo ucfirst($bayar['metode_pembayaran']); ?> |
                                    <?php echo date('d-m-Y H:i', strtotime($bayar['tanggal_pembayaran'])); ?>
                                </small>
                            </div>
                            <div class="col-md-5 text-end">
                                <?php
                                if ($bayar['status_pembayaran'] == 'berhasil') {
                                    if ($bayar['metode_pembayaran'] == 'tunai') {
                                        echo '<span class="badge bg-pending">⏳ Menunggu Verifikasi Admin</span>';
                                    } else {
                                        echo '<span class="badge bg-verified">✅ Diverifikasi</span>';
                                    }
                                } elseif ($bayar['status_pembayaran'] == 'pending') {
                                    echo '<span class="badge bg-warning text-white">⏳ Diproses</span>';
                                } else {
                                    echo '<span class="badge bg-rejected">❌ Ditolak - Bayar Ulang</span>';
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Daftar Denda -->
                <div class="col-md-8">
                    <h4 class="mb-3">Rincian Denda</h4>
                    <?php foreach ($transaksi_denda as $item):
                        if (empty($item['sisa_denda']) || $item['sisa_denda'] <= 0) {
                            continue;
                        }
                        $sisa_denda = $item['sisa_denda'];
                    ?>
                        <div class="card denda-card">
                            <div class="denda-header">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($item['judul_buku']); ?></h5>
                                        <small class="text-muted">
                                            Terpinjam: <?php echo date('d-m-Y', strtotime($item['tgl_pinjam'])); ?>
                                            | Terlambat: <?php echo $item['terlambat']; ?> hari
                                        </small>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <strong class="text-danger">Rp <?php echo number_format($sisa_denda, 0, ',', '.'); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Form Pembayaran -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Form Pembayaran</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Cek apakah ada pembayaran tunai yang menunggu verifikasi admin (berhasil tapi belum auto return)
                            $pending_verification_tunai = 0;
                            foreach ($pembayaran_list as $p) {
                                if ($p['status_pembayaran'] == 'berhasil' && $p['metode_pembayaran'] == 'tunai' && $p['status_transaksi'] == 'peminjaman') {
                                    $pending_verification_tunai++;
                                }
                            }
                            ?>

                            <?php if ($pending_verification_tunai > 0): ?>
                                <div class="alert alert-info" role="alert">
                                    <h4 class="alert-heading">⏳ Menunggu Verifikasi Admin</h4>
                                    <p class="mb-2">Anda memiliki <strong><?php echo $pending_verification_tunai; ?></strong> pembayaran tunai yang sedang menunggu verifikasi dari admin.</p>
                                    <hr>
                                    <p class="mb-0"><small>✓ Pembayaran sudah berhasil dikirim<br>✓ Admin akan mengonfirmasi dalam 1-24 jam<br>✓ Anda akan dapat mengembalikan buku setelah diverifikasi</small></p>
                                </div>
                            <?php endif; ?>

                            <form id="formPembayaran" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Nama Anggota</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($nama_anggota); ?>" disabled>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Total Denda</label>
                                    <input type="text" class="form-control" value="Rp <?php echo number_format($total_denda, 0, ',', '.'); ?>" disabled>
                                    <input type="hidden" name="total_denda" value="<?php echo $total_denda; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Jumlah Pembayaran</label>
                                    <input type="number" class="form-control" name="jumlah_pembayaran" min="0" max="<?php echo $total_denda; ?>" value="<?php echo $total_denda; ?>" required>
                                    <small class="text-muted">Maksimal: Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Metode Pembayaran</label>
                                    <select class="form-select" name="metode_pembayaran" id="metodePembayaran" required>
                                        <option value="">-- Pilih Metode --</option>
                                        <option value="qris">QRIS (Pembayaran Otomatis)</option>
                                        <option value="tunai">Tunai (Bayar di Tempat)</option>
                                    </select>
                                </div>

                                <!-- DOKU Payment Info -->
                                <div id="dokuInfo" class="metode-pembayaran hidden">
                                    <h6>🌐 Pembayaran via DOKU:</h6>
                                    <div class="text-center mb-3">
                                        <img src="https://www.doku.com/wp-content/uploads/2021/06/Logo-DOKU-e162371962.png" alt="DOKU" class="img-fluid" style="max-width: 120px;" onerror="this.style.display='none'">
                                    </div>
                                    <ul class="mb-3">
                                        <li>Anda akan diarahkan ke halaman pembayaran DOKU</li>
                                        <li>Pilih metode pembayaran (GoPay, OVO, DANA, LinkAja, Bank Transfer, Kartu Kredit)</li>
                                        <li>Lakukan pembayaran sesuai instruksi</li>
                                        <li>Setelah berhasil, Anda akan dikembalikan otomatis</li>
                                    </ul>
                                    <div class="alert alert-info mb-0">
                                        <strong>💡 Keunggulan DOKU:</strong><br>
                                        • Pembayaran instan dan otomatis<br>
                                        • Banyak pilihan metode pembayaran
                                    </div>
                                </div>

                                <!-- QRIS Payment Info -->
                                <div id="qrisInfo" class="metode-pembayaran hidden">
                                    <h6>📱 Pembayaran QRIS:</h6>
                                    <div class="text-center mb-3">
                                        <img src="../qris.jpeg" alt="QRIS" class="img-fluid" style="max-width: 200px; border: 2px solid #0066cc; border-radius: 10px;">
                                    </div>
                                    <ul class="mb-3">
                                        <li>Scan QR Code menggunakan aplikasi banking/e-wallet</li>
                                        <li>Masukkan jumlah pembayaran sesuai nominal</li>
                                        <li>Simpan bukti pembayaran (screenshot)</li>
                                        <li>Unggah bukti pembayaran di bawah</li>
                                    </ul>
                                </div>

                                <!-- Tunai Payment Info -->
                                <div id="tunaiInfo" class="metode-pembayaran hidden">
                                    <h6>💵 Pembayaran Tunai:</h6>
                                    <ul class="mb-3">
                                        <li>Datang ke perpustakaan untuk membayar langsung</li>
                                        <li>Serahkan uang pembayaran kepada petugas</li>
                                        <li>Minta bukti pembayaran/struk</li>
                                    </ul>
                                    <div class="alert alert-warning mb-0">
                                        <strong>⏳ Catatan:</strong> Pembayaran tunai memerlukan verifikasi dari admin. Buku akan dikembalikan setelah pembayaran diverifikasi.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Bukti Pembayaran</label>
                                    <input type="file" class="form-control" name="bukti_pembayaran" accept="image/*,.pdf">
                                    <small class="text-muted d-block mt-2">
                                        📸 <strong>Unggah bukti pembayaran Anda:</strong><br>
                                        • Untuk transfer bank: Screenshot konfirmasi transfer<br>
                                        • Untuk e-wallet: Screenshot bukti pembayaran<br>
                                        • Untuk tunai: Struk pembayaran (opsional)<br>
                                        • Format: JPG, PNG, PDF (Max 5MB)
                                    </small>
                                </div>

                                <button type="submit" class="btn btn-success btn-bayar">
                                    💳 Kirim Pembayaran
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> Selamat! Anda tidak memiliki denda yang harus dibayar.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="modalKonfirmasi" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Konten akan diisi dengan JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnKonfirmasi">Konfirmasi</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Logout dengan SweetAlert
    document.getElementById('btnLogout').addEventListener('click', function(e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        Swal.fire({
            title: 'Keluar',
            text: 'Yakin ingin logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, logout',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });

    // Auto-refresh untuk melihat update status pembayaran
    // Jika ada pembayaran tunai yang menunggu verifikasi, refresh halaman setiap 30 detik
    function checkAndRefresh() {
        const pendingElements = document.querySelectorAll('.badge.bg-pending');
        if (pendingElements.length > 0) {
            // Ada pembayaran tunai yang menunggu verifikasi
            // Refresh setiap 30 detik
            setInterval(() => {
                location.reload();
            }, 30000); // 30 detik
        }
    }

    // Jalankan check saat halaman dimuat
    document.addEventListener('DOMContentLoaded', checkAndRefresh);

    // Handle form submission dengan validasi
    document.getElementById('formPembayaran').addEventListener('submit', onPembayaranSubmit);
    document.getElementById('metodePembayaran').addEventListener('change', togglePaymentInfo);

    <?php
    // load external script for this page
    $extraJs = ['../js/pembayaran.js'];
    require '../includes/footer.php';
    ?>