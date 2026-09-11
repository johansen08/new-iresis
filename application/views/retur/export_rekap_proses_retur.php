<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rekap Proses Retur</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .num { mso-number-format: "0"; text-align: right; }
    </style>
</head>
<body>
    <h2>Rekap Proses Retur</h2>
    <p>Periode Tanggal Retur: <?= htmlspecialchars($start_date) ?> s/d <?= htmlspecialchars($end_date) ?></p>
    <p>Kategori: <strong><?= htmlspecialchars(
        empty($bucket) ? 'Semua' : (isset($buckets[$bucket]) ? $buckets[$bucket]['label'] : $bucket)
    ) ?></strong></p>
    <?php
    $total_nilai = 0;
    $total_qty   = 0;
    foreach ($list_data as $row) {
        $total_nilai += (float) $row['nilai'];
        $total_qty   += (int) $row['qty_jubelio'];
    }
    ?>
    <p>
        <strong>Total: <?= number_format(count($list_data), 0, ',', '.') ?> resi ·
        <?= number_format($total_qty, 0, ',', '.') ?> pcs ·
        Rp <?= number_format($total_nilai, 0, ',', '.') ?></strong>
    </p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl Retur</th>
                <th>No. Resi</th>
                <th>No. Pesanan</th>
                <th>Marketplace</th>
                <th>Toko</th>
                <th>Kurir</th>
                <th>Qty Marketplace</th>
                <th>Qty Diterima</th>
                <th>Selisih Qty</th>
                <th>Nilai</th>
                <th>Umur (hari)</th>
                <th>Kategori</th>
                <th>Status Retur MP</th>
                <th>Status Buka</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($list_data as $row): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $row['tanggal_retur'] ? date('d/m/Y', strtotime($row['tanggal_retur'])) : '-' ?></td>
                <td><?= htmlspecialchars($row['no_resi']) ?></td>
                <td><?= htmlspecialchars($row['no_pesanan'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['marketplace'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['nama_toko'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['kurir'] ?: '-') ?></td>
                <td class="num"><?= (int) $row['qty_jubelio'] ?></td>
                <td class="num"><?= (int) $row['qty_buka'] ?></td>
                <td class="num"><?= (int) $row['qty_jubelio'] - (int) $row['qty_buka'] ?></td>
                <td class="num"><?= number_format((float) $row['nilai'], 0, ',', '.') ?></td>
                <td class="num"><?= (int) $row['umur_hari'] ?></td>
                <td><?= htmlspecialchars(isset($buckets[$row['bucket']]) ? $buckets[$row['bucket']]['label'] : $row['bucket']) ?></td>
                <td><?= htmlspecialchars($row['status_retur'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['status_list'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
