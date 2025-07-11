<?php

session_start();

require('koneksi.php');

if (!isset($_SESSION['email'])) {

    header("Location: index.php");

    exit;

}



$email = $_SESSION['email'];

$pesan = "";



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password_lama = $_POST['password_lama'] ?? '';

    $password_baru = $_POST['password_baru'] ?? '';

    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';



    // Ambil password lama dari database

    $stmt = $conn->prepare("SELECT password FROM tb_akun WHERE email = ?");

    $stmt->bind_param("s", $email);

    $stmt->execute();

    $stmt->bind_result($password_hash);

    $stmt->fetch();

    $stmt->close();



    if (!password_verify($password_lama, $password_hash)) {

        $pesan = "Password lama salah!";

    } elseif ($password_baru !== $konfirmasi_password) {

        $pesan = "Konfirmasi password baru tidak cocok!";

    } elseif (strlen($password_baru) < 6) {

        $pesan = "Password baru minimal 6 karakter!";

    } else {

        // Update password

        $password_baru_hash = password_hash($password_baru, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE tb_akun SET password = ? WHERE email = ?");

        $stmt->bind_param("ss", $password_baru_hash, $email);

        if ($stmt->execute()) {

            $pesan = "Password berhasil diganti!";

        } else {

            $pesan = "Gagal mengganti password!";

        }

        $stmt->close();

    }

}

?>

<!DOCTYPE html>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti Password</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light">

    <div class="container py-5">

        <div class="row justify-content-center">

            <div class="col-12 col-md-6">

                <div class="card shadow">

                    <div class="card-header bg-primary text-white text-center">

                        <h4>Ganti Password</h4>

                    </div>

                    <div class="card-body">

                        <?php if ($pesan): ?>

                            <div class="alert alert-info text-center"><?= htmlspecialchars($pesan) ?></div>

                        <?php endif; ?>

                        <form method="POST">

                            <div class="mb-3">

                                <label for="password_lama" class="form-label">Password Lama</label>

                                <input type="password" class="form-control" id="password_lama" name="password_lama" required>

                            </div>

                            <div class="mb-3">

                                <label for="password_baru" class="form-label">Password Baru</label>

                                <input type="password" class="form-control" id="password_baru" name="password_baru" required>

                            </div>

                            <div class="mb-3">

                                <label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru</label>

                                <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>

                            </div>

                            <button type="submit" class="btn btn-primary w-100">Ganti Password</button>

                        </form>

                        <a href="dashboard.php" class="btn btn-link w-100 mt-3">Kembali ke Dashboard</a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>