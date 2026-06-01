<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * QR Code Library Wrapper untuk CodeIgniter 3
 * Sesuai SRS v4.0 Bab 7.3.3 - QR Code Generation
 * 
 * Requires: endroid/qr-code ^4.0 (via Composer)
 * Alternative: phpqrcode/phpqrcode (lightweight, no composer)
 */

class Qrcode_lib {
    
    protected $ci;
    protected $qr_path;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ci = &get_instance();
        
        // Path untuk menyimpan QR code
        $this->qr_path = FCPATH . 'uploads/qr/';
        
        // Buat direktori jika belum ada
        if (!file_exists($this->qr_path)) {
            mkdir($this->qr_path, 0755, true);
        }
        
        $this->ci->load->config('app');
    }
    
    /**
     * Generate QR Code untuk meja
     * 
     * @param string $table_code Kode meja (misal: "TBL-001")
     * @param string $base_url Base URL aplikasi
     * @param int $size Ukuran QR code (default: 200px)
     * @return string|FALSE Path file relatif atau FALSE jika gagal
     */
    public function generate_table_qr($table_code, $base_url, $size = 200) {
        // Generate URL lengkap untuk QR code
        // Format: https://domain.com/customer/order?table=TBL-001&token=xxx
        $customer_token = generate_customer_token($this->_get_table_id_by_code($table_code));
        $qr_url = rtrim($base_url, '/') . '/customer/order?table=' . urlencode($table_code) . '&token=' . $customer_token;
        
        // Nama file: [table_code].png
        $filename = strtoupper($table_code) . '.png';
        $filepath = $this->qr_path . $filename;
        
        try {
            // Cek apakah library Endroid tersedia (via Composer)
            if (class_exists('\Endroid\QrCode\QrCode')) {
                return $this->_generate_with_endroid($qr_url, $filepath, $size);
            }
            
            // Fallback ke phpqrcode jika Endroid tidak tersedia
            if (file_exists(APPPATH . 'vendor/phpqrcode/phpqrcode/qrlib.php')) {
                return $this->_generate_with_phpqrcode($qr_url, $filepath, $size);
            }
            
            // Jika tidak ada library, gunakan API Google Charts (fallback terakhir)
            return $this->_generate_with_google_api($qr_url, $filepath, $size);
            
        } catch (Exception $e) {
            log_message('error', 'QR Code generation failed: ' . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Generate QR Code dengan Endroid QR Code library
     * 
     * @param string $data Data untuk QR code
     * @param string $filepath Path file output
     * @param int $size Ukuran
     * @return string Path file relatif
     */
    private function _generate_with_endroid($data, $filepath, $size) {
        use Endroid\QrCode\QrCode;
        use Endroid\QrCode\Writer\PngWriter;
        use Endroid\QrCode\Label\Label;
        use Endroid\QrCode\Encoding\Encoding;
        use Endroid\QrCode\ErrorCorrectionLevel;
        
        $qrCode = QrCode::create($data)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize($size)
            ->setMargin(10);
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        // Simpan file
        $result->saveToFile($filepath);
        
        // Return path relatif
        return 'uploads/qr/' . basename($filepath);
    }
    
    /**
     * Generate QR Code dengan phpqrcode library
     * 
     * @param string $data Data untuk QR code
     * @param string $filepath Path file output
     * @param int $size Ukuran
     * @return string Path file relatif
     */
    private function _generate_with_phpqrcode($data, $filepath, $size) {
        require_once APPPATH . 'vendor/phpqrcode/phpqrcode/qrlib.php';
        
        // phpqrcode menggunakan ukuran pixel, bukan bounding box
        // Error correction level: L, M, Q, H (H = tertinggi)
        QRcode::png($data, $filepath, QR_ECLEVEL_H, 10, 2);
        
        // Resize jika diperlukan
        if (file_exists($filepath)) {
            $this->_resize_image($filepath, $size, $size);
        }
        
        return 'uploads/qr/' . basename($filepath);
    }
    
    /**
     * Generate QR Code dengan Google Charts API (fallback)
     * 
     * @param string $data Data untuk QR code
     * @param string $filepath Path file output
     * @param int $size Ukuran
     * @return string Path file relatif
     */
    private function _generate_with_google_api($data, $filepath, $size) {
        // Google Charts API URL
        $api_url = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . 
                   '&cht=qr&chl=' . urlencode($data) . '&choe=UTF-8';
        
        // Download image dari API
        $image_data = file_get_contents($api_url);
        
        if ($image_data === FALSE) {
            throw new Exception('Failed to fetch QR code from Google API');
        }
        
        // Simpan file
        file_put_contents($filepath, $image_data);
        
        return 'uploads/qr/' . basename($filepath);
    }
    
    /**
     * Resize image ke ukuran tertentu
     * 
     * @param string $source_path Path file sumber
     * @param int $width Lebar target
     * @param int $height Tinggi target
     */
    private function _resize_image($source_path, $width, $height) {
        if (!extension_loaded('gd')) {
            return;
        }
        
        $image_info = getimagesize($source_path);
        if ($image_info === FALSE) {
            return;
        }
        
        $original_width = $image_info[0];
        $original_height = $image_info[1];
        
        // Tidak perlu resize jika sudah sesuai
        if ($original_width == $width && $original_height == $height) {
            return;
        }
        
        // Buat image resource baru
        $new_image = imagecreatetruecolor($width, $height);
        
        // Berdasarkan tipe image
        switch ($image_info[2]) {
            case IMAGETYPE_PNG:
                $source_image = imagecreatefrompng($source_path);
                imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $width, $height, $original_width, $original_height);
                imagepng($new_image, $source_path, 9);
                break;
            case IMAGETYPE_JPEG:
                $source_image = imagecreatefromjpeg($source_path);
                imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $width, $height, $original_width, $original_height);
                imagejpeg($new_image, $source_path, 90);
                break;
            case IMAGETYPE_GIF:
                $source_image = imagecreatefromgif($source_path);
                imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $width, $height, $original_width, $original_height);
                imagegif($new_image, $source_path);
                break;
        }
        
        imagedestroy($new_image);
        if (isset($source_image)) {
            imagedestroy($source_image);
        }
    }
    
    /**
     * Get table ID dari table code
     * 
     * @param string $table_code
     * @return int|null
     */
    private function _get_table_id_by_code($table_code) {
        $this->ci->load->database();
        
        $query = $this->ci->db->where('table_code', $table_code)
                              ->get('tables', 1);
        
        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }
        
        return 0;
    }
    
    /**
     * Generate QR Code custom dengan data apapun
     * 
     * @param string $data Data untuk QR code
     * @param string $filename Nama file output
     * @param int $size Ukuran
     * @return string|FALSE Path file relatif atau FALSE
     */
    public function generate_custom($data, $filename, $size = 200) {
        $filepath = $this->qr_path . $filename;
        
        try {
            // Gunakan Google API sebagai default untuk custom QR
            return $this->_generate_with_google_api($data, $filepath, $size);
        } catch (Exception $e) {
            log_message('error', 'Custom QR Code generation failed: ' . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Delete QR Code file
     * 
     * @param string $filename Nama file
     * @return bool
     */
    public function delete_qr($filename) {
        $filepath = $this->qr_path . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return FALSE;
    }
    
    /**
     * Check apakah QR Code sudah ada
     * 
     * @param string $table_code
     * @return bool
     */
    public function qr_exists($table_code) {
        $filename = strtoupper($table_code) . '.png';
        return file_exists($this->qr_path . $filename);
    }
}
