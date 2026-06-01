<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Table Controller
 * Mengelola CRUD meja restoran dengan QR code (UC-ADM-03)
 */
class Admin_table extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        
        // Load model
        $this->load->model('table_model');
        
        // Load form validation
        $this->load->library('form_validation');
        
        // Load Dompdf untuk print QR
        $this->load->library('dompdf_lib');
        
        // Set CSRF protection
        $this->security->csrf_verify();
    }

    /**
     * Index - Halaman utama tables dengan DataTable
     */
    public function index()
    {
        $data['title'] = 'Manajemen Meja';
        $data['page_title'] = 'Daftar Meja Restoran';
        $data['breadcrumb'] = [
            ['label' => 'Dashboard', 'url' => site_url('admin/dashboard')],
            ['label' => 'Meja', 'url' => '#']
        ];
        
        $this->load->view('admin/tables', $data);
    }

    /**
     * DataTable server-side response
     * POST: draw, start, length, search[value], order[0][column], order[0][dir]
     */
    public function datatable()
    {
        // Get DataTables parameters
        $draw = $this->input->post('draw') ?? 1;
        $start = $this->input->post('start') ?? 0;
        $length = $this->input->post('length') ?? 10;
        $search = $this->input->post('search')['value'] ?? '';
        $order_column = $this->input->post('order')[0]['column'] ?? 0;
        $order_dir = $this->input->post('order')[0]['dir'] ?? 'ASC';
        
        // Map column index to field name
        $columns = ['id', 'table_number', 'table_name', 'capacity', 'location', 'status', 'is_active', 'created_at'];
        $order_field = $columns[$order_column] ?? 'table_number';
        
        // Build filters
        $filters = [
            'search' => $search,
            'status' => $this->input->post('status') ?? '',
            'location' => $this->input->post('location') ?? ''
        ];
        
        // Get data from model
        $result = $this->table_model->get_datatable(
            $filters,
            $length,
            $start,
            $order_field,
            $order_dir
        );
        
        // Format data for DataTables
        $data = [];
        foreach ($result['data'] as $row) {
            $qr_preview = !empty($row['qr_code']) && file_exists(FCPATH . $row['qr_code']) 
                ? base_url($row['qr_code']) 
                : null;
            
            $data[] = [
                'id' => $row['id'],
                'table_number' => esc_html($row['table_number']),
                'table_name' => esc_html($row['table_name'] ?? '-'),
                'capacity' => (int)$row['capacity'],
                'location' => esc_html($row['location'] ?? '-'),
                'status' => $row['status'],
                'is_active' => (int)$row['is_active'],
                'qr_code' => $row['qr_code'],
                'qr_preview' => $qr_preview,
                'created_at' => date('d-m-Y H:i', strtotime($row['created_at'])),
                'actions' => [
                    'edit' => site_url('admin_table/edit/' . $row['id']),
                    'delete' => site_url('admin_table/delete/' . $row['id']),
                    'toggle' => site_url('admin_table/toggle_status/' . $row['id']),
                    'print_qr' => site_url('admin_table/print_qr/' . $row['id'])
                ]
            ];
        }
        
        // Output JSON
        $output = [
            'draw' => (int)$draw,
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['filtered'],
            'data' => $data
        ];
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($output));
    }

    /**
     * Save - Create/Update table via modal dengan auto-generate QR
     * POST: id (optional), table_number, table_name, capacity, location, is_active
     */
    public function save()
    {
        $this->security->csrf_verify();
        
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        // Validation rules
        $id = $this->input->post('id');
        $is_update = !empty($id);
        
        $this->form_validation->set_rules([
            [
                'field' => 'table_number',
                'label' => 'Kode Meja',
                'rules' => 'required|min_length[2]|max_length[10]|alpha_numeric|callback_check_unique_table_number[' . $id . ']'
            ],
            [
                'field' => 'table_name',
                'label' => 'Nama Meja',
                'rules' => 'max_length[50]'
            ],
            [
                'field' => 'capacity',
                'label' => 'Kapasitas',
                'rules' => 'required|integer|greater_than[0]|less_than_equal_to[50]'
            ],
            [
                'field' => 'location',
                'label' => 'Lokasi',
                'rules' => 'max_length[50]'
            ]
        ]);
        
        if ($this->form_validation->run() === FALSE) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'errors' => $this->form_validation->error_array()
                ]));
            return;
        }
        
        // Prepare data
        $data = [
            'table_number' => strtoupper($this->security->xss_clean($this->input->post('table_number'))),
            'table_name' => $this->security->xss_clean($this->input->post('table_name')),
            'capacity' => (int)$this->input->post('capacity'),
            'location' => $this->security->xss_clean($this->input->post('location')),
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ];
        
        try {
            if ($is_update) {
                // Update existing (auto regenerate QR jika table_number berubah)
                $old_data = $this->table_model->get_by_id($id);
                $regenerate_qr = $data['table_number'] !== $old_data['table_number'];
                
                $result = $this->table_model->update($id, $data, $regenerate_qr);
                
                if ($result) {
                    $qr_action = $regenerate_qr ? 'dengan regenerate QR' : '';
                    $this->log_activity('UPDATE_TABLE', 'Update meja: ' . $data['table_number'] . ' ' . $qr_action, $id);
                    
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'success' => TRUE,
                            'message' => 'Meja berhasil diperbarui',
                            'qr_regenerated' => $regenerate_qr
                        ]));
                } else {
                    throw new Exception('Gagal memperbarui meja');
                }
            } else {
                // Create new dengan auto-generate QR
                $new_id = $this->table_model->create($data);
                
                if ($new_id) {
                    $this->log_activity('CREATE_TABLE', 'Buat meja baru: ' . $data['table_number'], $new_id);
                    
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'success' => TRUE,
                            'message' => 'Meja berhasil ditambahkan dengan QR code',
                            'id' => $new_id
                        ]));
                } else {
                    throw new Exception('Gagal menambahkan meja');
                }
            }
        } catch (Exception $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Edit - Get table data for modal
     */
    public function edit($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $table = $this->table_model->get_by_id($id);
        
        if (!$table) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Meja tidak ditemukan'
                ]));
            return;
        }
        
        // Add QR preview URL
        if (!empty($table['qr_code']) && file_exists(FCPATH . $table['qr_code'])) {
            $table['qr_preview'] = base_url($table['qr_code']);
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => TRUE,
                'data' => $table
            ]));
    }

    /**
     * Delete - Validasi: tidak bisa hapus jika meja terisi/menunggu bayar/dibersihkan
     */
    public function delete($id)
    {
        $this->security->csrf_verify();
        
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $result = $this->table_model->hard_delete($id);
        
        if ($result['success']) {
            $this->log_activity('DELETE_TABLE', 'Hapus meja: ' . $id, $id);
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => TRUE,
                    'message' => $result['message']
                ]));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => $result['message']
                ]));
        }
    }

    /**
     * Toggle Status - AJAX toggle active/inactive
     */
    public function toggle_status($id)
    {
        $this->security->csrf_verify();
        
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $table = $this->table_model->get_by_id($id);
        
        if (!$table) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Meja tidak ditemukan'
                ]));
            return;
        }
        
        $new_status = $this->table_model->toggle_status($id);
        
        if ($new_status !== FALSE) {
            $status_text = $new_status == 1 ? 'aktif' : 'nonaktif';
            $this->log_activity('TOGGLE_TABLE_STATUS', 'Toggle status meja ' . $table['table_number'] . ' menjadi ' . $status_text, $id);
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => TRUE,
                    'is_active' => $new_status,
                    'message' => 'Status berhasil diubah menjadi ' . $status_text
                ]));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Gagal mengubah status'
                ]));
        }
    }

    /**
     * Print QR - Generate PDF label (50x50mm, 16 label per A4) via Dompdf
     */
    public function print_qr($id)
    {
        $table = $this->table_model->get_by_id($id);
        
        if (!$table) {
            show_error('Meja tidak ditemukan', 404);
        }
        
        if (empty($table['qr_code']) || !file_exists(FCPATH . $table['qr_code'])) {
            show_error('QR code tidak ditemukan', 404);
        }
        
        // Load view untuk QR label
        $data = [
            'table' => $table,
            'qr_image' => base_url($table['qr_code'])
        ];
        
        $html = $this->load->view('admin/tables/print_qr', $data, TRUE);
        
        // Generate PDF dengan Dompdf
        $pdf_content = $this->dompdf_lib->generate_custom($html, 'A4', 'portrait');
        
        // Download
        $filename = 'QR_' . $table['table_number'] . '.pdf';
        $this->dompdf_lib->download($filename, $pdf_content);
    }

    /**
     * Regenerate All QR - Batch regenerate jika BASE_URL berubah
     */
    public function regenerate_all_qr()
    {
        $this->security->csrf_verify();
        
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        try {
            $result = $this->table_model->regenerate_all_qr();
            
            $this->log_activity('REGENERATE_ALL_QR', 'Regenerate semua QR code. Sukses: ' . $result['success'] . ', Gagal: ' . $result['failed']);
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => TRUE,
                    'message' => sprintf('Berhasil regenerate %d QR code, %d gagal', $result['success'], $result['failed']),
                    'success_count' => $result['success'],
                    'failed_count' => $result['failed']
                ]));
        } catch (Exception $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Callback: Check unique table number
     */
    public function check_unique_table_number($str, $exclude_id = null)
    {
        if ($this->table_model->table_number_exists($str, $exclude_id)) {
            $this->form_validation->set_message('check_unique_table_number', 'Kode meja sudah digunakan');
            return FALSE;
        }
        return TRUE;
    }
}
