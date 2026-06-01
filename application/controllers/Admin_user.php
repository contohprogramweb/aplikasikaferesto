<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin User Controller
 * Mengelola CRUD user management (UC-ADM-04)
 * 
 * Fitur:
 * - DataTable server-side
 * - Create/update user dengan password hashing BCRYPT
 * - Soft delete (active = 0) dengan constraint:
 *   * Tidak bisa nonaktifkan diri sendiri
 *   * Minimal 1 admin aktif harus ada
 * - AJAX toggle status
 * - Remote validation untuk username unique
 * - Password pattern validation (huruf+angka, min 6 char)
 */
class Admin_user extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        
        // Load model
        $this->load->model('user_model');
        
        // Load form validation
        $this->load->library('form_validation');
        
        // Set CSRF protection
        $this->security->csrf_verify();
    }

    /**
     * Index - Halaman utama user management dengan DataTable
     */
    public function index()
    {
        $data['title'] = 'Manajemen User';
        $data['page_title'] = 'Daftar User';
        $data['breadcrumb'] = [
            ['label' => 'Dashboard', 'url' => site_url('admin/dashboard')],
            ['label' => 'User Management', 'url' => '#']
        ];
        
        $this->load->view('admin/users', $data);
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
        $columns = ['u.id', 'u.full_name', 'u.username', 'u.role', 'u.active', 'u.last_login', 'u.created_at'];
        $order_field = $columns[$order_column] ?? 'u.id';
        
        // Build filters
        $filters = [
            'search' => $search,
            'role' => $this->input->post('role') ?? '',
            'status' => $this->input->post('status') ?? ''
        ];
        
        // Get data from model
        $result = $this->user_model->get_datatable(
            $filters,
            $length,
            $start,
            $order_field,
            $order_dir
        );
        
        // Format data for DataTables
        $data = [];
        foreach ($result['data'] as $row) {
            $data[] = [
                'id' => $row['id'],
                'full_name' => esc_html($row['full_name']),
                'username' => esc_html($row['username']),
                'email' => esc_html($row['email']),
                'role' => esc_html($row['role']),
                'role_display' => $this->_get_role_display($row['role']),
                'active' => (int)$row['active'],
                'status_display' => $row['active'] == 1 ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Nonaktif</span>',
                'last_login' => !empty($row['last_login']) ? date('d-m-Y H:i', strtotime($row['last_login'])) : '-',
                'created_at' => date('d-m-Y', strtotime($row['created_at'])),
                'actions' => [
                    'edit' => site_url('admin_user/edit/' . $row['id']),
                    'delete' => site_url('admin_user/delete/' . $row['id']),
                    'toggle' => site_url('admin_user/toggle_status/' . $row['id'])
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
     * Save - Create/Update user
     * Handle password hashing BCRYPT
     * POST: id (optional), full_name, username, email, role, password, active
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
                'field' => 'full_name',
                'label' => 'Nama Lengkap',
                'rules' => 'required|max_length[100]'
            ],
            [
                'field' => 'username',
                'label' => 'Username',
                'rules' => 'required|min_length[4]|max_length[20]|regex_match[/^[a-zA-Z0-9_]+$/]|callback_check_unique_username[' . $id . ']'
            ],
            [
                'field' => 'email',
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[100]|callback_check_unique_email[' . $id . ']'
            ],
            [
                'field' => 'role',
                'label' => 'Role',
                'rules' => 'required|in_list[admin,cashier,kitchen,waiter]'
            ],
            [
                'field' => 'active',
                'label' => 'Status',
                'rules' => 'integer|in_list[0,1]'
            ]
        ]);
        
        // Password validation hanya untuk create atau jika diisi saat update
        if (!$is_update || !empty($this->input->post('password'))) {
            $this->form_validation->set_rules([
                [
                    'field' => 'password',
                    'label' => 'Password',
                    'rules' => 'required|min_length[6]|regex_match[/^(?=.*[A-Za-z])(?=.*\d).{6,}$/]'
                ]
            ]);
        }
        
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
            'full_name' => $this->security->xss_clean($this->input->post('full_name')),
            'username' => $this->security->xss_clean($this->input->post('username')),
            'email' => $this->security->xss_clean($this->input->post('email')),
            'role' => $this->input->post('role'),
            'active' => (int)($this->input->post('active') ?? 1)
        ];
        
        // Handle password
        $password = $this->input->post('password');
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }
        
        try {
            if ($is_update) {
                // Update existing
                $result = $this->user_model->update($id, $data);
                
                if ($result) {
                    $this->log_activity('UPDATE_USER', 'Update user: ' . $data['username'], $id, 'high');
                    
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'success' => TRUE,
                            'message' => 'User berhasil diperbarui'
                        ]));
                } else {
                    throw new Exception('Gagal memperbarui user');
                }
            } else {
                // Create new
                $new_id = $this->user_model->create($data);
                
                if ($new_id) {
                    $this->log_activity('CREATE_USER', 'Buat user baru: ' . $data['username'], $new_id, 'high');
                    
                    $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'success' => TRUE,
                            'message' => 'User berhasil ditambahkan',
                            'id' => $new_id
                        ]));
                } else {
                    throw new Exception('Gagal menambahkan user');
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
     * Edit - Get user data for modal
     */
    public function edit($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $user = $this->user_model->get_by_id($id);
        
        if (!$user) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'User tidak ditemukan'
                ]));
            return;
        }
        
        // Remove password dari response
        unset($user['password']);
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => TRUE,
                'data' => $user
            ]));
    }

    /**
     * Delete - Soft delete user (active = 0)
     * Constraint:
     * - Tidak bisa nonaktifkan diri sendiri
     * - Minimal 1 admin aktif harus ada
     */
    public function delete($id)
    {
        $this->security->csrf_verify();
        
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $current_user_id = $this->get_user_id();
        
        // Check tidak bisa nonaktifkan diri sendiri
        if ($id == $current_user_id) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri'
                ]));
            return;
        }
        
        $user = $this->user_model->get_by_id($id);
        
        if (!$user) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'User tidak ditemukan'
                ]));
            return;
        }
        
        // Check minimal 1 admin aktif
        if ($user['role'] === 'admin' && $user['active'] == 1) {
            $active_admins = $this->user_model->count_active_admins();
            
            if ($active_admins <= 1) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => FALSE,
                        'message' => 'Tidak dapat menonaktifkan user. Minimal 1 admin aktif harus ada.'
                    ]));
                return;
            }
        }
        
        try {
            // Soft delete
            $result = $this->user_model->soft_delete($id);
            
            if ($result) {
                $this->log_activity('SOFT_DELETE_USER', 'Nonaktifkan user: ' . $user['username'], $id, 'high');
                
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => TRUE,
                        'message' => 'User berhasil dinonaktifkan'
                    ]));
            } else {
                throw new Exception('Gagal menonaktifkan user');
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
     * Toggle Status - AJAX toggle active 0/1
     * Constraint:
     * - Tidak bisa toggle diri sendiri
     * - Minimal 1 admin aktif harus ada
     */
    public function toggle_status($id)
    {
        $this->security->csrf_verify();
        
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $current_user_id = $this->get_user_id();
        
        // Check tidak bisa toggle diri sendiri
        if ($id == $current_user_id) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'Anda tidak dapat mengubah status akun Anda sendiri'
                ]));
            return;
        }
        
        $user = $this->user_model->get_by_id($id);
        
        if (!$user) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => FALSE,
                    'message' => 'User tidak ditemukan'
                ]));
            return;
        }
        
        // Check jika akan nonaktifkan admin
        if ($user['role'] === 'admin' && $user['active'] == 1) {
            $active_admins = $this->user_model->count_active_admins();
            
            if ($active_admins <= 1) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => FALSE,
                        'message' => 'Tidak dapat menonaktifkan user. Minimal 1 admin aktif harus ada.'
                    ]));
                return;
            }
        }
        
        $new_status = $this->user_model->toggle_status($id);
        
        if ($new_status !== FALSE) {
            $status_text = $new_status == 1 ? 'aktif' : 'nonaktif';
            $this->log_activity('TOGGLE_USER_STATUS', 'Toggle status user ' . $user['username'] . ' menjadi ' . $status_text, $id, 'high');
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => TRUE,
                    'active' => $new_status,
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
     * Callback: Check unique username (case-insensitive)
     */
    public function check_unique_username($str, $exclude_id = null)
    {
        if ($this->user_model->username_exists($str, $exclude_id)) {
            $this->form_validation->set_message('check_unique_username', 'Username sudah digunakan');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Callback: Check unique email (case-insensitive)
     */
    public function check_unique_email($str, $exclude_id = null)
    {
        if ($this->user_model->email_exists($str, $exclude_id)) {
            $this->form_validation->set_message('check_unique_email', 'Email sudah digunakan');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Remote validation untuk username (AJAX)
     */
    public function check_username_unique_ajax()
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $username = $this->input->post('username');
        $exclude_id = $this->input->post('exclude_id');
        
        $exists = $this->user_model->username_exists($username, $exclude_id);
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'valid' => !$exists,
                'message' => $exists ? 'Username sudah digunakan' : ''
            ]));
    }

    /**
     * Remote validation untuk email (AJAX)
     */
    public function check_email_unique_ajax()
    {
        if (!$this->input->is_ajax_request()) {
            show_error('Method not allowed', 405);
        }
        
        $email = $this->input->post('email');
        $exclude_id = $this->input->post('exclude_id');
        
        $exists = $this->user_model->email_exists($email, $exclude_id);
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'valid' => !$exists,
                'message' => $exists ? 'Email sudah digunakan' : ''
            ]));
    }

    /**
     * Get role display name
     */
    private function _get_role_display($role)
    {
        $roles = [
            'admin' => 'Administrator',
            'cashier' => 'Kasir',
            'kitchen' => 'Dapur',
            'waiter' => 'Pelayan'
        ];
        
        return $roles[$role] ?? ucfirst($role);
    }
}
