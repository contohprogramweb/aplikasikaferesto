<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Custom Exceptions Handler
 * Sesuai SRS v4.0 NFR-REL-09 (Error Handling & Logging)
 * 
 * Fitur:
 * - Catch uncaught exceptions
 * - Log ke file (application/logs/)
 * - Tampilkan pesan generic ke user (HTTP 500)
 * - Development mode menampilkan detail error
 */
class MY_Exceptions extends CI_Exceptions {

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Override exception handler untuk logging dan custom error page
     * 
     * @param Throwable $exception
     * @return void
     */
    public function show_exception($exception)
    {
        // Log exception ke file
        $this->_log_exception($exception);

        // Dapatkan HTTP status code
        $status_code = $this->_get_status_code($exception);

        // Di production, tampilkan pesan generic
        if (ENVIRONMENT !== 'development') {
            $this->_show_generic_error($status_code);
            return;
        }

        // Di development, tampilkan detail error
        parent::show_exception($exception);
    }

    /**
     * Log exception ke file
     * 
     * @param Throwable $exception
     * @return void
     */
    protected function _log_exception($exception)
    {
        $CI =& get_instance();
        $CI->load->helper('file');
        
        $log_message = [
            'timestamp' => date('Y-m-d H:i:s'),
            'severity' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => current_url(),
            'ip_address' => $CI->input->ip_address(),
            'user_agent' => $CI->input->user_agent(),
        ];

        // Format log message
        $log_entry = "\n" . str_repeat('-', 80) . "\n";
        $log_entry .= "EXCEPTION LOG - " . $log_message['timestamp'] . "\n";
        $log_entry .= str_repeat('-', 80) . "\n";
        $log_entry .= "Severity: " . $log_message['severity'] . "\n";
        $log_entry .= "Message: " . $log_message['message'] . "\n";
        $log_entry .= "File: " . $log_message['file'] . " (Line: " . $log_message['line'] . ")\n";
        $log_entry .= "URL: " . $log_message['url'] . "\n";
        $log_entry .= "IP: " . $log_message['ip_address'] . "\n";
        $log_entry .= "User Agent: " . $log_message['user_agent'] . "\n";
        $log_entry .= "\nStack Trace:\n" . $log_message['trace'] . "\n";
        $log_entry .= str_repeat('-', 80) . "\n";

        // Tulis ke file log
        $log_path = config_item('log_path') ?: APPPATH . 'logs/';
        $log_file = $log_path . 'exception_' . date('Y-m-d') . '.php';
        
        // Pastikan direktori logs ada
        if (!is_dir($log_path)) {
            mkdir($log_path, 0755, TRUE);
        }

        // Append ke file log
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Dapatkan HTTP status code dari exception
     * 
     * @param Throwable $exception
     * @return int
     */
    protected function _get_status_code($exception)
    {
        // Check jika exception punya method getStatusCode
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        // Map exception type ke status code
        $class = get_class($exception);
        
        switch ($class) {
            case 'Show_404':
                return 404;
            case 'Show_Error':
                // Default 500 untuk Show_Error
                return 500;
            default:
                return 500;
        }
    }

    /**
     * Tampilkan halaman error generic
     * 
     * @param int $status_code
     * @return void
     */
    protected function _show_generic_error($status_code)
    {
        $CI =& get_instance();
        
        // Cek maintenance mode
        if (config_item('maintenance_mode') === TRUE) {
            // Admin bisa bypass dengan key
            $bypass_key = $CI->input->get('maintenance_bypass');
            $valid_bypass = config_item('maintenance_bypass_key');
            
            if ($bypass_key !== $valid_bypass || !$valid_bypass) {
                // Tampilkan maintenance page
                $this->_show_maintenance_page();
                return;
            }
        }

        // Load view sesuai status code
        $view_map = [
            403 => 'errors/html/error_403',
            404 => 'errors/html/error_404',
            500 => 'errors/html/error_500',
            503 => 'errors/html/error_503',
        ];

        $view = isset($view_map[$status_code]) ? $view_map[$status_code] : 'errors/html/error_500';

        // Set HTTP status header
        set_status_header($status_code);

        // Render error page
        try {
            $CI->load->view($view, ['exception' => null]);
        } catch (Exception $e) {
            // Fallback jika view tidak ditemukan
            echo "<h1>Error {$status_code}</h1>";
            echo "<p>Terjadi kesalahan pada sistem.</p>";
        }
    }

    /**
     * Tampilkan maintenance page
     * 
     * @return void
     */
    protected function _show_maintenance_page()
    {
        $CI =& get_instance();
        
        set_status_header(503);
        
        // Coba load maintenance view, fallback ke simple HTML
        try {
            $CI->load->view('errors/html/error_503', ['maintenance' => TRUE]);
        } catch (Exception $e) {
            echo '<!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Maintenance Mode</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background: linear-gradient(135deg, #f5af19 0%, #f12711 100%);
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0;
                    }
                    .container {
                        text-align: center;
                        background: white;
                        padding: 60px 40px;
                        border-radius: 20px;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                        max-width: 500px;
                    }
                    h1 { color: #f12711; font-size: 48px; margin-bottom: 20px; }
                    p { color: #666; font-size: 18px; line-height: 1.6; }
                    button {
                        background: linear-gradient(135deg, #f5af19 0%, #f12711 100%);
                        color: white;
                        border: none;
                        padding: 15px 40px;
                        border-radius: 50px;
                        font-size: 16px;
                        cursor: pointer;
                        margin-top: 20px;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>🔧 Maintenance</h1>
                    <p>Sedang dalam perawatan.<br>Silakan coba lagi beberapa saat lagi.</p>
                    <button onclick="location.reload()">Coba Lagi</button>
                </div>
            </body>
            </html>';
        }
    }
}
