<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Menu Model
 * Mengelola CRUD menu items dengan join categories
 */
class Menu_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->table = 'menu_items';
    }

    /**
     * Get all menu items dengan pagination server-side dan join categories
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @param string $order_field
     * @param string $order_dir
     * @return array ['data' => [], 'total' => int, 'filtered' => int]
     */
    public function get_datatable($filters = [], $limit = 10, $offset = 0, $order_field = 'm.sort_order', $order_dir = 'ASC')
    {
        // Build query dengan join categories
        $this->db->from($this->table . ' m');
        $this->db->join('categories c', 'c.id = m.category_id', 'left');
        $this->db->where('m.deleted_at IS NULL');
        
        // Search filter
        if (!empty($filters['search'])) {
            $search = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('m.name', $filters['search'], 'both');
            $this->db->or_like('m.description', $filters['search'], 'both');
            $this->db->or_like('c.name', $filters['search'], 'both');
            $this->db->group_end();
        }
        
        // Category filter
        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $this->db->where('m.category_id', $filters['category_id']);
        }
        
        // Status filter (is_available)
        if (isset($filters['status']) && $filters['status'] !== '') {
            $this->db->where('m.is_available', $filters['status']);
        }
        
        // Get total filtered
        $filtered_total = $this->db->count_all_results('', FALSE);
        
        // Get total all
        $this->db->reset_query();
        $this->db->where('deleted_at IS NULL');
        $total = $this->db->count_all($this->table, FALSE);
        
        // Ordering
        $allowed_fields = ['m.id', 'm.name', 'c.name', 'm.price', 'm.is_available', 'm.sort_order', 'm.created_at'];
        if (!in_array($order_field, $allowed_fields)) {
            $order_field = 'm.sort_order';
        }
        $order_dir = strtoupper($order_dir) === 'DESC' ? 'DESC' : 'ASC';
        $this->db->order_by($order_field, $order_dir);
        
        // Pagination
        $this->db->limit($limit, $offset);
        
        // Execute
        $query = $this->db->get();
        
        return [
            'data' => $query->result_array(),
            'total' => $total,
            'filtered' => $filtered_total
        ];
    }

    /**
     * Get menu item by ID dengan category info
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        $this->db->select('m.*, c.name as category_name');
        $this->db->from($this->table . ' m');
        $this->db->join('categories c', 'c.id = m.category_id', 'left');
        $this->db->where('m.id', $id);
        $query = $this->db->get();
        return $query->row_array();
    }

    /**
     * Check if menu name exists (untuk validasi unique, case-insensitive)
     * @param string $name
     * @param int|null $exclude_id Exclude ID untuk update
     * @return bool
     */
    public function name_exists($name, $exclude_id = null)
    {
        $this->db->where('LOWER(name)', strtolower($name));
        $this->db->where('deleted_at IS NULL');
        if ($exclude_id !== null) {
            $this->db->where('id !=', $exclude_id);
        }
        $query = $this->db->get($this->table, 1);
        return $query->num_rows() > 0;
    }

    /**
     * Create new menu item
     * @param array $data
     * @return int|bool Insert ID atau FALSE jika gagal
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->insert($this->table, $data)) {
            return $this->db->insert_id();
        }
        return FALSE;
    }

    /**
     * Update menu item
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
     * Soft delete menu item (set deleted_at)
     * @param int $id
     * @return bool
     */
    public function soft_delete($id)
    {
        $data = [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Toggle availability status
     * @param int $id
     * @return bool|null New status atau FALSE jika gagal
     */
    public function toggle_available($id)
    {
        $menu = $this->get_by_id($id);
        if (!$menu) {
            return FALSE;
        }
        
        $new_status = $menu['is_available'] == 1 ? 0 : 1;
        $this->update($id, ['is_available' => $new_status]);
        
        return $new_status;
    }

    /**
     * Get all active menu items (untuk dropdown/API)
     * @return array
     */
    public function get_active_list()
    {
        $this->db->where('is_available', 1);
        $this->db->where('deleted_at IS NULL');
        $this->db->order_by('sort_order', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Get menu items by category
     * @param int $category_id
     * @return array
     */
    public function get_by_category($category_id)
    {
        $this->db->where('category_id', $category_id);
        $this->db->where('is_available', 1);
        $this->db->where('deleted_at IS NULL');
        $this->db->order_by('sort_order', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Get featured menu items
     * @return array
     */
    public function get_featured()
    {
        $this->db->where('is_featured', 1);
        $this->db->where('is_available', 1);
        $this->db->where('deleted_at IS NULL');
        $this->db->order_by('sort_order', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Search menu items
     * @param string $keyword
     * @return array
     */
    public function search($keyword)
    {
        $this->db->where('deleted_at IS NULL');
        $this->db->group_start();
        $this->db->like('name', $keyword, 'both');
        $this->db->or_like('description', $keyword, 'both');
        $this->db->group_end();
        $this->db->order_by('sort_order', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }
}
