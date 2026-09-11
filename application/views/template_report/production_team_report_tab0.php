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
    .no-border td {
        border: none;
    }
    .header-inti {
        background-color: #1a5276;
        color: #ffffff;
        font-weight: bold;
        text-align: center;
    }
    .header-perbantuan {
        background-color: #145a32;
        color: #ffffff;
        font-weight: bold;
        text-align: center;
    }
    .sub-header-inti {
        background-color: #2e86c1;
        color: #ffffff;
        font-weight: bold;
        text-align: center;
    }
    .sub-header-perbantuan {
        background-color: #27ae60;
        color: #ffffff;
        font-weight: bold;
        text-align: center;
    }
    .row-name-inti {
        background-color: #d6eaf8;
        font-weight: bold;
    }
    .row-name-perbantuan {
        background-color: #d5f5e3;
        font-weight: bold;
    }
    .subtotal-inti {
        background-color: #aed6f1;
        font-weight: bold;
    }
    .subtotal-perbantuan {
        background-color: #a9dfbf;
        font-weight: bold;
    }
    .summary-header {
        background-color: #2c3e50;
        color: #ffffff;
        font-weight: bold;
        text-align: center;
    }
    .summary-label {
        background-color: #ecf0f1;
        font-weight: bold;
    }
    .summary-inti-val {
        background-color: #d6eaf8;
        text-align: center;
        font-weight: bold;
    }
    .summary-perb-val {
        background-color: #d5f5e3;
        text-align: center;
        font-weight: bold;
    }
    .summary-total-val {
        background-color: #fef9e7;
        text-align: center;
        font-weight: bold;
        color: #c0392b;
    }
    .spacer-col {
        background-color: #f5f5f5;
        width: 20px;
        border: none;
    }
    .grand-total-row {
        background-color: #2c3e50;
        color: #ffffff;
        font-weight: bold;
    }
</style>

<h2 style="font-family: Calibri; color: #1a5276;">&#128203; LAPORAN PRODUKSI PICKER</h2>

<table style="border:none; margin-bottom: 10px;" class="no-border">
    <tbody>
    <tr>
        <td style="border:none; font-weight:bold; color:#555;">Periode</td>
        <td style="border:none;">:</td>
        <td style="border:none;"><?= explode(" - ", $reportrange)[0] ?></td>
    </tr>
    <tr>
        <td style="border:none; font-weight:bold; color:#555;">Sampai dengan</td>
        <td style="border:none;">:</td>
        <td style="border:none;"><?= explode(" - ", $reportrange)[1] ?></td>
    </tr>
    <tr>
        <td style="border:none; font-weight:bold; color:#555;">Total Paket</td>
        <td style="border:none;">:</td>
        <td style="border:none; font-weight:bold; color:#1a5276;"><?= $grand_total ?></td>
    </tr>
    </tbody>
</table>

<br>

<?php
// Prepare rows for side-by-side: collect all rows for each side
$rows_inti = [];
$rows_perb = [];

$i_inti = 1;
foreach ($list_inti as $pegawai => $items) {
    $sub_total = 0;
    $sub_sku_special = 0;
    $sub_resi_1_sku_sd_9 = 0;
    $sub_resi_2_9_sku_sd_9 = 0;
    $sub_resi_qty_banyak = 0;

    $name_row = ['type' => 'name', 'no' => $i_inti++, 'nama' => $pegawai, 'side' => 'inti'];
    $rows_inti[] = $name_row;
    foreach ($items as $item) {
        $sub_total += $item['total'];
        $sub_sku_special += $item['sku_special'] ?? 0;
        $sub_resi_1_sku_sd_9 += $item['resi_1_sku_sd_9'] ?? 0;
        $sub_resi_2_9_sku_sd_9 += $item['resi_2_9_sku_sd_9'] ?? 0;
        $sub_resi_qty_banyak += $item['resi_qty_banyak'] ?? 0;

        $rows_inti[] = [
            'type' => 'item',
            'tanggal' => empty($item['tanggal']) ? '' : date('d/m/Y', strtotime($item['tanggal'])),
            'jam_mulai' => empty($item['jam_mulai']) ? '' : date('H:i:s', strtotime($item['jam_mulai'])),
            'jam_selesai' => empty($item['jam_selesai']) ? '' : date('H:i:s', strtotime($item['jam_selesai'])),
            'total' => $item['total'],
            'sku_special' => $item['sku_special'] ?? 0,
            'resi_1_sku_sd_9' => $item['resi_1_sku_sd_9'] ?? 0,
            'resi_2_9_sku_sd_9' => $item['resi_2_9_sku_sd_9'] ?? 0,
            'resi_qty_banyak' => $item['resi_qty_banyak'] ?? 0,
            'status_performa' => $item['status_performa'] ?? ''
        ];
    }
    $rows_inti[] = [
        'type' => 'subtotal', 
        'sub_total' => $sub_total,
        'sub_sku_special' => $sub_sku_special,
        'sub_resi_1_sku_sd_9' => $sub_resi_1_sku_sd_9,
        'sub_resi_2_9_sku_sd_9' => $sub_resi_2_9_sku_sd_9,
        'sub_resi_qty_banyak' => $sub_resi_qty_banyak,
        'side' => 'inti'
    ];
}

