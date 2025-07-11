<?php
require('koneksi.php');

$pesan = '';
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Cek token valid dan belum expired
    $stmt = $conn->prepare("SELECT id, email FROM tb_akun WHERE reset_token = ? AND reset_expire > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Jika form submit
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password2'] ?? '';

            if ($password !== $password2) {
                $pesan = "Password dan konfirmasi tidak sama!";
            } elseif (strlen($password) < 6) {
                $pesan = "Password minimal 6 karakter!";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("UPDATE tb_akun SET password=?, reset_token=NULL, reset_expire=NULL WHERE id=?");
                $stmt2->bind_param("si", $hash, $user['id']);
                if ($stmt2->execute()) {
                    echo "
                    <script>
                        alert('Password berhasil direset! Silakan login.');
                        window.location.href = 'index.php';
                    </script>";
                    exit;
                } else {
                    $pesan = "Gagal menyimpan password baru.";
                }
                $stmt2->close();
            }
        }
    } else {
        echo "
        <script>
            alert('Link reset tidak valid atau sudah kadaluarsa.');
            window.location.href = 'index.php';
        </script>";
        exit;
    }
    $stmt->close();
    $conn->close();
} else {
    echo "
    <script>
        alert('Permintaan tidak valid.');
        window.location.href = 'index.php';
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body { background: #f5f7fa; }
        .reset-card {
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            background: #fff;
            padding: 32px 28px 24px 28px;
            margin: 48px auto 0 auto;
            max-width: 400px;
        }
        .brand-title {
            color: #1976d2;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }
        .btn-success {
            background: #1976d2;
            border: none;
        }
        .btn-success:hover {
            background: #125ea7;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">Monitoring Telur</span>
    </div>
</nav>
<div class="container">
    <div class="reset-card">
        <div class="text-center mb-3">
            <span class="brand-title">Reset Password</span>
            <div style="font-size:1.1em;color:#888;">Silakan masukkan password baru Anda</div>
        </div>
        <?php if ($pesan) echo "<div class='alert alert-danger text-center'>$pesan</div>"; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="password" class="form-control" required minlength="6" placeholder="Password baru">
            </div>
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="password2" class="form-control" required minlength="6" placeholder="Konfirmasi password">
            </div>
            <button class="btn btn-success w-100 mt-2">Reset Password</button>
        </form>
        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none" style="color:#1976d2;">Kembali ke Login</a>
        </div>
    </div>
</div>
</body>
</html>