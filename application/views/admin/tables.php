<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc_html($page_title ?? 'Manajemen Meja') ?> - Smart Restaurant POS</title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/css/all.min.css') ?>">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/vendor/datatables/css/dataTables.bootstrap4.min.css') ?>">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
</head>
<body class="admin-body">

<div class="wrapper">
    <!-- Sidebar (placeholder) -->
    <nav class="sidebar" id="sidebar">
        <!-- Sidebar content loaded via partial -->
    </nav>

    <!-- Page Content -->
    <div id="content" class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
            <button type="button" id="sidebarCollapse" class="btn btn-outline-secondary">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="ml-auto d-flex align-items-center">
                <span class="mr-3"><?= esc_html($this->session->userdata('full_name')) ?></span>
                <a href="<?= site_url('auth/logout') ?>" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid py-4">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <?php foreach ($breadcrumb as $item): ?>
                        <?php if ($item['url'] === '#'): ?>
                            <li class="breadcrumb-item active"><?= esc_html($item['label']) ?></li>
                        <?php else: ?>
                            <li class="breadcrumb-item"><a href="<?= esc_html($item['url']) ?>"><?= esc_html($item['label']) ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><?= esc_html($page_title) ?></h2>
                <div>
                    <button type="button" class="btn btn-info mr-2" onclick="regenerateAllQR()">
                        <i class="fas fa-sync"></i> Regenerate Semua QR
                    </button>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#tableModal" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Tambah Meja
                    </button>
                </div>
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- DataTable -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableTable" class="table table-hover dataTable" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kode Meja</th>
                                    <th>Nama Meja</th>
                                    <th>Kapasitas</th>
                                    <th>Lokasi</th>
                                    <th>Status</th>
                                    <th>QR Code</th>
                                    <th>Aktif</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Table -->
<div class="modal fade" id="tableModal" tabindex="-1" role="dialog" aria-labelledby="tableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="tableForm" method="POST" action="<?= site_url('admin_table/save') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="tableId">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="tableModalLabel">Tambah Meja</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="table_number">Kode Meja <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="table_number" name="table_number" required minlength="2" maxlength="10" placeholder="Contoh: T001">
                                <small class="form-text text-muted">Huruf dan angka saja, akan diubah ke uppercase</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="table_name">Nama Meja</label>
                                <input type="text" class="form-control" id="table_name" name="table_name" maxlength="50" placeholder="Contoh: Meja Utama">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="capacity">Kapasitas (orang) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="capacity" name="capacity" value="4" min="1" max="50" required>
                                <small class="form-text text-muted">Minimal 1, maksimal 50 orang</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="location">Lokasi</label>
                                <input type="text" class="form-control" id="location" name="location" maxlength="50" placeholder="Contoh: Main Hall">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="is_active">Status</label>
                        <div class="custom-control custom-switch mt-2">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                            <label class="custom-control-label" for="is_active">Aktif</label>
                        </div>
                    </div>
                    
                    <!-- QR Preview (untuk edit) -->
                    <div class="form-group" id="qrPreviewGroup" style="display: none;">
                        <label>Preview QR Code</label>
                        <div class="text-center p-3 border rounded bg-light">
                            <img id="qrPreview" src="" alt="QR Code" style="max-width: 200px;">
                            <p class="mt-2 mb-0 text-muted small">Scan untuk akses menu digital</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSave">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="<?= base_url('assets/vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/js/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/datatables/js/dataTables.bootstrap4.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/jquery-validate/jquery.validate.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/jquery-validate/additional-methods.min.js') ?>"></script>
