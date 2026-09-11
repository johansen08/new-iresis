<!DOCTYPE html>
<html>
<head>
    <title>Laporan Lost Scan Packer</title>
</head>
<body>
    <div style="text-align: center;">
        <h2>Laporan Lost Scan <?= isset($type) ? $type : 'PACKER' ?></h2>
        <h4>Periode: <?= $reportrange ?></h4>
    </div>
    <br>
    <table border="1">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th width="50">No</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>No Resi</th>
                <th>Tipe</th>
                <th>Status Resi</th>
                <th>Kurir</th>
                <th>Karyawan (Pelaku)</th>
                <th>Pelapor</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($list_data)) : ?>
                <?php $no = 1; foreach ($list_data as $row) : ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                        <td><?= date('H:i:s', strtotime($row['created_at'])) ?></td>
                        <td>'<?= $row['noresi'] ?></td> <!-- Quote to prevent scientific notation in Excel -->
                        <td><?= $row['lost_type'] ?></td>
                        <td><?= $row['status_resi'] ?></td>
                        <td><?= $row['kurir'] ?></td>
                        <td><?= $row['nama_packer'] ?></td>
                        <td><?= $row['nama_pelapor'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="9" align="center">Data tidak ditemukan</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
