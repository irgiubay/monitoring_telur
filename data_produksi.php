<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
require('koneksi.php');

// === Import otomatis hanya untuk user biasa ===
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    // Debug: log setiap kali import otomatis dijalankan
    file_put_contents('debug_import.txt', date('Y-m-d H:i:s')." Import otomatis dijalankan\n", FILE_APPEND);
    include_once 'unggah_excel.php';
}
// Ambil filter bulan dan tahun dari URL
$filterTahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : null;
$filterBulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : null;

// Query untuk mendapatkan daftar tahun dan bulan unik
$queryTahunBulan = "SELECT DISTINCT YEAR(tanggal) AS tahun, MONTH(tanggal) AS bulan FROM tb_produksi ORDER BY tahun ASC, bulan ASC";
$resultTahunBulan = $conn->query($queryTahunBulan);

// Query untuk mendapatkan data total berdasarkan filter bulan dan tahun
if ($filterTahun && $filterBulan) {
    $queryTotal = "SELECT 
                    SUM(jumlah_produksi) AS total_jumlah_produksi, 
                    SUM(jumlah_cacat) AS total_telur_cacat, 
                    SUM(total_produksi) AS total_total_produksi 
                   FROM tb_produksi 
                   WHERE YEAR(tanggal) = $filterTahun AND MONTH(tanggal) = $filterBulan";
} else {
    $queryTotal = "SELECT 
                    SUM(jumlah_produksi) AS total_jumlah_produksi, 
                    SUM(jumlah_cacat) AS total_telur_cacat, 
                    SUM(total_produksi) AS total_total_produksi 
                   FROM tb_produksi";
}
$resultTotal = $conn->query($queryTotal);

$dataTotal = $resultTotal->fetch_assoc() ?: [
    'total_jumlah_produksi' => 0,
    'total_telur_cacat' => 0,
    'total_total_produksi' => 0
];
// Query untuk mendapatkan data berdasarkan filter bulan dan tahun
if ($filterTahun && $filterBulan) {
    $queryPerBulan = "SELECT 
                        DAY(tanggal) AS hari, 
                        SUM(jumlah_produksi) AS jumlah_produksi, 
                        SUM(jumlah_cacat) AS telur_cacat, 
                        SUM(total_produksi) AS total_produksi 
                      FROM tb_produksi 
                      WHERE YEAR(tanggal) = $filterTahun AND MONTH(tanggal) = $filterBulan
                      GROUP BY DAY(tanggal)";
} else {
    // Jika tidak ada filter, ambil data total per bulan
    $queryPerBulan = "SELECT 
                        MONTH(tanggal) AS bulan, 
                        SUM(jumlah_produksi) AS jumlah_produksi, 
                        SUM(jumlah_cacat) AS telur_cacat, 
                        SUM(total_produksi) AS total_produksi 
                      FROM tb_produksi 
                      GROUP BY MONTH(tanggal)";
}
$resultPerBulan = $conn->query($queryPerBulan);

// Inisialisasi data untuk chart
$dataLabel = [];
$dataJumlahProduksi = [];
$dataTelurCacat = [];
$dataTotalProduksi = [];

// Simpan data baris untuk tabel
$dataTabel = [];

while ($row = $resultPerBulan->fetch_assoc()) {
    if ($filterTahun && $filterBulan) {
        $dataLabel[] = htmlspecialchars($row['hari'], ENT_QUOTES, 'UTF-8'); // Hari
    } else {
        $dataLabel[] = htmlspecialchars(date("F", mktime(0, 0, 0, $row['bulan'], 10)), ENT_QUOTES, 'UTF-8'); // Nama bulan
    }
    $dataJumlahProduksi[] = (int)$row['jumlah_produksi'];
    $dataTelurCacat[] = (int)$row['telur_cacat'];
    $dataTotalProduksi[] = (int)$row['total_produksi'];
    $dataTabel[] = $row;
}

