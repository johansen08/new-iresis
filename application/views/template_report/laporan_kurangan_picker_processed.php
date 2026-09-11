<?php
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=Laporan_Kurangan_Picker_Processed.xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<table border="1">
    <thead>
        <tr>
            <th colspan="6" style="text-align: center; font-weight: bold;">Laporan Kurangan Picker (Processed)</th>
        </tr>
        <tr>
            <th colspan="6" style="text-align: center;">Rentang Waktu: <?= $reportrange ?></th>
        </tr>
        <tr>
            <th>SKU</th>
            <th>Jumlah Resi</th>
            <th>Total QTY Kurang</th>
            <th>Marketplace</th>
            <th>Tgl Cetak Terlama</th>
            <th>Batas Akhir Kirim Terlama</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($list_data)): ?>
            <?php foreach ($list_data as $row): ?>
                <tr>
                    <td><?= $row['sku'] ?></td>
                    <td><?= $row['jumlah_resi'] ?></td>
                    <td><?= $row['total_qty_kurang'] ?></td>
                    <td><?= $row['marketplace'] ?></td>
                    <td><?= !empty($row['tgl_cetak']) ? date('d/m/Y H:i', strtotime($row['tgl_cetak'])) : '-' ?></td>
                    <td><?= !empty($row['b_akhir_kirim']) ? date('d/m/Y', strtotime($row['b_akhir_kirim'])) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center;">Tidak ada data</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
