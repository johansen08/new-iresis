<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");
$today = date('Y-m-d');
$cancel_filter = "(status_pesanan NOT LIKE '%CANCEL%' OR status_pesanan IS NULL)";

$q1 = $mysqli->query("SELECT COUNT(*) as cnt FROM tblprintresi WHERE DATE(tanggal_printresi) = '$today' AND $cancel_filter");
$c1 = $q1->fetch_assoc()['cnt'];

$q2 = $mysqli->query("SELECT COUNT(DISTINCT noresi) as cnt FROM tblprintresi WHERE DATE(tanggal_printresi) = '$today' AND $cancel_filter");
$c2 = $q2->fetch_assoc()['cnt'];

echo "TOTAL_RECORDS:" . $c1 . "\n";
echo "TOTAL_UNIQUE_RESI:" . $c2 . "\n";

$mysqli->close();
