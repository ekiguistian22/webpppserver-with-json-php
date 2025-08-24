<?php
session_start();

// Kalau sudah login, langsung ke tambah_secret.php
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: secret_mikrotik");
    exit;
}

$msg = "";
if (isset($_GET['error'])) {
    if ($_GET['error'] === "auth") {
        $msg = '<div class="alert alert-danger">Username atau password salah. &#9888; </div>';
    } elseif ($_GET['error'] === "timeout") {
        $msg = '<div class="alert alert-warning">Kamu belum login, Silakan login dulu ya. &#128522; </div>';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - PPP Server by Eki Guistian</title>
  <link rel="icon" type="image/png" href="/assets/img/favicon.png">
  <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card bg-secondary p-4 rounded-4 shadow-lg">
        <h3 class="mb-4 text-center">Login Server PPP</h3>
        <?= $msg ?>
        <form method="post" action="auth_check.php">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required autofocus placeholder="Masukan Username" autocomplete="off">
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="Masukan Password" autocomplete="off">
          </div>
          <button type="submit" class="btn btn-primary w-100">Sign in</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="text-center mt-4 mb-2 small">
  <p>Design By <b>Eki Guistian</b></p>
</footer>

</body>
</html>
