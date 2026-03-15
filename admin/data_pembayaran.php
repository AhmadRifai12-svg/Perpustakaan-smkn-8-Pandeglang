<?php
require '../koneksi.php';

// Session check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id_admin'])) {
    header("Location: ../login-admin.php");
    exit;
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get filter status from URL
$filter_status = $_GET['status'] ?? 'all';

// Get all payments with optional filter - updated to show remaining fine
$query = "
    SELECT p.*, a.nama_anggota, b.judul_buku, 
           t.denda as total_denda, 
           t.status_transaksi,
           COALESCE((SELECT SUM(jumlah_dibayar) FROM pembayaran WHERE id_transaksi = t.id_transaksi AND status_pembayaran = 'berhasil'), 0) as total_dibayar
    FROM pembayaran p
    JOIN anggota a ON p.id_anggota = a.id_anggota
    JOIN transaksi t ON p.id_transaksi = t.id_transaksi
    JOIN buku b ON t.id_buku = b.id_buku
";

if ($filter_status === 'perlu_verifikasi') {
    // Tunai payments that still need verification (transaksi belum pengembalian)
    $query .= " WHERE p.metode_pembayaran = 'tunai' AND p.status_pembayaran = 'berhasil' AND t.status_transaksi = 'peminjaman'";
    $stmt = $koneksi->prepare($query . " ORDER BY p.tanggal_pembayaran DESC");
} elseif ($filter_status === 'selesai') {
    // Payments where book has been auto-returned
    $query .= " WHERE t.status_transaksi = 'pengembalian'";
    $stmt = $koneksi->prepare($query . " ORDER BY p.tanggal_pembayaran DESC");
} elseif ($filter_status === 'ditolak') {
    // Rejected payments
    $query .= " WHERE p.status_pembayaran = 'ditolak'";
    $stmt = $koneksi->prepare($query . " ORDER BY p.tanggal_pembayaran DESC");
} else {
    // Default: all payments
    $stmt = $koneksi->prepare($query . " ORDER BY p.tanggal_pembayaran DESC");
}

if (!$stmt) {
    die("SQL ERROR: " . $koneksi->error);
}
$stmt->execute();
$result = $stmt->get_result();
$pembayaran_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get counts for each filter type
$count_query = "
    SELECT 
        'all' as filter_type,
        COUNT(*) as total
    FROM pembayaran
    UNION ALL
    SELECT 
        'perlu_verifikasi' as filter_type,
        COUNT(*) as total
    FROM pembayaran p
    JOIN transaksi t ON p.id_transaksi = t.id_transaksi
    WHERE p.metode_pembayaran = 'tunai' AND p.status_pembayaran = 'berhasil' AND t.status_transaksi = 'peminjaman'
    UNION ALL
    SELECT 
        'selesai' as filter_type,
        COUNT(*) as total
    FROM pembayaran p
    JOIN transaksi t ON p.id_transaksi = t.id_transaksi
    WHERE t.status_transaksi = 'pengembalian'
    UNION ALL
    SELECT 
        'ditolak' as filter_type,
        COUNT(*) as total
    FROM pembayaran
    WHERE status_pembayaran = 'ditolak'
";
$count_stmt = $koneksi->prepare($count_query);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$counts = ['all' => 0, 'perlu_verifikasi' => 0, 'selesai' => 0, 'ditolak' => 0];
while ($row = $count_result->fetch_assoc()) {
    $counts[$row['filter_type']] = $row['total'];
}
$count_stmt->close();

// Set page title dan include header
$pageTitle = 'Data Pembayaran - Admin';
require '../includes/header_admin.php';
?>

