<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

$today = date('Y-m-d');
$cancel_filter = "(pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)";

echo "--- REVERTED INDICATOR CHECK ---\n\n";

$where_clause = "DATE(pr.tanggal_bataskirim) = '$today' AND ho.id_resikeluar IS NULL";

$subquery = "
    SELECT 
        pr.id_printresi,
        COUNT(DISTINCT dpr.sku) as distinct_skus,
        SUM(dpr.jumlah) as total_qty,
        MAX(CASE WHEN s.is_special = 1 THEN 1 ELSE 0 END) as has_special
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    JOIN tbldetailprintresi dpr ON dpr.id_resi = pr.id_printresi
    LEFT JOIN tblsku s ON s.id_sku = dpr.sku
    WHERE $where_clause
    AND $cancel_filter
    GROUP BY pr.id_printresi
";

$sql = "
    SELECT
        COUNT(*) as total_resi,
        SUM(CASE WHEN has_special = 1 THEN 1 ELSE 0 END) as sku_special,
        SUM(CASE WHEN has_special = 0 AND (total_qty > 50 OR distinct_skus > 50) THEN 1 ELSE 0 END) as resi_qty_banyak,
        SUM(CASE WHEN has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND distinct_skus >= 10 THEN 1 ELSE 0 END) as resi_lebih_10_sku,
        SUM(CASE WHEN has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as resi_satuan,
        SUM(CASE WHEN has_special = 0 AND NOT (total_qty > 50 OR distinct_skus > 50) AND NOT (distinct_skus >= 10) AND NOT (distinct_skus = 1 AND total_qty = 1) THEN 1 ELSE 0 END) as resi_kurang_10_sku
    FROM ($subquery) as indicators
";

$res = $mysqli->query($sql)->fetch_assoc();
echo "Date: $today\n";
echo "  TOTAL: " . $res['total_resi'] . "\n";
echo "  RESI 1 SKU (satuan): " . $res['resi_satuan'] . "\n";
echo "  RESI 2-10 SKU (kurang 10): " . $res['resi_kurang_10_sku'] . "\n";

$mysqli->close();
