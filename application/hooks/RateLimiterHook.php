<?php
/**
 * Rate Limiter Hook
 * 
 * Digunakan di Base_Controller constructor atau sebagai pre_controller hook
 * untuk menerapkan rate limiting pada semua request
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class RateLimiterHook {
    
    protected $CI;
    protected $limits = [];
    
    public function __construct()
    {
        $this->CI =& get_instance();
        
        // Definisi limit untuk setiap endpoint group
        $this->limits = [
            'table_check' => ['limit' => 10, 'window' => 60, 'block' => 300],    // 10 req/menit, block 5 menit
            'session' => ['limit' => 10, 'window' => 60, 'block' => 300],
            'polling' => ['limit' => 1, 'window' => 3, 'block' => 0],            // 1 req/3 detik, no block
            'login' => ['limit' => 5, 'window' => 900, 'block' => 900],          // 5x/15 menit, block 15 menit
            'admin' => ['limit' => 60, 'window' => 60, 'block' => 300],         // 60 req/menit
        ];
    }
    
    /**
     * Pre-controller hook untuk rate limiting
     * Dipanggil sebelum controller dieksekusi
     */
    public function apply_rate_limit()
    {
        // Load library rate limiter
        $this->CI->load->library('rate_limiter');
        
        // Tentukan endpoint group berdasarkan URI atau method
        $endpoint_group = $this->_determine_endpoint_group();
        
        if ($endpoint_group) {
            $result = $this->CI->rate_limiter->check_limit($endpoint_group);
            
            if (!$result['allowed']) {
                $this->_send_429_response($result);
            }
        }
    }
    
    /**
     * Apply rate limit untuk endpoint spesifik
     * Bisa dipanggil langsung dari controller method
     * 
     * @param string $endpoint_group
     * @return bool TRUE jika allowed, FALSE jika exceeded
     */
    public function check($endpoint_group)
    {
        $this->CI->load->library('rate_limiter');
        
        $result = $this->CI->rate_limiter->check_limit($endpoint_group);
        
        if (!$result['allowed']) {
            $this->_send_429_response($result);
            return FALSE;
        }
        
        return TRUE;
    }
    
    /**
     * Determine endpoint group berdasarkan URI
     * 
     * @return string|null
     */
    private function _determine_endpoint_group()
    {
        $uri_string = $this->CI->uri->uri_string();
        $method = $this->CI->input->method();
        
        // Mapping URI ke endpoint group
        $mappings = [
            'api/table/check' => 'table_check',
            'api/table/status' => 'table_check',
            'api/session/keepalive' => 'session',
            'api/polling' => 'polling',
            'api/poll' => 'polling',
            'auth/login' => 'login',
            'auth/signin' => 'login',
            'login' => 'login',
            'admin/' => 'admin',
            'api/admin/' => 'admin',
        ];
        
        foreach ($mappings as $pattern => $group) {
            if (strpos($uri_string, $pattern) === 0) {
                return $group;
            }
        }
        
        // Default: tidak ada rate limiting untuk endpoint yang tidak terdefinisi
        return null;
    }
    
    /**
     * Send 429 Too Many Requests response
     * 
     * @param array $result Result dari check_limit()
     * @return void
     */
    private function _send_429_response($result)
    {
        // Set header Retry-After
        $this->CI->output
            ->set_status_header(429)
            ->set_content_type('application/json', 'utf-8')
            ->set_header('Retry-After: ' . $result['retry_after']);
        
        // Response JSON sesuai spesifikasi SRS
        $response = [
            'status' => 'error',
            'message' => 'Terlalu banyak permintaan. Silakan tunggu.',
            'code' => 429
        ];
        
        // Tambahkan informasi tambahan jika perlu
        if ($result['blocked']) {
            $response['blocked'] = true;
            $response['retry_after'] = $result['retry_after'];
        }
        
        $this->CI->output->set_output(json_encode($response));
        $this->CI->output->_display();
        exit;
    }
}
