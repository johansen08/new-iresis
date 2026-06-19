<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Buka Retur</title>
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
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>Laporan Buka Retur</h2>
    <p>Periode: <?= $start_date ?> s/d <?= $end_date ?></p>
    <?php 
    $data_retur = $list_retur->result();
    $total_quantity = 0;
    $total_tagihan = 0;
    foreach ($data_retur as $row) {
        $total_quantity += $row->jumlah ?: 0;
        if (isset($row->harga) && $row->harga !== null) {
            $total_tagihan += (float) $row->harga * (int) ($row->jumlah ?: 0);
        }
    }
    ?>
    <p><strong>Total Quantity: <?= number_format($total_quantity, 0, ',', '.') ?></strong></p>
    <p><strong>Total Tagihan: <?= number_format($total_tagihan, 0, ',', '.') ?></strong></p>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Retur</th>
                <th>No. Resi</th>
                <th>Marketplace</th>
                <th>Nama Toko</th>
                <th>Kurir</th>
                <th>Tanggal Buka Retur</th>
                <th>Jam Buka Retur</th>
                <th>SKU</th>
                <th>Quantity</th>
                <th>Harga</th>
                <th>Total</th>
                <th>Status Detail</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($data_retur) > 0): ?>
                <?php $no = 1; ?>
                <?php foreach ($data_retur as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $row->no_pesanan ?: '-' ?></td>
                        <td><?= $row->noresi ?></td>
                        <td><?= $row->nama_marketplace ?: '-' ?></td>
                        <td><?= $row->nama_toko ?: '-' ?></td>
                        <td><?= $row->nama_kurir ?: '-' ?></td>
                        <td><?= date('Y-m-d', strtotime($row->tanggal_resiretur)) ?></td>
                        <td><?= date('H:i:s', strtotime($row->tanggal_resiretur)) ?></td>
                        <td><?= $row->sku ?: '-' ?></td>
                        <td><?= $row->jumlah ?: '0' ?></td>
                        <td><?= (isset($row->harga) && $row->harga !== null) ? number_format((float) $row->harga, 0, ',', '.') : '-' ?></td>
                        <td><?= (isset($row->harga) && $row->harga !== null) ? number_format((float) $row->harga * (int) ($row->jumlah ?: 0), 0, ',', '.') : '-' ?></td>
                        <td><?= $row->status_detail ?: '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="13" style="text-align: center;">Tidak ada data</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

