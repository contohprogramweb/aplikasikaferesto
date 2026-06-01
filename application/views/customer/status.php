<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= $restaurant_name ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/customer.css') ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding-bottom: 80px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .table-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            margin-top: 10px;
            font-weight: 600;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-state img {
            width: 150px;
            height: 150px;
            margin-bottom: 20px;
            opacity: 0.6;
        }
        
        .empty-state h2 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        /* Order Groups */
        .order-group {
            background: white;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .order-header {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        
        .order-header h3 {
            font-size: 1.1rem;
            color: #333;
        }
        
        .order-header .order-number {
            color: #667eea;
            font-weight: 600;
        }
        
        .order-header .toggle-icon {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .order-header.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        
        .order-body {
            padding: 20px;
        }
        
        .order-body.collapsed {
            display: none;
        }
        
        /* Timeline/Stepper */
        .timeline {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            position: relative;
            padding: 0 10px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 30px;
            right: 30px;
            height: 3px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .timeline-step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }
        
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            transition: all 0.3s ease;
        }
        
        .timeline-icon i {
            font-size: 1.2rem;
            color: #ccc;
        }
        
        .timeline-label {
            font-size: 0.75rem;
            color: #999;
            font-weight: 500;
        }
        
        /* Step States */
        .timeline-step.active .timeline-icon {
            border-color: #667eea;
            background: #667eea;
            animation: pulse 1s infinite;
        }
        
        .timeline-step.active .timeline-icon i {
            color: white;
        }
        
        .timeline-step.active .timeline-label {
            color: #667eea;
            font-weight: 600;
        }
        
        .timeline-step.completed .timeline-icon {
            border-color: #28a745;
            background: #28a745;
        }
        
        .timeline-step.completed .timeline-icon i {
            color: white;
        }
        
        .timeline-step.completed .timeline-label {
            color: #28a745;
        }
        
        @keyframes pulse {
            0% { transform: scale(1.0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1.0); }
        }
        
        /* Order Items */
        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
            transition: opacity 0.3s ease;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item.fade-in {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            margin-right: 15px;
            background: #f0f0f0;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .item-meta {
            font-size: 0.85rem;
            color: #666;
        }
        
        .item-qty {
            background: #f0f0f0;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .item-status {
            margin-left: 15px;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-diterima {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-dimasak {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .status-siap {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .status-terkirim,
        .status-delivered,
        .status-completed {
            background: #e0e0e0;
            color: #616161;
        }
        
        .status-batal,
        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }
        
        /* Cancelled Item */
        .order-item.cancelled {
            opacity: 0.6;
        }
        
        .order-item.cancelled .item-name {
            text-decoration: line-through;
        }
        
        .cancel-reason {
            display: block;
            font-size: 0.75rem;
            color: #c62828;
            margin-top: 5px;
        }
        
        /* Floating Bill Button */
        .floating-bill-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .floating-bill-btn:hover:not(:disabled) {
            transform: scale(1.1);
        }
        
        .floating-bill-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }
        
        .floating-bill-btn.hidden {
            display: none;
        }
        
        .floating-bill-btn .btn-text {
            font-size: 0.7rem;
            position: absolute;
            bottom: -20px;
            white-space: nowrap;
            background: rgba(0,0,0,0.8);
            padding: 3px 8px;
            border-radius: 5px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .floating-bill-btn:hover .btn-text {
            opacity: 1;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        
        .modal-overlay.active .modal {
            transform: translateY(0);
        }
        
        .modal h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .bill-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .bill-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .bill-row.total {
            border-top: 2px solid #dee2e6;
            padding-top: 10px;
            margin-top: 10px;
            margin-bottom: 0;
            font-weight: 700;
            font-size: 1.1rem;
            color: #667eea;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-modal {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel {
            background: #f0f0f0;
            color: #666;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-confirm:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        /* Offline Banner */
        .offline-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            text-align: center;
            font-weight: 600;
            z-index: 3000;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        }
        
        .offline-banner.active {
            transform: translateY(0);
        }
        
        .retry-countdown {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        /* Back button */
        .back-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Offline Banner -->
    <div id="offline-banner" class="offline-banner">
        <div>Koneksi terputus. Mencoba menyambung ulang...</div>
        <div class="retry-countdown" id="retry-countdown"></div>
    </div>

    <!-- Header -->
    <div class="header">
        <button class="back-btn" onclick="history.back()">←</button>
        <h1>Status Pesanan</h1>
        <p>Pantau pesanan Anda secara real-time</p>
        <div class="table-badge">Meja <?= htmlspecialchars($table['table_number']) ?></div>
    </div>

    <div class="container">
        <!-- Empty State -->
        <?php if (empty($order_items)): ?>
        <div class="empty-state">
            <svg width="150" height="150" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
            </svg>
            <h2>Belum ada pesanan</h2>
            <p>Anda belum memiliki pesanan aktif untuk meja ini</p>
            <a href="<?= site_url('customer/menu?token=' . urlencode($token)) ?>" class="btn-primary">Pesan Sekarang</a>
        </div>
        <?php else: ?>
        
        <!-- Order Groups -->
        <?php 
        // Group items by order number
        $orders_grouped = [];
        foreach ($order_items as $item) {
            $order_num = $item['order_number'] ?? 'ORD-' . $item['order_id'];
            if (!isset($orders_grouped[$order_num])) {
                $orders_grouped[$order_num] = [
                    'order_id' => $item['order_id'],
                    'order_number' => $order_num,
                    'items' => []
                ];
            }
            $orders_grouped[$order_num]['items'][] = $item;
        }
        
        foreach ($orders_grouped as $order_num => $order_data): 
        ?>
        <div class="order-group" data-order-id="<?= $order_data['order_id'] ?>">
            <div class="order-header" onclick="toggleOrder(this)">
                <h3>
                    <span class="order-number">#<?= htmlspecialchars($order_num) ?></span>
                    <span id="order-status-<?= $order_data['order_id'] ?>">
                        <?php
                        $status_map = [
                            'pending' => 'Diterima',
                            'confirmed' => 'Dimasak',
                            'preparing' => 'Dimasak',
                            'ready' => 'Siap',
                            'delivered' => 'Terkirim',
                            'completed' => 'Selesai',
                            'menunggu_bayar' => 'Menunggu Bayar',
                            'cancelled' => 'Dibatalkan'
                        ];
                        $order_status = $order_status_map[$order_data['order_id']] ?? 'pending';
                        echo htmlspecialchars($status_map[$order_status] ?? $order_status);
                        ?>
                    </span>
                </h3>
                <span class="toggle-icon">▼</span>
            </div>
            
            <div class="order-body">
                <!-- Timeline -->
                <div class="timeline">
                    <div class="timeline-step" data-step="diterima">
                        <div class="timeline-icon"><i>📋</i></div>
                        <div class="timeline-label">Diterima</div>
                    </div>
                    <div class="timeline-step" data-step="dimasak">
                        <div class="timeline-icon"><i>👨‍🍳</i></div>
                        <div class="timeline-label">Dimasak</div>
                    </div>
                    <div class="timeline-step" data-step="siap">
                        <div class="timeline-icon"><i>✅</i></div>
                        <div class="timeline-label">Siap</div>
                    </div>
                    <div class="timeline-step" data-step="terkirim">
                        <div class="timeline-icon"><i>🚗</i></div>
                        <div class="timeline-label">Terkirim</div>
                    </div>
                </div>
                
                <!-- Items -->
                <div class="items-container" id="items-<?= $order_data['order_id'] ?>">
                    <?php foreach ($order_data['items'] as $item): ?>
                    <div class="order-item <?= $item['status'] === 'cancelled' ? 'cancelled' : '' ?>" 
                         data-item-id="<?= $item['id'] ?>"
                         data-status="<?= htmlspecialchars($item['status']) ?>">
                        <img src="<?= !empty($item['menu_item_image']) ? base_url('uploads/' . $item['menu_item_image']) : base_url('assets/images/placeholder.png') ?>" 
                             alt="<?= htmlspecialchars($item['menu_item_name']) ?>" 
                             class="item-image"
                             onerror="this.src='<?= base_url('assets/images/placeholder.png') ?>'">
                        
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($item['menu_item_name']) ?></div>
                            <div class="item-meta">
                                Rp <?= number_format($item['price'], 0, ',', '.') ?>
                                <span class="item-qty">x<?= $item['quantity'] ?></span>
                            </div>
                            <?php if ($item['status'] === 'cancelled'): ?>
                            <span class="cancel-reason">
                                🚫 Dibatalkan: <?= htmlspecialchars($item['cancel_reason'] ?? 'Tanpa alasan') ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-status">
                            <?php
                            $item_status_badge = '';
                            $item_status_label = '';
                            
                            switch ($item['status']) {
                                case 'pending':
                                    $item_status_badge = 'status-diterima';
                                    $item_status_label = 'Diterima';
                                    break;
                                case 'confirmed':
                                case 'preparing':
                                    $item_status_badge = 'status-dimasak';
                                    $item_status_label = 'Dimasak';
                                    break;
                                case 'ready':
                                    $item_status_badge = 'status-siap';
                                    $item_status_label = 'Siap';
                                    break;
                                case 'delivered':
                                case 'completed':
                                    $item_status_badge = 'status-terkirim';
                                    $item_status_label = 'Terkirim';
                                    break;
                                case 'cancelled':
                                    $item_status_badge = 'status-batal';
                                    $item_status_label = 'Batal';
                                    break;
                                default:
                                    $item_status_badge = 'status-diterima';
                                    $item_status_label = $item['status'];
                            }
                            ?>
                            <span class="status-badge <?= $item_status_badge ?>"><?= $item_status_label ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>

    <!-- Floating Bill Button -->
    <?php if ($can_request_bill): ?>
    <button id="floating-bill-btn" class="floating-bill-btn" onclick="openBillModal()">
        💰
        <span class="btn-text">Minta Bill</span>
    </button>
    <?php endif; ?>

    <!-- Bill Confirmation Modal -->
    <div id="bill-modal" class="modal-overlay">
        <div class="modal">
            <h3>Konfirmasi Minta Tagihan</h3>
            <p>Apakah Anda yakin ingin meminta tagihan?</p>
            
            <div class="bill-summary" id="bill-summary">
                <div class="bill-row">
                    <span>Subtotal</span>
                    <span id="subtotal-display">Rp 0</span>
                </div>
                <div class="bill-row">
                    <span>Pajak (10%)</span>
                    <span id="tax-display">Rp 0</span>
                </div>
                <div class="bill-row">
                    <span>Service (5%)</span>
                    <span id="service-display">Rp 0</span>
                </div>
                <div class="bill-row total">
                    <span>Total</span>
                    <span id="total-display">Rp 0</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button class="btn-modal btn-cancel" onclick="closeBillModal()">Batal</button>
                <button id="btn-confirm-bill" class="btn-modal btn-confirm" onclick="requestBill()">
                    Ya, Minta Tagihan
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script>
        var CustomerStatus = (function() {
            'use strict';
            
            var config = {
                token: '<?= htmlspecialchars($token) ?>',
                orderStatusUrl: '<?= site_url('customer/order_status') ?>',
                requestBillUrl: '<?= site_url('customer/request_bill') ?>',
                lastTimestamp: '<?= $current_timestamp ?>',
                lastId: 0,
                pollingInterval: 5000,
                offlineRetryMax: 3,
                offlineRetryCount: 0
            };
            
            var pollingTimer = null;
            var offlineTimer = null;
            
            function init() {
                startPolling();
                monitorConnection();
                updateTimeline();
            }
            
            function startPolling() {
                pollingTimer = setInterval(function() {
                    pollOrderStatus();
                }, config.pollingInterval);
            }
            
            function pollOrderStatus() {
                if (!navigator.onLine) {
                    showOfflineBanner();
                    return;
                }
                
                $.ajax({
                    url: config.orderStatusUrl,
                    type: 'GET',
                    data: {
                        token: config.token,
                        last_timestamp: config.lastTimestamp,
                        last_id: config.lastId
                    },
                    dataType: 'json',
                    success: function(response) {
                        hideOfflineBanner();
                        config.offlineRetryCount = 0;
                        
                        if (response.status === 'success' && response.data.has_changes) {
                            updateItems(response.data.items);
                            config.lastTimestamp = response.data.current_timestamp;
                            
                            // Update timeline
                            updateTimeline();
                        } else if (response.status === 'success') {
                            // No changes, still update timestamp
                            config.lastTimestamp = response.data.current_timestamp;
                        }
                    },
                    error: function(xhr) {
                        config.offlineRetryCount++;
                        
                        if (config.offlineRetryCount >= config.offlineRetryMax) {
                            showOfflineBanner(true);
                        } else {
                            showOfflineBanner(false);
                        }
                    }
                });
            }
            
            function updateItems(newItems) {
                newItems.forEach(function(item) {
                    var $existingItem = $('.order-item[data-item-id="' + item.id + '"]');
                    
                    if ($existingItem.length > 0) {
                        // Update existing item with fade effect
                        $existingItem.addClass('fade-in');
                        $existingItem.attr('data-status', item.status);
                        
                        // Update status badge
                        var statusBadge = $existingItem.find('.status-badge');
                        var newBadgeClass = getStatusBadgeClass(item.status);
                        var newStatusLabel = getStatusLabel(item.status);
                        
                        statusBadge.removeClass().addClass('status-badge ' + newBadgeClass);
                        statusBadge.text(newStatusLabel);
                        
                        // Scroll to changed item
                        scrollToElement($existingItem);
                        
                        setTimeout(function() {
                            $existingItem.removeClass('fade-in');
                        }, 300);
                    } else {
                        // New item - append to list
                        var $itemsContainer = $('#items-' + item.order_id);
                        if ($itemsContainer.length > 0) {
                            var itemHtml = createItemHtml(item);
                            $itemsContainer.append(itemHtml);
                            var $newItem = $itemsContainer.children().last();
                            $newItem.addClass('fade-in');
                            scrollToElement($newItem);
                        }
                    }
                });
            }
            
            function createItemHtml(item) {
                var isCancelled = item.status === 'cancelled';
                var badgeClass = getStatusBadgeClass(item.status);
                var statusLabel = getStatusLabel(item.status);
                var imageUrl = item.menu_item_image ? '<?= base_url('uploads/') ?>' + item.menu_item_image : '<?= base_url('assets/images/placeholder.png') ?>';
                
                return '<div class="order-item ' + (isCancelled ? 'cancelled' : '') + '" ' +
                       'data-item-id="' + item.id + '" ' +
                       'data-status="' + item.status + '">' +
                    '<img src="' + imageUrl + '" alt="' + escapeHtml(item.menu_item_name) + '" class="item-image">' +
                    '<div class="item-details">' +
                        '<div class="item-name">' + escapeHtml(item.menu_item_name) + '</div>' +
                        '<div class="item-meta">Rp ' + formatNumber(item.price) + 
                            '<span class="item-qty">x' + item.quantity + '</span></div>' +
                        (isCancelled ? '<span class="cancel-reason">🚫 Dibatalkan: ' + 
                            escapeHtml(item.cancel_reason || 'Tanpa alasan') + '</span>' : '') +
                    '</div>' +
                    '<div class="item-status">' +
                        '<span class="status-badge ' + badgeClass + '">' + statusLabel + '</span>' +
                    '</div>' +
                '</div>';
            }
            
            function getStatusBadgeClass(status) {
                var classes = {
                    'pending': 'status-diterima',
                    'confirmed': 'status-dimasak',
                    'preparing': 'status-dimasak',
                    'ready': 'status-siap',
                    'delivered': 'status-terkirim',
                    'completed': 'status-terkirim',
                    'cancelled': 'status-batal'
                };
                return classes[status] || 'status-diterima';
            }
            
            function getStatusLabel(status) {
                var labels = {
                    'pending': 'Diterima',
                    'confirmed': 'Dimasak',
                    'preparing': 'Dimasak',
                    'ready': 'Siap',
                    'delivered': 'Terkirim',
                    'completed': 'Terkirim',
                    'cancelled': 'Batal'
                };
                return labels[status] || status;
            }
            
            function updateTimeline() {
                $('.order-group').each(function() {
                    var orderId = $(this).data('order-id');
                    var $items = $('#items-' + orderId + ' .order-item');
                    
                    var hasPending = false, hasPreparing = false, hasReady = false, hasDelivered = false;
                    
                    $items.each(function() {
                        var status = $(this).data('status');
                        if (status === 'pending') hasPending = true;
                        if (status === 'confirmed' || status === 'preparing') hasPreparing = true;
                        if (status === 'ready') hasReady = true;
                        if (status === 'delivered' || status === 'completed') hasDelivered = true;
                    });
                    
                    // Update timeline steps
                    var $steps = $(this).find('.timeline-step');
                    
                    $steps.filter('[data-step="diterima"]').toggleClass('completed', true);
                    $steps.filter('[data-step="dimasak"]').toggleClass('active', hasPreparing && !hasReady && !hasDelivered)
                                                          .toggleClass('completed', hasReady || hasDelivered || (hasPreparing && !hasPending));
                    $steps.filter('[data-step="siap"]').toggleClass('active', hasReady && !hasDelivered)
                                                     .toggleClass('completed', hasDelivered);
                    $steps.filter('[data-step="terkirim"]').toggleClass('active', hasDelivered)
                                                           .toggleClass('completed', hasDelivered);
                });
            }
            
            function scrollToElement($element) {
                $('html, body').animate({
                    scrollTop: $element.offset().top - 100
                }, 300);
            }
            
            function toggleOrder(header) {
                $(header).toggleClass('collapsed');
                $(header).next('.order-body').toggleClass('collapsed');
            }
            
            function openBillModal() {
                $('#bill-modal').addClass('active');
                loadBillSummary();
            }
            
            function closeBillModal() {
                $('#bill-modal').removeClass('active');
            }
            
            function loadBillSummary() {
                // Load bill summary via AJAX
                $.ajax({
                    url: config.requestBillUrl,
                    type: 'POST',
                    data: {
                        token: config.token,
                        get_summary: true
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success' && response.data) {
                            updateBillDisplay(response.data);
                        }
                    }
                });
            }
            
            function updateBillDisplay(data) {
                $('#subtotal-display').text('Rp ' + formatNumber(data.subtotal || 0));
                $('#tax-display').text('Rp ' + formatNumber(data.tax_amount || 0));
                $('#service-display').text('Rp ' + formatNumber(data.service_amount || 0));
                $('#total-display').text('Rp ' + formatNumber(data.total || 0));
            }
            
            function requestBill() {
                var $btn = $('#btn-confirm-bill');
                $btn.prop('disabled', true).html('<span class="spinner">⏳</span> Memproses...');
                
                $.ajax({
                    url: config.requestBillUrl,
                    type: 'POST',
                    data: {
                        token: config.token
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            alert(response.message);
                            closeBillModal();
                            $('#floating-bill-btn').addClass('hidden');
                            
                            // Update order status display
                            location.reload();
                        } else {
                            alert(response.message || 'Gagal meminta tagihan');
                            $btn.prop('disabled', false).text('Ya, Minta Tagihan');
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan. Silakan coba lagi.');
                        $btn.prop('disabled', false).text('Ya, Minta Tagihan');
                    }
                });
            }
            
            function monitorConnection() {
                window.addEventListener('online', function() {
                    hideOfflineBanner();
                    config.offlineRetryCount = 0;
                    pollOrderStatus();
                });
                
                window.addEventListener('offline', function() {
                    showOfflineBanner();
                });
            }
            
            function showOfflineBanner(isFinal) {
                $('#offline-banner').addClass('active');
                
                if (isFinal) {
                    $('#retry-countdown').text('Mencoba menyambung ulang dalam 30 detik...');
                    
                    offlineTimer = setTimeout(function() {
                        config.offlineRetryCount = 0;
                        pollOrderStatus();
                    }, 30000);
                } else {
                    var remaining = config.offlineRetryMax - config.offlineRetryCount;
                    $('#retry-countdown').text('Percobaan tersisa: ' + remaining);
                }
            }
            
            function hideOfflineBanner() {
                $('#offline-banner').removeClass('active');
                if (offlineTimer) {
                    clearTimeout(offlineTimer);
                    offlineTimer = null;
                }
            }
            
            function formatNumber(num) {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }
            
            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            return {
                init: init,
                toggleOrder: toggleOrder,
                openBillModal: openBillModal,
                closeBillModal: closeBillModal,
                requestBill: requestBill
            };
        })();
        
        // Global functions for onclick handlers
        function toggleOrder(header) {
            CustomerStatus.toggleOrder(header);
        }
        
        function openBillModal() {
            CustomerStatus.openBillModal();
        }
        
        function closeBillModal() {
            CustomerStatus.closeBillModal();
        }
        
        function requestBill() {
            CustomerStatus.requestBill();
        }
        
        // Initialize on DOM ready
        $(document).ready(function() {
            CustomerStatus.init();
        });
    </script>
</body>
</html>