<style>
    .status-berhasil {
        background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
        color: #1c1c1c;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
    }

    .status-pending {
        background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
    }

    .status-ditolak {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: #1c1c1c;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
    }

    .filter-btn {
        margin-right: 5px;
        margin-bottom: 10px;
    }

    .filter-btn.active {
        font-weight: bold;
    }

    /* Hidden class for toggling payment methods */
    .hidden {
        display: none !important;
    }

    .modal-body img {
        max-width: 100%;
        height: auto;
    }

    .detail-row {
        border-bottom: 1px solid #eee;
        padding: 8px 0;
    }

    .detail-label {
        font-weight: 600;
        color: #666;
    }

    /* Action buttons styling */
    .btn-action-group {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .btn-sm {
        font-size: 11px;
        padding: 4px 8px;
    }

    .btn-success,
    .btn-danger {
        font-weight: 600;
    }

    /* Table responsive styling */
    .table {
        font-size: 13px;
    }

    .table th,
    .table td {
        vertical-align: middle;
        white-space: nowrap;
    }

    .table-responsive {
        overflow-x: auto;
    }
</style>

.table {
font-size: 13px;
}

.table th,
.table td {
vertical-align: middle;
white-space: nowrap;
}

.table-responsive {
overflow-x: auto;
}
</style>

<h4>HALAMAN ADMIN | Aplikasi Perpustakaan SMKN 8 PANDEGLANG Digital</h4>


<div class="card p-3 shadow-sm">
    <h2 class="mb-4">💰 Data Pembayaran Denda</h2>

    <!-- Filter Buttons -->
    <div class="mb-3">
        <a href="data_pembayaran.php?status=all" class="btn btn-sm filter-btn <?php echo $filter_status === 'all' ? 'btn-primary active' : 'btn-outline-primary'; ?>">
            Semua (<?php echo $counts['all']; ?>)
        </a>
        <a href="data_pembayaran.php?status=perlu_verifikasi" class="btn btn-sm filter-btn <?php echo $filter_status === 'perlu_verifikasi' ? 'btn-warning active' : 'btn-outline-warning'; ?>">
            📋 Perlu Verifikasi (<?php echo $counts['perlu_verifikasi']; ?>)
        </a>
        <a href="data_pembayaran.php?status=selesai" class="btn btn-sm filter-btn <?php echo $filter_status === 'selesai' ? 'btn-success active' : 'btn-outline-success'; ?>">
            ✅ Selesai (<?php echo $counts['selesai']; ?>)
        </a>
        <a href="data_pembayaran.php?status=ditolak" class="btn btn-sm filter-btn <?php echo $filter_status === 'ditolak' ? 'btn-danger active' : 'btn-outline-danger'; ?>">
            ❌ Ditolak (<?php echo $counts['ditolak']; ?>)
        </a>
    </div>

    <?php if (!empty($pembayaran_list)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 130px;">Nama Anggota</th>
                        <th style="width: 100px;">Buku</th>
                        <th style="width: 90px;">Denda</th>
                        <th style="width: 90px;">Dibayar</th>
                        <th style="width: 70px;">Metode</th>
                        <th style="width: 120px;">Tanggal</th>
                        <th style="width: 80px;">Trans</th>
                        <th style="width: 70px;">Status</th>
                        <th style="width: 80px;">Bukti</th>
                        <th style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pembayaran_list as $item): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($item['id_pembayaran']); ?></td>
                            <td><?php echo htmlspecialchars($item['nama_anggota']); ?></td>
                            <td><?php echo htmlspecialchars($item['judul_buku']); ?></td>
                            <td>Rp <?php echo number_format($item['jumlah_denda'], 0, ',', '.'); ?></td>
                            <td><strong>Rp <?php echo number_format($item['jumlah_dibayar'], 0, ',', '.'); ?></strong></td>
                            <td><?php echo ucfirst(htmlspecialchars($item['metode_pembayaran'])); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($item['tanggal_pembayaran'])); ?></td>
                            <td>
                                <?php
                                $trans_status = $item['status_transaksi'] ?? '-';
                                echo $trans_status === 'peminjaman' ? '📖 Dipinjam' : '✅ Dikembalikan';
                                ?>
                            </td>
                            <td>
                                <?php
                                $status_class = 'status-' . $item['status_pembayaran'];
                                echo '<span class="' . $status_class . '">' . ucfirst($item['status_pembayaran']) . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php if (!empty($item['bukti_pembayaran'])): ?>
                                    <button class="btn btn-sm btn-info btn-lihat-bukti"
                                        data-id="<?php echo $item['id_pembayaran']; ?>"
                                        data-bukti="<?php echo htmlspecialchars($item['bukti_pembayaran']); ?>"
                                        data-anggota="<?php echo htmlspecialchars($item['nama_anggota']); ?>"
                                        data-buku="<?php echo htmlspecialchars($item['judul_buku']); ?>"
                                        data-jumlah="<?php echo $item['jumlah_dibayar']; ?>"
                                        data-metode="<?php echo $item['metode_pembayaran']; ?>"
                                        data-status="<?php echo $item['status_pembayaran']; ?>"
                                        data-transaksi="<?php echo $item['status_transaksi']; ?>"
                                        data-denda="<?php echo $item['total_denda']; ?>"
                                        data-total-dibayar="<?php echo $item['total_dibayar']; ?>">
                                        Lihat
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['status_pembayaran'] == 'berhasil' && $item['metode_pembayaran'] == 'tunai' && $item['status_transaksi'] == 'peminjaman'): ?>
                                    <div class="btn-action-group">
                                        <button class="btn btn-sm btn-success" onclick="verifikasiPembayaran(<?php echo $item['id_pembayaran']; ?>, 'berhasil')" title="Terima Pembayaran">
                                            ✓ Terima
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="verifikasiPembayaran(<?php echo $item['id_pembayaran']; ?>, 'ditolak')" title="Tolak Pembayaran">
                                            ✗ Tolak
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" title="Tidak ada aksi">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            Tidak ada data pembayaran.
        </div>
    <?php endif; ?>
