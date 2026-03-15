<?php
// Set header JSON dan error handling
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

require '../koneksi.php';

// Session check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id_anggota'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit;
}

$id_anggota = $_SESSION['id_anggota'];
$jumlah_pembayaran = (int)($_POST['jumlah_pembayaran'] ?? 0);
$metode_pembayaran = $_POST['metode_pembayaran'] ?? '';
$total_denda = (int)($_POST['total_denda'] ?? 0);

// Validasi metode pembayaran (hanya QRIS dan Tunai)
if (empty($metode_pembayaran) || !in_array($metode_pembayaran, ['qris', 'tunai'])) {
    echo json_encode(['success' => false, 'message' => 'Metode pembayaran harus dipilih (QRIS atau Tunai)']);
    exit;
}

// QRIS requires proof of payment
if ($metode_pembayaran === 'qris' && empty($_FILES['bukti_pembayaran']['name'])) {
    echo json_encode(['success' => false, 'message' => 'Bukti pembayaran QRIS wajib diunggah']);
    exit;
}

// Handle file upload
$bukti_pembayaran = null;
if (!empty($_FILES['bukti_pembayaran']['name'])) {
    $file = $_FILES['bukti_pembayaran'];
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];

    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Format file tidak didukung']);
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
        echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)']);
        exit;
    }

    $upload_dir = '../uploads/bukti_pembayaran/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = time() . '_' . basename($file['name']);
    $file_path = $upload_dir . $file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $bukti_pembayaran = $file_name;
    }
}

// Get transaksi dengan denda yang belum dibayar
$query = "
    SELECT t.* FROM transaksi t
    WHERE t.id_anggota = ? AND t.denda > 0
    ORDER BY t.tgl_pinjam ASC
";

$stmt = $koneksi->prepare($query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL ERROR: ' . $koneksi->error]);
    exit;
}
$stmt->bind_param('i', $id_anggota);
$stmt->execute();
$result = $stmt->get_result();
$transaksi_list = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Jika tidak ada transaksi dengan denda, jangan proses pembayaran
if (empty($transaksi_list)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada denda yang perlu dibayar']);
    exit;
}

// Proses pembayaran ke transaksi
$sisa_pembayaran = $jumlah_pembayaran;
date_default_timezone_set("Asia/Jakarta");

// Semua pembayaran status berhasil (user sudah membayar)
// QRIS = auto return + auto verify
// Tunai = tunggu admin verify baru auto return
$status_pembayaran = 'berhasil';

$auto_return = false;

try {
    // Mulai transaction
    $koneksi->begin_transaction();

    foreach ($transaksi_list as $transaksi) {
        if ($sisa_pembayaran <= 0) break;

        // Hitung sisa denda transaksi ini
        $check_stmt = $koneksi->prepare("SELECT SUM(jumlah_dibayar) as total_dibayar FROM pembayaran WHERE id_transaksi = ? AND status_pembayaran = 'berhasil'");
        $check_stmt->bind_param('i', $transaksi['id_transaksi']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_data = $check_result->fetch_assoc();
        $check_stmt->close();

        $total_dibayar = $check_data['total_dibayar'] ?? 0;
        $sisa_denda = $transaksi['denda'] - $total_dibayar;

        if ($sisa_denda <= 0) continue;

        // Tentukan jumlah yang dibayarkan untuk transaksi ini
        $bayar_sekarang = min($sisa_pembayaran, $sisa_denda);

        // Insert pembayaran
        $insert_stmt = $koneksi->prepare("
            INSERT INTO pembayaran 
            (id_transaksi, id_anggota, jumlah_denda, jumlah_dibayar, metode_pembayaran, tanggal_pembayaran, status_pembayaran, bukti_pembayaran)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$insert_stmt) {
            throw new Exception("SQL ERROR: " . $koneksi->error);
        }

        $tanggal_sekarang = date("Y-m-d H:i:s");
        $insert_stmt->bind_param(
            'iiiissss',
            $transaksi['id_transaksi'],
            $id_anggota,
            $sisa_denda,
            $bayar_sekarang,
            $metode_pembayaran,
            $tanggal_sekarang,
            $status_pembayaran,
            $bukti_pembayaran
        );

        if (!$insert_stmt->execute()) {
            throw new Exception("Gagal insert pembayaran: " . $insert_stmt->error);
        }
        $insert_stmt->close();

        $sisa_pembayaran -= $bayar_sekarang;

        // Jika QRIS dan transaksi masih berstatus peminjaman, proses auto return
        if ($metode_pembayaran === 'qris' && $transaksi['status_transaksi'] === 'peminjaman') {
            // Update status pembayaran ke berhasil untuk QRIS
            $update_qris_stmt = $koneksi->prepare("UPDATE pembayaran SET status_pembayaran = 'berhasil' WHERE id_pembayaran = ?");
            $update_qris_stmt->bind_param('i', $last_insert_id);
            $update_qris_stmt->execute();
            $update_qris_stmt->close();
            // Update status transaksi ke pengembalian
            $return_stmt = $koneksi->prepare("
                UPDATE transaksi 
                SET status_transaksi = 'pengembalian',
                    tgl_kembali = NOW(),
                    denda = 0
                WHERE id_transaksi = ?
            ");
            $return_stmt->bind_param('i', $transaksi['id_transaksi']);
            $return_stmt->execute();
            $return_stmt->close();

            // Update stok buku
            $stock_stmt = $koneksi->prepare("
                UPDATE buku 
                SET stok = stok + 1,
                    status = 'tersedia'
                WHERE id_buku = ?
            ");
            $stock_stmt->bind_param('i', $transaksi['id_buku']);
            $stock_stmt->execute();
            $stock_stmt->close();

            $auto_return = true;
        }
    }

    $koneksi->commit();

    // Response berdasarkan metode pembayaran
    if ($metode_pembayaran === 'qris') {
        echo json_encode([
            'success' => true,
            'auto_return' => $auto_return,
            'message' => 'Pembayaran QRIS berhasil! Buku akan dikembalikan secara otomatis.'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'auto_return' => false,
            'message' => 'Pembayaran berhasil! Admin akan memverifikasi bukti pembayaran Anda segera.'
        ]);
    }
} catch (Exception $e) {
    $koneksi->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$koneksi->close();
