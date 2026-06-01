<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Menu Item Model
 * Handles menu items operations for customer-facing features
 * Based on SRS v4.0
 */
class Menu_item_model extends CI_Model {

    private $table = 'menu_items';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all available menu items for customer display
     * @return array
     */
    public function get_all_available()
    {
        $this->db->select('m.*, c.name as category_name');
        $this->db->from($this->table . ' m');
        $this->db->join('categories c', 'c.id = m.category_id', 'left');
        $this->db->where('m.is_available', 1);
        $this->db->where('m.deleted_at IS NULL');
        $this->db->order_by('m.sort_order', 'ASC');
        $this->db->order_by('m.name', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get all menu items (including unavailable) for admin
     * @return array
     */
    public function get_all()
    {
        $this->db->select('m.*, c.name as category_name');
        $this->db->from($this->table . ' m');
        $this->db->join('categories c', 'c.id = m.category_id', 'left');
        $this->db->where('m.deleted_at IS NULL');
        $this->db->order_by('c.sort_order', 'ASC');
        $this->db->order_by('m.sort_order', 'ASC');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get menu item by ID
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        $query = $this->db->get_where($this->table, ['id' => $id, 'deleted_at' => null], 1);
        return $query->row_array();
    }

    /**
     * Get menu items by category
     * @param int $category_id
     * @param bool $available_only
     * @return array
     */
    public function get_by_category($category_id, $available_only = true)
    {
        $this->db->where('category_id', $category_id);
        $this->db->where('deleted_at IS NULL');
        
        if ($available_only) {
            $this->db->where('is_available', 1);
        }
        
        $this->db->order_by('sort_order', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Search menu items by keyword
     * @param string $keyword
     * @param bool $available_only
     * @return array
     */
    public function search($keyword, $available_only = true)
    {
        $this->db->select('m.*, c.name as category_name');
        $this->db->from($this->table . ' m');
        $this->db->join('categories c', 'c.id = m.category_id', 'left');
        $this->db->where('m.deleted_at IS NULL');
        
        if ($available_only) {
            $this->db->where('m.is_available', 1);
        }
        
        $this->db->group_start();
        $this->db->like('m.name', $keyword, 'both');
        $this->db->or_like('m.description', $keyword, 'both');
        $this->db->or_like('c.name', $keyword, 'both');
        $this->db->group_end();
        
        $this->db->order_by('m.name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Update availability status
     * @param int $id
     * @param bool $is_available
     * @return bool
     */
    public function update_availability($id, $is_available)
    {
        $data = [
            'is_available' => $is_available ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Soft delete menu item
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $data = [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Insert new menu item
     * @param array $data
     * @return int|bool Insert ID or false
     */
    public function insert($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->insert($this->table, $data)) {
            return $this->db->insert_id();
        }
        return false;
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
     * Check if menu item name exists (case-insensitive)
     * @param string $name
     * @param int|null $exclude_id
     * @return bool
     */
    public function name_exists($name, $exclude_id = null)
    {
        $this->db->where('LOWER(name)', strtolower($name));
        $this->db->where('deleted_at IS NULL');
        
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        
        $query = $this->db->get($this->table, 1);
        return $query->num_rows() > 0;
    }

    /**
     * Get featured menu items
     * @param int $limit
     * @return array
     */
    public function get_featured($limit = 5)
    {
        $this->db->where('is_featured', 1);
        $this->db->where('is_available', 1);
        $this->db->where('deleted_at IS NULL');
        $this->db->order_by('sort_order', 'ASC');
        $this->db->limit($limit);
        
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Count total menu items
     * @param bool $available_only
     * @return int
     */
    public function count($available_only = false)
    {
        $this->db->where('deleted_at IS NULL');
        
        if ($available_only) {
            $this->db->where('is_available', 1);
        }
        
        return $this->db->count_all_results($this->table);
    }

    /**
     * Get menu items with low stock (if stock tracking is enabled)
     * @param int $threshold
     * @return array
     */
    public function get_low_stock($threshold = 10)
    {
        // This would require a stock field in the table
        // Placeholder for future implementation
        return [];
    }
}