// Jika data kosong, tambahkan placeholder
if (empty($dataLabel)) {
    $dataLabel[] = 'No Data';
    $dataJumlahProduksi[] = 0;
    $dataTelurCacat[] = 0;
    $dataTotalProduksi[] = 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Produksi</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <style>
    .dropdown-menu {
        max-height: 300px;
        overflow-y: auto;
    }
    .chart-container {
        position: relative;
        margin: auto;
        height: 300px;
        width: 100%;
        max-width: 500px;
    }
    @media (max-width: 767.98px) {
        .chart-container {
            height: 250px;
            max-width: 100%;
        }
        .container, .container-fluid {
            padding-left: 8px;
            padding-right: 8px;
        }
    }
    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }
    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #004085;
    }
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

    <div class="container mt-3">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <!-- Tombol Import hanya untuk admin -->
            <button id="importSheet" class="btn btn-primary mb-3">Import Data dari Google Sheet</button>
            <div id="importResult"></div>
        <?php endif; ?>
        <!-- Tombol Export (kirim ke Google Sheet) untuk SEMUA ROLE -->
        <form action="kirimdata.php" method="post" style="display:inline;">
            <button type="submit" class="btn btn-success mb-3" onclick="return confirm('Kirim data ke Google Sheet?')">
                Kirim Data ke Sheet
            </button>
        </form>
    </div>

    <div class="container mt-5">
        <div class="row">
            <!-- Sidebar Kiri -->
            <div class="col-md-3">
                <h5 class="text-center mb-3">Filter Bulan dan Tahun</h5>
                <div class="list-group">
                    <a href="data_produksi.php" class="list-group-item list-group-item-action <?php echo !$filterTahun && !$filterBulan ? 'active' : ''; ?>">
                        Semua Data
                    </a>
                    <?php while ($row = $resultTahunBulan->fetch_assoc()): ?>
                        <a href="data_produksi.php?tahun=<?php echo $row['tahun']; ?>&bulan=<?php echo $row['bulan']; ?>" 
                        class="list-group-item list-group-item-action <?php echo ($filterTahun == $row['tahun'] && $filterBulan == $row['bulan']) ? 'active' : ''; ?>">
                            <?php echo date("F", mktime(0, 0, 0, $row['bulan'], 10)) . " " . $row['tahun']; ?>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Konten Utama -->
            <div class="col-md-9">
                <h5 class="text-center mb-3">Chart Data Produksi</h5>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h6 class="text-center">Total Data</h6>
                        <div class="chart-container">
                            <canvas id="chartTotalData"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="text-center">Jumlah Produksi per Bulan</h6>
                        <div class="chart-container">
                            <canvas id="chartJumlahProduksi"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="text-center">Telur Cacat per Bulan</h6>
                        <div class="chart-container">
                            <canvas id="chartTelurCacat"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="text-center">Total Produksi per Bulan</h6>
                        <div class="chart-container">
                            <canvas id="chartTotalProduksi"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
