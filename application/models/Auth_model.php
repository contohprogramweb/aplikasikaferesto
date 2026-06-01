<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth Model
 * 
 * Model untuk menangani operasi authentication user
 * Sesuai SRS v4.0 Bab 4.6 - Admin Authentication
 * 
 * Fitur:
 * - Login validation dengan BCRYPT
 * - Login attempt tracking (5x gagal = blokir 15 menit)
 * - Session management
 * - Concurrent login logging
 */
class Auth_model extends CI_Model {
    
    /**
     * Tabel database
     */
    const TABLE_USERS = 'users';
    const TABLE_LOGIN_ATTEMPTS = 'login_attempts';
    const TABLE_ACTIVITY_LOGS = 'activity_logs';
    
    /**
     * Konfigurasi security
     */
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 900; // 15 menit dalam detik
    const SESSION_TTL = 28800; // 8 jam dalam detik
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }
    
    /**
     * Get user by username
     * 
     * @param string $username
     * @return object|null User object atau null jika tidak ditemukan
     */
    public function get_user_by_username($username) {
        $query = $this->db->where('username', $username)
                          ->where('is_active', 1)
                          ->get(self::TABLE_USERS, 1);
        
        if ($query->num_rows() > 0) {
            return $query->row();
        }
        
        return null;
    }
    
    /**
     * Verify password menggunakan BCRYPT
     * 
     * @param string $password Password plain text
     * @param string $hash Password hash dari database
     * @return bool TRUE jika password cocok
     */
    public function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Record login attempt (gagal)
     * 
     * @param string $username Username yang dicoba
     * @param string $ip_address IP address user
     * @return void
     */
    public function record_login_attempt($username, $ip_address) {
        $data = [
            'username' => $username,
            'ip_address' => $ip_address,
            'attempt_time' => date('Y-m-d H:i:s'),
            'success' => 0
        ];
        
        $this->db->insert(self::TABLE_LOGIN_ATTEMPTS, $data);
        
        // Cleanup old records (lebih dari 24 jam)
        $this->db->delete(
            self::TABLE_LOGIN_ATTEMPTS, 
            ['attempt_time <' => date('Y-m-d H:i:s', strtotime('-24 hours'))]
        );
    }
    
    /**
     * Check jika IP/username diblokir karena terlalu banyak gagal login
     * 
     * @param string $username
     * @param string $ip_address
     * @return array ['blocked' => bool, 'remaining_time' => int]
     */
    public function check_lockout($username, $ip_address) {
        $cutoff_time = date('Y-m-d H:i:s', time() - self::LOCKOUT_DURATION);
        
        // Hitung failed attempts dari username atau IP dalam 15 menit terakhir
        $this->db->where('success', 0);
        $this->db->where('attempt_time >=', $cutoff_time);
        $this->db->group_start();
        $this->db->where('username', $username);
        $this->db->or_where('ip_address', $ip_address);
        $this->db->group_end();
        
        $query = $this->db->get(self::TABLE_LOGIN_ATTEMPTS);
        $failed_attempts = $query->num_rows();
        
        if ($failed_attempts >= self::MAX_LOGIN_ATTEMPTS) {
            // Hitung remaining lockout time
            $last_attempt = $query->row('attempt_time');
            $lockout_until = strtotime($last_attempt) + self::LOCKOUT_DURATION;
            $remaining_time = max(0, $lockout_until - time());
            
            return [
                'blocked' => true,
                'remaining_time' => $remaining_time
            ];
        }
        
        return [
            'blocked' => false,
            'remaining_time' => 0
        ];
    }
    
    /**
     * Clear login attempts setelah successful login
     * 
     * @param string $username
     * @param string $ip_address
     * @return void
     */
    public function clear_login_attempts($username, $ip_address) {
        $this->db->where('username', $username)
                 ->where('ip_address', $ip_address)
                 ->where('success', 0)
                 ->delete(self::TABLE_LOGIN_ATTEMPTS);
    }
    
    /**
     * Create session data untuk user yang berhasil login
     * 
     * @param object $user User object dari database
     * @param bool $remember_me Apakah user memilih remember me
     * @return array Session data
     */
    public function create_session_data($user, $remember_me = false) {
        $session_data = [
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'full_name' => $user->full_name,
            'logged_in' => true,
            'login_time' => date('Y-m-d H:i:s'),
            'session_ttl' => self::SESSION_TTL,
            'remember_me' => $remember_me
        ];
        
        // Jika remember me, set session lebih lama (7 hari)
        if ($remember_me) {
            $session_data['session_ttl'] = 604800; // 7 hari
        }
        
        return $session_data;
    }
    
    /**
     * Log concurrent login ke activity logs
     * 
     * @param int $user_id
     * @param string $username
     * @param string $ip_address
     * @param string $user_agent
     * @return void
     */
    public function log_concurrent_login($user_id, $username, $ip_address, $user_agent) {
        $log_data = [
            'action' => 'LOGIN',
            'entity_type' => 'user',
            'entity_id' => $user_id,
            'old_value' => null,
            'new_value' => 'Concurrent login successful',
            'user_id' => $user_id,
            'username' => $username,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert(self::TABLE_ACTIVITY_LOGS, $log_data);
    }
    
    /**
     * Update last login timestamp dengan security data
     * 
     * @param int $user_id
     * @param string $ip_address
     * @return void
     */
    public function update_last_login_with_security($user_id, $ip_address) {
        $this->db->where('id', $user_id)
                 ->update(self::TABLE_USERS, [
                     'last_login' => date('Y-m-d H:i:s'),
                     'last_login_ip' => $ip_address,
                     'last_activity' => date('Y-m-d H:i:s'),
                     'failed_login_attempts' => 0,
                     'locked_until' => null
                 ]);
    }
    
    /**
     * Update last login timestamp (legacy method)
     * 
     * @param int $user_id
     * @return void
     */
    public function update_last_login($user_id) {
        $this->update_last_login_with_security($user_id, '0.0.0.0');
    }
    
    /**
     * Set remember me cookie
     * 
     * @param int $user_id
     * @param string $username
     * @return string Token cookie
     */
    public function set_remember_me_cookie($user_id, $username) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $series = bin2hex(random_bytes(16));
        
        // Hash token untuk disimpan di database
        $token_hash = hash('sha256', $token);
        
        // Simpan ke database (untuk persistent login tokens table jika ada)
        // Untuk sekarang, kita simpan di cookie saja
        
        // Set cookie dengan durasi 7 hari
        $cookie_data = [
            'name'   => 'srpos_remember',
            'value'  => json_encode([
                'user_id' => $user_id,
                'username' => $username,
                'token' => $token,
                'series' => $series,
                'expires' => time() + 604800
            ]),
            'expire' => 604800,
            'secure' => config_item('cookie_secure'),
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        
        $this->input->set_cookie($cookie_data);
        
        return $token;
    }
    
    /**
     * Validate remember me cookie dan auto-login
     * 
     * @return object|null User object jika valid, null jika tidak
     */
    public function validate_remember_me() {
        $cookie = $this->input->cookie('srpos_remember', true);
        
        if (empty($cookie)) {
            return null;
        }
        
        $cookie_data = json_decode($cookie, true);
        
        if (!is_array($cookie_data)) {
            return null;
        }
        
        // Check expired
        if (isset($cookie_data['expires']) && $cookie_data['expires'] < time()) {
            $this->clear_remember_me_cookie();
            return null;
        }
        
        // Get user dari database
        $user = $this->get_user_by_username($cookie_data['username']);
        
        if (!$user || $user->id != $cookie_data['user_id']) {
            $this->clear_remember_me_cookie();
            return null;
        }
        
        return $user;
    }
    
    /**
     * Clear remember me cookie
     * 
     * @return void
     */
    public function clear_remember_me_cookie() {
        delete_cookie('srpos_remember');
    }
    
    /**
     * Get active sessions count untuk user tertentu
     * (Untuk monitoring concurrent login)
     * 
     * @param int $user_id
     * @return int
     */
    public function get_active_sessions_count($user_id) {
        // Query ke session table (jika menggunakan database session)
        $this->db->like('user_id', $user_id);
        $query = $this->db->get('ci_sessions');
        
        return $query->num_rows();
    }
}
