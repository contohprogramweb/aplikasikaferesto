<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin Menu Controller
 * Mengelola CRUD menu items (UC-ADM-01)
 * 
 * Fitur:
 * - DataTable server-side dengan join categories
 * - Upload gambar dengan resize dan thumbnail
 * - Soft delete (is_deleted = 1)
 * - AJAX toggle available status
 * - Remote validation untuk nama unique
 * - Cache invalidation saat CRUD
 */
class Admin_menu extends Admin_Controller {

    private $upload_path;
    private $thumb_path;

    public function __construct()
    {
        parent::__construct();
        
        // Load model
        $this->load->model('menu_model');
        $this->load->model('category_model');
        
        // Load form validation dan upload library
        $this->load->library('form_validation');
        $this->load->library('upload');
        $this->load->library('image_lib');
        
        // Set CSRF protection
        $this->security->csrf_verify();
        
        // Setup upload paths
        $this->upload_path = FCPATH . 'uploads/menu/';
        $this->thumb_path = FCPATH . 'uploads/menu/thumbnails/';
    }

    /**
     * Index - Halaman utama menu items dengan DataTable
     */
    public function index()
    {
        $data['title'] = 'Manajemen Menu Items';
        $data['page_title'] = 'Daftar Menu Items';
        $data['breadcrumb'] = [
            ['label' => 'Dashboard', 'url' => site_url('admin/dashboard')],
            ['label' => 'Menu Items', 'url' => '#']
        ];
        
        // Load categories untuk dropdown
        $data['categories'] = $this->category_model->get_active_list();
        
        $this->load->view('admin/menu_items', $data);
    }

