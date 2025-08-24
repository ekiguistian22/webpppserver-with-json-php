<?php
session_start();

// Cek login
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: index.php?error=timeout");
    exit;
}

require_once 'routeros_api.class.php';
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: /");
    exit;
}

// KONFIGURASI MikroTik
$MT_HOST = "ip-public-mikrotik/vpn";
$MT_USER = "user-mikrotik";
$MT_PASS = "password-mikrotik";

$API = new RouterosAPI();
$totalActive = 0;

if ($API->connect($MT_HOST, $MT_USER, $MT_PASS)) {
    $activeUsers = $API->comm("/ppp/active/print");
    $totalActive = count($activeUsers);
    $API->disconnect();
}

echo $totalActive;
