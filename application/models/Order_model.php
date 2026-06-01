<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Order Model
 * Handles order operations for customer and staff
 * Based on SRS v4.0
 */
class Order_model extends CI_Model {

    private $table = 'orders';
    private $items_table = 'order_items';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get open order by table ID
     * An order is considered "open" if it's not completed, cancelled, or paid
     * @param int $table_id
     * @return array|null
     */
    public function get_open_order_by_table($table_id)
    {
        $open_statuses = ['pending', 'confirmed', 'preparing', 'ready'];
        
        $this->db->where('table_id', $table_id);
        $this->db->where_in('status', $open_statuses);
        $this->db->where('payment_status !=', 'paid');
        $this->db->order_by('created_at', 'DESC');
        
        $query = $this->db->get($this->table, 1);
        return $query->row_array();
    }

    /**
     * Get order by ID with items
     * @param int $id
     * @return array|null
     */
    public function get_with_items($id)
    {
        $order = $this->get_by_id($id);
        
        if ($order) {
            $order['items'] = $this->get_items_by_order($id);
        }
        
        return $order;
    }

    /**
     * Get order by ID
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        $query = $this->db->get_where($this->table, ['id' => $id], 1);
        return $query->row_array();
    }

    /**
     * Get order by order number
     * @param string $order_number
     * @return array|null
     */
    public function get_by_order_number($order_number)
    {
        $query = $this->db->get_where($this->table, ['order_number' => $order_number], 1);
        return $query->row_array();
    }

