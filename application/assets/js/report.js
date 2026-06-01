/**
 * Report Module JavaScript
 * Menangani chart dan interaksi untuk laporan penjualan
 */

let categoryChart = null;
let revenueChart = null;

/**
 * Initialize Category Pie Chart
 * @param {Array} data - Array of {category_name, total_qty, total_sales}
 */
function initCategoryChart(data) {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    
    if (categoryChart) {
        categoryChart.destroy();
    }
    
    const labels = data.map(item => item.category_name);
    const values = data.map(item => parseFloat(item.total_sales));
    
    categoryChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pendapatan per Kategori',
                data: values,
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40',
                    '#C9CBCF'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
}

/**
 * Initialize Revenue Line Chart
 * @param {Array} data - Array of {date, revenue}
 */
function initRevenueChart(data) {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    if (revenueChart) {
        revenueChart.destroy();
    }
    
    const labels = data.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    });
    const values = data.map(item => parseFloat(item.revenue));
    
    revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pendapatan Harian',
                data: values,
                borderColor: '#4facfe',
                backgroundColor: 'rgba(79, 172, 254, 0.2)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
}

/**
 * Refresh chart data via AJAX
 * @param {string} startDate - YYYY-MM-DD
 * @param {string} endDate - YYYY-MM-DD
 */
function refreshChartData(startDate, endDate) {
    $.ajax({
        url: BASE_URL + 'admin/report/api_chart_data',
        method: 'GET',
        data: {
            start_date: startDate,
            end_date: endDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.sales_by_category) {
                initCategoryChart(response.sales_by_category);
            }
            if (response.daily_revenue) {
                initRevenueChart(response.daily_revenue);
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to refresh chart data:', error);
            alert('Gagal memuat data chart. Silakan refresh halaman.');
        }
    });
}

// Auto-refresh every 5 minutes if on report page
$(document).ready(function() {
    const $startDate = $('#start_date');
    const $endDate = $('#end_date');
    
    // Update charts when date inputs change
    $startDate.add($endDate).on('change', function() {
        const start = $startDate.val();
        const end = $endDate.val();
        
        if (start && end && start <= end) {
            refreshChartData(start, end);
        }
    });
    
    // Periodic refresh
    setInterval(function() {
        if ($startDate.val() && $endDate.val()) {
            refreshChartData($startDate.val(), $endDate.val());
        }
    }, 300000); // 5 minutes
});
