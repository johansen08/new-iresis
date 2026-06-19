<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

$today = date('Y-m-d');
$seven_days_ago = date('Y-m-d 00:00:00', strtotime('-7 days'));
$cancel_filter = "(status_pesanan NOT LIKE '%CANCEL%' OR status_pesanan IS NULL)";

echo "--- DIAGNOSTIC LOGIC BATAS KIRIM PAKET ($today) ---\n\n";

// 1. Top Total Resi (The Widget)
$sql_top = "
    SELECT COUNT(DISTINCT pr.id_printresi) as total
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    WHERE (
        (DATE(pr.tanggal_printresi) = '$today')
        OR (DATE(pr.tanggal_bataskirim) = '$today')
        OR (pr.tanggal_printresi >= '$seven_days_ago' AND pr.tanggal_printresi < '$today 00:00:00' AND ho.id_resikeluar IS NULL)
    )
    AND $cancel_filter
";
$top_total = $mysqli->query($sql_top)->fetch_assoc()['total'];
echo "A. TOTAL RESI (TOP WIDGET): $top_total\n";

// 2. 5-Day Panels
$panel_sum = 0;
$panel_ids = [];
for ($i = 0; $i < 5; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $sql_day = "
        SELECT pr.id_printresi
        FROM tblprintresi pr
        LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
        WHERE DATE(pr.tanggal_bataskirim) = '$date' 
        AND ho.id_resikeluar IS NULL
        AND $cancel_filter
    ";
    $q = $mysqli->query($sql_day);
    $count = $q->num_rows;
    echo "   - Panel Day $i ($date): $count\n";
    $panel_sum += $count;
    
    while($r = $q->fetch_assoc()) {
        $panel_ids[] = $r['id_printresi'];
    }
}
$unique_panel_ids = array_unique($panel_ids);
echo "B. TOTAL UNIQUE IN 5 PANELS: " . count($unique_panel_ids) . "\n";

// 3. Backlog (In Top Widget but NOT in 5 Panels)
// These are:
// - Printed Today but Deadline is NOT in [Today, Today+4] (e.g. Deadline Yesterday or Deadline Today+5)
// - Pending 7 Days (Deadline Yesterday or older)
$sql_backlog = "
    SELECT COUNT(DISTINCT pr.id_printresi) as total
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    WHERE (
        (DATE(pr.tanggal_printresi) = '$today')
        OR (DATE(pr.tanggal_bataskirim) = '$today')
        OR (pr.tanggal_printresi >= '$seven_days_ago' AND pr.tanggal_printresi < '$today 00:00:00' AND ho.id_resikeluar IS NULL)
    )
    AND $cancel_filter
    AND pr.id_printresi NOT IN (
        SELECT pr2.id_printresi
        FROM tblprintresi pr2
        LEFT JOIN tblresikeluar ho2 ON ho2.id_resi = pr2.id_printresi
        WHERE DATE(pr2.tanggal_bataskirim) BETWEEN '$today' AND '" . date('Y-m-d', strtotime('+4 days')) . "'
        AND ho2.id_resikeluar IS NULL
        AND (pr2.status_pesanan NOT LIKE '%CANCEL%' OR pr2.status_pesanan IS NULL)
    )
";
$backlog = $mysqli->query($sql_backlog)->fetch_assoc()['total'];
echo "C. BACKLOG (In Top Widget BUT NOT in any 5 Panels): $backlog\n";

// 4. Verification
echo "\n--- VERIFICATION ---\n";
echo "Top Widget ($top_total) = Panels (" . count($unique_panel_ids) . ") + Backlog ($backlog)?\n";
if ($top_total == count($unique_panel_ids) + $backlog) {
    echo "STATUS: CONSISTENT\n";
} else {
    echo "STATUS: DISCREPANCY FOUND\n";
}

$mysqli->close();
