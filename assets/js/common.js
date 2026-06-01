/**
 * Common JavaScript untuk Sistem Manajemen Kafe & Resto
 * Sesuai SRS v4.0 Bab 7.3.5 - Frontend Utilities
 * 
 * Fitur:
 * - CSRF Token handling untuk AJAX
 * - Auto-refresh CSRF token jika 403
 * - Toast notification system (Toastr.js wrapper)
 * - Rate limit error handler (429)
 * - Utility functions
 */

// ============================================================================
// CSRF TOKEN HANDLING
// ============================================================================

/**
 * Get cookie value by name
 * @param {string} name - Cookie name
 * @returns {string|null} Cookie value or null
 */
function getCookie(name) {
    let cookieValue = null;
    if (document.cookie && document.cookie !== '') {
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            // Does this cookie string begin with the name we want?
            if (cookie.substring(0, name.length + 1) === (name + '=')) {
                cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                break;
            }
        }
    }
    return cookieValue;
}

// Get CSRF token from cookie
const CSRF_TOKEN_NAME = 'csrf_test_name'; // Default CI3 CSRF token name
const CSRF_HEADER_NAME = 'X-CSRF-TOKEN';

/**
 * Setup jQuery AJAX dengan CSRF token otomatis
 */
$.ajaxSetup({
    beforeSend: function(xhr, settings) {
        // Hanya tambahkan CSRF token untuk request non-GET
        if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
            const csrfToken = getCookie(CSRF_TOKEN_NAME);
            if (csrfToken) {
                xhr.setRequestHeader(CSRF_HEADER_NAME, csrfToken);
            }
        }
    },
    statusCode: {
        /**
         * Handle 403 Forbidden - kemungkinan CSRF token expired
         */
        403: function(xhr, status, error) {
            console.warn('403 Forbidden - Possible CSRF token issue');
            
            // Refresh CSRF token dan retry request
            refreshCsrfToken().then(function(newToken) {
                console.log('CSRF token refreshed, retrying request...');
                
                // Retry original request dengan token baru
                const originalRequest = xhr.originalRequest || {};
                if (originalRequest.url) {
                    $.ajax({
                        url: originalRequest.url,
                        type: originalRequest.type || 'POST',
                        data: originalRequest.data,
                        success: function(response) {
                            showToast('success', 'Request berhasil diproses ulang');
                            if (originalRequest.success) {
                                originalRequest.success(response);
                            }
                        },
                        error: function(xhr, status, error) {
                            showToast('error', 'Request gagal setelah retry');
                            if (originalRequest.error) {
                                originalRequest.error(xhr, status, error);
                            }
                        }
                    });
                }
            }).catch(function(err) {
                console.error('Failed to refresh CSRF token:', err);
                showToast('error', 'Sesi Anda telah berakhir. Silakan refresh halaman.');
                
                // Redirect ke login jika bukan di halaman login
                if (!window.location.href.includes('/auth/login')) {
                    setTimeout(function() {
                        window.location.href = '/auth/login';
                    }, 2000);
                }
            });
        },
        
        /**
         * Handle 429 Too Many Requests - Rate limiting
         */
        429: function(xhr, status, error) {
            console.warn('429 Too Many Requests - Rate limited');
            
            const retryAfter = xhr.getResponseHeader('Retry-After');
            const waitTime = retryAfter ? parseInt(retryAfter) : 5;
            
            showToast('warning', `Terlalu banyak request. Silakan tunggu ${waitTime} detik.`, {
                timeOut: waitTime * 1000 + 2000
            });
            
            // Log rate limit event
            logEvent('rate_limit_hit', {
                url: xhr.originalRequest?.url || 'unknown',
                retry_after: waitTime
            });
        },
        
        /**
         * Handle 401 Unauthorized
         */
        401: function(xhr, status, error) {
            console.warn('401 Unauthorized');
            showToast('error', 'Silakan login kembali');
            
            setTimeout(function() {
                window.location.href = '/auth/login';
            }, 1500);
        },
        
        /**
         * Handle 500 Internal Server Error
         */
        500: function(xhr, status, error) {
            console.error('500 Internal Server Error:', error);
            showToast('error', 'Terjadi kesalahan pada server. Silakan coba lagi.');
        },
        
        /**
         * Handle 503 Service Unavailable
         */
        503: function(xhr, status, error) {
            console.error('503 Service Unavailable');
            showToast('error', 'Sistem sedang maintenance. Silakan coba beberapa saat lagi.');
        }
    }
});

/**
 * Refresh CSRF token dari server
 * @returns {Promise<string>} New CSRF token
 */
function refreshCsrfToken() {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/auth/refresh_csrf',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.csrf_token) {
                    resolve(response.csrf_token);
                } else {
                    reject(new Error('Invalid CSRF token response'));
                }
            },
            error: function(xhr, status, error) {
                reject(new Error('Failed to refresh CSRF token: ' + error));
            }
        });
    });
}

