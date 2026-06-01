<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User Model
 * Mengelola CRUD user dengan RBAC dan soft delete (active = 0)
 */
class User_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->table = 'users';
    }

    /**
     * Get all users dengan pagination server-side
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @param string $order_field
     * @param string $order_dir
     * @return array ['data' => [], 'total' => int, 'filtered' => int]
     */
    public function get_datatable($filters = [], $limit = 10, $offset = 0, $order_field = 'u.id', $order_dir = 'ASC')
    {
        // Build query
        $this->db->from($this->table . ' u');
        $this->db->where('u.deleted_at IS NULL');
        
        // Search filter
        if (!empty($filters['search'])) {
            $search = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('u.full_name', $filters['search'], 'both');
            $this->db->or_like('u.username', $filters['search'], 'both');
            $this->db->or_like('u.email', $filters['search'], 'both');
            $this->db->group_end();
        }
        
        // Role filter
        if (isset($filters['role']) && $filters['role'] !== '') {
            $this->db->where('u.role', $filters['role']);
        }
        
        // Status filter (active)
        if (isset($filters['status']) && $filters['status'] !== '') {
            $this->db->where('u.active', $filters['status']);
        }
        
        // Get total filtered
        $filtered_total = $this->db->count_all_results('', FALSE);
        
        // Get total all
        $this->db->reset_query();
        $this->db->where('deleted_at IS NULL');
        $total = $this->db->count_all($this->table, FALSE);
        
        // Ordering
        $allowed_fields = ['u.id', 'u.full_name', 'u.username', 'u.role', 'u.active', 'u.last_login', 'u.created_at'];
        if (!in_array($order_field, $allowed_fields)) {
            $order_field = 'u.id';
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
     * Get user by ID
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        $this->db->where('id', $id);
        $this->db->where('deleted_at IS NULL');
        $query = $this->db->get($this->table);
        return $query->row_array();
    }

    /**
     * Get user by username
     * @param string $username
     * @return array|null
     */
    public function get_by_username($username)
    {
        $this->db->where('username', $username);
        $this->db->where('deleted_at IS NULL');
        $query = $this->db->get($this->table);
        return $query->row_array();
    }

    /**
     * Check if username exists (case-insensitive)
     * @param string $username
     * @param int|null $exclude_id Exclude ID untuk update
     * @return bool
     */
    public function username_exists($username, $exclude_id = null)
    {
        $this->db->where('LOWER(username)', strtolower($username));
        $this->db->where('deleted_at IS NULL');
        if ($exclude_id !== null) {
            $this->db->where('id !=', $exclude_id);
        }
        $query = $this->db->get($this->table, 1);
        return $query->num_rows() > 0;
    }

    /**
     * Check if email exists (case-insensitive)
     * @param string $email
     * @param int|null $exclude_id Exclude ID untuk update
     * @return bool
     */
    public function email_exists($email, $exclude_id = null)
    {
        $this->db->where('LOWER(email)', strtolower($email));
        $this->db->where('deleted_at IS NULL');
        if ($exclude_id !== null) {
            $this->db->where('id !=', $exclude_id);
        }
        $query = $this->db->get($this->table, 1);
        return $query->num_rows() > 0;
    }

    /**
     * Create new user
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
     * Update user
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
     * Soft delete user (set active = 0 dan deleted_at)
     * @param int $id
     * @return bool
     */
    public function soft_delete($id)
    {
        $data = [
            'active' => 0,
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Toggle user active status
     * @param int $id
     * @return bool|null New status atau FALSE jika gagal
     */
    public function toggle_status($id)
    {
        $user = $this->get_by_id($id);
        if (!$user) {
            return FALSE;
        }
        
        $new_status = $user['active'] == 1 ? 0 : 1;
        $this->update($id, ['active' => $new_status]);
        
        return $new_status;
    }

    /**
     * Count active admins
     * @return int
     */
    public function count_active_admins()
    {
        $this->db->where('role', 'admin');
        $this->db->where('active', 1);
        $this->db->where('deleted_at IS NULL');
        return $this->db->count_all_results($this->table);
    }

    /**
     * Get last login info
     * @param int $user_id
     * @return string|null
     */
    public function get_last_login($user_id)
    {
        $user = $this->get_by_id($user_id);
        return $user['last_login'] ?? null;
    }

    /**
     * Update last login timestamp
     * @param int $user_id
     * @return bool
     */
    public function update_last_login($user_id)
    {
        $data = ['last_login' => date('Y-m-d H:i:s')];
        $this->db->where('id', $user_id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Get all active users for dropdown
     * @return array
     */
    public function get_active_list()
    {
        $this->db->where('active', 1);
        $this->db->where('deleted_at IS NULL');
        $this->db->order_by('full_name', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Get users by role
     * @param string $role
     * @return array
     */
    public function get_by_role($role)
    {
        $this->db->where('role', $role);
        $this->db->where('active', 1);
        $this->db->where('deleted_at IS NULL');
        $this->db->order_by('full_name', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }
}
