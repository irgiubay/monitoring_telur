<?php
require('koneksi.php');
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$email = $_GET['email'] ?? '';

if (!$email) {
    echo "
    <script>
        alert('Email tidak ditemukan!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

// Ambil token lama atau buat baru
$stmt = $conn->prepare("SELECT verify_token, verify_status FROM tb_akun WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['verify_status'] == 1) {
        echo "
        <script>
            alert('Akun sudah diverifikasi!');
            window.location.href = 'index.php';
        </script>";
        exit;
    }
    $verify_token = $row['verify_token'];
    if (!$verify_token) {
        $verify_token = bin2hex(random_bytes(16));
        $update = $conn->prepare("UPDATE tb_akun SET verify_token = ? WHERE email = ?");
        $update->bind_param("ss", $verify_token, $email);
        $update->execute();
        $update->close();
    }

    // Kirim ulang email verifikasi
    $mail = new PHPMailer(true);
    try {
        // Konfigurasi SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'telurpemisah@gmail.com'; // Ganti dengan email pengirim
    $mail->Password = 'udpi pzkq esct guzv';    // Ganti dengan app password Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Pengaturan email
    $mail->setFrom('telurpemisah@gmail.com', 'Admin Monitoring Telur');
    $mail->addAddress($email); // Email tujuan
    $mail->addReplyTo('no-reply@example.com', 'No Reply');
    $mail->isHTML(true);
    $mail->Subject = 'Verifikasi Email Anda';
    $mail->Body = "
        <h1>Verifikasi Email Anda</h1>
        <p>Terima kasih telah mendaftar. Klik link di bawah ini untuk memverifikasi email Anda:</p>
        <a href='http://monitor-telur.fwh.is/verifikasi.php?email=$email&token=$verify_token'>Verifikasi Email</a>
        <br><br>
        <small>Abaikan email ini jika Anda tidak merasa mendaftar.</small>
    ";

        $mail->send();
        echo "
        <script>
            alert('Email verifikasi telah dikirim ulang. Silakan cek email Anda.');
            window.location.href = 'index.php';
        </script>";
    } catch (Exception $e) {
        echo "
        <script>
            alert('Gagal mengirim email verifikasi: {$mail->ErrorInfo}');
            window.location.href = 'index.php';
        </script>";
    }
} else {
    echo "
    <script>
        alert('Email tidak ditemukan!');
        window.location.href = 'index.php';
    </script>";
}

$stmt->close();
$conn->close();
?>