$i_perb = 1;
foreach ($list_perbantuan as $pegawai => $items) {
    $sub_total = 0;
    $sub_sku_special = 0;
    $sub_resi_1_sku_sd_9 = 0;
    $sub_resi_2_9_sku_sd_9 = 0;
    $sub_resi_qty_banyak = 0;

    $rows_perb[] = ['type' => 'name', 'no' => $i_perb++, 'nama' => $pegawai, 'side' => 'perbantuan'];
    foreach ($items as $item) {
        $sub_total += $item['total'];
        $sub_sku_special += $item['sku_special'] ?? 0;
        $sub_resi_1_sku_sd_9 += $item['resi_1_sku_sd_9'] ?? 0;
        $sub_resi_2_9_sku_sd_9 += $item['resi_2_9_sku_sd_9'] ?? 0;
        $sub_resi_qty_banyak += $item['resi_qty_banyak'] ?? 0;

        $rows_perb[] = [
            'type' => 'item',
            'tanggal' => empty($item['tanggal']) ? '' : date('d/m/Y', strtotime($item['tanggal'])),
            'jam_mulai' => empty($item['jam_mulai']) ? '' : date('H:i:s', strtotime($item['jam_mulai'])),
            'jam_selesai' => empty($item['jam_selesai']) ? '' : date('H:i:s', strtotime($item['jam_selesai'])),
            'total' => $item['total'],
            'sku_special' => $item['sku_special'] ?? 0,
            'resi_1_sku_sd_9' => $item['resi_1_sku_sd_9'] ?? 0,
            'resi_2_9_sku_sd_9' => $item['resi_2_9_sku_sd_9'] ?? 0,
            'resi_qty_banyak' => $item['resi_qty_banyak'] ?? 0,
            'status_performa' => $item['status_performa'] ?? ''
        ];
    }
    $rows_perb[] = [
        'type' => 'subtotal', 
        'sub_total' => $sub_total,
        'sub_sku_special' => $sub_sku_special,
        'sub_resi_1_sku_sd_9' => $sub_resi_1_sku_sd_9,
        'sub_resi_2_9_sku_sd_9' => $sub_resi_2_9_sku_sd_9,
        'sub_resi_qty_banyak' => $sub_resi_qty_banyak,
        'side' => 'perbantuan'
    ];
}

$max_rows = max(count($rows_inti), count($rows_perb));
?>

