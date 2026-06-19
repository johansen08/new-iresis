<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli->connect_error;
    exit();
}

$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

echo "--- REPORT COUNTS ($today_start to $today_end) ---\n\n";

// 1. Simple count print resi today
$q1 = $mysqli->query("SELECT COUNT(1) as cnt FROM tblprintresi WHERE tanggal_printresi >= '$today_start' AND tanggal_printresi <= '$today_end'");
$res1 = $q1->fetch_assoc();
echo "1. Simple Count (tanggal_printresi Today): " . $res1['cnt'] . "\n";

// 2. Count distinct noresi print today
$q2 = $mysqli->query("SELECT COUNT(DISTINCT noresi) as cnt FROM tblprintresi WHERE tanggal_printresi >= '$today_start' AND tanggal_printresi <= '$today_end'");
$res2 = $q2->fetch_assoc();
echo "2. Distinct noresi (tanggal_printresi Today): " . $res2['cnt'] . "\n";

// 3. Simple count deadline today
$q3 = $mysqli->query("SELECT COUNT(1) as cnt FROM tblprintresi WHERE tanggal_bataskirim >= '$today_start' AND tanggal_bataskirim <= '$today_end'");
$res3 = $q3->fetch_assoc();
echo "3. Simple Count (tanggal_bataskirim Today): " . $res3['cnt'] . "\n";

// 4. Breakdown from get_top_stats
$seven_days_ago = date('Y-m-d 00:00:00', strtotime('-7 days'));
$q4 = $mysqli->query("
    SELECT COUNT(DISTINCT pr.id_printresi) as total
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    WHERE (DATE(pr.tanggal_printresi) = DATE('$today_start'))
    OR (DATE(pr.tanggal_bataskirim) = DATE('$today_start'))
    OR (pr.tanggal_printresi >= '$seven_days_ago' AND pr.tanggal_printresi < '$today_start' AND ho.id_resikeluar IS NULL)
");
$res4 = $q4->fetch_assoc();
echo "4. Total Resi (Dashboard Logic): " . $res4['total'] . "\n";

$mysqli->close();
