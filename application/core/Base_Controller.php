<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base Controller
 * 
 * All controllers extend from this base controller
 * Provides common functionality and security measures
 */
class Base_Controller extends CI_Controller {

    protected $data = [];
    protected $user_role = null;
    protected $user_id = null;
    protected $is_logged_in = FALSE;

    public function __construct()
    {
        parent::__construct();
        
        // Load common libraries
        $this->load->library('session');
        $this->load->library('form_validation');
        $this->load->library('rate_limiter');
        $this->load->helper(['url', 'form', 'security']);
        $this->load->driver('cache');
        
        // Initialize default data
        $this->data['base_url'] = base_url();
        $this->data['site_title'] = config_item('restaurant_name');
        
        // Set timezone
        date_default_timezone_set('Asia/Jakarta');
        
        // Rate limiter configuration
        $this->limits = [
            'table_check' => ['limit' => 10, 'window' => 60, 'block' => 300],    // 10 req/menit, block 5 menit
            'session' => ['limit' => 10, 'window' => 60, 'block' => 300],
            'polling' => ['limit' => 1, 'window' => 3, 'block' => 0],            // 1 req/3 detik, no block
            'login' => ['limit' => 5, 'window' => 900, 'block' => 900],          // 5x/15 menit, block 15 menit
            'admin' => ['limit' => 60, 'window' => 60, 'block' => 300],         // 60 req/menit
        ];
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    protected function is_authenticated()
    {
        return $this->session->userdata('logged_in') === TRUE;
    }

    /**
     * Get current user ID
     * @return int|null
     */
    protected function get_user_id()
    {
        return $this->session->userdata('user_id');
    }

    /**
     * Get current user role
     * @return string|null
     */
    protected function get_user_role()
    {
        return $this->session->userdata('role');
    }

    /**
     * Require authentication, redirect to login if not authenticated
     * @param string $redirect_url
     */
    protected function require_auth($redirect_url = 'auth/login')
    {
        if (!$this->is_authenticated()) {
            redirect($redirect_url);
            exit;
        }
    }

    /**
     * Require specific role
     * @param array|string $roles
     * @param string $redirect_url
     */
    protected function require_role($roles, $redirect_url = 'dashboard')
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $current_role = $this->get_user_role();
        
        if (!in_array($current_role, $roles)) {
            show_error('Access Denied: You do not have permission to access this resource.', 403);
            exit;
        }
    }

    /**
     * Render view with data
     * @param string $view
     * @param array $data
     * @param bool $return
     * @return string|void
     */
    protected function render($view, $data = [], $return = FALSE)
    {
        $data = array_merge($this->data, $data);
        
        if ($return) {
            return $this->load->view($view, $data, TRUE);
        }
        
        $this->load->view($view, $data);
    }

    /**
     * JSON response helper
     * @param mixed $data
     * @param int $status_code
     * @return void
     */
    protected function json_response($data, $status_code = 200)
    {
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    /**
     * Success JSON response
     * @param mixed $data
     * @param string $message
     * @return void
     */
    protected function json_success($data = null, $message = 'Success')
    {
        $this->json_response([
            'success' => TRUE,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    /**
     * Error JSON response
     * @param string $message
     * @param int $status_code
     * @return void
     */
    protected function json_error($message = 'Error', $status_code = 400)
    {
        $this->json_response([
            'success' => FALSE,
            'message' => $message
        ], $status_code);
    }

    /**
     * Hash password using BCRYPT
     * @param string $password
     * @return string
     */
    protected function hash_password($password)
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * Verify password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    protected function verify_password($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate CSRF token
     * @return string
     */
    protected function generate_csrf_token()
    {
        if (config_item('csrf_protection') === TRUE) {
            return $this->security->get_csrf_token_name();
        }
        return '';
    }

    /**
     * Check rate limit untuk endpoint group tertentu
     * 
     * @param string $endpoint_group
     * @param string|null $identifier Custom identifier (optional)
     * @return bool TRUE jika allowed, FALSE jika exceeded
     */
    protected function check_rate_limit($endpoint_group, $identifier = null)
    {
        $result = $this->rate_limiter->check_limit($endpoint_group, $identifier);
        
        if (!$result['allowed']) {
            $this->_send_429_response($result);
            return FALSE;
        }
        
        return TRUE;
    }

    /**
     * Record login attempt (untuk tracking dan blocking)
     * 
     * @param string $username
     * @param bool $success
     * @return void
     */
    protected function record_login_attempt($username, $success = false)
    {
        $ip_address = $this->input->ip_address();
        $this->rate_limiter->record_login_attempt($ip_address, $username, $success);
    }

    /**
     * Send 429 Too Many Requests response
     * 
     * @param array $result Result dari check_limit()
     * @return void
     */
    protected function _send_429_response($result)
    {
        // Set header Retry-After sesuai spesifikasi
        $this->output
            ->set_status_header(429)
            ->set_content_type('application/json', 'utf-8')
            ->set_header('Retry-After: ' . $result['retry_after']);
        
        // Response JSON sesuai spesifikasi SRS
        $response = [
            'status' => 'error',
            'message' => 'Terlalu banyak permintaan. Silakan tunggu.',
            'code' => 429
        ];
        
        // Tambahkan informasi tambahan jika blocked
        if ($result['blocked']) {
            $response['blocked'] = true;
            $response['retry_after'] = $result['retry_after'];
        }
        
        $this->output->set_output(json_encode($response));
        $this->output->_display();
        exit;
    }
}
