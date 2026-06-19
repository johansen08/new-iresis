<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

$today = date('Y-m-d');
$cancel_filter = "(pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)";

echo "--- BACKLOG DISTRIBUTION BY MONTH (Deadline <= Today, NOT HO) ---\n\n";

$sql = "
    SELECT 
        DATE_FORMAT(pr.tanggal_bataskirim, '%Y-%m') as month,
        COUNT(*) as cnt
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    WHERE DATE(pr.tanggal_bataskirim) <= '$today'
    AND ho.id_resikeluar IS NULL
    AND $cancel_filter
    GROUP BY month
    ORDER BY month DESC
";

$q = $mysqli->query($sql);
while($r = $q->fetch_assoc()) {
    echo $r['month'] . ": " . $r['cnt'] . "\n";
}

$mysqli->close();
