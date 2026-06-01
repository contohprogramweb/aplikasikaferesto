<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title; ?> - Admin</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css'); ?>">
    <style>
        .item-row { border-bottom: 1px solid #eee; padding: 10px 0; }
        .refund-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col-12">
                <h2><i class="fas fa-undo"></i> <?= $page_title; ?></h2>
                <a href="<?= site_url('admin/refund'); ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Transaction Info -->
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Detail Transaksi</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>No Order:</strong> <?= esc_html($transaction['order_number']); ?></p>
                        <p><strong>Meja:</strong> <?= esc_html($transaction['table_code']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Kasir:</strong> <?= esc_html($transaction['cashier_name']); ?></p>
                        <p><strong>Tanggal:</strong> <?= date('d/m/Y H:i:s', strtotime($transaction['created_at'])); ?></p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Total Bayar:</strong> <span class="text-danger font-weight-bold">Rp <?= number_format($transaction['amount'], 0, ',', '.'); ?></span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Item Pesanan</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= esc_html($item['item_name']); ?></td>
                            <td><?= $item['quantity']; ?></td>
                            <td>Rp <?= number_format($item['price_snapshot'], 0, ',', '.'); ?></td>
                            <td>Rp <?= number_format($item['total_price'], 0, ',', '.'); ?></td>
                            <td><span class="badge badge-info"><?= ucfirst($item['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Refund Form -->
        <div class="refund-form">
            <h5><i class="fas fa-undo"></i> Proses Refund</h5>
            <form id="refundForm" action="<?= site_url('admin/refund/process/' . $transaction['id']); ?>" method="POST">
                <div class="form-group">
                    <label for="refund_type">Tipe Refund:</label>
                    <select class="form-control" id="refund_type" name="refund_type" required>
                        <option value="full">Full Refund (Rp <?= number_format($transaction['amount'], 0, ',', '.'); ?>)</option>
                        <option value="partial">Partial Refund</option>
                    </select>
                </div>

                <div class="form-group" id="partial_amount_group" style="display:none;">
                    <label for="refund_amount">Jumlah Refund (Rp):</label>
                    <input type="number" class="form-control" id="refund_amount" name="refund_amount" 
                           min="1" max="<?= $transaction['amount']; ?>" step="1000">
                    <small class="form-text text-muted">Max: Rp <?= number_format($transaction['amount'], 0, ',', '.'); ?></small>
                </div>

                <div class="form-group">
                    <label for="reason">Alasan Refund: <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reason" name="reason" rows="4" required 
                              placeholder="Jelaskan alasan refund secara detail..."></textarea>
                    <small class="form-text text-muted">Alasan refund wajib diisi untuk audit trail</small>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Perhatian:</strong> Refund tidak dapat dibatalkan. Pastikan data sudah benar.
                </div>

                <button type="submit" class="btn btn-warning btn-lg" id="btnSubmit">
                    <i class="fas fa-undo"></i> Konfirmasi Refund
                </button>
                <a href="<?= site_url('admin/refund'); ?>" class="btn btn-secondary btn-lg">Batal</a>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= base_url('assets/js/jquery.min.js'); ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.min.js'); ?>"></script>
    <script>
        $(document).ready(function() {
            // Toggle partial amount input
            $('#refund_type').on('change', function() {
                if ($(this).val() === 'partial') {
                    $('#partial_amount_group').slideDown();
                    $('#refund_amount').prop('required', true);
                } else {
                    $('#partial_amount_group').slideUp();
                    $('#refund_amount').prop('required', false);
                }
            });

            // Form submission with AJAX
            $('#refundForm').on('submit', function(e) {
                e.preventDefault();
                
                if (!confirm('Apakah Anda YAKIN ingin memproses refund? Tindakan ini tidak dapat dibatalkan.')) {
                    return false;
                }

                const formData = new FormData(this);
                const url = $(this).attr('action');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    beforeSend: function() {
                        $('#btnSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Refund berhasil diproses!\nJumlah refund: Rp ' + response.refund_amount.toLocaleString('id-ID'));
                            window.location.href = '<?= site_url('admin/refund'); ?>';
                        } else {
                            alert('Error: ' + response.message);
                            $('#btnSubmit').prop('disabled', false).html('<i class="fas fa-undo"></i> Konfirmasi Refund');
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Terjadi kesalahan saat memproses refund. Silakan coba lagi.');
                        $('#btnSubmit').prop('disabled', false).html('<i class="fas fa-undo"></i> Konfirmasi Refund');
                    }
                });

                return false;
            });
        });
    </script>
</body>
</html>
