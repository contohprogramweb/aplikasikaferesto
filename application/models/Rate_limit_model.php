<?php
/**
 * Rate Limit Model
 * 
 * Model untuk operasi database terkait rate limiting
 * Berdasarkan SRS v4.0 Bab 3.4.7 dan NFR-SEC-16
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Rate_limit_model extends CI_Model {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get all blocked identifiers
     * 
     * @param string|null $endpoint_group Filter by endpoint group
     * @return array
     */
    public function get_blocked($endpoint_group = null)
    {
        $now = time();
        $this->db->where('blocked_until >', $now);
        
        if ($endpoint_group !== null) {
            $this->db->where('endpoint_group', $endpoint_group);
        }
        
        $query = $this->db->get('rate_limits');
        return $query->result_array();
    }
    
    /**
     * Get block by identifier
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @return array|null
     */
    public function get_block($identifier, $endpoint_group)
    {
        $this->db->where('identifier', $identifier);
        $this->db->where('endpoint_group', $endpoint_group);
        
        $query = $this->db->get('rate_limits');
        return $query->row_array();
    }
    
    /**
     * Unblock identifier
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @return bool
     */
    public function unblock($identifier, $endpoint_group)
    {
        $this->db->where('identifier', $identifier);
        $this->db->where('endpoint_group', $endpoint_group);
        
        return $this->db->update('rate_limits', [
            'blocked_until' => 0,
            'request_count' => 0,
            'updated_at' => time()
        ]);
    }
    
    /**
     * Record a request for sliding window
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @return void
     */
    public function record_request($identifier, $endpoint_group)
    {
        $this->db->insert('rate_limit_requests', [
            'identifier' => $identifier,
            'endpoint_group' => $endpoint_group,
            'request_time' => time()
        ]);
    }
    
    /**
     * Get request count in window
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @param int $window_seconds Window size in seconds
     * @return int
     */
    public function get_request_count($identifier, $endpoint_group, $window_seconds)
    {
        $window_start = time() - $window_seconds;
        
        $this->db->where('identifier', $identifier);
        $this->db->where('endpoint_group', $endpoint_group);
        $this->db->where('request_time >', $window_start);
        
        $query = $this->db->get('rate_limit_requests');
        return $query->num_rows();
    }
    
    /**
     * Block an identifier
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @param int $duration_seconds Block duration in seconds
     * @return void
     */
    public function block($identifier, $endpoint_group, $duration_seconds)
    {
        $blocked_until = time() + $duration_seconds;
        
        $this->db->where('identifier', $identifier);
        $this->db->where('endpoint_group', $endpoint_group);
        
        $exists = $this->db->count_all_results('rate_limits') > 0;
        
        if ($exists) {
            $this->db->update('rate_limits', [
                'blocked_until' => $blocked_until,
                'request_count' => 0,
                'updated_at' => time()
            ]);
        } else {
            $this->db->insert('rate_limits', [
                'identifier' => $identifier,
                'endpoint_group' => $endpoint_group,
                'blocked_until' => $blocked_until,
                'request_count' => 0,
                'created_at' => time(),
                'updated_at' => time()
            ]);
        }
    }
    
    /**
     * Cleanup expired records
     * 
     * @return array Stats about cleanup
     */
    public function cleanup()
    {
        $now = time();
        $max_window = 900; // 15 menit
        
        // Delete old requests
        $this->db->where('request_time <', $now - $max_window);
        $this->db->delete('rate_limit_requests');
        $deleted_requests = $this->db->affected_rows();
        
        // Reset expired blocks
        $this->db->where('blocked_until > 0 AND blocked_until <', $now);
        $this->db->update('rate_limits', [
            'blocked_until' => 0,
            'request_count' => 0,
            'updated_at' => $now
        ]);
        $reset_blocks = $this->db->affected_rows();
        
        return [
            'deleted_requests' => $deleted_requests,
            'reset_blocks' => $reset_blocks
        ];
    }
    
    /**
     * Get login attempts statistics
     * 
     * @param string $ip_address
     * @param int $window_seconds Window in seconds (default 900 = 15 minutes)
     * @return array
     */
    public function get_login_attempts($ip_address, $window_seconds = 900)
    {
        $window_start = date('Y-m-d H:i:s', time() - $window_seconds);
        
        $this->db->where('ip_address', $ip_address);
        $this->db->where('attempted_at >=', $window_start);
        
        $query = $this->db->get('login_attempts');
        $attempts = $query->result_array();
        
        $total = count($attempts);
        $failed = 0;
        
        foreach ($attempts as $attempt) {
            if ($attempt['success'] == 0) {
                $failed++;
            }
        }
        
        return [
            'total' => $total,
            'failed' => $failed,
            'success' => $total - $failed,
            'attempts' => $attempts
        ];
    }
    
    /**
     * Record login attempt
     * 
     * @param string $ip_address
     * @param string $username
     * @param bool $success
     * @return void
     */
    public function record_login_attempt($ip_address, $username, $success = false)
    {
        $this->db->insert('login_attempts', [
            'ip_address' => $ip_address,
            'username' => $username,
            'attempted_at' => date('Y-m-d H:i:s'),
            'success' => $success ? 1 : 0
        ]);
    }
    
    /**
     * Get rate limit statistics
     * 
     * @return array
     */
    public function get_statistics()
    {
        $now = time();
        $last_day = date('Y-m-d H:i:s', $now - 86400);
        
        // Currently blocked
        $this->db->where('blocked_until >', $now);
        $currently_blocked = $this->db->count_all_results('rate_limits');
        
        // Login attempts last 24h
        $this->db->where('attempted_at >=', $last_day);
        $total_login_attempts = $this->db->count_all_results('login_attempts');
        
        $this->db->where('attempted_at >=', $last_day);
        $this->db->where('success', 0);
        $failed_login_attempts = $this->db->count_all_results('login_attempts');
        
        return [
            'currently_blocked' => $currently_blocked,
            'total_login_attempts_24h' => $total_login_attempts,
            'failed_login_attempts_24h' => $failed_login_attempts
        ];
    }
}
