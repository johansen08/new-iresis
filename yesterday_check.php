<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");
$yesterday = date('Y-m-d', strtotime('-1 day'));
$q = $mysqli->query("SELECT COUNT(1) as cnt FROM tblprintresi WHERE DATE(tanggal_printresi) = '$yesterday'");
echo "YESTERDAY_COUNT:" . $q->fetch_assoc()['cnt'] . "\n";
$mysqli->close();
