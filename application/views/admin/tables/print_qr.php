<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QR Label - <?= esc_html($table['table_number']) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .label-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(4, 1fr);
            gap: 5mm;
            width: 190mm;
            height: 270mm;
        }
        
        .qr-label {
            width: 45mm;
            height: 45mm;
            border: 1px solid #ddd;
            border-radius: 3mm;
            padding: 3mm;
            text-align: center;
            background: #fff;
            page-break-inside: avoid;
        }
        
        .qr-image {
            width: 35mm;
            height: 35mm;
            margin-bottom: 2mm;
        }
        
        .table-number {
            font-size: 10pt;
            font-weight: bold;
            color: #333;
        }
        
        .table-name {
            font-size: 8pt;
            color: #666;
            margin-top: 1mm;
        }
        
        .scan-text {
            font-size: 6pt;
            color: #999;
            margin-top: 1mm;
        }
    </style>
</head>
<body>
    <div class="label-container">
        <?php for ($i = 1; $i <= 16; $i++): ?>
        <div class="qr-label">
            <img src="<?= $qr_image ?>" alt="QR Code" class="qr-image">
            <div class="table-number"><?= esc_html($table['table_number']) ?></div>
            <?php if (!empty($table['table_name'])): ?>
            <div class="table-name"><?= esc_html($table['table_name']) ?></div>
            <?php endif; ?>
            <div class="scan-text">Scan untuk menu</div>
        </div>
        <?php endfor; ?>
    </div>
</body>
</html>
