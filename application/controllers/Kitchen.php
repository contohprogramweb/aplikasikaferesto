<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kitchen Controller
 * Handles Kitchen Display System (KDS) operations
 * Based on SRS v4.0:
 * - UC-KIT-01: Lihat Pesanan Masuk (View Incoming Orders)
 * - UC-KIT-02: Update Status Pesanan (Update Order Status)
 * - UC-KIT-03: Tandai Item Tidak Tersedia (Mark Item Unavailable)
 */
class Kitchen extends CI_Controller {

    private $valid_statuses = ['diterima', 'dimasak', 'siap'];
    private $kitchen_statuses = ['pending', 'confirmed', 'preparing', 'ready'];

    public function __construct()
    {
        parent::__construct();
        
        // Check if user is logged in and has kitchen role
        if (!$this->session->userdata('logged_in')) {
            redirect('auth/login');
            return;
        }
        
        $user = $this->session->userdata('user');
        $allowed_roles = ['kitchen', 'admin', 'manager'];
        
        if (!in_array($user['role'], $allowed_roles)) {
            show_error('Akses ditolak. Hanya staff kitchen yang dapat mengakses halaman ini.', 403);
            return;
        }
        
        $this->load->model('Order_model');
        $this->load->helper(['text', 'date']);
    }

