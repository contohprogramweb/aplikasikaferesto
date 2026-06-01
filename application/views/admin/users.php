<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc_html($page_title ?? 'Manajemen User') ?> - Smart Restaurant POS</title>
    
    <!-- Bootstrap 4 CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/css/all.min.css') ?>">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/vendor/datatables/css/dataTables.bootstrap4.min.css') ?>">
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/vendor/select2/css/select2.min.css') ?>">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
    
    <style>
        .custom-switch-label {
            cursor: pointer;
        }
        .password-input-group {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .password-toggle:hover {
            color: #343a40;
        }
    </style>
</head>
<body class="admin-body">

<div class="wrapper">
    <!-- Sidebar -->
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
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#userModal" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Tambah User
                </button>
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Filter Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label>Filter Role</label>
                                <select class="form-control" id="filterRole">
                                    <option value="">Semua Role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="cashier">Kasir</option>
                                    <option value="kitchen">Dapur</option>
                                    <option value="waiter">Pelayan</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label>Status Aktif</label>
                                <select class="form-control" id="filterStatus">
                                    <option value="">Semua</option>
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 text-right">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-info btn-block" onclick="applyFilters()">
                                <i class="fas fa-filter"></i> Terapkan Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DataTable -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="userTable" class="table table-hover dataTable" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
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

<!-- Modal Form User -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="userForm" method="POST" action="<?= site_url('admin_user/save') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="userId">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Tambah User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="full_name">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required minlength="3" maxlength="100" placeholder="Contoh: John Doe">
                                <small class="form-text text-muted">Minimal 3 karakter, maksimal 100 karakter</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required minlength="4" maxlength="20" pattern="[a-zA-Z0-9_]+" placeholder="john_doe">
                                <small class="form-text text-muted">4-20 karakter, hanya huruf, angka, dan underscore</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required maxlength="100" placeholder="john@example.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role">Role <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="role" name="role" required style="width: 100%;">
                                    <option value="">Pilih Role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="cashier">Kasir</option>
                                    <option value="kitchen">Dapur</option>
                                    <option value="waiter">Pelayan</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password <span class="text-danger" id="passwordRequired">*</span></label>
                                <div class="password-input-group">
                                    <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="Minimal 6 karakter">
                                    <span class="password-toggle" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </span>
                                </div>
                                <small class="form-text text-muted">Minimal 6 karakter, harus mengandung huruf dan angka</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="active">Status Aktif</label>
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" id="active" name="active" checked>
                                    <label class="custom-control-label custom-switch-label" for="active">Aktif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info" id="passwordNote" style="display: none;">
                        <i class="fas fa-info-circle"></i> Kosongkan password jika tidak ingin mengubah password
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
<script src="<?= base_url('assets/vendor/select2/js/select2.min.js') ?>"></script>
<script src="<?= base_url('assets/js/common.js') ?>"></script>
<script src="<?= base_url('assets/js/admin.js') ?>"></script>

<script>
var userTable;
var isEditMode = false;

$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        placeholder: 'Pilih Role',
        allowClear: true,
        width: '100%'
    });
    
    // Initialize DataTable
    userTable = $('#userTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= site_url('admin_user/datatable') ?>',
            type: 'POST',
            data: function(d) {
                d._token = '<?= csrf_token() ?>';
                d.role = $('#filterRole').val();
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'id', width: '50px' },
            { data: 'full_name' },
            { data: 'username' },
            { data: 'email' },
            { 
                data: 'role_display',
                render: function(data, type, row) {
                    return '<span class="badge badge-info">' + data + '</span>';
                }
            },
            { 
                data: 'status_display',
                width: '100px',
                className: 'text-center'
            },
            { 
                data: 'last_login',
                width: '120px'
            },
            { 
                data: 'created_at',
                width: '100px'
            },
            { 
                data: 'actions',
                width: '150px',
                className: 'text-center',
                orderable: false,
                render: function(data, type, row) {
                    let buttons = `
                        <button class="btn btn-sm btn-warning" onclick="openEditModal(${row.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-${row.active == 1 ? 'danger' : 'success'}" onclick="toggleStatus(${row.id})" title="${row.active == 1 ? 'Nonaktifkan' : 'Aktifkan'}">
                            <i class="fas fa-${row.active == 1 ? 'ban' : 'check'}"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${row.id})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    return buttons;
                }
            }
        ],
        order: [[0, 'ASC']],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
            lengthMenu: "Tampilkan _MENU_ data",
            zeroRecords: "Tidak ada data ditemukan",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            infoEmpty: "Tidak ada data yang ditampilkan",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        },
        drawCallback: function(settings) {
            // Re-initialize tooltips after DataTable redraw
            $('[data-toggle="tooltip"]').tooltip();
        }
    });
    
    // Initialize form validation
    initValidation();
});

/**
 * Initialize jQuery Validate
 */
function initValidation() {
    $('#userForm').validate({
        errorElement: 'span',
        errorClass: 'invalid-feedback',
        errorPlacement: function(error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        highlight: function(element, errorClass, validClass) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        rules: {
            full_name: {
                required: true,
                minlength: 3,
                maxlength: 100
            },
            username: {
                required: true,
                minlength: 4,
                maxlength: 20,
                pattern: /^[a-zA-Z0-9_]+$/,
                remote: {
                    url: '<?= site_url('admin_user/check_username_unique_ajax') ?>',
                    type: 'post',
                    data: {
                        username: function() {
                            return $('#username').val();
                        },
                        exclude_id: function() {
                            return $('#userId').val();
                        },
                        _token: '<?= csrf_token() ?>'
                    }
                }
            },
            email: {
                required: true,
                email: true,
                maxlength: 100,
                remote: {
                    url: '<?= site_url('admin_user/check_email_unique_ajax') ?>',
                    type: 'post',
                    data: {
                        email: function() {
                            return $('#email').val();
                        },
                        exclude_id: function() {
                            return $('#userId').val();
                        },
                        _token: '<?= csrf_token() ?>'
                    }
                }
            },
            role: {
                required: true
            },
            password: {
                required: function() {
                    return !isEditMode || ($('#password').val() !== '');
                },
                minlength: 6,
                pattern: /^(?=.*[A-Za-z])(?=.*\d).{6,}$/
            }
        },
        messages: {
            full_name: {
                required: 'Nama lengkap wajib diisi',
                minlength: 'Minimal 3 karakter',
                maxlength: 'Maksimal 100 karakter'
            },
            username: {
                required: 'Username wajib diisi',
                minlength: 'Minimal 4 karakter',
                maxlength: 'Maksimal 20 karakter',
                pattern: 'Hanya huruf, angka, dan underscore yang diperbolehkan',
                remote: 'Username sudah digunakan'
            },
            email: {
                required: 'Email wajib diisi',
                email: 'Format email tidak valid',
                maxlength: 'Maksimal 100 karakter',
                remote: 'Email sudah digunakan'
            },
            role: {
                required: 'Role wajib dipilih'
            },
            password: {
                required: 'Password wajib diisi',
                minlength: 'Minimal 6 karakter',
                pattern: 'Password harus mengandung huruf dan angka, minimal 6 karakter'
            }
        },
        submitHandler: function(form) {
            submitForm();
        }
    });
}

/**
 * Open modal untuk add new user
 */
function openAddModal() {
    isEditMode = false;
    $('#userModalLabel').text('Tambah User');
    $('#userForm')[0].reset();
    $('#userId').val('');
    $('#password').prop('required', true);
    $('#passwordRequired').show();
    $('#passwordNote').hide();
    $('.select2').val('').trigger('change');
    $('#active').prop('checked', true);
    
    // Reset validation
    $('#userForm').validate().resetForm();
    $('#userForm').find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
}

/**
 * Open modal untuk edit user
 */
function openEditModal(id) {
    isEditMode = true;
    $('#userModalLabel').text('Edit User');
    $('#passwordRequired').hide();
    $('#passwordNote').show();
    
    loadingOverlay(true, 'Memuat data user...');
    
    $.ajax({
        url: '<?= site_url('admin_user/edit/') ?>' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            loadingOverlay(false);
            
            if (response.success) {
                const data = response.data;
                
                $('#userId').val(data.id);
                $('#full_name').val(data.full_name);
                $('#username').val(data.username);
                $('#email').val(data.email);
                $('#role').val(data.role).trigger('change');
                $('#active').prop('checked', data.active == 1);
                $('#password').val('');
                
                // Remove validation marks
                $('#userForm').validate().resetForm();
                $('#userForm').find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
                
                $('#userModal').modal('show');
            } else {
                showToast('error', response.message || 'Gagal memuat data user');
            }
        },
        error: function(xhr, status, error) {
            loadingOverlay(false);
            showToast('error', 'Terjadi kesalahan saat memuat data user');
            console.error(error);
        }
    });
}

/**
 * Toggle password visibility
 */
function togglePassword() {
    const passwordInput = $('#password');
    const toggleIcon = $('#toggleIcon');
    
    if (passwordInput.attr('type') === 'password') {
        passwordInput.attr('type', 'text');
        toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        passwordInput.attr('type', 'password');
        toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
}

/**
 * Submit form via AJAX
 */
function submitForm() {
    loadingOverlay(true, 'Menyimpan data...');
    
    const formData = new FormData($('#userForm')[0]);
    formData.append('_token', '<?= csrf_token() ?>');
    
    $.ajax({
        url: $('#userForm').attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            loadingOverlay(false);
            
            if (response.success) {
                showToast('success', response.message);
                $('#userModal').modal('hide');
                userTable.ajax.reload(null, false);
            } else {
                if (response.errors) {
                    // Show validation errors
                    Object.keys(response.errors).forEach(function(key) {
                        $('#' + key).addClass('is-invalid').removeClass('is-valid');
                        $('#' + key).closest('.form-group').append(
                            '<span class="invalid-feedback">' + response.errors[key] + '</span>'
                        );
                    });
                } else {
                    showToast('error', response.message || 'Gagal menyimpan data');
                }
            }
        },
        error: function(xhr, status, error) {
            loadingOverlay(false);
            showToast('error', 'Terjadi kesalahan pada server');
            console.error(error);
        }
    });
}

/**
 * Delete user
 */
function deleteUser(id) {
    confirmDialog('Konfirmasi Hapus User', 'Apakah Anda yakin ingin menonaktifkan user ini? User akan di-nonaktifkan (soft delete).')
        .then(function(confirmed) {
            if (confirmed) {
                loadingOverlay(true, 'Menonaktifkan user...');
                
                $.ajax({
                    url: '<?= site_url('admin_user/delete/') ?>' + id,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        _token: '<?= csrf_token() ?>'
                    },
                    success: function(response) {
                        loadingOverlay(false);
                        
                        if (response.success) {
                            showToast('success', response.message);
                            userTable.ajax.reload(null, false);
                        } else {
                            showToast('error', response.message || 'Gagal menonaktifkan user');
                        }
                    },
                    error: function(xhr, status, error) {
                        loadingOverlay(false);
                        showToast('error', 'Terjadi kesalahan pada server');
                        console.error(error);
                    }
                });
            }
        });
}

/**
 * Toggle user status
 */
function toggleStatus(id) {
    loadingOverlay(true, 'Mengubah status...');
    
    $.ajax({
        url: '<?= site_url('admin_user/toggle_status/') ?>' + id,
        type: 'POST',
        dataType: 'json',
        data: {
            _token: '<?= csrf_token() ?>'
        },
        success: function(response) {
            loadingOverlay(false);
            
            if (response.success) {
                showToast('success', response.message);
                userTable.ajax.reload(null, false);
            } else {
                showToast('error', response.message || 'Gagal mengubah status');
            }
        },
        error: function(xhr, status, error) {
            loadingOverlay(false);
            showToast('error', 'Terjadi kesalahan pada server');
            console.error(error);
        }
    });
}

/**
 * Apply filters
 */
function applyFilters() {
    userTable.ajax.reload();
}
</script>

</body>
</html>
