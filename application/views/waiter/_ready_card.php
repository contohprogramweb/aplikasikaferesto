<div class="waiter-order-card" data-order-id="<?= $order['order_id'] ?>" data-table="<?= $order['table_number'] ?>">
    <div class="waiter-card-header">
        <div>
            <div class="waiter-table-code">Meja <?= htmlspecialchars($order['table_number']) ?></div>
            <div class="waiter-order-number">#<?= htmlspecialchars($order['order_number']) ?></div>
        </div>
        <div class="waiter-timer" data-ready-time="<?= $order['items'][0]['created_at'] ?? date('Y-m-d H:i:s') ?>">
            00:00:00
        </div>
    </div>
    
    <div class="waiter-card-body">
        <?php foreach ($order['items'] as $item): ?>
        <div class="waiter-item-row">
            <div class="waiter-item-info">
                <div class="waiter-item-name">
                    <span class="waiter-item-qty"><?= (int)$item['quantity'] ?>x</span>
                    <?= htmlspecialchars($item['menu_item_name'] ?? 'Item deleted') ?>
                </div>
                <?php if (!empty($item['notes'])): ?>
                <div class="waiter-item-note" title="<?= htmlspecialchars($item['notes']) ?>">
                    📝 <?= htmlspecialchars(substr($item['notes'], 0, 50)) ?><?= strlen($item['notes']) > 50 ? '...' : '' ?>
                </div>
                <?php endif; ?>
            </div>
            <span class="waiter-item-badge">SIAP</span>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="waiter-card-footer">
        <button class="waiter-deliver-btn" onclick="WaiterDashboard.deliverOrder(<?= json_encode(array_column($order['items'], 'id')) ?>)">
            🚀 Antar ke Meja <?= htmlspecialchars($order['table_number']) ?>
        </button>
    </div>
</div>
