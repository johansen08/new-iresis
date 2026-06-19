<?php
$conn = mysqli_connect('localhost', 'root', '', 'iresis-prod');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$start_date = '2026-02-26 00:00:00';
$end_date = '2026-02-26 23:59:59';

// Default order by used in Receipt_fcd.php (NEW FIXED LOGIC)
$order_by = " ORDER BY tanggal_printresi DESC";

$sql = "
        SELECT 
            a.noresi
        FROM (
            SELECT id_printresi, noresi, tanggal_printresi
            FROM tblprintresi 
            WHERE tanggal_printresi >= '$start_date'
            AND tanggal_printresi <= '$end_date'
            {$order_by}
            LIMIT 10 OFFSET 0
        ) a
";

echo "Running query with default order by (NEW FIXED LOGIC)...\n";
$res = mysqli_query($conn, $sql);
if ($res) {
    echo "Success! Rows: " . mysqli_num_rows($res) . "\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
