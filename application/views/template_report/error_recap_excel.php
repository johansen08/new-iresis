<!DOCTYPE html>
<html>
<head>
    <title>Rekap Kesalahan <?= $type ?></title>
</head>
<body>
    <div style="text-align: center;">
        <h2>Rekap Kesalahan Kerja <?= $type ?></h2>
        <h4>Periode Pekerjaan: <?= $reportrange ?> | Tipe Pengelompokkan: <?= ucfirst($period) ?></h4>
    </div>
    <br>
    <table border="1">
        <thead>
            <tr style="background-color: #337ab7; color: white;">
                <th width="50">No</th>
                <th>Nama <?= $type ?></th>
                <th>Periode</th>
                <th>Total Resi Kerja</th>
                <th>Total SKU Kerja</th>
                <th>Total Qty Kerja</th>
                <th>Jumlah Resi Salah</th>
                <th>Total Qty Salah</th>
                <th>Error Rate (%)</th>
                <?php foreach ($headers as $header): ?>
                    <th>Salah: <?= $header ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($recap_data)) : ?>
                <?php $no = 1; foreach ($recap_data as $row) : ?>
                    <?php 
                        $total_resi = (int)$row['total_resi'];
                        $total_kesalahan = (int)$row['total_kesalahan'];
                        $error_rate = $total_resi > 0 ? round(($total_kesalahan / $total_resi) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td align="center"><?= $no++ ?></td>
                        <td><?= $row['nama'] ?></td>
                        <td align="center"><?= $row['periode'] ?></td>
                        <td align="center"><?= $total_resi ?></td>
                        <td align="center"><?= $row['total_sku'] ?></td>
                        <td align="center"><?= $row['total_qty'] ?></td>
                        <td align="center" style="color: red; font-weight: bold;"><?= $total_kesalahan ?></td>
                        <td align="center"><?= $row['total_qty_salah'] ?></td>
                        <td align="center" style="font-weight: bold;"><?= $error_rate ?>%</td>
                        <td align="center"><?= $row['salah_1'] ?></td>
                        <td align="center"><?= $row['salah_2'] ?></td>
                        <td align="center"><?= $row['salah_3'] ?></td>
                        <td align="center"><?= $row['salah_4'] ?></td>
                        <td align="center"><?= $row['salah_5'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="<?= 9 + count($headers) ?>" align="center">Data tidak ditemukan untuk periode ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
