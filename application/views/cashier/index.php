<div class="cashier-dashboard">
    <!-- Sticky Header -->
    <header class="cashier-header sticky-header">
        <div class="header-left">
            <h1 class="page-title">Dashboard Kasir</h1>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($user['username'] ?? 'Cashier') ?></span>
                <span class="user-role badge badge-primary"><?= ucfirst($user['role']) ?></span>
            </div>
        </div>
        <div class="header-right">
            <div class="filter-controls">
                <input type="text" id="tableSearch" class="form-control search-input" placeholder="Cari kode meja..." autocomplete="off">
                <select id="statusFilter" class="form-control status-filter">
                    <option value="">Semua Status</option>
                    <option value="available">Tersedia (Hijau)</option>
                    <option value="occupied">Terisi (Merah)</option>
                    <option value="pending_payment">Menunggu Bayar (Kuning)</option>
                    <option value="cleaning">Dibersihkan (Biru)</option>
                    <option value="maintenance">Tutup/Rusak (Abu)</option>
                </select>
            </div>
            <div class="connection-status">
                <span class="status-dot" id="connectionDot"></span>
                <span class="status-text">Auto-refresh</span>
            </div>
        </div>
    </header>

    <!-- Tables Grid -->
    <main class="tables-grid-container">
        <div id="tablesGrid" class="tables-grid">
            <!-- Cards will be rendered by JavaScript -->
            <div class="loading-state">
                <div class="spinner"></div>
                <p>Memuat data meja...</p>
            </div>
        </div>
    </main>

    <!-- Table Detail Modal -->
    <div id="tableDetailModal" class="modal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 class="modal-title">Detail Tagihan - <span id="modalTableNumber"></span></h2>
                <button class="modal-close" onclick="closeTableDetail()">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Tabs -->
                <div class="tab-container">
                    <div class="tab-buttons">
                        <button class="tab-btn active" data-tab="active-orders">Pesanan Aktif</button>
                        <button class="tab-btn" data-tab="order-history">Riwayat</button>
                    </div>
                    
                    <!-- Active Orders Tab -->
                    <div class="tab-content active" id="activeOrdersTab">
                        <div id="activeOrdersContainer">
                            <!-- Orders will be rendered here -->
                        </div>
                        
                        <!-- Summary Section -->
                        <div class="order-summary">
                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span id="summarySubtotal">Rp 0</span>
                            </div>
                            <div class="summary-row">
                                <span>Diskon:</span>
                                <div class="discount-editable">
                                    <span id="summaryDiscount">Rp 0</span>
                                    <button class="btn btn-sm btn-outline" onclick="openDiscountModal()">Edit</button>
                                </div>
                            </div>
                            <div class="summary-row">
                                <span>Pajak/Service:</span>
                                <span id="summaryTaxService">Rp 0</span>
                            </div>
                            <div class="summary-row grand-total">
                                <span>Grand Total:</span>
                                <span id="summaryGrandTotal" class="grand-total-amount">Rp 0</span>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button class="btn btn-secondary" onclick="openDiscountModal()">
                                <i class="icon-discount"></i> Terapkan Diskon
                            </button>
                            <button class="btn btn-primary btn-large" onclick="openPaymentModal()">
                                <i class="icon-payment"></i> Proses Pembayaran
                            </button>
                            <button class="btn btn-success" onclick="printReceipt()">
                                <i class="icon-print"></i> Cetak Struk
                            </button>
                        </div>
                    </div>
                    
                    <!-- Order History Tab -->
                    <div class="tab-content" id="orderHistoryTab">
                        <p>Riwayat pesanan akan ditampilkan di sini.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Discount Modal -->
    <div id="discountModal" class="modal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-md">
            <div class="modal-header">
                <h2 class="modal-title">Terapkan Diskon</h2>
                <button class="modal-close" onclick="closeDiscountModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="discountForm">
                    <input type="hidden" id="discountOrderId" value="">
                    
                    <div class="form-group">
                        <label>Jenis Diskon</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="discount_type" value="percentage" checked onchange="toggleDiscountInput()">
                                Persentase (%)
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="discount_type" value="fixed" onchange="toggleDiscountInput()">
                                Nominal (Rp)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label id="discountValueLabel">Nilai Diskon (%)</label>
                        <input type="number" id="discountValue" class="form-control" min="0" max="100" step="0.01" oninput="calculateDiscountPreview()">
                    </div>
                    
                    <div class="form-group">
                        <label>Alasan/Keterangan (opsional)</label>
                        <textarea id="discountReason" class="form-control" maxlength="200" rows="3" placeholder="Maksimal 200 karakter"></textarea>
                        <small class="form-text"><span id="reasonCharCount">0</span>/200</small>
                    </div>
                    
                    <div class="discount-preview">
                        <div class="preview-row">
                            <span>Subtotal:</span>
                            <span id="previewSubtotal">Rp 0</span>
                        </div>
                        <div class="preview-row discount-amount">
                            <span>Diskon:</span>
                            <span id="previewDiscount" class="text-danger">- Rp 0</span>
                        </div>
                        <div class="preview-row grand-total">
                            <span>Grand Total Baru:</span>
                            <span id="previewGrandTotal" class="text-primary">Rp 0</span>
                        </div>
                    </div>
                    
                    <div id="discountError" class="alert alert-danger" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDiscountModal()">Batal</button>
                <button class="btn btn-primary" onclick="applyDiscount()">Terapkan Diskon</button>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 class="modal-title">Proses Pembayaran</h2>
                <button class="modal-close" onclick="closePaymentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" id="paymentOrderId" value="">
                    
                    <div class="payment-grand-total">
                        <span class="total-label">Grand Total</span>
                        <span class="total-amount" id="paymentGrandTotal">Rp 0</span>
                    </div>
                    
                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select id="paymentMethod" class="form-control" onchange="toggleCashInput()">
                            <option value="">-- Pilih Metode --</option>
                            <option value="cash">Tunai</option>
                            <option value="debit">Kartu Debit</option>
                            <option value="credit">Kartu Kredit</option>
                            <option value="qris">QRIS</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    
                    <!-- Cash Input Section -->
                    <div id="cashInputSection" style="display: none;">
                        <div class="form-group">
                            <label>Jumlah Uang Diterima</label>
                            <input type="number" id="amountPaid" class="form-control amount-input" min="0" step="1000" oninput="calculateChange()">
                            
                            <!-- Quick Amount Buttons -->
                            <div class="quick-amounts">
                                <button type="button" class="btn btn-sm btn-outline" onclick="setQuickAmount('exact')">Uang Pas</button>
                                <button type="button" class="btn btn-sm btn-outline" onclick="addQuickAmount(5000)">+5rb</button>
                                <button type="button" class="btn btn-sm btn-outline" onclick="addQuickAmount(10000)">+10rb</button>
                                <button type="button" class="btn btn-sm btn-outline" onclick="addQuickAmount(50000)">+50rb</button>
                            </div>
                        </div>
                        
                        <div class="change-display">
                            <span>Kembalian:</span>
                            <span id="changeAmount" class="change-amount">Rp 0</span>
                        </div>
                    </div>
                    
                    <div id="paymentError" class="alert alert-danger" style="display: none;"></div>
                    
                    <div class="payment-confirm">
                        <p>Yakin menyelesaikan pembayaran?</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closePaymentModal()">Batal</button>
                <button class="btn btn-success btn-large" onclick="processPayment()">Konfirmasi Pembayaran</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initCashierDashboard();
});
</script>
