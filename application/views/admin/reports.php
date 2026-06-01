<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title; ?> - Admin</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css'); ?>">
    <style>
        .summary-card { border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card-orders { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .card-revenue { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .card-tables { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        .card-completed { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
        .summary-value { font-size: 2.5rem; font-weight: bold; }
        .chart-container { position: relative; height: 300px; margin: 20px 0; }
        .filter-panel { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= site_url('admin/dashboard'); ?>">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('admin/menu'); ?>">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('admin/tables'); ?>">Meja</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('admin/users'); ?>">Pengguna</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?= site_url('admin/report'); ?>">Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('auth/logout'); ?>">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col-12">
                <h2><i class="fas fa-chart-line"></i> <?= $page_title; ?></h2>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="filter-panel">
            <form method="get" action="<?= site_url('admin/report'); ?>" class="form-inline">
                <div class="form-group mr-3">
                    <label for="start_date" class="mr-2">Tanggal Mulai:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date; ?>">
                </div>
                <div class="form-group mr-3">
                    <label for="end_date" class="mr-2">Tanggal Akhir:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date; ?>">
                </div>
                <button type="submit" class="btn btn-primary mr-2">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="<?= site_url('admin/report/export_pdf?start_date=' . $start_date . '&end_date=' . $end_date); ?>" class="btn btn-success" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="summary-card card-orders">
                    <h5>Total Pesanan</h5>
                    <div class="summary-value"><?= $summary['total_orders'] ?? 0; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card card-revenue">
                    <h5>Total Pendapatan</h5>
                    <div class="summary-value">Rp <?= number_format($summary['total_revenue'] ?? 0, 0, ',', '.'); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card card-tables">
                    <h5>Meja Unik</h5>
                    <div class="summary-value"><?= $summary['unique_tables'] ?? 0; ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card card-completed">
                    <h5>Pesanan Selesai</h5>
                    <div class="summary-value"><?= $summary['completed_orders'] ?? 0; ?></div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Penjualan per Kategori</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar"></i> Tren Pendapatan Harian</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Items Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-star"></i> Top 10 Item Terlaris</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Jumlah Terjual</th>
                                    <th>Total Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_items)): ?>
                                    <?php $no = 1; foreach ($top_items as $item): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td><?= esc_html($item['item_name']); ?></td>
                                        <td><?= $item['total_qty']; ?> item</td>
                                        <td>Rp <?= number_format($item['total_sales'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">Tidak ada data pada periode ini</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-receipt"></i> Riwayat Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>No Order</th>
                                    <th>Meja</th>
                                    <th>Kasir</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($transactions)): ?>
                                    <?php foreach ($transactions as $trx): ?>
                                    <tr>
                                        <td><?= esc_html($trx['order_number']); ?></td>
                                        <td><?= esc_html($trx['table_code']); ?></td>
                                        <td><?= esc_html($trx['cashier_name']); ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($trx['created_at'])); ?></td>
                                        <td>Rp <?= number_format($trx['amount'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="badge badge-<?= $trx['status'] == 'paid' ? 'success' : 'warning'; ?>">
                                                <?= ucfirst($trx['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">Tidak ada transaksi</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= base_url('assets/js/jquery.min.js'); ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="<?= base_url('assets/js/report.js'); ?>"></script>
    <script>
        // Initialize charts with server-side data
        const categoryData = <?= json_encode($sales_by_category); ?>;
        const revenueData = <?= json_encode($daily_revenue); ?>;
        
        $(document).ready(function() {
            initCategoryChart(categoryData);
            initRevenueChart(revenueData);
        });
    </script>
</body>
</html>
