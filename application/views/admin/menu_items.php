<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc_html($page_title ?? 'Manajemen Menu Items') ?> - Smart Restaurant POS</title>
    
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
        .thumb-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }
        .table-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .image-upload-container {
            position: relative;
            display: inline-block;
        }
        .image-upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            border-radius: 8px;
            color: white;
            cursor: pointer;
        }
        .image-upload-container:hover .image-upload-overlay {
            opacity: 1;
        }
        .custom-switch-label {
            cursor: pointer;
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
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#menuModal" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Tambah Menu Item
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
                                <label>Filter Kategori</label>
                                <select class="form-control" id="filterCategory">
                                    <option value="">Semua Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= esc_html($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label>Status Ketersediaan</label>
                                <select class="form-control" id="filterStatus">
                                    <option value="">Semua</option>
                                    <option value="1">Tersedia</option>
                                    <option value="0">Tidak Tersedia</option>
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
                        <table id="menuTable" class="table table-hover dataTable" style="width: 100%">
                            <thead>
                                <tr>
                                    <th>Gambar</th>
                                    <th>ID</th>
                                    <th>Nama Menu</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Deskripsi</th>
                                    <th>Status</th>
                                    <th>Urutan</th>
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

<!-- Modal Form Menu Item -->
<div class="modal fade" id="menuModal" tabindex="-1" role="dialog" aria-labelledby="menuModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="menuForm" method="POST" action="<?= site_url('admin_menu/save') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="menuId">
                <input type="hidden" name="old_image" id="oldImage">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="menuModalLabel">Tambah Menu Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="name">Nama Menu <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required minlength="3" maxlength="100" placeholder="Contoh: Nasi Goreng Spesial">
                                <small class="form-text text-muted">Minimal 3 karakter, maksimal 100 karakter</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="category_id">Kategori <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="category_id" name="category_id" required style="width: 100%;">
                                    <option value="">Pilih Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= esc_html($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="price">Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="price" name="price" required placeholder="25000" data-a-sep="." data-a-sign="Rp " data-p-sign="s" data-m-dec="0" data-w-empty="sign">
                                <small class="form-text text-muted">Minimal Rp 100</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sort_order">Urutan Tampil <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="1000" placeholder="Deskripsi menu..."></textarea>
                        <small class="form-text text-muted">Maksimal 1000 karakter</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Gambar Menu</label>
                        <div class="image-upload-container">
                            <img id="imagePreview" src="<?= base_url('assets/img/placeholder.png') ?>" alt="Preview" class="thumb-preview">
                            <div class="image-upload-overlay" onclick="$('#image').click()">
                                <i class="fas fa-camera fa-2x"></i>
                            </div>
                        </div>
                        <input type="file" class="form-control mt-2" id="image" name="image" accept="image/jpeg,image/jpg,image/png" onchange="previewImage(this)">
                        <small class="form-text text-muted">Max 2MB, format JPG/PNG. Akan diresize ke 800x800px dan thumbnail 200x200px</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="is_available">Status Ketersediaan</label>
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" id="is_available" name="is_available" checked>
                                    <label class="custom-control-label custom-switch-label" for="is_available">Tersedia</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <p class="text-muted small">Toggle untuk mengubah status ketersediaan menu</p>
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
<script src="<?= base_url('assets/vendor/select2/js/select2.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/autonumeric/autoNumeric.min.js') ?>"></script>
<script src="<?= base_url('assets/js/common.js') ?>"></script>
<script src="<?= base_url('assets/js/admin.js') ?>"></script>

<script>
var menuTable;

$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        placeholder: 'Pilih Kategori',
        allowClear: true,
        width: '100%'
    });
    
    // Initialize AutoNumeric untuk harga
    AutoNumeric.init('#price', {
        digitGroupSeparator: '.',
        currencySymbol: 'Rp ',
        positionSymbol: 's',
        decimalPlaces: 0,
        emptyInputBehavior: 'null'
    });
    
    // Initialize DataTable
    menuTable = $('#menuTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '<?= site_url('admin_menu/datatable') ?>',
            type: 'POST',
            data: function(d) {
                d._token = '<?= csrf_token() ?>';
                d.category_id = $('#filterCategory').val();
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            { 
                data: 'thumb_url', 
                width: '70px',
                className: 'text-center',
                render: function(data, type, row) {
                    return '<img src="' + data + '" alt="' + row.name + '" class="table-thumbnail">';
                }
            },
            { data: 'id', width: '50px' },
            { data: 'name' },
            { data: 'category_name' },
            { 
                data: 'price', 
                width: '120px',
                render: function(data, type, row) {
                    return 'Rp ' + data;
                }
            },
            { data: 'description' },
            { 
                data: 'is_available', 
                width: '100px', 
                className: 'text-center',
                render: function(data, type, row) {
                    return data == 1 
                        ? '<span class="badge badge-success">Tersedia</span>' 
                        : '<span class="badge badge-secondary">Tidak Tersedia</span>';
                }
            },
            { data: 'sort_order', width: '80px', className: 'text-center' },
            { data: 'created_at', width: '120px' },
            { 
                data: 'actions', 
                width: '250px',
                className: 'text-center',
                orderable: false,
                render: function(data, type, row) {
                    var btns = '';
                    btns += '<button class="btn btn-sm btn-info mr-1" onclick="openEditModal(' + row.id + ')">';
                    btns += '<i class="fas fa-edit"></i> Edit';
                    btns += '</button>';
                    
                    btns += '<button class="btn btn-sm btn-' + (row.is_available == 1 ? 'warning' : 'success') + ' mr-1" onclick="toggleAvailable(' + row.id + ', ' + row.is_available + ')">';
                    btns += '<i class="fas fa-' + (row.is_available == 1 ? 'eye-slash' : 'eye') + '"></i> ' + (row.is_available == 1 ? 'Sembunyikan' : 'Tampilkan');
                    btns += '</button>';
                    
                    btns += '<button class="btn btn-sm btn-danger" onclick="deleteMenu(' + row.id + ')">';
                    btns += '<i class="fas fa-trash"></i> Hapus';
                    btns += '</button>';
                    
                    return btns;
                }
            }
        ],
        order: [[7, 'asc']],
        lengthMenu: [10, 25, 50, 100],
        pageLength: 10,
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
            emptyTable: 'Tidak ada data menu items',
            zeroRecords: 'Tidak ditemukan data yang sesuai'
        }
    });
    
    // jQuery Validation untuk form
    $('#menuForm').validate({
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
                minlength: 3,
                maxlength: 100,
                remote: {
                    url: '<?= site_url('admin_menu/check_name_unique_ajax') ?>',
                    type: 'POST',
                    data: {
                        _token: '<?= csrf_token() ?>',
                        name: function() {
                            return $('#name').val();
                        },
                        exclude_id: function() {
                            return $('#menuId').val();
                        }
                    }
                }
            },
            category_id: {
                required: true,
                number: true,
                min: 1
            },
            price: {
                required: true,
                number: true,
                min: 100
            },
            sort_order: {
                required: true,
                number: true,
                min: 0
            },
            image: {
                extension: 'jpg|jpeg|png',
                filesize: 2097152  // 2MB in bytes
            }
        },
        messages: {
            name: {
                required: 'Nama menu wajib diisi',
                minlength: 'Minimal 3 karakter',
                maxlength: 'Maksimal 100 karakter',
                remote: 'Nama menu sudah digunakan'
            },
            category_id: {
                required: 'Kategori wajib dipilih',
                number: 'Pilih kategori yang valid'
            },
            price: {
                required: 'Harga wajib diisi',
                number: 'Harus berupa angka',
                min: 'Harga minimal Rp 100'
            },
            sort_order: {
                required: 'Urutan wajib diisi',
                number: 'Harus berupa angka',
                min: 'Nilai minimal 0'
            },
            image: {
                extension: 'File harus berformat JPG atau PNG',
                filesize: 'Ukuran file maksimal 2MB'
            }
        },
        submitHandler: function(form) {
            var formData = new FormData(form);
            var submitUrl = $(form).attr('action');
            
            $.ajax({
                url: submitUrl,
                type: 'POST',
                data: formData,
                dataType: 'json',
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#btnSave').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
                },
                success: function(response) {
                    if (response.success) {
                        showToast('success', response.message);
                        $('#menuModal').modal('hide');
                        menuTable.ajax.reload(null, false);
                    } else {
                        showToast('danger', response.message || 'Terjadi kesalahan');
                        if (response.errors) {
                            $.each(response.errors, function(key, value) {
                                $('#' + key).addClass('is-invalid');
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    showToast('danger', 'Gagal menyimpan data: ' + error);
                },
                complete: function() {
                    $('#btnSave').prop('disabled', false).html('<i class="fas fa-save"></i> Simpan');
                }
            });
        }
    });
});

