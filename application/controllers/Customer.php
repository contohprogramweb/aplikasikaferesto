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

    /**
     * UC-CUST-06: Order Status Page
     * Displays real-time order status with timeline/stepper
     * BR-14: Only shows orders for active table
     */
    public function status()
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

        // Get active orders for this table
        $this->load->model('Order_model');
        $orders = $this->Order_model->get_open_order_by_table($table['id']);
        
        $order_items = [];
        $order_status_map = [];
        
        if ($orders) {
            $order_items = $this->Order_model->get_items_by_order($orders['id']);
            $order_status_map[$orders['id']] = $orders['status'];
        }

        // Check if can request bill (BR-15)
        $can_request_bill = false;
        $has_delivered_item = false;
        $all_items_delivered = true;
        
        foreach ($order_items as $item) {
            if (in_array($item['status'], ['delivered', 'completed'])) {
                $has_delivered_item = true;
            }
            if (!in_array($item['status'], ['delivered', 'completed', 'cancelled'])) {
                $all_items_delivered = false;
            }
        }
        
        // Check table status for bill request
        $blocked_table_statuses = ['menunggu_bayar', 'lunas'];
        $table_status = strtolower($table['status']);
        
        if (!in_array($table_status, $blocked_table_statuses)) {
            // Config: BILL_AFTER_ALL_DELIVERED (default: false = at least 1 item)
            $bill_after_all = $this->config->item('BILL_AFTER_ALL_DELIVERED') ?? false;
            
            if ($bill_after_all) {
                $can_request_bill = $all_items_delivered && count($order_items) > 0;
            } else {
                $can_request_bill = $has_delivered_item;
            }
        }

        $data = [
            'page_title' => 'Status Pesanan',
            'token' => $token,
            'table' => $table,
            'orders' => $orders,
            'order_items' => $order_items,
            'order_status_map' => $order_status_map,
            'can_request_bill' => $can_request_bill,
            'current_timestamp' => date('Y-m-d H:i:s'),
            'restaurant_name' => $this->config->item('restaurant_name') ?: 'Smart Restaurant'
        ];

        $this->load->view('customer/status', $data);
    }

    /**
     * UC-CUST-06: AJAX Order Status Polling Endpoint
     * Returns delta updates since last_timestamp
     * Response: {items[], order_status, has_changes}
     */
    public function order_status()
    {
        $this->output->set_content_type('application/json');
        
        $token = $this->input->get('token');
        $last_timestamp = $this->input->get('last_timestamp');
        $last_id = $this->input->get('last_id', 0);
        
        if (!$token) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Token required',
                'code' => 422
            ]);
            return;
        }

        // Validate session
        $session = $this->Customer_session_model->get_by_token($token);
        
        if (!$session) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Session invalid',
                'code' => 401
            ]);
            return;
        }

        // Get table info (BR-14)
        $table = $this->Table_model->get_by_id($session['table_id']);
        
        if (!$table) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Table not found',
                'code' => 404
            ]);
            return;
        }

        $this->load->model('Order_model');
        
        // Get open order for this table
        $order = $this->Order_model->get_open_order_by_table($table['id']);
        
        $items = [];
        $order_status = null;
        $has_changes = false;
        
        if ($order) {
            $order_status = $order['status'];
            
            // Get items with delta filtering
            if (!empty($last_timestamp)) {
                // Delta query: items updated after last_timestamp OR id > last_id
                $this->db->select('oi.*, m.name as menu_item_name, m.image as menu_item_image');
                $this->db->from('order_items oi');
                $this->db->join('menu_items m', 'm.id = oi.menu_item_id', 'left');
                $this->db->where('oi.order_id', $order['id']);
                $this->db->where("(oi.updated_at > " . $this->db->escape($last_timestamp) . " OR oi.id > " . (int)$last_id . ")");
                $this->db->order_by('oi.created_at', 'ASC');
                $query = $this->db->get();
                $items = $query->result_array();
                $has_changes = count($items) > 0;
            }
            
            // If no delta or initial load, get all items
            if (empty($last_timestamp) || empty($items)) {
                $items = $this->Order_model->get_items_by_order($order['id']);
            }
        }

        echo json_encode([
            'status' => 'success',
            'data' => [
                'items' => $items,
                'order_status' => $order_status,
                'has_changes' => $has_changes,
                'current_timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * UC-CUST-07: Request Bill
     * Customer requests payment (BR-15, BR-16)
     * Updates orders.status and tables.status to 'menunggu_bayar'
     */
    public function request_bill()
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

        // Validate session
        $session = $this->Customer_session_model->get_by_token($token);
        
        if (!$session) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Session invalid',
                'code' => 401
            ]);
            return;
        }

        // Get table info
        $table = $this->Table_model->get_by_id($session['table_id']);
        
        if (!$table) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Table not found',
                'code' => 404
            ]);
            return;
        }

        // BR-15: Validate bill request eligibility
        $blocked_table_statuses = ['menunggu_bayar', 'lunas'];
        $table_status = strtolower($table['status']);
        
        if (in_array($table_status, $blocked_table_statuses)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tagihan sudah diminta atau sudah lunas',
                'code' => 400
            ]);
            return;
        }

        $this->load->model('Order_model');
        
        // Get open order for this table
        $order = $this->Order_model->get_open_order_by_table($table['id']);
        
        if (!$order) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tidak ada pesanan aktif',
                'code' => 400
            ]);
            return;
        }

        // Get order items
        $order_items = $this->Order_model->get_items_by_order($order['id']);
        
        // Validate: at least 1 delivered item (or all, depending on config)
        $has_delivered_item = false;
        $all_items_delivered = true;
        
        foreach ($order_items as $item) {
            if (in_array($item['status'], ['delivered', 'completed'])) {
                $has_delivered_item = true;
            }
            if (!in_array($item['status'], ['delivered', 'completed', 'cancelled'])) {
                $all_items_delivered = false;
            }
        }
        
        $bill_after_all = $this->config->item('BILL_AFTER_ALL_DELIVERED') ?? false;
        
        if ($bill_after_all) {
            if (!$all_items_delivered || count($order_items) === 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Semua item harus terkirim sebelum meminta tagihan',
                    'code' => 400
                ]);
                return;
            }
        } else {
            if (!$has_delivered_item) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Minimal 1 item harus terkirim sebelum meminta tagihan',
                    'code' => 400
                ]);
                return;
            }
        }

        // Calculate totals (excluding cancelled items)
        $subtotal = 0;
        foreach ($order_items as $item) {
            if ($item['status'] !== 'cancelled') {
                $subtotal += (float) $item['subtotal'];
            }
        }
        
        $tax_rate = $this->config->item('tax_rate') ?: 0.10;
        $service_rate = $this->config->item('service_rate') ?: 0.05;
        $tax_amount = $subtotal * $tax_rate;
        $service_amount = $subtotal * $service_rate;
        $total = $subtotal + $tax_amount + $service_amount;

        // Update order status to 'menunggu_bayar'
        $this->Order_model->update($order['id'], [
            'status' => 'menunggu_bayar',
            'payment_status' => 'pending',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Update table status to 'menunggu_bayar' (BR-16)
        $this->Table_model->update_status($table['id'], 'menunggu_bayar');

        // Log activity
        $this->load->model('Activity_log_model');
        $this->Activity_log_model->create([
            'action' => 'bill_requested',
            'description' => 'Customer requested bill for order #' . $order['order_number'],
            'related_table' => 'orders',
            'related_id' => $order['id'],
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'priority' => 'high'
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Tagihan berhasil diminta. Mohon tunggu konfirmasi kasir.',
            'data' => [
                'order_id' => $order['id'],
                'order_number' => $order['order_number'],
                'subtotal' => $subtotal,
                'tax_amount' => $tax_amount,
                'service_amount' => $service_amount,
                'total' => $total,
                'table_status' => 'menunggu_bayar'
            ]
        ]);
    }
}
