<style>
/* Waiter Dashboard Styles */
.waiter-dashboard {
    padding: 20px;
}

/* Responsive Layout */
@media (min-width: 1024px) {
    .waiter-split-view {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .waiter-column {
        min-height: calc(100vh - 200px);
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .waiter-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .waiter-tab-btn {
        flex: 1;
        padding: 12px 20px;
        border: none;
        background: #e9ecef;
        cursor: pointer;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s;
    }
    
    .waiter-tab-btn.active {
        background: #0d6efd;
        color: white;
    }
    
    .waiter-tab-content {
        display: none;
    }
    
    .waiter-tab-content.active {
        display: block;
    }
}

@media (max-width: 767px) {
    .waiter-stacked {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
}

/* Column Headers */
.waiter-column-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.waiter-column-header.in-progress {
    background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
}

.waiter-column-title {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
}

.waiter-column-count {
    background: rgba(255,255,255,0.3);
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 14px;
}

/* Order Cards */
.waiter-order-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 15px;
    overflow: hidden;
    transition: all 0.3s;
    border-left: 4px solid #0d6efd;
}

.waiter-order-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.waiter-order-card.highlight-new {
    border-left-color: #FFC107;
    animation: pulse-border 2s infinite;
}

@keyframes pulse-border {
    0%, 100% { box-shadow: 0 2px 8px rgba(255,193,7,0.3); }
    50% { box-shadow: 0 2px 16px rgba(255,193,7,0.6); }
}

.waiter-card-header {
    padding: 12px 16px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.waiter-table-code {
    font-size: 18px;
    font-weight: 700;
    color: #212529;
}

.waiter-order-number {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    color: #6c757d;
}

.waiter-timer {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 4px;
    background: #e9ecef;
}

.waiter-timer.overtime {
    color: #dc3545;
    background: #f8d7da;
    animation: blink-timer 1s infinite;
}

@keyframes blink-timer {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.waiter-card-body {
    padding: 12px 16px;
    max-height: 180px;
    overflow-y: auto;
}

.waiter-item-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 8px 0;
    border-bottom: 1px solid #f1f3f5;
}

.waiter-item-row:last-child {
    border-bottom: none;
}

.waiter-item-info {
    flex: 1;
}

.waiter-item-name {
    font-weight: 600;
    color: #212529;
    margin-bottom: 4px;
}

.waiter-item-qty {
    display: inline-block;
    background: #0d6efd;
    color: white;
    font-size: 12px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
    margin-right: 8px;
}

.waiter-item-note {
    font-size: 12px;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.waiter-item-badge {
    background: #28a745;
    color: white;
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 10px;
    font-weight: 600;
}

.waiter-card-footer {
    padding: 12px 16px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.waiter-deliver-btn {
    width: 100%;
    min-height: 44px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.waiter-deliver-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40,167,69,0.4);
}

.waiter-deliver-btn:active {
    transform: translateY(0);
}

/* In Progress Cards */
.waiter-order-card.in-progress {
    border-left-color: #6c757d;
    background: #f8f9fa;
}

.waiter-order-card.in-progress .waiter-card-header {
    background: #e9ecef;
}

.waiter-kitchen-status {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
}

.waiter-kitchen-status.confirmed {
    background: #ffc107;
    color: #000;
}

.waiter-kitchen-status.preparing {
    background: #17a2b8;
    color: white;
}

/* Stats Bar */
.waiter-stats-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.waiter-stat-card {
    background: white;
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.waiter-stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #0d6efd;
}

.waiter-stat-label {
    font-size: 13px;
    color: #6c757d;
    margin-top: 5px;
}

/* Auto-refresh indicator */
.waiter-refresh-indicator {
    position: fixed;
    top: 80px;
    right: 20px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #28a745;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.waiter-refresh-indicator.error {
    background: #dc3545;
}

/* Mute button */
.waiter-mute-btn {
    position: fixed;
    top: 80px;
    right: 45px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #6c757d;
    color: white;
    border: none;
    cursor: pointer;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.waiter-mute-btn.muted {
    background: #dc3545;
}

/* Empty state */
.waiter-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.waiter-empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Loading spinner */
.waiter-loading {
    text-align: center;
    padding: 20px;
}

.waiter-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #0d6efd;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="waiter-dashboard">
    <!-- Stats Bar -->
    <div class="waiter-stats-bar">
        <div class="waiter-stat-card">
            <div class="waiter-stat-value"><?= $ready_count ?></div>
            <div class="waiter-stat-label">Siap Diantar</div>
        </div>
        <div class="waiter-stat-card">
            <div class="waiter-stat-value"><?= $total_items_ready ?></div>
            <div class="waiter-stat-label">Total Item</div>
        </div>
        <div class="waiter-stat-card">
            <div class="waiter-stat-value"><?= $in_progress_count ?></div>
            <div class="waiter-stat-label">Dalam Proses</div>
        </div>
    </div>

    <!-- Auto-refresh indicator & mute button -->
    <div id="refreshIndicator" class="waiter-refresh-indicator"></div>
    <button id="muteBtn" class="waiter-mute-btn" title="Toggle sound">🔔</button>

    <!-- Main Content -->
    <div class="waiter-split-view">
        <!-- Left Column: Ready to Deliver -->
        <div class="waiter-column">
            <div class="waiter-column-header">
                <h3 class="waiter-column-title">🚀 Siap Diantar</h3>
                <span class="waiter-column-count" id="readyCount"><?= $ready_count ?> meja</span>
            </div>
            
            <div id="readyOrdersContainer">
                <?php if (empty($ready_orders)): ?>
                <div class="waiter-empty-state">
                    <i>📦</i>
                    <p>Belum ada pesanan siap antar</p>
                </div>
                <?php else: ?>
                    <?php foreach ($ready_orders as $order): ?>
                    <?= $this->load->view('waiter/_ready_card', ['order' => $order], TRUE); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: In Progress -->
        <div class="waiter-column">
            <div class="waiter-column-header in-progress">
                <h3 class="waiter-column-title">⏳ Dalam Proses</h3>
                <span class="waiter-column-count" id="inProgressCount"><?= $in_progress_count ?> meja</span>
            </div>
            
            <div id="inProgressOrdersContainer">
                <?php if (empty($in_progress_orders)): ?>
                <div class="waiter-empty-state">
                    <i>👨‍🍳</i>
                    <p>Semua pesanan sudah siap!</p>
                </div>
                <?php else: ?>
                    <?php foreach ($in_progress_orders as $order): ?>
                    <?= $this->load->view('waiter/_progress_card', ['order' => $order], TRUE); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Audio element for alerts -->
<audio id="alertSound" preload="auto">
    <source src="<?= base_url('assets/audio/beep.mp3') ?>" type="audio/mpeg">
</audio>

<script src="<?= base_url('assets/js/waiter.js') ?>"></script>
<script>
// Initialize waiter dashboard
document.addEventListener('DOMContentLoaded', function() {
    WaiterDashboard.init({
        pollingEndpoint: '<?= site_url('api/waiter/ready') ?>',
        deliverEndpoint: '<?= site_url('waiter/deliver') ?>',
        initialReadyData: <?= json_encode($ready_orders) ?>,
        lastId: <?= !empty($ready_orders) ? max(array_column(array_merge(...array_column($ready_orders, 'items')), 'id')) : 0 ?>,
        lastTimestamp: '<?= date('Y-m-d H:i:s') ?>'
    });
});
</script>
