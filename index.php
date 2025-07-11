<?php
require('koneksi.php');
session_start();
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin_panel.php");
        exit;
    } else if ($_SESSION['role'] == 'user') {
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Akun</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <style type="text/css">
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f5f5 60%, #c9e7fa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-login {
            max-width: 400px;
            margin: auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 32px 28px 24px 28px;
        }
        .form-login .logo {
            display: block;
            margin: 0 auto 18px auto;
            width: 64px;
            height: 64px;
        }
        .form-login h3 {
            margin-bottom: 24px;
            font-weight: 600;
            color: #007bff;
        }
        .form-login .btn-primary {
            background: #007bff;
            border: none;
        }
        .form-login .btn-primary:hover {
            background: #0056b3;
        }
        .form-login .text-muted {
            font-size: 0.95em;
        }
        .alert-warning {
            font-size: 0.95em;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-5">
        <form class="form-login" method="POST" action="sistem_masuk.php">
            <!-- Logo (opsional, ganti src sesuai logo Anda) -->
            <!-- <img src="logo.png" alt="Logo" class="logo"> -->
            <h3 class="text-center">Masuk Akun</h3>
            <div class="form-floating mb-3">
                <input type="email" name="email" class="form-control" id="email" placeholder="E-mail" required>
                <label for="email">E-mail</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" name="password" class="form-control" id="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="bi bi-box-arrow-in-right"></i> Masuk
            </button>
            <p class="mb-3 text-center">Belum punya akses? <a href="daftar.php">Daftar</a></p>
            <p class="text-muted text-center mb-0">&copy; <?= date('Y') ?></p>
        </form>
        <?php if (isset($_GET['email'])): ?>
            <div class="alert alert-warning mt-3 text-center">
                Belum menerima email verifikasi? <a href="kirim_ulang_verifikasi.php?email=<?= htmlspecialchars($_GET['email']) ?>">Kirim Ulang Verifikasi</a>
            </div>
        <?php endif; ?>
    </div>
    <!-- Optional: Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</body>
</html>