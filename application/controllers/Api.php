<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API Controller
 * Handles AJAX API endpoints for customer ordering system
 * Based on SRS v4.0 UC-CUST-03, UC-CUST-04, UC-CUST-05
 */
class Api extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Customer_session_model');
        $this->load->model('Order_model');
        $this->load->model('Menu_item_model');
        $this->load->model('Table_model');
        $this->load->helper(['text']);
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
}
