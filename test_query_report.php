<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

$start_date = date('Y-m-d 00:00:00');
$end_date = date('Y-m-d 23:59:59');

echo "Replicating get_data_daily_report for range: $start_date to $end_date\n";

$sql = "
SELECT 
    f.nama_marketplace,
    e.nama_kurir,
    a.noresi,
    a.nomorpicklist,
    a.tanggal_printresi,
    t1.nama_pegawai as admin_scan,
    b.tanggal_resiambilbarang,
    t2.nama_pegawai as admin_picker,
    COALESCE(sp_picker.status_name, '') as picker_status,
    c.tanggal_packing,
    t3.name as admin_packer,
    COALESCE(sp_packer.status_name, '') as packer_status,
    d.tanggal_resikeluar,
    t4.nama_pegawai as admin_ho
FROM (
    SELECT id_printresi, noresi, nomorpicklist, tanggal_printresi, id_marketplace, id_kurir, admin_pegawai
    FROM tblprintresi 
    WHERE tanggal_printresi >= '$start_date'
    AND tanggal_printresi <= '$end_date'
    ORDER BY tanggal_printresi DESC
    LIMIT 10 OFFSET 0
) a
LEFT JOIN tblresiambilbarang b ON a.id_printresi = b.id_resi
LEFT JOIN tblpacking c ON a.id_printresi = c.id_resi
LEFT JOIN tblresikeluar d ON a.id_printresi = d.id_resi
LEFT JOIN tblkurir e ON e.id_kurir = a.id_kurir
LEFT JOIN tblmarketplace f ON f.id_marketplace = a.id_marketplace
LEFT JOIN tblpegawai t1 ON t1.kode_pegawai = a.admin_pegawai
LEFT JOIN tblpegawai t2 ON t2.kode_pegawai = b.yangambil_pegawai
LEFT JOIN tbluser t3 ON t3.id_user = c.packer_pegawai
LEFT JOIN tblpegawai t4 ON t4.kode_pegawai = d.id_pegawai
LEFT JOIN tblmasterstatusperforma sp_picker ON sp_picker.id_statusperforma = b.status_performa_id
LEFT JOIN tblmasterstatusperforma sp_packer ON sp_packer.id_statusperforma = c.status_performa_id
";

$res = $mysqli->query($sql);
if (!$res) {
    echo "Query failed: " . $mysqli->error . "\n";
} else {
    echo "Results found in replicated query: " . $res->num_rows . "\n";
    while ($row = $res->fetch_assoc()) {
        echo " - No Resi: " . $row['noresi'] . ", Market: " . $row['nama_marketplace'] . ", Picker: " . $row['admin_picker'] . "\n";
    }
}

// Check if total count query works too
$sql_total = "SELECT COUNT(*) as num FROM tblprintresi a WHERE a.tanggal_printresi >= '$start_date' AND a.tanggal_printresi <= '$end_date'";
$total = $mysqli->query($sql_total)->fetch_assoc()['num'];
echo "Total in tblprintresi (count query): $total\n";

$mysqli->close();
