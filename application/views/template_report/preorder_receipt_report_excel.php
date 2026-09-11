<style>
    .header-blue { background-color: #1e3c72; color: #ffffff; font-weight: bold; text-align: center; }
    .header-light { background-color: #f1f4f9; font-weight: bold; }
    .text-center { text-align: center; }
    .title { font-size: 16px; font-weight: bold; }
</style>

<table border="0">
    <tr>
        <td colspan="7" class="title">LAPORAN RESI PREORDER</td>
    </tr>
    <tr>
        <td colspan="7">Periode: <?= $reportrange ?></td>
    </tr>
</table>

<br>

<table border="1">
    <thead>
        <tr class="header-light">
            <th colspan="3" style="text-align: right;">GRAND TOTAL</th>
            <th class="text-center" style="background-color: #e8f5e9; color: #2e7d32; font-weight: bold;">RESI: <?= number_format($total_resi) ?></th>
            <th class="text-center" style="background-color: #e1f5fe; color: #0288d1; font-weight: bold;">QTY: <?= number_format($grand_total) ?></th>
            <th colspan="2"></th>
        </tr>
        <tr class="header-blue">
            <th>No.</th>
            <th>Nomor Resi</th>
            <th>SKU</th>
            <th>Nama Barang</th>
            <th>Qty</th>
            <th>Batas Kirim</th>
            <th>Tanggal Pick</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($list_data)): ?>
            <?php $i = 1; foreach ($list_data as $row): ?>
            <tr>
                <td class="text-center"><?= $i++ ?>.</td>
                <td><?= $row['noresi'] ?></td>
                <td><?= $row['sku'] ?></td>
                <td><?= $row['nama_sku'] ?></td>
                <td class="text-center"><?= $row['qty'] ?></td>
                <?php 
                    $is_today = (!empty($row['tanggal_bataskirim']) && date('Y-m-d', strtotime($row['tanggal_bataskirim'])) == date('Y-m-d'));
                ?>
                <td class="text-center" <?= $is_today ? 'style="color: #ff0000; font-weight: bold;"' : '' ?>><?= $row['tanggal_bataskirim'] ?></td>
                <td class="text-center"><?= $row['tanggal_resiambilbarang'] ?></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center">Tidak ada data untuk periode ini.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
