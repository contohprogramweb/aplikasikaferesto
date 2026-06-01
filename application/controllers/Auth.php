<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth Controller
 * 
 * Controller untuk menangani authentication user (login, logout, forgot password)
 * Sesuai SRS v4.0 Bab 4.6 - Admin Authentication Module
 * 
 * Fitur:
 * - Login form dengan jQuery Validation
 * - BCRYPT password verification
 * - Session management (CI3 native session, database driver)
 * - Login attempt limit (5x gagal = blokir 15 menit)
 * - CSRF protection
 * - XSS filtering
 * - Remember me (7 hari)
 * - Concurrent login logging
 */
class Auth extends Base_Controller {
    
    /**
     * Load model dan dependencies
     */
    public function __construct() {
        parent::__construct();
        
        // Load model
        $this->load->model('auth_model');
        
        // Load helper tambahan
        $this->load->helper(['captcha', 'cookie']);
        
        // Redirect ke dashboard jika sudah login
        if ($this->is_authenticated() && $this->uri->segment(2) !== 'logout') {
            redirect('dashboard');
        }
    }
    
    /**
     * Tampilkan form login
     * URL: auth/login
     * Method: GET
     */
    public function login() {
        // Cek remember me cookie untuk auto-login
        $user = $this->auth_model->validate_remember_me();
        
        if ($user) {
            // Auto-login successful
            $session_data = $this->auth_model->create_session_data($user, true);
            $this->session->set_userdata($session_data);
            
            // Set session TTL
            $this->session->set_temp_userdata('session_expires', time() + 604800, 604800);
            
            redirect('dashboard');
            return;
        }
        
        // Siapkan data untuk view
        $data = [
            'page_title' => 'Login - ' . config_item('restaurant_name'),
            'csrf_token_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash()
        ];
        
        $this->render('auth/login', $data);
    }
    
    /**
     * Process login form submission
     * URL: auth/do_login
     * Method: POST
     */
    public function do_login() {
        // Check CSRF token
        if ($this->security->csrf_verify() === FALSE) {
            $this->session->set_flashdata('error', 'Token keamanan kadaluarsa. Silakan coba lagi.');
            redirect('auth/login');
            return;
        }
        
        // Get input dengan XSS filtering
        $username = $this->input->post('username', true);
        $password = $this->input->post('password', true);
        $remember_me = $this->input->post('remember_me', false);
        
        // Validasi input manual (selain form_validation untuk response lebih cepat)
        $errors = [];
        
        // Validasi username: required, min 4, pattern ^[a-zA-Z0-9_]+$
        if (empty($username)) {
            $errors[] = 'Username harus diisi';
        } elseif (strlen($username) < 4) {
            $errors[] = 'Username minimal 4 karakter';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username hanya boleh berisi huruf, angka, dan underscore';
        }
        
        // Validasi password: required, min 6, pattern huruf+angka
        if (empty($password)) {
            $errors[] = 'Password harus diisi';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter';
        } elseif (!preg_match('/(?=.*[a-zA-Z])(?=.*\d)/', $password)) {
            $errors[] = 'Password harus kombinasi huruf dan angka';
        }
        
        // Jika ada error validasi, kembalikan ke form
        if (!empty($errors)) {
            $this->session->set_flashdata('validation_errors', $errors);
            $this->session->set_flashdata('old_username', $username);
            redirect('auth/login');
            return;
        }
        
        // Get IP address dan user agent
        $ip_address = $this->input->ip_address();
        $user_agent = $this->input->user_agent();
        
        // Check lockout status (apakah IP/username diblokir)
        $lockout = $this->auth_model->check_lockout($username, $ip_address);
        
        if ($lockout['blocked']) {
            $minutes = ceil($lockout['remaining_time'] / 60);
            $this->session->set_flashdata('blocked', true);
            $this->session->set_flashdata('lockout_remaining', $lockout['remaining_time']);
            $this->session->set_flashdata('error', "Akun Anda diblokir sementara karena terlalu banyak percobaan login gagal. Silakan coba lagi dalam {$minutes} menit.");
            redirect('auth/login');
            return;
        }
        
        // Get user dari database
        $user = $this->auth_model->get_user_by_username($username);
        
        // Verify password dengan BCRYPT
        $login_success = false;
        
        if ($user && $this->auth_model->verify_password($password, $user->password)) {
            $login_success = true;
            
            // Clear failed login attempts
            $this->auth_model->clear_login_attempts($username, $ip_address);
            
            // Create session data
            $session_data = $this->auth_model->create_session_data($user, !empty($remember_me));
            $this->session->set_userdata($session_data);
            
            // Set session TTL (8 jam default, 7 hari jika remember me)
            $ttl = !empty($remember_me) ? 604800 : 28800;
            $this->session->set_temp_userdata('session_expires', time() + $ttl, $ttl);
            
            // Set remember me cookie jika dipilih
            if (!empty($remember_me)) {
                $this->auth_model->set_remember_me_cookie($user->id, $user->username);
            }
            
            // Update last login timestamp
            $this->auth_model->update_last_login($user->id);
            
            // Log concurrent login
            $this->auth_model->log_concurrent_login($user->id, $user->username, $ip_address, $user_agent);
            
            // Log successful login
            log_message('info', "User login successful: {$username} (ID: {$user->id}) from IP: {$ip_address}");
            
            // Redirect berdasarkan role
            $redirect_url = $this->input->post('redirect_url') ?: 'dashboard';
            redirect($redirect_url);
            return;
        }
        
        // Login gagal - record attempt
        $this->auth_model->record_login_attempt($username, $ip_address);
        
        // Log failed login
        log_message('warning', "Failed login attempt for username: {$username} from IP: {$ip_address}");
        
        // Set error message
        $this->session->set_flashdata('error', 'Username atau password salah');
        $this->session->set_flashdata('old_username', $username);
        
        // Hitung remaining attempts
        $lockout_check = $this->auth_model->check_lockout($username, $ip_address);
        $attempts_left = self::MAX_LOGIN_ATTEMPTS - $this->_get_failed_attempts_count($username, $ip_address);
        
        if ($attempts_left <= 0) {
            $minutes = ceil($lockout_check['remaining_time'] / 60);
            $this->session->set_flashdata('blocked', true);
            $this->session->set_flashdata('lockout_remaining', $lockout_check['remaining_time']);
            $this->session->set_flashdata('error', "Terlalu banyak percobaan login gagal. Akun diblokir selama {$minutes} menit.");
        } else {
            $this->session->set_flashdata('attempts_left', $attempts_left);
            $this->session->set_flashdata('error', "Username atau password salah. Percobaan tersisa: {$attempts_left}");
        }
        
        redirect('auth/login');
    }
    
