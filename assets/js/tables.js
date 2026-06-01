/**
 * Tables Dashboard JavaScript
 * Handles table grid interactions and clean table confirmation
 * Based on SRS v4.0 UC-WAIT-03
 */

const TablesDashboard = (function() {
    // State
    let state = {
        cleanTableEndpoint: '',
        currentModalTable: null
    };

    // DOM Elements
    let elements = {};

    /**
     * Initialize Tables Dashboard
     * @param {Object} options - Configuration options
     */
    function init(options) {
        state.cleanTableEndpoint = options.cleanTableEndpoint;

        // Cache DOM elements
        elements = {
            modalOverlay: document.getElementById('tableModalOverlay'),
            modalTitle: document.getElementById('modalTitle'),
            modalBody: document.getElementById('modalBody')
        };

        // Setup event listeners
        setupEventListeners();

        console.log('[TablesDashboard] Initialized');
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Close modal on overlay click
        if (elements.modalOverlay) {
            elements.modalOverlay.addEventListener('click', function(e) {
                if (e.target === elements.modalOverlay) {
                    closeModal();
                }
            });
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    }

    /**
     * Show table detail modal
     * @param {Object} table - Table data
     */
    function showTableDetail(table) {
        state.currentModalTable = table;

        if (!elements.modalOverlay || !elements.modalTitle || !elements.modalBody) {
            console.error('[TablesDashboard] Modal elements not found');
            return;
        }

        // Set title
        elements.modalTitle.textContent = 'Meja ' + table.table_number;

        // Build detail content
        const statusLabels = {
            'available': 'Tersedia',
            'occupied': 'Terisi',
            'waiting_payment': 'Menunggu Bayar',
            'cleaning': 'Dibersihkan',
            'maintenance': 'Tutup/Rusak'
        };

        const statusColors = {
            'available': '#28a745',
            'occupied': '#dc3545',
            'waiting_payment': '#ffc107',
            'cleaning': '#17a2b8',
            'maintenance': '#6c757d'
        };

        elements.modalBody.innerHTML = `
            <div class="table-detail-row">
                <span class="table-detail-label">Nomor Meja</span>
                <span class="table-detail-value">${escapeHtml(table.table_number)}</span>
            </div>
            <div class="table-detail-row">
                <span class="table-detail-label">Nama Meja</span>
                <span class="table-detail-value">${escapeHtml(table.table_name || '-')}</span>
            </div>
            <div class="table-detail-row">
                <span class="table-detail-label">Status</span>
                <span class="table-detail-value" style="color: ${statusColors[table.status] || '#6c757d'}">
                    ${statusLabels[table.status] || table.status}
                </span>
            </div>
            <div class="table-detail-row">
                <span class="table-detail-label">Kapasitas</span>
                <span class="table-detail-value">${table.capacity || '-'} orang</span>
            </div>
            <div class="table-detail-row">
                <span class="table-detail-label">Lokasi</span>
                <span class="table-detail-value">${escapeHtml(table.location || '-')}</span>
            </div>
            <div class="table-detail-row">
                <span class="table-detail-label">Pesanan Aktif</span>
                <span class="table-detail-value">${table.active_orders || 0}</span>
            </div>
            ${table.status === 'cleaning' ? `
                <div style="margin-top: 20px;">
                    <button class="table-clean-btn" onclick="TablesDashboard.confirmClean(${table.id}, '${escapeHtml(table.table_number)}')">
                        ✅ Meja Sudah Bersih
                    </button>
                </div>
            ` : ''}
        `;

        // Show modal
        elements.modalOverlay.classList.add('active');

        console.log('[TablesDashboard] Showing detail for table:', table.table_number);
    }

    /**
     * Close table detail modal
     */
    function closeModal() {
        if (elements.modalOverlay) {
            elements.modalOverlay.classList.remove('active');
        }

        state.currentModalTable = null;

        console.log('[TablesDashboard] Modal closed');
    }

    /**
     * Confirm table cleaning
     * @param {number} tableId - Table ID
     * @param {string} tableNumber - Table number for display
     */
    async function confirmClean(tableId, tableNumber) {
        if (!confirm(`Konfirmasi meja ${tableNumber} sudah dibersihkan dan siap digunakan?`)) {
            return;
        }

        try {
            const response = await fetch(state.cleanTableEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    table_id: tableId
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                alert(`✅ Meja ${tableNumber} berhasil ditandai sebagai tersedia!`);

                // Close modal if open
                closeModal();

                // Reload page to update grid
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                alert('❌ Gagal: ' + (data.message || 'Error tidak diketahui'));
            }
        } catch (error) {
            console.error('[TablesDashboard] Clean table error:', error);
            alert('❌ Terjadi kesalahan saat mengubah status meja');
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

    // Public API
    return {
        init,
        showTableDetail,
        closeModal,
        confirmClean
    };
})();
