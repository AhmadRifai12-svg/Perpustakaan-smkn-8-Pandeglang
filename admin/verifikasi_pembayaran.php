<?php
// Set header JSON dan error handling
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../koneksi.php';

// Session check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['id_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit;
}

$id_admin = $_SESSION['id_admin'];

// Generate CSRF token if not exists (must be before checking)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Token keamanan tidak valid. Silakan refresh halaman dan coba lagi.']);
    exit;
}

// Get parameters dengan error handling
$id_pembayaran = isset($_POST['id_pembayaran']) ? (int)$_POST['id_pembayaran'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validasi parameter
if ($id_pembayaran <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID pembayaran tidak valid']);
    exit;
}

if (empty($status) || !in_array($status, ['berhasil', 'ditolak'])) {
    echo json_encode(['success' => false, 'message' => 'Status pembayaran tidak valid. Gunakan: berhasil atau ditolak']);
    exit;
}

// Get pembayaran data
$get_stmt = $koneksi->prepare("SELECT * FROM pembayaran WHERE id_pembayaran = ?");
if (!$get_stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL ERROR: ' . $koneksi->error]);
    exit;
}
$get_stmt->bind_param('i', $id_pembayaran);
$get_stmt->execute();
$get_result = $get_stmt->get_result();
$pembayaran = $get_result->fetch_assoc();
$get_stmt->close();

if (!$pembayaran) {
    echo json_encode(['success' => false, 'message' => 'Data pembayaran tidak ditemukan']);
    exit;
}

// Cek jika sudah diverifikasi sebelumnya (transaksi sudah pengembalian)
if ($pembayaran['status_pembayaran'] !== 'berhasil') {
    echo json_encode([
        'success' => false,
        'message' => 'Pembayaran sudah pernah diverifikasi dengan status: ' . htmlspecialchars($pembayaran['status_pembayaran'])
    ]);
    exit;
}

// Check apakah transaksi sudah dikembalikan
if (!isset($pembayaran['id_transaksi'])) {
    echo json_encode(['success' => false, 'message' => 'Data transaksi tidak ditemukan']);
    exit;
}

// Get transaksi data untuk informasi lengkap
$trans_stmt = $koneksi->prepare("
    SELECT t.*, b.judul_buku, a.nama_anggota 
    FROM transaksi t 
    JOIN buku b ON t.id_buku = b.id_buku 
    JOIN anggota a ON t.id_anggota = a.id_anggota 
    WHERE t.id_transaksi = ?
");
if (!$trans_stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL ERROR: ' . $koneksi->error]);
    exit;
}
$trans_stmt->bind_param('i', $pembayaran['id_transaksi']);
$trans_stmt->execute();
$trans_result = $trans_stmt->get_result();
$transaksi = $trans_result->fetch_assoc();
$trans_stmt->close();

if (!$transaksi) {
    echo json_encode(['success' => false, 'message' => 'Data transaksi tidak ditemukan']);
    exit;
}

// Jika pembayaran ditolak, langsung proses tanpa perlu cek denda
if ($status === 'ditolak') {
    try {
        $koneksi->begin_transaction();

        // Update status pembayaran ke ditolak
        $update_stmt = $koneksi->prepare("UPDATE pembayaran SET status_pembayaran = 'ditolak' WHERE id_pembayaran = ?");
        if (!$update_stmt) {
            throw new Exception("SQL ERROR: " . $koneksi->error);
        }
        $update_stmt->bind_param('i', $id_pembayaran);
        if (!$update_stmt->execute()) {
            throw new Exception("Gagal update pembayaran: " . $update_stmt->error);
        }
        $update_stmt->close();

        $koneksi->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Pembayaran telah ditolak.',
            'auto_return' => false,
            'data' => [
                'id_pembayaran' => $id_pembayaran,
                'id_transaksi' => $pembayaran['id_transaksi'],
                'anggota' => $transaksi['nama_anggota'],
                'buku' => $transaksi['judul_buku'],
                'jumlah_dibayar' => $pembayaran['jumlah_dibayar'],
                'status' => 'ditolak'
            ]
        ]);
    } catch (Exception $e) {
        $koneksi->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    $koneksi->close();
    exit;
}

// Proses verifikasi BERHASIL
try {
    $koneksi->begin_transaction();

    $auto_return = false; // Flag untuk tracking pengembalian otomatis
    $denda_cleared = false; // Flag untuk tracking清除denda

    // Update status pembayaran
    $update_stmt = $koneksi->prepare("UPDATE pembayaran SET status_pembayaran = ? WHERE id_pembayaran = ?");
    if (!$update_stmt) {
        throw new Exception("SQL ERROR: " . $koneksi->error);
    }
    $update_stmt->bind_param('si', $status, $id_pembayaran);
    if (!$update_stmt->execute()) {
        throw new Exception("Gagal update pembayaran: " . $update_stmt->error);
    }
    $update_stmt->close();

    // Hitung total pembayaran yang sudah berhasil untuk transaksi ini (termasuk pembayaran saat ini)
    $check_stmt = $koneksi->prepare("
        SELECT SUM(jumlah_dibayar) as total_dibayar 
        FROM pembayaran 
        WHERE id_transaksi = ? AND status_pembayaran = 'berhasil'
    ");
    if (!$check_stmt) {
        throw new Exception("SQL ERROR: " . $koneksi->error);
    }
    $check_stmt->bind_param('i', $pembayaran['id_transaksi']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_data = $check_result->fetch_assoc();
    $check_stmt->close();

    $total_dibayar = $check_data['total_dibayar'] ?? 0;
    $jumlah_denda = $transaksi['denda'] ?? 0;

    // Jika sudah lunas, proses pengembalian otomatis
    if ($total_dibayar >= $jumlah_denda && $jumlah_denda > 0) {
        // Hanya proses otomatis jika transaksi masih status 'peminjaman'
        if ($transaksi['status_transaksi'] === 'peminjaman') {
            date_default_timezone_set("Asia/Jakarta");

            // Update status transaksi ke 'pengembalian' dan set tgl_kembali (TIDAK SET DENDA = 0 agar tetap terlihat)
            $return_stmt = $koneksi->prepare("
                UPDATE transaksi 
                SET status_transaksi = 'pengembalian',
                    tgl_kembali = NOW()
                WHERE id_transaksi = ?
            ");
            if (!$return_stmt) {
                throw new Exception("SQL ERROR (return transaksi): " . $koneksi->error);
            }
            $return_stmt->bind_param('i', $pembayaran['id_transaksi']);
            if (!$return_stmt->execute()) {
                throw new Exception("Gagal update return transaksi: " . $return_stmt->error);
            }
            $return_stmt->close();

            // Kembalikan stok buku
            $stock_stmt = $koneksi->prepare("
                UPDATE buku 
                SET stok = stok + 1
                WHERE id_buku = ?
            ");
            if (!$stock_stmt) {
                throw new Exception("SQL ERROR (update stok): " . $koneksi->error);
            }
            $stock_stmt->bind_param('i', $transaksi['id_buku']);
            if (!$stock_stmt->execute()) {
                throw new Exception("Gagal update stok buku: " . $stock_stmt->error);
            }

            // Cek apakah stok sudah tersedia untuk diupdate status
            $cek_stok_stmt = $koneksi->prepare("SELECT stok FROM buku WHERE id_buku = ?");
            $cek_stok_stmt->bind_param('i', $transaksi['id_buku']);
            $cek_stok_stmt->execute();
            $cek_stok_result = $cek_stok_stmt->get_result();
            $cek_stok_data = $cek_stok_result->fetch_assoc();
            $cek_stok_stmt->close();

            if ($cek_stok_data && $cek_stok_data['stok'] > 0) {
                $status_stmt = $koneksi->prepare("UPDATE buku SET status = 'tersedia' WHERE id_buku = ?");
                $status_stmt->bind_param('i', $transaksi['id_buku']);
                $status_stmt->execute();
                $status_stmt->close();
            }
            $stock_stmt->close();

            $auto_return = true;
        } else {
            // Jika sudah pengembalian, hanya clear denda (TIDAK JADI DIGUNAKAN - denda tetap terlihat)
            $denda_cleared = true;
        }
    } elseif ($jumlah_denda > 0) {
        // Update sisa denda jika belum lunas
        $sisa_denda = max(0, $jumlah_denda - $total_dibayar);
        $update_denda_stmt = $koneksi->prepare("UPDATE transaksi SET denda = ? WHERE id_transaksi = ?");
        $update_denda_stmt->bind_param('ii', $sisa_denda, $pembayaran['id_transaksi']);
        if (!$update_denda_stmt->execute()) {
            throw new Exception("Gagal update sisa denda: " . $update_denda_stmt->error);
        }
        $update_denda_stmt->close();
    }

    $koneksi->commit();

    // Buat pesan success yang informatif
    if ($auto_return) {
        $message = 'Pembayaran diverifikasi! Buku "' . htmlspecialchars($transaksi['judul_buku']) . '" otomatis dikembalikan dan stok diperbarui.';
    } elseif ($denda_cleared) {
        $message = 'Pembayaran diverifikasi! Denda telah lunas.';
    } else {
        $message = 'Pembayaran berhasil diverifikasi.';
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'auto_return' => $auto_return,
        'data' => [
            'id_pembayaran' => $id_pembayaran,
            'id_transaksi' => $pembayaran['id_transaksi'],
            'anggota' => htmlspecialchars($transaksi['nama_anggota']),
            'buku' => htmlspecialchars($transaksi['judul_buku']),
            'jumlah_dibayar' => $pembayaran['jumlah_dibayar'],
            'status' => $status,
            'total_dibayar' => $total_dibayar,
            'sisa_denda' => $jumlah_denda - $total_dibayar
        ]
    ]);
} catch (Exception $e) {
    $koneksi->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$koneksi->close();
