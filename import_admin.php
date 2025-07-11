<?php
session_start();
require('koneksi.php');
$alert = [];
$jumlahDataBaru = 0;

if (!isset($_POST['csv'])) {
    echo '<div class="alert alert-danger">Tidak ada data CSV dikirim!</div>';
    exit;
}

$csv = $_POST['csv'];
$rows = explode("\n", $csv);
foreach ($rows as $rowIndex => $line) {
    if ($rowIndex === 0 || trim($line) === '') continue; // Lewati header/empty
    $cols = str_getcsv($line);
    $tanggal = $cols[0] ?? '';
    $jumlah_produksi = $cols[1] ?? '';
    $jumlah_cacat = $cols[2] ?? '';
    $total_produksi = $cols[3] ?? '';
    $id_user = $cols[5] ?? ''; // Kolom ke-6 (index 5) jika ada persentase di kolom 5

    if (
        !is_numeric($jumlah_produksi) ||
        !is_numeric($jumlah_cacat) ||
        !is_numeric($total_produksi)
    ) {
        $alert[] = "Baris $rowIndex: Data bukan angka!";
        continue;
    }
    // Cek apakah data sudah ada untuk kombinasi tanggal dan id_user
    $cek = $conn->prepare("SELECT COUNT(*) FROM tb_produksi WHERE tanggal = ? AND id_user = ?");
    $cek->bind_param("ss", $tanggal, $id_user);
    $cek->execute();
    $cek->bind_result($sudahAda);
    $cek->fetch();
    $cek->close();
    if ($sudahAda > 0) continue;

    $stmt = $conn->prepare("INSERT INTO tb_produksi (tanggal, jumlah_produksi, jumlah_cacat, total_produksi, id_user) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siiis", $tanggal, $jumlah_produksi, $jumlah_cacat, $total_produksi, $id_user);
    if ($stmt->execute()) $jumlahDataBaru++;
    $stmt->close();
}

if ($jumlahDataBaru > 0) {
    echo '<div class="alert alert-success">Ada ' . $jumlahDataBaru . ' data baru dari Google Spreadsheet yang berhasil diimpor ke database!</div>';
} elseif (count($alert) > 0) {
    echo '<div class="alert alert-danger">' . implode('<br>', $alert) . '</div>';
} else {
    echo '<div class="alert alert-info">Tidak ada perubahan data di Google Spreadsheet. Tidak ada data baru yang di-upload.</div>';
}
?>