/**
 * Admin Module JavaScript
 * Shared DataTable initialization, modal handler, AJAX CRUD
 * Untuk modul Categories dan Tables
 */

(function($) {
    'use strict';

    // Global utility functions
    window.AdminUtils = {
        /**
         * Show alert message
         * @param {string} type - success, danger, warning, info
         * @param {string} message
         * @param {string} container - Selector untuk container alert
         */
        showAlert: function(type, message, container) {
            container = container || '#alertContainer';
            
            var alertClass = {
                'success': 'alert-success',
                'danger': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            };
            
            var icon = {
                'success': 'fa-check-circle',
                'danger': 'fa-exclamation-circle',
                'warning': 'fa-exclamation-triangle',
                'info': 'fa-info-circle'
            };
            
            var html = '<div class="alert ' + (alertClass[type] || 'alert-info') + ' alert-dismissible fade show" role="alert">';
            html += '<i class="fas ' + (icon[type] || 'fa-info-circle') + ' mr-2"></i>' + this.escapeHtml(message);
            html += '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
            html += '<span aria-hidden="true">&times;</span></button></div>';
            
            $(container).html(html);
            
            // Auto close setelah 5 detik
            setTimeout(function() {
                $(container + ' .alert').alert('close');
            }, 5000);
        },

        /**
         * Escape HTML untuk mencegah XSS
         * @param {string} text
         * @returns {string}
         */
        escapeHtml: function(text) {
            if (!text) return '';
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Format currency ke Rupiah
         * @param {number} amount
         * @returns {string}
         */
        formatRupiah: function(amount) {
            return 'Rp ' + Number(amount).toLocaleString('id-ID');
        },

        /**
         * Format date ke format Indonesia
         * @param {string|Date} date
         * @param {string} format
         * @returns {string}
         */
        formatDate: function(date, format) {
            if (!date) return '-';
            var d = new Date(date);
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            
            if (format === 'short') {
                return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
            }
            
            return d.getDate() + '-' + (d.getMonth() + 1) + '-' + d.getFullYear() + ' ' + 
                   String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        },

        /**
         * Confirm dialog dengan custom message
         * @param {string} message
         * @returns {Promise<boolean>}
         */
        confirm: function(message) {
            return new Promise(function(resolve) {
                resolve(window.confirm(message));
            });
        }
    };

    // Initialize DataTables dengan konfigurasi default
    $.fn.adminDataTable = function(options) {
        var defaults = {
            processing: true,
            serverSide: true,
            responsive: true,
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
                emptyTable: 'Tidak ada data',
                zeroRecords: 'Tidak ditemukan data yang sesuai',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data yang ditampilkan',
                infoFiltered: '(difilter dari _MAX_ total data)',
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    previous: '<i class="fas fa-angle-left"></i>'
                }
            },
            order: [[0, 'asc']],
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10
        };

        return this.DataTable($.extend(true, defaults, options));
    };

    // jQuery Validation additional methods
    if ($.validator) {
        // Alphanumeric only
        $.validator.addMethod('alphanumeric', function(value, element) {
            return this.optional(element) || /^[a-zA-Z0-9]+$/.test(value);
        }, 'Hanya boleh huruf dan angka');

        // Pattern validation
        $.validator.addMethod('pattern', function(value, element, param) {
            if (this.optional(element)) {
                return true;
            }
            if (typeof param === 'string') {
                param = new RegExp('^(?:' + param + ')$');
            }
            return param.test(value);
        }, 'Format tidak valid');

        // Remote validation with CSRF token
        $.validator.addMethod('remote_csrf', function(value, element, param) {
            var result = null;
            $.ajax({
                url: param,
                type: 'POST',
                data: {
                    value: value,
                    field: element.name,
                    _token: $('[name="_token"]').val() || $('[name="<?= csrf_token() ?>"]').val()
                },
                async: false,
                dataType: 'json'
            }).done(function(response) {
                result = response.valid === true;
            });
            return result;
        }, 'Nilai sudah digunakan');
    }

    // Sidebar toggle
    $(document).ready(function() {
        $('#sidebarCollapse').on('click', function() {
            $('#sidebar').toggleClass('active');
        });

        // Auto-hide alerts
        $(document).on('closed.bs.alert', function() {
            $(this).remove();
        });

        // Form reset on modal close
        $('.modal').on('hidden.bs.modal', function() {
            $(this).find('form')[0].reset();
            $(this).find('.is-invalid').removeClass('is-invalid');
            $(this).find('.is-valid').removeClass('is-valid');
        });

        // AJAX setup untuk CSRF token
        $.ajaxSetup({
            beforeSend: function(xhr, settings) {
                if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                    var token = $('meta[name="csrf-token"]').attr('content');
                    if (!token) {
                        token = $('[name="_token"]').val() || $('[name*="csrf"]').val();
                    }
                    if (token) {
                        xhr.setRequestHeader('X-CSRF-TOKEN', token);
                    }
                }
            }
        });
    });

})(jQuery);
