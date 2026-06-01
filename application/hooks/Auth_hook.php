<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Authentication Hook untuk CodeIgniter 3
 * Sesuai SRS v4.0 Bab 7.3.4 - Security Middleware
 * 
 * Fungsi:
 * - Check role/authorization untuk setiap request
 * - Redirect ke 403 jika unauthorized
 * - Regenerate session ID setiap 5 menit untuk security
 */

class Auth_hook {
    
    protected $ci;
    protected $session_timeout = 300; // 5 menit dalam detik
    protected $public_routes = [
        'customer',
        'auth/login',
        'auth/forgot_password',
        'auth/reset_password',
        'api/public'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ci = &get_instance();
        
        // Load dependencies
        $this->ci->load->database();
        $this->ci->load->library('session');
        $this->ci->load->helper('url');
    }
    
    /**
     * Main hook function - dipanggil sebelum setiap controller execution
     * Sesuai dengan CI3 hooks: pre_controller
     */
    public function check_auth() {
        // Dapatkan URI saat ini
        $uri_string = $this->ci->uri->uri_string();
        $current_route = strtolower($uri_string);
        
        // Skip untuk public routes
        if ($this->_is_public_route($current_route)) {
            return TRUE;
        }
        
        // Skip untuk asset files (css, js, images)
        if ($this->_is_asset_request($current_route)) {
            return TRUE;
        }
        
        // Cek apakah user sudah login
        $user_id = $this->ci->session->userdata('user_id');
        $role = $this->ci->session->userdata('role');
        
        if (empty($user_id)) {
            // User belum login
            if ($this->_is_ajax_request()) {
                show_error('Unauthorized', 401, 'Silakan login terlebih dahulu');
            } else {
                redirect('auth/login', 'refresh');
            }
            return FALSE;
        }
        
        // Regenerate session ID setiap 5 menit untuk security
        $this->_regenerate_session_if_needed();
        
        // Update last activity
        $this->_update_last_activity();
        
        // Check role-based authorization
        if (!$this->_check_role_authorization($current_route, $role)) {
            if ($this->_is_ajax_request()) {
                show_error('Forbidden', 403, 'Anda tidak memiliki akses ke halaman ini');
            } else {
                // Set flashdata untuk error message
                $this->ci->session->set_flashdata('error', 'Anda tidak memiliki akses ke halaman ini');
                redirect('dashboard', 'refresh');
            }
            return FALSE;
        }
        
        return TRUE;
    }
    
    /**
     * Cek apakah route adalah public route (tidak perlu login)
     * 
     * @param string $route
     * @return bool
     */
    private function _is_public_route($route) {
        // Normalize route
        $route = trim($route, '/');
        
        foreach ($this->public_routes as $public_route) {
            if (strpos($route, $public_route) === 0) {
                return TRUE;
            }
        }
        
        // Check untuk empty route (homepage)
        if (empty($route) || $route === '/') {
            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
     * Cek apakah request adalah untuk asset files
     * 
     * @param string $route
     * @return bool
     */
    private function _is_asset_request($route) {
        $asset_extensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf'];
        
        foreach ($asset_extensions as $ext) {
            if (substr($route, -strlen($ext)) === $ext) {
                return TRUE;
            }
        }
        
        // Check untuk uploads folder
        if (strpos($route, 'uploads/') === 0) {
            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
     * Cek apakah request adalah AJAX
     * 
     * @return bool
     */
    private function _is_ajax_request() {
        return $this->ci->input->is_ajax_request();
    }
    
    /**
     * Regenerate session ID jika sudah lebih dari 5 menit
     */
    private function _regenerate_session_if_needed() {
        $last_regenerate = $this->ci->session->userdata('last_session_regenerate');
        $now = time();
        
        if (empty($last_regenerate) || ($now - $last_regenerate) > $this->session_timeout) {
            // Regenerate session ID
            $this->ci->session->sess_regenerate(TRUE);
            
            // Update timestamp
            $this->ci->session->set_userdata('last_session_regenerate', $now);
            
            log_message('debug', 'Session ID regenerated for user: ' . $this->ci->session->userdata('user_id'));
        }
    }
    
    /**
     * Update last activity timestamp di database
     */
    private function _update_last_activity() {
        $user_id = $this->ci->session->userdata('user_id');
        
        if (!empty($user_id)) {
            $this->ci->db->where('id', $user_id)
                         ->update('users', [
                             'last_activity' => date('Y-m-d H:i:s')
                         ]);
        }
    }
    
    /**
     * Check role-based authorization untuk route tertentu
     * 
     * @param string $route Route yang diakses
     * @param string $role Role user (admin, staff, customer)
     * @return bool
     */
    private function _check_role_authorization($route, $role) {
        // Define route patterns untuk setiap role
        $admin_routes = ['admin', 'reports', 'settings', 'users'];
        $staff_routes = ['kitchen', 'waiter', 'cashier', 'orders'];
        $customer_routes = ['customer'];
        
        // Admin bisa akses semua
        if ($role === 'admin') {
            return TRUE;
        }
        
        // Staff dapat akses staff routes + orders
        if ($role === 'staff') {
            foreach ($staff_routes as $staff_route) {
                if (strpos($route, $staff_route) === 0) {
                    return TRUE;
                }
            }
            
            // Staff juga bisa akses beberapa admin route terbatas
            if (strpos($route, 'menu') === 0) {
                return TRUE;
            }
            
            return FALSE;
        }
        
        // Customer hanya bisa akses customer routes
        if ($role === 'customer') {
            foreach ($customer_routes as $customer_route) {
                if (strpos($route, $customer_route) === 0) {
                    return TRUE;
                }
            }
            return FALSE;
        }
        
        // Default: deny
        return FALSE;
    }
    
    /**
     * Middleware khusus untuk Admin-only routes
     * Bisa dipanggil langsung dari controller
     */
    public function require_admin() {
        $role = $this->ci->session->userdata('role');
        
        if ($role !== 'admin') {
            show_error('Access Denied', 403, 'Hanya administrator yang dapat mengakses halaman ini');
        }
    }
    
    /**
     * Middleware khusus untuk Staff-only routes
     * Bisa dipanggil langsung dari controller
     */
    public function require_staff() {
        $role = $this->ci->session->userdata('role');
        
        if (!in_array($role, ['admin', 'staff'])) {
            show_error('Access Denied', 403, 'Hanya staff yang dapat mengakses halaman ini');
        }
    }
    
    /**
     * Middleware untuk check permission spesifik
     * 
     * @param string $permission Nama permission
     */
    public function require_permission($permission) {
        $user_id = $this->ci->session->userdata('user_id');
        
        if (empty($user_id)) {
            redirect('auth/login');
        }
        
        // Check permission di database (jika ada tabel permissions)
        $this->ci->db->select('p.permission_name');
        $this->ci->db->from('permissions p');
        $this->ci->db->join('role_permissions rp', 'rp.permission_id = p.id');
        $this->ci->db->join('users u', 'u.role = rp.role');
        $this->ci->db->where('u.id', $user_id);
        $this->ci->db->where('p.permission_name', $permission);
        
        $query = $this->ci->db->get();
        
        if ($query->num_rows() === 0) {
            show_error('Access Denied', 403, 'Anda tidak memiliki permission: ' . $permission);
        }
    }
}
