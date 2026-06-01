<style>
/* Tables Grid Styles */
.tables-dashboard {
    padding: 20px;
}

.tables-stats-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.tables-stat-card {
    background: white;
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #0d6efd;
}

.tables-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #0d6efd;
}

.tables-stat-label {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

/* Tables Grid */
.tables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 15px;
}

@media (min-width: 1400px) {
    .tables-grid {
        grid-template-columns: repeat(6, 1fr);
    }
}

@media (min-width: 1200px) and (max-width: 1399px) {
    .tables-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

@media (min-width: 992px) and (max-width: 1199px) {
    .tables-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (min-width: 768px) and (max-width: 991px) {
    .tables-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 767px) {
    .tables-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: all 0.3s;
    cursor: pointer;
    position: relative;
}

.table-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.table-card-header {
    padding: 15px;
    text-align: center;
    color: white;
    font-weight: 700;
    font-size: 18px;
}

.table-card-body {
    padding: 12px;
    text-align: center;
}

.table-card-status {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 12px;
    display: inline-block;
    font-weight: 600;
    margin-bottom: 8px;
}

.table-card-orders {
    font-size: 13px;
    color: #6c757d;
}

.table-card-orders strong {
    color: #212529;
    font-size: 16px;
}

/* Status Colors */
.table-card.available .table-card-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.table-card.available .table-card-status {
    background: #d4edda;
    color: #155724;
}

.table-card.occupied .table-card-header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.table-card.occupied .table-card-status {
    background: #f8d7da;
    color: #721c24;
}

.table-card.waiting_payment .table-card-header {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
}

.table-card.waiting_payment .table-card-status {
    background: #fff3cd;
    color: #856404;
}

.table-card.cleaning .table-card-header {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.table-card.cleaning .table-card-status {
    background: #d1ecf1;
    color: #0c5460;
}

.table-card.maintenance .table-card-header {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
}

.table-card.maintenance .table-card-status {
    background: #e2e3e5;
    color: #383d41;
}

/* Clean button for cleaning tables */
.table-clean-btn {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.table-clean-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40,167,69,0.4);
}

/* Modal */
.table-modal-overlay {
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
    transition: all 0.3s;
}

.table-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.table-modal {
    background: white;
    border-radius: 16px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    transform: scale(0.9);
    transition: all 0.3s;
}

.table-modal-overlay.active .table-modal {
    transform: scale(1);
}

.table-modal-header {
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-modal-title {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
}

.table-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
}

.table-modal-body {
    padding: 20px;
}

.table-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f1f3f5;
}

.table-detail-row:last-child {
    border-bottom: none;
}

.table-detail-label {
    color: #6c757d;
    font-weight: 500;
}

.table-detail-value {
    font-weight: 600;
    color: #212529;
}

/* Legend */
.tables-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
}

.legend-color.available { background: #28a745; }
.legend-color.occupied { background: #dc3545; }
.legend-color.waiting_payment { background: #ffc107; }
.legend-color.cleaning { background: #17a2b8; }
.legend-color.maintenance { background: #6c757d; }
</style>

<div class="tables-dashboard">
    <!-- Stats Bar -->
    <div class="tables-stats-bar">
        <div class="tables-stat-card" style="border-color: #28a745;">
            <div class="tables-stat-value" style="color: #28a745;"><?= $status_counts['available'] ?? 0 ?></div>
            <div class="tables-stat-label">Tersedia</div>
        </div>
        <div class="tables-stat-card" style="border-color: #dc3545;">
            <div class="tables-stat-value" style="color: #dc3545;"><?= $status_counts['occupied'] ?? 0 ?></div>
            <div class="tables-stat-label">Terisi</div>
        </div>
        <div class="tables-stat-card" style="border-color: #ffc107;">
            <div class="tables-stat-value" style="color: #ffc107;"><?= $status_counts['waiting_payment'] ?? 0 ?></div>
            <div class="tables-stat-label">Menunggu Bayar</div>
        </div>
        <div class="tables-stat-card" style="border-color: #17a2b8;">
            <div class="tables-stat-value" style="color: #17a2b8;"><?= $status_counts['cleaning'] ?? 0 ?></div>
            <div class="tables-stat-label">Dibersihkan</div>
        </div>
        <div class="tables-stat-card" style="border-color: #6c757d;">
            <div class="tables-stat-value" style="color: #6c757d;"><?= $status_counts['maintenance'] ?? 0 ?></div>
            <div class="tables-stat-label">Tutup/Rusak</div>
        </div>
    </div>

    <!-- Legend -->
    <div class="tables-legend">
        <div class="legend-item">
            <div class="legend-color available"></div>
            <span>Tersedia</span>
        </div>
        <div class="legend-item">
            <div class="legend-color occupied"></div>
            <span>Terisi</span>
        </div>
        <div class="legend-item">
            <div class="legend-color waiting_payment"></div>
            <span>Menunggu Bayar</span>
        </div>
        <div class="legend-item">
            <div class="legend-color cleaning"></div>
            <span>Dibersihkan</span>
        </div>
        <div class="legend-item">
            <div class="legend-color maintenance"></div>
            <span>Tutup/Rusak</span>
        </div>
    </div>

    <!-- Tables Grid -->
    <div class="tables-grid">
        <?php foreach ($tables as $table): ?>
        <div class="table-card <?= $table['status'] ?>" 
             data-table-id="<?= $table['id'] ?>" 
             data-table-number="<?= htmlspecialchars($table['table_number']) ?>"
             data-status="<?= $table['status'] ?>"
             onclick="TablesDashboard.showTableDetail(<?= json_encode($table) ?>)">
            
            <div class="table-card-header">
                Meja <?= htmlspecialchars($table['table_number']) ?>
            </div>
            
            <div class="table-card-body">
                <div class="table-card-status"><?= $table['status_label'] ?></div>
                <div class="table-card-orders">
                    <strong><?= $table['active_orders'] ?></strong> pesanan aktif
                </div>
                
                <?php if ($table['status'] === 'cleaning'): ?>
                <button class="table-clean-btn" 
                        onclick="event.stopPropagation(); TablesDashboard.confirmClean(<?= $table['id'] ?>, '<?= htmlspecialchars($table['table_number']) ?>')">
                    ✅ Meja Sudah Bersih
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Table Detail Modal -->
<div id="tableModalOverlay" class="table-modal-overlay">
    <div class="table-modal">
        <div class="table-modal-header">
            <h3 class="table-modal-title" id="modalTitle">Detail Meja</h3>
            <button class="table-modal-close" onclick="TablesDashboard.closeModal()">&times;</button>
        </div>
        <div class="table-modal-body" id="modalBody">
            <!-- Content will be injected by JS -->
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/tables.js') ?>"></script>
<script>
// Initialize tables dashboard
document.addEventListener('DOMContentLoaded', function() {
    TablesDashboard.init({
        cleanTableEndpoint: '<?= site_url('waiter/clean_table') ?>'
    });
});
</script>
