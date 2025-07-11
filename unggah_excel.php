<?php
require('koneksi.php');
file_put_contents('debug_import.txt', date('Y-m-d H:i:s')." unggah_excel.php masuk\n", FILE_APPEND);

$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vSmGlD1Qj5hHk1xCsJLzPH1ewsnrNYeILB1BQyha1J-VMaclj7rPN5jrWoLL6wCtP6S8Jwrm8_YIqkJ/pub?output=csv";
if (($handle = fopen($csvUrl, "r")) !== FALSE) {
    $rowIndex = 0;
    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if ($rowIndex === 0) { $rowIndex++; continue; } // Lewati header

        $tanggal = trim($row[0] ?? '');
        $jumlah_produksi = trim($row[1] ?? '');
        $jumlah_cacat = trim($row[2] ?? '');
        $total_produksi = trim($row[3] ?? '');
        $id_user = trim($row[5] ?? ''); // Kolom ke-6 (index 5) jika ada persentase di kolom 5

        if (
            !is_numeric($jumlah_produksi) ||
            !is_numeric($jumlah_cacat) ||
            !is_numeric($total_produksi)
        ) {
            $rowIndex++;
            continue;
        }

        // Cek apakah data sudah ada untuk kombinasi tanggal dan id_user
        $cek = $conn->prepare("SELECT COUNT(*) FROM tb_produksi WHERE tanggal = ? AND id_user = ?");
        $cek->bind_param("ss", $tanggal, $id_user);
        $cek->execute();
        $cek->bind_result($sudahAda);
        $cek->fetch();
        $cek->close();
        if ($sudahAda > 0) { $rowIndex++; continue; }

        $stmt = $conn->prepare("INSERT INTO tb_produksi (tanggal, jumlah_produksi, jumlah_cacat, total_produksi, id_user) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiis", $tanggal, $jumlah_produksi, $jumlah_cacat, $total_produksi, $id_user);
        $stmt->execute();
        $stmt->close();
        $rowIndex++;
    }
    fclose($handle);
    file_put_contents('debug_import.txt', date('Y-m-d H:i:s')." unggah_excel.php selesai\n", FILE_APPEND);
} else {
    file_put_contents('debug_import.txt', date('Y-m-d H:i:s')." unggah_excel.php gagal fopen\n", FILE_APPEND);
}
?>