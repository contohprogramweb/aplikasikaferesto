<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Category Model
 * Mengelola CRUD kategori menu
 */
class Category_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->table = 'categories';
    }

    /**
     * Get all categories dengan pagination server-side
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @param string $order_field
     * @param string $order_dir
     * @return array ['data' => [], 'total' => int, 'filtered' => int]
     */
    public function get_datatable($filters = [], $limit = 10, $offset = 0, $order_field = 'sort_order', $order_dir = 'ASC')
    {
        // Build query
        $this->db->from($this->table);
        
        // Search filter
        if (!empty($filters['search'])) {
            $search = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('name', $filters['search'], 'both');
            $this->db->or_like('description', $filters['search'], 'both');
            $this->db->group_end();
        }
        
        // Status filter
        if (isset($filters['status']) && $filters['status'] !== '') {
            $this->db->where('is_active', $filters['status']);
        }
        
        // Get total filtered
        $filtered_total = $this->db->count_all_results('', FALSE);
        
        // Get total all
        $this->db->reset_query();
        $total = $this->db->count_all($this->table);
        
        // Ordering
        $allowed_fields = ['id', 'name', 'sort_order', 'is_active', 'created_at'];
        if (!in_array($order_field, $allowed_fields)) {
            $order_field = 'sort_order';
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
     * Get category by ID
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        $query = $this->db->get_where($this->table, ['id' => $id]);
        return $query->row_array();
    }

    /**
     * Check if category name exists (untuk validasi unique)
     * @param string $name
     * @param int|null $exclude_id Exclude ID untuk update
     * @return bool
     */
    public function name_exists($name, $exclude_id = null)
    {
        $this->db->where('name', $name);
        if ($exclude_id !== null) {
            $this->db->where('id !=', $exclude_id);
        }
        $query = $this->db->get($this->table, 1);
        return $query->num_rows() > 0;
    }

    /**
     * Create new category
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
     * Update category
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
     * Soft delete category (set is_active = 0)
     * @param int $id
     * @return bool
     */
    public function soft_delete($id)
    {
        $data = [
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Hard delete category (hanya jika tidak punya item)
     * @param int $id
     * @return bool TRUE jika berhasil, FALSE jika masih punya item
     */
    public function hard_delete($id)
    {
        // Cek apakah masih punya menu items
        $this->db->where('category_id', $id);
        $this->db->where('deleted_at IS NULL');
        $count = $this->db->count_all_results('menu_items');
        
        if ($count > 0) {
            return FALSE;
        }
        
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Toggle status active/inactive
     * @param int $id
     * @return bool|null New status atau FALSE jika gagal
     */
    public function toggle_status($id)
    {
        $category = $this->get_by_id($id);
        if (!$category) {
            return FALSE;
        }
        
        $new_status = $category['is_active'] == 1 ? 0 : 1;
        $this->update($id, ['is_active' => $new_status]);
        
        return $new_status;
    }

    /**
     * Get all active categories (untuk dropdown)
     * @return array
     */
    public function get_active_list()
    {
        $this->db->where('is_active', 1);
        $this->db->order_by('sort_order', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Count menu items in category
     * @param int $category_id
     * @return int
     */
    public function count_items($category_id)
    {
        $this->db->where('category_id', $category_id);
        $this->db->where('deleted_at IS NULL');
        return $this->db->count_all_results('menu_items');
    }
}
