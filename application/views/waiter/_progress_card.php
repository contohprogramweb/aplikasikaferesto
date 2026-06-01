<div class="waiter-order-card in-progress" data-order-id="<?= $order['order_id'] ?>" data-table="<?= $order['table_number'] ?>">
    <div class="waiter-card-header">
        <div>
            <div class="waiter-table-code">Meja <?= htmlspecialchars($order['table_number']) ?></div>
            <div class="waiter-order-number">#<?= htmlspecialchars($order['order_number']) ?></div>
        </div>
        <span class="waiter-kitchen-status <?= $order['kitchen_status'] ?? 'confirmed' ?>">
            <?php
            $status_labels = [
                'pending' => 'Menunggu',
                'confirmed' => 'Diterima',
                'preparing' => 'Dimasak'
            ];
            echo $status_labels[$order['kitchen_status'] ?? 'confirmed'] ?? 'Diterima';
            ?>
        </span>
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
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="waiter-card-footer">
        <div style="text-align: center; color: #6c757d; font-size: 14px; padding: 10px;">
            ⏳ Sedang disiapkan oleh dapur
        </div>
    </div>
</div>
