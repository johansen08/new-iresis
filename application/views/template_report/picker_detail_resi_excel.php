<style>
    body {
        font-family: "Calibri", "Open Sans", sans-serif;
        font-size: 11px;
    }
    table {
        font-family: "Calibri", "Open Sans", sans-serif;
        font-size: 11px;
        border-collapse: collapse;
    }
    th, td {
        border: 1px solid #ccc;
        padding: 4px 8px;
    }
    .header {
        background-color: #1a5276;
        color: #ffffff;
        font-weight: bold;
        text-align: center;
    }
</style>

<h2 style="font-family: Calibri; color: #1a5276;">📋 DETAIL RESI PICKER</h2>
<table style="border:none; margin-bottom: 10px;">
    <tbody>
    <tr>
        <td style="border:none; font-weight:bold;">Nama Picker</td>
        <td style="border:none;">:</td>
        <td style="border:none;"><?= htmlspecialchars($nama_picker) ?></td>
    </tr>
    <tr>
        <td style="border:none; font-weight:bold;">Tanggal</td>
        <td style="border:none;">:</td>
        <td style="border:none;"><?= htmlspecialchars($tanggal) ?></td>
    </tr>
    <tr>
        <td style="border:none; font-weight:bold;">Kategori</td>
        <td style="border:none;">:</td>
        <td style="border:none; font-weight:bold;"><?= htmlspecialchars($category_label) ?></td>
    </tr>
    </tbody>
</table>

<br>

<table>
    <thead>
        <tr>
            <th class="header" style="width:50px;">No</th>
            <th class="header" style="width:180px;">No. Resi</th>
            <th class="header" style="width:150px;">Batas Kirim</th>
            <th class="header" style="width:120px;">Status Pesanan</th>
            <th class="header" style="width:350px;">Detail Barang (SKU & Qty)</th>
            <th class="header" style="width:80px;">Total Qty</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($list)): ?>
        <tr>
            <td colspan="6" align="center">Tidak ada data</td>
        </tr>
    <?php else: ?>
        <?php $no = 1; foreach ($list as $row): ?>
            <tr>
                <td align="center"><?= $no++ ?></td>
                <td>'<?= htmlspecialchars($row['noresi']) ?></td>
                <td><?= htmlspecialchars($row['tanggal_bataskirim']) ?></td>
                <td align="center"><?= htmlspecialchars($row['status_pesanan']) ?></td>
                <td><?= htmlspecialchars($row['detail_barang']) ?></td>
                <td align="center"><?= htmlspecialchars($row['total_qty']) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
