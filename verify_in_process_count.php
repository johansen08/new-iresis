<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

echo "--- IN PROCESS REPORT VERIFICATION ($today_start to $today_end) ---\n\n";

// Logic from get_total_data_per_day_report
$sql = "
    SELECT COUNT(DISTINCT t.id_printresi) as num
    FROM tblprintresi t
    LEFT JOIN tblresiambilbarang t2 ON t.id_printresi = t2.id_resi
    LEFT JOIN tblpacking t3 ON t.id_printresi = t3.id_resi
    LEFT JOIN tblresikeluar t4 ON t.id_printresi = t4.id_resi
    WHERE t.tanggal_printresi >= '$today_start'
    AND t.tanggal_printresi <= '$today_end'
    AND (
        t2.id_resiambilbarang IS NULL
        OR t3.id_packing IS NULL
        OR t4.id_resikeluar IS NULL
    )
";

$q = $mysqli->query($sql);
$res = $q->fetch_assoc();
echo "Count of 'In Process' Resi Today: " . $res['num'] . "\n";

$mysqli->close();
