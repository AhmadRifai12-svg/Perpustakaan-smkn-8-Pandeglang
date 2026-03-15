<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Anggota - Perpustakaan SMKN 8 PANDEGLANG Digital</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="vh-100 row justify-content-center align-items-center">
        <form method="post" class="col-md-3 border p-4 bg-white rounded-4">

            <h4 class="text-center">Pendaftaran Anggota</h4>
            <h6 class="text-center mb-3">Aplikasi Perpustakaan SMKN 8 PANDEGLANG Digital</h6>

            <input type="text" name="nis" class="form-control mb-3" placeholder="Masukan NIS" required>
            <input type="text" name="nama_anggota" class="form-control mb-3" placeholder="Masukan Nama Anggota" required>
            <input type="text" name="username" class="form-control mb-3" placeholder="Masukan Username" required>
            <input type="password" name="password" class="form-control mb-3" placeholder="Masukan Password" required>
            <input type="text" name="kelas" class="form-control mb-3" placeholder="Masukan Kelas" required>

            <button name="tombol" type="submit" class="btn btn-success w-100 mb-2">
                Daftar
            </button>
            <p>sudah punya akun? <a href="login-anggota.php" class="text-decoration-none">Login</a></p>

        </form>
    </div>
</body>

</html>

<?php
if (isset($_POST['tombol'])) {
    session_start();
    include "koneksi.php";

    $nis          = $_POST['nis'];
    $nama_anggota = $_POST['nama_anggota'];
    $username     = $_POST['username'];
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $kelas        = $_POST['kelas'];

    $query = "
        INSERT INTO anggota 
        (nis, nama_anggota, username, password, kelas)
        VALUES 
        ('$nis', '$nama_anggota', '$username', '$password', '$kelas')
    ";

    $data = mysqli_query($koneksi, $query);

    if ($data) {
        $_SESSION['id_anggota']   = mysqli_insert_id($koneksi);
        $_SESSION['username']     = $username;
        $_SESSION['nama_anggota'] = $nama_anggota;

        header("Location: anggota/dashboard.php");
        exit;
    } else {
        echo '<script src="js/sweetalert2.all.min.js"></script>';
        echo "<script>Swal.fire({icon:'error',title:'Gagal',text:'❌ maaf login gagal'}).then(function(){ window.location.assign('login-admin.php'); });</script>";
    }
}
?>