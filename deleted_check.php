<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");
$today = date('Y-m-d');
$sql = "SELECT COUNT(1) as cnt FROM tblprintresihapus WHERE DATE(tanggal_printresi_hapus) = '$today'";
$q = $mysqli->query($sql);
echo "DELETED_TODAY:" . $q->fetch_assoc()['cnt'] . "\n";
$mysqli->close();
