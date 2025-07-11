<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pemisah_telur"; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>