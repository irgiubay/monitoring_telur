<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if (!isset($_SESSION['email'])) {
    echo "
    <script>
        alert('Silakan login terlebih dahulu!');
        window.location.href = 'index.php';
    </script>";
    exit;
}
require('koneksi.php'); // Koneksi ke database

// Query untuk mendapatkan user terproduktif (total produksi terbanyak) - hanya id_user
$queryUserProduktif = "SELECT a.id_user, SUM(p.total_produksi) AS total_produksi
                       FROM tb_akun a
                       JOIN tb_produksi p ON a.id_user = p.id_user
                       GROUP BY a.id_user
                       ORDER BY total_produksi DESC
                       LIMIT 1";
$resultUserProduktif = $conn->query($queryUserProduktif);
$userProduktif = ($resultUserProduktif && $resultUserProduktif->num_rows > 0) ? $resultUserProduktif->fetch_assoc() : null;

// Query untuk mendapatkan data total produksi terbanyak
$queryMaxProduksi = "SELECT tanggal, total_produksi FROM tb_produksi ORDER BY total_produksi DESC LIMIT 1";
$resultMaxProduksi = $conn->query($queryMaxProduksi);
$maxProduksi = ($resultMaxProduksi && $resultMaxProduksi->num_rows > 0) ? $resultMaxProduksi->fetch_assoc() : null;

// Query untuk mendapatkan data telur cacat terbanyak
$queryMaxCacat = "SELECT tanggal, jumlah_cacat FROM tb_produksi ORDER BY jumlah_cacat DESC LIMIT 1";
$resultMaxCacat = $conn->query($queryMaxCacat);
$maxCacat = ($resultMaxCacat && $resultMaxCacat->num_rows > 0) ? $resultMaxCacat->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    <!-- Header -->
    <div class="container-fluid bg-light py-5 shadow-sm">
        <div class="text-center">
            <h2 class="fw-bold text-primary">Dashboard Monitoring Telur</h2>
            <p class="lead">
                Halaman ini merupakan halaman utama untuk data pemantauan produksi telur.
            </p>
        </div>
    </div>

    <!-- Statistik -->
    <div class="container mt-5">
        <h3 class="text-center mb-4">Statistik Produksi Telur</h3>
        <div class="row text-center">
            <div class="col-12 col-md-4 mb-4">
                <div class="card border-info shadow">
                    <div class="card-body">
                        <h5 class="card-title text-info">User Terproduktif</h5>
                        <?php if ($userProduktif): ?>
                        <p class="card-text">
                            <strong>ID User:</strong> <?php echo htmlspecialchars($userProduktif['id_user']); ?><br>
                            <strong>Total Produksi:</strong> <?php echo $userProduktif['total_produksi']; ?>
                        </p>
                        <?php else: ?>
                        <p class="card-text">Belum ada data.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-4">
                <div class="card border-success shadow">
                    <div class="card-body">
                        <h5 class="card-title text-success">Total Produksi Terbanyak</h5>
                        <?php if ($maxProduksi): ?>
                        <p class="card-text">
                            <strong>Tanggal:</strong> <?php echo $maxProduksi['tanggal']; ?><br>
                            <strong>Total Produksi:</strong> <?php echo $maxProduksi['total_produksi']; ?>
                        </p>
                        <?php else: ?>
                        <p class="card-text">Belum ada data.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 mb-4">
                <div class="card border-danger shadow">
                    <div class="card-body">
                        <h5 class="card-title text-danger">Telur Cacat Terbanyak</h5>
                        <?php if ($maxCacat): ?>
                        <p class="card-text">
                            <strong>Tanggal:</strong> <?php echo $maxCacat['tanggal']; ?><br>
                            <strong>Jumlah Cacat:</strong> <?php echo $maxCacat['jumlah_cacat']; ?>
                        </p>
                        <?php else: ?>
                        <p class="card-text">Belum ada data.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik -->
    <div class="container mt-5 mb-5">
        <h3 class="text-center mb-4">Grafik Produksi Telur</h3>
        <div class="card shadow">
            <div class="card-body">
                <div style="width:100%;max-width:1000px;margin:auto;">
                    <canvas id="chartProduksi"></canvas>
                </div>
            </div>
        </div>
    </div>

<?php
// Query untuk mendapatkan total produksi per bulan
$queryGrafik = "SELECT DATE_FORMAT(tanggal, '%M %Y') AS bulan, SUM(total_produksi) AS total_bulanan 
                FROM tb_produksi 
                GROUP BY bulan 
                ORDER BY tanggal ASC";
$resultGrafik = $conn->query($queryGrafik);

$labels = [];
$dataProduksi = [];

if ($resultGrafik && $resultGrafik->num_rows > 0) {
    while ($row = $resultGrafik->fetch_assoc()) {
        $labels[] = $row['bulan']; // Format: Nama Bulan Tahun (contoh: Januari 2025)
        $dataProduksi[] = $row['total_bulanan']; // Total produksi per bulan
    }
}
?>

<script>
    // Ambil data dari PHP untuk grafik
    const labels = <?php echo json_encode($labels); ?>; // Label bulan (contoh: Januari 2025)
    const dataProduksi = <?php echo json_encode($dataProduksi); ?>; // Total produksi per bulan

    // Generate warna berbeda untuk setiap data
    const generateColors = (size) => {
        const colors = [];
        for (let i = 0; i < size; i++) {
            colors.push(`hsl(${Math.floor(Math.random() * 360)}, 70%, 70%)`);
        }
        return colors;
    };

    const barColors = generateColors(dataProduksi.length);

    const ctx = document.getElementById('chartProduksi').getContext('2d');
    const chartProduksi = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Produksi Per Bulan',
                data: dataProduksi,
                backgroundColor: barColors,
                borderColor: barColors,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        generateLabels: function (chart) {
                            return chart.data.labels.map((label, index) => ({
                                text: label,
                                fillStyle: chart.data.datasets[0].backgroundColor[index],
                                strokeStyle: chart.data.datasets[0].borderColor[index],
                                lineWidth: 1
                            }));
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Bulan'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Total Produksi'
                    },
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>