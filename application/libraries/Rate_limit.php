<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rate Limiting Library
 * Implements rate limiting for API endpoints and user actions
 * 
 * Usage:
 *   $this->rate_limit->check('api_endpoint', 60, 10); // 10 requests per minute
 *   $this->rate_limit->check('login_attempts', 900, 5); // 5 attempts per 15 minutes
 */
class Rate_limit {
    
    protected $CI;
    protected $cache_driver;
    protected $db;
    
    // Default limits
    protected $default_limits = [
        'api_public' => ['duration' => 60, 'max_requests' => 10],      // 10 req/min
        'api_polling' => ['duration' => 3, 'max_requests' => 1],       // 1 req/3 sec
        'api_admin' => ['duration' => 60, 'max_requests' => 60],       // 60 req/min
        'login' => ['duration' => 900, 'max_requests' => 5],           // 5 attempts/15 min
        'password_reset' => ['duration' => 3600, 'max_requests' => 3], // 3 requests/hour
        'order_create' => ['duration' => 60, 'max_requests' => 10],    // 10 orders/min
        'payment' => ['duration' => 300, 'max_requests' => 5],         // 5 payments/5 min
    ];
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->driver('cache', ['adapter' => 'file', 'backup' => 'dummy']);
        $this->cache_driver = $this->CI->cache;
        
        // Load database for persistent tracking
        $this->CI->load->database();
        $this->db = $this->CI->db;
    }
    
    /**
     * Check rate limit for a given key
     * 
     * @param string $key Unique identifier for the rate limit (e.g., IP, user_id, endpoint)
     * @param int $duration Time window in seconds
     * @param int $max_requests Maximum allowed requests in the time window
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
     */
    public function check($key, $duration = 60, $max_requests = 10) {
        $now = time();
        $cache_key = 'rate_limit_' . md5($key);
        
        // Get current data from cache
        $data = $this->cache_driver->get($cache_key);
        
        if ($data === FALSE) {
            // First request or expired
            $data = [
                'count' => 1,
                'first_request' => $now,
                'blocked_until' => 0
            ];
            $this->cache_driver->save($cache_key, $data, $duration);
            
            return [
                'allowed' => TRUE,
                'remaining' => $max_requests - 1,
                'reset_time' => $now + $duration
            ];
        }
        
        // Check if blocked
        if ($data['blocked_until'] > $now) {
            return [
                'allowed' => FALSE,
                'remaining' => 0,
                'reset_time' => $data['blocked_until'],
                'blocked' => TRUE
            ];
        }
        
        // Check if time window has passed
        if ($now - $data['first_request'] >= $duration) {
            // Reset counter
            $data = [
                'count' => 1,
                'first_request' => $now,
                'blocked_until' => 0
            ];
            $this->cache_driver->save($cache_key, $data, $duration);
            
            return [
                'allowed' => TRUE,
                'remaining' => $max_requests - 1,
                'reset_time' => $now + $duration
            ];
        }
        
        // Within time window
        if ($data['count'] >= $max_requests) {
            // Rate limit exceeded
            return [
                'allowed' => FALSE,
                'remaining' => 0,
                'reset_time' => $data['first_request'] + $duration,
                'blocked' => FALSE
            ];
        }
        
        // Increment counter
        $data['count']++;
        $this->cache_driver->save($cache_key, $data, $duration);
        
        return [
            'allowed' => TRUE,
            'remaining' => $max_requests - $data['count'],
            'reset_time' => $data['first_request'] + $duration
        ];
    }
    
    /**
     * Check rate limit using predefined profiles
     * 
     * @param string $profile Profile name (api_public, api_polling, login, etc.)
     * @param string $identifier Additional identifier (IP, user_id, etc.)
     * @return array Rate limit result
     */
    public function check_profile($profile, $identifier = '') {
        if (!isset($this->default_limits[$profile])) {
            log_message('error', 'Rate limit profile not found: ' . $profile);
            return ['allowed' => TRUE, 'remaining' => PHP_INT_MAX, 'reset_time' => 0];
        }
        
        $limit = $this->default_limits[$profile];
        $key = $profile . '_' . $identifier . '_' . $this->get_client_ip();
        
        return $this->check($key, $limit['duration'], $limit['max_requests']);
    }
    
    /**
     * Block an identifier for a specific duration
     * 
     * @param string $key Identifier to block
     * @param int $duration Block duration in seconds
     */
    public function block($key, $duration = 900) {
        $cache_key = 'rate_limit_' . md5($key);
        $data = $this->cache_driver->get($cache_key);
        
        if ($data === FALSE) {
            $data = ['count' => 0, 'first_request' => time(), 'blocked_until' => 0];
        }
        
        $data['blocked_until'] = time() + $duration;
        $this->cache_driver->save($cache_key, $data, $duration);
        
        log_message('warning', 'Rate limit block applied: ' . $key . ' for ' . $duration . ' seconds');
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    protected function get_client_ip() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === TRUE) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== FALSE) {
                        return $ip;
                    }
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Log rate limit violation to database
     * 
     * @param string $key Identifier
     * @param string $profile Profile name
     * @param array $result Rate limit result
     */
    public function log_violation($key, $profile, $result) {
        $this->db->insert('rate_limit_logs', [
            'identifier' => $key,
            'profile' => $profile,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $this->CI->input->user_agent(),
            'blocked' => isset($result['blocked']) ? $result['blocked'] : FALSE,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Clean up expired rate limit entries
     * Called by cron job
     */
    public function cleanup() {
        // Cache driver handles expiration automatically
        // Just log the cleanup action
        log_message('info', 'Rate limit cache cleanup executed');
    }
}
