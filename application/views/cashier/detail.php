<div class="cashier-detail-page">
    <!-- Header -->
    <header class="detail-header">
        <div class="header-content">
            <button class="btn-back" onclick="window.location.href='<?= site_url('cashier') ?>'">&larr; Kembali</button>
            <h1>Detail Tagihan - Meja <?= htmlspecialchars($table['table_number']) ?></h1>
            <span class="status-badge status-<?= $table['status'] ?>"><?= ucfirst($table['status']) ?></span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="detail-content">
        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <p>Tidak ada pesanan aktif di meja ini</p>
            <a href="<?= site_url('cashier') ?>" class="btn btn-primary">Kembali ke Dashboard</a>
        </div>
        <?php else: ?>
        
        <!-- Orders List -->
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-card-header">
                    <div class="order-info">
                        <span class="order-number">Order #<?= htmlspecialchars($order['order_number']) ?></span>
                        <span class="order-time"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                    </div>
                    <span class="badge badge-<?= $order['status'] ?>"><?= $order['status'] ?></span>
                </div>
                
                <div class="order-items">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Item</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Subtotal</th>
                                <th>Catatan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $idx => $item): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><?= htmlspecialchars($item['menu_item_name'] ?? $item['name']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                                <td><?= $item['notes'] ? htmlspecialchars($item['notes']) : '-' ?></td>
                                <td><span class="badge badge-sm badge-<?= $item['status'] ?>"><?= $item['status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>Rp <?= number_format($order['totals']['subtotal'], 0, ',', '.') ?></span>
                    </div>
                    <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                    <div class="total-row discount">
                        <span>Diskon (<?= $order['discount_type'] === 'percentage' ? $order['discount_value'] . '%' : 'Fixed' ?>):</span>
                        <span>- Rp <?= number_format($order['discount_amount'], 0, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="total-row">
                        <span>Pajak (<?= round($order['totals']['tax_rate'] * 100) ?>%):</span>
                        <span>Rp <?= number_format($order['totals']['tax_amount'], 0, ',', '.') ?></span>
                    </div>
                    <div class="total-row">
                        <span>Service (<?= round($order['totals']['service_rate'] * 100) ?>%):</span>
                        <span>Rp <?= number_format($order['totals']['service_amount'], 0, ',', '.') ?></span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span>Rp <?= number_format($order['totals']['total'] - ($order['discount_amount'] ?? 0), 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Summary Section -->
        <div class="summary-section">
            <h3>Ringkasan Tagihan</h3>
            <div class="summary-grid">
                <?php 
                $grandSubtotal = 0;
                $grandDiscount = 0;
                $grandTax = 0;
                $grandService = 0;
                
                foreach ($orders as $order) {
                    $grandSubtotal += $order['totals']['subtotal'];
                    $grandDiscount += $order['discount_amount'] ?? 0;
                    $grandTax += $order['totals']['tax_amount'];
                    $grandService += $order['totals']['service_amount'];
                }
                
                $grandTotal = $grandSubtotal + $grandTax + $grandService - $grandDiscount;
                ?>
                <div class="summary-item">
                    <span class="label">Subtotal</span>
                    <span class="value">Rp <?= number_format($grandSubtotal, 0, ',', '.') ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Diskon</span>
                    <span class="value text-danger">- Rp <?= number_format($grandDiscount, 0, ',', '.') ?></span>
                </div>
                <div class="summary-item">
                    <span class="label">Pajak & Service</span>
                    <span class="value">Rp <?= number_format($grandTax + $grandService, 0, ',', '.') ?></span>
                </div>
                <div class="summary-item grand-total">
                    <span class="label">Grand Total</span>
                    <span class="value">Rp <?= number_format($grandTotal, 0, ',', '.') ?></span>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-secondary" onclick="alert('Fitur diskon akan segera hadir')">
                    <i class="icon-discount"></i> Terapkan Diskon
                </button>
                <button class="btn btn-primary btn-large" onclick="processPayment(<?= $orders[0]['id'] ?>)">
                    <i class="icon-payment"></i> Proses Pembayaran
                </button>
                <button class="btn btn-success" onclick="printReceipt(<?= $orders[0]['id'] ?>)">
                    <i class="icon-print"></i> Cetak Struk
                </button>
            </div>
        </div>
        
        <?php endif; ?>
    </main>
</div>

<script>
function processPayment(orderId) {
    // Redirect to payment modal or page
    window.location.href = '<?= site_url('cashier/detail') ?>/' + <?= $table['id'] ?> + '#payment';
}

function printReceipt(orderId) {
    window.open('<?= site_url('cashier/print_receipt') ?>/' + orderId, '_blank');
}
</script>

<style>
.cashier-detail-page {
    min-height: 100vh;
    background: #f5f5f5;
}

.detail-header {
    background: white;
    padding: 1rem 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-back {
    background: none;
    border: none;
    font-size: 1rem;
    cursor: pointer;
    color: #666;
    padding: 0.5rem;
}

.detail-header h1 {
    font-size: 1.5rem;
    margin: 0;
    flex: 1;
}

.detail-content {
    padding: 1.5rem;
    max-width: 1400px;
    margin: 0 auto;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.order-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.order-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.order-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.order-number {
    font-weight: bold;
    font-size: 1.1rem;
}

.order-time {
    font-size: 0.85rem;
    color: #666;
}

.order-items {
    padding: 1.5rem;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th,
.items-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.items-table th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 0.85rem;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-sm {
    padding: 0.2rem 0.5rem;
    font-size: 0.7rem;
}

.badge-pending { background: #fff3cd; color: #856404; }
.badge-confirmed { background: #d1ecf1; color: #0c5460; }
.badge-preparing { background: #cce5ff; color: #004085; }
.badge-ready { background: #d4edda; color: #155724; }

.order-totals {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #eee;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
}

.total-row.discount {
    color: #dc3545;
}

.total-row.grand-total {
    border-top: 2px solid #dee2e6;
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-size: 1.25rem;
    font-weight: bold;
}

.summary-section {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.summary-section h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: #333;
}

.summary-grid {
    display: grid;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.summary-item.grand-total {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    font-size: 1.25rem;
    font-weight: bold;
}

.text-danger {
    color: #dc3545;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1rem;
    min-height: 44px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-success {
    background: #28a745;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: white;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .header-content {
        flex-wrap: wrap;
    }
    
    .detail-header h1 {
        width: 100%;
        font-size: 1.25rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .items-table {
        font-size: 0.85rem;
    }
    
    .items-table th,
    .items-table td {
        padding: 0.5rem;
    }
}
</style>
