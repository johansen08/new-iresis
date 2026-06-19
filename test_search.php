<?php
$conn = mysqli_connect('localhost', 'root', '', 'iresis-prod');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$start_date = '2026-02-26 00:00:00';
$end_date = '2026-02-26 23:59:59';
$search = 'test';

$search_conditions = [
    "f.nama_marketplace LIKE '%{$search}%'",
    "e.nama_kurir LIKE '%{$search}%'",
    "a.noresi LIKE '%{$search}%'",
    "a.nomorpicklist LIKE '%{$search}%'",
    "t1.nama_pegawai LIKE '%{$search}%'",
    "t2.nama_pegawai LIKE '%{$search}%'",
    "t3.name LIKE '%{$search}%'",
    "t4.nama_pegawai LIKE '%{$search}%'"
];
$search_where = " AND (" . implode(" OR ", $search_conditions) . ")";

// NEW LOGIC: Joins are OUTSIDE subquery when searching
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
            LEFT JOIN tblmasterstatusperforma sp_picker ON sp_picker.id_statusperforma = b.status_performa_id
            LEFT JOIN tblmasterstatusperforma sp_packer ON sp_packer.id_statusperforma = c.status_performa_id
            WHERE a.tanggal_printresi >= '$start_date'
            AND a.tanggal_printresi <= '$end_date'
            {$search_where}
            ORDER BY a.tanggal_printresi DESC
            LIMIT 10 OFFSET 0
";

echo "Running query with search term (NEW LOGIC)...\n";
$res = mysqli_query($conn, $sql);
if ($res) {
    echo "Success! Rows: " . mysqli_num_rows($res) . "\n";
} else {
    echo "Error: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
