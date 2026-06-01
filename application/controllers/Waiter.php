<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Waiter Controller
 * Handles Waiter operations for order delivery and table management
 * Based on SRS v4.0:
 * - UC-WAIT-01: Lihat Item Siap Antar (View Ready-to-Deliver Items)
 * - UC-WAIT-02: Konfirmasi Pengantaran (Confirm Delivery)
 * - UC-WAIT-03: Kelola Status Meja (Manage Table Status)
 */
class Waiter extends CI_Controller {

    private $allowed_statuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered'];
    private $waiter_roles = ['waiter', 'admin', 'manager'];

    public function __construct()
    {
        parent::__construct();
        
        // Check if user is logged in and has waiter role
        if (!$this->session->userdata('logged_in')) {
            redirect('auth/login');
            return;
        }
        
        $user = $this->session->userdata('user');
        
        if (!in_array($user['role'], $this->waiter_roles)) {
            show_error('Akses ditolak. Hanya waiter atau admin yang dapat mengakses halaman ini.', 403);
            return;
        }
        
        $this->load->model(['Order_model', 'Table_model']);
        $this->load->helper(['text', 'date']);
    }

    /**
     * UC-WAIT-01: Dashboard Waiter - View Ready-to-Deliver Items
     * GET /waiter
     * 
     * Features:
     * - Landscape (≥1024px): 2 columns side-by-side (50:50)
     *   * Left: "Siap Diantar" (Ready to Deliver)
     *   * Right: "Dalam Proses" (In Progress)
     * - Portrait (768-1023px): Tab toggle
     * - Mobile (<768px): Stacked cards
     * - Audio alert for new ready items (enabled via user interaction)
     */
    public function index()
    {
        $data['page_title'] = 'Dashboard Waiter';
        $data['page_subtitle'] = 'Manajemen Pengantaran Pesanan';
        
        // Load initial ready orders
        $data['ready_orders'] = $this->_get_ready_orders_grouped();
        $data['in_progress_orders'] = $this->_get_in_progress_orders_grouped();
        
        // Calculate statistics
        $data['ready_count'] = count($data['ready_orders']);
        $data['in_progress_count'] = count($data['in_progress_orders']);
        $data['total_items_ready'] = $this->_count_total_ready_items($data['ready_orders']);
        
        // Load views
        $this->load->view('templates/header', $data);
        $this->load->view('waiter/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * AJAX Polling Endpoint - Smart Polling for Ready Orders
     * POST /api/waiter/ready
     * 
     * Request parameters:
     * - last_id: Last known order item ID (for delta updates)
     * - last_timestamp: Last known timestamp (ISO format)
     * 
     * Response:
     * - updated: Boolean indicating if there are changes
     * - data: Array of ready orders grouped by table
     * - last_id: New last ID for next polling
     * - last_timestamp: New timestamp for next polling
     * 
     * Business Rules:
     * - BR-39: Only ready items can be delivered
     * - Rate limit: max 1 req/3 detik per session
     * - Exponential backoff on error: 5s → 10s → 20s (max)
     */
    public function ready()
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
        
        // Rate limiting: max 1 request per 3 seconds per session
        $session_id = session_id();
        $last_request = $this->session->userdata('waiter_last_poll');
        
        if ($last_request && (time() - $last_request) < 3) {
            echo json_encode([
                'status' => 'rate_limited',
                'message' => 'Rate limit exceeded. Max 1 request per 3 seconds.',
                'retry_after' => 3 - (time() - $last_request),
                'updated' => false,
                'data' => [],
                'last_id' => $this->input->post('last_id', 0),
                'last_timestamp' => $this->input->post('last_timestamp', '')
            ]);
            return;
        }
        
        // Update last request time
        $this->session->set_userdata('waiter_last_poll', time());
        
        // Get parameters
        $last_id = (int) $this->input->post('last_id', 0);
        $last_timestamp = $this->input->post('last_timestamp', '');
        
        // Get ready orders with delta filtering
        $ready_items = $this->Order_model->get_ready_orders_delta($last_id, $last_timestamp);
        
        // Group by table
        $grouped = [];
        $new_last_id = $last_id;
        $new_last_timestamp = $last_timestamp;
        
        foreach ($ready_items as $item) {
            $table_key = $item['table_number'] ?? 'Unknown';
            
            if (!isset($grouped[$table_key])) {
                $grouped[$table_key] = [
                    'table_number' => $table_key,
                    'table_id' => $item['table_id'],
                    'order_id' => $item['order_id'],
                    'order_number' => $item['order_number'],
                    'items' => []
                ];
            }
            
            $grouped[$table_key]['items'][] = $item;
            
            // Track max ID and timestamp
            if ($item['id'] > $new_last_id) {
                $new_last_id = $item['id'];
            }
            if (empty($new_last_timestamp) || $item['created_at'] > $new_last_timestamp) {
                $new_last_timestamp = $item['created_at'];
            }
        }
        
        // Determine if there are updates
        $has_updates = !empty($grouped) && ($new_last_id > $last_id || $new_last_timestamp > $last_timestamp);
        
        echo json_encode([
            'status' => 'success',
            'updated' => $has_updates,
            'data' => array_values($grouped),
            'last_id' => $new_last_id,
            'last_timestamp' => $new_last_timestamp,
            'count' => count($grouped)
        ]);
    }

    /**
     * UC-WAIT-02: Confirm Delivery
     * POST /waiter/deliver
     * 
     * Updates item status from 'ready' to 'delivered'
     * 
     * Parameters:
     * - item_id: Order item ID (single or array for batch)
     * 
     * Business Rules:
     * - BR-39: Only ready items can be delivered
     * - BR-40: Must be logged in as waiter role
     * - BR-41: No double confirm
     */
    public function deliver()
    {
        $this->output->set_content_type('application/json');
        
        if ($this->input->method() !== 'post') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
                'code' => 405
            ]);
            return;
        }
        
        $item_ids = $this->input->post('item_ids');
        
        if (empty($item_ids)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No item IDs provided',
                'code' => 422
            ]);
            return;
        }
        
        // Convert to array if single value
        if (!is_array($item_ids)) {
            $item_ids = [$item_ids];
        }
        
        $this->db->trans_start();
        
        $delivered_count = 0;
        $skipped_count = 0;
        $errors = [];
        
        foreach ($item_ids as $item_id) {
            $item_id = (int) $item_id;
            $item = $this->Order_model->get_item_by_id($item_id);
            
            if (!$item) {
                $errors[] = 'Item #' . $item_id . ' not found';
                continue;
            }
            
            // BR-39 & BR-41: Can only deliver if status is 'ready'
            if ($item['status'] !== 'ready') {
                $skipped_count++;
                continue;
            }
            
            // Update item status to delivered
            $this->Order_model->update_item_status($item_id, 'delivered');
            
            $delivered_count++;
            
            // Log activity
            $this->_log_activity('item_delivered', 'Item #' . $item_id . ' delivered to table', $item['order_id']);
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to deliver items',
                'code' => 500
            ]);
            return;
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Berhasil mengantar ' . $delivered_count . ' item',
            'delivered_count' => $delivered_count,
            'skipped_count' => $skipped_count,
            'errors' => $errors
        ]);
    }

    /**
     * UC-WAIT-03: Tables Grid - View All Table Status
     * GET /waiter/tables
     * 
     * Features:
     * - Grid: 4-6 columns depending on screen size
     * - Color-coded status:
     *   * Green: Tersedia (Available)
     *   * Red: Terisi (Occupied)
     *   * Yellow: Menunggu Bayar (Waiting Payment)
     *   * Blue: Dibersihkan (Cleaning)
     *   * Gray: Tutup/Rusak (Closed/Maintenance)
     * - Badge showing active orders count
     * - Click for read-only detail
     */
    public function tables()
    {
        $data['page_title'] = 'Status Meja';
        $data['page_subtitle'] = 'Grid Semua Meja Restoran';
        
        // Get all tables with active orders count
        $tables = $this->Table_model->get_datatable([], 100, 0, 'table_number', 'ASC');
        
        // Add active orders count to each table
        foreach ($tables['data'] as &$table) {
            $table['active_orders'] = $this->_count_active_orders($table['id']);
            $table['status_label'] = $this->_get_status_label($table['status']);
            $table['status_color'] = $this->_get_status_color($table['status']);
        }
        
        $data['tables'] = $tables['data'];
        $data['total_tables'] = count($tables['data']);
        
        // Count by status
        $data['status_counts'] = [
            'available' => 0,
            'occupied' => 0,
            'waiting_payment' => 0,
            'cleaning' => 0,
            'maintenance' => 0
        ];
        
        foreach ($tables['data'] as $table) {
            $status_key = $table['status'];
            if (isset($data['status_counts'][$status_key])) {
                $data['status_counts'][$status_key]++;
            }
        }
        
        // Load views
        $this->load->view('templates/header', $data);
        $this->load->view('waiter/tables', $data);
        $this->load->view('templates/footer');
    }

    /**
     * UC-WAIT-03: Clean Table Confirmation
     * POST /waiter/clean_table
     * 
     * Updates table status from 'cleaning' to 'available'
     * Only waiter/admin can perform this action
     * 
     * Parameters:
     * - table_id: Table ID
     * 
     * Business Rules:
     * - BR-31-A: After payment → cleaning → waiter confirms → available
     * - BR-31-B: Only waiter/admin can change cleaning→available
     */
    public function clean_table()
    {
        $this->output->set_content_type('application/json');
        
        if ($this->input->method() !== 'post') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed',
                'code' => 405
            ]);
            return;
        }
        
        $table_id = (int) $this->input->post('table_id');
        
        if (!$table_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Table ID required',
                'code' => 422
            ]);
            return;
        }
        
        // Get table
        $table = $this->Table_model->get_by_id($table_id);
        
        if (!$table) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Meja tidak ditemukan',
                'code' => 404
            ]);
            return;
        }
        
        // Check if table is in 'cleaning' status (or equivalent)
        // In our system, we'll use a custom status field or check order status
        // For now, we'll allow transition if no active orders
        
        $active_orders = $this->_count_active_orders($table_id);
        
        if ($active_orders > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Tidak dapat mengubah status meja karena masih ada pesanan aktif (' . $active_orders . ' order)',
                'code' => 400
            ]);
            return;
        }
        
        // Update table status to available
        $this->db->trans_start();
        
        $this->Table_model->update_status($table_id, 'available');
        
        // Log activity
        $this->_log_activity('table_cleaned', 'Table #' . $table['table_number'] . ' marked as cleaned and available', $table_id);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal mengubah status meja',
                'code' => 500
            ]);
            return;
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Meja ' . $table['table_number'] . ' berhasil ditandai sebagai tersedia',
            'table_id' => $table_id,
            'new_status' => 'available'
        ]);
    }

    /**
     * Helper: Get ready orders grouped by table
     * @return array
     */
    private function _get_ready_orders_grouped()
    {
        $ready_items = $this->Order_model->get_ready_orders_full();
        
        $grouped = [];
        foreach ($ready_items as $item) {
            $table_key = $item['table_number'] ?? 'Unknown';
            
            if (!isset($grouped[$table_key])) {
                $grouped[$table_key] = [
                    'table_number' => $table_key,
                    'table_id' => $item['table_id'],
                    'order_id' => $item['order_id'],
                    'order_number' => $item['order_number'],
                    'items' => []
                ];
            }
            
            $grouped[$table_key]['items'][] = $item;
        }
        
        return array_values($grouped);
    }

    /**
     * Helper: Get in-progress orders grouped by table
     * @return array
     */
    private function _get_in_progress_orders_grouped()
    {
        $in_progress_items = $this->Order_model->get_in_progress_orders_full();
        
        $grouped = [];
        foreach ($in_progress_items as $item) {
            $table_key = $item['table_number'] ?? 'Unknown';
            
            if (!isset($grouped[$table_key])) {
                $grouped[$table_key] = [
                    'table_number' => $table_key,
                    'table_id' => $item['table_id'],
                    'order_id' => $item['order_id'],
                    'order_number' => $item['order_number'],
                    'kitchen_status' => $item['kitchen_status'],
                    'items' => []
                ];
            }
            
            $grouped[$table_key]['items'][] = $item;
        }
        
        return array_values($grouped);
    }

    /**
     * Helper: Count total ready items
     * @param array $orders
     * @return int
     */
    private function _count_total_ready_items($orders)
    {
        $total = 0;
        foreach ($orders as $order) {
            $total += count($order['items'] ?? []);
        }
        return $total;
    }

    /**
     * Helper: Count active orders for a table
     * @param int $table_id
     * @return int
     */
    private function _count_active_orders($table_id)
    {
        $this->db->where('table_id', $table_id);
        $this->db->where_in('status', ['pending', 'confirmed', 'preparing', 'ready']);
        $this->db->where('payment_status !=', 'paid');
        return $this->db->count_all_results('orders');
    }

    /**
     * Helper: Get status label
     * @param string $status
     * @return string
     */
    private function _get_status_label($status)
    {
        $labels = [
            'available' => 'Tersedia',
            'occupied' => 'Terisi',
            'waiting_payment' => 'Menunggu Bayar',
            'cleaning' => 'Dibersihkan',
            'maintenance' => 'Tutup/Rusak'
        ];
        
        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Helper: Get status color class
     * @param string $status
     * @return string
     */
    private function _get_status_color($status)
    {
        $colors = [
            'available' => 'bg-success',      // Green
            'occupied' => 'bg-danger',        // Red
            'waiting_payment' => 'bg-warning', // Yellow
            'cleaning' => 'bg-info',          // Blue
            'maintenance' => 'bg-secondary'   // Gray
        ];
        
        return $colors[$status] ?? 'bg-secondary';
    }

    /**
     * Helper: Log activity
     * @param string $action
     * @param string $description
     * @param int|null $reference_id
     */
    private function _log_activity($action, $description, $reference_id = null)
    {
        $user = $this->session->userdata('user');
        
        $data = [
            'user_id' => $user['id'] ?? null,
            'action' => $action,
            'description' => $description,
            'reference_id' => $reference_id,
            'ip_address' => $this->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('activity_logs', $data);
    }
}
