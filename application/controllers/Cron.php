<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cron Controller
 * Handles scheduled cron jobs for system maintenance
 * Based on SRS v4.0 - Session Cleanup Requirements
 * 
 * Usage:
 *   CLI: php index.php cron cleanup_sessions
 *   Web: /cron/cleanup_sessions?key=YOUR_CRON_KEY
 * 
 * Cron setup (run every hour):
 *   0 * * * * cd /path/to/project && php index.php cron cleanup_sessions
 */
class Cron extends CI_Controller {

    private $cron_key;

    public function __construct()
    {
        parent::__construct();
        
        // Load required models
        $this->load->model('Customer_session_model');
        
        // Get cron key from config or use default
        $this->cron_key = $this->config->item('cron_key') ?: 'default_cron_key_change_me';
    }

    /**
     * Verify cron request authorization
     * Allows CLI execution or web with valid key
     */
    private function verify_authorization()
    {
        // Allow CLI execution
        if ($this->input->is_cli_request()) {
            return true;
        }

        // Check for cron key in GET or POST
        $key = $this->input->get('key') ?: $this->input->post('key');
        
        if ($key !== $this->cron_key) {
            show_error('Unauthorized cron access', 403);
            return false;
        }

        return true;
    }

    /**
     * Cleanup expired sessions
     * DELETE FROM customer_sessions WHERE expires_at < NOW()
     * 
     * Endpoint: /cron/cleanup_sessions
     * Schedule: Every hour (0 * * * *)
     */
    public function cleanup_sessions()
    {
        // Verify authorization
        if (!$this->verify_authorization()) {
            return;
        }

        // Set output for CLI or web
        if ($this->input->is_cli_request()) {
            echo "Starting session cleanup...\n";
        } else {
            $this->output->set_content_type('application/json');
        }

        // Perform cleanup
        $deleted_count = $this->Customer_session_model->delete_expired();

        // Log the cleanup activity
        $this->load->model('Activity_log_model');
        if (method_exists($this->Activity_log_model, 'create')) {
            $this->Activity_log_model->create([
                'action' => 'session_cleanup',
                'description' => 'Cron job cleaned up ' . $deleted_count . ' expired sessions',
                'related_table' => 'customer_sessions',
                'related_id' => null,
                'ip_address' => $this->input->ip_address(),
                'user_agent' => 'cron_job',
                'priority' => 'low'
            ]);
        }

        // Output result
        if ($this->input->is_cli_request()) {
            echo "Session cleanup completed.\n";
            echo "Deleted {$deleted_count} expired session(s).\n";
        } else {
            echo json_encode([
                'status' => 'success',
                'message' => 'Session cleanup completed',
                'data' => [
                    'deleted_count' => $deleted_count,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }
    }

    /**
     * Manual session cleanup with extended options
     * Can clean up sessions older than specified hours
     * 
     * Endpoint: /cron/cleanup_sessions_manual?hours=24
     */
    public function cleanup_sessions_manual()
    {
        // Verify authorization
        if (!$this->verify_authorization()) {
            return;
        }

        $hours = (int) $this->input->get('hours') ?: 24;
        $cutoff_time = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

        if ($this->input->is_cli_request()) {
            echo "Starting manual session cleanup (older than {$hours} hours)...\n";
        } else {
            $this->output->set_content_type('application/json');
        }

        // Delete sessions older than cutoff
        $this->db->where('expires_at <', $cutoff_time);
        $this->db->delete('customer_sessions');
        $deleted_count = $this->db->affected_rows();

        // Log the cleanup activity
        $this->load->model('Activity_log_model');
        if (method_exists($this->Activity_log_model, 'create')) {
            $this->Activity_log_model->create([
                'action' => 'session_cleanup_manual',
                'description' => 'Manual cleanup of ' . $deleted_count . ' sessions older than ' . $hours . ' hours',
                'related_table' => 'customer_sessions',
                'related_id' => null,
                'ip_address' => $this->input->ip_address(),
                'user_agent' => 'cron_job_manual',
                'priority' => 'medium'
            ]);
        }

        if ($this->input->is_cli_request()) {
            echo "Manual session cleanup completed.\n";
            echo "Deleted {$deleted_count} session(s) older than {$hours} hours.\n";
        } else {
            echo json_encode([
                'status' => 'success',
                'message' => 'Manual session cleanup completed',
                'data' => [
                    'deleted_count' => $deleted_count,
                    'cutoff_time' => $cutoff_time,
                    'hours' => $hours,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
        }
    }

    /**
     * Health check endpoint for monitoring
     * 
     * Endpoint: /cron/health
     */
    public function health()
    {
        if (!$this->verify_authorization()) {
            return;
        }

        $data = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'system' => [
                'php_version' => phpversion(),
                'codeigniter_version' => CI_VERSION,
                'server_time' => time()
            ],
            'database' => [
                'connected' => $this->db->conn_id ? true : false,
                'active_sessions' => $this->db->where('expires_at >', date('Y-m-d H:i:s'))
                                        ->count_all_results('customer_sessions')
            ]
        ];

        if ($this->input->is_cli_request()) {
            echo "System Health Check\n";
            echo "==================\n";
            echo "Status: {$data['status']}\n";
            echo "Timestamp: {$data['timestamp']}\n";
            echo "PHP Version: {$data['system']['php_version']}\n";
            echo "Active Sessions: {$data['database']['active_sessions']}\n";
        } else {
            $this->output->set_content_type('application/json');
            echo json_encode($data);
        }
    }
}