// Open modal untuk tambah menu baru
function openAddModal() {
    $('#menuForm')[0].reset();
    $('#menuId').val('');
    $('#oldImage').val('');
    $('#menuModalLabel').text('Tambah Menu Item');
    $('#imagePreview').attr('src', '<?= base_url('assets/img/placeholder.png') ?>');
    $('#menuForm').find('.is-invalid').removeClass('is-invalid').addClass('is-valid');
    
    // Reset Select2
    $('#category_id').val(null).trigger('change');
    
    // Reset AutoNumeric
    AutoNumeric.getInstance('#price').set('');
}

// Open modal untuk edit menu
function openEditModal(id) {
    $.ajax({
        url: '<?= site_url('admin_menu/edit') ?>/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var data = response.data;
                $('#menuId').val(data.id);
                $('#name').val(data.name);
                $('#category_id').val(data.category_id).trigger('change');
                
                // Set price dengan AutoNumeric
                var autoNumericInstance = AutoNumeric.getInstance('#price');
                autoNumericInstance.set(data.price);
                
                $('#description').val(data.description || '');
                $('#sort_order').val(data.sort_order);
                $('#is_available').prop('checked', data.is_available == 1);
                $('#oldImage').val(data.image || '');
                
                // Set image preview
                if (data.thumb_url) {
                    $('#imagePreview').attr('src', data.thumb_url);
                } else {
                    $('#imagePreview').attr('src', '<?= base_url('assets/img/placeholder.png') ?>');
                }
                
                $('#menuModalLabel').text('Edit Menu Item');
                $('#menuModal').modal('show');
            } else {
                showToast('danger', response.message);
            }
        },
        error: function() {
            showToast('danger', 'Gagal mengambil data menu');
        }
    });
}

