<!DOCTYPE html>
<html>
<head>
    <title>Cetak Batch Retur Display - <?= $batch['kode_batch'] ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 12px;
            background-color: #fff;
        }
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 20px;
            background: #fff;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
            padding: 4px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 5px 0;
        }
        .batch-code {
            font-size: 16px;
            font-weight: bold;
            color: #000;
            border: 2px solid #000;
            padding: 5px 10px;
            display: inline-block;
            margin-top: 5px;
        }
        .metadata-label {
            font-weight: bold;
            width: 120px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            margin-bottom: 30px;
        }
        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .signature-section {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }
        .signature-section td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            height: 100px;
        }
        .signature-line {
            width: 200px;
            border-bottom: 1px solid #000;
            margin: 0 auto 5px auto;
        }
        @media print {
            body {
                padding: 0;
                background-color: #fff;
            }
            .print-container {
                border: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="print-container">
        
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    <h1 class="title">SLIP SERAH TERIMA<br>RETUR KE DISPLAY</h1>
                    <div class="batch-code"><?= $batch['kode_batch'] ?></div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <table style="float: right; text-align: left;">
                        <tr>
                            <td class="metadata-label">Tanggal Kirim</td>
                            <td>: <?= date('d/m/Y H:i', strtotime($batch['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <td class="metadata-label">Dikirim Oleh</td>
                            <td>: <?= $batch['creator_name'] ?></td>
                        </tr>
                        <tr>
                            <td class="metadata-label">Status</td>
                            <td>: <strong><?= $batch['status'] ?></strong></td>
                        </tr>
                        <tr>
                            <td class="metadata-label">Tanggal Terima</td>
                            <td>: <?= $batch['received_at'] ? date('d/m/Y H:i', strtotime($batch['received_at'])) : '-' ?></td>
                        </tr>
                        <tr>
                            <td class="metadata-label">Diterima Oleh</td>
                            <td>: <?= $batch['receiver_name'] ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>SKU</th>
                    <th>Nama Barang</th>
                    <th style="width: 50px;" class="text-center">Qty</th>
                    <th style="width: 80px;" class="text-center">No. Rak</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php $no = 1; $total_qty = 0; ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= $item['sku'] ?></td>
                            <td><?= $item['nama_barang'] ?: '-' ?></td>
                            <td class="text-center"><?= $item['qty'] ?></td>
                            <td class="text-center"><?= $item['no_rak'] ?: '-' ?></td>
                        </tr>
                        <?php $total_qty += $item['qty']; ?>
                    <?php endforeach; ?>
                    <tr style="font-weight: bold; background-color: #f9f9f9;">
                        <td colspan="3" style="text-align: right;">TOTAL:</td>
                        <td class="text-center"><?= $total_qty ?></td>
                        <td></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 20px;">Tidak ada data barang.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <table class="signature-section">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    <div>Pengirim (Tim Retur)</div>
                    <div style="font-size: 10px; color: #666; margin-top: 3px;"><?= $batch['creator_name'] ?></div>
                </td>
                <td>
                    <div class="signature-line"></div>
                    <div>Penerima (Tim Display)</div>
                    <div style="font-size: 10px; color: #666; margin-top: 3px;"><?= $batch['receiver_name'] ?></div>
                </td>
            </tr>
        </table>

        <div style="margin-top: 40px; text-align: center;" class="no-print">
            <button onclick="window.print();" style="padding: 6px 15px; font-weight: bold; cursor: pointer;">Cetak Ulang</button>
            <button onclick="window.close();" style="padding: 6px 15px; margin-left: 10px; cursor: pointer;">Tutup Halaman</button>
        </div>

    </div>

</body>
</html>
