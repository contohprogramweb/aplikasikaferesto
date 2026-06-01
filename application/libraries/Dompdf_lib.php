<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dompdf Library Wrapper untuk CodeIgniter 3
 * Sesuai SRS v4.0 Bab 7.3.3 - PDF Generation
 * 
 * Requires: dompdf/dompdf ^1.0 (via Composer)
 */

require_once APPPATH . 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class Dompdf_lib {
    
    protected $ci;
    protected $dompdf;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ci = &get_instance();
        
        // Konfigurasi Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('tempDir', sys_get_temp_dir() . '/dompdf');
        
        // Buat direktori temp jika belum ada
        if (!file_exists($options->getTempDir())) {
            mkdir($options->getTempDir(), 0755, true);
        }
        
        $this->dompdf = new Dompdf($options);
        
        $this->ci->load->config('app');
    }
    
    /**
     * Generate PDF untuk struk/receipt thermal 80mm
     * 
     * @param int $order_id ID order
     * @return string Base64 encoded PDF atau FALSE jika gagal
     */
    public function generate_receipt($order_id) {
        $this->ci->load->model('order_model');
        $this->ci->load->model('order_item_model');
        $this->ci->load->helper('custom');
        
        // Ambil data order
        $order = $this->ci->order_model->get_with_items($order_id);
        
        if (!$order) {
            return FALSE;
        }
        
        // Load view untuk receipt
        $html = $this->ci->load->view('pdf/receipt', ['order' => $order], TRUE);
        
        // Set paper size: 80mm thermal receipt
        // 80mm = ~227 points, panjang menyesuaikan content
        $this->dompdf->setPaper([0, 0, 227, 500], 'portrait');
        $this->dompdf->loadHtml($html);
        $this->dompdf->render();
        
        return $this->dompdf->output();
    }
    
    /**
     * Generate PDF untuk report A4 landscape
     * 
     * @param array $data Data untuk report
     * @param string $title Judul report
     * @param string $view_path Path view (relative ke application/views)
     * @return string Base64 encoded PDF atau FALSE jika gagal
     */
    public function generate_report($data, $title, $view_path = 'pdf/report') {
        // Load view untuk report
        $html = $this->ci->load->view($view_path, [
            'data' => $data,
            'title' => $title,
            'generated_at' => date('d-m-Y H:i:s')
        ], TRUE);
        
        // Set paper size: A4 landscape
        $this->dompdf->setPaper('A4', 'landscape');
        $this->dompdf->loadHtml($html);
        $this->dompdf->render();
        
        return $this->dompdf->output();
    }
    
    /**
     * Generate PDF dengan custom HTML
     * 
     * @param string $html HTML content
     * @param string $paper_size Paper size ('A4', 'letter', dll)
     * @param string $orientation Orientation ('portrait', 'landscape')
     * @return string Base64 encoded PDF
     */
    public function generate_custom($html, $paper_size = 'A4', $orientation = 'portrait') {
        $this->dompdf->setPaper($paper_size, $orientation);
        $this->dompdf->loadHtml($html);
        $this->dompdf->render();
        
        return $this->dompdf->output();
    }
    
    /**
     * Download PDF langsung ke browser
     * 
     * @param string $filename Nama file
     * @param string $pdf_content Content PDF (binary)
     */
    public function download($filename, $pdf_content) {
        $this->dompdf->stream($filename, [
            'Attachment' => TRUE,
            'compress' => TRUE
        ]);
    }
    
    /**
     * Output PDF inline di browser
     * 
     * @param string $filename Nama file
     * @param string $pdf_content Content PDF (binary)
     */
    public function inline($filename, $pdf_content) {
        $this->dompdf->stream($filename, [
            'Attachment' => FALSE
        ]);
    }
    
    /**
     * Get Dompdf instance untuk akses langsung
     * 
     * @return Dompdf
     */
    public function get_dompdf() {
        return $this->dompdf;
    }
}
