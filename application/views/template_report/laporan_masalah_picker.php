<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Masalah Picker</title>
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
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN MASALAH PICKER</h2>
        <p>Periode: <?= $reportrange ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Picker</th>
                <th>Packer / Pelapor</th>
                <th>No. Resi</th>
                <th>SKU</th>
                <th>SKU Salah</th>
                <th>QTY</th>
                <th>QTY Bermasalah</th>
                <th>Tipe Masalah</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($list_data)): ?>
                <?php $no = 1; ?>
                <?php foreach ($list_data as $row): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nama_picker'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['nama_packer'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['noresi'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['sku'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['sku_salah'] ?? '-') ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['qty'] ?? 0) ?></td>
                        <td class="text-center"><?= htmlspecialchars($row['qty_bermasalah'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($row['type_masalah'] ?? '-') ?></td>
                        <td class="text-center">
                            <?= !empty($row['created']) ? date('d/m/Y H:i:s', strtotime($row['created'])) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <br>
    <p>Total Data: <?= count($list_data) ?></p>
    <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
</body>
</html>