    /**
     * Logout user
     * URL: auth/logout
     * Method: GET/POST
     */
    public function logout() {
        // Get user info sebelum destroy session untuk logging
        $user_id = $this->session->userdata('user_id');
        $username = $this->session->userdata('username');
        $ip_address = $this->input->ip_address();
        
        // Clear remember me cookie jika ada
        $this->auth_model->clear_remember_me_cookie();
        
        // Destroy session
        $this->session->sess_destroy();
        
        // Log logout activity
        if ($user_id) {
            log_message('info', "User logout: {$username} (ID: {$user_id}) from IP: {$ip_address}");
            
            // Catat di activity logs
            $this->db->insert('activity_logs', [
                'action' => 'LOGOUT',
                'entity_type' => 'user',
                'entity_id' => $user_id,
                'old_value' => null,
                'new_value' => 'User logged out',
                'user_id' => $user_id,
                'username' => $username,
                'ip_address' => $ip_address,
                'user_agent' => $this->input->user_agent(),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Redirect ke login page
        $this->session->set_flashdata('success', 'Anda telah berhasil logout');
        redirect('auth/login');
    }
    
    /**
     * Forgot password form (placeholder)
     * URL: auth/forgot_password
     * Method: GET
     */
    public function forgot_password() {
        $data = [
            'page_title' => 'Lupa Password - ' . config_item('restaurant_name'),
            'csrf_token_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash()
        ];
        
        $this->render('auth/forgot_password', $data);
    }
    
    /**
     * Process forgot password request (placeholder)
     * URL: auth/do_forgot_password
     * Method: POST
     */
    public function do_forgot_password() {
        // Placeholder untuk fitur forgot password
        // Implementasi selanjutnya dapat menambahkan:
        // - Email verification
        // - Reset token generation
        // - Password reset link via email
        
        $email = $this->input->post('email', true);
        
        if (empty($email)) {
            $this->session->set_flashdata('error', 'Email harus diisi');
            redirect('auth/forgot_password');
            return;
        }
        
        // TODO: Implementasi actual forgot password
        // - Check email di database
        // - Generate reset token
        // - Send email dengan reset link
        
        $this->session->set_flashdata('info', 'Jika email terdaftar, kami akan mengirimkan link reset password.');
        redirect('auth/forgot_password');
    }
    
    /**
     * Helper function untuk menghitung jumlah failed attempts
     * 
     * @param string $username
     * @param string $ip_address
     * @return int
     */
    private function _get_failed_attempts_count($username, $ip_address) {
        $cutoff_time = date('Y-m-d H:i:s', time() - $this->auth_model::LOCKOUT_DURATION);
        
        $this->db->where('success', 0);
        $this->db->where('attempt_time >=', $cutoff_time);
        $this->db->group_start();
        $this->db->where('username', $username);
        $this->db->or_where('ip_address', $ip_address);
        $this->db->group_end();
        
        $query = $this->db->get('login_attempts');
        return $query->num_rows();
    }
}
