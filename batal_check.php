<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");
$today = date('Y-m-d');
$sql = "SELECT COUNT(1) as cnt FROM tblprintresi WHERE DATE(tanggal_printresi) = '$today' AND (batal IS NOT NULL AND batal != '')";
$q = $mysqli->query($sql);
echo "BATAL_TODAY:" . $q->fetch_assoc()['cnt'] . "\n";
$mysqli->close();
