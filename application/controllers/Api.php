<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API Controller - Centralized AJAX Endpoints
 * Based on SRS v4.0 Bab 7.3
 * 
 * All endpoints return JSON format:
 * Success: {"status": "success", "message": "...", "data": {...}}
 * Error: {"status": "error", "message": "...", "code": 404, "errors": {...}}
 */
class Api extends CI_Controller {

    protected $rate_limiter;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Customer_session_model');
        $this->load->model('Order_model');
        $this->load->model('Menu_item_model');
        $this->load->model('Table_model');
        $this->load->helper(['text', 'security']);
        
        // Load rate limiter library
        $this->load->library('rate_limiter');
        
        // Skip rate limiting for CLI requests
        if (!$this->input->is_cli_request()) {
            // Apply rate limiting based on endpoint
            $method = $this->router->fetch_method();
            $endpoint_map = [
                'table_check' => 'table_check',
                'session_create' => 'session',
                'session_validate' => 'session',
                'session_heartbeat' => 'session',
                'cart_sync' => 'session',
                'order_create' => 'session',
                'order_status' => 'polling',
                'order_bill' => 'session',
                'check_unique' => 'admin'
            ];
            
            if (isset($endpoint_map[$method])) {
                $result = $this->rate_limiter->check_limit($endpoint_map[$method]);
                
                if (!$result['allowed']) {
                    $this->output
                        ->set_status_header(429)
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'status' => 'error',
                            'message' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
                            'code' => 429,
                            'retry_after' => $result['retry_after']
                        ]));
                    exit;
                }
            }
        }
    }

    /**
     * Standard JSON response helper
     */
    protected function json_response($status, $message, $data = null, $code = 200, $errors = null)
    {
        $response = [
            'status' => $status,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        if ($code !== null && $code !== 200) {
            $response['code'] = $code;
        }
        
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    /**
     * CSRF Validation Helper
     * Validates CSRF token from header or POST parameter
     */
    protected function validate_csrf()
    {
        // Skip for GET requests
        if ($this->input->method() !== 'post') {
            return true;
        }
        
        // Check X-CSRF-TOKEN header first
        $token = $this->input->get_request_header('X-CSRF-TOKEN');
        
        // Fallback to POST parameter
        if (empty($token)) {
            $token = $this->input->post('csrf_token');
        }
        
        if (empty($token)) {
            $this->json_response('error', 'CSRF token tidak ditemukan', null, 403);
            return false;
        }
        
        // Validate against CodeIgniter's CSRF token
        $ci_token = $this->security->get_csrf_hash();
        
        // For AJAX requests, we need to check if the token matches the session
        if ($token !== $this->security->get_csrf_token_name() && 
            !hash_equals($ci_token, $token)) {
            $this->json_response('error', 'Aksi tidak diizinkan. Silakan refresh halaman.', null, 403);
            return false;
        }
        
        return true;
    }

    /**
     * Home endpoint for API
     * GET /api
     */
    public function home()
    {
        $this->json_response('success', 'API is running', [
            'version' => '4.0',
            'endpoints' => [
                '/api/table/check',
                '/api/session/create',
                '/api/session/validate',
                '/api/session/heartbeat',
                '/api/csrf/refresh',
                '/api/menu',
                '/api/cart/sync',
                '/api/order/create',
                '/api/order/status',
                '/api/order/bill',
                '/api/check_unique/[entity]'
            ]
        ]);
    }

    /**
     * UC-CUST-03: Sync Cart to Server
     * POST /api/cart/sync
     * Structure: [{menu_item_id, qty, notes, price_snapshot, subtotal}]
     */
    public function cart_sync()
    {
        $this->output->set_content_type('application/json');
        
        // Only accept POST
        if ($this->input->method() !== 'post') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
                'code' => 405
            ]);
            return;
        }

        $token = $this->input->post('token');
        $cart_data = $this->input->post('cart_data');

        // Validate token
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
                'message' => 'Session tidak ditemukan',
                'code' => 404
            ]);
            return;
        }

        // Check session expired
        if (strtotime($session['expires_at']) <= time()) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Session telah berakhir',
                'code' => 410,
                'expired' => true
            ]);
            return;
        }

        // Parse and validate cart data
        $cart_items = [];
        if (!empty($cart_data)) {
            $cart_items = is_array($cart_data) ? $cart_data : json_decode($cart_data, true);
        }

        if (!is_array($cart_items)) {
            $cart_items = [];
        }

        // Validate each cart item (BR-06, BR-07, BR-08)
        $validated_cart = [];
        foreach ($cart_items as $item) {
            // Validate menu_item_id
            if (empty($item['menu_item_id'])) {
                continue;
            }

            // Get menu item to verify availability and price
            $menu_item = $this->Menu_item_model->get_by_id($item['menu_item_id']);
            
            if (!$menu_item || !$menu_item['is_available']) {
                // Skip unavailable items
                continue;
            }

            // Validate quantity (BR-08: max 99)
            $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
            $qty = max(1, min(99, $qty));

            // Validate notes (BR-07: max 200 chars)
            $notes = isset($item['notes']) ? substr(trim($item['notes']), 0, 200) : '';

            // Price snapshot (BR-06: frozen at add-to-cart time)
            $price_snapshot = isset($item['price_snapshot']) ? (float)$item['price_snapshot'] : (float)$menu_item['price'];

            // Calculate subtotal
            $subtotal = $price_snapshot * $qty;

            $validated_cart[] = [
                'menu_item_id' => (int)$item['menu_item_id'],
                'name' => $menu_item['name'],
                'qty' => $qty,
                'notes' => $notes,
                'price_snapshot' => $price_snapshot,
                'subtotal' => $subtotal,
                'image' => $menu_item['image']
            ];
        }

        // Update session cart data
        $this->Customer_session_model->update_cart($token, $validated_cart);

        // Extend session activity
        $this->Customer_session_model->update_last_activity($session['id']);

        // Calculate totals
        $total_items = 0;
        $total_amount = 0;
        foreach ($validated_cart as $item) {
            $total_items += $item['qty'];
            $total_amount += $item['subtotal'];
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Cart synced successfully',
            'data' => [
                'cart_items' => $validated_cart,
                'total_items' => $total_items,
                'total_amount' => $total_amount,
                'session_expires_at' => $session['expires_at']
            ]
        ]);
    }

    /**
     * UC-CUST-04 & UC-CUST-05: Create Order
     * POST /api/order/create
     * Handles both new order and add-on order (BR-09)
     */
    public function order_create()
    {
        $this->output->set_content_type('application/json');
        
        // Only accept POST
        if ($this->input->method() !== 'post') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
                'code' => 405
            ]);
            return;
        }

        $token = $this->input->post('token');
        $cart_data = $this->input->post('cart_data');

        // Validate token
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
                'message' => 'Session tidak ditemukan',
                'code' => 404
            ]);
            return;
        }

        // Check session expired
        if (strtotime($session['expires_at']) <= time()) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Session telah berakhir',
                'code' => 410
            ]);
            return;
        }

        // Parse cart data
        $cart_items = is_array($cart_data) ? $cart_data : json_decode($cart_data, true);
        
        if (empty($cart_items) || !is_array($cart_items)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Keranjang kosong',
                'code' => 422
            ]);
            return;
        }

        // Validate table
        $table = $this->Table_model->get_by_id($session['table_id']);
        
        if (!$table || !$table['is_active']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Meja tidak valid',
                'code' => 400
            ]);
            return;
        }

        // BR-09: Check if table has active order (add-on order scenario)
        $existing_order = $this->Order_model->get_open_order_by_table($table['id']);
        
        $this->db->trans_start();

        if ($existing_order) {
            // ADD-ON ORDER: Add items to existing order
            // Check status is not waiting_payment
            if (strtolower($existing_order['status']) === 'waiting_payment' || 
                strtolower($existing_order['payment_status']) === 'paid') {
                $this->db->trans_rollback();
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Tidak dapat menambah pesanan. Silakan hubungi staf.',
                    'code' => 400
                ]);
                return;
            }

            $order_id = $existing_order['id'];
            $order_number = $existing_order['order_number'];

            // Add items to existing order
            foreach ($cart_items as $item) {
                // Validate menu item availability
                $menu_item = $this->Menu_item_model->get_by_id($item['menu_item_id']);
                
                if (!$menu_item || !$menu_item['is_available']) {
                    $this->db->trans_rollback();
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Item "' . ($item['name'] ?? 'Unknown') . '" tidak tersedia',
                        'code' => 400,
                        'unavailable_item' => $item['menu_item_id']
                    ]);
                    return;
                }

                // BR-11: Use price_snapshot or current price
                $unit_price = isset($item['price_snapshot']) ? (float)$item['price_snapshot'] : (float)$menu_item['price'];
                $qty = max(1, min(99, (int)$item['qty']));
                $subtotal = $unit_price * $qty;
                $notes = isset($item['notes']) ? substr(trim($item['notes']), 0, 200) : '';

                // Insert order item with price snapshot
                $this->Order_model->add_item([
                    'order_id' => $order_id,
                    'menu_item_id' => (int)$item['menu_item_id'],
                    'quantity' => $qty,
                    'unit_price' => $unit_price,
                    'subtotal' => $subtotal,
                    'notes' => $notes,
                    'status' => 'pending'
                ]);
            }

            // Recalculate order totals
            $totals = $this->Order_model->calculate_totals($order_id);
            
            $this->Order_model->update($order_id, [
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'service_charge_amount' => $totals['service_amount'],
                'total_amount' => $totals['total'],
                'status' => 'pending' // Reset to pending for kitchen
            ]);

        } else {
            // NEW ORDER: Create new order
            // Validate all items first
            foreach ($cart_items as $item) {
                $menu_item = $this->Menu_item_model->get_by_id($item['menu_item_id']);
                
                if (!$menu_item || !$menu_item['is_available']) {
                    $this->db->trans_rollback();
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Item "' . ($item['name'] ?? 'Unknown') . '" tidak tersedia',
                        'code' => 400,
                        'unavailable_item' => $item['menu_item_id']
                    ]);
                    return;
                }
            }

            // BR-10: Atomic order number generation
            $order_number = $this->Order_model->generate_order_number();

            // Calculate totals
            $subtotal = 0;
            foreach ($cart_items as $item) {
                $price = isset($item['price_snapshot']) ? (float)$item['price_snapshot'] : 0;
                $qty = max(1, min(99, (int)$item['qty']));
                $subtotal += $price * $qty;
            }

            $tax_rate = $this->config->item('tax_rate') ?: 0.10;
            $service_rate = $this->config->item('service_rate') ?: 0.05;
            $tax_amount = $subtotal * $tax_rate;
            $service_amount = $subtotal * $service_rate;
            $total_amount = $subtotal + $tax_amount + $service_amount;

            // Create order
            $order_id = $this->Order_model->create([
                'order_number' => $order_number,
                'table_id' => $table['id'],
                'customer_session_id' => $session['id'],
                'status' => 'pending',
                'order_type' => 'dine_in',
                'subtotal' => $subtotal,
                'tax_amount' => $tax_amount,
                'service_charge_amount' => $service_amount,
                'total_amount' => $total_amount,
                'payment_status' => 'unpaid',
                'notes' => 'Order from table ' . $table['table_number']
            ]);

            if (!$order_id) {
                $this->db->trans_rollback();
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Gagal membuat pesanan',
                    'code' => 500
                ]);
                return;
            }

            // Insert order items with price snapshot (BR-11)
            foreach ($cart_items as $item) {
                $menu_item = $this->Menu_item_model->get_by_id($item['menu_item_id']);
                $unit_price = isset($item['price_snapshot']) ? (float)$item['price_snapshot'] : (float)$menu_item['price'];
                $qty = max(1, min(99, (int)$item['qty']));
                $subtotal = $unit_price * $qty;
                $notes = isset($item['notes']) ? substr(trim($item['notes']), 0, 200) : '';

                $this->Order_model->add_item([
                    'order_id' => $order_id,
                    'menu_item_id' => (int)$item['menu_item_id'],
                    'quantity' => $qty,
                    'unit_price' => $unit_price,
                    'subtotal' => $subtotal,
                    'notes' => $notes,
                    'status' => 'pending'
                ]);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal memproses pesanan',
                'code' => 500
            ]);
            return;
        }

        // Clear cart after successful order
        $this->Customer_session_model->update_cart($token, []);

        // Log activity
        $this->load->model('Activity_log_model');
        $this->Activity_log_model->create([
            'action' => 'order_created',
            'description' => 'Order ' . $order_number . ' created for table ' . $table['table_number'],
            'related_table' => 'orders',
            'related_id' => $order_id,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'priority' => 'high'
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Pesanan berhasil dibuat',
            'data' => [
                'order_id' => $order_id,
                'order_number' => $order_number,
                'table_number' => $table['table_number'],
                'is_addon' => !empty($existing_order),
                'redirect_url' => site_url('customer/status?order=' . urlencode($order_number))
            ]
        ]);
    }

    /**
     * Get menu item details
     * GET /api/menu/item/{id}
     */
    public function menu_item($id)
    {
        $this->output->set_content_type('application/json');
        
        $item = $this->Menu_item_model->get_by_id($id);
        
        if (!$item) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Item tidak ditemukan',
                'code' => 404
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'data' => $item
        ]);
    }

    /**
     * Validate session
     * POST /api/session/validate
     */
    public function session_validate()
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
                'message' => 'Session tidak ditemukan',
                'code' => 404,
                'valid' => false
            ]);
            return;
        }

        if (strtotime($session['expires_at']) <= time()) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Session telah berakhir',
                'code' => 410,
                'valid' => false,
                'expired' => true,
                'cart_data' => json_decode($session['cart_data'], true) ?: []
            ]);
            return;
        }

        // Extend session
        $this->Customer_session_model->update_last_activity($session['id']);

        $table = $this->Table_model->get_by_id($session['table_id']);
        $cart_data = json_decode($session['cart_data'], true) ?: [];

        echo json_encode([
            'status' => 'success',
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
     * Session Heartbeat
     * POST /api/session/heartbeat
     * Extends session expiry by 30 minutes
     */
    public function session_heartbeat()
    {
        $this->output->set_content_type('application/json');
        
        // Only accept POST
        if ($this->input->method() !== 'post') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
                'code' => 405
            ]);
            return;
        }

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
                'message' => 'Session tidak ditemukan',
                'code' => 404
            ]);
            return;
        }

        // Check if session is already expired
        if (strtotime($session['expires_at']) <= time()) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Session telah berakhir',
                'code' => 410,
                'expired' => true
            ]);
            return;
        }

        // Update expires_at = NOW() + 30 minutes
        $new_expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $this->Customer_session_model->extend($token, 30);

        echo json_encode([
            'status' => 'success',
            'message' => 'Session extended',
            'data' => [
                'expires_at' => $new_expires_at,
                'extended_by' => 30 // minutes
            ]
        ]);
    }

    /**
     * Check table availability
     * POST /api/table/check
     */
    public function table_check()
    {
        // Validate CSRF for POST requests
        if (!$this->validate_csrf()) {
            return;
        }

        $table_id = $this->input->post('table_id', TRUE);
        
        if (empty($table_id)) {
            $this->json_response('error', 'Table ID required', null, 422);
            return;
        }

        $table = $this->Table_model->get_by_id($table_id);
        
        if (!$table) {
            $this->json_response('error', 'Meja tidak ditemukan', null, 404);
            return;
        }

        // Check if table is active
        if (!$table['is_active']) {
            $this->json_response('error', 'Meja tidak aktif', [
                'table_id' => $table['id'],
                'table_number' => $table['table_number'],
                'is_active' => false
            ], 400);
            return;
        }

        // Check if table has active order
        $has_active_order = false;
        $open_order = $this->Order_model->get_open_order_by_table($table['id']);
        
        if ($open_order) {
            $has_active_order = true;
        }

        $this->json_response('success', 'Meja tersedia', [
            'table_id' => $table['id'],
            'table_number' => $table['table_number'],
            'table_code' => $table['table_code'] ?? null,
            'is_active' => $table['is_active'],
            'has_active_order' => $has_active_order,
            'qr_code' => $table['qr_code'] ?? null
        ]);
    }

    /**
     * Create new customer session
     * POST /api/session/create
     */
    public function session_create()
    {
        // Validate CSRF for POST requests
        if (!$this->validate_csrf()) {
            return;
        }

        $table_id = $this->input->post('table_id', TRUE);
        $table_code = $this->input->post('table_code', TRUE);
        
        if (empty($table_id) && empty($table_code)) {
            $this->json_response('error', 'Table ID atau Table Code required', null, 422);
            return;
        }

        // Find table by ID or code
        $table = null;
        if (!empty($table_id)) {
            $table = $this->Table_model->get_by_id($table_id);
        } elseif (!empty($table_code)) {
            $this->db->where('table_code', $table_code);
            $table = $this->db->get('tables')->row_array();
        }

        if (!$table) {
            $this->json_response('error', 'Meja tidak ditemukan', null, 404);
            return;
        }

        // Check if table is active
        if (!$table['is_active']) {
            $this->json_response('error', 'Meja tidak aktif', null, 400);
            return;
        }

        // Check if there's already an active session for this table
        $this->db->where('table_id', $table['id']);
        $this->db->where('expires_at >', date('Y-m-d H:i:s'));
        $existing_session = $this->db->get('customer_sessions')->row_array();

        if ($existing_session) {
            // Return existing session
            $this->json_response('success', 'Session sudah ada', [
                'session_id' => $existing_session['id'],
                'token' => $existing_session['token'],
                'table_id' => $existing_session['table_id'],
                'table_number' => $table['table_number'],
                'expires_at' => $existing_session['expires_at'],
                'cart_data' => json_decode($existing_session['cart_data'], true) ?: [],
                'is_existing' => true
            ]);
            return;
        }

        // Create new session
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $session_data = [
            'table_id' => $table['id'],
            'token' => $token,
            'expires_at' => $expires_at,
            'cart_data' => json_encode([]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('customer_sessions', $session_data);
        $session_id = $this->db->insert_id();

        if (!$session_id) {
            $this->json_response('error', 'Gagal membuat session', null, 500);
            return;
        }

        // Log activity
        $this->load->model('Activity_log_model');
        $this->Activity_log_model->create([
            'action' => 'session_created',
            'description' => 'New session created for table ' . $table['table_number'],
            'related_table' => 'customer_sessions',
            'related_id' => $session_id,
            'ip_address' => $this->input->ip_address(),
            'user_agent' => $this->input->user_agent(),
            'priority' => 'medium'
        ]);

        $this->json_response('success', 'Session berhasil dibuat', [
            'session_id' => $session_id,
            'token' => $token,
            'table_id' => $table['id'],
            'table_number' => $table['table_number'],
            'expires_at' => $expires_at,
            'cart_data' => [],
            'is_existing' => false
        ]);
    }

    /**
     * Refresh CSRF token
     * GET /api/csrf/refresh
     */
    public function csrf_refresh()
    {
        // Get fresh CSRF token
        $csrf_token_name = $this->security->get_csrf_token_name();
        $csrf_hash = $this->security->get_csrf_hash();

        $this->json_response('success', 'CSRF token refreshed', [
            'csrf_token_name' => $csrf_token_name,
            'csrf_hash' => $csrf_hash
        ]);
    }

    /**
     * Get menu items
     * GET /api/menu
     */
    public function menu()
    {
        $category_id = $this->input->get('category', TRUE);
        $available_only = $this->input->get('available', TRUE) !== 'false';

        $this->db->select('menu_items.*, categories.name as category_name');
        $this->db->from('menu_items');
        $this->db->join('categories', 'categories.id = menu_items.category_id', 'left');
        
        if (!empty($category_id)) {
            $this->db->where('menu_items.category_id', $category_id);
        }
        
        if ($available_only) {
            $this->db->where('menu_items.is_available', 1);
        }
        
        $this->db->order_by('categories.name', 'ASC');
        $this->db->order_by('menu_items.name', 'ASC');
        
        $items = $this->db->get()->result_array();

        // Format response
        $formatted_items = [];
        foreach ($items as $item) {
            $formatted_items[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'description' => $item['description'],
                'price' => (float)$item['price'],
                'image' => $item['image'] ?? null,
                'is_available' => (bool)$item['is_available'],
                'category_id' => $item['category_id'],
                'category_name' => $item['category_name'] ?? 'Uncategorized'
            ];
        }

        $this->json_response('success', 'Menu retrieved', [
            'items' => $formatted_items,
            'count' => count($formatted_items)
        ]);
    }

    /**
     * Get order status
     * POST /api/order/status
     */
    public function order_status()
    {
        $order_number = $this->input->post('order_number', TRUE);
        $order_id = $this->input->post('order_id', TRUE);

        if (empty($order_number) && empty($order_id)) {
            $this->json_response('error', 'Order number atau Order ID required', null, 422);
            return;
        }

        $order = null;
        if (!empty($order_id)) {
            $order = $this->Order_model->get_by_id($order_id);
        } elseif (!empty($order_number)) {
            $this->db->where('order_number', $order_number);
            $order = $this->db->get('orders')->row_array();
        }

        if (!$order) {
            $this->json_response('error', 'Pesanan tidak ditemukan', null, 404);
            return;
        }

        // Get order items
        $this->db->where('order_id', $order['id']);
        $this->db->order_by('id', 'ASC');
        $items = $this->db->get('order_items')->result_array();

        $formatted_items = [];
        foreach ($items as $item) {
            $formatted_items[] = [
                'id' => $item['id'],
                'menu_item_id' => $item['menu_item_id'],
                'name' => $item['name'] ?? 'Unknown',
                'quantity' => (int)$item['quantity'],
                'unit_price' => (float)$item['unit_price'],
                'subtotal' => (float)$item['subtotal'],
                'notes' => $item['notes'] ?? '',
                'status' => $item['status']
            ];
        }

        // Get table info
        $table = $this->Table_model->get_by_id($order['table_id']);

        $this->json_response('success', 'Order status retrieved', [
            'order_id' => $order['id'],
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'table_number' => $table ? $table['table_number'] : null,
            'total_amount' => (float)$order['total_amount'],
            'items' => $formatted_items,
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at']
        ]);
    }

    /**
     * Get order bill for payment
     * POST /api/order/bill
     */
    public function order_bill()
    {
        $order_number = $this->input->post('order_number', TRUE);
        $order_id = $this->input->post('order_id', TRUE);

        if (empty($order_number) && empty($order_id)) {
            $this->json_response('error', 'Order number atau Order ID required', null, 422);
            return;
        }

        $order = null;
        if (!empty($order_id)) {
            $order = $this->Order_model->get_by_id($order_id);
        } elseif (!empty($order_number)) {
            $this->db->where('order_number', $order_number);
            $order = $this->db->get('orders')->row_array();
        }

        if (!$order) {
            $this->json_response('error', 'Pesanan tidak ditemukan', null, 404);
            return;
        }

        // Check if order can be paid
        if (strtolower($order['payment_status']) === 'paid') {
            $this->json_response('error', 'Pesanan sudah dibayar', [
                'order_number' => $order['order_number'],
                'payment_status' => $order['payment_status']
            ], 400);
            return;
        }

        // Get order items
        $this->db->where('order_id', $order['id']);
        $items = $this->db->get('order_items')->result_array();

        // Get table info
        $table = $this->Table_model->get_by_id($order['table_id']);

        $bill_data = [
            'order_id' => $order['id'],
            'order_number' => $order['order_number'],
            'table_number' => $table ? $table['table_number'] : null,
            'status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'subtotal' => (float)$order['subtotal'],
            'tax_rate' => $this->config->item('tax_rate') ?: 0.10,
            'tax_amount' => (float)$order['tax_amount'],
            'service_rate' => $this->config->item('service_rate') ?: 0.05,
            'service_amount' => (float)$order['service_charge_amount'],
            'total_amount' => (float)$order['total_amount'],
            'items' => $items,
            'created_at' => $order['created_at']
        ];

        $this->json_response('success', 'Bill retrieved', $bill_data);
    }

    /**
     * Check uniqueness of entity (username, email, table number, etc.)
     * POST /api/check_unique/[entity]
     * @param string $entity - The entity type to check (username, email, table_number, menu_name, etc.)
     */
    public function check_unique($entity = null)
    {
        // Validate CSRF for POST requests
        if (!$this->validate_csrf()) {
            return;
        }

        if (empty($entity)) {
            $entity = $this->input->post('entity', TRUE);
        }

        $value = $this->input->post('value', TRUE);
        $exclude_id = $this->input->post('exclude_id', TRUE);

        if (empty($entity) || empty($value)) {
            $this->json_response('error', 'Entity dan Value required', null, 422);
            return;
        }

        // Map entity to database table and column
        $entity_map = [
            'username' => ['table' => 'users', 'column' => 'username'],
            'email' => ['table' => 'users', 'column' => 'email'],
            'table_number' => ['table' => 'tables', 'column' => 'table_number'],
            'table_code' => ['table' => 'tables', 'column' => 'table_code'],
            'menu_name' => ['table' => 'menu_items', 'column' => 'name'],
            'category_name' => ['table' => 'categories', 'column' => 'name'],
            'order_number' => ['table' => 'orders', 'column' => 'order_number']
        ];

        if (!isset($entity_map[$entity])) {
            $this->json_response('error', 'Entity tidak valid', null, 400);
            return;
        }

        $table = $entity_map[$entity]['table'];
        $column = $entity_map[$entity]['column'];

        $this->db->where($column, $value);
        
        if (!empty($exclude_id)) {
            $this->db->where('id !=', $exclude_id);
        }

        $exists = $this->db->get($table)->row_array();

        $is_unique = empty($exists);

        $this->json_response('success', 'Uniqueness check completed', [
            'entity' => $entity,
            'value' => $value,
            'is_unique' => $is_unique,
            'exists' => !$is_unique
        ]);
    }
}
