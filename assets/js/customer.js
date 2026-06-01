/**
 * Customer JavaScript Module
 * Handles customer-facing operations for dine-in ordering system
 * Based on SRS v4.0 UC-CUST-01 and UC-CUST-02
 */

var CustomerLanding = (function() {
    'use strict';

    var config = {};
    var html5QrCode = null;
    var currentTableCode = null;
    var existingSession = null;
    var heartbeatInterval = null;
    var heartbeatQueue = [];

    /**
     * Initialize landing page functionality
     */
    function init(options) {
        config = $.extend({
            csrfToken: '',
            checkTableUrl: '',
            createSessionUrl: '',
            menuUrl: ''
        }, options);

        // Check browser support
        checkBrowserSupport();

        // Setup event listeners
        setupEventListeners();

        // Check for existing session in localStorage
        checkExistingSession();

        // Check URL parameters for errors
        checkUrlErrors();
    }

    /**
     * Check browser support for LocalStorage and JavaScript
     */
    function checkBrowserSupport() {
        var supportsLocalStorage = (function() {
            try {
                var test = '__storage_test__';
                localStorage.setItem(test, test);
                localStorage.removeItem(test);
                return true;
            } catch (e) {
                return false;
            }
        })();

        if (!supportsLocalStorage || !window.JSON) {
            $('#browser-warning').show();
            $('#table-form').hide();
            $('#btn-scan-qr').hide();
        }
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Table form submission
        $('#table-form').on('submit', function(e) {
            e.preventDefault();
            var tableCode = $('#table_code').val().trim().toUpperCase();
            if (tableCode) {
                validateAndEnterTable(tableCode);
            }
            return false;
        });

        // QR Scan button
        $('#btn-scan-qr').on('click', function() {
            openScanner();
        });

        // Close scanner button
        $('#btn-close-scanner').on('click', function() {
            closeScanner();
        });

        // Re-scan dialog buttons
        $('#btn-rescan-cancel').on('click', function() {
            $('#rescan-dialog').removeClass('active');
        });

        $('#btn-rescan-confirm').on('click', function() {
            $('#rescan-dialog').removeClass('active');
            if (currentTableCode) {
                validateAndEnterTable(currentTableCode);
            }
        });
    }

    /**
     * Check for existing session in localStorage
     */
    function checkExistingSession() {
        var token = localStorage.getItem('customer_token');
        if (token) {
            existingSession = {
                token: token,
                tableCode: localStorage.getItem('customer_table_code'),
                expiresAt: localStorage.getItem('customer_expires_at')
            };

            // Validate session with server
            $.ajax({
                url: config.checkTableUrl.replace('check_table', 'validate_session'),
                type: 'POST',
                data: {
                    token: token,
                    '<?= $this->security->get_csrf_token_name() ?>': config.csrfToken
                },
                success: function(response) {
                    if (response.status === 'success' && response.valid) {
                        // Valid session, redirect to menu
                        window.location.href = config.menuUrl + '?token=' + token;
                    } else {
                        // Session invalid, clear localStorage
                        clearSession();
                    }
                },
                error: function() {
                    // Server error, keep local session for now
                }
            });
        }
    }

    /**
     * Check URL for error parameters
     */
    function checkUrlErrors() {
        var urlParams = new URLSearchParams(window.location.search);
        var error = urlParams.get('error');

        if (error) {
            var message = '';
            switch (error) {
                case 'session_expired':
                    message = 'Sesi Anda telah berakhir. Silakan scan ulang.';
                    break;
                case 'session_invalid':
                    message = 'Sesi tidak valid. Silakan masukkan kode meja again.';
                    break;
                case 'table_invalid':
                    message = 'Meja tidak ditemukan atau tidak aktif.';
                    break;
                default:
                    message = 'Terjadi kesalahan. Silakan coba lagi.';
            }
            showError(message);
        }
    }

    /**
     * Validate table code and enter
     */
    function validateAndEnterTable(tableCode) {
        setLoading(true);
        hideAlerts();

        $.ajax({
            url: config.checkTableUrl,
            type: 'POST',
            data: {
                table_code: tableCode,
                '<?= $this->security->get_csrf_token_name() ?>': config.csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Table is valid
                    handleValidTable(response.data);
                } else {
                    showError(response.message);
                    setLoading(false);
                }
            },
            error: function(xhr) {
                var response = xhr.responseJSON;
                if (response && response.message) {
                    showError(response.message);
                } else {
                    showError('Terjadi kesalahan sistem. Silakan coba lagi.');
                }
                setLoading(false);
            }
        });
    }

    /**
     * Handle valid table response
     */
    function handleValidTable(data) {
        // Check if user has existing session at different table
        if (existingSession && existingSession.tableCode !== data.table_code) {
            // Show re-scan confirmation dialog
            $('#rescan-message').text(
                'Anda sedang di Meja ' + existingSession.tableCode + 
                '. Pindah ke Meja ' + data.table_code + '? Keranjang Anda akan hilang.'
            );
            $('#rescan-dialog').addClass('active');
            currentTableCode = data.table_code;
            setLoading(false);
            return;
        }

        // Check if table has existing session with open bill
        if (data.has_existing_session && data.has_open_bill) {
            if (!confirm('Meja ini memiliki pesanan aktif. Lanjutkan?')) {
                setLoading(false);
                return;
            }
        }

        // Create new session
        createSession(data.table_id);
    }

    /**
     * Create customer session
     */
    function createSession(tableId) {
        $.ajax({
            url: config.createSessionUrl,
            type: 'POST',
            data: {
                table_id: tableId,
                '<?= $this->security->get_csrf_token_name() ?>': config.csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Store token in localStorage (primary storage)
                    localStorage.setItem('customer_token', response.data.token);
                    localStorage.setItem('customer_table_id', response.data.table_id);
                    localStorage.setItem('customer_table_code', response.data.table_code || '');
                    localStorage.setItem('customer_expires_at', response.data.expires_at);
                    localStorage.setItem('customer_cart', JSON.stringify([]));

                    // Also set cookie as fallback
                    setCookie('customer_token', response.data.token, 30);

                    // Redirect to menu
                    window.location.href = response.data.redirect_url;
                } else {
                    showError(response.message);
                    setLoading(false);
                }
            },
            error: function(xhr) {
                var response = xhr.responseJSON;
                if (response && response.message) {
                    showError(response.message);
                } else {
                    showError('Gagal membuat session. Silakan coba lagi.');
                }
                setLoading(false);
            }
        });
    }

    /**
     * Open QR Scanner using html5-qrcode
     */
    function openScanner() {
        $('#qr-scanner-modal').addClass('active');

        html5QrCode = new Html5Qrcode("qr-reader");

        var config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanFailure
        ).catch(function(err) {
            showError('Gagal mengakses kamera. Pastikan izin kamera diberikan.');
            closeScanner();
        });
    }

    /**
     * Close QR Scanner
     */
    function closeScanner() {
        $('#qr-scanner-modal').removeClass('active');

        if (html5QrCode) {
            html5QrCode.stop().then(function() {
                html5QrCode.clear();
                html5QrCode = null;
            }).catch(function(err) {
                console.error('Failed to stop scanner', err);
            });
        }
    }

    /**
     * QR Scan success callback
     */
    function onScanSuccess(decodedText, decodedResult) {
        // Stop scanning
        closeScanner();

        // Extract table code from URL or plain text
        var tableCode = extractTableCode(decodedText);

        if (tableCode) {
            validateAndEnterTable(tableCode.toUpperCase());
        } else {
            showError('QR Code tidak valid. Pastikan QR Code meja yang benar.');
        }
    }

    /**
     * QR Scan failure callback
     */
    function onScanFailure(error) {
        // Console log only, don't show error to user
        console.warn('QR scan failure:', error);
    }

    /**
     * Extract table code from QR code content
     */
    function extractTableCode(text) {
        // Check if it's a URL with table parameter
        var urlMatch = text.match(/[?&]table=([A-Za-z0-9]+)/i);
        if (urlMatch) {
            return urlMatch[1];
        }

        // Check if it's just the table code
        if (/^[A-Za-z0-9]{1,10}$/.test(text)) {
            return text;
        }

        return null;
    }

    /**
     * Set cookie helper
     */
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    /**
     * Clear session from localStorage
     */
    function clearSession() {
        localStorage.removeItem('customer_token');
        localStorage.removeItem('customer_table_id');
        localStorage.removeItem('customer_table_code');
        localStorage.removeItem('customer_expires_at');
        localStorage.removeItem('customer_cart');
    }

    /**
     * Show error message
     */
    function showError(message) {
        $('#alert-error').text(message).show();
        $('#alert-success').hide();
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        $('#alert-success').text(message).show();
        $('#alert-error').hide();
    }

    /**
     * Hide all alerts
     */
    function hideAlerts() {
        $('#alert-error').hide();
        $('#alert-success').hide();
    }

    /**
     * Set loading state
     */
    function setLoading(loading) {
        $('#btn-submit').prop('disabled', loading);
        if (loading) {
            $('#btn-text').hide();
            $('#btn-spinner').removeClass('d-none');
        } else {
            $('#btn-text').show();
            $('#btn-spinner').addClass('d-none');
        }
    }

    return {
        init: init
    };
})();