</div>
</div>

<!-- Modal for viewing payment proof -->
<div class="modal fade" id="buktiModal" tabindex="-1" aria-labelledby="buktiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="buktiModalLabel">Detail Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-row">
                            <span class="detail-label">ID Pembayaran:</span> <span id="modalId"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Nama Anggota:</span> <span id="modalAnggota"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Buku:</span> <span id="modalBuku"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Denda:</span> <span id="modalTotalDenda"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Sudah Dibayar:</span> <span id="modalTotalDibayar"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Jumlah Dibayar:</span> <span id="modalJumlah"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Metode:</span> <span id="modalMetode"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status:</span> <span id="modalStatus"></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status Transaksi:</span> <span id="modalStatusTransaksi"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Bukti Pembayaran:</h6>
                        <div id="buktiContainer" style="max-height: 400px; overflow-y: auto;">
                            <img id="modalBuktiImg" src="" alt="Bukti Pembayaran" class="img-fluid rounded" style="display: none;">
                            <iframe id="modalBuktiPdf" src="" style="width: 100%; height: 400px; border: 1px solid #ddd; border-radius: 5px; display: none;"></iframe>
                            <a id="modalBuktiLink" href="" class="btn btn-sm btn-primary" target="_blank" style="display: none;">
                                📥 Download Bukti
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-info" id="btnPrintStruk" onclick="printAdminStruk()" style="display: none;">
                    🖨️ Cetak Struk
                </button>
                <div id="verifikasiButtonGroup" style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-success" id="btnVerifikasi" onclick="verifikasiDariModal(currentIdPembayaran, 'berhasil')" style="display: none;">
                        ✓ Terima Pembayaran
                    </button>
                    <button type="button" class="btn btn-danger" id="btnTolak" onclick="verifikasiDariModal(currentIdPembayaran, 'ditolak')" style="display: none;">
                        ✗ Tolak Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<script src="../js/sweetalert2.all.min.js"></script>