<table>
    <thead>
        <tr>
            <th colspan="11" class="header-inti">&#11088; PICKER INTI</th>
            <td class="spacer-col" rowspan="<?= $max_rows + 2 ?>"></td>
            <th colspan="11" class="header-perbantuan">&#128100; PERBANTUAN / LAIN-LAIN</th>
        </tr>
        <tr>
            <th class="sub-header-inti">No</th>
            <th class="sub-header-inti">Nama</th>
            <th class="sub-header-inti">Tanggal</th>
            <th class="sub-header-inti">Jam Mulai</th>
            <th class="sub-header-inti">Jam Selesai</th>
            <th class="sub-header-inti">Jumlah</th>
            <th class="sub-header-inti">Resi Special</th>
            <th class="sub-header-inti">1 SKU & Qty S/D 9</th>
            <th class="sub-header-inti">2-9 SKU & Qty S/D 9</th>
            <th class="sub-header-inti">Qty Banyak (&gt;9)</th>
            <th class="sub-header-inti">Status</th>
            
            <th class="sub-header-perbantuan">No</th>
            <th class="sub-header-perbantuan">Nama</th>
            <th class="sub-header-perbantuan">Tanggal</th>
            <th class="sub-header-perbantuan">Jam Mulai</th>
            <th class="sub-header-perbantuan">Jam Selesai</th>
            <th class="sub-header-perbantuan">Jumlah</th>
            <th class="sub-header-perbantuan">Resi Special</th>
            <th class="sub-header-perbantuan">1 SKU & Qty S/D 9</th>
            <th class="sub-header-perbantuan">2-9 SKU & Qty S/D 9</th>
            <th class="sub-header-perbantuan">Qty Banyak (&gt;9)</th>
            <th class="sub-header-perbantuan">Status</th>
        </tr>
    </thead>
    <tbody>
    <?php for ($r = 0; $r < $max_rows; $r++): ?>
        <tr>
            <?php if (isset($rows_inti[$r])): ?>
                <?php $ri = $rows_inti[$r]; ?>
                <?php if ($ri['type'] === 'name'): ?>
                    <td class="row-name-inti"><?= $ri['no'] ?></td>
                    <td class="row-name-inti" colspan="9"><?= htmlspecialchars($ri['nama']) ?></td>
                    <td class="row-name-inti"></td>
                <?php elseif ($ri['type'] === 'item'): ?>
                    <td></td>
                    <td></td>
                    <td><?= $ri['tanggal'] ?></td>
                    <td><?= $ri['jam_mulai'] ?></td>
                    <td><?= $ri['jam_selesai'] ?></td>
                    <td align="right"><?= $ri['total'] ?></td>
                    <td align="right"><?= $ri['sku_special'] ?></td>
                    <td align="right"><?= $ri['resi_1_sku_sd_9'] ?></td>
                    <td align="right"><?= $ri['resi_2_9_sku_sd_9'] ?></td>
                    <td align="right"><?= $ri['resi_qty_banyak'] ?></td>
                    <td style="font-style:italic; color:#555;"><?= htmlspecialchars($ri['status_performa'] ?? '') ?></td>
                <?php elseif ($ri['type'] === 'subtotal'): ?>
                    <td class="subtotal-inti"></td>
                    <td class="subtotal-inti"></td>
                    <td class="subtotal-inti" colspan="3" align="right">&#128205; Total</td>
                    <td class="subtotal-inti" align="right"><?= $ri['sub_total'] ?></td>
                    <td class="subtotal-inti" align="right"><?= $ri['sub_sku_special'] ?></td>
                    <td class="subtotal-inti" align="right"><?= $ri['sub_resi_1_sku_sd_9'] ?></td>
                    <td class="subtotal-inti" align="right"><?= $ri['sub_resi_2_9_sku_sd_9'] ?></td>
                    <td class="subtotal-inti" align="right"><?= $ri['sub_resi_qty_banyak'] ?></td>
                    <td class="subtotal-inti"></td>
                <?php endif; ?>
            <?php else: ?>
                <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
            <?php endif; ?>

            <!-- spacer already handled by rowspan -->

            <?php if (isset($rows_perb[$r])): ?>
                <?php $rp = $rows_perb[$r]; ?>
                <?php if ($rp['type'] === 'name'): ?>
                    <td class="row-name-perbantuan"><?= $rp['no'] ?></td>
                    <td class="row-name-perbantuan" colspan="9"><?= htmlspecialchars($rp['nama']) ?></td>
                    <td class="row-name-perbantuan"></td>
                <?php elseif ($rp['type'] === 'item'): ?>
                    <td></td>
                    <td></td>
                    <td><?= $rp['tanggal'] ?></td>
                    <td><?= $rp['jam_mulai'] ?></td>
                    <td><?= $rp['jam_selesai'] ?></td>
                    <td align="right"><?= $rp['total'] ?></td>
                    <td align="right"><?= $rp['sku_special'] ?></td>
                    <td align="right"><?= $rp['resi_1_sku_sd_9'] ?></td>
                    <td align="right"><?= $rp['resi_2_9_sku_sd_9'] ?></td>
                    <td align="right"><?= $rp['resi_qty_banyak'] ?></td>
                    <td style="font-style:italic; color:#555;"><?= htmlspecialchars($rp['status_performa'] ?? '') ?></td>
                <?php elseif ($rp['type'] === 'subtotal'): ?>
                    <td class="subtotal-perbantuan"></td>
                    <td class="subtotal-perbantuan"></td>
                    <td class="subtotal-perbantuan" colspan="3" align="right">&#128205; Total</td>
                    <td class="subtotal-perbantuan" align="right"><?= $rp['sub_total'] ?></td>
                    <td class="subtotal-perbantuan" align="right"><?= $rp['sub_sku_special'] ?></td>
                    <td class="subtotal-perbantuan" align="right"><?= $rp['sub_resi_1_sku_sd_9'] ?></td>
                    <td class="subtotal-perbantuan" align="right"><?= $rp['sub_resi_2_9_sku_sd_9'] ?></td>
                    <td class="subtotal-perbantuan" align="right"><?= $rp['sub_resi_qty_banyak'] ?></td>
                    <td class="subtotal-perbantuan"></td>
                <?php endif; ?>
            <?php else: ?>
                <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
            <?php endif; ?>
        </tr>
    <?php endfor; ?>

    <!-- Grand Total Row -->
    <tr>
        <td class="grand-total-row" colspan="11" align="right">TOTAL KESELURUHAN (INTI): <?= number_format($total_paket_inti) ?> paket</td>
        <td class="spacer-col"></td>
        <td class="grand-total-row" colspan="11" align="right">TOTAL KESELURUHAN (PERBANTUAN): <?= number_format($total_paket_perbantuan) ?> paket</td>
    </tr>
    </tbody>
