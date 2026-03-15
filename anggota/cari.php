<?php
// Session check untuk keamanan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['id_anggota'])) {
    header("location:../login-anggota.php");
    exit;
}

include "../koneksi.php";

$kunci = isset($_POST['kunci']) ? mysqli_real_escape_string($koneksi, $_POST['kunci']) : "";

if (!empty($kunci)) {
    $query_cari = "SELECT * FROM buku WHERE judul_buku LIKE '%$kunci%' ORDER BY id_buku DESC";
    $data_cari = mysqli_query($koneksi, $query_cari);
?>

    <h4>🔍 Hasil Pencarian Buku: "<?= htmlspecialchars($kunci); ?>"</h4>
    <div class="row">
        <?php
        if (mysqli_num_rows($data_cari) > 0) {
            foreach ($data_cari as $buku) {
        ?>
                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm p-3 h-100">
                        <h5><?= $buku['judul_buku'] ?></h5>
                        <p class="small mb-1"><strong>Pengarang:</strong> <?= $buku['pengarang'] ?></p>
                        <p class="small mb-1"><strong>Penerbit:</strong> <?= $buku['penerbit'] ?></p>

                        <div class="mt-auto">
                            <?php if ($buku['status'] == "tersedia") { ?>
                                <span class="badge bg-success mb-2 d-block">✅ Tersedia</span>
                                <a onclick="pinjam('Yakin pinjam <?= addslashes($buku['judul_buku']) ?>?', <?= $buku['id_buku'] ?>)" class="btn btn-primary btn-sm w-100">🛒 Pinjam</a>
                            <?php } else { ?>
                                <span class="badge bg-danger mb-2 d-block">❌ Tidak Tersedia</span>
                                <button class="btn btn-secondary btn-sm w-100" disabled>Pinjam</button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php }
        } else { ?>
            <div class="col-12">
                <div class="alert alert-warning">Buku dengan judul "<?= htmlspecialchars($kunci); ?>" tidak ditemukan.</div>
            </div>
        <?php } ?>
    </div>

<?php
} else {
    echo "<script>window.location.href='dashboard.php';</script>";
}
?>