// Preview image sebelum upload
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#imagePreview').attr('src', e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Toggle available status
function toggleAvailable(id, currentStatus) {
    var confirmMsg = currentStatus == 1 
        ? 'Apakah Anda yakin ingin menyembunyikan menu ini?' 
        : 'Apakah Anda yakin ingin menampilkan menu ini?';
    
    if (!confirm(confirmMsg)) {
        return;
    }
    
    $.ajax({
        url: '<?= site_url('admin_menu/toggle_available') ?>/' + id,
        type: 'POST',
        data: { _token: '<?= csrf_token() ?>' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast('success', response.message);
                menuTable.ajax.reload(null, false);
            } else {
                showToast('danger', response.message);
            }
        },
        error: function() {
            showToast('danger', 'Gagal mengubah status');
        }
    });
}

// Delete menu
function deleteMenu(id) {
    if (!confirm('Apakah Anda yakin ingin menonaktifkan menu item ini? Menu akan di-soft delete.')) {
        return;
    }
    
    $.ajax({
        url: '<?= site_url('admin_menu/delete') ?>/' + id,
        type: 'POST',
        data: { _token: '<?= csrf_token() ?>' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showToast('success', response.message);
                menuTable.ajax.reload(null, false);
            } else {
                showToast('danger', response.message);
            }
        },
        error: function() {
            showToast('danger', 'Gagal menghapus menu');
        }
    });
}

// Apply filters
function applyFilters() {
    menuTable.ajax.reload();
}

// Custom validation method untuk filesize
$.validator.addMethod('filesize', function(value, element, param) {
    if (element.files && element.files[0]) {
        return element.files[0].size <= param;
    }
    return true; // No file selected, validation passes
}, 'Ukuran file melebihi batas yang ditentukan');
</script>

</body>
</html>
