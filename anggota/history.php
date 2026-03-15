  <?php
    // Session check untuk keamanan
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['id_anggota'])) {
        header("location:../login-anggota.php");
        exit;
    }
    ?>

  <h4>✅ History Peminjaman</h4>
  <p class="text-muted">Daftar buku yang sudah Anda kembalikan.</p>

  <table class="table table-bordered bg-white shadow-sm">
      <thead class="table-primary">
          <tr class="fw-bold">
              <td>No</td>
              <td>Judul Buku</td>
              <td>Tanggal Pinjam</td>
              <td>Tanggal Pengembalian</td>
              <td>Terlambat</td>
              <td>Denda</td>
              <td>Status Pembayaran</td>
          </tr>
      </thead>
      <tbody>
          <?php
            $no = 1;
            $id_anggota = $_SESSION['id_anggota'];
            $query = "SELECT * FROM transaksi 
                  INNER JOIN buku ON buku.id_buku = transaksi.id_buku 
                  WHERE transaksi.id_anggota = '$id_anggota' 
                  AND transaksi.status_transaksi = 'pengembalian'
                  ORDER BY tgl_kembali DESC";

            $data = mysqli_query($koneksi, $query);

            if (mysqli_num_rows($data) > 0) {
                foreach ($data as $pengembalian) { ?>
                  <tr>
                      <td><?= $no++; ?></td>

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
                                    echo '<span class="badge bg-success">✅ Lunas</span>';
                                } else {
                                    $pending_stmt = mysqli_query($koneksi, "SELECT COUNT(*) as pending_count FROM pembayaran WHERE id_transaksi={$pengembalian['id_transaksi']} AND status_pembayaran='pending'");
                                    $pending_data = mysqli_fetch_assoc($pending_stmt);

                                    if ($pending_data['pending_count'] > 0) {
                                        echo '<span class="badge bg-warning">⏳ Menunggu Verifikasi</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">❌ Belum Bayar</span>';
                                    }
                                }
                            } else {
                                echo '<span class="badge bg-secondary">-</span>';
                            }
                            ?>
                      </td>
                  </tr>
              <?php }
            } else { ?>
              <tr>
                  <td colspan="7" class="text-center text-muted">Belum ada riwayat pengembalian.</td>
              </tr>
          <?php } ?>
      </tbody>
  </table>