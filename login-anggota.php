<?php
session_start();

// Jika sudah login, redirect ke dashboard
if (!empty($_SESSION['id_anggota'])) {
    header("location:anggota/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Anggota - Perpustakaan SMKN 8 PANDEGLANG Digital</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="vh-100 row justify-content-center align-items-center">
        <form method="post" action="" class="col-md-3 border p-4 bg-white rounded-4">
            <h4 class="text-center">Login Anggota</h4>
            <h5 class="text-center mb-3">Aplikasi Perpustakaan SMKN 8 PANDEGLANG Digital</h5>
            <input type="text" name="username" class="form-control mb-3" placeholder="Masukan Username" required>
            <input type="password" name="password" class="form-control mb-3" placeholder="Masukan Password" required>
            <button name="tombol" type="submit" class="btn btn-success w-100 mb-2">Login</button>
            <a href="login-admin.php" class="text-decoration-none">Login Sebagai admin</a><br>
            <a href="pendaftaran-anggota.php" class="text-decoration-none">Pendaftaran Anggota</a>
        </form>
    </div>
    <script src="js/sweetalert2.all.min.js"></script>
</body>

</html>

<?php
// Tampilkan notifikasi SweetAlert jika datang dari aksi logout
if (isset($_GET['loggedout']) && $_GET['loggedout'] == '1') {
    echo "<script>
        Swal.fire({
          icon: 'success',
          title: 'Berhasil logout',
          showConfirmButton: false,
          timer: 1300,
          toast: true,
          position: 'top-end'
        });
        </script>";
}
?>

<?php
if (isset($_POST['tombol'])) {
    include "koneksi.php";
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];
    $query = "SELECT * FROM anggota WHERE username='$username'";
    $data = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($data) > 0) {
        $row = mysqli_fetch_array($data);
        if (password_verify($password, $row['password']) || $password == $row['password']) {
            $_SESSION['id_anggota']   = $row['id_anggota'];
            $_SESSION['username']     = $row['username'];
            $_SESSION['nama_anggota'] = $row['nama_anggota'];

            header("location:anggota/dashboard.php");
            exit();
        } else {
            echo "<script>Swal.fire({icon:'error',title:'Gagal',text:'❌ Password salah!'}).then(function(){ window.location.assign('login-anggota.php'); });</script>";
        }
    } else {
        echo "<script>Swal.fire({icon:'error',title:'Gagal',text:'❌ Username tidak ditemukan!'}).then(function(){ window.location.assign('login-anggota.php'); });</script>";
    }
}
?>