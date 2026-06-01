<?php
/**
 * Rate Limiter Admin Controller
 * 
 * Panel admin untuk melihat dan manage IP yang diblokir
 * Berdasarkan SRS v4.0 Bab 3.4.7 dan NFR-SEC-16
 */

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'core/Admin_Controller.php';

class Rate_limiter_admin extends Admin_Controller {
    
    public function __construct()
    {
        parent::__construct();
        
        // Require admin role
        $this->require_role('admin');
        
        // Load rate limiter library
        $this->load->library('rate_limiter');
        $this->load->model('rate_limit_model');
    }
    
    /**
     * Dashboard - List semua IP yang diblokir
     */
    public function index()
    {
        $this->data['page_title'] = 'Blocked IPs Management';
        
        // Get filter parameter
        $endpoint_group = $this->input->get('endpoint_group');
        
        // Get blocked IPs
        $blocked_ips = $this->rate_limiter->get_blocked_ips($endpoint_group);
        
        // Format data untuk display
        $formatted_blocked = [];
        foreach ($blocked_ips as $block) {
            $remaining_time = max(0, $block['blocked_until'] - time());
            
            $formatted_blocked[] = [
                'id' => $block['id'],
                'identifier' => $block['identifier'],
                'ip_address' => str_replace(['ip:', 'session:'], '', $block['identifier']),
                'endpoint_group' => $block['endpoint_group'],
                'blocked_until' => date('Y-m-d H:i:s', $block['blocked_until']),
                'remaining_seconds' => $remaining_time,
                'remaining_formatted' => $this->_format_time($remaining_time),
                'auto_expire' => $remaining_time > 0
            ];
        }
        
        $this->data['blocked_ips'] = $formatted_blocked;
        $this->data['endpoint_groups'] = ['table_check', 'session', 'polling', 'login', 'admin'];
        $this->data['selected_group'] = $endpoint_group;
        
        $this->render('admin/rate_limiter/index', $this->data);
    }
    
    /**
     * Unblock IP manually
     */
    public function unblock($id = null)
    {
        if ($id === null) {
            $this->json_error('Invalid request', 400);
            return;
        }
        
        // Get block data
        $this->db->where('id', $id);
        $block = $this->db->get('rate_limits')->row_array();
        
        if (!$block) {
            $this->json_error('Block record not found', 404);
            return;
        }
        
        // Unblock
        $result = $this->rate_limiter->unblock_identifier($block['identifier'], $block['endpoint_group']);
        
        if ($result) {
            // Log activity
            $this->db->insert('activity_logs', [
                'ip_address' => $this->input->ip_address(),
                'user_id' => $this->get_user_id(),
                'action' => 'ip_unblocked',
                'category' => 'rate_limit',
                'timestamp' => date('Y-m-d H:i:s'),
                'details' => json_encode([
                    'blocked_id' => $id,
                    'identifier' => $block['identifier'],
                    'endpoint_group' => $block['endpoint_group']
                ])
            ]);
            
            $this->json_success(null, 'IP berhasil di-unblock');
        } else {
            $this->json_error('Failed to unblock IP', 500);
        }
    }
    
    /**
     * Unblock by identifier
     */
    public function unblock_by_identifier()
    {
        $identifier = $this->input->post('identifier');
        $endpoint_group = $this->input->post('endpoint_group');
        
        if (empty($identifier)) {
            $this->json_error('Identifier required', 400);
            return;
        }
        
        $result = $this->rate_limiter->unblock_identifier($identifier, $endpoint_group);
        
        if ($result) {
            $this->json_success(null, 'IP berhasil di-unblock');
        } else {
            $this->json_error('Failed to unblock IP atau identifier tidak ditemukan', 404);
        }
    }
    
    /**
     * View detail block
     */
    public function detail($id = null)
    {
        if ($id === null) {
            show_404();
            return;
        }
        
        $this->db->where('id', $id);
        $block = $this->db->get('rate_limits')->row_array();
        
        if (!$block) {
            show_404();
            return;
        }
        
        // Get request history
        $this->db->where('identifier', $block['identifier']);
        $this->db->where('endpoint_group', $block['endpoint_group']);
        $this->db->order_by('request_time', 'DESC');
        $this->db->limit(100);
        $requests = $this->db->get('rate_limit_requests')->result_array();
        
        $this->data['block'] = $block;
        $this->data['requests'] = $requests;
        $this->data['page_title'] = 'Block Detail - ' . $block['identifier'];
        
        $this->render('admin/rate_limiter/detail', $this->data);
    }
    
    /**
     * Cleanup expired blocks manually
     */
    public function cleanup()
    {
        $this->rate_limiter->cleanup();
        
        $this->json_success(null, 'Cleanup berhasil dilakukan');
    }
    
    /**
     * Statistics dashboard
     */
    public function stats()
    {
        // Get statistics
        $now = time();
        $last_hour = $now - 3600;
        $last_day = $now - 86400;
        
        // Total blocks
        $this->db->where('blocked_until >', 0);
        $total_blocked = $this->db->count_all_results('rate_limits');
        
        // Currently blocked
        $this->db->where('blocked_until >', $now);
        $currently_blocked = $this->db->count_all_results('rate_limits');
        
        // Login attempts (last 24 hours)
        $this->db->where('attempted_at >=', date('Y-m-d H:i:s', $last_day));
        $total_login_attempts = $this->db->count_all_results('login_attempts');
        
        $this->db->where('attempted_at >=', date('Y-m-d H:i:s', $last_day));
        $this->db->where('success', 0);
        $failed_login_attempts = $this->db->count_all_results('login_attempts');
        
        // Rate limit exceeded events (last 24 hours)
        $this->db->where('action', 'rate_limit_exceeded');
        $this->db->where('timestamp >=', date('Y-m-d H:i:s', $last_day));
        $rate_limit_events = $this->db->count_all_results('activity_logs');
        
        $stats = [
            'total_blocked' => $total_blocked,
            'currently_blocked' => $currently_blocked,
            'total_login_attempts_24h' => $total_login_attempts,
            'failed_login_attempts_24h' => $failed_login_attempts,
            'rate_limit_events_24h' => $rate_limit_events,
            'auto_expire_count' => $currently_blocked // Semua block auto-expire
        ];
        
        $this->json_success($stats);
    }
    
    /**
     * Format seconds to human readable
     */
    private function _format_time($seconds)
    {
        if ($seconds <= 0) {
            return 'Expired';
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' jam';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' menit';
        }
        if ($secs > 0 || empty($parts)) {
            $parts[] = $secs . ' detik';
        }
        
        return implode(' ', $parts);
    }
}
