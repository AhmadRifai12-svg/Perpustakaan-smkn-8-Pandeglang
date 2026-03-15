<?php
require '../koneksi.php';

// Hindari double session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login admin
if (empty($_SESSION['id_admin'])) {
    header("Location: ../login-admin.php");
    exit;
}

$id   = (int)($_GET['id'] ?? 0);
$buku = (int)($_GET['buku'] ?? 0);

if ($id > 0 && $buku > 0) {

    // ==============================
    // Ambil tanggal pinjam
    // ==============================
    $stmt = $koneksi->prepare("SELECT tgl_pinjam FROM transaksi WHERE id_transaksi=?");
    if (!$stmt) {
        die("SQL ERROR (SELECT): " . $koneksi->error);
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if (!$data) {
        die("Data transaksi tidak ditemukan.");
    }

    // ==============================
    // Hitung selisih hari
    // ==============================
    date_default_timezone_set("Asia/Jakarta");

    $tgl_pinjam = strtotime($data['tgl_pinjam']);
    $sekarang   = strtotime(date("Y-m-d H:i:s"));

    $selisih = floor(($sekarang - $tgl_pinjam) / (60 * 60 * 24));

    // ==============================
    // ===== DENDA =====
    // ==============================
    $batas_hari = 3;        // Batas pinjam tanpa denda
    $denda_per_hari = 2000; // Denda per hari

    if ($selisih > $batas_hari) {
        $terlambat   = $selisih - $batas_hari;
        $total_denda = $terlambat * $denda_per_hari;
    } else {
        $terlambat   = 0;
        $total_denda = 0;
    }

    // ==============================
    // CEK DENDA - Jika ada denda, arahkan ke pembayaran
    // ==============================
    if ($total_denda > 0) {
        $denda_text = number_format($total_denda, 0, ',', '.');
        $id_anggota_transaksi = null;

        // Get id_anggota dari transaksi
        $stmt_anggota = $koneksi->prepare("SELECT id_anggota FROM transaksi WHERE id_transaksi=?");
        if ($stmt_anggota) {
            $stmt_anggota->bind_param('i', $id);
            $stmt_anggota->execute();
            $result_anggota = $stmt_anggota->get_result();
            $data_anggota = $result_anggota->fetch_assoc();
            $stmt_anggota->close();
            $id_anggota_transaksi = $data_anggota['id_anggota'] ?? null;
        }

        // Insert pembayaran dengan status pending
        if ($id_anggota_transaksi) {
            $stmt_insert = $koneksi->prepare("
                INSERT INTO pembayaran 
                (id_transaksi, id_anggota, jumlah_denda, jumlah_dibayar, metode_pembayaran, tanggal_pembayaran, status_pembayaran)
                VALUES (?, ?, ?, 0, '', NOW(), 'pending')
            ");
            if ($stmt_insert) {
                $zero = 0;
                $stmt_insert->bind_param('iii', $id, $id_anggota_transaksi, $total_denda);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }

        echo '<script src="../js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'warning',title:'Ada Denda',text:'Anggota memiliki denda sebesar Rp $denda_text. Silahkan membayar denda terlebih dahulu sebelum pengembalian buku dapat diselesaikan.',confirmButtonText:'Ke Halaman Pembayaran'}).then(function(){ window.location.href='../anggota/pembayaran.php'; });</script>";
        exit;
    }

    // ==============================
    // Update transaksi
    // ==============================
    $stmt = $koneksi->prepare("
        UPDATE transaksi 
        SET status_transaksi='pengembalian',
            tgl_kembali = NOW(),
            terlambat = ?,
            denda = ?
        WHERE id_transaksi = ?
    ");

    if (!$stmt) {
        die("SQL ERROR (UPDATE transaksi): " . $koneksi->error);
    }

    $stmt->bind_param('iii', $terlambat, $total_denda, $id);
    $stmt->execute();
    $stmt->close();

    // ==============================
    // Kembalikan stok buku
    // ==============================
    $stmt = $koneksi->prepare("
        UPDATE buku 
        SET stok = stok + 1, 
            status = 'tersedia'
        WHERE id_buku = ?
    ");

    if (!$stmt) {
        die("SQL ERROR (UPDATE buku): " . $koneksi->error);
    }

    $stmt->bind_param('i', $buku);
    $stmt->execute();
    $stmt->close();

    // ==============================
    // Notifikasi (SweetAlert2)
    // ==============================
    $denda_text = number_format($total_denda, 0, ',', '.');
    echo '<script src="../js/sweetalert2.all.min.js"></script>';
    echo "<script>Swal.fire({icon:'success',title:'Pengembalian berhasil',text:'Denda: Rp $denda_text',confirmButtonText:'OK'}).then(function(){ window.location.href='dashboard.php?halaman=data_peminjaman'; });</script>";
} else {
    header("Location: dashboard.php?halaman=data_peminjaman");
}

exit;
