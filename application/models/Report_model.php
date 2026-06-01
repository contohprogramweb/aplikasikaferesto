<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Report Model
 * Menangani query agregat untuk laporan penjualan, pendapatan, dan statistik.
 */
class Report_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get Dashboard Summary
     * @param string $start_date YYYY-MM-DD
     * @param string $end_date YYYY-MM-DD
     * @return array Summary stats
     */
    public function get_dashboard_summary($start_date, $end_date)
    {
        $this->db->select('
            COUNT(DISTINCT o.id) as total_orders,
            COUNT(DISTINCT o.table_id) as unique_tables,
            SUM(CASE WHEN o.status = "completed" THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN o.status = "cancelled" THEN 1 ELSE 0 END) as cancelled_orders
        ');
        $this->db->from('orders o');
        $this->db->where('DATE(o.created_at) >=', $start_date);
        $this->db->where('DATE(o.created_at) <=', $end_date);
        
        $query = $this->db->get();
        $stats = $query->row_array();

        // Get Revenue
        $this->db->select('SUM(amount) as total_revenue');
        $this->db->from('transactions');
        $this->db->where('DATE(created_at) >=', $start_date);
        $this->db->where('DATE(created_at) <=', $end_date);
        $this->db->where('status', 'paid');
        $rev_query = $this->db->get();
        $revenue = $rev_query->row_array();

        $stats['total_revenue'] = $revenue['total_revenue'] ?? 0;
        
        return $stats;
    }

    /**
     * Get Sales by Category
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function get_sales_by_category($start_date, $end_date)
    {
        $this->db->select('c.name as category_name, SUM(oi.quantity) as total_qty, SUM(oi.total_price) as total_sales');
        $this->db->from('order_items oi');
        $this->db->join('menu_items mi', 'oi.menu_item_id = mi.id');
        $this->db->join('categories c', 'mi.category_id = c.id');
        $this->db->join('orders o', 'oi.order_id = o.id');
        $this->db->where('DATE(o.created_at) >=', $start_date);
        $this->db->where('DATE(o.created_at) <=', $end_date);
        $this->db->where('o.status !=', 'cancelled');
        $this->db->group_by('c.id');
        $this->db->order_by('total_sales', 'DESC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Get Top Selling Items
     * @param string $start_date
     * @param string $end_date
     * @param int $limit
     * @return array
     */
    public function get_top_items($start_date, $end_date, $limit = 10)
    {
        $this->db->select('mi.name as item_name, SUM(oi.quantity) as total_qty, SUM(oi.total_price) as total_sales');
        $this->db->from('order_items oi');
        $this->db->join('menu_items mi', 'oi.menu_item_id = mi.id');
        $this->db->join('orders o', 'oi.order_id = o.id');
        $this->db->where('DATE(o.created_at) >=', $start_date);
        $this->db->where('DATE(o.created_at) <=', $end_date);
        $this->db->where('o.status !=', 'cancelled');
        $this->db->group_by('mi.id');
        $this->db->order_by('total_qty', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result_array();
    }

    /**
     * Get Daily Revenue Trend
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function get_daily_revenue($start_date, $end_date)
    {
        $this->db->select('DATE(created_at) as date, SUM(amount) as revenue');
        $this->db->from('transactions');
        $this->db->where('DATE(created_at) >=', $start_date);
        $this->db->where('DATE(created_at) <=', $end_date);
        $this->db->where('status', 'paid');
        $this->db->group_by('DATE(created_at)');
        $this->db->order_by('date', 'ASC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Get Transactions for Refund/Detail
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function get_transactions($start_date, $end_date)
    {
        $this->db->select('t.*, o.order_number, u.username as cashier_name, tb.code as table_code');
        $this->db->from('transactions t');
        $this->db->join('orders o', 't.order_id = o.id');
        $this->db->join('users u', 't.user_id = u.id');
        $this->db->join('tables tb', 'o.table_id = tb.id');
        $this->db->where('DATE(t.created_at) >=', $start_date);
        $this->db->where('DATE(t.created_at) <=', $end_date);
        $this->db->order_by('t.created_at', 'DESC');
        
        return $this->db->get()->result_array();
    }
}
