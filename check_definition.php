<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

$today = date('Y-m-d');
$cancel_filter = "(pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)";

echo "--- DEFINITION CHECK: 1 SKU ---\n\n";

$base_sql = "
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
    WHERE DATE(pr.tanggal_bataskirim) = '$today'
    AND ho.id_resikeluar IS NULL
    AND $cancel_filter
";

// Definition A: 1 SKU 1 Qty (Current)
$sqlA = "
    SELECT COUNT(*) as cnt FROM (
        SELECT pr.id_printresi, COUNT(DISTINCT dpr.sku) as ds, SUM(dpr.jumlah) as tq
        $base_sql
        GROUP BY pr.id_printresi
        HAVING ds = 1 AND tq = 1
    ) t
";
$cntA = $mysqli->query($sqlA)->fetch_assoc()['cnt'];

// Definition B: 1 SKU Any Qty
$sqlB = "
    SELECT COUNT(*) as cnt FROM (
        SELECT pr.id_printresi, COUNT(DISTINCT dpr.sku) as ds
        $base_sql
        GROUP BY pr.id_printresi
        HAVING ds = 1
    ) t
";
$cntB = $mysqli->query($sqlB)->fetch_assoc()['cnt'];

echo "If Definition is '1 SKU 1 Qty' (Current): $cntA\n";
echo "If Definition is '1 SKU (Any Qty)': $cntB\n";

$mysqli->close();
