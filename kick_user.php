<?php
session_start();
// Cek login
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: index.php?error=timeout");
    exit;
}
require_once 'routeros_api.class.php';
if (!isset($_POST['user'])) {
    exit("ERR");
}

$username = $_POST['user'];

// KONFIGURASI MikroTik
$MT_HOST = "ip-public-mikrotik/vpn";
$MT_USER = "user-mikrotik";
$MT_PASS = "password-mikrotik";

$API = new RouterosAPI();

if ($API->connect($MT_HOST, $MT_USER, $MT_PASS)) {
    $activeUsers = $API->comm("/ppp/active/print", ["?name" => $username]);
    if (!empty($activeUsers)) {
        $id = $activeUsers[0][".id"];
        $API->comm("/ppp/active/remove", [".id" => $id]);
        echo "OK";
    } else {
        echo "ERR";
    }
    $API->disconnect();
} else {
    echo "ERR";
}
