<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title; ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1, h2, h3 { color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .summary-box { display: inline-block; width: 23%; margin: 1%; padding: 15px; background: #f5f5f5; border-radius: 5px; text-align: center; vertical-align: top; }
        .summary-box h3 { margin: 0 0 10px 0; font-size: 14px; color: #666; }
        .summary-box .value { font-size: 24px; font-weight: bold; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #3498db; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .filter-section { margin: 20px 0; padding: 15px; background: #ecf0f1; border-radius: 5px; }
        .chart-placeholder { text-align: center; padding: 20px; background: #f9f9f9; margin: 15px 0; border: 1px dashed #ccc; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #777; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PENJUALAN</h1>
        <p>Sistem Manajemen Kafe & Resto</p>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <strong>Periode:</strong> <?= date('d/m/Y', strtotime($start_date)); ?> s/d <?= date('d/m/Y', strtotime($end_date)); ?>
    </div>

    <!-- Summary Cards -->
    <div style="margin: 20px 0;">
        <div class="summary-box">
            <h3>Total Pesanan</h3>
            <div class="value"><?= $summary['total_orders'] ?? 0; ?></div>
        </div>
        <div class="summary-box">
            <h3>Meja Unik</h3>
            <div class="value"><?= $summary['unique_tables'] ?? 0; ?></div>
        </div>
        <div class="summary-box">
            <h3>Pesanan Selesai</h3>
            <div class="value"><?= $summary['completed_orders'] ?? 0; ?></div>
        </div>
        <div class="summary-box">
            <h3>Total Pendapatan</h3>
            <div class="value">Rp <?= number_format($summary['total_revenue'] ?? 0, 0, ',', '.'); ?></div>
        </div>
    </div>

    <!-- Sales by Category -->
    <h2>Penjualan per Kategori</h2>
    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th>Jumlah Terjual</th>
                <th>Total Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sales_by_category)): ?>
                <?php foreach ($sales_by_category as $cat): ?>
                <tr>
                    <td><?= esc_html($cat['category_name']); ?></td>
                    <td><?= $cat['total_qty']; ?> item</td>
                    <td>Rp <?= number_format($cat['total_sales'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align:center;">Tidak ada data</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Top Items -->
    <h2>Top 10 Item Terlaris</h2>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Jumlah Terjual</th>
                <th>Total Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($top_items)): ?>
                <?php foreach ($top_items as $item): ?>
                <tr>
                    <td><?= esc_html($item['item_name']); ?></td>
                    <td><?= $item['total_qty']; ?> item</td>
                    <td>Rp <?= number_format($item['total_sales'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align:center;">Tidak ada data</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Daily Revenue Trend -->
    <h2>Tren Pendapatan Harian</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($daily_revenue)): ?>
                <?php foreach ($daily_revenue as $day): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($day['date'])); ?></td>
                    <td>Rp <?= number_format($day['revenue'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="2" style="text-align:center;">Tidak ada data</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: <?= $generated_at; ?></p>
        <p>&copy; <?= date('Y'); ?> Sistem Manajemen Kafe & Resto</p>
    </div>
</body>
</html>
