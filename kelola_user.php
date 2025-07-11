<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}
require('koneksi.php');

// Kirim link reset password ke email user (pakai SMTP PHPMailer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

if (isset($_POST['kirim_link_reset']) && isset($_POST['user_id']) && isset($_POST['user_email'])) {
    $user_id = intval($_POST['user_id']);
    $user_email = $_POST['user_email'];
    $token = bin2hex(random_bytes(32));
    $conn->query("UPDATE tb_akun SET reset_token='$token', reset_expire=DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id=$user_id");
    $resetLink = "https://www.monitor-telur.fwh.is/reset_password.php?token=$token";

    // Kirim email dengan PHPMailer SMTP
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'telurpemisah@gmail.com';
        $mail->Password = 'udpi pzkq esct guzv';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('telurpemisah@gmail.com', 'Admin Monitoring Telur');
        $mail->addAddress($user_email);
        $mail->isHTML(true);
        $mail->Subject = 'Reset Password Monitoring Telur';
        $mail->Body = "
            <h1>Reset Password</h1>
            <p>Klik link di bawah ini untuk mengatur ulang password akun Anda:</p>
            <a href='$resetLink'>Reset Password</a>
            <br><br>
            <small>Abaikan email ini jika Anda tidak meminta reset password.</small>
        ";

        $mail->send();
        $pesan = "Link reset password sudah dikirim ke email user.";
    } catch (Exception $e) {
        $pesan = "Gagal mengirim email. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Hapus user
if (isset($_POST['hapus_user'])) {
    $user_id = intval($_POST['user_id']);
    $stmt = $conn->prepare("DELETE FROM tb_akun WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    $pesan = "Akun user berhasil dihapus.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola User</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <style>
        body { background: #f5f7fa; }
        .panel-card {
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            background: #fff;
            padding: 32px 28px 24px 28px;
            margin: 32px auto;
            max-width: 900px;
        }
        .table-sm th, .table-sm td { font-size: 0.98em; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">Monitoring Telur</span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="admin_panel.php">Admin Panel</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="kelola_user.php">Kelola User</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="kelola_kode.php">Kelola Kode</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="data_produksi.php">Data Produksi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user_activity.php">User Activity</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <span class="navbar-text text-white">
                        Selamat Datang, <?php echo htmlspecialchars($_SESSION['email']); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a href="ganti_pass.php" class="btn btn-warning btn-sm ms-3">Ganti Password</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="btn btn-danger btn-sm ms-3" data-bs-toggle="modal" data-bs-target="#modalKeluar">Keluar</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
    <div class="panel-card">
        <h2 class="mb-4 text-center" style="color:#1976d2;">Kelola User</h2>
        <?php if (isset($pesan)): ?>
            <div class="alert alert-success text-center"><?= $pesan ?></div>
        <?php endif; ?>

        <h5 class="mt-4 mb-2">Daftar User</h5>
        <div style="overflow-x:auto;">
        <table class="table table-bordered table-sm bg-white">
            <tr class="table-secondary">
                <th>No</th>
                <th>Email</th>
                <th>ID User</th>
                <th>Status Verifikasi</th>
                <th>Role</th>
                <th>Aksi</th>
            </tr>
            <?php
            $no = 1;
            $res = $conn->query("SELECT id, email, id_user, verify_status, role FROM tb_akun ORDER BY role DESC, email ASC");
            while ($row = $res->fetch_assoc()) {
                echo "<tr>
                    <td>{$no}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['id_user']}</td>
                    <td>" . ($row['verify_status'] ? '<span class=\"badge bg-success\">Aktif</span>' : '<span class=\"badge bg-warning text-dark\">Belum</span>') . "</td>
                    <td>{$row['role']}</td>
                    <td>
                        <!-- Tombol kirim link reset password ke email user -->
                        <form method='post' style='display:inline;'>
                            <input type='hidden' name='user_id' value='{$row['id']}'>
                            <input type='hidden' name='user_email' value='{$row['email']}'>
                            <button type='submit' name='kirim_link_reset' class='btn btn-sm btn-info'>Kirim Link Reset</button>
                        </form>
                        <form method='post' style='display:inline;' onsubmit=\"return confirm('Hapus user ini?')\">
                            <input type='hidden' name='user_id' value='{$row['id']}'>
                            <button type='submit' name='hapus_user' class='btn btn-sm btn-danger'>Hapus</button>
                        </form>
                    </td>
                </tr>";
                $no++;
            }
            ?>
        </table>
        </div>
    </div>
</div>
<!-- Modal Konfirmasi Keluar -->
<div class="modal fade" id="modalKeluar" tabindex="-1" aria-labelledby="modalKeluarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalKeluarLabel">Konfirmasi Keluar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin keluar?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="sistem_keluar.php" class="btn btn-danger">Keluar</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>