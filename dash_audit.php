<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

$today = date('Y-m-d');
$seven_days_ago = date('Y-m-d 00:00:00', strtotime('-7 days'));
$cancel_filter = "(status_pesanan NOT LIKE '%CANCEL%' OR status_pesanan IS NULL)";

echo "--- DASHBOARD AUDIT ($today) ---\n\n";

// 1. TOTAL RESI Audit
$sql_printed = "SELECT COUNT(*) as cnt FROM tblprintresi WHERE DATE(tanggal_printresi) = '$today' AND $cancel_filter";
$printed_today = $mysqli->query($sql_printed)->fetch_assoc()['cnt'];

$sql_pending = "
    SELECT COUNT(*) as cnt 
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    WHERE pr.tanggal_printresi >= '$seven_days_ago' 
    AND pr.tanggal_printresi < '$today 00:00:00' 
    AND ho.id_resikeluar IS NULL
    AND $cancel_filter
";
$pending_7d = $mysqli->query($sql_pending)->fetch_assoc()['cnt'];

$total_expected = $printed_today + $pending_7d;

echo "1. TOTAL RESI BREAKDOWN:\n";
echo "   - Printed Today (All status): $printed_today\n";
echo "   - Pending Last 7 Days (Not HO): $pending_7d\n";
echo "   - Combined Unique Total (A+B): $total_expected\n";

// 2. Indicators Audit for "Today" Panel (DATE = Today)
$sql_today_ind = "
    SELECT 
        pr.id_printresi,
        COUNT(DISTINCT dpr.sku) as distinct_skus,
        SUM(dpr.jumlah) as total_qty,
        MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
    LEFT JOIN tblsku s ON s.id_sku = dpr.sku
    WHERE DATE(pr.tanggal_bataskirim) = '$today'
    AND ho.id_resikeluar IS NULL
    AND $cancel_filter
    GROUP BY pr.id_printresi
";

$q = $mysqli->query("
    SELECT
        COUNT(*) as total_resi,
        SUM(CASE WHEN has_special = 1 THEN 1 ELSE 0 END) as sku_special,
        SUM(CASE WHEN has_special = 0 AND (total_qty > 50 OR distinct_skus > 50) THEN 1 ELSE 0 END) as qty_banyak,
        SUM(CASE WHEN has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND distinct_skus >= 10 THEN 1 ELSE 0 END) as lebih_10_sku,
        SUM(CASE WHEN has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as satuan,
        SUM(CASE WHEN has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND NOT (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as kurang_10_sku
    FROM ($sql_today_ind) as indicators
");
$ind = $q->fetch_assoc();

echo "\n2. TODAY PANEL INDICATORS (Deadline = Today, Not HO):\n";
echo "   - Total in Panel: " . $ind['total_resi'] . "\n";
echo "   - SKU SPECIAL: " . $ind['sku_special'] . "\n";
echo "   - QTY > 50: " . $ind['qty_banyak'] . "\n";
echo "   - > 10 SKU: " . $ind['lebih_10_sku'] . "\n";
echo "   - 1 SKU: " . $ind['satuan'] . "\n";
echo "   - 2-10 SKU: " . $ind['kurang_10_sku'] . "\n";

// 3. Consistency Check
echo "\n3. CONSISTENCY CHECK:\n";
echo "   - Why was 1 SKU ever 11,112?\n";
$sql_crazy = "
    SELECT COUNT(*) as total
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    WHERE DATE(pr.tanggal_bataskirim) <= '$today'
    AND ho.id_resikeluar IS NULL
    AND $cancel_filter
";
$crazy = $mysqli->query($sql_crazy)->fetch_assoc()['total'];
echo "   - Total Resi (Deadline <= Today, Not HO, NO DATE LIMIT): $crazy\n";

$mysqli->close();
