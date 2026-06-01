<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Base_Controller.php';

/**
 * Customer Controller
 * 
 * Base controller for customer-facing operations
 * Handles QR code ordering, table sessions, etc.
 */
class Customer_Controller extends Base_Controller {

    protected $table_id = null;
    protected $session_id = null;

    public function __construct()
    {
        parent::__construct();
        
        // Load customer-specific models
        $this->load->model('table_model');
        $this->load->model('menu_model');
        $this->load->model('order_model');
        $this->load->model('customer_session_model');
        
        // Initialize customer session
        $this->init_customer_session();
    }

    /**
     * Initialize or validate customer session
     */
    protected function init_customer_session()
    {
        // Get session from URL parameter or existing session
        $session_token = $this->input->get('session') ?? $this->session->userdata('customer_session_token');
        $table_id = $this->input->get('table') ?? $this->session->userdata('table_id');
        
        if ($session_token && $table_id) {
            // Validate session
            $this->load->model('customer_session_model');
            $session_data = $this->customer_session_model->validate_session($session_token, $table_id);
            
            if ($session_data) {
                $this->session_id = $session_data->id;
                $this->table_id = $session_data->table_id;
                
                // Store in CI session
                $this->session->set_userdata([
                    'customer_session_token' => $session_token,
                    'table_id' => $table_id,
                    'is_customer' => TRUE
                ]);
            } else {
                // Invalid session, redirect to table selection
                redirect('customer/tables');
            }
        }
    }

    /**
     * Check if customer has active session
     * @return bool
     */
    protected function has_active_session()
    {
        return $this->session_id !== null && $this->table_id !== null;
    }

    /**
     * Require active customer session
     */
    protected function require_session()
    {
        if (!$this->has_active_session()) {
            redirect('customer/tables');
            exit;
        }
    }

    /**
     * Get current table ID
     * @return int|null
     */
    protected function get_table_id()
    {
        return $this->table_id;
    }

    /**
     * Get current session ID
     * @return int|null
     */
    protected function get_session_id()
    {
        return $this->session_id;
    }

    /**
     * Create new customer session for a table
     * @param int $table_id
     * @return string|false Session token or false on failure
     */
    protected function create_session($table_id)
    {
        $this->load->model('customer_session_model');
        $token = $this->customer_session_model->create_session($table_id);
        
        if ($token) {
            $this->session_id = $this->customer_session_model->get_session_by_token($token)->id;
            $this->table_id = $table_id;
            
            $this->session->set_userdata([
                'customer_session_token' => $token,
                'table_id' => $table_id,
                'is_customer' => TRUE
            ]);
        }
        
        return $token;
    }

    /**
     * End customer session
     */
    protected function end_session()
    {
        $this->load->model('customer_session_model');
        $this->customer_session_model->end_session($this->session_id);
        
        $this->session->unset_userdata([
            'customer_session_token',
            'table_id',
            'is_customer'
        ]);
        
        $this->session_id = null;
        $this->table_id = null;
    }
}
