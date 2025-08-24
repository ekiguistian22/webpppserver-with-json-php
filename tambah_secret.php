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

// Ambil pesan dari session (PRG)
$msg = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $comment  = trim($_POST['comment'] ?? "");

    // Validasi
    if (!preg_match('/^[A-Za-z0-9._@-]+$/', $username)) {
        $_SESSION['flash_msg'] = '<div class="alert alert-warning">⚠ Username hanya boleh huruf, angka, titik, garis bawah, strip, atau @.</div>';
        header("Location: tambah_secret");
        exit;
    }

    if ($username) {
        $API = new RouterosAPI();
        if ($API->connect($MT_HOST, $MT_USER, $MT_PASS)) {

            // Cek apakah secret sudah ada
            $existing = $API->comm("/ppp/secret/print", [
                "?name" => $username
            ]);

            if (!empty($existing)) {
                $_SESSION['flash_msg'] = '<div class="alert alert-warning">⚠ Secret PPPoE <strong>' . htmlspecialchars($username) . '</strong> sudah ada di MikroTik.</div>';
            } else {
                // Tambah secret baru (username = password)
                $API->comm("/ppp/secret/add", [
                    "name"     => $username,
                    "password" => $username,
                    "service"  => "pppoe",
                    "profile"  => $_POST['profile'],
                    "comment"  => $comment
                ]);
                $_SESSION['flash_msg'] = '<div class="alert alert-success">✅ PPPoE Secret <strong>' . htmlspecialchars($username) . '</strong> berhasil d';
            }

            $API->disconnect();
        } else {
            $_SESSION['flash_msg'] = '<div class="alert alert-danger">❌ Gagal konek ke MikroTik.</div>';
        }
    } else {
        $_SESSION['flash_msg'] = '<div class="alert alert-warning">⚠ Username wajib diisi!</div>';
    }

    // PRG Redirect
    header("Location: tambah_secret");
    exit;
}

// Ambil profile
$profiles = [];
$API = new RouterosAPI();
if ($API->connect($MT_HOST, $MT_USER, $MT_PASS)) {
    $profileList = $API->comm("/ppp/profile/print");
    foreach ($profileList as $p) {
        if (isset($p['name'])) {
            $profiles[] = $p['name'];
        }
    }
    $API->disconnect();
} else {
    $profiles[] = "Mikrotik Tidak Terhubung..!!!";
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tambah PPPoE Secret - By Eki Guistian</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card bg-secondary p-4 rounded-4 shadow">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Tambah PPPoE Pelanggan</h3>
                    <a href="secret_mikrotik" class="btn btn-info btn-sm">Daftar Pelanggan</a>
                    <a href="logout" class="btn btn-danger btn-sm">Logout</a>
                </div>
                <?= $msg ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Secret PPPoE Pelanggan Baru</label>
                        <input type="text" name="username" class="form-control" autocomplete="off" required placeholder="Contoh : 2012132432435@Siti_PGB">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Paket Pelanggan</label>
                        <select name="profile" class="form-select" required>
                            <?php foreach ($profiles as $pf): ?>
                                <option value="<?= htmlspecialchars($pf) ?>"><?= htmlspecialchars($pf) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Pelanggan</label>
                        <input type="text" name="comment" class="form-control" autocomplete="off" placeholder="Contoh : SITI NURHALIZA">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Kirim</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
