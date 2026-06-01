<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Base_Controller.php';

/**
 * Staff Controller
 * 
 * Base controller for staff members (waiters, cashiers, kitchen staff)
 */
class Staff_Controller extends Base_Controller {

    public function __construct()
    {
        parent::__construct();
        
        // Require authentication for all staff controllers
        $this->require_auth('auth/login');
        
        // Require staff role
        $this->require_role(['staff', 'waiter', 'cashier', 'kitchen'], 'auth/login');
        
        // Load staff-specific models if needed
        $this->load->model('order_model');
        $this->load->model('table_model');
        $this->load->model('menu_model');
    }

    /**
     * Check if user is a waiter
     * @return bool
     */
    protected function is_waiter()
    {
        return $this->get_user_role() === 'waiter';
    }

    /**
     * Check if user is a cashier
     * @return bool
     */
    protected function is_cashier()
    {
        return $this->get_user_role() === 'cashier';
    }

    /**
     * Check if user is kitchen staff
     * @return bool
     */
    protected function is_kitchen()
    {
        return $this->get_user_role() === 'kitchen';
    }

    /**
     * Log staff activity
     * @param string $action
     * @param string $description
     * @param int $related_id
     */
    protected function log_activity($action, $description, $related_id = null)
    {
        $this->load->model('activity_log_model');
        $this->activity_log_model->log(
            $this->get_user_id(),
            $action,
            $description,
            $related_id
        );
    }
}
