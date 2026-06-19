<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

$start_date = date('Y-m-d 00:00:00');
$end_date = date('Y-m-d 23:59:59');

echo "Checking data for range: $start_date to $end_date\n";

$sql_total = "SELECT COUNT(*) as total FROM tblprintresi WHERE tanggal_printresi >= '$start_date' AND tanggal_printresi <= '$end_date'";
$total = $mysqli->query($sql_total)->fetch_assoc()['total'];
echo "Total in tblprintresi: $total\n";

$sql_sample = "SELECT * FROM tblprintresi WHERE tanggal_printresi >= '$start_date' AND tanggal_printresi <= '$end_date' LIMIT 5";
$res_sample = $mysqli->query($sql_sample);
echo "Samples found: " . $res_sample->num_rows . "\n";
while ($row = $res_sample->fetch_assoc()) {
    echo " - No Resi: " . $row['noresi'] . ", Tanggal: " . $row['tanggal_printresi'] . "\n";
}

$mysqli->close();
