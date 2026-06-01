<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Activity Log Model
 * 
 * Handles audit logging for all critical system actions
 * Stores old values, new values, user info, IP, and timestamps
 */
class Activity_log_model extends CI_Model {

    private $table_name = 'activity_logs';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Create a new activity log entry
     * 
     * @param array $data Log data including:
     *   - action: string (required) - The action performed
     *   - table: string (optional) - Table affected
     *   - record_id: int|string (optional) - Record ID affected
     *   - old_value: array|object (optional) - Previous state (will be JSON encoded)
     *   - new_value: array|object (optional) - New state (will be JSON encoded)
     *   - user_id: int (optional) - User who performed action (defaults to current session)
     *   - user_type: string (optional) - Type of user (admin, cashier, kitchen, waiter)
     *   - ip_address: string (optional) - Auto-detected if not provided
     *   - user_agent: string (optional) - Auto-detected if not provided
     *   - description: string (optional) - Human-readable description
     * 
     * @return int|bool Insert ID on success, FALSE on failure
     */
    public function create($data = [])
    {
        // Validate required field
        if (empty($data['action'])) {
            log_message('error', 'Activity_log_model::create - Missing required "action" field');
            return FALSE;
        }

        // Set defaults from session if not provided
        $ci =& get_instance();
        
        $log_data = [
            'action' => $data['action'],
            'table_name' => isset($data['table']) ? $data['table'] : NULL,
            'record_id' => isset($data['record_id']) ? $data['record_id'] : NULL,
            'old_value' => isset($data['old_value']) ? json_encode($data['old_value']) : NULL,
            'new_value' => isset($data['new_value']) ? json_encode($data['new_value']) : NULL,
            'user_id' => isset($data['user_id']) ? $data['user_id'] : ($ci->session->userdata('user_id') ?? NULL),
            'user_type' => isset($data['user_type']) ? $data['user_type'] : ($ci->session->userdata('role') ?? NULL),
            'ip_address' => isset($data['ip_address']) ? $data['ip_address'] : $ci->input->ip_address(),
            'user_agent' => isset($data['user_agent']) ? $data['user_agent'] : $ci->agent->agent_string(),
            'description' => isset($data['description']) ? $data['description'] : NULL,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Attempt to insert
        try {
            $this->db->insert($this->table_name, $log_data);
            
            if ($this->db->affected_rows() > 0) {
                $insert_id = $this->db->insert_id();
                log_message('info', 'Activity log created: ID=' . $insert_id . ', Action=' . $data['action']);
                return $insert_id;
            }
            
            return FALSE;
        } catch (Exception $e) {
            // Graceful degradation - log error but don't break the main flow
            log_message('error', 'Activity_log_model::create - Database error: ' . $e->getMessage());
            
            // If table doesn't exist yet, create it automatically (development mode)
            if (strpos($e->getMessage(), 'doesn\'t exist') !== FALSE || strpos($e->getMessage(), 'Table') !== FALSE) {
                $this->_create_table_if_not_exists();
                // Retry once
                $this->db->insert($this->table_name, $log_data);
                return $this->db->insert_id();
            }
            
            return FALSE;
        }
    }

    /**
     * Get activity logs with filtering
     * 
     * @param array $filters Filtering options
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return array Query results
     */
    public function get_logs($filters = [], $limit = 50, $offset = 0)
    {
        $this->db->select('*');
        $this->db->from($this->table_name);
        
        // Apply filters
        if (!empty($filters['action'])) {
            $this->db->like('action', $filters['action']);
        }
        
        if (!empty($filters['table_name'])) {
            $this->db->where('table_name', $filters['table_name']);
        }
        
        if (!empty($filters['user_id'])) {
            $this->db->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['record_id'])) {
            $this->db->where('record_id', $filters['record_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $this->db->where('created_at >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $this->db->where('created_at <=', $filters['date_to']);
        }
        
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get total count of logs matching filters
     * 
     * @param array $filters Filtering options
     * @return int Total count
     */
    public function count_logs($filters = [])
    {
        $this->db->from($this->table_name);
        
        if (!empty($filters['action'])) {
            $this->db->like('action', $filters['action']);
        }
        
        if (!empty($filters['table_name'])) {
            $this->db->where('table_name', $filters['table_name']);
        }
        
        if (!empty($filters['user_id'])) {
            $this->db->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['record_id'])) {
            $this->db->where('record_id', $filters['record_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $this->db->where('created_at >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $this->db->where('created_at <=', $filters['date_to']);
        }
        
        return $this->db->count_all_results();
    }

    /**
     * Clean up old activity logs (older than specified days)
     * 
     * @param int $days Number of days to keep
     * @return int Number of deleted records
     */
    public function cleanup_old_logs($days = 90)
    {
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $this->db->where('created_at <', $cutoff_date);
        $this->db->delete($this->table_name);
        
        return $this->db->affected_rows();
    }

    /**
     * Create the activity_logs table if it doesn't exist
     * Used for automatic schema creation in development
     */
    private function _create_table_if_not_exists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(100) NOT NULL,
            table_name VARCHAR(100) DEFAULT NULL,
            record_id VARCHAR(50) DEFAULT NULL,
            old_value TEXT DEFAULT NULL COMMENT 'JSON encoded old values',
            new_value TEXT DEFAULT NULL COMMENT 'JSON encoded new values',
            user_id INT DEFAULT NULL,
            user_type VARCHAR(50) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            description TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_action (action),
            INDEX idx_table (table_name),
            INDEX idx_record (record_id),
            INDEX idx_user (user_id),
            INDEX idx_created (created_at),
            INDEX idx_user_type (user_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->query($sql);
        log_message('info', 'Activity_log_model: Created activity_logs table');
    }
}
