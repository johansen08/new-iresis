<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Label - Pergantian Barang</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #fff;
            color: #000;
        }
        .label-container {
            width: 100mm;
            height: 100mm; /* Sesuai dengan ukuran kertas thermal standar 10x10 atau disesuaikan */
            padding: 5mm;
            box-sizing: border-box;
            border: 1px solid #000;
            font-size: 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .header {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 2px dashed #000;
            padding-bottom: 5px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-row span.label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }
        .sku-box {
            margin-top: 15px;
            text-align: center;
            padding: 10px;
            border: 2px solid #000;
            font-size: 20px;
            font-weight: bold;
        }
        .qty-rak-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 18px;
            font-weight: bold;
        }
        .print-btn {
            margin: 20px;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .label-container {
                border: none;
                width: 100%;
                height: 100%;
                page-break-after: always;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    
    <div class="no-print" style="text-align: center;">
        <button class="print-btn" onclick="window.print();">Print Struk</button>
    </div>

    <div class="label-container">
        <div class="header">
            LABEL PERGANTIAN BARANG
        </div>

        <div class="info-row">
            <span class="label">Pesanan:</span> <?= htmlspecialchars($no_pesanan) ?>
        </div>
        <div class="info-row">
            <span class="label">No Resi:</span> <?= htmlspecialchars($noresi) ?>
        </div>

        <div class="sku-box">
            SKU Baru:<br>
            <span style="font-size: 26px;"><?= htmlspecialchars($sku) ?></span>
        </div>

        <div class="qty-rak-row">
            <div>QTY: <?= htmlspecialchars($qty) ?> x</div>
            <div>RAK: <?= htmlspecialchars($no_rak) ?></div>
        </div>
        
        <div style="margin-top: 20px; text-align: center; font-size: 11px;">
            Dicetak pada: <?= date('d M Y H:i') ?>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
