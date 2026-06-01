<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin_Controller.php';

/**
 * Admin Report Controller
 * Mengelola laporan penjualan, pendapatan, dan statistik restoran.
 * 
 * Implements: UC-ADM-05, BR-54 s/d BR-57
 */
class Admin_report extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('report_model');
        $this->load->model('order_model');
        $this->load->library('dompdf_lib');
        
        // Ensure admin role
        if ($this->session->userdata('role') !== 'admin') {
            show_error('Akses ditolak. Hanya administrator yang dapat mengakses laporan.', 403);
        }
    }

    /**
     * Dashboard Laporan (Default View)
     * Menampilkan ringkasan dengan filter tanggal
     */
    public function index()
    {
        // Default filter: 7 hari terakhir
        $start_date = $this->input->get('start_date') ?? date('Y-m-d', strtotime('-7 days'));
        $end_date = $this->input->get('end_date') ?? date('Y-m-d');
        
        // Validate dates
        if (strtotime($start_date) > strtotime($end_date)) {
            $this->session->set_flashdata('error', 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir.');
            redirect('admin/report');
        }

        $data['page_title'] = 'Laporan Penjualan';
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        
        // Get summary stats
        $data['summary'] = $this->report_model->get_dashboard_summary($start_date, $end_date);
        
        // Get chart data
        $data['sales_by_category'] = $this->report_model->get_sales_by_category($start_date, $end_date);
        $data['top_items'] = $this->report_model->get_top_items($start_date, $end_date, 10);
        $data['daily_revenue'] = $this->report_model->get_daily_revenue($start_date, $end_date);
        
        // Get recent transactions
        $data['transactions'] = $this->report_model->get_transactions($start_date, $end_date);
        
        $this->load->view('admin/reports', $data);
    }

    /**
     * Daily Report Detail
     * Tampilan detail per hari
     */
    public function daily()
    {
        $date = $this->input->get('date') ?? date('Y-m-d');
        
        $data['page_title'] = 'Laporan Harian - ' . date('d/m/Y', strtotime($date));
        $data['date'] = $date;
        
        // Same as index but single day
        $data['summary'] = $this->report_model->get_dashboard_summary($date, $date);
        $data['sales_by_category'] = $this->report_model->get_sales_by_category($date, $date);
        $data['top_items'] = $this->report_model->get_top_items($date, $date, 10);
        $data['transactions'] = $this->report_model->get_transactions($date, $date);
        
        $this->load->view('admin/reports_daily', $data);
    }

    /**
     * Monthly Report
     * Agregasi per bulan
     */
    public function monthly()
    {
        $year = $this->input->get('year') ?? date('Y');
        $month = $this->input->get('month') ?? date('m');
        
        // Validate month
        if (!checkdate($month, 1, $year)) {
            show_error('Bulan atau tahun tidak valid.');
        }
        
        $start_date = date('Y-m-01', strtotime("$year-$month-01"));
        $end_date = date('Y-m-t', strtotime("$year-$month-01")); // Last day of month
        
        $data['page_title'] = 'Laporan Bulanan - ' . date('F Y', strtotime("$year-$month-01"));
        $data['year'] = $year;
        $data['month'] = $month;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        
        $data['summary'] = $this->report_model->get_dashboard_summary($start_date, $end_date);
        $data['sales_by_category'] = $this->report_model->get_sales_by_category($start_date, $end_date);
        $data['top_items'] = $this->report_model->get_top_items($start_date, $end_date, 10);
        
        // Daily breakdown for the month
        $data['daily_revenue'] = $this->report_model->get_daily_revenue($start_date, $end_date);
        
        $this->load->view('admin/reports_monthly', $data);
    }

    /**
     * Export PDF Report
     * Generate PDF untuk laporan
     */
    public function export_pdf()
    {
        $start_date = $this->input->get('start_date') ?? date('Y-m-d', strtotime('-7 days'));
        $end_date = $this->input->get('end_date') ?? date('Y-m-d');
        $type = $this->input->get('type') ?? 'summary'; // summary, detailed
        
        $data['summary'] = $this->report_model->get_dashboard_summary($start_date, $end_date);
        $data['sales_by_category'] = $this->report_model->get_sales_by_category($start_date, $end_date);
        $data['top_items'] = $this->report_model->get_top_items($start_date, $end_date, 10);
        $data['daily_revenue'] = $this->report_model->get_daily_revenue($start_date, $end_date);
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        $data['generated_at'] = date('d/m/Y H:i:s');
        
        $html = $this->load->view('admin/pdf/report', $data, TRUE);
        
        $filename = 'Laporan_Penjualan_' . date('YmdHis') . '.pdf';
        
        $this->dompdf_lib->generate($html, $filename);
    }

    /**
     * AJAX Endpoint untuk Chart Data
     * Digunakan oleh report.js untuk update dinamis
     */
    public function api_chart_data()
    {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        
        if (!$start_date || !$end_date) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_code(400)
                ->set_output(json_encode(['error' => 'Parameter tanggal diperlukan']));
        }
        
        $response = [
            'sales_by_category' => $this->report_model->get_sales_by_category($start_date, $end_date),
            'daily_revenue' => $this->report_model->get_daily_revenue($start_date, $end_date),
            'top_items' => $this->report_model->get_top_items($start_date, $end_date, 5)
        ];
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
}
