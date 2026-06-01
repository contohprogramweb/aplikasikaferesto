<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Category Controller
 * Mengelola CRUD kategori menu (UC-ADM-02)
 */
class Admin_category extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        
        // Load model
        $this->load->model('category_model');
        
        // Load form validation
        $this->load->library('form_validation');
        
        // Set CSRF protection
        $this->security->csrf_verify();
    }

    /**
     * Index - Halaman utama kategori dengan DataTable
     */
    public function index()
    {
        $data['title'] = 'Manajemen Kategori';
        $data['page_title'] = 'Daftar Kategori Menu';
        $data['breadcrumb'] = [
            ['label' => 'Dashboard', 'url' => site_url('admin/dashboard')],
            ['label' => 'Kategori', 'url' => '#']
        ];
        
        $this->load->view('admin/categories', $data);
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
        $columns = ['id', 'name', 'description', 'sort_order', 'is_active', 'created_at'];
        $order_field = $columns[$order_column] ?? 'sort_order';
        
        // Build filters
        $filters = [
            'search' => $search,
            'status' => $this->input->post('status') ?? ''
        ];
        
        // Get data from model
        $result = $this->category_model->get_datatable(
            $filters,
            $length,
            $start,
            $order_field,
            $order_dir
        );
        
        // Format data for DataTables
        $data = [];
        foreach ($result['data'] as $row) {
            $item_count = $this->category_model->count_items($row['id']);
            
            $data[] = [
                'id' => $row['id'],
                'name' => esc_html($row['name']),
                'description' => esc_html($row['description'] ?? '-'),
                'sort_order' => $row['sort_order'],
                'is_active' => (int)$row['is_active'],
                'item_count' => $item_count,
                'created_at' => date('d-m-Y H:i', strtotime($row['created_at'])),
                'actions' => [
                    'edit' => site_url('admin_category/edit/' . $row['id']),
                    'delete' => site_url('admin_category/delete/' . $row['id']),
                    'toggle' => site_url('admin_category/toggle_status/' . $row['id'])
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
     * Save - Create/Update category via modal
     * POST: id (optional), name, description, icon, sort_order, is_active
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
                'field' => 'name',
                'label' => 'Nama Kategori',
                'rules' => 'required|min_length[2]|max_length[100]|callback_check_unique_name[' . $id . ']'
            ],
            [
                'field' => 'description',
                'label' => 'Deskripsi',
                'rules' => 'max_length[500]'
            ],
            [
                'field' => 'icon',
                'label' => 'Icon',
                'rules' => 'max_length[50]'
            ],
            [
                'field' => 'sort_order',
                'label' => 'Urutan',
                'rules' => 'required|integer|greater_than_equal_to[0]'
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
            'name' => $this->security->xss_clean($this->input->post('name')),
            'description' => $this->security->xss_clean($this->input->post('description')),
            'icon' => $this->security->xss_clean($this->input->post('icon')),
            'sort_order' => (int)$this->input->post('sort_order'),
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ];
        
        try {
            if ($is_update) {
                // Update existing
                $result = $this->category_model->update($id, $data);
                
                if ($result) {
                    $this->log_activity('UPDATE_CATEGORY', 'Update kategori: ' . $data['name'], $id);
                    
                    // Invalidate cache
                    $this->_invalidate_cache($id);
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'success' => TRUE,
                            'message' => 'Kategori berhasil diperbarui'
                        ]));
                } else {
                    throw new Exception('Gagal memperbarui kategori');
                }
            } else {
                // Create new
                $new_id = $this->category_model->create($data);
                
                if ($new_id) {
                    $this->log_activity('CREATE_CATEGORY', 'Buat kategori baru: ' . $data['name'], $new_id);
                    
                    // Invalidate cache
                    $this->_invalidate_cache();
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'success' => TRUE,
                            'message' => 'Kategori berhasil ditambahkan',
                            'id' => $new_id
                        ]));
                } else {
                    throw new Exception('Gagal menambahkan kategori');
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
     * Edit - Get category data for modal
     */
    public function edit($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $category = $this->category_model->get_by_id($id);
        
        if (!$category) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Kategori tidak ditemukan'
                ]));
            return;
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => TRUE,
                'data' => $category
            ]));
    }

    /**
     * Delete - Soft delete atau hard delete jika tidak punya item
     */
    public function delete($id)
    {
        $this->security->csrf_verify();
        
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $category = $this->category_model->get_by_id($id);
        
        if (!$category) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Kategori tidak ditemukan'
                ]));
            return;
        }
        
        // Count items in category
        $item_count = $this->category_model->count_items($id);
        
        try {
            if ($item_count > 0) {
                // Soft delete jika masih punya item
                $result = $this->category_model->soft_delete($id);
                
                if ($result) {
                    $this->log_activity('SOFT_DELETE_CATEGORY', 'Nonaktifkan kategori: ' . $category['name'] . ' (masih ada ' . $item_count . ' item)', $id);
                    
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'success' => TRUE,
                            'message' => 'Kategori dinonaktifkan (masih ada ' . $item_count . ' item)',
                            'soft_delete' => TRUE
                        ]));
                } else {
                    throw new Exception('Gagal menonaktifkan kategori');
                }
            } else {
                // Hard delete jika tidak punya item
                $result = $this->category_model->hard_delete($id);
                
                if ($result) {
                    $this->log_activity('HARD_DELETE_CATEGORY', 'Hapus kategori: ' . $category['name'], $id);
                    
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'success' => TRUE,
                            'message' => 'Kategori berhasil dihapus',
                            'soft_delete' => FALSE
                        ]));
                } else {
                    throw new Exception('Gagal menghapus kategori');
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
     * Toggle Status - AJAX toggle active/inactive
     */
    public function toggle_status($id)
    {
        $this->security->csrf_verify();
        
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $category = $this->category_model->get_by_id($id);
        
        if (!$category) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Kategori tidak ditemukan'
                ]));
            return;
        }
        
        $new_status = $this->category_model->toggle_status($id);
        
        if ($new_status !== FALSE) {
            $status_text = $new_status == 1 ? 'aktif' : 'nonaktif';
            $this->log_activity('TOGGLE_CATEGORY_STATUS', 'Toggle status kategori ' . $category['name'] . ' menjadi ' . $status_text, $id);
            
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
     * Callback: Check unique name
     */
    public function check_unique_name($str, $exclude_id = null)
    {
        if ($this->category_model->name_exists($str, $exclude_id)) {
            $this->form_validation->set_message('check_unique_name', 'Nama kategori sudah digunakan');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Invalidate cache categories
     * TTL 1 jam untuk cache
     */
    private function _invalidate_cache($id = null)
    {
        // Delete global cache
        delete_cache('categories_all');
        delete_cache('menu_all');
        
        // Delete specific category cache jika ada ID
        if ($id !== null) {
            delete_cache('category_' . $id);
        }
    }
}
