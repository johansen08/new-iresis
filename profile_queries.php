<?php
$conn = mysqli_connect('localhost', 'root', '', 'iresis-prod');
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$start_date = '2026-02-26 00:00:00';
$end_date = '2026-02-26 23:59:59';

function profile_query($conn, $name, $sql) {
    echo "Profiling: $name\n";
    $start = microtime(true);
    $res = mysqli_query($conn, $sql);
    $end = microtime(true);
    if ($res) {
        echo "Time: " . round($end - $start, 4) . "s, Rows: " . mysqli_num_rows($res) . "\n";
    } else {
        echo "Error: " . mysqli_error($conn) . "\n";
    }
    echo "-----------------------------------\n";
}

// 1. Header Scan
profile_query($conn, "Header Scan", "SELECT COUNT(a.id_printresi) as num FROM tblprintresi a WHERE a.tanggal_printresi >= '$start_date' AND a.tanggal_printresi <= '$end_date'");

// 2. Header Pick
profile_query($conn, "Header Pick", "SELECT COUNT(b.id_resiambilbarang) as num FROM tblresiambilbarang b JOIN tblprintresi a ON a.id_printresi = b.id_resi WHERE a.tanggal_printresi >= '$start_date' AND a.tanggal_printresi <= '$end_date'");

// 3. Header Pack
profile_query($conn, "Header Pack", "SELECT COUNT(c.id_packing) as num FROM tblpacking c JOIN tblprintresi a ON a.id_printresi = c.id_resi WHERE a.tanggal_printresi >= '$start_date' AND a.tanggal_printresi <= '$end_date'");

// 4. Header HO
profile_query($conn, "Header HO", "SELECT COUNT(d.id_resikeluar) as num FROM tblresikeluar d JOIN tblprintresi a ON a.id_printresi = d.id_resi WHERE a.tanggal_printresi >= '$start_date' AND a.tanggal_printresi <= '$end_date'");

// 5. Main Data (NO SEARCH - Subquery optimized)
$sql_main_no_search = "
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

profile_query($conn, "Main Data (No Search)", $sql_main_no_search);

// 6. Total Count
profile_query($conn, "Total Count", "SELECT COUNT(*) FROM tblprintresi a WHERE a.tanggal_printresi >= '$start_date' AND a.tanggal_printresi <= '$end_date'");

mysqli_close($conn);
?>
