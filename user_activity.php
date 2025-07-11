<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit;
}
require('koneksi.php');

// Cek kolom penghubung
$colCheck = $conn->query("SHOW COLUMNS FROM tb_produksi LIKE 'id_user'");
if ($colCheck->num_rows > 0) {
    // Join via id_user jika ada
    $query = "
        SELECT a.id_user, 
               SUM(p.jumlah_produksi) AS jumlah_produksi,
               SUM(p.jumlah_cacat) AS jumlah_cacat,
               SUM(p.total_produksi) AS total_produksi
        FROM tb_akun a
        JOIN tb_produksi p ON a.id_user = p.id_user
        GROUP BY a.id_user
        ORDER BY jumlah_produksi DESC
        LIMIT 10
    ";
} else {
    // Join via email jika id_user tidak ada
    $query = "
        SELECT a.id_user, 
               SUM(p.jumlah_produksi) AS jumlah_produksi,
               SUM(p.jumlah_cacat) AS jumlah_cacat,
               SUM(p.total_produksi) AS total_produksi
        FROM tb_akun a
        JOIN tb_produksi p ON a.email = p.email
        GROUP BY a.id_user
        ORDER BY jumlah_produksi DESC
        LIMIT 10
    ";
}
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Activity</title>
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
            max-width: 800px;
        }
        .table-sm th, .table-sm td { font-size: 0.98em; }
        .btn-custom {
            background: linear-gradient(90deg,#1976d2 60%,#36a2eb 100%);
            color: #fff;
            border: none;
        }
        .btn-custom:hover {
            background: #1976d2;
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold">Monitoring Telur</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'admin_panel.php') echo ' active'; ?>" href="admin_panel.php">Admin Panel</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="kelola_user.php">Kelola User</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="kelola_kode.php">Kelola Kode</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo ' active'; ?>" href="dashboard.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'data_produksi.php') echo ' active'; ?>" href="data_produksi.php">Data Produksi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'user_activity.php') echo ' active'; ?>" href="user_activity.php">User Activity</a>
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
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Konten -->
    <div class="container">
        <div class="panel-card mt-5">
            <h2 class="mb-4 text-center" style="color:#1976d2;">User Activity</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-sm bg-white">
                    <thead class="table-secondary">
                        <tr>
                            <th>No</th>
                            <th>ID User</th>
                            <th>Jumlah Produksi</th>
                            <th>Jumlah Cacat</th>
                            <th>Total Produksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>{$no}</td>
                                <td>{$row['id_user']}</td>
                                <td>{$row['jumlah_produksi']}</td>
                                <td>{$row['jumlah_cacat']}</td>
                                <td>{$row['total_produksi']}</td>
                            </tr>";
                            $no++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>