<?php
session_start();
require('koneksi.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Ambil password, verify_status, role, id dari database (tanpa username)
    $stmt = $conn->prepare("SELECT id, password, verify_status, role FROM tb_akun WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashed_password = $row['password'];
        $verify_status = $row['verify_status'];
        $role = $row['role'];
        $user_id = $row['id'];

        if (password_verify($password, $hashed_password)) {
            if ($verify_status == 1) {
                // Set session sesuai role
                $_SESSION['email'] = $email;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = $role;

                if ($role == 'admin') {
                    header("Location: admin_panel.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;
            } else {
                echo "
                <script>
                    alert('Akun Anda belum diverifikasi! Silakan cek email Anda atau klik Kirim Ulang Verifikasi.');
                    window.location.href = 'index.php?email=$email';
                </script>";
                exit;
            }
        } else {
            echo "
            <script>
                alert('Password salah !');
                window.location.href = 'index.php';
            </script>";
        }
    } else {
       echo "
        <script>
            alert('Anda tidak memiliki akses !');
            window.location.href = 'index.php';
        </script>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: index.php");
    exit;
}
?>