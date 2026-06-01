<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc_html($page_title ?? 'Manajemen Kategori') ?> - Smart Restaurant POS</title>
    
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
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#categoryModal" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Tambah Kategori
                </button>
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- DataTable -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="categoryTable" class="table table-hover dataTable" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Kategori</th>
                                    <th>Deskripsi</th>
                                    <th>Urutan</th>
                                    <th>Jumlah Item</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
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

<!-- Modal Form Category -->
<div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="categoryForm" method="POST" action="<?= site_url('admin_category/save') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="categoryId">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Tambah Kategori</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required minlength="2" maxlength="100" placeholder="Contoh: Makanan Utama">
                        <small class="form-text text-muted">Minimal 2 karakter, hanya huruf, angka, dan underscore</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="500" placeholder="Deskripsi kategori..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="icon">Icon (FontAwesome class)</label>
                        <input type="text" class="form-control" id="icon" name="icon" maxlength="50" placeholder="fa-utensils">
                        <small class="form-text text-muted">Contoh: fa-utensils, fa-coffee, fa-ice-cream</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sort_order">Urutan Tampil <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="is_active">Status</label>
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                    <label class="custom-control-label" for="is_active">Aktif</label>
                                </div>
                            </div>
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
    var table = $('#categoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= site_url('admin_category/datatable') ?>',
            type: 'POST',
            data: function(d) {
                d._token = '<?= csrf_token() ?>';
            }
        },
        columns: [
            { data: 'id', width: '50px' },
            { data: 'name' },
            { data: 'description' },
            { data: 'sort_order', width: '80px', className: 'text-center' },
            { data: 'item_count', width: '100px', className: 'text-center' },
            { 
                data: 'is_active', 
                width: '100px', 
                className: 'text-center',
                render: function(data, type, row) {
                    return data == 1 
                        ? '<span class="badge badge-success">Aktif</span>' 
                        : '<span class="badge badge-secondary">Nonaktif</span>';
                }
            },
            { data: 'created_at', width: '120px' },
            { 
                data: 'actions', 
                width: '200px',
                className: 'text-center',
                orderable: false,
                render: function(data, type, row) {
                    var btns = '';
                    btns += '<button class="btn btn-sm btn-info mr-1" onclick="openEditModal(' + row.id + ')">';
                    btns += '<i class="fas fa-edit"></i> Edit';
                    btns += '</button>';
                    
                    btns += '<button class="btn btn-sm btn-' + (row.is_active == 1 ? 'warning' : 'success') + ' mr-1" onclick="toggleStatus(' + row.id + ', ' + row.is_active + ')">';
                    btns += '<i class="fas fa-' + (row.is_active == 1 ? 'ban' : 'check') + '"></i> ' + (row.is_active == 1 ? 'Nonaktif' : 'Aktif');
                    btns += '</button>';
                    
                    btns += '<button class="btn btn-sm btn-danger" onclick="deleteCategory(' + row.id + ', ' + row.item_count + ')">';
                    btns += '<i class="fas fa-trash"></i> Hapus';
                    btns += '</button>';
                    
                    return btns;
                }
            }
        ],
        order: [[3, 'asc']],
        lengthMenu: [10, 25, 50, 100],
        pageLength: 10,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
            emptyTable: 'Tidak ada data kategori',
            zeroRecords: 'Tidak ditemukan data yang sesuai'
        }
    });

    // jQuery Validation untuk form
    $('#categoryForm').validate({
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
            name: {
                required: true,
                minlength: 2,
                maxlength: 100,
                pattern: /^[a-zA-Z0-9_\s]+$/
            },
            sort_order: {
                required: true,
                number: true,
                min: 0
            }
        },
        messages: {
            name: {
                required: 'Nama kategori wajib diisi',
                minlength: 'Minimal 2 karakter',
                maxlength: 'Maksimal 100 karakter',
                pattern: 'Hanya boleh huruf, angka, spasi, dan underscore'
            },
            sort_order: {
                required: 'Urutan wajib diisi',
                number: 'Harus berupa angka',
                min: 'Nilai minimal 0'
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
                        $('#categoryModal').modal('hide');
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

// Open modal untuk tambah kategori baru
function openAddModal() {
    $('#categoryForm')[0].reset();
    $('#categoryId').val('');
    $('#categoryModalLabel').text('Tambah Kategori');
    $('#categoryForm').find('.is-invalid').removeClass('is-invalid').addClass('is-valid');
}

// Open modal untuk edit kategori
function openEditModal(id) {
    $.ajax({
        url: '<?= site_url('admin_category/edit') ?>/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var data = response.data;
                $('#categoryId').val(data.id);
                $('#name').val(data.name);
                $('#description').val(data.description || '');
                $('#icon').val(data.icon || '');
                $('#sort_order').val(data.sort_order);
                $('#is_active').prop('checked', data.is_active == 1);
                $('#categoryModalLabel').text('Edit Kategori');
                $('#categoryModal').modal('show');
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Gagal mengambil data kategori');
        }
    });
}

// Toggle status kategori
function toggleStatus(id, currentStatus) {
    if (!confirm('Apakah Anda yakin ingin mengubah status kategori ini?')) {
        return;
    }
    
    $.ajax({
        url: '<?= site_url('admin_category/toggle_status') ?>/' + id,
        type: 'POST',
        data: { _token: '<?= csrf_token() ?>' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#categoryTable').DataTable().ajax.reload(null, false);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Gagal mengubah status');
        }
    });
}

// Delete kategori
function deleteCategory(id, itemCount) {
    var message = itemCount > 0 
        ? 'Kategori ini masih memiliki ' + itemCount + ' item. Kategori akan dinonaktifkan (soft delete). Lanjutkan?'
        : 'Kategori ini tidak memiliki item dan akan dihapus permanen. Lanjutkan?';
    
    if (!confirm(message)) {
        return;
    }
    
    $.ajax({
        url: '<?= site_url('admin_category/delete') ?>/' + id,
        type: 'POST',
        data: { _token: '<?= csrf_token() ?>' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('success', response.message);
                $('#categoryTable').DataTable().ajax.reload(null, false);
            } else {
                showAlert('danger', response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Gagal menghapus kategori');
        }
    });
}

// Show alert message
function showAlert(type, message) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
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
