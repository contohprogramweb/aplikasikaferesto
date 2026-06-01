<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#e74c3c">
    <title><?= $page_title ?> - <?= $restaurant_name ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            margin: 0;
            padding-bottom: 80px; /* Space for floating cart */
        }
        
        /* Sticky Category Header */
        .category-header {
            position: sticky;
            top: 0;
            z-index: 1020;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 10px 0;
        }
        
        .category-scroll {
            display: flex;
            overflow-x: auto;
            gap: 10px;
            padding: 0 15px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .category-scroll::-webkit-scrollbar {
            display: none;
        }
        
        .category-tab {
            flex-shrink: 0;
            padding: 8px 20px;
            background: #f0f0f0;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            border: none;
        }
        
        .category-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .category-tab .badge-count {
            background: rgba(255,255,255,0.3);
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 5px;
        }
        
        /* Search Bar */
        .search-container {
            padding: 15px;
            background: white;
            position: sticky;
            top: 60px;
            z-index: 1019;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .search-input-wrapper {
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        /* Menu Grid */
        .menu-container {
            padding: 15px;
        }
        
        .category-section {
            margin-bottom: 30px;
        }
        
        .category-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            padding-left: 5px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        @media (min-width: 768px) {
            .menu-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        .menu-item-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
            cursor: pointer;
        }
        
        .menu-item-card:hover {
            transform: translateY(-3px);
        }
        
        .menu-item-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .item-image-container {
            width: 100%;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            position: relative;
            background: #f0f0f0;
        }
        
        .item-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #999;
            font-size: 40px;
        }
        
        /* Sold Out Badge */
        .sold-out-badge {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            background: rgba(231, 76, 60, 0.95);
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            z-index: 10;
        }
        
        .item-details {
            padding: 12px;
        }
        
        .item-name {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 40px;
        }
        
        .item-price {
            font-size: 16px;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 8px;
        }
        
        .item-category-badge {
            display: inline-block;
            background: #f0f0f0;
            color: #666;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }
        
        /* Floating Cart Button */
        .floating-cart {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            cursor: pointer;
            z-index: 1030;
            transition: transform 0.2s;
        }
        
        .floating-cart:hover {
            transform: scale(1.05);
        }
        
        .floating-cart i {
            font-size: 24px;
        }
        
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            border: 2px solid white;
            transition: transform 0.2s ease;
        }
        
        .cart-badge.bounce {
            animation: bounceBadge 0.3s ease;
        }
        
        @keyframes bounceBadge {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        
        .cart-badge.hidden {
            display: none;
        }
        
        /* Cart Panel (Slide-up) */
        .cart-panel-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1031;
        }
        
        .cart-panel-overlay.active {
            display: block;
        }
        
        .cart-panel {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            max-height: 70vh;
            background: white;
            border-radius: 20px 20px 0 0;
            z-index: 1032;
            transform: translateY(100%);
            transition: transform 0.3s ease-out;
            display: flex;
            flex-direction: column;
        }
        
        .cart-panel.active {
            transform: translateY(0);
        }
        
        .cart-panel-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .cart-panel-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }
        
        .btn-close-cart {
            background: none;
            border: none;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            padding: 5px;
        }
        
        .cart-panel-body {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        
        .cart-item {
            display: flex;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cart-item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: #f0f0f0;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }
        
        .cart-item-notes {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .cart-item-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .qty-btn {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 1px solid #e0e0e0;
            background: white;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .cart-item-price {
            font-size: 14px;
            font-weight: 700;
            color: #e74c3c;
        }
        
        .cart-item-remove {
            color: #e74c3c;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            font-size: 16px;
        }
        
        .cart-panel-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            background: white;
        }
        
        .cart-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .cart-total-label {
            font-size: 16px;
            color: #666;
        }
        
        .cart-total-value {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }
        
        .btn-order-now {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-order-now:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .btn-empty-cart {
            width: 100%;
            padding: 12px;
            background: white;
            color: #e74c3c;
            border: 2px solid #e74c3c;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        .empty-state p {
            font-size: 14px;
        }
        
        /* Session Timeout Warning Modal */
        .session-timeout-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .session-timeout-modal.active {
            display: flex;
        }
        
        .session-timeout-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 320px;
            width: 90%;
            text-align: center;
        }
        
        .session-timeout-content h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .session-timeout-content p {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .countdown-timer {
            font-size: 36px;
            font-weight: 700;
            color: #e74c3c;
            margin: 20px 0;
        }
        
        /* Offline Banner */
        .offline-banner {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #e74c3c;
            color: white;
            padding: 12px 15px;
            text-align: center;
            font-size: 13px;
            z-index: 10001;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        
        .offline-banner.active {
            display: block;
            opacity: 1;
        }
        
        .offline-banner.fade-out {
            opacity: 0;
        }
        
        .offline-banner .retry-countdown {
            font-weight: 700;
            margin-left: 5px;
        }
        
        .offline-banner .retry-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.5);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            margin-left: 10px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.2s;
        }
        
        .offline-banner .retry-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        @media (max-width: 360px) {
            .menu-grid {
                gap: 10px;
            }
            
            .item-name {
                font-size: 13px;
                min-height: 35px;
            }
        }
    </style>
</head>
<body>
    <!-- Offline Banner -->
    <div class="offline-banner" id="offline-banner">
        <i class="fas fa-wifi"></i> Koneksi terputus. Menghubungkan ulang... 
        <span class="retry-countdown" id="retry-countdown">(3)</span>
        <button class="retry-btn" id="btn-retry-offline" onclick="window.location.reload()">
            <i class="fas fa-redo"></i> Refresh Manual
        </button>
    </div>

    <!-- Sticky Category Header -->
    <div class="category-header">
        <div class="category-scroll" id="category-scroll">
            <button class="category-tab active" data-category="all">
                Semua <span class="badge-count"><?= count($items_by_category) ?></span>
            </button>
            <?php foreach ($categories as $cat): ?>
            <button class="category-tab" data-category="<?= $cat['id'] ?>">
                <?= $cat['name'] ?> 
                <span class="badge-count"><?= isset($items_by_category[$cat['id']]['items']) ? count($items_by_category[$cat['id']]['items']) : 0 ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <div class="search-input-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" id="search-input" placeholder="Cari menu...">
        </div>
    </div>

    <!-- Menu Container -->
    <div class="menu-container">
        <?php if (empty($items_by_category)): ?>
        <div class="empty-state">
            <i class="fas fa-utensils"></i>
            <p>Belum ada menu tersedia</p>
        </div>
        <?php else: ?>
        
        <!-- All Items Section (default view) -->
        <div class="category-section" data-category="all">
            <h2 class="category-title">Semua Menu</h2>
            <div class="menu-grid">
                <?php foreach ($items_by_category as $cat_data): ?>
                    <?php foreach ($cat_data['items'] as $item): ?>
                    <div class="menu-item-card <?= !$item['is_available'] ? 'disabled' : '' ?>" 
                         data-item-id="<?= $item['id'] ?>"
                         data-category-id="<?= $item['category_id'] ?>"
                         data-available="<?= $item['is_available'] ?>">
                        <div class="item-image-container">
                            <?php if ($item['image']): ?>
                            <img src="<?= base_url('uploads/menu/' . $item['image']) ?>" 
                                 alt="<?= $item['name'] ?>" 
                                 class="item-image"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                            <?php endif; ?>
                            <div class="item-placeholder" style="<?= $item['image'] ? 'display:none' : '' ?>">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <?php if (!$item['is_available']): ?>
                            <div class="sold-out-badge">Habis</div>
                            <?php endif; ?>
                        </div>
                        <div class="item-details">
                            <div class="item-name"><?= $item['name'] ?></div>
                            <div class="item-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                            <span class="item-category-badge"><?= $cat_data['category']['name'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Category Sections -->
        <?php foreach ($items_by_category as $cat_data): ?>
        <?php if (!empty($cat_data['items'])): ?>
        <div class="category-section" data-category="<?= $cat_data['category']['id'] ?>">
            <h2 class="category-title"><?= $cat_data['category']['name'] ?></h2>
            <div class="menu-grid">
                <?php foreach ($cat_data['items'] as $item): ?>
                <div class="menu-item-card <?= !$item['is_available'] ? 'disabled' : '' ?>" 
                     data-item-id="<?= $item['id'] ?>"
                     data-category-id="<?= $item['category_id'] ?>"
                     data-available="<?= $item['is_available'] ?>">
                    <div class="item-image-container">
                        <?php if ($item['image']): ?>
                        <img src="<?= base_url('uploads/menu/' . $item['image']) ?>" 
                             alt="<?= $item['name'] ?>" 
                             class="item-image"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                        <?php endif; ?>
                        <div class="item-placeholder" style="<?= $item['image'] ? 'display:none' : '' ?>">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <?php if (!$item['is_available']): ?>
                        <div class="sold-out-badge">Habis</div>
                        <?php endif; ?>
                    </div>
                    <div class="item-details">
                        <div class="item-name"><?= $item['name'] ?></div>
                        <div class="item-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                        <span class="item-category-badge"><?= $cat_data['category']['name'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>

    <!-- Floating Cart Button -->
    <div class="floating-cart" id="floating-cart">
        <i class="fas fa-shopping-cart"></i>
        <div class="cart-badge <?= $cart_count == 0 ? 'hidden' : '' ?>" id="cart-badge">
            <?= $cart_count ?>
        </div>
    </div>

    <!-- Cart Panel Overlay -->
    <div class="cart-panel-overlay" id="cart-panel-overlay"></div>

    <!-- Cart Panel -->
    <div class="cart-panel" id="cart-panel">
        <div class="cart-panel-header">
            <span class="cart-panel-title">Pesanan Anda</span>
            <button class="btn-close-cart" id="btn-close-cart">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="cart-panel-body" id="cart-panel-body">
            <!-- Cart items will be rendered here by JavaScript -->
        </div>
        <div class="cart-panel-footer">
            <div class="cart-total-row">
                <span class="cart-total-label">Total</span>
                <span class="cart-total-value" id="cart-total">Rp 0</span>
            </div>
            <button class="btn-order-now" id="btn-order-now" disabled>
                Pesan Sekarang
            </button>
            <button class="btn-empty-cart" id="btn-empty-cart">
                <i class="fas fa-trash"></i> Kosongkan Keranjang
            </button>
        </div>
    </div>

    <!-- Session Timeout Modal -->
    <div class="session-timeout-modal" id="session-timeout-modal">
        <div class="session-timeout-content">
            <h3><i class="fas fa-clock"></i> Sesi Akan Berakhir</h3>
            <p>Sesi Anda akan berakhir karena tidak ada aktivitas. Lanjutkan sesi?</p>
            <div class="countdown-timer" id="session-countdown">60</div>
            <button class="btn-order-now" id="btn-extend-session">
                Ya, Lanjutkan Sesi
            </button>
        </div>
    </div>

    <!-- Item Detail Modal -->
    <div class="item-detail-modal-overlay" id="item-detail-overlay"></div>
    <div class="item-detail-modal" id="item-detail-modal">
        <div class="item-detail-content">
            <button class="btn-close-modal" id="btn-close-modal">
                <i class="fas fa-times"></i>
            </button>
            <div class="item-detail-image-container">
                <img src="" alt="" class="item-detail-image" id="modal-item-image">
                <div class="item-detail-placeholder" id="modal-item-placeholder">
                    <i class="fas fa-utensils"></i>
                </div>
            </div>
            <div class="item-detail-body">
                <h2 class="item-detail-name" id="modal-item-name"></h2>
                <div class="item-detail-price" id="modal-item-price"></div>
                <p class="item-detail-description" id="modal-item-description"></p>
                
                <div class="qty-adjuster-container">
                    <label>Jumlah:</label>
                    <div class="qty-adjuster">
                        <button class="qty-btn-large" id="btn-decrease-qty">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="qty-input" id="modal-qty-input" value="1" min="1" max="99">
                        <button class="qty-btn-large" id="btn-increase-qty">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                
                <div class="notes-container">
                    <label for="modal-notes">Catatan Khusus (opsional):</label>
                    <textarea class="notes-textarea" id="modal-notes" maxlength="200" rows="3" placeholder="Contoh: Tidak pedas, kurang garam, dll."></textarea>
                    <div class="notes-counter"><span id="notes-char-count">0</span>/200</div>
                </div>
                
                <div class="subtotal-container">
                    <span>Subtotal:</span>
                    <span class="subtotal-value" id="modal-subtotal"></span>
                </div>
            </div>
            <div class="item-detail-footer">
                <button class="btn-add-to-cart" id="btn-add-to-cart">
                    <i class="fas fa-shopping-cart"></i> Tambah ke Pesanan
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden data for JavaScript -->
    <div id="menu-data" 
         data-token="<?= $token ?>"
         data-table-code="<?= $table['table_number'] ?>"
         data-cart-sync-url="<?= site_url('api/cart/sync') ?>"
         data-session-validate-url="<?= site_url('customer/validate_session') ?>"
         data-session-heartbeat-url="<?= site_url('customer/heartbeat') ?>"
         data-session-expires="<?= $session_expires_at ?>"
         style="display: none;">
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('assets/js/customer.js') ?>"></script>
    <script>
        $(document).ready(function() {
            CustomerMenu.init({
                token: '<?= $token ?>',
                tableCode: '<?= $table['table_number'] ?>',
                cartSyncUrl: '<?= site_url('api/cart/sync') ?>',
                sessionValidateUrl: '<?= site_url('customer/validate_session') ?>',
                sessionHeartbeatUrl: '<?= site_url('customer/heartbeat') ?>',
                sessionExpires: '<?= $session_expires_at ?>'
            });
        });
    </script>
</body>
</html>
