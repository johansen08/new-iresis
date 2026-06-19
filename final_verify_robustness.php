<?php
$mysqli = new mysqli("127.0.0.1", "root", "", "iresis-prod");

$start_date = date('Y-m-d 00:00:00');
$end_date = date('Y-m-d 23:59:59');

function test_query($mysqli, $start_date, $end_date, $order_col, $dir = 'DESC') {
    echo "Testing Order: $order_col $dir... ";
    $start_time = microtime(true);
    
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
    ORDER BY $order_col $dir
    LIMIT 10
    ";
    
    $res = $mysqli->query($sql);
    $end_time = microtime(true);
    $duration = $end_time - $start_time;
    
    if ($res) {
        echo "SUCCESS (" . round($duration, 4) . "s, " . $res->num_rows . " rows)\n";
    } else {
        echo "FAILED: " . $mysqli->error . "\n";
    }
}

test_query($mysqli, $start_date, $end_date, 'a.tanggal_printresi', 'DESC');
test_query($mysqli, $start_date, $end_date, 'f.nama_marketplace', 'ASC');
test_query($mysqli, $start_date, $end_date, 'e.nama_kurir', 'ASC');
test_query($mysqli, $start_date, $end_date, 'a.noresi', 'ASC');

$mysqli->close();
