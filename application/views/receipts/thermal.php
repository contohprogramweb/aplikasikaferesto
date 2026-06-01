<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?= $order['order_number'] ?? '' ?></title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 5mm;
            width: 70mm;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        
        .restaurant-name {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }
        
        .restaurant-info {
            font-size: 10px;
            margin: 3px 0;
        }
        
        .order-info {
            margin: 10px 0;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        
        .order-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .items-table th,
        .items-table td {
            text-align: left;
            padding: 3px 0;
            border-bottom: 1px solid #eee;
        }
        
        .items-table th {
            font-weight: bold;
            font-size: 11px;
        }
        
        .item-name {
            width: 50%;
        }
        
        .item-qty {
            width: 15%;
            text-align: center;
        }
        
        .item-price {
            width: 20%;
            text-align: right;
        }
        
        .item-subtotal {
            width: 15%;
            text-align: right;
        }
        
        .item-note {
            font-size: 9px;
            color: #666;
            font-style: italic;
        }
        
        .summary-section {
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        
        .grand-total {
            font-weight: bold;
            font-size: 14px;
            margin-top: 5px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        
        .payment-info {
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="receipt-header">
        <h1 class="restaurant-name"><?= htmlspecialchars($restaurant_name) ?></h1>
        <?php if (!empty($restaurant_address)): ?>
        <div class="restaurant-info"><?= htmlspecialchars($restaurant_address) ?></div>
        <?php endif; ?>
        <?php if (!empty($restaurant_phone)): ?>
        <div class="restaurant-info">Telp: <?= htmlspecialchars($restaurant_phone) ?></div>
        <?php endif; ?>
    </div>
    
    <!-- Order Info -->
    <div class="order-info">
        <div class="order-row">
            <span>No. Pesanan:</span>
            <span><?= htmlspecialchars($order['order_number']) ?></span>
        </div>
        <div class="order-row">
            <span>Meja:</span>
            <span><?= htmlspecialchars($table['table_number'] ?? '-') ?></span>
        </div>
        <div class="order-row">
            <span>Tanggal:</span>
            <span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
        </div>
        <?php if (!empty($cashier)): ?>
        <div class="order-row">
            <span>Kasir:</span>
            <span><?= htmlspecialchars($cashier['username']) ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="item-name">Item</th>
                <th class="item-qty">Qty</th>
                <th class="item-price">Harga</th>
                <th class="item-subtotal">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($order['items'])): ?>
                <?php foreach ($order['items'] as $item): ?>
                <tr>
                    <td class="item-name">
                        <?= htmlspecialchars($item['menu_item_name'] ?? $item['name']) ?>
                        <?php if (!empty($item['notes'])): ?>
                        <div class="item-note">* <?= htmlspecialchars($item['notes']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="item-qty text-center"><?= (int)$item['quantity'] ?></td>
                    <td class="item-price text-right">Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                    <td class="item-subtotal text-right">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Summary -->
    <div class="summary-section">
        <div class="summary-row">
            <span>Subtotal</span>
            <span>Rp <?= number_format($totals['subtotal'], 0, ',', '.') ?></span>
        </div>
        <?php if ($discount_amount > 0): ?>
        <div class="summary-row">
            <span>Diskon</span>
            <span>- Rp <?= number_format($discount_amount, 0, ',', '.') ?></span>
        </div>
        <?php endif; ?>
        <div class="summary-row">
            <span>Pajak (<?= round($totals['tax_rate'] * 100) ?>%)</span>
            <span>Rp <?= number_format($totals['tax_amount'], 0, ',', '.') ?></span>
        </div>
        <div class="summary-row">
            <span>Service (<?= round($totals['service_rate'] * 100) ?>%)</span>
            <span>Rp <?= number_format($totals['service_amount'], 0, ',', '.') ?></span>
        </div>
        <div class="summary-row grand-total">
            <span>Grand Total</span>
            <span>Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
        </div>
    </div>
    
    <!-- Payment Info -->
    <div class="payment-info">
        <div class="order-row">
            <span>Metode Pembayaran:</span>
            <span><?= ucfirst(htmlspecialchars($order['payment_method'] ?? '-')) ?></span>
        </div>
        <?php if ($order['payment_method'] === 'cash' && !empty($order['amount_paid'])): ?>
        <div class="order-row">
            <span>Jumlah Dibayar:</span>
            <span>Rp <?= number_format($order['amount_paid'], 0, ',', '.') ?></span>
        </div>
        <?php if (!empty($order['change_amount'])): ?>
        <div class="order-row">
            <span>Kembalian:</span>
            <span>Rp <?= number_format($order['change_amount'], 0, ',', '.') ?></span>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($order['paid_at'])): ?>
        <div class="order-row">
            <span>Waktu Bayar:</span>
            <span><?= date('d/m/Y H:i', strtotime($order['paid_at'])) ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>Terima kasih atas kunjungan Anda!</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
        <p style="margin-top: 10px;">*** STRUK INI ADALAH BUKTI PEMBAYARAN YANG SAH ***</p>
    </div>
</body>
</html>
