<?php
/**
 * Rate Limiter Library
 * 
 * Implements rate limiting based on SRS v4.0 Bab 3.4.7 dan NFR-SEC-16
 * - File-based atau database storage
 * - Sliding window algorithm
 * - Identifier: IP address untuk publik, session_id untuk auth
 * - Auto-blocking setelah melebihi limit
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Rate_limiter {
    
    protected $CI;
    protected $storage_type = 'database'; // 'database' atau 'file'
    protected $file_path;
    protected $block_cache = [];
    
    public function __construct($config = [])
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        
        // Konfigurasi storage type dari config jika ada
        $this->storage_type = isset($config['storage_type']) ? $config['storage_type'] : 'database';
        
        if ($this->storage_type === 'file') {
            $this->file_path = APPPATH . 'cache/rate_limits/';
            if (!is_dir($this->file_path)) {
                mkdir($this->file_path, 0755, TRUE);
            }
        }
        
        log_message('info', 'Rate Limiter Library Initialized');
    }
    
    /**
     * Check rate limit for an endpoint group
     * 
     * @param string $endpoint_group Nama grup endpoint (table_check, session, polling, login, admin)
     * @param string|null $identifier Identifier custom (jika null, akan auto-detect dari IP/session)
     * @return array ['allowed' => bool, 'remaining' => int, 'reset' => int, 'blocked' => bool, 'retry_after' => int]
     */
    public function check_limit($endpoint_group, $identifier = null)
    {
        // Dapatkan identifier jika tidak disediakan
        if ($identifier === null) {
            $identifier = $this->_get_identifier();
        }
        
        // Dapatkan konfigurasi limit untuk endpoint group ini
        $limits = $this->_get_limits_config();
        
        if (!isset($limits[$endpoint_group])) {
            // Jika endpoint group tidak dikenali, izinkan tanpa limit
            return ['allowed' => true, 'remaining' => -1, 'reset' => 0, 'blocked' => false, 'retry_after' => 0];
        }
        
        $config = $limits[$endpoint_group];
        $limit = $config['limit'];
        $window = $config['window']; // dalam detik
        $block_duration = isset($config['block']) ? $config['block'] : 0; // dalam detik
        
        // Cek apakah identifier sedang diblokir
        if ($block_duration > 0 && $this->_is_blocked($identifier, $endpoint_group)) {
            $retry_after = $this->_get_block_remaining_time($identifier, $endpoint_group);
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset' => time() + $retry_after,
                'blocked' => true,
                'retry_after' => $retry_after
            ];
        }
        
        // Hitung request dalam window saat ini menggunakan sliding window
        $request_count = $this->_get_request_count($identifier, $endpoint_group, $window);
        
        if ($request_count >= $limit) {
            // Melebihi limit, blokir jika block_duration > 0
            if ($block_duration > 0) {
                $this->_block_identifier($identifier, $endpoint_group, $block_duration);
                log_message('warning', "Rate limit exceeded for {$endpoint_group} - {$identifier}. Blocked for {$block_duration}s");
                
                // Catat di activity logs jika ini adalah login attempt
                if ($endpoint_group === 'login') {
                    $this->_log_activity($identifier, 'rate_limit_exceeded', $endpoint_group);
                }
            }
            
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset' => time() + $window,
                'blocked' => $block_duration > 0,
                'retry_after' => $block_duration > 0 ? $block_duration : $window
            ];
        }
        
        // Catat request ini
        $this->_record_request($identifier, $endpoint_group);
        
        return [
            'allowed' => true,
            'remaining' => $limit - $request_count - 1,
            'reset' => time() + $window,
            'blocked' => false,
            'retry_after' => 0
        ];
    }
    
    /**
     * Record a failed login attempt
     * 
     * @param string $ip_address
     * @param string $username
     * @param bool $success
     * @return void
     */
    public function record_login_attempt($ip_address, $username, $success = false)
    {
        $data = [
            'ip_address' => $ip_address,
            'username' => $username,
            'attempted_at' => date('Y-m-d H:i:s'),
            'success' => $success ? 1 : 0
        ];
        
        $this->CI->db->insert('login_attempts', $data);
        
        // Catat juga di activity logs
        $action = $success ? 'login_success' : 'login_failed';
        $this->_log_activity($ip_address, $action, 'login', $username);
        
        // Cek apakah perlu diblokir setelah 5x gagal dalam 15 menit
        if (!$success) {
            $this->_check_login_block($ip_address);
        }
    }
    
    /**
     * Get blocked IPs untuk admin panel
     * 
     * @return array List of blocked identifiers
     */
    public function get_blocked_ips($endpoint_group = null)
    {
        if ($this->storage_type === 'database') {
            $this->CI->db->where('block_expires >', now());
            
            if ($endpoint_group !== null) {
                $this->CI->db->where('endpoint_group', $endpoint_group);
            }
            
            $query = $this->CI->db->get('rate_limits');
            return $query->result_array();
        } else {
            // File-based storage
            $blocked = [];
            $files = glob($this->file_path . '*.json');
            
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                
                if ($data && isset($data['blocked_until']) && $data['blocked_until'] > time()) {
                    if ($endpoint_group === null || $data['endpoint_group'] === $endpoint_group) {
                        $blocked[] = $data;
                    }
                }
            }
            
            return $blocked;
        }
    }
    
    /**
     * Unblock an identifier manually
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @return bool
     */
    public function unblock_identifier($identifier, $endpoint_group = null)
    {
        if ($this->storage_type === 'database') {
            $this->CI->db->where('identifier', $identifier);
            
            if ($endpoint_group !== null) {
                $this->CI->db->where('endpoint_group', $endpoint_group);
            }
            
            $this->CI->db->update('rate_limits', [
                'blocked_until' => 0,
                'request_count' => 0
            ]);
            
            return TRUE;
        } else {
            // File-based storage
            $key = $this->_get_file_key($identifier, $endpoint_group);
            $file = $this->file_path . $key . '.json';
            
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file), true);
                if ($data) {
                    $data['blocked_until'] = 0;
                    $data['requests'] = [];
                    file_put_contents($file, json_encode($data));
                    return TRUE;
                }
            }
            
            return FALSE;
        }
    }
    
    /**
     * Clear expired blocks and requests (should be called periodically)
     * 
     * @return void
     */
    public function cleanup()
    {
        if ($this->storage_type === 'database') {
            // Hapus record yang sudah expired
            $this->CI->db->where('blocked_until > 0 AND blocked_until <', now());
            $this->CI->db->update('rate_limits', [
                'blocked_until' => 0,
                'request_count' => 0
            ]);
            
            // Hapus request timestamps yang sudah expired (lebih tua dari window terbesar)
            $max_window = 900; // 15 menit (window terbesar dari config)
            $this->CI->db->where('last_request <', time() - $max_window);
            $this->CI->db->delete('rate_limits');
        } else {
            // File-based cleanup
            $files = glob($this->file_path . '*.json');
            $max_window = 900;
            
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $data = json_decode($content, true);
                
                if ($data) {
                    // Filter requests yang masih dalam window
                    if (isset($data['requests'])) {
                        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($max_window) {
                            return $timestamp > (time() - $max_window);
                        });
                        $data['requests'] = array_values($data['requests']);
                    }
                    
                    // Update blocked status jika sudah expired
                    if (isset($data['blocked_until']) && $data['blocked_until'] <= time()) {
                        $data['blocked_until'] = 0;
                    }
                    
                    // Simpan kembali atau hapus jika kosong
                    if (empty($data['requests']) && (!isset($data['blocked_until']) || $data['blocked_until'] == 0)) {
                        unlink($file);
                    } else {
                        file_put_contents($file, json_encode($data));
                    }
                }
            }
        }
    }
    
    // =====================================================
    // PRIVATE METHODS
    // =====================================================
    
    /**
     * Get identifier based on authentication status
     * 
     * @return string
     */
    private function _get_identifier()
    {
        // Cek apakah user sudah authenticated (punya session)
        $session_id = $this->CI->session->userdata('session_id');
        
        if ($session_id) {
            // User authenticated, gunakan session_id
            return 'session:' . $session_id;
        }
        
        // User tidak authenticated, gunakan IP address
        return 'ip:' . $this->CI->input->ip_address();
    }
    
    /**
     * Get limits configuration
     * 
     * @return array
     */
    private function _get_limits_config()
    {
        return [
            'table_check' => ['limit' => 10, 'window' => 60, 'block' => 300],    // 10 req/menit, block 5 menit
            'session' => ['limit' => 10, 'window' => 60, 'block' => 300],
            'polling' => ['limit' => 1, 'window' => 3, 'block' => 0],            // 1 req/3 detik, no block
            'login' => ['limit' => 5, 'window' => 900, 'block' => 900],          // 5x/15 menit, block 15 menit
            'admin' => ['limit' => 60, 'window' => 60, 'block' => 300],         // 60 req/menit
        ];
    }
    
    /**
     * Get request count in the current sliding window
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @param int $window Window size in seconds
     * @return int
     */
    private function _get_request_count($identifier, $endpoint_group, $window)
    {
        $now = time();
        $window_start = $now - $window;
        
        if ($this->storage_type === 'database') {
            // Gunakan sliding window dengan menyimpan timestamps
            $this->CI->db->where('identifier', $identifier);
            $this->CI->db->where('endpoint_group', $endpoint_group);
            $this->CI->db->where('request_time >', $window_start);
            
            $query = $this->CI->db->get('rate_limit_requests');
            return $query->num_rows();
        } else {
            // File-based storage
            $key = $this->_get_file_key($identifier, $endpoint_group);
            $file = $this->file_path . $key . '.json';
            
            if (!file_exists($file)) {
                return 0;
            }
            
            $data = json_decode(file_get_contents($file), true);
            
            if (!$data || !isset($data['requests'])) {
                return 0;
            }
            
            // Filter requests dalam window
            $requests_in_window = array_filter($data['requests'], function($timestamp) use ($window_start) {
                return $timestamp > $window_start;
            });
            
            return count($requests_in_window);
        }
    }
    
    /**
     * Record a new request
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @return void
     */
    private function _record_request($identifier, $endpoint_group)
    {
        $now = time();
        
        if ($this->storage_type === 'database') {
            $data = [
                'identifier' => $identifier,
                'endpoint_group' => $endpoint_group,
                'request_time' => $now
            ];
            
            $this->CI->db->insert('rate_limit_requests', $data);
        } else {
            // File-based storage
            $key = $this->_get_file_key($identifier, $endpoint_group);
            $file = $this->file_path . $key . '.json';
            
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file), true);
            } else {
                $data = ['requests' => [], 'blocked_until' => 0, 'endpoint_group' => $endpoint_group];
            }
            
            $data['requests'][] = $now;
            file_put_contents($file, json_encode($data));
        }
    }
    
    /**
     * Check if identifier is currently blocked
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @return bool
     */
    private function _is_blocked($identifier, $endpoint_group)
    {
        if ($this->storage_type === 'database') {
            $this->CI->db->where('identifier', $identifier);
            $this->CI->db->where('endpoint_group', $endpoint_group);
            $this->CI->db->where('blocked_until >', now());
            
            $query = $this->CI->db->get('rate_limits');
            return $query->num_rows() > 0;
        } else {
            // File-based storage
            $key = $this->_get_file_key($identifier, $endpoint_group);
            $file = $this->file_path . $key . '.json';
            
            if (!file_exists($file)) {
                return false;
            }
            
            $data = json_decode(file_get_contents($file), true);
            return isset($data['blocked_until']) && $data['blocked_until'] > time();
        }
    }
    
    /**
     * Get remaining block time
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @return int Seconds remaining
     */
    private function _get_block_remaining_time($identifier, $endpoint_group)
    {
        if ($this->storage_type === 'database') {
            $this->CI->db->where('identifier', $identifier);
            $this->CI->db->where('endpoint_group', $endpoint_group);
            
            $query = $this->CI->db->get('rate_limits');
            $row = $query->row_array();
            
            if ($row && $row['blocked_until'] > 0) {
                return max(0, $row['blocked_until'] - time());
            }
            
            return 0;
        } else {
            // File-based storage
            $key = $this->_get_file_key($identifier, $endpoint_group);
            $file = $this->file_path . $key . '.json';
            
            if (!file_exists($file)) {
                return 0;
            }
            
            $data = json_decode(file_get_contents($file), true);
            
            if (isset($data['blocked_until']) && $data['blocked_until'] > time()) {
                return $data['blocked_until'] - time();
            }
            
            return 0;
        }
    }
    
    /**
     * Block an identifier
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @param int $duration Block duration in seconds
     * @return void
     */
    private function _block_identifier($identifier, $endpoint_group, $duration)
    {
        $blocked_until = time() + $duration;
        
        if ($this->storage_type === 'database') {
            // Check if record exists
            $this->CI->db->where('identifier', $identifier);
            $this->CI->db->where('endpoint_group', $endpoint_group);
            $query = $this->CI->db->get('rate_limits');
            
            if ($query->num_rows() > 0) {
                $this->CI->db->update('rate_limits', [
                    'blocked_until' => $blocked_until,
                    'request_count' => 0
                ]);
            } else {
                $this->CI->db->insert('rate_limits', [
                    'identifier' => $identifier,
                    'endpoint_group' => $endpoint_group,
                    'blocked_until' => $blocked_until,
                    'request_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } else {
            // File-based storage
            $key = $this->_get_file_key($identifier, $endpoint_group);
            $file = $this->file_path . $key . '.json';
            
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file), true);
            } else {
                $data = ['requests' => [], 'endpoint_group' => $endpoint_group];
            }
            
            $data['blocked_until'] = $blocked_until;
            $data['requests'] = []; // Reset requests
            file_put_contents($file, json_encode($data));
        }
    }
    
    /**
     * Check login attempts and block if necessary
     * 
     * @param string $ip_address
     * @return void
     */
    private function _check_login_block($ip_address)
    {
        $window_start = date('Y-m-d H:i:s', time() - 900); // 15 menit yang lalu
        
        $this->CI->db->where('ip_address', $ip_address);
        $this->CI->db->where('success', 0);
        $this->CI->db->where('attempted_at >=', $window_start);
        
        $query = $this->CI->db->get('login_attempts');
        $failed_count = $query->num_rows();
        
        if ($failed_count >= 5) {
            // Blokir IP ini selama 15 menit
            $this->_block_identifier('ip:' . $ip_address, 'login', 900);
            log_message('error', "IP {$ip_address} blocked due to 5+ failed login attempts");
        }
    }
    
    /**
     * Log activity to activity_logs table
     * 
     * @param string $identifier
     * @param string $action
     * @param string $category
     * @param string|null $username
     * @return void
     */
    private function _log_activity($identifier, $action, $category, $username = null)
    {
        // Extract IP from identifier
        $ip_address = str_replace('ip:', '', str_replace('session:', '', $identifier));
        
        $data = [
            'ip_address' => $ip_address,
            'user_id' => $username ? $username : null,
            'action' => $action,
            'category' => $category,
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => json_encode([
                'identifier' => $identifier,
                'user_agent' => $this->CI->input->user_agent()
            ])
        ];
        
        // Cek apakah tabel activity_logs ada
        if ($this->CI->db->table_exists('activity_logs')) {
            $this->CI->db->insert('activity_logs', $data);
        }
    }
    
    /**
     * Generate file key for file-based storage
     * 
     * @param string $identifier
     * @param string $endpoint_group
     * @return string
     */
    private function _get_file_key($identifier, $endpoint_group)
    {
        return md5($identifier . '_' . $endpoint_group);
    }
}
