<?php
session_start();

// Cek login
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: index.php?error=timeout");
    exit;
}

require_once 'routeros_api.class.php';
// KONFIGURASI MikroTik
$MT_HOST = "ip-public-mikrotik/vpn";
$MT_USER = "user-mikrotik";
$MT_PASS = "password-mikrotik";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $profile  = trim($_POST['profile']);
    $comment  = trim($_POST['comment']);

    if (!preg_match('/^[A-Za-z0-9._@-]+$/', $username)) {
        $_SESSION['flash'] = '<div class="alert alert-danger">Username tidak valid.</div>';
        header("Location: tambah_secret");
        exit;
    }

    $API = new RouterosAPI();
    if ($API->connect($MT_HOST, $MT_USER, $MT_PASS)) {
        $API->comm("/ppp/secret/add", [
            "name"     => $username,
            "password" => $username, // username = password
            "profile"  => $profile,
            "comment"  => $comment
        ]);
        $_SESSION['flash'] = '<div class="alert alert-success">Secret berhasil ditambahkan.</div>';
        $API->disconnect();
    } else {
        $_SESSION['flash'] = '<div class="alert alert-danger">Gagal koneksi ke MikroTik.</div>';
    }
    header("Location: tambah_secret");
    exit;
}
header("Location: tambah_secret");
exit;
