<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rekonsiliasi Retur Jubelio</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Laporan Rekonsiliasi Retur (iresis vs Jubelio)</h2>
    <p>Periode: <?= $start_date ?: '-' ?> s/d <?= $end_date ?: '-' ?></p>
    <?php
    $cocok = 0; $only_iresis = 0; $only_jubelio = 0;
    foreach ($list as $row) {
        if ($row['kondisi'] === 'Cocok') $cocok++;
        elseif ($row['kondisi'] === 'Hanya di iresis') $only_iresis++;
        else $only_jubelio++;
    }
    ?>
    <p>
        <strong>Cocok:</strong> <?= $cocok ?> &nbsp;|&nbsp;
        <strong>Hanya di iresis:</strong> <?= $only_iresis ?> &nbsp;|&nbsp;
        <strong>Hanya di Jubelio:</strong> <?= $only_jubelio ?>
    </p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Resi</th>
                <th>No. Pesanan</th>
                <th>Marketplace</th>
                <th>Nama Toko</th>
                <th>Kurir</th>
                <th>Kategori iresis</th>
                <th>Status Jubelio</th>
                <th>Qty iresis</th>
                <th>Qty Jubelio</th>
                <th>Tanggal</th>
                <th>Kondisi</th>
                <th>Terverifikasi</th>
                <th>Diverifikasi Oleh</th>
                <th>Waktu Verifikasi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($list)): ?>
                <?php $no = 1; ?>
                <?php foreach ($list as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['no_resi']) ?></td>
                        <td><?= htmlspecialchars($row['no_pesanan']) ?></td>
                        <td><?= htmlspecialchars($row['marketplace']) ?></td>
                        <td><?= htmlspecialchars($row['nama_toko']) ?></td>
                        <td><?= htmlspecialchars($row['kurir']) ?></td>
                        <td><?= htmlspecialchars($row['kategori_iresis']) ?></td>
                        <td><?= htmlspecialchars($row['status_jubelio']) ?></td>
                        <td><?= (int) $row['qty_iresis'] ?></td>
                        <td><?= (int) $row['qty_jubelio'] ?></td>
                        <td><?= htmlspecialchars($row['tanggal']) ?></td>
                        <td><?= htmlspecialchars($row['kondisi']) ?></td>
                        <td><?= !empty($row['verified']) ? 'Ya' : 'Belum' ?></td>
                        <td><?= htmlspecialchars($row['verified_by'] ?? '') ?></td>
                        <td><?= !empty($row['verified_at']) ? date('Y-m-d H:i', strtotime($row['verified_at'])) : '' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="15" style="text-align:center;">Tidak ada data</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
