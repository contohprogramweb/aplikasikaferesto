/**
 * Kitchen Display System (KDS) - Smart Polling & UI Logic
 * Based on SRS v4.0: UC-KIT-01, UC-KIT-02, UC-KIT-03
 * 
 * Features:
 * - Smart polling with exponential backoff (5s → 10s → 20s max)
 * - Deduplication via is_requesting flag
 * - Delta updates using last_id and last_timestamp
 * - Rate limiting: max 1 req/3 detik per session
 * - Sound alerts (must be enabled via user interaction)
 * - Mute status persisted in LocalStorage
 */

(function($) {
    'use strict';

    // Configuration
    const CONFIG = {
        POLL_INTERVAL_DEFAULT: 5000,      // 5 seconds default
        POLL_INTERVAL_MIN: 5000,          // Minimum 5s
        POLL_INTERVAL_MAX: 20000,         // Maximum 20s
        BACKOFF_MULTIPLIER: 2,            // Exponential backoff multiplier
        RATE_LIMIT_WINDOW: 3000,          // 3 seconds rate limit
        UNDO_WINDOW: 30000,               // 30 seconds undo window
        NEW_ORDER_THRESHOLD: 120000,      // 2 minutes for new order highlight
        OVERTIME_THRESHOLD: 900000,       // 15 minutes for overtime warning
        MAX_VISIBLE_ITEMS: 4              // Max items visible before scroll
    };

    // State
    let state = {
        orders: [],
        lastId: 0,
        lastTimestamp: '',
        isRequesting: false,
        pollInterval: CONFIG.POLL_INTERVAL_DEFAULT,
        isMuted: false,
        audioEnabled: false,
        selectedOrders: [],
        lastPollTime: 0
    };

    // Audio context for beep sound
    let audioContext = null;

    /**
     * Initialize KDS
     */
    function init() {
        // Load mute status from LocalStorage
        loadMuteStatus();
        
        // Initialize with server data
        if (window.initialOrders && window.initialOrders.length > 0) {
            state.orders = window.initialOrders;
            updateLastIdAndTimestamp(state.orders);
            renderOrders();
            updateStats();
        } else {
            showEmptyState();
        }
        
        // Setup event listeners
        setupEventListeners();
        
        // Start polling
        startPolling();
        
        // Update timers every second
        setInterval(updateTimers, 1000);
    }

    /**
     * Load mute status from LocalStorage
     */
    function loadMuteStatus() {
        const savedMute = localStorage.getItem('kds_mute');
        state.isMuted = savedMute === 'true';
        updateMuteButton();
    }

    /**
     * Save mute status to LocalStorage
     */
    function saveMuteStatus() {
        localStorage.setItem('kds_mute', state.isMuted.toString());
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Mute button
        $('#btn-mute').on('click', function() {
            toggleMute();
        });
        
        // Batch accept button
        $('#btn-batch-accept').on('click', function() {
            batchAccept();
        });
        
        // Confirm cancel item
        $('#btn-confirm-cancel').on('click', function() {
            confirmCancelItem();
        });
        
        // Enable audio on first user interaction
        $(document).one('click keypress', function() {
            enableAudio();
        });
    }

    /**
     * Enable audio (must be triggered by user interaction)
     */
    function enableAudio() {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            state.audioEnabled = true;
        }
    }

    /**
     * Play beep sound (800Hz, 500ms)
     */
    function playBeep() {
        if (state.isMuted || !state.audioEnabled || !audioContext) {
            return;
        }
        
        try {
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;  // 800Hz
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (e) {
            console.warn('Failed to play beep:', e);
        }
    }

    /**
     * Toggle mute status
     */
    function toggleMute() {
        state.isMuted = !state.isMuted;
        saveMuteStatus();
        updateMuteButton();
    }

    /**
     * Update mute button UI
     */
    function updateMuteButton() {
        const $btn = $('#btn-mute');
        const $icon = $('#mute-icon');
        
        if (state.isMuted) {
            $btn.addClass('muted');
            $icon.removeClass('fa-volume-up').addClass('fa-volume-mute');
        } else {
            $btn.removeClass('muted');
            $icon.removeClass('fa-volume-mute').addClass('fa-volume-up');
        }
    }

    /**
     * Start smart polling
     */
    function startPolling() {
        pollOrders();
    }

    /**
     * Poll orders from server with smart polling logic
     */
    function pollOrders() {
        // Deduplication: skip if request is pending
        if (state.isRequesting) {
            setTimeout(pollOrders, CONFIG.POLL_INTERVAL_DEFAULT);
            return;
        }
        
        // Rate limiting: check time since last poll
        const now = Date.now();
        if (now - state.lastPollTime < CONFIG.RATE_LIMIT_WINDOW) {
            setTimeout(pollOrders, CONFIG.RATE_LIMIT_WINDOW - (now - state.lastPollTime));
            return;
        }
        
        state.isRequesting = true;
        state.lastPollTime = now;
        
        // Update refresh indicator
        setRefreshIndicator('loading');
        
        $.ajax({
            url: window.apiEndpoint,
            method: 'POST',
            dataType: 'json',
            data: {
                last_id: state.lastId,
                last_timestamp: state.lastTimestamp
            },
            timeout: 10000,
            success: function(response) {
                handlePollSuccess(response);
            },
            error: function(xhr, status, error) {
                handlePollError(xhr, status, error);
            },
            complete: function() {
                state.isRequesting = false;
            }
        });
    }

    /**
     * Handle successful poll response
     */
    function handlePollSuccess(response) {
        setRefreshIndicator('success');
        
        if (response.status === 'rate_limited') {
            // Server-side rate limiting
            const retryAfter = (response.retry_after || 3) * 1000;
            setTimeout(pollOrders, retryAfter);
            return;
        }
        
        if (response.status !== 'success') {
            console.warn('Poll returned error:', response.message);
            adjustPollInterval(true);
            setTimeout(pollOrders, state.pollInterval);
            return;
        }
        
        // Process delta updates
        if (response.updated && response.data && response.data.length > 0) {
            mergeOrders(response.data);
            updateLastIdAndTimestamp(response.data);
            renderOrders();
            updateStats();
            
            // Play sound for new orders
            const hasNewOrders = response.data.some(o => {
                const createdAt = new Date(o.created_at).getTime();
                return Date.now() - createdAt < CONFIG.NEW_ORDER_THRESHOLD;
            });
            
            if (hasNewOrders) {
                playBeep();
            }
        }
        
        // Reset poll interval on success
        state.pollInterval = CONFIG.POLL_INTERVAL_DEFAULT;
        
        // Schedule next poll
        setTimeout(pollOrders, state.pollInterval);
    }

    /**
     * Handle poll error with exponential backoff
     */
    function handlePollError(xhr, status, error) {
        setRefreshIndicator('error');
        console.warn('Poll error:', status, error);
        
        // Exponential backoff
        adjustPollInterval(true);
        
        // Retry after backoff interval
        setTimeout(pollOrders, state.pollInterval);
    }

    /**
     * Adjust poll interval (exponential backoff)
     */
    function adjustPollInterval(increase) {
        if (increase) {
            state.pollInterval = Math.min(
                state.pollInterval * CONFIG.BACKOFF_MULTIPLIER,
                CONFIG.POLL_INTERVAL_MAX
            );
        } else {
            state.pollInterval = CONFIG.POLL_INTERVAL_DEFAULT;
        }
    }

    /**
     * Set refresh indicator status
     */
    function setRefreshIndicator(status) {
        const $indicator = $('#refresh-indicator');
        
        switch (status) {
            case 'success':
                $indicator.removeClass('error').css('background-color', '#10b981');
                break;
            case 'error':
                $indicator.addClass('error').css('background-color', '#ef4444');
                break;
            case 'loading':
                $indicator.css('background-color', '#f59e0b');
                break;
        }
    }

    /**
     * Merge new/updated orders with existing orders
     */
    function mergeOrders(newOrders) {
        newOrders.forEach(newOrder => {
            const existingIndex = state.orders.findIndex(o => o.id === newOrder.id);
            
            if (existingIndex >= 0) {
                // Update existing order
                state.orders[existingIndex] = newOrder;
            } else {
                // Add new order
                state.orders.push(newOrder);
            }
        });
        
        // Remove completed/cancelled orders
        state.orders = state.orders.filter(o => 
            ['pending', 'confirmed', 'preparing', 'ready'].includes(o.status)
        );
    }

    /**
     * Update last_id and last_timestamp from orders
     */
    function updateLastIdAndTimestamp(orders) {
        orders.forEach(order => {
            if (order.items) {
                order.items.forEach(item => {
                    if (item.id > state.lastId) {
                        state.lastId = item.id;
                    }
                    if (!state.lastTimestamp || item.created_at > state.lastTimestamp) {
                        state.lastTimestamp = item.created_at;
                    }
                });
            }
        });
    }

    /**
     * Render orders to grid
     */
    function renderOrders() {
        const $grid = $('#orders-grid');
        $grid.empty();
        
        if (state.orders.length === 0) {
            showEmptyState();
            return;
        }
        
        hideEmptyState();
        
        // Sort by created_at (FIFO - BR-17)
        const sortedOrders = [...state.orders].sort((a, b) => 
            new Date(a.created_at) - new Date(b.created_at)
        );
        
        sortedOrders.forEach(order => {
            const $card = createOrderCard(order);
            $grid.append($card);
        });
        
        updateBatchButton();
    }

    /**
     * Create order card HTML
     */
    function createOrderCard(order) {
        const $card = $('<div>').addClass('order-card');
        
        // Check if new order (<2 minutes)
        const createdAt = new Date(order.created_at).getTime();
        const age = Date.now() - createdAt;
        
        if (age < CONFIG.NEW_ORDER_THRESHOLD) {
            $card.addClass('new-order');
        }
        
        // Checkbox for batch selection
        const checkboxHtml = `
            <div class="order-checkbox-wrapper">
                <input type="checkbox" class="batch-checkbox" data-order-id="${order.id}" ${state.selectedOrders.includes(order.id) ? 'checked' : ''}>
            </div>
        `;
        
        // Header
        const duration = calculateDuration(order.created_at);
        const isOvertime = age > CONFIG.OVERTIME_THRESHOLD;
        
        const headerHtml = `
            <div class="card-header-custom">
                <div>
                    ${checkboxHtml}
                    <span class="table-number">Meja ${order.table_number || '-'}</span>
                </div>
                <div class="text-right">
                    <div class="order-number">#${order.order_number || ''}</div>
                    <div class="order-time">${formatTime(order.created_at)}</div>
                </div>
                <span class="timer-badge ${isOvertime ? 'overtime' : ''}">${duration}</span>
            </div>
        `;
        
        // Body (items list)
        let itemsHtml = '<div class="card-body-custom">';
        
        if (order.items && order.items.length > 0) {
            order.items.forEach((item, index) => {
                const canCancel = item.status === 'confirmed';
                const statusClass = getStatusClass(item.status);
                
                itemsHtml += `
                    <div class="order-item" data-item-id="${item.id}">
                        <div class="item-info">
                            <div>
                                <span class="item-qty">${item.quantity}</span>
                                <span class="item-name">${escapeHtml(item.menu_item_name || 'Unknown Item')}</span>
                            </div>
                            ${item.notes ? `<div class="item-notes">📝 ${escapeHtml(item.notes)}</div>` : ''}
                            <div style="margin-top: 0.25rem;">
                                <span class="status-badge ${statusClass}">${translateStatus(item.status)}</span>
                            </div>
                        </div>
                        ${canCancel ? `
                            <button class="cancel-btn" onclick="showCancelModal(${item.id})">
                                <i class="fas fa-times"></i>
                            </button>
                        ` : ''}
                    </div>
                `;
            });
        } else {
            itemsHtml = '<div class="card-body-custom"><p class="text-muted text-center">No items</p></div>';
        }
        
        itemsHtml += '</div>';
        
        // Footer (status buttons)
        const footerHtml = createStatusButtons(order);
        
        $card.append(headerHtml);
        $card.append(itemsHtml);
        $card.append(footerHtml);
        
        // Bind checkbox event
        $card.find('.batch-checkbox').on('change', function() {
            toggleOrderSelection(order.id);
        });
        
        return $card;
    }

    /**
     * Create status buttons based on order status
     */
    function createStatusButtons(order) {
        let buttonsHtml = '<div class="card-footer-custom"><div class="status-buttons">';
        
        const status = order.status;
        
        if (status === 'pending') {
            buttonsHtml += `
                <button class="status-btn accept" onclick="acceptOrder(${order.id})">
                    <i class="fas fa-check"></i> Terima
                </button>
            `;
        } else if (status === 'confirmed') {
            buttonsHtml += `
                <button class="status-btn cooking" onclick="updateItemStatus(${order.id}, 'dimasak')">
                    <i class="fas fa-fire"></i> Sedang Dimasak
                </button>
            `;
        } else if (status === 'preparing') {
            buttonsHtml += `
                <button class="status-btn ready" onclick="updateItemStatus(${order.id}, 'siap')">
                    <i class="fas fa-check-circle"></i> Siap
                </button>
            `;
        } else if (status === 'ready') {
            buttonsHtml += `
                <button class="status-btn" disabled style="background: #10b981; color: white;">
                    <i class="fas fa-check-double"></i> Siap Saji
                </button>
            `;
        }
        
        buttonsHtml += '</div></div>';
        
        return buttonsHtml;
    }

    /**
     * Accept order(s)
     */
    window.acceptOrder = function(orderId) {
        const orderIds = Array.isArray(orderId) ? orderId : [orderId];
        
        $.ajax({
            url: window.acceptEndpoint,
            method: 'POST',
            dataType: 'json',
            data: { order_ids: orderIds },
            success: function(response) {
                if (response.status === 'success') {
                    // Refresh immediately
                    pollOrders();
                    showNotification('Pesanan berhasil diterima', 'success');
                } else {
                    showNotification(response.message || 'Gagal menerima pesanan', 'error');
                }
            },
            error: function() {
                showNotification('Terjadi kesalahan', 'error');
            }
        });
    };

    /**
     * Update item status
     */
    window.updateItemStatus = function(orderId, newStatus) {
        // Get first item from the order that matches current status flow
        const order = state.orders.find(o => o.id === orderId);
        if (!order || !order.items || order.items.length === 0) {
            return;
        }
        
        const item = order.items.find(i => {
            const statusMap = {
                'diterima': 'confirmed',
                'dimasak': 'preparing',
                'siap': 'ready'
            };
            return i.status === Object.keys(statusMap).find(k => statusMap[k] === newStatus) ||
                   (newStatus === 'dimasak' && i.status === 'confirmed') ||
                   (newStatus === 'siap' && i.status === 'preparing');
        });
        
        if (!item) {
            // Try to get any active item
            const activeItem = order.items.find(i => 
                ['pending', 'confirmed', 'preparing'].includes(i.status)
            );
            if (!activeItem) return;
            
            // Determine next status
            const nextStatusMap = {
                'pending': 'diterima',
                'confirmed': 'dimasak',
                'preparing': 'siap'
            };
            newStatus = nextStatusMap[activeItem.status] || newStatus;
        }
        
        $.ajax({
            url: window.updateStatusEndpoint,
            method: 'POST',
            dataType: 'json',
            data: {
                item_id: item ? item.id : order.items[0].id,
                status: newStatus
            },
            success: function(response) {
                if (response.status === 'success') {
                    pollOrders();
                    showNotification(`Status diubah ke ${newStatus}`, 'success');
                } else {
                    showNotification(response.message || 'Gagal update status', 'error');
                }
            },
            error: function() {
                showNotification('Terjadi kesalahan', 'error');
            }
        });
    };

    /**
     * Show cancel item modal
     */
    window.showCancelModal = function(itemId) {
        $('#cancel-item-id').val(itemId);
        $('#cancel-reason').val('');
        $('#modalCancelItem').modal('show');
    };

    /**
     * Confirm cancel item
     */
    function confirmCancelItem() {
        const itemId = $('#cancel-item-id').val();
        const reason = $('#cancel-reason').val().trim();
        
        if (!itemId) return;
        
        $.ajax({
            url: window.cancelItemEndpoint,
            method: 'POST',
            dataType: 'json',
            data: {
                item_id: itemId,
                reason: reason
            },
            success: function(response) {
                if (response.status === 'success') {
                    $('#modalCancelItem').modal('hide');
                    pollOrders();
                    showNotification('Item berhasil dibatalkan', 'success');
                } else {
                    showNotification(response.message || 'Gagal membatalkan item', 'error');
                }
            },
            error: function() {
                showNotification('Terjadi kesalahan', 'error');
            }
        });
    }

    /**
     * Batch accept selected orders
     */
    function batchAccept() {
        if (state.selectedOrders.length === 0) {
            showNotification('Pilih pesanan terlebih dahulu', 'warning');
            return;
        }
        
        acceptOrder(state.selectedOrders);
        state.selectedOrders = [];
        updateBatchButton();
    }

    /**
     * Toggle order selection for batch operations
     */
    function toggleOrderSelection(orderId) {
        const index = state.selectedOrders.indexOf(orderId);
        
        if (index >= 0) {
            state.selectedOrders.splice(index, 1);
        } else {
            state.selectedOrders.push(orderId);
        }
        
        updateBatchButton();
    }

    /**
     * Update batch accept button state
     */
    function updateBatchButton() {
        const $btn = $('#btn-batch-accept');
        const hasPending = state.orders.some(o => o.status === 'pending');
        const hasSelection = state.selectedOrders.length > 0;
        
        $btn.prop('disabled', !(hasPending && hasSelection));
        
        if (hasSelection) {
            $btn.html(`<i class="fas fa-check-double"></i> Terima ${state.selectedOrders.length}`);
        } else {
            $btn.html('<i class="fas fa-check-double"></i> Terima Semua');
        }
    }

    /**
     * Update statistics in header
     */
    function updateStats() {
        const activeCount = state.orders.length;
        const pendingCount = state.orders.filter(o => o.status === 'pending').length;
        
        // Calculate average wait time
        let totalWaitTime = 0;
        let countWithWait = 0;
        
        state.orders.forEach(order => {
            const waitTime = Date.now() - new Date(order.created_at).getTime();
            if (waitTime > 0) {
                totalWaitTime += waitTime;
                countWithWait++;
            }
        });
        
        const avgWaitTime = countWithWait > 0 ? Math.floor(totalWaitTime / countWithWait) : 0;
        
        // Update DOM
        $('#active-orders-count').text(activeCount);
        $('#pending-count').text(pendingCount);
        $('#avg-wait-time').text(formatDuration(avgWaitTime));
    }

    /**
     * Update timers on all order cards
     */
    function updateTimers() {
        $('.timer-badge').each(function() {
            const $card = $(this).closest('.order-card');
            const orderNumber = $card.find('.order-number').text().replace('#', '');
            const order = state.orders.find(o => o.order_number === orderNumber);
            
            if (order) {
                const duration = calculateDuration(order.created_at);
                $(this).text(duration);
                
                const age = Date.now() - new Date(order.created_at).getTime();
                if (age > CONFIG.OVERTIME_THRESHOLD) {
                    $(this).addClass('overtime');
                } else {
                    $(this).removeClass('overtime');
                }
            }
        });
        
        // Update stats every 10 seconds
        if (Date.now() % 10000 < 1000) {
            updateStats();
        }
    }

    /**
     * Calculate duration string from timestamp
     */
    function calculateDuration(timestamp) {
        const diff = Date.now() - new Date(timestamp).getTime();
        return formatDuration(diff);
    }

    /**
     * Format duration in HH:MM:SS
     */
    function formatDuration(ms) {
        const totalSeconds = Math.floor(ms / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    /**
     * Format time from timestamp
     */
    function formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }

    /**
     * Get status CSS class
     */
    function getStatusClass(status) {
        const map = {
            'pending': 'pending',
            'confirmed': 'confirmed',
            'preparing': 'preparing',
            'ready': 'ready',
            'cancelled': 'cancelled'
        };
        return map[status] || 'pending';
    }

    /**
     * Translate status to Indonesian
     */
    function translateStatus(status) {
        const map = {
            'pending': 'Menunggu',
            'confirmed': 'Diterima',
            'preparing': 'Dimasak',
            'ready': 'Siap',
            'cancelled': 'Dibatalkan'
        };
        return map[status] || status;
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show empty state
     */
    function showEmptyState() {
        $('#orders-grid').hide();
        $('#empty-state').show();
    }

    /**
     * Hide empty state
     */
    function hideEmptyState() {
        $('#empty-state').hide();
        $('#orders-grid').show();
    }

    /**
     * Show notification toast
     */
    function showNotification(message, type = 'info') {
        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        
        const $toast = $(`
            <div class="toast-notification" style="
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: ${colors[type] || colors.info};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                animation: slideIn 0.3s ease;
            ">
                ${message}
            </div>
        `);
        
        $('body').append($toast);
        
        setTimeout(() => {
            $toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Add slideIn animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