    /**
     * UC-KIT-01: Halaman KDS (Kitchen Display System)
     * GET /kitchen
     * 
     * Features:
     * - Grid layout: 3 columns (desktop ≥1280px), 2 columns (tablet), 1 column (mobile)
     * - Sticky header with active orders count, average wait time, batch accept, mute button
     * - Order cards with fixed height 280px
     * - Highlight new orders (<2 minutes) with yellow border and pulse animation
     * - Auto-refresh indicator (green/red dot)
     */
    public function index()
    {
        $data['page_title'] = 'Kitchen Display System';
        $data['page_subtitle'] = 'Manajemen Pesanan Dapur';
        
        // Load initial orders
        $data['orders'] = $this->Order_model->get_kds_orders_full();
        
        // Calculate statistics
        $stats = $this->_calculate_kitchen_stats($data['orders']);
        $data['active_orders_count'] = $stats['active_orders_count'];
        $data['avg_wait_time'] = $stats['avg_wait_time'];
        $data['pending_count'] = $stats['pending_count'];
        
        // Load views
        $this->load->view('templates/header', $data);
        $this->load->view('kitchen/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * AJAX Polling Endpoint - Smart Polling
     * POST /api/kitchen/orders
     * 
     * Request parameters:
     * - last_id: Last known order item ID (for delta updates)
     * - last_timestamp: Last known timestamp (ISO format)
     * 
     * Response:
     * - updated: Boolean indicating if there are changes
     * - data: Array of orders/items
     * - last_id: New last ID for next polling
     * - last_timestamp: New timestamp for next polling
     * 
     * Business Rules:
     * - BR-17: FIFO ordering (created_at)
     * - BR-18: Only show accepted/cooking items
     * - Rate limit: max 1 req/3 detik per session
     */
    public function orders()
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
        $last_request = $this->session->userdata('kitchen_last_poll');
        
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
        $this->session->set_userdata('kitchen_last_poll', time());
        
        // Get parameters
        $last_id = (int) $this->input->post('last_id', 0);
        $last_timestamp = $this->input->post('last_timestamp', '');
        
        // Get orders with delta filtering
        $orders = $this->Order_model->get_kds_orders_delta($last_id, $last_timestamp);
        
        // Find the maximum ID and timestamp from results
        $new_last_id = $last_id;
        $new_last_timestamp = $last_timestamp;
        
        foreach ($orders as $order) {
            if (!empty($order['items'])) {
                foreach ($order['items'] as $item) {
                    if ($item['id'] > $new_last_id) {
                        $new_last_id = $item['id'];
                    }
                    if (empty($new_last_timestamp) || $item['created_at'] > $new_last_timestamp) {
                        $new_last_timestamp = $item['created_at'];
                    }
                }
            }
        }
        
        // Determine if there are updates
        $has_updates = !empty($orders) && ($new_last_id > $last_id || $new_last_timestamp > $last_timestamp);
        
        echo json_encode([
            'status' => 'success',
            'updated' => $has_updates,
            'data' => $orders,
            'last_id' => $new_last_id,
            'last_timestamp' => $new_last_timestamp,
            'count' => count($orders)
        ]);
    }

    /**
     * UC-KIT-01: Accept Order(s)
     * POST /kitchen/accept
     * 
     * Updates order status from 'pending' to 'confirmed' (diterima)
     * Supports batch accept via array of order IDs
     * 
     * Business Rules:
     * - BR-19: No double accept
     * - BR-20: Sequential status flow (no skipping)
     */
    public function accept()
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
        
        $order_ids = $this->input->post('order_ids');
        
        if (empty($order_ids)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No order IDs provided',
                'code' => 422
            ]);
            return;
        }
        
        // Convert to array if single value
        if (!is_array($order_ids)) {
            $order_ids = [$order_ids];
        }
        
        $this->db->trans_start();
        
        $accepted_count = 0;
        $skipped_count = 0;
        
        foreach ($order_ids as $order_id) {
            $order_id = (int) $order_id;
            $order = $this->Order_model->get_by_id($order_id);
            
            if (!$order) {
                continue;
            }
            
            // BR-19: Prevent double accept
            if (!in_array($order['status'], ['pending'])) {
                $skipped_count++;
                continue;
            }
            
            // Update order status to confirmed (diterima)
            $this->Order_model->update_status($order_id, 'confirmed');
            
            // Update all order items status to confirmed
            $items = $this->Order_model->get_items_by_order($order_id);
            foreach ($items as $item) {
                if ($item['status'] === 'pending') {
                    $this->Order_model->update_item_status($item['id'], 'confirmed');
                }
            }
            
            $accepted_count++;
            
            // Log activity
            $this->_log_activity('order_accepted', 'Order #' . $order['order_number'] . ' accepted by kitchen', $order_id);
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to accept orders',
                'code' => 500
            ]);
            return;
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Berhasil menerima ' . $accepted_count . ' pesanan',
            'accepted_count' => $accepted_count,
            'skipped_count' => $skipped_count
        ]);
    }

    /**
     * UC-KIT-02: Update Item Status
     * POST /kitchen/update_status
     * 
     * Updates individual item status through the flow:
     * diterima (confirmed) → dimasak (preparing) → siap (ready)
     * 
     * Parameters:
     * - item_id: Order item ID
     * - status: New status (confirmed, preparing, ready)
     * 
     * Business Rules:
     * - BR-20: Sequential status flow (no skipping)
     * - Undo allowed within 30 seconds (1x per item)
     */
    public function update_status()
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
        
        $item_id = (int) $this->input->post('item_id');
        $new_status = $this->input->post('status');
        
        if (!$item_id || !$new_status) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Item ID and status required',
                'code' => 422
            ]);
            return;
        }
        
        // Validate status
        $status_map = [
            'diterima' => 'confirmed',
            'dimasak' => 'preparing',
            'siap' => 'ready'
        ];
        
        if (!isset($status_map[$new_status])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid status. Must be: diterima, dimasak, or siap',
                'code' => 422
            ]);
            return;
        }
        
        $target_status = $status_map[$new_status];
        
        // Get item
        $item = $this->Order_model->get_item_by_id($item_id);
        
        if (!$item) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Item not found',
                'code' => 404
            ]);
            return;
        }
        
        // BR-20: Validate sequential status flow
        $status_sequence = ['pending' => 0, 'confirmed' => 1, 'preparing' => 2, 'ready' => 3];
        $current_level = isset($status_sequence[$item['status']]) ? $status_sequence[$item['status']] : 0;
        $target_level = isset($status_sequence[$target_status]) ? $status_sequence[$target_status] : 0;
        
        // Can only move forward one step at a time
        if ($target_level !== $current_level + 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid status transition. Current: ' . $item['status'] . ', Target: ' . $target_status,
                'code' => 400,
                'current_status' => $item['status'],
                'allowed_next_status' => array_search($current_level + 1, $status_sequence)
            ]);
            return;
        }
        
        $this->db->trans_start();
        
        // Update item status
        $this->Order_model->update_item_status($item_id, $target_status);
        
        // Check if all items in order are ready -> update order status
        $order = $this->Order_model->get_by_id($item['order_id']);
        $order_items = $this->Order_model->get_items_by_order($item['order_id']);
        
        // BR-22: If all active items are ready, set order status to ready
        $all_ready = true;
        $has_active_items = false;
        
        foreach ($order_items as $oi) {
            if ($oi['status'] !== 'cancelled') {
                $has_active_items = true;
                if ($oi['status'] !== 'ready') {
                    $all_ready = false;
                }
            }
        }
        
        if ($has_active_items && $all_ready) {
            $this->Order_model->update_status($item['order_id'], 'ready');
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update status',
                'code' => 500
            ]);
            return;
        }
        
        // Store undo info in session (30 seconds window)
        $undo_data = $this->session->userdata('kitchen_undo') ?: [];
        $undo_data[$item_id] = [
            'previous_status' => $item['status'],
            'timestamp' => time()
        ];
        $this->session->set_userdata('kitchen_undo', $undo_data);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Status berhasil diubah ke ' . $new_status,
            'item_id' => $item_id,
            'new_status' => $target_status,
            'can_undo' => true,
            'undo_until' => time() + 30
        ]);
    }

    /**
     * UC-KIT-03: Cancel Item (Mark as Unavailable)
     * POST /kitchen/cancel_item
     * 
     * Marks an item as cancelled due to unavailability
     * 
     * Parameters:
     * - item_id: Order item ID
     * - reason: Optional cancellation reason (max 100 chars)
     * 
     * Business Rules:
     * - BR-23: Can only cancel if status is 'diterima' (confirmed)
     * - Updates order subtotal (deduct cancelled item price)
     * - Notifies customer via polling
     */
    public function cancel_item()
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
        
        $item_id = (int) $this->input->post('item_id');
        $reason = $this->input->post('reason');
        
        if (!$item_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Item ID required',
                'code' => 422
            ]);
            return;
        }
        
        // Validate reason length
        if (!empty($reason)) {
            $reason = substr(trim($reason), 0, 100);
        }
        
        // Get item
        $item = $this->Order_model->get_item_by_id($item_id);
        
        if (!$item) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Item not found',
                'code' => 404
            ]);
            return;
        }
        
        // BR-23: Can only cancel if status is 'confirmed' (diterima)
        if ($item['status'] !== 'confirmed') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Hanya item dengan status "diterima" yang dapat dibatalkan',
                'code' => 400,
                'current_status' => $item['status']
            ]);
            return;
        }
        
        $this->db->trans_start();
        
        // Update item status to cancelled
        $this->Order_model->update_item_status($item_id, 'cancelled');
        
        // Update order totals (subtract cancelled item)
        $order = $this->Order_model->get_by_id($item['order_id']);
        $totals = $this->Order_model->calculate_totals($item['order_id']);
        
        $this->Order_model->update($item['order_id'], [
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax_amount'],
            'service_charge_amount' => $totals['service_amount'],
            'total_amount' => $totals['total']
        ]);
        
        // Check if this was the last active item
        $order_items = $this->Order_model->get_items_by_order($item['order_id']);
        $has_active_items = false;
        
        foreach ($order_items as $oi) {
            if ($oi['status'] !== 'cancelled') {
                $has_active_items = true;
                break;
            }
        }
        
        // If no active items remain, cancel the order
        if (!$has_active_items) {
            $this->Order_model->update_status($item['order_id'], 'cancelled');
            $this->Order_model->update($item['order_id'], [
                'cancel_reason' => !empty($reason) ? $reason : 'Semua item tidak tersedia'
            ]);
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to cancel item',
                'code' => 500
            ]);
            return;
        }
        
        // Log activity
        $this->_log_activity(
            'item_cancelled',
            'Item cancelled: ' . $item['menu_item_name'] . ' (Order #' . $order['order_number'] . '). Reason: ' . ($reason ?? 'N/A'),
            $item_id
        );
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Item berhasil dibatalkan',
            'item_id' => $item_id,
            'order_id' => $item['order_id'],
            'reason' => $reason,
            'refunded_amount' => $item['subtotal'],
            'notified_customer' => true
        ]);
    }

    /**
     * Undo Status Update
     * POST /kitchen/undo_status
     * 
     * Reverts the last status change if within 30 seconds
     * Limited to 1 undo per item
     */
    public function undo_status()
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
        
        $item_id = (int) $this->input->post('item_id');
        
        if (!$item_id) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Item ID required',
                'code' => 422
            ]);
            return;
        }
        
        // Check undo eligibility
        $undo_data = $this->session->userdata('kitchen_undo') ?: [];
        
        if (!isset($undo_data[$item_id])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No undo available for this item',
                'code' => 400
            ]);
            return;
        }
        
        $undo_info = $undo_data[$item_id];
        
        // Check 30 second window
        if (time() - $undo_info['timestamp'] > 30) {
            // Clean up expired undo
            unset($undo_data[$item_id]);
            $this->session->set_userdata('kitchen_undo', $undo_data);
            
            echo json_encode([
                'status' => 'error',
                'message' => 'Undo window expired (30 seconds)',
                'code' => 400
            ]);
            return;
        }
        
        $this->db->trans_start();
        
        // Revert status
        $this->Order_model->update_item_status($item_id, $undo_info['previous_status']);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to undo',
                'code' => 500
            ]);
            return;
        }
        
        // Remove undo entry (1x per item)
        unset($undo_data[$item_id]);
        $this->session->set_userdata('kitchen_undo', $undo_data);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Status berhasil dikembalikan',
            'item_id' => $item_id,
            'restored_status' => $undo_info['previous_status']
        ]);
    }

    /**
     * Calculate kitchen statistics
     */
    private function _calculate_kitchen_stats($orders)
    {
        $active_orders_count = 0;
        $pending_count = 0;
        $total_wait_time = 0;
        $orders_with_wait_time = 0;
        
        foreach ($orders as $order) {
            if (in_array($order['status'], $this->kitchen_statuses)) {
                $active_orders_count++;
                
                if ($order['status'] === 'pending') {
                    $pending_count++;
                }
                
                // Calculate wait time
                $created_at = strtotime($order['created_at']);
                $wait_time = time() - $created_at;
                
                if ($wait_time > 0) {
                    $total_wait_time += $wait_time;
                    $orders_with_wait_time++;
                }
            }
        }
        
        $avg_wait_time = $orders_with_wait_time > 0 
            ? floor($total_wait_time / $orders_with_wait_time) 
            : 0;
        
        return [
            'active_orders_count' => $active_orders_count,
            'avg_wait_time' => $avg_wait_time,
            'pending_count' => $pending_count
        ];
    }

    /**
     * Log activity
     */
    private function _log_activity($action, $description, $related_id = null)
    {
        $this->load->model('Activity_log_model');
        
        if (class_exists('Activity_log_model')) {
            $this->Activity_log_model->create([
                'user_id' => $this->session->userdata('user')['id'] ?? null,
                'action' => $action,
                'description' => $description,
                'related_table' => 'orders',
                'related_id' => $related_id,
                'ip_address' => $this->input->ip_address(),
                'priority' => 'medium'
            ]);
        }
    }
}