<script src="<?= base_url('assets/js/admin.js') ?>"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#tableTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= site_url('admin_table/datatable') ?>',
            type: 'POST',
            data: function(d) {
                d._token = '<?= csrf_token() ?>';
            }
        },
        columns: [
            { data: 'id', width: '50px' },
            { data: 'table_number' },
            { data: 'table_name' },
            { data: 'capacity', width: '80px', className: 'text-center' },
            { data: 'location' },
            { 
                data: 'status', 
                width: '100px',
                className: 'text-center',
                render: function(data, type, row) {
                    var badgeClass = {
                        'available': 'success',
                        'occupied': 'danger',
                        'reserved': 'warning',
                        'maintenance': 'secondary'
                    };
                    var statusText = {
                        'available': 'Tersedia',
                        'occupied': 'Terisi',
                        'reserved': 'Direservasi',
                        'maintenance': 'Perbaikan'
                    };
                    return '<span class="badge badge-' + (badgeClass[data] || 'secondary') + '">' + (statusText[data] || data) + '</span>';
                }
            },
            { 
                data: 'qr_preview', 
                width: '100px',
                className: 'text-center',
                render: function(data, type, row) {
                    if (data) {
                        return '<img src="' + data + '" alt="QR" style="width: 50px; height: 50px;">';
                    }
                    return '<span class="text-muted">No QR</span>';
                }
            },
            { 
                data: 'is_active', 
                width: '70px', 
                className: 'text-center',
                render: function(data, type, row) {
                    return data == 1 
                        ? '<span class="badge badge-success">Ya</span>' 
                        : '<span class="badge badge-secondary">Tidak</span>';
                }
            },
            { 
                data: 'actions', 
                width: '250px',
                className: 'text-center',
                orderable: false,
                render: function(data, type, row) {
                    var btns = '';
                    btns += '<button class="btn btn-sm btn-info mr-1" onclick="openEditModal(' + row.id + ')">';
                    btns += '<i class="fas fa-edit"></i>';
                    btns += '</button>';
                    
                    btns += '<button class="btn btn-sm btn-success mr-1" onclick="printQR(' + row.id + ')" title="Print QR Label">';
                    btns += '<i class="fas fa-print"></i>';
                    btns += '</button>';
                    
                    btns += '<button class="btn btn-sm btn-' + (row.is_active == 1 ? 'warning' : 'success') + ' mr-1" onclick="toggleStatus(' + row.id + ', ' + row.is_active + ')" title="Toggle Status">';
                    btns += '<i class="fas fa-' + (row.is_active == 1 ? 'ban' : 'check') + '"></i>';
                    btns += '</button>';
                    
                    btns += '<button class="btn btn-sm btn-danger" onclick="deleteTable(' + row.id + ')" title="Hapus Meja">';
                    btns += '<i class="fas fa-trash"></i>';
                    btns += '</button>';
                    
                    return btns;
                }
            }
        ],
        order: [[1, 'asc']],
        lengthMenu: [10, 25, 50, 100],
        pageLength: 10,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
            emptyTable: 'Tidak ada data meja',
            zeroRecords: 'Tidak ditemukan data yang sesuai'
        }
    });

    // jQuery Validation untuk form
    $('#tableForm').validate({
        errorClass: 'is-invalid',
        validClass: 'is-valid',
        errorElement: 'div',
        errorPlacement: function(error, element) {
            error.addClass('invalid-feedback');
            element.parent().append(error);
        },
        highlight: function(element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function(element) {
            $(element).addClass('is-valid').removeClass('is-invalid');
        },
        rules: {
            table_number: {
                required: true,
                minlength: 2,
                maxlength: 10,
                alphanumeric: true
            },
            capacity: {
                required: true,
                number: true,
                min: 1,
                max: 50
            }
        },
        messages: {
            table_number: {
                required: 'Kode meja wajib diisi',
                minlength: 'Minimal 2 karakter',
                maxlength: 'Maksimal 10 karakter',
                alphanumeric: 'Hanya boleh huruf dan angka'
            },
            capacity: {
                required: 'Kapasitas wajib diisi',
                number: 'Harus berupa angka',
                min: 'Minimal 1 orang',
                max: 'Maksimal 50 orang'
            }
        },
        submitHandler: function(form) {
            var formData = $(form).serialize();
            var submitUrl = $(form).attr('action');
            
            $.ajax({
                url: submitUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                beforeSend: function() {
                    $('#btnSave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        $('#tableModal').modal('hide');
                        table.ajax.reload(null, false);
                    } else {
                        showAlert('danger', response.message || 'Terjadi kesalahan');
                        if (response.errors) {
                            $.each(response.errors, function(key, value) {
                                $('#' + key).addClass('is-invalid');
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('danger', 'Gagal menyimpan data: ' + error);
                },
                complete: function() {
                    $('#btnSave').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
                }
            });
        }
    });
});

// Open modal untuk tambah meja baru
function openAddModal() {
    $('#tableForm')[0].reset();
    $('#tableId').val('');
    $('#tableModalLabel').text('Tambah Meja');
    $('#qrPreviewGroup').hide();
    $('#tableForm').find('.is-invalid').removeClass('is-invalid').addClass('is-valid');
}

// Open modal untuk edit meja
function openEditModal(id) {
    $.ajax({
        url: '<?= site_url('admin_table/edit') ?>/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var data = response.data;
                $('#tableId').val(data.id);
                $('#table_number').val(data.table_number);
                $('#table_name').val(data.table_name || '');
                $('#capacity').val(data.capacity);
                $('#location').val(data.location || '');
                $('#is_active').prop('checked', data.is_active == 1);
                
                if (data.qr_preview) {
                    $('#qrPreview').attr('src', data.qr_preview);
                    $('#qrPreviewGroup').show();
                } else {
                    $('#qrPreviewGroup').hide();
                }
                
                $('#tableModalLabel').text('Edit Meja');
                $('#tableModal').modal('show');
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Gagal mengambil data meja');
        }
    });
}

// Toggle status meja
function toggleStatus(id, currentStatus) {
    if (!confirm('Apakah Anda yakin ingin mengubah status meja ini?')) {
        return;
    }
    
    $.ajax({
        url: '<?= site_url('admin_table/toggle_status') ?>/' + id,
        type: 'POST',
        data: { _token: '<?= csrf_token() ?>' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#tableTable').DataTable().ajax.reload(null, false);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Gagal mengubah status');
        }
    });
}

// Delete meja
function deleteTable(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus meja ini?\n\nPERHATIAN: Meja tidak dapat dihapus jika masih ada order aktif atau status terisi.')) {
        return;
    }
    
    $.ajax({
        url: '<?= site_url('admin_table/delete') ?>/' + id,
        type: 'POST',
        data: { _token: '<?= csrf_token() ?>' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#tableTable').DataTable().ajax.reload(null, false);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Gagal menghapus meja');
        }
    });
}

// Print QR label
function printQR(id) {
    window.open('<?= site_url('admin_table/print_qr') ?>/' + id, '_blank');
}

// Regenerate all QR codes
function regenerateAllQR() {
    if (!confirm('Regenerate semua QR code?\n\nLakukan ini jika BASE_URL aplikasi berubah.\nSemua QR code lama akan diganti dengan yang baru.')) {
        return;
    }
    
    $.ajax({
        url: '<?= site_url('admin_table/regenerate_all_qr') ?>',
        type: 'POST',
        data: { _token: '<?= csrf_token() ?>' },
        dataType: 'json',
        beforeSend: function() {
            showAlert('info', 'Sedang regenerate QR code...');
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#tableTable').DataTable().ajax.reload(null, false);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Gagal regenerate QR code');
        }
    });
}

// Show alert message
function showAlert(type, message) {
    var alertClass = type === 'success' ? 'alert-success' : (type === 'info' ? 'alert-info' : 'alert-danger');
    var icon = type === 'success' ? 'fa-check-circle' : (type === 'info' ? 'fa-info-circle' : 'fa-exclamation-circle');
    
    var html = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">';
    html += '<i class="fas ' + icon + ' mr-2"></i>' + message;
    html += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
    html += '<span aria-hidden="true">&times;</span></button></div>';
    
    $('#alertContainer').html(html);
    
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}
</script>

</body>
</html>