<script>
    // CSRF Token
    const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
    let currentIdPembayaran = 0;
    let buktiModal = null;

    document.addEventListener('DOMContentLoaded', function() {
        buktiModal = new bootstrap.Modal(document.getElementById('buktiModal'));
    });

    // Store current modal data for printing
    let currentModalData = {};

    function lihatBukti(id, bukti, anggota, buku, jumlah, metode, status) {
        // Validate ID
        if (!id || id <= 0 || isNaN(id)) {
            Swal.fire('Error!', 'ID pembayaran tidak valid', 'error');
            return;
        }

        currentIdPembayaran = id;
        currentModalData = {
            id: id,
            anggota: anggota,
            buku: buku,
            jumlah: jumlah,
            metode: metode,
            status: status,
            tanggal: new Date().toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
        };

        document.getElementById('modalId').textContent = '#' + id;
        document.getElementById('modalAnggota').textContent = anggota;
        document.getElementById('modalBuku').textContent = buku;
        document.getElementById('modalTotalDenda').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalDenda);
        document.getElementById('modalTotalDibayar').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalDibayar);
        document.getElementById('modalJumlah').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(jumlah);
        document.getElementById('modalMetode').textContent = metode.charAt(0).toUpperCase() + metode.slice(1);

        const statusEl = document.getElementById('modalStatus');
        statusEl.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        statusEl.className = 'status-' + status;

        const statusTransaksiEl = document.getElementById('modalStatusTransaksi');
        statusTransaksiEl.textContent = statusTransaksi === 'peminjaman' ? '📖 Dipinjam' : '✅ Dikembalikan';
        statusTransaksiEl.className = statusTransaksi === 'peminjaman' ? 'text-warning' : 'text-success';

        // Handle bukti display based on file type
        const buktiPath = '../uploads/bukti_pembayaran/' + bukti;
        const imageImg = document.getElementById('modalBuktiImg');
        const pdfIframe = document.getElementById('modalBuktiPdf');
        const downloadLink = document.getElementById('modalBuktiLink');

        // Hide all elements first
        imageImg.style.display = 'none';
        pdfIframe.style.display = 'none';
        downloadLink.style.display = 'none';

        // Check file extension
        if (bukti.toLowerCase().endsWith('.pdf')) {
            // Show PDF in iframe
            pdfIframe.src = buktiPath;
            pdfIframe.style.display = 'block';
            // Also show download link
            downloadLink.href = buktiPath;
            downloadLink.innerHTML = '📥 Download PDF';
            downloadLink.style.display = 'inline-block';
            downloadLink.style.marginTop = '10px';
        } else {
            // Show image
            imageImg.src = buktiPath;
            imageImg.style.display = 'block';
        }

        // Show/hide action buttons based on status
        const btnVerifikasi = document.getElementById('btnVerifikasi');
        const btnTolak = document.getElementById('btnTolak');
        const btnPrint = document.getElementById('btnPrintStruk');
        const verifikasiButtonGroup = document.getElementById('verifikasiButtonGroup');

        if (status === 'berhasil' && metode === 'tunai' && statusTransaksi === 'peminjaman') {
            btnVerifikasi.style.display = 'inline-block';
            btnTolak.style.display = 'inline-block';
            btnPrint.style.display = 'inline-block';
            verifikasiButtonGroup.style.display = 'flex';
        } else {
            btnVerifikasi.style.display = 'none';
            btnTolak.style.display = 'none';
            btnPrint.style.display = 'none';
            verifikasiButtonGroup.style.display = 'none';
        }

        buktiModal.show();
    }

    function printAdminStruk() {
        const data = currentModalData;
        const htmlStruk = `
            <html>
            <head>
                <title>Struk Pembayaran</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .struk { max-width: 450px; margin: 0 auto; border: 2px solid #333; padding: 20px; background: white; }
                    .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
                    .header h2 { margin: 5px 0; font-size: 20px; }
                    .header p { margin: 3px 0; font-size: 12px; color: #666; }
                    .detail { margin-bottom: 15px; }
                    .detail-row { display: flex; justify-content: space-between; margin: 8px 0; font-size: 14px; }
                    .detail-row .label { font-weight: 600; }
                    .separator { border-top: 1px dashed #333; margin: 10px 0; }
                    .total { text-align: right; margin-top: 10px; font-weight: 600; font-size: 16px; }
                    .footer { text-align: center; margin-top: 15px; font-size: 12px; color: #666; border-top: 2px solid #333; padding-top: 10px; }
                    .success-stamp { text-align: center; color: green; font-weight: 600; margin: 10px 0; font-size: 18px; }
                    @media print {
                        body { margin: 0; padding: 0; }
                        .struk { border: none; box-shadow: none; }
                    }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                <div class="struk">
                    <div class="header">
                        <h2>📚 PERPUSTAKAAN DIGITAL</h2>
                        <p>Aplikasi Perpustakaan SMKN 8 PANDEGLANG</p>
                        <p style="margin-top: 5px;">BUKTI PEMBAYARAN DENDA</p>
                    </div>
                    
                    <div class="success-stamp">✅ PEMBAYARAN BERHASIL</div>
                    
                    <div class="detail">
                        <div class="detail-row">
                            <span class="label">No. Pembayaran:</span>
                            <span>#${data.id}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Nama Anggota:</span>
                            <span>${data.anggota}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Judul Buku:</span>
                            <span>${data.buku}</span>
                        </div>
                        <div class="separator"></div>
                        <div class="detail-row">
                            <span class="label">Jumlah Pembayaran:</span>
                            <span></span>
                        </div>
                        <div class="detail-row" style="text-align: right;">
                            <span style="width: 100%; text-align: right; font-weight: 600; font-size: 16px;">Rp ${new Intl.NumberFormat('id-ID').format(data.jumlah)}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Metode:</span>
                            <span>${data.metode}</span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Tanggal:</span>
                            <span>${data.tanggal}</span>
                        </div>
                    </div>
                    
                    <div class="separator"></div>
                    <div class="footer">
                        <p><strong>Terima kasih atas pembayaran</strong></p>
                        <p>Diverifikasi oleh: Admin Perpustakaan</p>
                        <p style="margin-top: 10px; color: #999; font-size: 11px;">Printed: ${new Date().toLocaleString('id-ID')}</p>
                    </div>
                </div>
            </body>
            </html>
        `;
        const printWindow = window.open('', '', 'height=600,width=500');
        printWindow.document.write(htmlStruk);
        printWindow.document.close();
    }

    // Event listener untuk tombol 'Lihat Bukti'
    document.querySelectorAll('.btn-lihat-bukti').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = parseInt(this.getAttribute('data-id'));
            const bukti = this.getAttribute('data-bukti');
            const anggota = this.getAttribute('data-anggota');
            const buku = this.getAttribute('data-buku');
            const jumlah = parseInt(this.getAttribute('data-jumlah'));
            const metode = this.getAttribute('data-metode');
            const status = this.getAttribute('data-status');
            const statusTransaksi = this.getAttribute('data-transaksi');
            const totalDenda = parseInt(this.getAttribute('data-denda'));
            const totalDibayar = parseInt(this.getAttribute('data-total-dibayar'));

            lihatBukti(id, bukti, anggota, buku, jumlah, metode, status, statusTransaksi, totalDenda, totalDibayar);
        });
    });

    function verifikasiDariModal(id, status) {
        if (!id || id <= 0) {
            Swal.fire('Error!', 'ID pembayaran tidak valid', 'error');
            return;
        }
        buktiModal.hide();
        setTimeout(() => {
            verifikasiPembayaran(id, status);
        }, 300);
    }

    function verifikasiPembayaran(id, status) {
        Swal.fire({
            title: 'Konfirmasi',
            text: 'Apakah Anda yakin ingin ' + (status === 'berhasil' ? 'memverifikasi' : 'menolak') + ' pembayaran ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                const params = new URLSearchParams();
                params.append('id_pembayaran', id);
                params.append('status', status);
                params.append('csrf_token', csrfToken);

                fetch('verifikasi_pembayaran.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: params.toString()
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.success) {
                            const icon = data.auto_return ? 'success' : 'info';
                            const title = data.auto_return ? '✅ Pengembalian Otomatis!' : 'Berhasil';
                            Swal.fire(title, data.message, icon).then(() => {
                                location.reload();
                            });
                        } else {
                            const errMsg = (data && data.message) ? data.message : 'Terjadi kesalahan saat memverifikasi pembayaran';
                            Swal.fire('Error!', errMsg, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'Terjadi kesalahan: ' + error.message, 'error');
                    });
            }
        });
    }

    // Logout confirmation
    document.getElementById('btnLogout').addEventListener('click', function(e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        Swal.fire({
            title: 'Konfirmasi Logout',
            text: 'Apakah Anda yakin ingin logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Logout',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = href;
            }
        });
    });
</script>

<?php require '../includes/footer_admin.php'; ?>