document.getElementById('importSheet').onclick = function() {
    fetch('https://docs.google.com/spreadsheets/d/e/2PACX-1vSmGlD1Qj5hHk1xCsJLzPH1ewsnrNYeILB1BQyha1J-VMaclj7rPN5jrWoLL6wCtP6S8Jwrm8_YIqkJ/pub?output=csv')
    .then(response => response.text())
    .then(csv => {
        // Kirim ke server untuk diproses dan simpan ke database
        fetch('import_admin.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'csv=' + encodeURIComponent(csv)
        })
        .then(res => res.text())
        .then(result => {
            document.getElementById('importResult').innerHTML = result;
        });
    })
    .catch(() => {
        document.getElementById('importResult').innerHTML = '<div class="alert alert-danger">Gagal mengambil data dari Google Sheet!</div>';
    });
};
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script>
    // Data untuk chart
    const dataLabel = <?php echo json_encode($dataLabel); ?>;
    const dataJumlahProduksi = <?php echo json_encode($dataJumlahProduksi); ?>;
    const dataTelurCacat = <?php echo json_encode($dataTelurCacat); ?>;
    const dataTotalProduksi = <?php echo json_encode($dataTotalProduksi); ?>;

    // Generate random colors based on the dataset size
    const generateColors = (size) => {
        const colors = [];
        for (let i = 0; i < size; i++) {
            colors.push(`hsl(${Math.floor(Math.random() * 360)}, 70%, 70%)`);
        }
        return colors;
    };

    const colors = generateColors(Math.max(dataLabel.length, 3)); // Ensure at least 3 colors for total data

    // Fungsi untuk menghitung persentase
    function calculatePercentage(value, total) {
        if (total === 0) return '0%';
        return ((value / total) * 100).toFixed(2) + '%';
    }

    // Fungsi untuk mendapatkan nilai terkecil array
    function getMin(arr) {
        if (!arr.length) return 0;
        return Math.min(...arr);
    }

    // Reusable function to create chart configuration
    function createChartConfig(type, labels, data, backgroundColors) {
        const totalSum = data.reduce((sum, val) => sum + val, 0);
        const minValue = getMin(data);

        return {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: type === 'line' ? [] : backgroundColors,
                    borderColor: type === 'line' ? '#000' : null,
                    borderWidth: type === 'line' ? 2 : 0,
                    pointRadius: type === 'line' ? 6 : 0,
                    pointHoverRadius: type === 'line' ? 8 : 0,
                    pointBackgroundColor: type === 'line' ? backgroundColors.slice(0, data.length) : null,
                    pointBorderColor: type === 'line' ? '#000' : null
                }]
            },
            plugins: [ChartDataLabels],
            options: {
                plugins: {
                    legend: {
                        display: type !== 'line'
                    },
                    datalabels: {
                        formatter: (value) => {
                            return calculatePercentage(value, totalSum);
                        },
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: type === 'pie' ? 16 : 12
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
                scales: type === 'line' ? {
                     x: {
                        beginAtZero: true,
                        grid: {
                            lineWidth: 1.5,
                            color: 'rgba(0, 0, 0, 0.1)',
                            drawBorder: true,
                            tickLength: 10
                        },
                        ticks: {
                            font: {
                                size: 14
                            }
                        }
                    },
                    y: {
                        beginAtZero: false,
                        min: Math.min(...data) - 30,
                        max: Math.max(...data) + 30,
                        ticks: {
                            stepSize: 10,
                            font: {
                                size: 14
                            }
                        },
                        grid: {
                            lineWidth: 1.5,
                            color: 'rgba(0, 0, 0, 0.1)',
                            drawBorder: true,
                            tickLength: 10
                        }
                    }
                } : {}
            }
        };
    }

    // Tentukan jenis chart berdasarkan filter
    const chartType = <?php echo ($filterTahun && $filterBulan) ? "'line'" : "'pie'"; ?>;

    // Chart Jumlah Produksi
    const ctxJumlahProduksi = document.getElementById('chartJumlahProduksi').getContext('2d');
    new Chart(ctxJumlahProduksi, createChartConfig(chartType, dataLabel, dataJumlahProduksi, colors.slice(0, dataLabel.length)));

    // Chart Telur Cacat
    const ctxTelurCacat = document.getElementById('chartTelurCacat').getContext('2d');
    new Chart(ctxTelurCacat, createChartConfig(chartType, dataLabel, dataTelurCacat, colors.slice(0, dataLabel.length)));

    // Chart Total Produksi
    const ctxTotalProduksi = document.getElementById('chartTotalProduksi').getContext('2d');
    new Chart(ctxTotalProduksi, createChartConfig(chartType, dataLabel, dataTotalProduksi, colors.slice(0, dataLabel.length)));

    // Chart Total Data
    const ctxTotalData = document.getElementById('chartTotalData').getContext('2d');
    new Chart(ctxTotalData, {
        type: 'pie',
        data: {
            labels: ['Jumlah Produksi', 'Telur Cacat', 'Total Produksi'],
            datasets: [{
                data: [
                    <?php echo $dataTotal['total_jumlah_produksi']; ?>,
                    <?php echo $dataTotal['total_telur_cacat']; ?>,
                    <?php echo $dataTotal['total_total_produksi']; ?>
                ],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
            }]
        },
        plugins: [ChartDataLabels],
        options: {
            plugins: {
                datalabels: {
                    formatter: (value, ctx) => {
                        const total = ctx.chart.data.datasets[0].data.reduce((sum, val) => sum + val, 0);
                        return calculatePercentage(value, total);
                    },
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 16
                    }
                }
            },
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>
</body>
</html>