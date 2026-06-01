/**
 * Waiter Dashboard JavaScript
 * Smart polling for ready orders with exponential backoff, deduplication, and audio alerts
 * Based on SRS v4.0 UC-WAIT-01, UC-WAIT-02, UC-WAIT-03
 */

const WaiterDashboard = (function() {
    // Configuration
    const CONFIG = {
        POLLING_INTERVAL: 5000, // 5 seconds default
        MAX_BACKOFF: 20000, // 20 seconds max
        MIN_BACKOFF: 5000, // 5 seconds min
        RATE_LIMIT_MS: 3000, // 3 seconds between requests
        OVERTIME_THRESHOLD: 600000, // 10 minutes in ms (red timer)
        AUDIO_FREQUENCY: 800, // Hz
        AUDIO_DURATION: 500 // ms
    };

    // State
    let state = {
        isRequesting: false,
        lastId: 0,
        lastTimestamp: '',
        currentBackoff: CONFIG.MIN_BACKOFF,
        consecutiveErrors: 0,
        isMuted: false,
        audioContext: null,
        pollingTimer: null,
        deliveredItems: new Set(), // Track delivered items to avoid double-confirm
        readyOrders: []
    };

    // DOM Elements
    let elements = {};

    /**
     * Initialize Waiter Dashboard
     * @param {Object} options - Configuration options
     */
    function init(options) {
        // Store endpoints
        state.pollingEndpoint = options.pollingEndpoint;
        state.deliverEndpoint = options.deliverEndpoint;
        state.lastId = options.lastId || 0;
        state.lastTimestamp = options.lastTimestamp || new Date().toISOString();

        // Cache DOM elements
        elements = {
            refreshIndicator: document.getElementById('refreshIndicator'),
            muteBtn: document.getElementById('muteBtn'),
            alertSound: document.getElementById('alertSound'),
            readyOrdersContainer: document.getElementById('readyOrdersContainer'),
            inProgressOrdersContainer: document.getElementById('inProgressOrdersContainer'),
            readyCount: document.getElementById('readyCount'),
            inProgressCount: document.getElementById('inProgressCount')
        };

        // Load mute status from LocalStorage
        const savedMuteStatus = localStorage.getItem('waiter_muted');
        if (savedMuteStatus === 'true') {
            state.isMuted = true;
            if (elements.muteBtn) {
                elements.muteBtn.classList.add('muted');
                elements.muteBtn.textContent = '🔇';
            }
        }

        // Setup event listeners
        setupEventListeners();

        // Enable audio context on first user interaction (browser policy)
        enableAudioOnInteraction();

        // Start polling
        startPolling();

        // Update timers every second
        setInterval(updateTimers, 1000);

        console.log('[WaiterDashboard] Initialized with lastId:', state.lastId);
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Mute button toggle
        if (elements.muteBtn) {
            elements.muteBtn.addEventListener('click', toggleMute);
        }

        // Handle page visibility change (pause polling when tab is hidden)
        document.addEventListener('visibilitychange', handleVisibilityChange);
    }

    /**
     * Enable audio context on first user interaction
     * Required by modern browsers to play audio
     */
    function enableAudioOnInteraction() {
        const enableAudio = () => {
            if (!state.audioContext) {
                try {
                    state.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    console.log('[WaiterDashboard] Audio context enabled');
                } catch (e) {
                    console.warn('[WaiterDashboard] Audio context not supported:', e);
                }
            }

            // Remove listeners after enabling
            document.removeEventListener('click', enableAudio);
            document.removeEventListener('touchstart', enableAudio);
            document.removeEventListener('keydown', enableAudio);
        };

        document.addEventListener('click', enableAudio);
        document.addEventListener('touchstart', enableAudio);
        document.addEventListener('keydown', enableAudio);
    }

    /**
     * Toggle mute status
     */
    function toggleMute() {
        state.isMuted = !state.isMuted;
        localStorage.setItem('waiter_muted', state.isMuted.toString());

        if (elements.muteBtn) {
            elements.muteBtn.classList.toggle('muted', state.isMuted);
            elements.muteBtn.textContent = state.isMuted ? '🔇' : '🔔';
        }

        console.log('[WaiterDashboard] Mute toggled:', state.isMuted);
    }

    /**
     * Play beep sound using Web Audio API
     */
    function playBeep() {
        if (state.isMuted || !state.audioContext) {
            return;
        }

        try {
            const oscillator = state.audioContext.createOscillator();
            const gainNode = state.audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(state.audioContext.destination);

            oscillator.frequency.value = CONFIG.AUDIO_FREQUENCY;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, state.audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, state.audioContext.currentTime + CONFIG.AUDIO_DURATION / 1000);

            oscillator.start(state.audioContext.currentTime);
            oscillator.stop(state.audioContext.currentTime + CONFIG.AUDIO_DURATION / 1000);

            console.log('[WaiterDashboard] Beep played');
        } catch (e) {
            console.warn('[WaiterDashboard] Failed to play beep:', e);
        }
    }

    /**
     * Start smart polling
     */
    function startPolling() {
        if (state.pollingTimer) {
            clearTimeout(state.pollingTimer);
        }

        pollOrders();
    }

    /**
     * Poll orders from server
     */
    async function pollOrders() {
        // Deduplication: skip if request is pending
        if (state.isRequesting) {
            console.log('[WaiterDashboard] Skipping poll - request pending');
            scheduleNextPoll(CONFIG.POLLING_INTERVAL);
            return;
        }

        state.isRequesting = true;
        updateRefreshIndicator('loading');

        try {
            const response = await fetch(state.pollingEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    last_id: state.lastId,
                    last_timestamp: state.lastTimestamp
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.status === 'rate_limited') {
                console.log('[WaiterDashboard] Rate limited, retry after:', data.retry_after);
                scheduleNextPoll(data.retry_after * 1000);
                return;
            }

            if (data.status === 'success') {
                // Reset backoff on success
                state.currentBackoff = CONFIG.MIN_BACKOFF;
                state.consecutiveErrors = 0;

                // Update state
                if (data.last_id > state.lastId) {
                    state.lastId = data.last_id;
                }
                if (data.last_timestamp > state.lastTimestamp) {
                    state.lastTimestamp = data.last_timestamp;
                }

                // Process updates if there are changes
                if (data.updated && data.data && data.data.length > 0) {
                    console.log('[WaiterDashboard] Received updates:', data.count, 'tables');
                    handleUpdates(data.data);
                } else {
                    console.log('[WaiterDashboard] No updates');
                }

                updateRefreshIndicator('success');
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        } catch (error) {
            console.error('[WaiterDashboard] Polling error:', error);
            state.consecutiveErrors++;

            // Exponential backoff
            state.currentBackoff = Math.min(
                state.currentBackoff * 2,
                CONFIG.MAX_BACKOFF
            );

            updateRefreshIndicator('error');
            console.log('[WaiterDashboard] Backoff:', state.currentBackoff, 'ms, Errors:', state.consecutiveErrors);
        } finally {
            state.isRequesting = false;
            scheduleNextPoll(state.currentBackoff);
        }
    }

    /**
     * Schedule next poll
     * @param {number} delay - Delay in milliseconds
     */
    function scheduleNextPoll(delay) {
        if (state.pollingTimer) {
            clearTimeout(state.pollingTimer);
        }

        state.pollingTimer = setTimeout(pollOrders, delay);
    }

    /**
     * Handle updates from server
     * @param {Array} newData - New/updated orders
     */
    function handleUpdates(newData) {
        // Play alert sound for new ready orders
        playBeep();

        // Merge with existing orders
        const updatedOrders = mergeOrders(state.readyOrders, newData);
        state.readyOrders = updatedOrders;

        // Re-render
        renderReadyOrders(updatedOrders);

        // Update count
        if (elements.readyCount) {
            elements.readyCount.textContent = updatedOrders.length + ' meja';
        }
    }

    /**
     * Merge new orders with existing
     * @param {Array} existing - Existing orders
     * @param {Array} newData - New data from server
     * @returns {Array} Merged orders
     */
    function mergeOrders(existing, newData) {
        const merged = new Map();

        // Add existing orders
        existing.forEach(order => {
            merged.set(order.table_number, order);
        });

        // Add/update with new data
        newData.forEach(order => {
            if (merged.has(order.table_number)) {
                // Merge items
                const existingOrder = merged.get(order.table_number);
                const existingItemIds = new Set(existingOrder.items.map(i => i.id));

                order.items.forEach(item => {
                    if (!existingItemIds.has(item.id)) {
                        existingOrder.items.push(item);
                    }
                });
            } else {
                merged.set(order.table_number, order);
            }
        });

        return Array.from(merged.values());
    }

    /**
     * Render ready orders
     * @param {Array} orders - Orders to render
     */
    function renderReadyOrders(orders) {
        if (!elements.readyOrdersContainer) return;

        if (orders.length === 0) {
            elements.readyOrdersContainer.innerHTML = `
                <div class="waiter-empty-state">
                    <i>📦</i>
                    <p>Belum ada pesanan siap antar</p>
                </div>
            `;
            return;
        }

        elements.readyOrdersContainer.innerHTML = orders.map(order => createReadyCardHTML(order)).join('');
    }

    /**
     * Create HTML for ready order card
     * @param {Object} order - Order data
     * @returns {string} HTML string
     */
    function createReadyCardHTML(order) {
        const readyTime = order.items[0]?.created_at || new Date().toISOString();
        const itemsHTML = order.items.map(item => `
            <div class="waiter-item-row">
                <div class="waiter-item-info">
                    <div class="waiter-item-name">
                        <span class="waiter-item-qty">${item.quantity}x</span>
                        ${escapeHtml(item.menu_item_name || 'Item deleted')}
                    </div>
                    ${item.notes ? `
                        <div class="waiter-item-note" title="${escapeHtml(item.notes)}">
                            📝 ${escapeHtml(item.notes.substring(0, 50))}${item.notes.length > 50 ? '...' : ''}
                        </div>
                    ` : ''}
                </div>
                <span class="waiter-item-badge">SIAP</span>
            </div>
        `).join('');

        const itemIds = JSON.stringify(order.items.map(i => i.id));

        return `
            <div class="waiter-order-card" data-order-id="${order.order_id}" data-table="${order.table_number}">
                <div class="waiter-card-header">
                    <div>
                        <div class="waiter-table-code">Meja ${escapeHtml(order.table_number)}</div>
                        <div class="waiter-order-number">#${escapeHtml(order.order_number)}</div>
                    </div>
                    <div class="waiter-timer" data-ready-time="${readyTime}">
                        00:00:00
                    </div>
                </div>
                <div class="waiter-card-body">
                    ${itemsHTML}
                </div>
                <div class="waiter-card-footer">
                    <button class="waiter-deliver-btn" onclick="WaiterDashboard.deliverOrder(${itemIds})">
                        🚀 Antar ke Meja ${escapeHtml(order.table_number)}
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Deliver order items
     * @param {Array|number} itemIds - Item ID(s) to deliver
     */
    async function deliverOrder(itemIds) {
        // Convert to array if single value
        if (!Array.isArray(itemIds)) {
            itemIds = [itemIds];
        }

        // BR-41: Prevent double confirm
        const alreadyDelivered = itemIds.filter(id => state.deliveredItems.has(id));
        if (alreadyDelivered.length > 0) {
            alert('Item sudah diantar sebelumnya!');
            return;
        }

        if (!confirm(`Konfirmasi pengantaran ${itemIds.length} item?`)) {
            return;
        }

        try {
            const response = await fetch(state.deliverEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    item_ids: JSON.stringify(itemIds)
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                // Mark as delivered
                itemIds.forEach(id => state.deliveredItems.add(id));

                // Remove from UI
                removeDeliveredItems(itemIds);

                alert(`✅ ${data.delivered_count} item berhasil diantar!`);

                // Refresh immediately
                pollOrders();
            } else {
                alert('❌ Gagal mengantar: ' + (data.message || 'Error tidak diketahui'));
            }
        } catch (error) {
            console.error('[WaiterDashboard] Deliver error:', error);
            alert('❌ Terjadi kesalahan saat mengantar pesanan');
        }
    }

    /**
     * Remove delivered items from UI
     * @param {Array} itemIds - Delivered item IDs
     */
    function removeDeliveredItems(itemIds) {
        // Remove from orders
        state.readyOrders = state.readyOrders.map(order => ({
            ...order,
            items: order.items.filter(item => !itemIds.includes(item.id))
        })).filter(order => order.items.length > 0);

        // Re-render
        renderReadyOrders(state.readyOrders);

        // Update count
        if (elements.readyCount) {
            elements.readyCount.textContent = state.readyOrders.length + ' meja';
        }
    }

    /**
     * Update timers on all cards
     */
    function updateTimers() {
        const timers = document.querySelectorAll('.waiter-timer[data-ready-time]');

        timers.forEach(timer => {
            const readyTime = new Date(timer.dataset.readyTime);
            const now = new Date();
            const diff = now - readyTime;

            // Format as HH:MM:SS
            const hours = Math.floor(diff / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);

            timer.textContent = `${padZero(hours)}:${padZero(minutes)}:${padZero(seconds)}`;

            // Add overtime class if > 10 minutes
            if (diff > CONFIG.OVERTIME_THRESHOLD) {
                timer.classList.add('overtime');
            } else {
                timer.classList.remove('overtime');
            }
        });
    }

    /**
     * Update refresh indicator
     * @param {string} status - 'loading', 'success', or 'error'
     */
    function updateRefreshIndicator(status) {
        if (!elements.refreshIndicator) return;

        elements.refreshIndicator.classList.remove('error');

        if (status === 'loading') {
            elements.refreshIndicator.style.opacity = '0.5';
        } else if (status === 'success') {
            elements.refreshIndicator.style.background = '#28a745';
            elements.refreshIndicator.style.opacity = '1';
        } else if (status === 'error') {
            elements.refreshIndicator.style.background = '#dc3545';
            elements.refreshIndicator.style.opacity = '1';
        }
    }

    /**
     * Handle page visibility change
     */
    function handleVisibilityChange() {
        if (document.hidden) {
            // Pause polling when tab is hidden
            if (state.pollingTimer) {
                clearTimeout(state.pollingTimer);
                state.pollingTimer = null;
            }
            console.log('[WaiterDashboard] Polling paused (tab hidden)');
        } else {
            // Resume polling when tab is visible
            startPolling();
            console.log('[WaiterDashboard] Polling resumed (tab visible)');
        }
    }

    /**
     * Helper: Escape HTML
     * @param {string} str - String to escape
     * @returns {string} Escaped string
     */
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Helper: Pad number with zero
     * @param {number} num - Number to pad
     * @returns {string} Padded string
     */
    function padZero(num) {
        return num.toString().padStart(2, '0');
    }

    // Public API
    return {
        init,
        deliverOrder,
        toggleMute,
        playBeep
    };
})();
