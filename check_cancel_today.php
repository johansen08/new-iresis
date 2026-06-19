<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");
$today = date('Y-m-d');
$q = $mysqli->query("SELECT COUNT(*) as cnt FROM tblprintresi WHERE DATE(tanggal_printresi) = '$today' AND (status_pesanan LIKE '%CANCEL%')");
echo "CANCELLED_TODAY:" . $q->fetch_assoc()['cnt'] . "\n";
$mysqli->close();