// Menu Page Module
var CustomerMenu = (function() {
    'use strict';

    var config = {};
    var cart = [];
    var searchTimeout = null;
    var sessionTimeoutTimer = null;
    var offlineRetryCount = 0;
    var offlineRetryTimer = null;
    var heartbeatInterval = null;
    var heartbeatQueue = [];

    /**
     * Initialize menu page functionality
     */
    function init(options) {
        config = $.extend({
            token: '',
            tableCode: '',
            cartSyncUrl: '',
            sessionValidateUrl: '',
            sessionHeartbeatUrl: '',
            sessionExpires: ''
        }, options);

        // Load cart from localStorage
        loadCart();

        // Setup event listeners
        setupEventListeners();

        // Start session monitoring with heartbeat
        startSessionMonitoring();

        // Monitor online/offline status
        monitorConnection();

        // Update UI
        updateCartBadge();
    }

    /**
     * Load cart from localStorage or cookie fallback
     */
    function loadCart() {
        var savedCart = localStorage.getItem('customer_cart');
        
        // Fallback to cookie if localStorage is empty
        if (!savedCart) {
            savedCart = getCookie('customer_cart');
            if (savedCart) {
                console.log('Loaded cart from cookie fallback');
            }
        }
        
        if (savedCart) {
            try {
                cart = JSON.parse(savedCart);
            } catch (e) {
                console.error('Error parsing cart data:', e);
                cart = [];
            }
        }
    }
    
    /**
     * Get cookie helper
     */
    function getCookie(name) {
        var nameEQ = name + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        return null;
    }

    /**
     * Save cart to localStorage and sync to server
     */
    function saveCart() {
        try {
            localStorage.setItem('customer_cart', JSON.stringify(cart));
        } catch (e) {
            // Handle QuotaExceededError
            if (e.name === 'QuotaExceededError' || e.name === 'NS_ERROR_DOM_QUOTA_REACHED') {
                console.warn('LocalStorage quota exceeded, trying cookie fallback');
                
                // Try to store in cookie as fallback
                var cartStr = JSON.stringify(cart);
                var maxCookieSize = 4000; // Most browsers support 4KB cookies
                
                if (cartStr.length <= maxCookieSize) {
                    setCookie('customer_cart', cartStr, 7); // 7 days
                    showToast('Penyimpanan lokal penuh, menggunakan cookie', 'warning');
                } else {
                    // Cart too large for cookie, show error
                    showToast('Keranjang terlalu besar! Kurangi jumlah item.', 'error', 5000);
                    
                    // Try to reduce cart size by removing oldest items
                    if (cart.length > 1) {
                        cart = cart.slice(-Math.floor(cart.length / 2)); // Keep last half
                        try {
                            localStorage.setItem('customer_cart', JSON.stringify(cart));
                            showToast('Keranjang dikurangi otomatis', 'warning');
                        } catch (e2) {
                            // Still failing, clear cart
                            cart = [];
                            localStorage.removeItem('customer_cart');
                            document.cookie = 'customer_cart=; expires=Thu, 01 Jan 1970 00:00:00 UTC';
                            showToast('Keranjang dikosongkan karena penyimpanan penuh', 'error', 5000);
                        }
                    }
                }
            } else {
                console.error('Error saving cart:', e);
                showToast('Gagal menyimpan keranjang', 'error', 5000);
            }
        }
        syncCartToServer();
    }
    
    /**
     * Set cookie helper
     */
    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + (value || '') + expires + '; path=/';
    }

    /**
     * Sync cart to server with debounce
     */
    var syncCartToServer = debounce(function() {
        if (!navigator.onLine) {
            console.log('Offline, cart will sync when online');
            return;
        }

        $.ajax({
            url: config.cartSyncUrl,
            type: 'POST',
            data: {
                token: config.token,
                cart_data: JSON.stringify(cart),
                '<?= $this->security->get_csrf_token_name() ?>': getCsrfToken()
            },
            dataType: 'json',
            success: function(response) {
                console.log('Cart synced successfully');
            },
            error: function() {
                console.log('Cart sync failed, will retry');
            }
        });
    }, 500);

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Category tabs
        $('.category-tab').on('click', function() {
            var category = $(this).data('category');
            filterByCategory(category);
        });

        // Search input with debounce
        $('#search-input').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                filterBySearch($(this).val());
            }.bind(this), 300);
        });

        // Menu item cards
        $('.menu-item-card').on('click', function() {
            if ($(this).hasClass('disabled')) {
                return;
            }
            var itemId = $(this).data('item-id');
            openItemModal(itemId);
        });

        // Floating cart button
        $('#floating-cart').on('click', function() {
            openCartPanel();
        });

        // Close cart panel
        $('#btn-close-cart').on('click', function() {
            closeCartPanel();
        });

        $('#cart-panel-overlay').on('click', function() {
            closeCartPanel();
        });

        // Empty cart
        $('#btn-empty-cart').on('click', function() {
            if (confirm('Kosongkan semua item? Aksi ini tidak dapat dibatalkan.')) {
                cart = [];
                saveCart();
                renderCartPanel();
                updateCartBadge();
                closeCartPanel();
            }
        });

        // Order now button
        $('#btn-order-now').on('click', function() {
            submitOrder();
        });

        // Extend session button
        $('#btn-extend-session').on('click', function() {
            extendSession();
        });
    }

    /**
     * Filter menu by category
     */
    function filterByCategory(category) {
        $('.category-tab').removeClass('active');
        $('.category-tab[data-category="' + category + '"]').addClass('active');

        if (category === 'all') {
            $('.category-section').show();
        } else {
            $('.category-section[data-category="all"]').hide();
            $('.category-section[data-category="' + category + '"]').show();
            $('.category-section:not([data-category="all"]):not([data-category="' + category + '"])').hide();
        }
    }

    /**
     * Filter menu by search
     */
    function filterBySearch(query) {
        query = query.toLowerCase().trim();

        $('.menu-item-card').each(function() {
            var itemName = $(this).find('.item-name').text().toLowerCase();
            var category = $(this).find('.item-category-badge').text().toLowerCase();

            if (itemName.indexOf(query) >= 0 || category.indexOf(query) >= 0) {
                $(this).parent().show();
            } else {
                $(this).parent().hide();
            }
        });
    }

    /**
     * Open item modal (placeholder - would need modal HTML)
     */
    function openItemModal(itemId) {
        // This would open a modal with item details
        // For now, just add to cart directly
        addToCart(itemId);
    }

    /**
     * Add item to cart
     */
    function addToCart(itemId) {
        var card = $('.menu-item-card[data-item-id="' + itemId + '"]');
        var name = card.find('.item-name').text();
        var price = parseFloat(card.find('.item-price').text().replace(/[^0-9]/g, ''));
        var image = card.find('.item-image').attr('src') || '';

        // Check if item already in cart
        var existingItem = cart.find(function(item) {
            return item.menu_item_id === itemId;
        });

        if (existingItem) {
            existingItem.qty += 1;
            existingItem.subtotal = existingItem.qty * existingItem.price_snapshot;
        } else {
            cart.push({
                menu_item_id: itemId,
                qty: 1,
                notes: '',
                price_snapshot: price,
                subtotal: price,
                name: name,
                image: image
            });
        }

        saveCart();
        updateCartBadge();
        showToast(name + ' ditambahkan ke pesanan');

        // Fly-to-cart animation
        animateFlyToCart(card);
    }

    /**
     * Animate fly to cart
     */
    function animateFlyToCart(element) {
        var flyingImg = element.find('.item-image-container').clone();
        flyingImg.css({
            position: 'fixed',
            zIndex: 9999,
            width: '50px',
            height: '50px',
            transition: 'all 0.3s ease-out'
        });

        var offset = element.offset();
        var cartOffset = $('#floating-cart').offset();

        flyingImg.offset({
            top: offset.top,
            left: offset.left
        });

        $('body').append(flyingImg);

        setTimeout(function() {
            flyingImg.offset({
                top: cartOffset.top,
                left: cartOffset.left
            });
            flyingImg.css('opacity', 0);
        }, 10);

        setTimeout(function() {
            flyingImg.remove();
        }, 300);
    }

    /**
     * Open cart panel
     */
    function openCartPanel() {
        renderCartPanel();
        $('#cart-panel-overlay').addClass('active');
        $('#cart-panel').addClass('active');
    }

    /**
     * Close cart panel
     */
    function closeCartPanel() {
        $('#cart-panel').removeClass('active');
        $('#cart-panel-overlay').removeClass('active');
    }

    /**
     * Render cart panel content
     */
    function renderCartPanel() {
        var body = $('#cart-panel-body');

        if (cart.length === 0) {
            body.html(`
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Keranjang Anda kosong</p>
                </div>
            `);
            $('#btn-order-now').prop('disabled', true);
            $('#cart-total').text('Rp 0');
            return;
        }

        var html = '';
        var total = 0;

        cart.forEach(function(item, index) {
            total += item.subtotal;
            html += `
                <div class="cart-item">
                    <img src="${item.image || ''}" alt="${item.name}" class="cart-item-image" 
                         onerror="this.style.background='#f0f0f0'">
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.name}</div>
                        ${item.notes ? '<div class="cart-item-notes">' + item.notes + '</div>' : ''}
                        <div class="cart-item-controls">
                            <div class="qty-controls">
                                <button class="qty-btn" onclick="CustomerMenu.decreaseQty(${index})" ${item.qty <= 1 ? 'disabled' : ''}>
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span>${item.qty}</span>
                                <button class="qty-btn" onclick="CustomerMenu.increaseQty(${index})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span class="cart-item-price">Rp ${item.subtotal.toLocaleString('id-ID')}</span>
                                <button class="cart-item-remove" onclick="CustomerMenu.removeItem(${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        body.html(html);
        $('#cart-total').text('Rp ' + total.toLocaleString('id-ID'));
        $('#btn-order-now').prop('disabled', false);
    }

    /**
     * Increase item quantity
     */
    function increaseQty(index) {
        cart[index].qty += 1;
        cart[index].subtotal = cart[index].qty * cart[index].price_snapshot;
        saveCart();
        renderCartPanel();
        updateCartBadge();
    }

    /**
     * Decrease item quantity
     */
    function decreaseQty(index) {
        if (cart[index].qty > 1) {
            cart[index].qty -= 1;
            cart[index].subtotal = cart[index].qty * cart[index].price_snapshot;
            saveCart();
            renderCartPanel();
            updateCartBadge();
        }
    }

    /**
     * Remove item from cart
     */
    function removeItem(index) {
        if (confirm('Hapus ' + cart[index].name + ' dari pesanan?')) {
            cart.splice(index, 1);
            saveCart();
            renderCartPanel();
            updateCartBadge();
        }
    }

    /**
     * Update cart badge with bounce animation
     */
    function updateCartBadge() {
        var count = cart.reduce(function(sum, item) {
            return sum + item.qty;
        }, 0);

        var badge = $('#cart-badge');
        if (count > 0) {
            badge.text(count).removeClass('hidden');
            
            // Trigger bounce animation
            badge.removeClass('bounce');
            // Force reflow to restart animation
            void badge[0].offsetWidth;
            badge.addClass('bounce');
            
            // Remove animation class after it completes
            setTimeout(function() {
                badge.removeClass('bounce');
            }, 300);
        } else {
            badge.addClass('hidden');
        }
    }

    /**
     * Submit order
     */
    function submitOrder() {
        if (cart.length === 0) {
            return;
        }

        if (!confirm('Konfirmasi pesanan Anda?')) {
            return;
        }

        $.ajax({
            url: '/api/order/create',
            type: 'POST',
            data: {
                token: config.token,
                cart_data: JSON.stringify(cart),
                '<?= $this->security->get_csrf_token_name() ?>': getCsrfToken()
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Clear cart
                    cart = [];
                    saveCart();
                    updateCartBadge();
                    closeCartPanel();

                    // Redirect to order status page
                    window.location.href = '/customer/status?order=' + response.data.order_number;
                } else {
                    alert(response.message || 'Gagal mengirim pesanan. Silakan coba lagi.');
                }
            },
            error: function(xhr) {
                var response = xhr.responseJSON;
                alert((response && response.message) || 'Gagal mengirim pesanan. Silakan coba lagi.');
            }
        });
    }

    /**
     * Start session monitoring with heartbeat
     */
    function startSessionMonitoring() {
        checkSessionExpiry();
        sessionTimeoutTimer = setInterval(checkSessionExpiry, 10000); // Check every 10 seconds
        
        // Start heartbeat: send every 5 minutes (300000ms)
        startHeartbeat();
    }

    /**
     * Start heartbeat mechanism
     * Sends heartbeat every 5 minutes to extend session
     * Only when online, queues if offline
     */
    function startHeartbeat() {
        // Clear any existing interval
        if (heartbeatInterval) {
            clearInterval(heartbeatInterval);
        }
        
        // Send heartbeat every 5 minutes (300000 ms)
        heartbeatInterval = setInterval(function() {
            sendHeartbeat();
        }, 300000);
    }

    /**
     * Send heartbeat to server
     * Queues if offline, sends when online
     */
    function sendHeartbeat() {
        if (!navigator.onLine) {
            // Queue heartbeat for later
            heartbeatQueue.push({
                token: config.token,
                timestamp: Date.now()
            });
            console.log('Offline: heartbeat queued');
            return;
        }
        
        // Send heartbeat AJAX request
        $.ajax({
            url: config.sessionHeartbeatUrl,
            type: 'POST',
            data: {
                token: config.token,
                '<?= $this->security->get_csrf_token_name() ?>': getCsrfToken()
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    config.sessionExpires = response.data.expires_at;
                    console.log('Heartbeat successful, session extended to: ' + response.data.expires_at);
                    
                    // Process any queued heartbeats
                    processHeartbeatQueue();
                } else {
                    console.log('Heartbeat failed:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Heartbeat error:', error);
                // Queue for retry
                heartbeatQueue.push({
                    token: config.token,
                    timestamp: Date.now()
                });
            }
        });
    }

    /**
     * Process queued heartbeats when back online
     */
    function processHeartbeatQueue() {
        if (heartbeatQueue.length === 0) {
            return;
        }
        
        // Only send one heartbeat to clear queue
        var queued = heartbeatQueue.shift();
        
        $.ajax({
            url: config.sessionHeartbeatUrl,
            type: 'POST',
            data: {
                token: config.token,
                '<?= $this->security->get_csrf_token_name() ?>': getCsrfToken()
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    config.sessionExpires = response.data.expires_at;
                    console.log('Queued heartbeat processed');
                    // Continue processing remaining queue
                    setTimeout(processHeartbeatQueue, 1000);
                }
            },
            error: function() {
                // Put it back in queue
                heartbeatQueue.unshift(queued);
                console.log('Queued heartbeat failed, will retry later');
            }
        });
    }

    /**
     * Check session expiry
     */
    function checkSessionExpiry() {
        var expiresAt = new Date(config.sessionExpires);
        var now = new Date();
        var timeRemaining = Math.floor((expiresAt - now) / 1000); // seconds

        if (timeRemaining <= 60 && timeRemaining > 0) {
            // Show timeout warning modal
            $('#session-timeout-modal').addClass('active');
            startCountdown(timeRemaining);
        } else if (timeRemaining <= 0) {
            // Session expired
            handleSessionExpired();
        }
    }

    /**
     * Start countdown timer
     */
    function startCountdown(seconds) {
        var countdownEl = $('#session-countdown');
        var interval = setInterval(function() {
            seconds--;
            countdownEl.text(seconds);

            if (seconds <= 0) {
                clearInterval(interval);
                handleSessionExpired();
            }
        }, 1000);
    }

    /**
     * Extend session
     */
    function extendSession() {
        $.ajax({
            url: config.sessionHeartbeatUrl,
            type: 'POST',
            data: {
                token: config.token,
                '<?= $this->security->get_csrf_token_name() ?>': getCsrfToken()
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#session-timeout-modal').removeClass('active');
                    config.sessionExpires = response.data.expires_at;
                    showToast('Sesi diperpanjang');
                }
            },
            error: function() {
                handleSessionExpired();
            }
        });
    }

    /**
     * Handle session expired
     */
    function handleSessionExpired() {
        clearInterval(sessionTimeoutTimer);
        localStorage.removeItem('customer_token');
        localStorage.removeItem('customer_cart');
        window.location.href = '/customer?error=session_expired';
    }

    /**
     * Monitor online/offline connection with session recovery and 30-minute timeout tracking
     */
    function monitorConnection() {
        window.addEventListener('online', function() {
            var offlineSince = localStorage.getItem('offline_since');
            
            // Clear offline timestamp
            if (offlineSince) {
                localStorage.removeItem('offline_since');
                
                // Check if offline for more than 30 minutes
                var offlineDuration = Date.now() - parseInt(offlineSince);
                var thirtyMinutes = 30 * 60 * 1000; // 30 minutes in ms
                
                if (offlineDuration > thirtyMinutes) {
                    console.log('Offline for more than 30 minutes (' + Math.round(offlineDuration/60000) + ' min), full reconnection needed');
                    showToast('Terputus lama, memulihkan sesi...', 'warning');
                } else {
                    console.log('Back online after ' + Math.round(offlineDuration/1000) + ' seconds');
                }
            }
            
            $('#offline-banner').removeClass('active');
            offlineRetryCount = 0;
            
            // Sync cart when back online
            syncCartToServer();
            
            // Process queued heartbeats
            processHeartbeatQueue();
            
            // Session recovery: validate token and restore if needed
            recoverSessionAfterOffline();
        });

        window.addEventListener('offline', function() {
            // Store offline timestamp for 30-minute timeout tracking
            localStorage.setItem('offline_since', Date.now().toString());
            
            $('#offline-banner').addClass('active');
            startOfflineRetry();
            showToast('Mode offline - pesanan akan disinkronkan saat online', 'warning');
        });
    }

    /**
     * Session recovery after coming back online
     * Validates token, creates new session if expired but cart is valid
     */
    function recoverSessionAfterOffline() {
        var token = localStorage.getItem('customer_token');
        var tableCode = localStorage.getItem('customer_table_code');
        var cartData = localStorage.getItem('customer_cart');
        
        if (!token) {
            console.log('No token found for session recovery');
            return;
        }
        
        // Validate session with server
        $.ajax({
            url: config.sessionValidateUrl,
            type: 'POST',
            data: {
                token: token,
                '<?= $this->security->get_csrf_token_name() ?>': getCsrfToken()
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.valid) {
                    console.log('Session validated successfully');
                    // Session is still valid, update expiry
                    config.sessionExpires = response.data.expires_at;
                    
                    // Sync cart to server
                    if (cartData) {
                        var localCart = JSON.parse(cartData);
                        if (localCart.length > 0) {
                            syncCartToServer();
                        }
                    }
                } else if (response.expired && cartData) {
                    // Token expired but we have cart data
                    console.log('Session expired, attempting recovery with cart data');
                    
                    var localCart = JSON.parse(cartData);
                    if (localCart.length > 0) {
                        // Create new session with same table and restore cart
                        createNewSessionWithCart(tableCode, localCart);
                    } else {
                        // No cart data, redirect to landing
                        handleSessionExpired();
                    }
                } else {
                    console.log('Session invalid, clearing');
                    handleSessionExpired();
                }
            },
            error: function(xhr, status, error) {
                console.log('Session validation failed:', error);
                // Try to recover with cart data if available
                if (cartData) {
                    var localCart = JSON.parse(cartData);
                    if (localCart.length > 0) {
                        createNewSessionWithCart(tableCode, localCart);
                    }
                }
            }
        });
    }

    /**
     * Create new session with saved cart data
     */
    function createNewSessionWithCart(tableCode, cartData) {
        // First need to get table_id from table_code
        $.ajax({
            url: config.checkTableUrl.replace('check_table', 'validate_session'),
            type: 'POST',
            data: {
                token: localStorage.getItem('customer_token'),
                '<?= $this->security->get_csrf_token_name() ?>': getCsrfToken()
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data && response.data.table_id) {
                    // We have the table_id, now create new session
                    // This requires redirecting to create a new session
                    // Store cart for later sync
                    localStorage.setItem('pending_cart_sync', JSON.stringify(cartData));
                    localStorage.setItem('pending_table_id', response.data.table_id);
                    
                    // Redirect to create new session
                    window.location.href = '/customer?error=session_expired&recover=1';
                } else {
                    // Can't recover, redirect to landing
                    handleSessionExpired();
                }
            },
            error: function() {
                handleSessionExpired();
            }
        });
    }

    /**
     * Start offline retry countdown with fade animation
     */
    function startOfflineRetry() {
        var countdownEl = $('#retry-countdown');
        var bannerEl = $('#offline-banner');
        var retryDelay = 3;

        // Fade in when starting
        bannerEl.removeClass('fade-out').addClass('active');

        offlineRetryTimer = setInterval(function() {
            retryDelay--;
            countdownEl.text('(' + retryDelay + ')');

            if (retryDelay <= 0) {
                clearInterval(offlineRetryTimer);
                if (navigator.onLine) {
                    // Fade out before hiding
                    bannerEl.addClass('fade-out');
                    setTimeout(function() {
                        bannerEl.removeClass('active').removeClass('fade-out');
                    }, 300);
                } else {
                    retryDelay = 3;
                    startOfflineRetry();
                }
            }
        }, 1000);
    }

    /**
     * Show toast notification
     * @param {string} message - Toast message
     * @param {string} type - Type: 'success', 'error', 'warning' (default: 'success')
     * @param {number} duration - Duration in ms (default: 3000 for success, 5000 for error)
     */
    function showToast(message, type, duration) {
        type = type || 'success';
        
        // Set default duration based on type
        if (duration === undefined) {
            duration = (type === 'error') ? 5000 : 3000;
        }
        
        var bgColor = 'rgba(0,0,0,0.8)';
        if (type === 'success') {
            bgColor = 'rgba(46, 204, 113, 0.95)'; // Green
        } else if (type === 'error') {
            bgColor = 'rgba(231, 76, 60, 0.95)'; // Red
        } else if (type === 'warning') {
            bgColor = 'rgba(241, 196, 15, 0.95)'; // Yellow
        }
        
        var toast = $('<div class="toast-notification">' + message + '</div>');
        toast.css({
            position: 'fixed',
            bottom: '80px',
            left: '50%',
            transform: 'translateX(-50%)',
            background: bgColor,
            color: (type === 'warning') ? '#000' : 'white',
            padding: '12px 24px',
            borderRadius: '25px',
            fontSize: '14px',
            fontWeight: '500',
            zIndex: 10000,
            opacity: 0,
            transition: 'opacity 0.3s',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
        });

        $('body').append(toast);

        setTimeout(function() {
            toast.css('opacity', 1);
        }, 10);

        setTimeout(function() {
            toast.css('opacity', 0);
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, duration);
    }

    /**
     * Debounce utility function
     */
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    /**
     * Get CSRF token
     */
    function getCsrfToken() {
        var name = '<?= $this->security->get_csrf_token_name() ?>' + '=';
        var decodedCookie = decodeURIComponent(document.cookie);
        var ca = decodedCookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return '';
    }

    return {
        init: init,
        increaseQty: increaseQty,
        decreaseQty: decreaseQty,
        removeItem: removeItem
    };
})();
