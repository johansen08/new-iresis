<!DOCTYPE html>
<html>
<head>
    <title>Laporan Scan <?= $type ?></title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h3>Laporan Scan <?= $type ?></h3>
    <p>Periode: <?= $start_date ?> s/d <?= $end_date ?></p>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No Resi</th>
                <th>Marketplace</th>
                <th>Kurir</th>
                <th>Waktu Scan</th>
                <th>Admin Scan</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($list_data as $row): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= $row['noresi'] ?></td>
                <td><?= $row['nama_marketplace'] ?></td>
                <td><?= $row['nama_kurir'] ?></td>
                <td><?= $row['tanggal_scan'] ?></td>
                <td><?= $row['nama_pegawai'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
