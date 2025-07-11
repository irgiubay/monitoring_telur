<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('koneksi.php');

$sukses = 0;
$gagal = 0;

// Ambil semua data status_kirim=0
$query = "SELECT tanggal, SUM(jumlah_produksi) AS jumlah_produksi, SUM(jumlah_cacat) AS jumlah_cacat, SUM(total_produksi) AS total_produksi, GROUP_CONCAT(id) AS ids
          FROM tb_produksi
          WHERE status_kirim = 0
          GROUP BY tanggal
          ORDER BY tanggal";
$result = $conn->query($query);

$url = 'https://script.google.com/macros/s/AKfycbzeI0yeWih4a_pTp_oCRCxHbg2LWRj6t9gyrQ0e3mjpqFUCE5vKjAD1Mk8GEXaq2rin/exec';

$dataArray = [];
$idArray = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dataArray[] = [
            'tanggal' => $row['tanggal'],
            'jumlah_produksi' => (int)$row['jumlah_produksi'],
            'jumlah_cacat' => (int)$row['jumlah_cacat'],
            'total_produksi' => (int)$row['total_produksi']
        ];
        // Gabungkan semua id yang tergabung pada tanggal yang sama
        foreach (explode(',', $row['ids']) as $id) {
            $idArray[] = (int)$id;
        }
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['data' => $dataArray]));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $resultSend = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<pre>Response dari Apps Script: [" . htmlspecialchars($resultSend) . "]</pre>";

    if ($resultSend !== FALSE && stripos(trim($resultSend), "OK") !== false) {
        $ids = implode(',', $idArray);
        if (!empty($ids)) {
            $conn->close();
            require('koneksi.php');
            $conn->query("UPDATE tb_produksi SET status_kirim = 1 WHERE id IN ($ids)");
        }
        $sukses = count($dataArray);
        $gagal = 0;
    } else {
        $sukses = 0;
        $gagal = count($dataArray);
    }
    echo "<script>alert('Sukses kirim: $sukses baris\\nGagal kirim: $gagal baris');window.location.href='data_produksi.php';</script>";
} else {
    echo "<script>alert('Tidak ada data yang dikirim.');window.location.href='data_produksi.php';</script>";
}
?>