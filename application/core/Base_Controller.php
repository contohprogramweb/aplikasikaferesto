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
        $this->load->helper(['url', 'form', 'security']);
        $this->load->driver('cache');
        
        // Initialize default data
        $this->data['base_url'] = base_url();
        $this->data['site_title'] = config_item('restaurant_name');
        
        // Set timezone
        date_default_timezone_set('Asia/Jakarta');
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
}
