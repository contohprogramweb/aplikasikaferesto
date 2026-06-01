<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer Controller
 * Handles customer-facing operations for dine-in ordering system
 * Based on SRS v4.0 UC-CUST-01 and UC-CUST-02
 */
class Customer extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Table_model');
        $this->load->model('Customer_session_model');
        $this->load->model('Menu_item_model');
        $this->load->model('Category_model');
        $this->load->helper(['form', 'text']);
    }

    /**
     * UC-CUST-01: Landing Page - Table Identification
     * Displays input for table code or QR scan
     */
    public function index()
    {
        // Check if customer already has valid session
        $token = $this->input->get('token');
        if (!$token) {
            $token = $this->session->userdata('customer_token');
        }

        if ($token) {
            $session = $this->Customer_session_model->get_by_token($token);
            if ($session && strtotime($session['expires_at']) > time()) {
                // Valid session, redirect to menu
                redirect('customer/menu?token=' . $token);
            }
        }

        // Check for QR code table parameter
        $table_code = $this->input->get('table');
        
        $data = [
            'page_title' => 'Selamat Datang',
            'table_code' => $table_code,
            'restaurant_name' => $this->config->item('restaurant_name') ?: 'Smart Restaurant',
            'logo_url' => base_url('assets/images/logo.png')
        ];

        $this->load->view('customer/landing', $data);
    }

    /**
     * UC-CUST-02: Menu Page - Digital Menu Display
     * Shows menu after table identification
     */
    public function menu()
    {
        $token = $this->input->get('token');
        
        if (!$token) {
            $token = $this->session->userdata('customer_token');
        }

        if (!$token) {
            redirect('customer');
            return;
        }

        // Validate session
        $session = $this->Customer_session_model->get_by_token($token);
        
        if (!$session) {
            $this->session->unset_userdata('customer_token');
            redirect('customer?error=session_invalid');
            return;
        }

        // Check if session expired
        if (strtotime($session['expires_at']) <= time()) {
            $this->Customer_session_model->delete($session['id']);
            $this->session->unset_userdata('customer_token');
            redirect('customer?error=session_expired');
            return;
        }

        // Get table info
        $table = $this->Table_model->get_by_id($session['table_id']);
        
        if (!$table || !$table['is_active']) {
            redirect('customer?error=table_invalid');
            return;
        }

        // Update session last activity
        $this->Customer_session_model->update_last_activity($session['id']);

        // Get categories and menu items
        $categories = $this->Category_model->get_active();
        $menu_items = $this->Menu_item_model->get_all_available();

        // Format menu items by category
        $items_by_category = [];
        foreach ($categories as $cat) {
            $items_by_category[$cat['id']] = [
                'category' => $cat,
                'items' => []
            ];
        }

        foreach ($menu_items as $item) {
            $cat_id = $item['category_id'];
            if (isset($items_by_category[$cat_id])) {
                $items_by_category[$cat_id]['items'][] = $item;
            }
        }

        // Get cart data from session
        $cart_data = json_decode($session['cart_data'], true) ?: [];
        $cart_count = 0;
        $cart_total = 0;
        
        foreach ($cart_data as $cart_item) {
            $cart_count += isset($cart_item['qty']) ? (int)$cart_item['qty'] : 0;
            $cart_total += isset($cart_item['subtotal']) ? (float)$cart_item['subtotal'] : 0;
        }

        $data = [
            'page_title' => 'Menu Digital',
            'token' => $token,
            'table' => $table,
            'categories' => $categories,
            'items_by_category' => $items_by_category,
            'cart_count' => $cart_count,
            'cart_total' => $cart_total,
            'session_expires_at' => $session['expires_at'],
            'restaurant_name' => $this->config->item('restaurant_name') ?: 'Smart Restaurant'
        ];

        $this->load->view('customer/menu', $data);
    }

    /**
     * AJAX: Validate Table Code
     * UC-CUST-01: Step 3-5
     */
    public function check_table()
    {
        $this->output->set_content_type('application/json');
        
        $table_code = strtoupper(trim($this->input->post('table_code')));
        
        // Validation: alphanumeric, not empty
        if (empty($table_code)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Kode meja tidak boleh kosong',
                'code' => 422
            ]);
            return;
        }

        if (!preg_match('/^[A-Z0-9]{1,10}$/', $table_code)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Kode meja harus alphanumeric (maksimal 10 karakter)',
                'code' => 422
            ]);
            return;
        }

        // Check table exists
        $table = $this->Table_model->get_by_code($table_code);
        
        if (!$table) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Kode meja tidak ditemukan. Silakan periksa kembali atau hubungi staf.',
                'code' => 404
            ]);
            return;
        }

        // Check table status - not available for: Tutup, Rusak, Dibersihkan
        $blocked_statuses = ['maintenance', 'closed', 'cleaning']; // Map to SRS: Tutup, Rusak, Dibersihkan
        
        // Map Indonesian statuses to English
        $status_map = [
            'tersedia' => 'available',
            'terisi' => 'occupied',
            'menunggu_bayar' => 'waiting_payment',
            'dibersihkan' => 'cleaning',
            'tutup' => 'closed',
            'rusak' => 'maintenance'
        ];

        $table_status = strtolower($table['status']);
        
        if (in_array($table_status, ['closed', 'maintenance', 'cleaning', 'tutup', 'rusak', 'dibersihkan'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Meja ini tidak tersedia. Silakan pilih meja lain.',
                'code' => 403,
                'table_status' => $table_status
            ]);
            return;
        }

        // Check if table already has active session with open bill
        $existing_session = $this->Customer_session_model->get_active_by_table($table['id']);
        
        $has_open_bill = false;
        if ($existing_session) {
            // Check if there's an open order
            $this->load->model('Order_model');
            $open_order = $this->Order_model->get_open_order_by_table($table['id']);
            $has_open_bill = !empty($open_order);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Meja valid',
            'data' => [
                'table_id' => $table['id'],
                'table_code' => $table['table_number'],
                'table_name' => $table['table_name'],
                'has_existing_session' => !empty($existing_session),
                'has_open_bill' => $has_open_bill
            ]
        ]);
    }

    /**
     * AJAX: Create Customer Session
     * UC-CUST-01: Step 7-11
     * Token format: tbl_[table_id]_[random_hash_16char]
     */
    public function create_session()
    {
        $this->output->set_content_type('application/json');
        
        $table_id = $this->input->post('table_id');
        
        if (!$table_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid table ID',
                'code' => 422
            ]);
            return;
        }

        // Generate token: tbl_[table_id]_[random_hash_16char]
        $random_hash = bin2hex(random_bytes(8)); // 16 characters
        $token = 'tbl_' . $table_id . '_' . $random_hash;

        // Delete any existing session for this table
        $this->Customer_session_model->delete_by_table($table_id);

        // Create new session
        $session_data = [
            'table_id' => $table_id,
            'token' => $token,
            'cart_data' => json_encode([]),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
        ];

        $session_id = $this->Customer_session_model->create($session_data);

        if (!$session_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal membuat session. Silakan coba lagi.',
                'code' => 500
            ]);
            return;
        }

        // Update table status to occupied if it was available
        $table = $this->Table_model->get_by_id($table_id);
        if ($table && strtolower($table['status']) === 'available' || strtolower($table['status']) === 'tersedia') {
            $this->Table_model->update_status($table_id, 'occupied');
        }

        // Store token in server session as fallback
        $this->session->set_userdata('customer_token', $token);

        // Log activity
        $this->load->model('Activity_log_model');
        $this->Activity_log_model->create([
            'action' => 'customer_session_created',
            'description' => 'Customer session created for table ' . $table['table_number'],
            'related_table' => 'tables',
            'related_id' => $table_id,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'priority' => 'low'
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Session berhasil dibuat',
            'data' => [
                'token' => $token,
                'table_id' => $table_id,
                'expires_at' => $session_data['expires_at'],
                'redirect_url' => site_url('customer/menu?token=' . urlencode($token))
            ]
        ]);
    }

    /**
     * AJAX: Validate Session Token
     * UC-CUST-01: Session Recovery (A3)
     */
    public function validate_session()
    {
        $this->output->set_content_type('application/json');
        
        $token = $this->input->post('token');
        
        if (!$token) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Token tidak provided',
                'code' => 422
            ]);
            return;
        }

        $session = $this->Customer_session_model->get_by_token($token);
        
        if (!$session) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Session tidak ditemukan',
                'code' => 404,
                'valid' => false
            ]);
            return;
        }

        // Check expiration
        if (strtotime($session['expires_at']) <= time()) {
            // Session expired
            echo json_encode([
                'status' => 'error',
                'message' => 'Session telah berakhir',
                'code' => 410,
                'valid' => false,
                'expired' => true
            ]);
            return;
        }

        // Update last activity to extend session
        $this->Customer_session_model->update_last_activity($session['id']);

        // Get table info
        $table = $this->Table_model->get_by_id($session['table_id']);

        // Get cart data
        $cart_data = json_decode($session['cart_data'], true) ?: [];

        echo json_encode([
            'status' => 'success',
            'message' => 'Session valid',
            'valid' => true,
            'data' => [
                'session_id' => $session['id'],
                'table_id' => $session['table_id'],
                'table_code' => $table ? $table['table_number'] : null,
                'cart_data' => $cart_data,
                'expires_at' => $session['expires_at'],
                'time_remaining' => strtotime($session['expires_at']) - time()
            ]
        ]);
    }

    /**
     * Heartbeat to extend session
     * Used for offline session extension (UC-CUST-01 A4)
     */
    public function heartbeat()
    {
        $this->output->set_content_type('application/json');
        
        $token = $this->input->post('token');
        
        if (!$token) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Token required',
                'code' => 422
            ]);
            return;
        }

        $session = $this->Customer_session_model->get_by_token($token);
        
        if (!$session) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Session not found',
                'code' => 404
            ]);
            return;
        }

        // Extend session by 30 minutes if still valid
        if (strtotime($session['expires_at']) > time()) {
            $new_expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $this->Customer_session_model->update($session['id'], [
                'expires_at' => $new_expires
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Session extended',
                'data' => [
                    'expires_at' => $new_expires,
                    'time_remaining' => strtotime($new_expires) - time()
                ]
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Session expired',
                'code' => 410
            ]);
        }
    }
}
