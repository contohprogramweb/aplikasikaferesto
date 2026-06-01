<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Base_Controller.php';

/**
 * Admin Controller
 * 
 * Base controller for administrators
 * Provides access to management features, reports, and settings
 */
class Admin_Controller extends Base_Controller {

    public function __construct()
    {
        parent::__construct();
        
        // Require authentication for all admin controllers
        $this->require_auth('auth/login');
        
        // Require admin role
        $this->require_role(['admin', 'manager'], 'auth/login');
        
        // Load admin-specific models
        $this->load->model('user_model');
        $this->load->model('report_model');
        $this->load->model('settings_model');
        
        // Load admin-specific libraries
        $this->load->library('pagination');
    }

    /**
     * Check if user is super admin
     * @return bool
     */
    protected function is_super_admin()
    {
        return $this->get_user_role() === 'admin';
    }

    /**
     * Check if user is manager
     * @return bool
     */
    protected function is_manager()
    {
        return $this->get_user_role() === 'manager';
    }

    /**
     * Require super admin role
     */
    protected function require_admin()
    {
        $this->require_role('admin', 'dashboard');
    }

    /**
     * Get dashboard statistics
     * @return array
     */
    protected function get_dashboard_stats()
    {
        $this->load->model('order_model');
        $this->load->model('transaction_model');
        
        return [
            'today_orders' => $this->order_model->count_today_orders(),
            'today_revenue' => $this->transaction_model->sum_today_revenue(),
            'pending_orders' => $this->order_model->count_pending_orders(),
            'active_tables' => $this->table_model->count_occupied_tables(),
            'total_tables' => $this->table_model->count_all_tables()
        ];
    }

    /**
     * Log admin activity with higher priority
     * @param string $action
     * @param string $description
     * @param int $related_id
     * @param string $priority
     */
    protected function log_activity($action, $description, $related_id = null, $priority = 'high')
    {
        $this->load->model('activity_log_model');
        $this->activity_log_model->log(
            $this->get_user_id(),
            $action,
            $description,
            $related_id,
            $priority
        );
    }

    /**
     * Generate report
     * @param string $type
     * @param array $filters
     * @return array
     */
    protected function generate_report($type, $filters = [])
    {
        $this->load->model('report_model');
        return $this->report_model->generate($type, $filters);
    }

    /**
     * Export data to CSV
     * @param array $data
     * @param string $filename
     * @param array $headers
     */
    protected function export_csv($data, $filename, $headers = [])
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($headers)) {
            fputcsv($output, $headers);
        }
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Validate admin permissions for specific module
     * @param string $module
     * @return bool
     */
    protected function can_access_module($module)
    {
        $role = $this->get_user_role();
        
        // Super admin can access everything
        if ($role === 'admin') {
            return TRUE;
        }
        
        // Define manager permissions
        $manager_permissions = [
            'orders', 'menu', 'tables', 'reports', 'staff'
        ];
        
        if ($role === 'manager' && in_array($module, $manager_permissions)) {
            return TRUE;
        }
        
        return FALSE;
    }
}
