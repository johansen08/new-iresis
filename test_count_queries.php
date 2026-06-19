<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

$start_date = date('Y-m-d 00:00:00');
$end_date = date('Y-m-d 23:59:59');

// Test the EXACT count query when search is empty
$sql_count_no_search = "SELECT COUNT(*) as num FROM tblprintresi a WHERE a.tanggal_printresi >= '$start_date' AND a.tanggal_printresi <= '$end_date'";
$res1 = $mysqli->query($sql_count_no_search);
echo "Count without search: " . ($res1 ? $res1->fetch_assoc()['num'] : "FAILED: " . $mysqli->error) . "\n";

// Test the count query with joins (what happens if search is used)
$sql_count_with_search = "
SELECT COUNT(*) as num 
FROM tblprintresi a 
LEFT JOIN tblresiambilbarang b ON a.id_printresi = b.id_resi 
LEFT JOIN tblpacking c ON a.id_printresi = c.id_resi 
LEFT JOIN tblresikeluar d ON a.id_printresi = d.id_resi 
LEFT JOIN tblkurir e ON e.id_kurir = a.id_kurir 
LEFT JOIN tblmarketplace f ON f.id_marketplace = a.id_marketplace 
LEFT JOIN tblpegawai t1 ON t1.kode_pegawai = a.admin_pegawai 
LEFT JOIN tblpegawai t2 ON t2.kode_pegawai = b.yangambil_pegawai 
LEFT JOIN tbluser t3 ON t3.id_user = c.packer_pegawai 
LEFT JOIN tblpegawai t4 ON t4.kode_pegawai = d.id_pegawai
WHERE a.tanggal_printresi >= '$start_date' AND a.tanggal_printresi <= '$end_date'
";
$res2 = $mysqli->query($sql_count_with_search);
echo "Count with joins (simulated search joins): " . ($res2 ? $res2->fetch_assoc()['num'] : "FAILED: " . $mysqli->error) . "\n";

$mysqli->close();