// ============================================================================
// TOAST NOTIFICATION SYSTEM (Toastr.js Wrapper)
// ============================================================================

/**
 * Show toast notification
 * @param {string} type - Type: 'success', 'error', 'warning', 'info'
 * @param {string} message - Message to display
 * @param {object} options - Toastr options
 */
function showToast(type, message, options = {}) {
    // Default options
    const defaultOptions = {
        closeButton: true,
        debug: false,
        newestOnTop: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        preventDuplicates: false,
        onclick: null,
        showDuration: 300,
        hideDuration: 1000,
        timeOut: 5000,
        extendedTimeOut: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut',
        iconClass: getToastIconClass(type)
    };
    
    // Merge options
    const finalOptions = $.extend({}, defaultOptions, options);
    
    // Show toast based on type
    switch (type.toLowerCase()) {
        case 'success':
            toastr.success(message, 'Berhasil', finalOptions);
            break;
        case 'error':
            toastr.error(message, 'Error', finalOptions);
            break;
        case 'warning':
            toastr.warning(message, 'Peringatan', finalOptions);
            break;
        case 'info':
        default:
            toastr.info(message, 'Info', finalOptions);
            break;
    }
}

/**
 * Get icon class for toast type
 * @param {string} type
 * @returns {string} Icon class
 */
function getToastIconClass(type) {
    const iconClasses = {
        'success': 'toast-success',
        'error': 'toast-error',
        'warning': 'toast-warning',
        'info': 'toast-info'
    };
    return iconClasses[type.toLowerCase()] || 'toast-info';
}

/**
 * Show success toast (shortcut)
 * @param {string} message
 */
function showSuccess(message) {
    showToast('success', message);
}

/**
 * Show error toast (shortcut)
 * @param {string} message
 */
function showError(message) {
    showToast('error', message);
}

/**
 * Show warning toast (shortcut)
 * @param {string} message
 */
function showWarning(message) {
    showToast('warning', message);
}

/**
 * Show info toast (shortcut)
 * @param {string} message
 */
function showInfo(message) {
    showToast('info', message);
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Format currency to IDR (Rupiah)
 * @param {number} amount
 * @returns {string} Formatted currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

/**
 * Format date to Indonesian format
 * @param {Date|string} date
 * @param {string} format - 'datetime', 'date', 'time'
 * @returns {string} Formatted date
 */
function formatDate(date, format = 'datetime') {
    if (!date) return '-';
    
    const d = new Date(date);
    const options = {
        datetime: { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        },
        date: {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        },
        time: {
            hour: '2-digit',
            minute: '2-digit'
        }
    };
    
    return d.toLocaleDateString('id-ID', options[format] || options.datetime);
}

/**
 * Debounce function to limit execution rate
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle function to limit execution frequency
 * @param {Function} func - Function to throttle
 * @param {number} limit - Time limit in milliseconds
 * @returns {Function} Throttled function
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Log event for analytics/debugging
 * @param {string} eventName
 * @param {object} data
 */
function logEvent(eventName, data = {}) {
    console.log(`[Event] ${eventName}:`, data);
    
    // Send to server if needed
    // $.post('/api/log-event', { event: eventName, data: data });
}

/**
 * Confirm dialog with SweetAlert style
 * @param {string} title
 * @param {string} message
 * @returns {Promise<boolean>}
 */
function confirmDialog(title, message) {
    return new Promise(function(resolve) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                resolve(result.isConfirmed);
            });
        } else {
            // Fallback to native confirm
            resolve(confirm(`${title}\n${message}`));
        }
    });
}

/**
 * Loading overlay show/hide
 * @param {boolean} show - Show or hide
 * @param {string} message - Loading message
 */
function loadingOverlay(show, message = 'Memproses...') {
    const overlayId = 'loading-overlay';
    let overlay = document.getElementById(overlayId);
    
    if (show) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = overlayId;
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">${message}</p>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    } else {
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
}

// ============================================================================
// INITIALIZATION
// ============================================================================

$(document).ready(function() {
    console.log('Common JS loaded successfully');
    
    // Initialize Toastr with default settings
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 5000
        };
    }
    
    // Store original jQuery ajax for retry functionality
    const originalAjax = $.ajax;
    $.ajax = function(settings) {
        // Store original request for retry
        if (settings.xhrFields) {
            settings.xhrFields.onprogress = function(e) {
                // Track progress if needed
            };
        }
        
        const xhr = originalAjax(settings);
        xhr.originalRequest = settings;
        return xhr;
    };
    
    // Auto-hide alerts after 5 seconds
    $('.alert-dismissible').not('.alert-permanent').delay(5000).fadeOut('slow', function() {
        $(this).alert('close');
    });
});

// Export functions for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        getCookie,
        refreshCsrfToken,
        showToast,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        formatCurrency,
        formatDate,
        debounce,
        throttle,
        confirmDialog,
        loadingOverlay
    };
}
