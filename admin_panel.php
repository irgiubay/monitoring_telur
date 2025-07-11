<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}
require('koneksi.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f5f5f5 60%, #c9e7fa 100%);
            min-height: 100vh;
        }
        .panel-card {
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            background: #fff;
            padding: 32px 28px 24px 28px;
            margin-bottom: 32px;
        }
        .stat-box {
            border-radius: 12px;
            padding: 18px 0;
            text-align: center;
            font-size: 1.2em;
            font-weight: 600;
        }
        .stat-user { background: #e3f2fd; color: #1976d2; }
        .stat-prod { background: #e8f5e9; color: #388e3c; }
        .stat-cacat { background: #ffebee; color: #d32f2f; }
        .table-sm th, .table-sm td { font-size: 0.98em; }
        .btn-group-custom .btn { margin-right: 8px; }
        .btn-group-custom .btn:last-child { margin-right: 0; }
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
                    <a class="nav-link active" href="admin_panel.php">Admin Panel</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="kelola_user.php">Kelola User</a>
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
<div class="container py-4">
    <div class="panel-card">
        <h2 class="mb-4 text-center" style="color:#1976d2;">Admin Panel</h2>
        
        <?php
        $totalUser = $conn->query("SELECT COUNT(*) FROM tb_akun")->fetch_row()[0];
        $totalProduksi = $conn->query("SELECT SUM(total_produksi) FROM tb_produksi")->fetch_row()[0];
        // Jika tidak ada kolom jumlah_cacat, hapus baris berikut
        $totalCacat = 0;
        $cacatQ = $conn->query("SHOW COLUMNS FROM tb_produksi LIKE 'jumlah_cacat'");
        if ($cacatQ->num_rows > 0) {
            $totalCacat = $conn->query("SELECT SUM(jumlah_cacat) FROM tb_produksi")->fetch_row()[0];
        }
        ?>
        <div class="row mb-4">
            <div class="col-md-4 mb-2"><div class="stat-box stat-user">Total User<br><span style="font-size:2em;"><?= $totalUser ?></span></div></div>
            <div class="col-md-4 mb-2"><div class="stat-box stat-prod">Total Produksi<br><span style="font-size:2em;"><?= $totalProduksi ?></span></div></div>
            <div class="col-md-4 mb-2"><div class="stat-box stat-cacat">Total Cacat<br><span style="font-size:2em;"><?= $totalCacat ?></span></div></div>
        </div>
        <h4 class="mb-3" style="color:#1976d2;">Log Data Produksi Harian</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped bg-white">
                <thead class="table-secondary">
                    <tr>
                        <th>Tanggal</th>
                        <th>Jumlah Produksi</th>
                        <?php if ($cacatQ->num_rows > 0): ?><th>Jumlah Cacat</th><?php endif; ?>
                        <th>Total Produksi</th>
                        <th>Status Kirim</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT tanggal, jumlah_produksi"
                        . ($cacatQ->num_rows > 0 ? ", jumlah_cacat" : "")
                        . ", total_produksi, status_kirim FROM tb_produksi ORDER BY tanggal DESC";
                    $res = $conn->query($query);
                    while ($row = $res->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['tanggal']}</td>
                            <td>{$row['jumlah_produksi']}</td>";
                        if ($cacatQ->num_rows > 0) echo "<td>{$row['jumlah_cacat']}</td>";
                        echo "<td>{$row['total_produksi']}</td>";
                        echo "<td>" . ($row['status_kirim'] ? '<span class=\"badge bg-success\">Terkirim</span>' : '<span class=\"badge bg-warning text-dark\">Belum</span>') . "</td>
                        </tr>";
                    }
                    ?>
                </tbody>
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