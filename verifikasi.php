<?php
require('koneksi.php');

if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = $_GET['email'];
    $token = $_GET['token'];

    // Periksa apakah email dan token verifikasi cocok
    $stmt = $conn->prepare("SELECT * FROM tb_akun WHERE email = ? AND verify_token = ?");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update status verifikasi
        $stmt = $conn->prepare("UPDATE tb_akun SET verify_status = 1, verify_token = NULL WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            echo "
            <script>
                alert('Email berhasil diverifikasi!');
                window.location.href = 'index.php';
            </script>";
        } else {
            echo "
            <script>
                alert('Terjadi kesalahan saat memverifikasi email.');
                window.location.href = 'index.php';
            </script>";
        }
    } else {
        echo "
        <script>
            alert('Link verifikasi tidak valid atau sudah kadaluarsa.');
            window.location.href = 'daftar.php';
        </script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "
    <script>
        alert('Permintaan tidak valid.');
        window.location.href = 'daftar.php';
    </script>";
}
?>