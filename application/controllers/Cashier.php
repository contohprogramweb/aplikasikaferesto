<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cashier Controller
 * Handles cashier operations: billing, payment, receipt printing
 * Based on SRS v4.0 UC-CASH-01 to UC-CASH-05
 */
class Cashier extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        
        // BR-40: Harus login dengan role cashier/admin
        if (!$this->session->userdata('logged_in')) {
            redirect('auth/login');
        }
        
        $user = $this->session->userdata('user');
        if (!in_array($user['role'], ['cashier', 'admin'])) {
            show_error('Akses ditolak. Hanya kasir dan admin yang dapat mengakses halaman ini.', 403);
        }
        
        $this->load->model('Order_model');
        $this->load->model('Table_model');
        $this->load->library('form_validation');
        $this->load->helper(['currency', 'date']);
    }

    /**
     * UC-CASH-01: Dashboard meja kasir
     * Menampilkan grid semua meja dengan status dan total tagihan
     */
    public function index()
    {
        $data['title'] = 'Dashboard Kasir';
        $data['page'] = 'cashier/index';
        $data['user'] = $this->session->userdata('user');
        
        // Load config for tax and service
        $data['default_tax_rate'] = $this->config->item('tax_rate') ?: 0.10;
        $data['default_service_rate'] = $this->config->item('service_rate') ?: 0.05;
        
        $this->load->view('templates/cashier_header', $data);
        $this->load->view('cashier/index', $data);
        $this->load->view('templates/cashier_footer', $data);
    }

    /**
     * UC-CASH-01: AJAX polling daftar meja aktif
     * Returns all tables with active orders and their details
     * 
     * POST /api/cashier/tables
     * @return JSON {tables: [], last_id, last_timestamp}
     */
    public function tables()
    {
        // Only allow POST
        if ($this->input->method() !== 'post') {
            show_error('Method not allowed', 405);
        }

        $last_id = (int) $this->input->post('last_id', 0);
        $last_timestamp = $this->input->post('last_timestamp', '');
        
        // Get all tables
        $this->db->from('tables');
        $this->db->where('is_active', 1);
        $this->db->order_by('table_number', 'ASC');
        $tables_query = $this->db->get();
        $all_tables = $tables_query->result_array();
        
        // Get active orders for each table
        $active_statuses = ['pending', 'confirmed', 'preparing', 'ready'];
        $this->db->select('o.*, t.table_number');
        $this->db->from('orders o');
        $this->db->join('tables t', 't.id = o.table_id');
        $this->db->where_in('o.status', $active_statuses);
        $this->db->where('o.payment_status !=', 'paid');
        $this->db->order_by('o.created_at', 'DESC');
        $orders_query = $this->db->get();
        $active_orders = $orders_query->result_array();
        
        // Build table data with order info
        $tables_data = [];
        foreach ($all_tables as $table) {
            $table_orders = array_filter($active_orders, function($order) use ($table) {
                return $order['table_id'] == $table['id'];
            });
            
            $table_data = [
                'id' => $table['id'],
                'table_number' => $table['table_number'],
                'status' => $table['status'],
                'location' => $table['location'],
                'capacity' => $table['capacity'],
                'active_orders_count' => count($table_orders),
                'total_amount' => 0,
                'items_count' => 0,
                'duration_seconds' => 0,
                'bill_requested' => false,
                'orders' => []
            ];
            
            // Calculate totals from orders
            $total_amount = 0;
            $items_count = 0;
            $earliest_order_time = null;
            
            foreach ($table_orders as $order) {
                $totals = $this->Order_model->calculate_totals($order['id']);
                $total_amount += $totals['total'];
                
                $items = $this->Order_model->get_items_by_order($order['id']);
                $items_count += count($items);
                
                $order_time = strtotime($order['created_at']);
                if ($earliest_order_time === null || $order_time < $earliest_order_time) {
                    $earliest_order_time = $order_time;
                }
                
                // Check if bill requested (could add a flag in orders table)
                if (!empty($order['bill_requested'])) {
                    $table_data['bill_requested'] = true;
                }
                
                $table_data['orders'][] = [
                    'id' => $order['id'],
                    'order_number' => $order['order_number'],
                    'status' => $order['status'],
                    'created_at' => $order['created_at']
                ];
            }
            
            $table_data['total_amount'] = $total_amount;
            $table_data['items_count'] = $items_count;
            
            if ($earliest_order_time !== null) {
                $table_data['duration_seconds'] = time() - $earliest_order_time;
            }
            
            $tables_data[] = $table_data;
        }
        
        // Find max ID and timestamp for delta polling
        $max_id = 0;
        $max_timestamp = '';
        
        foreach ($active_orders as $order) {
            if ($order['id'] > $max_id) {
                $max_id = $order['id'];
            }
            if (empty($max_timestamp) || $order['created_at'] > $max_timestamp) {
                $max_timestamp = $order['created_at'];
            }
        }
        
        // Return response
        echo json_encode([
            'success' => true,
            'tables' => $tables_data,
            'last_id' => $max_id,
            'last_timestamp' => $max_timestamp,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * UC-CASH-02: Detail tagihan per meja
     * Shows detailed billing information for a specific table
     * 
     * GET /cashier/detail/{table_id}
     * POST /api/cashier/detail (AJAX)
     */
    public function detail($table_id = null)
    {
        // Handle AJAX request
        if ($this->input->is_ajax_request()) {
            return $this->_ajax_detail();
        }
        
        // Regular page load
        if (!$table_id) {
            show_error('Table ID is required', 400);
        }
        
        $table = $this->Table_model->get_by_id($table_id);
        if (!$table) {
            show_error('Meja tidak ditemukan', 404);
        }
        
        $data['title'] = 'Detail Tagihan - Meja ' . $table['table_number'];
        $data['table'] = $table;
        $data['user'] = $this->session->userdata('user');
        $data['default_tax_rate'] = $this->config->item('tax_rate') ?: 0.10;
        $data['default_service_rate'] = $this->config->item('service_rate') ?: 0.05;
        
        // Get active orders for this table
        $active_statuses = ['pending', 'confirmed', 'preparing', 'ready'];
        $this->db->where('table_id', $table_id);
        $this->db->where_in('status', $active_statuses);
        $this->db->where('payment_status !=', 'paid');
        $this->db->order_by('created_at', 'ASC');
        $orders = $this->db->get('orders')->result_array();
        
        // Enrich orders with items and totals
        foreach ($orders as &$order) {
            $order['items'] = $this->Order_model->get_items_by_order($order['id']);
            $order['totals'] = $this->Order_model->calculate_totals($order['id']);
        }
        
        $data['orders'] = $orders;
        
        $this->load->view('templates/cashier_header', $data);
        $this->load->view('cashier/detail', $data);
        $this->load->view('templates/cashier_footer', $data);
    }

    /**
     * AJAX endpoint for table detail
     */
    private function _ajax_detail()
    {
        $table_id = $this->input->post('table_id');
        
        if (!$table_id) {
            echo json_encode(['success' => false, 'message' => 'Table ID required']);
            return;
        }
        
        $table = $this->Table_model->get_by_id($table_id);
        if (!$table) {
            echo json_encode(['success' => false, 'message' => 'Table not found']);
            return;
        }
        
        // Get active orders
        $active_statuses = ['pending', 'confirmed', 'preparing', 'ready'];
        $this->db->where('table_id', $table_id);
        $this->db->where_in('status', $active_statuses);
        $this->db->where('payment_status !=', 'paid');
        $this->db->order_by('created_at', 'ASC');
        $orders = $this->db->get('orders')->result_array();
        
        // Enrich orders
        foreach ($orders as &$order) {
            $order['items'] = $this->Order_model->get_items_by_order($order['id']);
            $order['totals'] = $this->Order_model->calculate_totals($order['id']);
        }
        
        echo json_encode([
            'success' => true,
            'table' => $table,
            'orders' => $orders
        ]);
    }

    /**
     * UC-CASH-03: Terapkan diskon
     * Apply discount to an order
     * 
     * POST /api/cashier/apply_discount
     * @param int order_id
     * @param string discount_type (percentage/fixed)
     * @param float discount_value
     * @param string reason (optional)
     * @return JSON
     */
    public function apply_discount()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $order_id = (int) $this->input->post('order_id');
        $discount_type = $this->input->post('discount_type'); // 'percentage' or 'fixed'
        $discount_value = (float) $this->input->post('discount_value');
        $reason = $this->input->post('reason', '');
        
        // Validation
        if (!$order_id) {
            echo json_encode(['success' => false, 'message' => 'Order ID required']);
            return;
        }
        
        $order = $this->Order_model->get_by_id($order_id);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }
        
        // BR-26: Diskon hanya sebelum lunas
        if ($order['payment_status'] === 'paid' || $order['status'] === 'completed') {
            echo json_encode(['success' => false, 'message' => 'Tidak dapat memberikan diskon pada pesanan yang sudah lunas']);
            return;
        }
        
        // Calculate current totals
        $totals = $this->Order_model->calculate_totals($order_id);
        $subtotal = $totals['subtotal'];
        
        // BR-27: Diskon tidak boleh melebihi subtotal
        $discount_amount = 0;
        if ($discount_type === 'percentage') {
            $discount_amount = ($subtotal * $discount_value) / 100;
            if ($discount_value < 0 || $discount_value > 100) {
                echo json_encode(['success' => false, 'message' => 'Diskon persentase harus antara 0-100%']);
                return;
            }
        } else {
            $discount_amount = $discount_value;
            if ($discount_value < 0 || $discount_value > $subtotal) {
                echo json_encode(['success' => false, 'message' => 'Diskon tidak boleh melebihi subtotal']);
                return;
            }
        }
        
        // Validate discount doesn't exceed subtotal
        if ($discount_amount > $subtotal) {
            echo json_encode(['success' => false, 'message' => 'Diskon tidak boleh melebihi subtotal']);
            return;
        }
        
        // BR-29: Log audit trail
        $user = $this->session->userdata('user');
        $audit_log = [
            'order_id' => $order_id,
            'action' => 'discount_applied',
            'user_id' => $user['id'],
            'username' => $user['username'],
            'details' => json_encode([
                'discount_type' => $discount_type,
                'discount_value' => $discount_value,
                'discount_amount' => $discount_amount,
                'reason' => $reason,
                'old_discount' => $order['discount_amount'] ?? 0
            ]),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Update order with discount
        $update_data = [
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'discount_amount' => $discount_amount,
            'discount_reason' => substr($reason, 0, 200),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->trans_start();
        
        // Insert audit log
        $this->db->insert('audit_logs', $audit_log);
        
        // Update order
        $this->db->where('id', $order_id);
        $this->db->update('orders', $update_data);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            echo json_encode(['success' => false, 'message' => 'Gagal menerapkan diskon']);
            return;
        }
        
        // Recalculate totals
        $new_totals = $this->Order_model->calculate_totals($order_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Diskon berhasil diterapkan',
            'discount_amount' => $discount_amount,
            'totals' => $new_totals
        ]);
    }

    /**
     * UC-CASH-04: Proses pembayaran
     * Process payment for an order
     * 
     * POST /api/cashier/pay
     * @param int order_id
     * @param string payment_method
     * @param float amount_paid (for cash)
     * @return JSON
     */
    public function pay()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $order_id = (int) $this->input->post('order_id');
        $payment_method = $this->input->post('payment_method');
        $amount_paid = (float) $this->input->post('amount_paid', 0);
        
        // Validation
        if (!$order_id) {
            echo json_encode(['success' => false, 'message' => 'Order ID required']);
            return;
        }
        
        if (!$payment_method) {
            echo json_encode(['success' => false, 'message' => 'Payment method required']);
            return;
        }
        
        $order = $this->Order_model->get_by_id($order_id);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }
        
        // Check if already paid
        if ($order['payment_status'] === 'paid' || $order['status'] === 'completed') {
            echo json_encode(['success' => false, 'message' => 'Pesanan sudah lunas']);
            return;
        }
        
        // Calculate final total
        $totals = $this->Order_model->calculate_totals($order_id);
        
        // Apply existing discount
        $discount_amount = $order['discount_amount'] ?? 0;
        $grand_total = $totals['total'] - $discount_amount;
        
        // BR-32: Validate cash payment
        $change_amount = 0;
        if ($payment_method === 'cash') {
            if ($amount_paid <= 0) {
                echo json_encode(['success' => false, 'message' => 'Jumlah uang harus diisi untuk pembayaran tunai']);
                return;
            }
            
            if ($amount_paid < $grand_total) {
                echo json_encode(['success' => false, 'message' => 'Uang pembayaran kurang']);
                return;
            }
            
            $change_amount = $amount_paid - $grand_total;
        }
        
        $user = $this->session->userdata('user');
        
        // BR-30 to BR-33: Atomic transaction
        $this->db->trans_start();
        
        // 1. Update orders table
        $order_update = [
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => $payment_method,
            'amount_paid' => $payment_method === 'cash' ? $amount_paid : $grand_total,
            'change_amount' => $change_amount,
            'paid_at' => date('Y-m-d H:i:s'),
            'cashier_id' => $user['id'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->where('id', $order_id);
        $this->db->update('orders', $order_update);
        
        // 2. Update tables status to 'cleaning' (BR-31)
        $this->db->where('id', $order['table_id']);
        $this->db->update('tables', [
            'status' => 'cleaning',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // 3. Insert transaction record (audit log)
        $transaction = [
            'order_id' => $order_id,
            'table_id' => $order['table_id'],
            'user_id' => $user['id'],
            'transaction_type' => 'payment',
            'payment_method' => $payment_method,
            'amount' => $grand_total,
            'amount_paid' => $payment_method === 'cash' ? $amount_paid : $grand_total,
            'change_amount' => $change_amount,
            'discount_amount' => $discount_amount,
            'tax_amount' => $totals['tax_amount'],
            'service_amount' => $totals['service_amount'],
            'notes' => 'Pembayaran order ' . $order['order_number'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('transactions', $transaction);
        
        // 4. Insert audit log
        $audit_log = [
            'order_id' => $order_id,
            'action' => 'payment_processed',
            'user_id' => $user['id'],
            'username' => $user['username'],
            'details' => json_encode([
                'payment_method' => $payment_method,
                'grand_total' => $grand_total,
                'amount_paid' => $payment_method === 'cash' ? $amount_paid : $grand_total,
                'change_amount' => $change_amount
            ]),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('audit_logs', $audit_log);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            echo json_encode(['success' => false, 'message' => 'Gagal memproses pembayaran']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Pembayaran berhasil',
            'order_number' => $order['order_number'],
            'grand_total' => $grand_total,
            'amount_paid' => $payment_method === 'cash' ? $amount_paid : $grand_total,
            'change_amount' => $change_amount,
            'receipt_url' => site_url('cashier/print_receipt/' . $order_id)
        ]);
    }

    /**
     * UC-CASH-05: Cetak struk PDF
     * Generate and print receipt PDF
     * 
     * GET /cashier/print_receipt/{order_id}
     */
    public function print_receipt($order_id)
    {
        $order = $this->Order_model->get_with_items($order_id);
        
        if (!$order) {
            show_error('Order tidak ditemukan', 404);
        }
        
        // Get table info
        $table = $this->Table_model->get_by_id($order['table_id']);
        
        // Get cashier info
        $cashier = null;
        if (!empty($order['cashier_id'])) {
            $this->load->model('User_model');
            $cashier = $this->User_model->get_by_id($order['cashier_id']);
        }
        
        // Calculate totals
        $totals = $this->Order_model->calculate_totals($order_id);
        $discount_amount = $order['discount_amount'] ?? 0;
        $grand_total = $totals['total'] - $discount_amount;
        
        $data = [
            'order' => $order,
            'table' => $table,
            'cashier' => $cashier,
            'totals' => $totals,
            'discount_amount' => $discount_amount,
            'grand_total' => $grand_total,
            'restaurant_name' => $this->config->item('restaurant_name') ?: 'Restaurant',
            'restaurant_address' => $this->config->item('restaurant_address') ?: '',
            'restaurant_phone' => $this->config->item('restaurant_phone') ?: ''
        ];
        
        // Load PDF library (assuming TCPDF or similar)
        $this->load->library('pdf');
        
        // Set paper size for thermal printer (80mm)
        $this->pdf->setPaper('80mm', 'auto');
        $this->pdf->setMargins(5, 5, 5);
        $this->pdf->AddPage();
        
        // Load view as HTML
        $html = $this->load->view('receipts/thermal', $data, TRUE);
        
        // Write HTML
        $this->pdf->writeHTML($html);
        
        // Output inline (open in new tab)
        $filename = 'RECEIPT-' . $order['order_number'] . '.pdf';
        $this->pdf->Output($filename, 'I');
        
        // Optional: Save to uploads/receipts/YYYY/MM/
        $upload_dir = FCPATH . 'uploads/receipts/' . date('Y') . '/' . date('m') . '/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, TRUE);
        }
        $this->pdf->Output($upload_dir . $filename, 'F');
    }

    /**
     * Clean table after payment
     * Update table status from cleaning to available
     * 
     * POST /api/cashier/clean_table
     */
    public function clean_table()
    {
        if ($this->input->method() !== 'post') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $table_id = (int) $this->input->post('table_id');
        
        if (!$table_id) {
            echo json_encode(['success' => false, 'message' => 'Table ID required']);
            return;
        }
        
        $table = $this->Table_model->get_by_id($table_id);
        if (!$table) {
            echo json_encode(['success' => false, 'message' => 'Table not found']);
            return;
        }
        
        // BR-31-B: Only waiter/admin can change cleaning→available
        $user = $this->session->userdata('user');
        if (!in_array($user['role'], ['waiter', 'admin', 'cashier'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        // Can only clean if status is 'cleaning'
        if ($table['status'] !== 'cleaning') {
            echo json_encode(['success' => false, 'message' => 'Meja harus dalam status dibersihkan terlebih dahulu']);
            return;
        }
        
        // Update status
        $this->db->where('id', $table_id);
        $this->db->update('tables', [
            'status' => 'available',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        // Log activity
        $audit_log = [
            'table_id' => $table_id,
            'action' => 'table_cleaned',
            'user_id' => $user['id'],
            'username' => $user['username'],
            'details' => json_encode([
                'old_status' => 'cleaning',
                'new_status' => 'available'
            ]),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('audit_logs', $audit_log);
        
        echo json_encode([
            'success' => true,
            'message' => 'Status meja berhasil diubah menjadi tersedia'
        ]);
    }
}
