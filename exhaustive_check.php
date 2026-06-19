<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");
$today = date('Y-m-d');
$today_start = "$today 00:00:00";
$today_end = date('Y-m-d H:i:s'); // Use H:i:s to match controller logic

echo "--- EXHAUSTIVE COUNTS ($today_start to $today_end) ---\n\n";

// 1. Header Daily Report (Welcome Page / Daily Receipt Report Header)
$q1 = $mysqli->query("SELECT count(1) as total FROM tblprintresi WHERE tanggal_printresi >= '$today_start' AND tanggal_printresi <= '$today_end'");
echo "1. Header Daily Report (tanggal_printresi today): " . $q1->fetch_assoc()['total'] . "\n";

// 2. Production Target (Deadline Today)
$q2 = $mysqli->query("SELECT COUNT(id_printresi) as total FROM tblprintresi WHERE DATE(tanggal_bataskirim) = '$today'");
echo "2. Production Target (Deadline Today): " . $q2->fetch_assoc()['total'] . "\n";

// 3. Per Day Report (In Process) - with distinct noresi
$q3 = $mysqli->query("
    SELECT COUNT(DISTINCT t.noresi) as num
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
");
echo "3. Per Day Report (In Process Today): " . $q3->fetch_assoc()['num'] . "\n";

// 4. Recipe Report Tab 0 (Total Resi Report) - distinct noresi
$q4 = $mysqli->query("
    SELECT COUNT(DISTINCT t.noresi) AS num 
    FROM tblprintresi t
    WHERE t.tanggal_printresi >= '$today_start'
    AND t.tanggal_printresi <= '$today_end'
");
echo "4. Total Resi Report (Distinct noresi today): " . $q4->fetch_assoc()['num'] . "\n";

// 5. Any chance 6191 is today's printresi including duplicates?
$q5 = $mysqli->query("SELECT COUNT(*) as total FROM tblprintresi WHERE DATE(tanggal_printresi) = '$today'");
echo "5. Total tblprintresi (DATE(tanggal_printresi) = today): " . $q5->fetch_assoc()['total'] . "\n";

// 6. What if it's based on created_at?
$q6 = $mysqli->query("SELECT COUNT(*) as total FROM tblprintresi WHERE DATE(created_at) = '$today'");
echo "6. Total tblprintresi (DATE(created_at) = today): " . $q6->fetch_assoc()['total'] . "\n";

$mysqli->close();
