<!DOCTYPE html>
<html>
<head>
    <title>Print Label - <?= $receipt['noresi'] ?></title>
    <style>
        @page {
            size: 100mm 150mm;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 5mm;
            font-family: Arial, sans-serif;
            width: 90mm; /* Account for padding */
            height: 140mm;
        }
        .label-container {
            border: 1px solid #000;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 5px;
        }
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-align: center;
        }
        .marketplace {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .no-resi {
            font-size: 24pt;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            word-break: break-all;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 12pt;
        }
        .info-item {
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            font-size: 10pt;
            color: #555;
        }
        .kurir {
            font-size: 20pt;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 10px 0;
            text-align: center;
            margin-top: auto;
        }
        .footer {
            margin-top: 10px;
            font-size: 9pt;
            text-align: right;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print(); window.close();">
    <div class="label-container">
        <div class="header">
            <div class="marketplace"><?= $receipt['nama_marketplace'] ?></div>
            <div><?= $receipt['toko'] ?></div>
        </div>

        <div class="no-resi">
            <?= $receipt['noresi'] ?>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">PICK LIST</div>
                <div><?= $receipt['nomorpicklist'] ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">TANGGAL SCAN</div>
                <div><?= date('d M Y H:i', strtotime($receipt['tanggal_printresi'])) ?></div>
            </div>
        </div>

        <div class="kurir">
            <?= $receipt['nama_kurir'] ?>
        </div>

        <div class="footer">
            Printed by iResis System
        </div>
    </div>

    <div class="no-print" style="position: fixed; bottom: 10px; right: 10px;">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
