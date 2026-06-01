<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Kitchen Display System' ?> - Smart Restaurant POS</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--gray-100);
            color: var(--gray-800);
            min-height: 100vh;
        }
        
        /* Sticky Header */
        .kds-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: linear-gradient(135deg, var(--gray-800) 0%, var(--gray-900) 100%);
            color: white;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .header-stats {
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-left: auto;
        }
        
        /* Auto-refresh Indicator */
        .refresh-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--success-color);
            animation: pulse-dot 2s infinite;
        }
        
        .refresh-indicator.error {
            background-color: var(--danger-color);
        }
        
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Main Content Grid */
        .kds-content {
            padding: 1.5rem;
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        
        /* Responsive Grid */
        @media (max-width: 1279px) {
            .orders-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 767px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
            
            .header-stats {
                gap: 1rem;
            }
            
            .stat-value {
                font-size: 1.25rem;
            }
        }
        
        /* Order Card */
        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            height: 280px;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }
        
        .order-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }
        
        /* New Order Highlight (<2 minutes) */
        .order-card.new-order {
            border-color: var(--warning-color);
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            animation: pulse-border 2s infinite;
        }
        
        @keyframes pulse-border {
            0%, 100% { 
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4);
            }
            50% { 
                box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
            }
        }
        
        /* Card Header */
        .card-header-custom {
            padding: 0.875rem 1rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-number {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--gray-900);
        }
        
        .order-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.875rem;
            color: var(--gray-700);
        }
        
        .order-time {
            font-size: 0.75rem;
            color: var(--gray-700);
        }
        
        .timer-badge {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            background: var(--gray-200);
        }
        
        .timer-badge.overtime {
            background: var(--danger-color);
            color: white;
            animation: blink-timer 1s infinite;
        }
        
        @keyframes blink-timer {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Card Body */
        .card-body-custom {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray-100);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--gray-800);
        }
        
        .item-qty {
            display: inline-block;
            min-width: 1.5rem;
            text-align: center;
            background: var(--primary-color);
            color: white;
            border-radius: 4px;
            font-size: 0.75rem;
            padding: 0.125rem 0.375rem;
            margin-right: 0.5rem;
        }
        
        .item-notes {
            font-size: 0.75rem;
            color: var(--gray-700);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            margin-top: 0.25rem;
        }
        
        /* Card Footer */
        .card-footer-custom {
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
        }
        
        .status-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .status-btn {
            flex: 1;
            min-height: 44px;
            border: none;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-btn.accept {
            background: var(--primary-color);
            color: white;
        }
        
        .status-btn.accept:hover {
            background: #1d4ed8;
        }
        
        .status-btn.cooking {
            background: var(--warning-color);
            color: white;
        }
        
        .status-btn.cooking:hover {
            background: #d97706;
        }
        
        .status-btn.ready {
            background: var(--success-color);
            color: white;
        }
        
        .status-btn.ready:hover {
            background: #059669;
        }
        
        .status-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Cancel Item Dropdown */
        .cancel-dropdown {
            position: relative;
        }
        
        .cancel-btn {
            background: transparent;
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
            border-radius: 6px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .cancel-btn:hover {
            background: var(--danger-color);
            color: white;
        }
        
        /* Batch Actions */
        .batch-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .batch-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn-batch-accept {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-batch-accept:hover {
            background: #059669;
        }
        
        .btn-batch-accept:disabled {
            background: var(--gray-300);
            cursor: not-allowed;
        }
        
        /* Mute Button */
        .btn-mute {
            background: transparent;
            border: 2px solid var(--gray-300);
            color: var(--gray-700);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-mute.muted {
            background: var(--gray-300);
            border-color: var(--gray-300);
        }
        
        .btn-mute:hover {
            border-color: var(--gray-700);
        }
        
        /* Modal */
        .modal-cancel .modal-content {
            border-radius: 12px;
            border: none;
        }
        
        .modal-cancel .modal-header {
            background: var(--danger-color);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        /* Scrollbar Styling */
        .card-body-custom::-webkit-scrollbar {
            width: 6px;
        }
        
        .card-body-custom::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 3px;
        }
        
        .card-body-custom::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 3px;
        }
        
        .card-body-custom::-webkit-scrollbar-thumb:hover {
            background: var(--gray-700);
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.pending {
            background: var(--gray-200);
            color: var(--gray-700);
        }
        
        .status-badge.confirmed {
            background: var(--primary-color);
            color: white;
        }
        
        .status-badge.preparing {
            background: var(--warning-color);
            color: white;
        }
        
        .status-badge.ready {
            background: var(--success-color);
            color: white;
        }
        
        /* Checkbox styling for batch selection */
        .order-checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Sticky Header -->
    <header class="kds-header">
        <div class="header-stats">
            <div class="stat-item">
                <i class="fas fa-receipt"></i>
                <div>
                    <div class="stat-value" id="active-orders-count"><?= $active_orders_count ?? 0 ?></div>
                    <div class="stat-label">Pesanan Aktif</div>
                </div>
            </div>
            
            <div class="stat-item">
                <i class="fas fa-clock"></i>
                <div>
                    <div class="stat-value" id="avg-wait-time"><?= gmdate('H:i:s', $avg_wait_time ?? 0) ?></div>
                    <div class="stat-label">Rata-rata Tunggu</div>
                </div>
            </div>
            
            <div class="stat-item">
                <i class="fas fa-hourglass-start"></i>
                <div>
                    <div class="stat-value" id="pending-count"><?= $pending_count ?? 0 ?></div>
                    <div class="stat-label">Menunggu</div>
                </div>
            </div>
            
            <div class="header-actions">
                <div class="batch-actions">
                    <button type="button" class="btn-batch-accept" id="btn-batch-accept" disabled>
                        <i class="fas fa-check-double"></i> Terima Semua
                    </button>
                </div>
                
                <button type="button" class="btn-mute" id="btn-mute" title="Toggle Sound">
                    <i class="fas fa-volume-up" id="mute-icon"></i>
                </button>
                
                <div class="refresh-indicator" id="refresh-indicator" title="Auto-refresh status"></div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="kds-content">
        <div class="orders-grid" id="orders-grid">
            <!-- Order cards will be rendered here by JavaScript -->
        </div>
        
        <!-- Empty State -->
        <div id="empty-state" class="text-center py-5" style="display: none;">
            <i class="fas fa-clipboard-check" style="font-size: 4rem; color: var(--gray-300);"></i>
            <h3 class="mt-3 text-muted">Tidak ada pesanan aktif</h3>
            <p class="text-muted">Pesanan baru akan muncul di sini</p>
        </div>
    </main>
    
    <!-- Cancel Item Modal -->
    <div class="modal fade modal-cancel" id="modalCancelItem" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Item Tidak Tersedia</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cancel-item-id">
                    <p class="mb-3">Apakah Anda yakin item ini tidak tersedia?</p>
                    <div class="mb-3">
                        <label for="cancel-reason" class="form-label">Alasan (opsional)</label>
                        <textarea class="form-control" id="cancel-reason" rows="3" maxlength="100" placeholder="Contoh: Bahan habis, peralatan rusak..."></textarea>
                        <small class="text-muted">Maksimal 100 karakter</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="btn-confirm-cancel">
                        <i class="fas fa-times-circle"></i> Tandai Tidak Tersedia
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Audio Element for Alert -->
    <audio id="alert-sound" preload="auto">
        <source src="data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=" type="audio/wav">
    </audio>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Kitchen JS -->
    <script src="<?= base_url('assets/js/kitchen.js') ?>"></script>
    
    <script>
        // Initialize with server data
        window.initialOrders = <?= json_encode($orders ?? []) ?>;
        window.baseUrl = '<?= base_url() ?>';
        window.apiEndpoint = '<?= site_url('api/kitchen/orders') ?>';
        window.acceptEndpoint = '<?= site_url('kitchen/accept') ?>';
        window.updateStatusEndpoint = '<?= site_url('kitchen/update_status') ?>';
        window.cancelItemEndpoint = '<?= site_url('kitchen/cancel_item') ?>';
        window.undoStatusEndpoint = '<?= site_url('kitchen/undo_status') ?>';
    </script>
</body>
</html>
