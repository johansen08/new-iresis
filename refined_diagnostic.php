<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

$today = date('Y-m-d');
$seven_days_ago = date('Y-m-d 00:00:00', strtotime('-7 days'));
$cancel_filter = "(status_pesanan NOT LIKE '%CANCEL%' OR status_pesanan IS NULL)";

echo "--- REFINED DIAGNOSTIC ($today) ---\n\n";

// 1. Backlog Breakdown
$sql_overdue = "
    SELECT COUNT(DISTINCT pr.id_printresi) as total
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    WHERE DATE(pr.tanggal_bataskirim) < '$today'
    AND pr.tanggal_printresi >= '$seven_days_ago'
    AND ho.id_resikeluar IS NULL
    AND $cancel_filter
";
$overdue = $mysqli->query($sql_overdue)->fetch_assoc()['total'];
echo "1. Overdue (Deadline < Today, Not HO, Printed 7d): $overdue\n";

$sql_future_print = "
    SELECT COUNT(DISTINCT pr.id_printresi) as total
    FROM tblprintresi pr
    WHERE DATE(pr.tanggal_printresi) = '$today'
    AND DATE(pr.tanggal_bataskirim) > '" . date('Y-m-d', strtotime('+4 days')) . "'
    AND $cancel_filter
";
$future_print = $mysqli->query($sql_future_print)->fetch_assoc()['total'];
echo "2. Future Deadline (Deadline > Today+4, Printed Today): $future_print\n";

$sql_today_panel = "
    SELECT COUNT(DISTINCT pr.id_printresi) as total
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    WHERE DATE(pr.tanggal_bataskirim) = '$today'
    AND ho.id_resikeluar IS NULL
    AND $cancel_filter
";
$today_count = $mysqli->query($sql_today_panel)->fetch_assoc()['total'];
echo "3. Current 'Today' Panel: $today_count\n";

echo "\n--- QUESTION ---\n";
echo "Should the 'Today' panel include Overdue resi ($overdue)?\n";
echo "If yes, the 'Today' panel would be: " . ($today_count + $overdue) . "\n";

$mysqli->close();