    /**
     * Get items by order ID
     * @param int $order_id
     * @return array
     */
    public function get_items_by_order($order_id)
    {
        $this->db->select('oi.*, m.name as menu_item_name, m.image as menu_item_image');
        $this->db->from($this->items_table . ' oi');
        $this->db->join('menu_items m', 'm.id = oi.menu_item_id', 'left');
        $this->db->where('oi.order_id', $order_id);
        $this->db->order_by('oi.created_at', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Create new order
     * @param array $data
     * @return int|bool Insert ID or false
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->insert($this->table, $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * Update order
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Add order item
     * @param array $data
     * @return int|bool Insert ID or false
     */
    public function add_item($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->insert($this->items_table, $data)) {
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * Update order item status
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function update_item_status($id, $status)
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($status === 'completed' || $status === 'delivered') {
            $data['delivered_at'] = date('Y-m-d H:i:s');
        }
        
        $this->db->where('id', $id);
        return $this->db->update($this->items_table, $data);
    }

    /**
     * Update order status
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function update_status($id, $status)
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Set timestamp based on status
        switch ($status) {
            case 'confirmed':
                $data['confirmed_at'] = date('Y-m-d H:i:s');
                break;
            case 'preparing':
                $data['prepared_at'] = date('Y-m-d H:i:s');
                break;
            case 'ready':
                $data['prepared_at'] = date('Y-m-d H:i:s');
                break;
            case 'completed':
                $data['completed_at'] = date('Y-m-d H:i:s');
                break;
            case 'cancelled':
                $data['cancelled_at'] = date('Y-m-d H:i:s');
                break;
        }
        
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Get ready orders for waiter (full list, no delta)
     * @return array
     */
    public function get_ready_orders_full()
    {
        $this->db->select('oi.*, o.order_number, o.table_id, t.table_number');
        $this->db->from($this->items_table . ' oi');
        $this->db->join($this->table . ' o', 'o.id = oi.order_id');
        $this->db->join('tables t', 't.id = o.table_id', 'left');
        $this->db->where('oi.status', 'ready');
        $this->db->order_by('oi.created_at', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get ready orders with delta filtering (for smart polling)
     * Returns items with ID > last_id OR created_at > last_timestamp
     * @param int $last_id
     * @param string $last_timestamp
     * @return array
     */
    public function get_ready_orders_delta($last_id = 0, $last_timestamp = '')
    {
        $this->db->select('oi.*, o.order_number, o.table_id, t.table_number');
        $this->db->from($this->items_table . ' oi');
        $this->db->join($this->table . ' o', 'o.id = oi.order_id');
        $this->db->join('tables t', 't.id = o.table_id', 'left');
        $this->db->where('oi.status', 'ready');
        
        // Delta filtering
        $delta_conditions = [];
        if ($last_id > 0) {
            $delta_conditions[] = 'oi.id > ' . (int)$last_id;
        }
        if (!empty($last_timestamp)) {
            $escaped_ts = $this->db->escape($last_timestamp);
            $delta_conditions[] = 'oi.created_at > ' . $escaped_ts;
        }
        
        if (!empty($delta_conditions)) {
            $this->db->where('(' . implode(' OR ', $delta_conditions) . ')');
        }
        
        $this->db->order_by('oi.created_at', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get in-progress orders for waiter (items not yet ready)
     * @return array
     */
    public function get_in_progress_orders_full()
    {
        $this->db->select('oi.*, o.order_number, o.table_id, t.table_number, oi.status as kitchen_status');
        $this->db->from($this->items_table . ' oi');
        $this->db->join($this->table . ' o', 'o.id = oi.order_id');
        $this->db->join('tables t', 't.id = o.table_id', 'left');
        $this->db->where_in('oi.status', ['pending', 'confirmed', 'preparing']);
        $this->db->order_by('oi.created_at', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get orders for KDS (Kitchen Display System)
     * @param string $last_timestamp
     * @param int $limit
     * @return array
     */
    public function get_kds_orders($last_timestamp = null, $limit = 50)
    {
        $this->db->select('o.*, t.table_number');
        $this->db->from($this->table . ' o');
        $this->db->join('tables t', 't.id = o.table_id', 'left');
        $this->db->where_in('o.status', ['pending', 'confirmed', 'preparing', 'ready']);
        
        if ($last_timestamp) {
            $this->db->where('o.created_at >', $last_timestamp);
        }
        
        $this->db->order_by('o.created_at', 'ASC');
        $this->db->limit($limit);
        
        $query = $this->db->get();
        $orders = $query->result_array();
        
        // Add items to each order
        foreach ($orders as &$order) {
            $order['items'] = $this->get_items_by_order($order['id']);
        }
        
        return $orders;
    }

    /**
     * Get full KDS orders (for initial page load)
     * Includes all active orders with their items
     * @return array
     */
    public function get_kds_orders_full()
    {
        return $this->get_kds_orders(null, 100);
    }

    /**
     * Get KDS orders with delta filtering (for smart polling)
     * Returns orders/items with ID > last_id OR created_at > last_timestamp
     * @param int $last_id
     * @param string $last_timestamp
     * @return array
     */
    public function get_kds_orders_delta($last_id = 0, $last_timestamp = '')
    {
        // Get orders that have new/updated items since last poll
        $this->db->select('o.*, t.table_number');
        $this->db->from($this->table . ' o');
        $this->db->join('tables t', 't.id = o.table_id', 'left');
        $this->db->join($this->items_table . ' oi', 'oi.order_id = o.id');
        $this->db->where_in('o.status', ['pending', 'confirmed', 'preparing', 'ready']);
        
        // Delta filtering: get orders with items newer than last known state
        $delta_conditions = [];
        if ($last_id > 0) {
            $delta_conditions[] = 'oi.id > ' . (int)$last_id;
        }
        if (!empty($last_timestamp)) {
            $escaped_ts = $this->db->escape($last_timestamp);
            $delta_conditions[] = 'oi.created_at > ' . $escaped_ts;
        }
        
        if (!empty($delta_conditions)) {
            $this->db->where('(' . implode(' OR ', $delta_conditions) . ')');
        }
        
        $this->db->group_by('o.id');
        $this->db->order_by('o.created_at', 'ASC');
        $this->db->limit(50);
        
        $query = $this->db->get();
        $orders = $query->result_array();
        
        // Add items to each order (with delta filtering)
        foreach ($orders as &$order) {
            $this->db->select('oi.*, m.name as menu_item_name, m.image as menu_item_image');
            $this->db->from($this->items_table . ' oi');
            $this->db->join('menu_items m', 'm.id = oi.menu_item_id', 'left');
            $this->db->where('oi.order_id', $order['id']);
            
            // Apply same delta filtering to items
            if (!empty($delta_conditions)) {
                $this->db->where('(' . implode(' OR ', $delta_conditions) . ')');
            }
            
            $this->db->order_by('oi.created_at', 'ASC');
            $items_query = $this->db->get();
            $order['items'] = $items_query->result_array();
        }
        
        return $orders;
    }

    /**
     * Get order item by ID
     * @param int $id
     * @return array|null
     */
    public function get_item_by_id($id)
    {
        $this->db->select('oi.*, m.name as menu_item_name, m.image as menu_item_image');
        $this->db->from($this->items_table . ' oi');
        $this->db->join('menu_items m', 'm.id = oi.menu_item_id', 'left');
        $this->db->where('oi.id', $id);
        
        $query = $this->db->get();
        return $query->row_array();
    }

    /**
     * Get ready orders for waiter
     * @param string $last_id
     * @return array
     */
    public function get_ready_orders($last_id = null)
    {
        $this->db->select('o.*, t.table_number');
        $this->db->from($this->items_table . ' oi');
        $this->db->join($this->table . ' o', 'o.id = oi.order_id');
        $this->db->join('tables t', 't.id = o.table_id', 'left');
        $this->db->where('oi.status', 'ready');
        
        if ($last_id) {
            $this->db->where('oi.id >', $last_id);
        }
        
        $this->db->order_by('oi.created_at', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Calculate order totals
     * @param int $order_id
     * @return array ['subtotal' => float, 'total' => float]
     */
    public function calculate_totals($order_id)
    {
        $this->db->select_sum('subtotal', 'subtotal');
        $this->db->where('order_id', $order_id);
        $query = $this->db->get($this->items_table);
        $result = $query->row_array();
        
        $subtotal = (float) ($result['subtotal'] ?? 0);
        
        // Add tax and service charge if applicable
        $tax_rate = $this->config->item('tax_rate') ?: 0.10; // 10% default
        $service_rate = $this->config->item('service_rate') ?: 0.05; // 5% default
        
        $tax_amount = $subtotal * $tax_rate;
        $service_amount = $subtotal * $service_rate;
        $total = $subtotal + $tax_amount + $service_amount;
        
        return [
            'subtotal' => $subtotal,
            'tax_amount' => $tax_amount,
            'service_amount' => $service_amount,
            'total' => $total
        ];
    }

    /**
     * Get recent orders for dashboard
     * @param int $limit
     * @return array
     */
    public function get_recent($limit = 10)
    {
        $this->db->select('o.*, t.table_number');
        $this->db->from($this->table . ' o');
        $this->db->join('tables t', 't.id = o.table_id', 'left');
        $this->db->order_by('o.created_at', 'DESC');
        $this->db->limit($limit);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Count orders by status
     * @param string $status
     * @return int
     */
    public function count_by_status($status)
    {
        $this->db->where('status', $status);
        return $this->db->count_all_results($this->table);
    }

    /**
     * Generate unique order number
     * Format: [PREFIX]-YYYYMMDD-XXXX
     * @return string
     */
    public function generate_order_number()
    {
        $prefix = $this->config->item('order_prefix') ?: 'ORD';
        $date_part = date('Ymd');
        
        // Use order_counters table for atomic increment
        $this->db->replace('order_counters', [
            'counter_name' => 'daily_orders_' . $date_part,
            'current_value' => 1
        ]);
        
        $this->db->set('current_value', 'current_value + 1', FALSE);
        $this->db->where('counter_name', 'daily_orders_' . $date_part);
        $this->db->update('order_counters');
        
        $query = $this->db->get_where('order_counters', ['counter_name' => 'daily_orders_' . $date_part]);
        $row = $query->row_array();
        
        $sequence = str_pad($row['current_value'], 4, '0', STR_PAD_LEFT);
        
        return $prefix . '-' . $date_part . '-' . $sequence;
    }
}
