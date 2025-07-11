<?php
require('koneksi.php');
require 'vendor/autoload.php'; // Pastikan PHPMailer sudah diinstal via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Cegah akses langsung ke file
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    header("Location: daftar.php");
    exit;
}

// Ambil data dari form
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$id_user = $_POST['id_user'] ?? '';

// Validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "
    <script>
        alert('Format email tidak valid!');
        window.location.href = 'daftar.php';
    </script>";
    exit;
}

// Validasi jika password dan konfirmasi password tidak cocok
if ($password !== $confirm_password) {
    echo "
    <script>
        alert('Password dan Konfirmasi Password tidak cocok!');
        window.location.href = 'daftar.php';
    </script>";
    exit;
}

// Validasi kode registrasi/id_user
$cek = $conn->prepare("SELECT * FROM tb_kode_verifikasi WHERE id_user=? AND status=0");
$cek->bind_param("s", $id_user);
$cek->execute();
$res = $cek->get_result();
if ($res->num_rows == 0) {
    echo "<script>alert('Kode registrasi tidak valid atau sudah dipakai!');window.location='daftar.php';</script>"; exit;
}

// Periksa apakah email sudah terdaftar
$stmt = $conn->prepare("SELECT * FROM tb_akun WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "
    <script>
        alert('Email sudah terdaftar!');
        window.location.href = 'daftar.php';
    </script>";
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$verify_token = bin2hex(random_bytes(16));
$verify_status = 0;
$role = 'user';

// Simpan user baru
$stmt = $conn->prepare("INSERT INTO tb_akun (email, password, verify_token, verify_status, id_user, role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssiss", $email, $hashed_password, $verify_token, $verify_status, $id_user, $role);


if ($stmt->execute()) {
    // Update status kode
    $conn->query("UPDATE tb_kode_verifikasi SET status=1 WHERE id_user='$id_user'");

    // Kirim email verifikasi
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
    echo "<script>alert('Pendaftaran berhasil! Silakan cek email Anda untuk verifikasi.');window.location.href = 'index.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Pendaftaran berhasil, tetapi email verifikasi gagal dikirim.');window.location.href = 'index.php';</script>";
    }
} else {
    echo "<script>alert('Pendaftaran Gagal! Terjadi kesalahan.');window.location.href = 'daftar.php';</script>";
}

$stmt->close();
$conn->close();
?>