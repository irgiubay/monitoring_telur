<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}
require('koneksi.php');

// Tambah kode manual
if (isset($_POST['buat_kode_manual'])) {
    $manual_id_user = strtoupper(trim($_POST['manual_id_user']));
    // Cek apakah sudah ada kode yang sama
    $cek = $conn->prepare("SELECT id FROM tb_kode_verifikasi WHERE id_user=?");
    $cek->bind_param("s", $manual_id_user);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        $pesan = "<span class='text-danger'>Kode ID User <b>$manual_id_user</b> sudah ada!</span>";
    } else {
        $stmt = $conn->prepare("INSERT INTO tb_kode_verifikasi (id_user, status) VALUES (?, 0)");
        $stmt->bind_param("s", $manual_id_user);
        $stmt->execute();
        $stmt->close();
        $pesan = "Kode ID User baru: <b>$manual_id_user</b>";
    }
    $cek->close();
}

// Hapus kode
if (isset($_GET['hapus'])) {
    $hapus_id = $_GET['hapus'];
    $conn->query("DELETE FROM tb_kode_verifikasi WHERE id_user='$hapus_id' AND status=0");
    $pesan = "Kode ID User <b>$hapus_id</b> berhasil dihapus (jika belum dipakai).";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Kode ID User</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f5f7fa; }
        .panel-card {
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            background: #fff;
            padding: 32px 28px 24px 28px;
            margin: 32px auto;
            max-width: 700px;
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
                    <a class="nav-link" href="kelola_user.php">Kelola User</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="kelola_kode.php">Kelola Kode</a>
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
        <h2 class="mb-4 text-center" style="color:#1976d2;">Kelola Kode ID User</h2>
        <?php if (isset($pesan)): ?>
            <div class="alert alert-info text-center"><?= $pesan ?></div>
        <?php endif; ?>

        <form method="post" class="mb-3 d-flex flex-wrap align-items-center gap-2">
            <input type="text" name="manual_id_user" class="form-control" style="max-width:200px;" placeholder="Kode ID User manual" required>
            <button type="submit" name="buat_kode_manual" class="btn btn-success">Tambah Kode Manual</button>
        </form>

        <div style="overflow-x:auto;">
        <table class="table table-bordered table-sm bg-white">
            <tr class="table-secondary">
                <th>No</th>
                <th>Kode ID User</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
            <?php
            $no = 1;
            $res = $conn->query("SELECT id_user, status FROM tb_kode_verifikasi ORDER BY id DESC");
            while ($row = $res->fetch_assoc()) {
                echo "<tr>
                    <td>{$no}</td>
                    <td>{$row['id_user']}</td>
                    <td>" . ($row['status'] ? '<span class="badge bg-secondary">Terpakai</span>' : '<span class="badge bg-success">Belum Dipakai</span>') . "</td>
                    <td>";
                if (!$row['status']) {
                    echo "<a href='kelola_kode.php?hapus={$row['id_user']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Hapus kode ini?')\">Hapus</a>";
                } else {
                    echo "-";
                }
                echo "</td>
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