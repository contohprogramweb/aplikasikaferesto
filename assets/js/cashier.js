/**
 * Cashier Dashboard JavaScript
 * Handles table grid, billing details, discount, payment, and receipt printing
 * Based on SRS v4.0 UC-CASH-01 to UC-CASH-05
 */

// Global state
let cashierState = {
    tables: [],
    filteredTables: [],
    lastId: 0,
    lastTimestamp: '',
    pollingInterval: null,
    selectedTableId: null,
    currentOrders: [],
    isRequesting: false,
    errorCount: 0,
    pollIntervalMs: 5000,
    maxPollIntervalMs: 20000,
    minPollIntervalMs: 3000 // Rate limit: max 1 req/3 detik
};

// Initialize dashboard
function initCashierDashboard() {
    setupEventListeners();
    setupKeyboardShortcuts();
    startPolling();
}

// Setup event listeners
function setupEventListeners() {
    // Search input with debounce
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                filterTables();
            }, 300);
        });
    }
    
    // Status filter
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterTables);
    }
    
    // Modal backdrop clicks
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function() {
            closeAllModals();
        });
    });
    
    // Reason char count
    const reasonTextarea = document.getElementById('discountReason');
    if (reasonTextarea) {
        reasonTextarea.addEventListener('input', function() {
            document.getElementById('reasonCharCount').textContent = this.value.length;
        });
    }
}

// Keyboard shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // F1 = Fokus Bayar
        if (e.key === 'F1') {
            e.preventDefault();
            const paymentModal = document.getElementById('paymentModal');
            if (paymentModal && paymentModal.style.display !== 'none') {
                document.getElementById('paymentMethod').focus();
            }
        }
        
        // F2 = Diskon
        if (e.key === 'F2') {
            e.preventDefault();
            openDiscountModal();
        }
        
        // Esc = Tutup Modal
        if (e.key === 'Escape') {
            closeAllModals();
        }
        
        // Enter = Konfirmasi (saat modal aktif)
        if (e.key === 'Enter') {
            const discountModal = document.getElementById('discountModal');
            const paymentModal = document.getElementById('paymentModal');
            
            if (discountModal && discountModal.style.display !== 'none') {
                e.preventDefault();
                applyDiscount();
            } else if (paymentModal && paymentModal.style.display !== 'none') {
                e.preventDefault();
                processPayment();
            }
        }
    });
}

// Smart polling with exponential backoff
function startPolling() {
    pollTables();
    
    cashierState.pollingInterval = setInterval(() => {
        pollTables();
    }, cashierState.pollIntervalMs);
}

function pollTables() {
    // Deduplication: skip if request pending
    if (cashierState.isRequesting) {
        return;
    }
    
    // Rate limit check
    const now = Date.now();
    if (cashierState.lastRequestTime && (now - cashierState.lastRequestTime) < cashierState.minPollIntervalMs) {
        return;
    }
    
    cashierState.isRequesting = true;
    cashierState.lastRequestTime = now;
    
    fetch(CASHIER_CONFIG.baseUrl + 'api/cashier/tables', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            last_id: cashierState.lastId,
            last_timestamp: cashierState.lastTimestamp,
            [csrfTokenName]: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reset error count on success
            cashierState.errorCount = 0;
            cashierState.pollIntervalMs = cashierState.minPollIntervalMs;
            
            // Update state
            cashierState.lastId = data.last_id;
            cashierState.lastTimestamp = data.last_timestamp;
            cashierState.tables = data.tables;
            
            // Render tables
            renderTablesGrid();
            updateConnectionStatus(true);
            
            // Refresh detail modal if open
            if (cashierState.selectedTableId) {
                refreshTableDetail();
            }
        } else {
            handleError(data.message || 'Gagal mengambil data');
        }
    })
    .catch(error => {
        console.error('Polling error:', error);
        handleError('Koneksi gagal');
    })
    .finally(() => {
        cashierState.isRequesting = false;
    });
}

function handleError(message) {
    cashierState.errorCount++;
    updateConnectionStatus(false);
    
    // Exponential backoff: 5s → 10s → 20s (max)
    cashierState.pollIntervalMs = Math.min(
        cashierState.pollIntervalMs * 2,
        cashierState.maxPollIntervalMs
    );
    
    // Restart polling with new interval
    clearInterval(cashierState.pollingInterval);
    cashierState.pollingInterval = setInterval(() => {
        pollTables();
    }, cashierState.pollIntervalMs);
}

function updateConnectionStatus(connected) {
    const dot = document.getElementById('connectionDot');
    if (dot) {
        dot.className = 'status-dot ' + (connected ? 'connected' : 'disconnected');
    }
}

