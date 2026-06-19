<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");
$today = date('Y-m-d');
$sql = "SELECT COUNT(1) as total FROM tblresikeluar WHERE DATE(tanggal_resikeluar) = '$today'";
$q = $mysqli->query($sql);
echo "TOTAL_HO_TODAY:" . $q->fetch_assoc()['total'] . "\n";
$mysqli->close();
