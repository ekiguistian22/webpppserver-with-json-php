<?php
session_start();
// Konfigurasi login
$WEB_USER = "admin";
$WEB_PASS = "password";

// Cek POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    if ($user === $WEB_USER && $pass === $WEB_PASS) {
        $_SESSION['login']     = true;
        $_SESSION['username']  = $user;
        $_SESSION['last_activity'] = time();

        header("Location: secret_mikrotik");
        exit;
    } else {
        header("Location: index.php?error=auth");
        exit;
    }
} else {
    header("Location: ../");
    exit;
}