// Filter tables based on search and status
function filterTables() {
    const searchTerm = document.getElementById('tableSearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    
    cashierState.filteredTables = cashierState.tables.filter(table => {
        // Search filter
        const matchesSearch = !searchTerm || 
            table.table_number.toLowerCase().includes(searchTerm) ||
            (table.location && table.location.toLowerCase().includes(searchTerm));
        
        // Status filter
        const matchesStatus = !statusFilter || table.status === statusFilter;
        
        return matchesSearch && matchesStatus;
    });
    
    renderTablesGrid();
}

// Render tables grid
function renderTablesGrid() {
    const grid = document.getElementById('tablesGrid');
    if (!grid) return;
    
    const tablesToRender = cashierState.filteredTables.length > 0 
        ? cashierState.filteredTables 
        : cashierState.tables;
    
    if (tablesToRender.length === 0) {
        grid.innerHTML = '<div class="empty-state"><p>Tidak ada data meja</p></div>';
        return;
    }
    
    grid.innerHTML = tablesToRender.map(table => {
        const statusClass = getStatusClass(table.status);
        const durationFormatted = formatDuration(table.duration_seconds);
        const isOvertime = table.duration_seconds > 1800; // >30 menit
        
        return `
            <div class="table-card ${statusClass}" onclick="openTableDetail(${table.id})">
                <div class="table-header">
                    <span class="table-number">${escapeHtml(table.table_number)}</span>
                    <span class="status-badge ${statusClass}">${getStatusText(table.status)}</span>
                </div>
                <div class="table-body">
                    <div class="table-info">
                        <span class="info-label">Pesanan:</span>
                        <span class="info-value">${table.active_orders_count}</span>
                    </div>
                    <div class="table-info">
                        <span class="info-label">Item:</span>
                        <span class="info-value">${table.items_count}</span>
                    </div>
                    <div class="table-info">
                        <span class="info-label">Durasi:</span>
                        <span class="info-value duration ${isOvertime ? 'overtime' : ''}">${durationFormatted}</span>
                    </div>
                </div>
                <div class="table-footer">
                    <span class="total-amount">Rp ${formatNumber(table.total_amount)}</span>
                </div>
                ${table.bill_requested ? '<div class="bill-requested-indicator">🔔</div>' : ''}
            </div>
        `;
    }).join('');
}

function getStatusClass(status) {
    const classes = {
        'available': 'status-available',
        'occupied': 'status-occupied',
        'pending_payment': 'status-pending',
        'cleaning': 'status-cleaning',
        'maintenance': 'status-maintenance'
    };
    return classes[status] || '';
}

function getStatusText(status) {
    const texts = {
        'available': 'Tersedia',
        'occupied': 'Terisi',
        'pending_payment': 'Menunggu Bayar',
        'cleaning': 'Dibersihkan',
        'maintenance': 'Tutup'
    };
    return texts[status] || status;
}

function formatDuration(seconds) {
    if (!seconds) return '00:00';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

// Open table detail modal
function openTableDetail(tableId) {
    cashierState.selectedTableId = tableId;
    
    fetch(CASHIER_CONFIG.baseUrl + 'api/cashier/detail', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            table_id: tableId,
            [csrfTokenName]: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderTableDetail(data.table, data.orders);
            showModal('tableDetailModal');
        } else {
            alert(data.message || 'Gagal mengambil detail');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

function renderTableDetail(table, orders) {
    document.getElementById('modalTableNumber').textContent = table.table_number;
    cashierState.currentOrders = orders;
    
    const container = document.getElementById('activeOrdersContainer');
    if (!container) return;
    
    if (orders.length === 0) {
        container.innerHTML = '<div class="empty-state"><p>Tidak ada pesanan aktif</p></div>';
        updateSummary(0, 0, 0, 0);
        return;
    }
    
    // Group by order for multiple orders support
    container.innerHTML = orders.map((order, index) => `
        <div class="order-section">
            <div class="order-header">
                <span class="order-number">Order #${escapeHtml(order.order_number)}</span>
                <span class="order-status badge">${order.status}</span>
                <span class="order-time">${formatDateTime(order.created_at)}</span>
            </div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Item</th>
                        <th>Qty</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                        <th>Catatan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${order.items.map((item, idx) => {
                        const isCanceled = item.status === 'canceled' || item.status === 'batal';
                        const rowClass = isCanceled ? 'canceled-item' : '';
                        const opacityStyle = isCanceled ? 'style="opacity: 0.5;"' : '';
                        return `
                        <tr class="${rowClass}" ${opacityStyle}>
                            <td>${idx + 1}</td>
                            <td>${isCanceled ? '<s>' : ''}${escapeHtml(item.menu_item_name || item.name)}${isCanceled ? '</s>' : ''}</td>
                            <td>${isCanceled ? '<s>' : ''}${item.quantity}${isCanceled ? '</s>' : ''}</td>
                            <td>Rp ${formatNumber(item.price)}</td>
                            <td>${isCanceled ? '<s>' : ''}Rp ${formatNumber(item.subtotal)}${isCanceled ? '</s>' : ''}</td>
                            <td>${item.notes ? escapeHtml(item.notes) : '-'}</td>
                            <td><span class="badge badge-${item.status}">${item.status}</span></td>
                        </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        </div>
    `).join('');
    
    // Calculate totals
    let subtotal = 0;
    let discount = 0;
    let taxAmount = 0;
    let serviceAmount = 0;
    
    orders.forEach(order => {
        if (order.totals) {
            subtotal += order.totals.subtotal || 0;
            taxAmount += order.totals.tax_amount || 0;
            serviceAmount += order.totals.service_amount || 0;
        }
        discount += order.discount_amount || 0;
    });
    
    const grandTotal = subtotal + taxAmount + serviceAmount - discount;
    updateSummary(subtotal, discount, taxAmount + serviceAmount, grandTotal);
}

function updateSummary(subtotal, discount, taxService, grandTotal) {
    document.getElementById('summarySubtotal').textContent = 'Rp ' + formatNumber(subtotal);
    document.getElementById('summaryDiscount').textContent = 'Rp ' + formatNumber(discount);
    document.getElementById('summaryTaxService').textContent = 'Rp ' + formatNumber(taxService);
    document.getElementById('summaryGrandTotal').textContent = 'Rp ' + formatNumber(grandTotal);
}

function refreshTableDetail() {
    if (cashierState.selectedTableId) {
        openTableDetail(cashierState.selectedTableId);
    }
}

// Discount functions
function openDiscountModal() {
    if (!cashierState.currentOrders || cashierState.currentOrders.length === 0) {
        alert('Tidak ada pesanan aktif');
        return;
    }
    
    // Use first order for discount (or implement multi-order discount)
    const order = cashierState.currentOrders[0];
    document.getElementById('discountOrderId').value = order.id;
    document.getElementById('discountValue').value = '';
    document.getElementById('discountReason').value = '';
    document.getElementById('reasonCharCount').textContent = '0';
    document.getElementById('discountError').style.display = 'none';
    
    // Set preview values
    const subtotal = order.totals?.subtotal || 0;
    document.getElementById('previewSubtotal').textContent = 'Rp ' + formatNumber(subtotal);
    
    toggleDiscountInput();
    showModal('discountModal');
}

function closeDiscountModal() {
    hideModal('discountModal');
}

function toggleDiscountInput() {
    const type = document.querySelector('input[name="discount_type"]:checked').value;
    const label = document.getElementById('discountValueLabel');
    const input = document.getElementById('discountValue');
    
    if (type === 'percentage') {
        label.textContent = 'Nilai Diskon (%)';
        input.min = 0;
        input.max = 100;
        input.step = 0.01;
    } else {
        label.textContent = 'Nilai Diskon (Rp)';
        input.min = 0;
        input.max = '';
        input.step = 1000;
    }
    
    calculateDiscountPreview();
}

function calculateDiscountPreview() {
    const order = cashierState.currentOrders?.[0];
    if (!order) return;
    
    const subtotal = order.totals?.subtotal || 0;
    const type = document.querySelector('input[name="discount_type"]:checked').value;
    const value = parseFloat(document.getElementById('discountValue').value) || 0;
    
    let discountAmount = 0;
    if (type === 'percentage') {
        discountAmount = (subtotal * value) / 100;
    } else {
        discountAmount = value;
    }
    
    // Validate
    if (discountAmount > subtotal) {
        document.getElementById('discountError').textContent = 'Diskon tidak boleh melebihi subtotal';
        document.getElementById('discountError').style.display = 'block';
        return;
    }
    document.getElementById('discountError').style.display = 'none';
    
    const grandTotal = subtotal - discountAmount;
    
    document.getElementById('previewDiscount').textContent = '- Rp ' + formatNumber(discountAmount);
    document.getElementById('previewGrandTotal').textContent = 'Rp ' + formatNumber(grandTotal);
}

function applyDiscount() {
    const orderId = document.getElementById('discountOrderId').value;
    const type = document.querySelector('input[name="discount_type"]:checked').value;
    const value = parseFloat(document.getElementById('discountValue').value) || 0;
    const reason = document.getElementById('discountReason').value;
    
    if (!orderId || value <= 0) {
        alert('Mohon lengkapi data diskon');
        return;
    }
    
    fetch(CASHIER_CONFIG.baseUrl + 'api/cashier/apply_discount', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            order_id: orderId,
            discount_type: type,
            discount_value: value,
            reason: reason,
            [csrfTokenName]: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Diskon berhasil diterapkan');
            closeDiscountModal();
            refreshTableDetail();
        } else {
            alert(data.message || 'Gagal menerapkan diskon');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

// Payment functions
function openPaymentModal() {
    if (!cashierState.currentOrders || cashierState.currentOrders.length === 0) {
        alert('Tidak ada pesanan aktif');
        return;
    }
    
    const order = cashierState.currentOrders[0];
    document.getElementById('paymentOrderId').value = order.id;
    document.getElementById('paymentMethod').value = '';
    document.getElementById('amountPaid').value = '';
    document.getElementById('paymentError').style.display = 'none';
    document.getElementById('cashInputSection').style.display = 'none';
    
    // Calculate grand total
    const subtotal = order.totals?.subtotal || 0;
    const discount = order.discount_amount || 0;
    const taxService = (order.totals?.tax_amount || 0) + (order.totals?.service_amount || 0);
    const grandTotal = subtotal + taxService - discount;
    
    document.getElementById('paymentGrandTotal').textContent = 'Rp ' + formatNumber(grandTotal);
    
    showModal('paymentModal');
}

function closePaymentModal() {
    hideModal('paymentModal');
}

function toggleCashInput() {
    const method = document.getElementById('paymentMethod').value;
    const cashSection = document.getElementById('cashInputSection');
    
    if (method === 'cash') {
        cashSection.style.display = 'block';
    } else {
        cashSection.style.display = 'none';
    }
}

function calculateChange() {
    const grandTotal = parseFloat(document.getElementById('paymentGrandTotal').textContent.replace(/[^0-9]/g, '')) || 0;
    const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
    const change = amountPaid - grandTotal;
    
    const changeEl = document.getElementById('changeAmount');
    changeEl.textContent = 'Rp ' + formatNumber(change);
    changeEl.className = 'change-amount ' + (change >= 0 ? 'text-success' : 'text-danger');
}

function setQuickAmount(type) {
    const grandTotal = parseFloat(document.getElementById('paymentGrandTotal').textContent.replace(/[^0-9]/g, '')) || 0;
    
    if (type === 'exact') {
        document.getElementById('amountPaid').value = grandTotal;
    }
    calculateChange();
}

function addQuickAmount(amount) {
    const current = parseFloat(document.getElementById('amountPaid').value) || 0;
    document.getElementById('amountPaid').value = current + amount;
    calculateChange();
}

function processPayment() {
    const orderId = document.getElementById('paymentOrderId').value;
    const method = document.getElementById('paymentMethod').value;
    const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;
    
    if (!orderId || !method) {
        alert('Mohon lengkapi data pembayaran');
        return;
    }
    
    if (method === 'cash' && amountPaid <= 0) {
        alert('Jumlah uang harus diisi untuk pembayaran tunai');
        return;
    }
    
    fetch(CASHIER_CONFIG.baseUrl + 'api/cashier/pay', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            order_id: orderId,
            payment_method: method,
            amount_paid: amountPaid,
            [csrfTokenName]: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Pembayaran berhasil!\nKembalian: Rp ' + formatNumber(data.change_amount || 0));
            closePaymentModal();
            closeTableDetail();
            
            // Open receipt in new tab
            if (confirm('Cetak struk sekarang?')) {
                window.open(data.receipt_url, '_blank');
            }
        } else {
            document.getElementById('paymentError').textContent = data.message || 'Gagal memproses pembayaran';
            document.getElementById('paymentError').style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

function printReceipt() {
    const orderId = document.getElementById('paymentOrderId').value || cashierState.currentOrders?.[0]?.id;
    if (!orderId) {
        alert('Tidak ada pesanan untuk dicetak');
        return;
    }
    
    window.open(CASHIER_CONFIG.baseUrl + 'cashier/print_receipt/' + orderId, '_blank');
}

// Utility functions
function showModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = '';
}

function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
    document.body.style.overflow = '';
}

function closeTableDetail() {
    cashierState.selectedTableId = null;
    hideModal('tableDetailModal');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            
            // Remove active from all tabs
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            // Add active to selected
            this.classList.add('active');
            document.getElementById(tabId.replace('-', '') + 'Tab')?.classList.add('active');
        });
    });
});
