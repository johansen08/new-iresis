<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= isset($is_komplain) && $is_komplain ? 'Laporan Retur Komplain' : 'Laporan Retur Lengkap' ?></title>
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
    <h2><?= isset($is_komplain) && $is_komplain ? 'Laporan Retur Komplain' : 'Laporan Retur Lengkap' ?></h2>
    <p>Periode: <?= htmlspecialchars($start_date) ?> s/d <?= htmlspecialchars($end_date) ?> (Berdasarkan: <?= strtoupper($date_type) ?>)</p>
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
                <th>No. Pesanan</th>
                <th>No. Resi</th>
                <th>SKU</th>
                <th>Quantity</th>
                <th>No. Rak</th>
                <th>Marketplace</th>
                <th>Nama Toko</th>
                <th>Harga</th>
                <th>Total</th>
                <th>Status Dibuka</th>
                <th>Status Retur</th>
                <th>Tanggal Terima</th>
                <th>Jam Terima</th>
                <th>Tanggal Buka</th>
                <th>Jam Buka</th>
                <th>Tanggal ACC</th>
                <th>Jam ACC</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($data_retur) > 0): ?>
                <?php $no = 1; ?>
                <?php foreach ($data_retur as $row): ?>
                    <?php 
                    $harga = isset($row->harga) && $row->harga !== null ? (float) $row->harga : null;
                    $qty = (int) ($row->jumlah ?: 0);
                    $total_row = $harga !== null ? $harga * $qty : null;
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row->no_pesanan ?: '-') ?></td>
                        <td><?= htmlspecialchars($row->noresi) ?></td>
                        <td><?= htmlspecialchars($row->sku ?: '-') ?></td>
                        <td><?= $qty ?></td>
                        <td><?= htmlspecialchars($row->no_rak ?: '-') ?></td>
                        <td><?= htmlspecialchars($row->nama_marketplace ?: '-') ?></td>
                        <td><?= htmlspecialchars($row->nama_toko ?: '-') ?></td>
                        <td><?= $harga !== null ? number_format($harga, 0, ',', '.') : '-' ?></td>
                        <td><?= $total_row !== null ? number_format($total_row, 0, ',', '.') : '-' ?></td>
                        <td><?= htmlspecialchars($row->status_detail_buka ?: '-') ?></td>
                        <td><?= htmlspecialchars($row->status_retur ?: '-') ?></td>
                        <td><?= !empty($row->tanggal_terima) ? date('Y-m-d', strtotime($row->tanggal_terima)) : '-' ?></td>
                        <td><?= !empty($row->tanggal_terima) ? date('H:i:s', strtotime($row->tanggal_terima)) : '-' ?></td>
                        <td><?= !empty($row->tanggal_buka) ? date('Y-m-d', strtotime($row->tanggal_buka)) : '-' ?></td>
                        <td><?= !empty($row->tanggal_buka) ? date('H:i:s', strtotime($row->tanggal_buka)) : '-' ?></td>
                        <td><?= !empty($row->tanggal_acc) ? date('Y-m-d', strtotime($row->tanggal_acc)) : '-' ?></td>
                        <td><?= !empty($row->tanggal_acc) ? date('H:i:s', strtotime($row->tanggal_acc)) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="18" style="text-align: center;">Tidak ada data</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
