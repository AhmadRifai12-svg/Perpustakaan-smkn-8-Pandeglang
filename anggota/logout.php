<?php
session_start();
session_unset();
session_destroy();
header("Location: ../login-anggota.php?loggedout=1");
exit();
