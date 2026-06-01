<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title; ?> - Admin</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css'); ?>">
    <style>
        .transaction-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #fff; }
        .transaction-card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .amount-badge { font-size: 1.2rem; font-weight: bold; color: #2c3e50; }
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
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('admin/report'); ?>">Laporan</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?= site_url('admin/refund'); ?>">Refund</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('auth/logout'); ?>">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col-12">
                <h2><i class="fas fa-undo"></i> <?= $page_title; ?></h2>
                <p class="text-muted">Kelola refund dan void transaksi</p>
            </div>
        </div>

        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $this->session->flashdata('success'); ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $this->session->flashdata('error'); ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Filter Panel -->
        <div class="filter-panel">
            <form method="get" action="<?= site_url('admin/refund'); ?>" class="form-inline">
                <div class="form-group mr-3">
                    <label for="start_date" class="mr-2">Tanggal Mulai:</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="form-group mr-3">
                    <label for="end_date" class="mr-2">Tanggal Akhir:</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <button type="submit" class="btn btn-primary mr-2">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="<?= site_url('admin/refund/history'); ?>" class="btn btn-info">
                    <i class="fas fa-history"></i> Riwayat Refund
                </a>
            </form>
        </div>

        <!-- Transactions List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-receipt"></i> Transaksi Tersedia untuk Refund</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($transactions)): ?>
                            <div class="row">
                                <?php foreach ($transactions as $trx): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="transaction-card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h5 class="mb-0"><?= htmlspecialchars($trx['order_number'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                            <span class="badge badge-success">Paid</span>
                                        </div>
                                        <p class="mb-1"><strong>Meja:</strong> <?= htmlspecialchars($trx['table_code'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="mb-1"><strong>Kasir:</strong> <?= htmlspecialchars($trx['cashier_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="mb-1"><strong>Tanggal:</strong> <?= date('d/m/Y H:i', strtotime($trx['created_at'])); ?></p>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <span class="amount-badge">Rp <?= number_format($trx['amount'], 0, ',', '.'); ?></span>
                                            <a href="<?= site_url('admin/refund/detail/' . $trx['id']); ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-undo"></i> Proses Refund
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> Tidak ada transaksi yang tersedia untuk refund pada periode ini.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= base_url('assets/js/jquery.min.js'); ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.min.js'); ?>"></script>
</body>
</html>
