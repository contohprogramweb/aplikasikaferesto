<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Customer Session Model
 * Manages customer session tokens for dine-in ordering
 * Based on SRS v4.0 - Customer Session Architecture
 */
class Customer_session_model extends CI_Model {

    private $table = 'customer_sessions';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get session by token
     * @param string $token
     * @return array|null
     */
    public function get_by_token($token)
    {
        $query = $this->db->get_where($this->table, ['token' => $token], 1);
        return $query->row_array();
    }

    /**
     * Get active session by table ID
     * @param int $table_id
     * @return array|null
     */
    public function get_active_by_table($table_id)
    {
        $this->db->where('table_id', $table_id);
        $this->db->where('expires_at >', date('Y-m-d H:i:s'));
        $query = $this->db->get($this->table, 1);
        return $query->row_array();
    }

    /**
     * Create new customer session
     * @param array $data
     * @return int|bool Insert ID or false on failure
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
     * Update session
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
     * Update last activity (extend session)
     * @param int $id
     * @return bool
     */
    public function update_last_activity($id)
    {
        $data = [
            'last_activity' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        return $this->update($id, $data);
    }

    /**
     * Delete session by ID
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Delete session by table ID
     * @param int $table_id
     * @return bool
     */
    public function delete_by_table($table_id)
    {
        $this->db->where('table_id', $table_id);
        return $this->db->delete($this->table);
    }

    /**
     * Delete expired sessions
     * @return int Number of deleted rows
     */
    public function delete_expired()
    {
        $this->db->where('expires_at <', date('Y-m-d H:i:s'));
        $this->db->delete($this->table);
        return $this->db->affected_rows();
    }

    /**
     * Get session with cart data
     * @param string $token
     * @return array|null
     */
    public function get_with_cart($token)
    {
        $session = $this->get_by_token($token);
        
        if ($session) {
            $session['cart_items'] = json_decode($session['cart_data'], true) ?: [];
        }
        
        return $session;
    }

    /**
     * Update cart data
     * @param string $token
     * @param array $cart_data
     * @return bool
     */
    public function update_cart($token, $cart_data)
    {
        $data = [
            'cart_data' => json_encode($cart_data),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->where('token', $token);
        return $this->db->update($this->table, $data);
    }

    /**
     * Clean up old sessions (garbage collection)
     * Should be called periodically
     * @return int Number of cleaned sessions
     */
    public function cleanup()
    {
        // Delete sessions older than 24 hours past expiry
        $this->db->where('expires_at <', date('Y-m-d H:i:s', strtotime('-24 hours')));
        $this->db->delete($this->table);
        return $this->db->affected_rows();
    }

    /**
     * Check if session is valid
     * @param string $token
     * @return bool
     */
    public function is_valid($token)
    {
        $session = $this->get_by_token($token);
        
        if (!$session) {
            return false;
        }
        
        return strtotime($session['expires_at']) > time();
    }

    /**
     * Get session count by table
     * @param int $table_id
     * @return int
     */
    public function count_by_table($table_id)
    {
        $this->db->where('table_id', $table_id);
        $this->db->where('expires_at >', date('Y-m-d H:i:s'));
        return $this->db->count_all_results($this->table);
    }

    /**
     * Get all active sessions
     * @return array
     */
    public function get_all_active()
    {
        $this->db->where('expires_at >', date('Y-m-d H:i:s'));
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Extend session expiry
     * @param string $token
     * @param int $minutes
     * @return bool
     */
    public function extend($token, $minutes = 30)
    {
        $data = [
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$minutes} minutes")),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->where('token', $token);
        return $this->db->update($this->table, $data);
    }
}
