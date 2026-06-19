<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

$today = date('Y-m-d');
$seven_days_ago = date('Y-m-d 00:00:00', strtotime('-7 days'));

$sql_pending = "
    SELECT COUNT(DISTINCT pr.id_printresi) as total
    FROM tblprintresi pr
    LEFT JOIN tblresikeluar ho ON ho.id_resi = pr.id_printresi
    WHERE (
        (DATE(pr.tanggal_printresi) = '$today')
        OR (DATE(pr.tanggal_bataskirim) = '$today')
        OR (pr.tanggal_printresi >= '$seven_days_ago' AND pr.tanggal_printresi < '$today 00:00:00' AND ho.id_resikeluar IS NULL)
    )
    AND (pr.status_pesanan NOT LIKE '%CANCEL%' OR pr.status_pesanan IS NULL)
";

$res = $mysqli->query($sql_pending)->fetch_assoc();
echo "NEW_TOTAL_RESI_FILTERED:" . $res['total'] . "\n";

$mysqli->close();
