<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Retur Shipped</title>
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
    <h2>Laporan Retur Shipped</h2>
    <p>Periode Buka Retur: <?= htmlspecialchars($start_date) ?> s/d <?= htmlspecialchars($end_date) ?></p>
    <?php 
    $total_quantity = 0;
    foreach ($list_data as $row) {
        $total_quantity += $row['jumlah'] ?: 0;
    }
    ?>
    <p><strong>Total Quantity: <?= number_format($total_quantity, 0, ',', '.') ?></strong></p>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>No. Resi</th>
                <th>No. Pesanan</th>
                <th>Nama Toko</th>
                <th>SKU</th>
                <th>Qty</th>
                <th>Status Paket</th>
                <th>Status Jubelio</th>
                <th>Status Berubah</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($list_data as $row): 
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= !empty($row['tanggal_buka']) ? date('Y-m-d H:i:s', strtotime($row['tanggal_buka'])) : '-' ?></td>
                    <td><?= htmlspecialchars($row['noresi']) ?></td>
                    <td><?= htmlspecialchars($row['no_pesanan'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['nama_toko'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['sku']) ?></td>
                    <td><?= htmlspecialchars($row['jumlah']) ?></td>
                    <td><?= htmlspecialchars($row['status_pesanan']) ?></td>
                    <td><?= !empty($row['status_jubelio']) ? htmlspecialchars($row['status_jubelio']) : '-' ?></td>
                    <td><?= htmlspecialchars($row['status_berubah']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
