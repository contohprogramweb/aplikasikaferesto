<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Security Helper
 * Fungsi tambahan untuk keamanan output dan input
 */

/**
 * Escape output untuk mencegah XSS
 * Wrapper untuk htmlspecialchars dengan setting aman
 * 
 * @param string $str
 * @param string $context (html, js, css, url, attr)
 * @return string
 */
if (!function_exists('esc')) {
    function esc($str, $context = 'html') {
        if (empty($str)) return '';
        
        // Jika sudah array, recursive
        if (is_array($str)) {
            return array_map(function($val) use ($context) {
                return esc($val, $context);
            }, $str);
        }

        $encoding = 'UTF-8';
        
        switch ($context) {
            case 'js':
                // Simple JS escaping, untuk kompleks gunakan library khusus
                return json_encode($str, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
            
            case 'attr':
                return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, $encoding);
                
            case 'url':
                return rawurlencode($str);
                
            case 'html':
            default:
                return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, $encoding);
        }
    }
}

/**
 * Sanitize filename untuk upload
 * Menghapus karakter berbahaya dan randomize nama
 * 
 * @param string $filename
 * @return string
 */
if (!function_exists('secure_filename')) {
    function secure_filename($filename) {
        // Ambil ekstensi asli
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Whitelist ekstensi aman
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        if (!in_array($ext, $allowed)) {
            $ext = 'bin'; // Fallback aman
        }
        
        // Generate random name
        $random_name = bin2hex(random_bytes(16));
        
        return $random_name . '.' . $ext;
    }
}

/**
 * Get User IP Address (handle proxy/cloudflare)
 * @return string
 */
if (!function_exists('get_client_ip')) {
    function get_client_ip() {
        $ip = '';
        $ci = get_instance();
        
        if ($ci->input->server('HTTP_CF_CONNECTING_IP')) {
            $ip = $ci->input->server('HTTP_CF_CONNECTING_IP');
        } elseif ($ci->input->server('HTTP_X_FORWARDED_FOR')) {
            $ip = $ci->input->server('HTTP_X_FORWARDED_FOR');
        } elseif ($ci->input->server('HTTP_CLIENT_IP')) {
            $ip = $ci->input->server('HTTP_CLIENT_IP');
        } else {
            $ip = $ci->input->server('REMOTE_ADDR');
        }
        
        // Validasi format IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        return '0.0.0.0';
    }
}

/**
 * Log Audit Trail
 * Menyimpan aksi kritis ke database
 * 
 * @param string $action
 * @param string $table
 * @param int $record_id
 * @param array $old_value
 * @param array $new_value
 * @return bool
 */
if (!function_exists('audit_log')) {
    function audit_log($action, $table, $record_id, $old_value = null, $new_value = null) {
        $ci = get_instance();
        $ci->load->database();
        
        $data = [
            'user_id' => $ci->session->userdata('user_id') ?? 0,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $record_id,
            'old_value' => $old_value ? json_encode($old_value) : null,
            'new_value' => $new_value ? json_encode($new_value) : null,
            'ip_address' => get_client_ip(),
            'user_agent' => $ci->input->user_agent(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $ci->db->insert('audit_logs', $data);
    }
}