    /**
     * DataTable server-side response
     * POST: draw, start, length, search[value], order[0][column], order[0][dir]
     * Join dengan categories untuk mendapatkan nama kategori
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
        $columns = ['m.id', 'm.name', 'c.name', 'm.price', 'm.is_available', 'm.sort_order', 'm.created_at'];
        $order_field = $columns[$order_column] ?? 'm.sort_order';
        
        // Build filters
        $filters = [
            'search' => $search,
            'category_id' => $this->input->post('category_id') ?? '',
            'status' => $this->input->post('status') ?? ''
        ];
        
        // Get data from model dengan join categories
        $result = $this->menu_model->get_datatable(
            $filters,
            $length,
            $start,
            $order_field,
            $order_dir
        );
        
        // Format data for DataTables
        $data = [];
        foreach ($result['data'] as $row) {
            // Generate thumbnail URL
            $thumb_url = !empty($row['image']) ? base_url('uploads/menu/thumbnails/' . basename($row['image'])) : base_url('assets/img/placeholder.png');
            
            $data[] = [
                'id' => $row['id'],
                'name' => esc_html($row['name']),
                'category_name' => esc_html($row['category_name']),
                'price' => number_format($row['price'], 0, ',', '.'),
                'price_raw' => $row['price'],
                'description' => esc_html(substr($row['description'] ?? '', 0, 50)) . (strlen($row['description'] ?? '') > 50 ? '...' : ''),
                'is_available' => (int)$row['is_available'],
                'sort_order' => $row['sort_order'],
                'image' => $row['image'],
                'thumb_url' => $thumb_url,
                'created_at' => date('d-m-Y H:i', strtotime($row['created_at'])),
                'actions' => [
                    'edit' => site_url('admin_menu/edit/' . $row['id']),
                    'delete' => site_url('admin_menu/delete/' . $row['id']),
                    'toggle' => site_url('admin_menu/toggle_available/' . $row['id'])
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
     * Save - Create/Update menu item via modal
     * Handle upload gambar dengan resize dan thumbnail
     * POST: id (optional), name, category_id, price, description, image, is_available, sort_order
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
                'label' => 'Nama Menu',
                'rules' => 'required|min_length[3]|max_length[100]|callback_check_unique_name[' . $id . ']'
            ],
            [
                'field' => 'category_id',
                'label' => 'Kategori',
                'rules' => 'required|integer|greater_than[0]'
            ],
            [
                'field' => 'price',
                'label' => 'Harga',
                'rules' => 'required|numeric|min_length[3]|greater_than_equal_to[100]'
            ],
            [
                'field' => 'description',
                'label' => 'Deskripsi',
                'rules' => 'max_length[1000]'
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
        
        // Handle file upload jika ada
        $image_path = null;
        $upload_error = null;
        
        if (!empty($_FILES['image']['name'])) {
            $upload_result = $this->_do_upload();
            
            if ($upload_result['success']) {
                $image_path = $upload_result['file_name'];
                
                // Delete old image jika update
                if ($is_update && !empty($this->input->post('old_image'))) {
                    $this->_delete_image($this->input->post('old_image'));
                }
            } else {
                $upload_error = $upload_result['error'];
            }
        }
        
        if ($upload_error) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Upload gambar gagal: ' . $upload_error
                ]));
            return;
        }
        
        // Prepare data
        $data = [
            'name' => $this->security->xss_clean($this->input->post('name')),
            'category_id' => (int)$this->input->post('category_id'),
            'price' => (float)$this->input->post('price'),
            'description' => $this->security->xss_clean($this->input->post('description')),
            'sort_order' => (int)$this->input->post('sort_order'),
            'is_available' => $this->input->post('is_available') ? 1 : 0,
            'sku' => $this->_generate_sku($this->input->post('name'))
        ];
        
        if ($image_path) {
            $data['image'] = $image_path;
        }
        
        try {
            if ($is_update) {
                // Update existing
                $result = $this->menu_model->update($id, $data);
                
                if ($result) {
                    $this->log_activity('UPDATE_MENU', 'Update menu item: ' . $data['name'], $id);
                    
                    // Invalidate cache
                    $this->_invalidate_cache();
                    
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'success' => TRUE,
                            'message' => 'Menu item berhasil diperbarui'
                        ]));
                } else {
                    throw new Exception('Gagal memperbarui menu item');
                }
            } else {
                // Create new
                $new_id = $this->menu_model->create($data);
                
                if ($new_id) {
                    $this->log_activity('CREATE_MENU', 'Buat menu item baru: ' . $data['name'], $new_id);
                    
                    // Invalidate cache
                    $this->_invalidate_cache();
                    
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'success' => TRUE,
                            'message' => 'Menu item berhasil ditambahkan',
                            'id' => $new_id
                        ]));
                } else {
                    throw new Exception('Gagal menambahkan menu item');
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
     * Edit - Get menu item data for modal
     */
    public function edit($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $menu = $this->menu_model->get_by_id($id);
        
        if (!$menu) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Menu item tidak ditemukan'
                ]));
            return;
        }
        
        // Add full image URL
        if (!empty($menu['image'])) {
            $menu['image_url'] = base_url('uploads/menu/' . $menu['image']);
            $menu['thumb_url'] = base_url('uploads/menu/thumbnails/' . basename($menu['image']));
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => TRUE,
                'data' => $menu
            ]));
    }

    /**
     * Delete - Soft delete menu item (is_deleted = 1)
     */
    public function delete($id)
    {
        $this->security->csrf_verify();
        
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $menu = $this->menu_model->get_by_id($id);
        
        if (!$menu) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Menu item tidak ditemukan'
                ]));
            return;
        }
        
        try {
            // Soft delete
            $result = $this->menu_model->soft_delete($id);
            
            if ($result) {
                $this->log_activity('SOFT_DELETE_MENU', 'Nonaktifkan menu item: ' . $menu['name'], $id);
                
                // Invalidate cache
                $this->_invalidate_cache();
                
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => TRUE,
                        'message' => 'Menu item berhasil dinonaktifkan'
                    ]));
            } else {
                throw new Exception('Gagal menonaktifkan menu item');
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
     * Toggle Available - AJAX toggle available 0/1
     */
    public function toggle_available($id)
    {
        $this->security->csrf_verify();
        
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $menu = $this->menu_model->get_by_id($id);
        
        if (!$menu) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Menu item tidak ditemukan'
                ]));
            return;
        }
        
        $new_status = $this->menu_model->toggle_available($id);
        
        if ($new_status !== FALSE) {
            $status_text = $new_status == 1 ? 'tersedia' : 'tidak tersedia';
            $this->log_activity('TOGGLE_MENU_AVAILABLE', 'Toggle ketersediaan menu ' . $menu['name'] . ' menjadi ' . $status_text, $id);
            
            // Invalidate cache
            $this->_invalidate_cache();
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => TRUE,
                    'is_available' => $new_status,
                    'message' => 'Ketersediaan berhasil diubah menjadi ' . $status_text
                ]));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Gagal mengubah ketersediaan'
                ]));
        }
    }

    /**
     * Callback: Check unique name (case-insensitive)
     */
    public function check_unique_name($str, $exclude_id = null)
    {
        if ($this->menu_model->name_exists($str, $exclude_id)) {
            $this->form_validation->set_message('check_unique_name', 'Nama menu sudah digunakan');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Remote validation untuk nama (AJAX)
     */
    public function check_name_unique_ajax()
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $name = $this->input->post('name');
        $exclude_id = $this->input->post('exclude_id');
        
        $exists = $this->menu_model->name_exists($name, $exclude_id);
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'valid' => !$exists,
                'message' => $exists ? 'Nama menu sudah digunakan' : ''
            ]));
    }

    /**
     * Do upload gambar dengan validasi
     * Max 2MB, JPG/PNG only
     * Resize ke max 800x800px
     * Thumbnail 200x200px
     * Rename: menu_[timestamp]_[random8].jpg
     * Path: /uploads/menu/YYYY/MM/
     */
    private function _do_upload()
    {
        // Create directory jika belum ada
        $year_month = date('Y/m');
        $target_path = $this->upload_path . $year_month . '/';
        $thumb_target_path = $this->thumb_path . $year_month . '/';
        
        if (!file_exists($target_path)) {
            mkdir($target_path, 0755, TRUE);
        }
        if (!file_exists($thumb_target_path)) {
            mkdir($thumb_target_path, 0755, TRUE);
        }
        
        // Generate filename: menu_[timestamp]_[random8].jpg
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'menu_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
        
        // Validasi manual sebelum upload
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime, $allowed_types)) {
            return ['success' => FALSE, 'error' => 'File harus berformat JPG atau PNG'];
        }
        
        // Check file size
        if ($_FILES['image']['size'] > $max_size) {
            return ['success' => FALSE, 'error' => 'Ukuran file maksimal 2MB'];
        }
        
        // Check extension
        if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
            return ['success' => FALSE, 'error' => 'Ekstensi file harus JPG atau PNG'];
        }
        
        // Upload file
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path . $filename)) {
            return ['success' => FALSE, 'error' => 'Gagal mengupload file'];
        }
        
        // Resize image ke max 800x800px
        $config['image_library'] = 'GD2';
        $config['source_image'] = $target_path . $filename;
        $config['maintain_ratio'] = TRUE;
        $config['width'] = 800;
        $config['height'] = 800;
        
        $this->image_lib->initialize($config);
        
        if (!$this->image_lib->resize()) {
            // Delete uploaded file jika resize gagal
            unlink($target_path . $filename);
            return ['success' => FALSE, 'error' => 'Gagal resize image: ' . $this->image_lib->display_errors()];
        }
        
        // Clear image lib
        $this->image_lib->clear();
        
        // Create thumbnail 200x200px
        $config_thumb['image_library'] = 'GD2';
        $config_thumb['source_image'] = $target_path . $filename;
        $config_thumb['new_image'] = $thumb_target_path . $filename;
        $config_thumb['create_thumbnail'] = TRUE;
        $config_thumb['thumb_marker'] = '';
        $config_thumb['maintain_ratio'] = TRUE;
        $config_thumb['width'] = 200;
        $config_thumb['height'] = 200;
        
        $this->image_lib->initialize($config_thumb);
        
        if (!$this->image_lib->resize()) {
            // Delete uploaded file jika thumbnail gagal
            unlink($target_path . $filename);
            return ['success' => FALSE, 'error' => 'Gagal membuat thumbnail: ' . $this->image_lib->display_errors()];
        }
        
        $this->image_lib->clear();
        
        // Return relative path dari uploads/menu/
        return [
            'success' => TRUE,
            'file_name' => $year_month . '/' . $filename,
            'full_path' => $target_path . $filename,
            'thumb_path' => $thumb_target_path . $filename
        ];
    }

    /**
     * Delete image dan thumbnail
     */
    private function _delete_image($image_path)
    {
        if (empty($image_path)) {
            return;
        }
        
        $full_path = FCPATH . 'uploads/menu/' . $image_path;
        $thumb_path = FCPATH . 'uploads/menu/thumbnails/' . basename($image_path);
        
        if (file_exists($full_path)) {
            unlink($full_path);
        }
        
        if (file_exists($thumb_path)) {
            unlink($thumb_path);
        }
    }

    /**
     * Generate SKU dari nama menu
     */
    private function _generate_sku($name)
    {
        $prefix = 'MENU';
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        return $prefix . '-' . $random;
    }

    /**
     * Invalidate cache menu dan categories
     * TTL 1 jam untuk cache
     */
    private function _invalidate_cache()
    {
        $this->cache->delete('menu_all');
        $this->cache->delete('categories_all');
    }
}