</table>

<br><br>

<!-- RANGKUMAN / SUMMARY TABLE -->
<table style="width: 500px;">
    <thead>
        <tr>
            <th class="summary-header" colspan="4">&#128202; RANGKUMAN PRODUKSI PICKER</th>
        </tr>
        <tr>
            <th class="summary-header" style="width:180px;">KETERANGAN</th>
            <th class="summary-header" style="width:100px;">INTI</th>
            <th class="summary-header" style="width:100px;">LAIN-LAIN</th>
            <th class="summary-header" style="width:100px;">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="summary-label">Jumlah Orang</td>
            <td class="summary-inti-val"><?= $total_populasi_inti ?></td>
            <td class="summary-perb-val"><?= $total_masuk_perbantuan ?></td>
            <td class="summary-total-val"><?= $total_populasi_inti + $total_masuk_perbantuan ?></td>
        </tr>
        <tr>
            <td class="summary-label">Yg Tdk Masuk (OFF)</td>
            <td class="summary-inti-val" style="color:#c0392b;"><?= max(0, $total_populasi_inti - $total_masuk_inti) ?></td>
            <td class="summary-perb-val">-</td>
            <td class="summary-total-val"><?= max(0, $total_populasi_inti - $total_masuk_inti) ?></td>
        </tr>
        <tr>
            <td class="summary-label">Masuk</td>
            <td class="summary-inti-val" style="color:#1a5276;"><?= $total_masuk_inti ?></td>
            <td class="summary-perb-val" style="color:#145a32;"><?= $total_masuk_perbantuan ?></td>
            <td class="summary-total-val"><?= $total_masuk_inti + $total_masuk_perbantuan ?></td>
        </tr>
        <tr>
            <td class="summary-label">Total Paket</td>
            <td class="summary-inti-val"><?= number_format($total_paket_inti) ?></td>
            <td class="summary-perb-val"><?= number_format($total_paket_perbantuan) ?></td>
            <td class="summary-total-val"><?= number_format($grand_total) ?></td>
        </tr>
        <tr>
            <td class="summary-label">Rata-rata Paket/Orang</td>
            <td class="summary-inti-val"><?= $total_masuk_inti > 0 ? number_format($total_paket_inti / $total_masuk_inti, 1) : 0 ?></td>
            <td class="summary-perb-val"><?= $total_masuk_perbantuan > 0 ? number_format($total_paket_perbantuan / $total_masuk_perbantuan, 1) : 0 ?></td>
            <td class="summary-total-val"><?= ($total_masuk_inti + $total_masuk_perbantuan) > 0 ? number_format($grand_total / ($total_masuk_inti + $total_masuk_perbantuan), 1) : 0 ?></td>
        </tr>
        <tr>
            <td class="summary-label">Jam Mulai (Paling Awal)</td>
            <td class="summary-inti-val"><?= $jam_mulai_inti ?></td>
            <td class="summary-perb-val"><?= $jam_mulai_perbantuan ?></td>
            <td class="summary-total-val"><?= ($jam_mulai_inti !== '-' && $jam_mulai_perbantuan !== '-') ? (strtotime($jam_mulai_inti) < strtotime($jam_mulai_perbantuan) ? $jam_mulai_inti : $jam_mulai_perbantuan) : ($jam_mulai_inti !== '-' ? $jam_mulai_inti : $jam_mulai_perbantuan) ?></td>
        </tr>
        <tr>
            <td class="summary-label">Jam Selesai (Paling Akhir)</td>
            <td class="summary-inti-val"><?= $jam_selesai_inti ?></td>
            <td class="summary-perb-val"><?= $jam_selesai_perbantuan ?></td>
            <td class="summary-total-val"><?= ($jam_selesai_inti !== '-' && $jam_selesai_perbantuan !== '-') ? (strtotime($jam_selesai_inti) > strtotime($jam_selesai_perbantuan) ? $jam_selesai_inti : $jam_selesai_perbantuan) : ($jam_selesai_inti !== '-' ? $jam_selesai_inti : $jam_selesai_perbantuan) ?></td>
        </tr>
    </tbody>
</table>