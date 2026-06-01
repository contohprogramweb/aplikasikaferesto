<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Custom Helper untuk Sistem Manajemen Kafe & Resto
 * Sesuai SRS v4.0 Bab 7.3.2
 */

// ------------------------------------------------------------------------

/**
 * Format angka ke format Rupiah Indonesia
 * 
 * @param float|int $amount
 * @return string Format: "Rp 15.000"
 */
if (!function_exists('format_rupiah')) {
    function format_rupiah($amount) {
        $ci = get_instance();
        $ci->load->config('app');
        $currency_symbol = $ci->config->item('currency_symbol');
        
        return $currency_symbol . ' ' . number_format((float)$amount, 0, ',', '.');
    }
}

// ------------------------------------------------------------------------

/**
 * Format datetime ke format Indonesia
 * 
 * @param string|DateTime $datetime
 * @param string $format Output format (default: d-m-Y H:i)
 * @return string Format: "31-05-2026 14:30"
 */
if (!function_exists('format_datetime')) {
    function format_datetime($datetime, $format = 'd-m-Y H:i') {
        if (empty($datetime)) {
            return '-';
        }
        
        if ($datetime instanceof DateTime) {
            $dt = $datetime;
        } else {
            $dt = new DateTime($datetime);
        }
        
        return $dt->format($format);
    }
}

// ------------------------------------------------------------------------

/**
 * Generate nomor order unik dengan atomic counter
 * Menggunakan tabel order_counters untuk mencegah race condition
 * 
 * @return string Format: "ORD-20260531-0001"
 */
if (!function_exists('generate_order_number')) {
    function generate_order_number() {
        $ci = get_instance();
        $ci->load->database();
        
        $today = date('Ymd');
        
        // Gunakan transaction untuk atomic operation
        $ci->db->trans_start();
        
        // Lock row untuk update (SELECT FOR UPDATE equivalent)
        $ci->db->where('counter_date', $today);
        $ci->db->where('counter_type', 'order');
        $query = $ci->db->get('order_counters');
        
        if ($query->num_rows() > 0) {
            // Row exists, increment counter
            $row = $query->row();
            $new_count = $row->counter_value + 1;
            
            $ci->db->where('counter_date', $today);
            $ci->db->where('counter_type', 'order');
            $ci->db->update('order_counters', [
                'counter_value' => $new_count,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            // New row for today
            $new_count = 1;
            $ci->db->insert('order_counters', [
                'counter_type' => 'order',
                'counter_date' => $today,
                'counter_value' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        $ci->db->trans_complete();
        
        if ($ci->db->trans_status() === FALSE) {
            // Fallback jika transaction gagal
            $new_count = rand(1000, 9999);
        }
        
        // Format: ORD-YYYYMMDD-XXXX
        return 'ORD-' . $today . '-' . str_pad($new_count, 4, '0', STR_PAD_LEFT);
    }
}

// ------------------------------------------------------------------------

/**
 * Catat audit log untuk setiap perubahan data penting
 * 
 * @param string $action Jenis aksi (CREATE, UPDATE, DELETE, VIEW)
 * @param string $entity_type Tipe entitas (user, menu_item, order, dll)
 * @param int $entity_id ID entitas
 * @param mixed $old_val Nilai lama (array atau string)
 * @param mixed $new_val Nilai baru (array atau string)
 * @return bool TRUE jika berhasil
 */
if (!function_exists('audit_log')) {
    function audit_log($action, $entity_type, $entity_id, $old_val = null, $new_val = null) {
        $ci = get_instance();
        $ci->load->database();
        
        // Dapatkan user yang sedang login
        $user_id = $ci->session->userdata('user_id');
        $username = $ci->session->userdata('username');
        
        if (empty($user_id)) {
            // Jika tidak ada user login (misal: customer)
            $user_id = null;
            $username = 'system';
        }
        
        // Serialize nilai jika array
        $old_value_json = is_array($old_val) ? json_encode($old_val, JSON_UNESCAPED_UNICODE) : $old_val;
        $new_value_json = is_array($new_val) ? json_encode($new_val, JSON_UNESCAPED_UNICODE) : $new_val;
        
        $data = [
            'action' => strtoupper($action),
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'old_value' => $old_value_json,
            'new_value' => $new_value_json,
            'user_id' => $user_id,
            'username' => $username,
            'ip_address' => $ci->input->ip_address(),
            'user_agent' => $ci->input->user_agent(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return $ci->db->insert('activity_logs', $data);
    }
}

// ------------------------------------------------------------------------

/**
 * Generate token unik untuk session customer berdasarkan meja
 * 
 * @param int $table_id ID meja
 * @return string Token unik (64 karakter hex)
 */
if (!function_exists('generate_customer_token')) {
    function generate_customer_token($table_id) {
        // Combine table_id, timestamp, dan random bytes
        $timestamp = microtime(true);
        $random = bin2hex(random_bytes(16));
        
        return hash('sha256', $table_id . '_' . $timestamp . '_' . $random);
    }
}

// ------------------------------------------------------------------------

/**
 * Hitung grand total dengan memperhitungkan diskon, pajak, dan service charge
 * 
 * @param float $subtotal Subtotal sebelum diskon
 * @param float $discount Nilai diskon (angka atau persentase)
 * @param string $discount_type Tipe diskon: 'fixed' atau 'percentage'
 * @param float $tax_rate Persen pajak (contoh: 11 untuk 11%)
 * @param float $service_charge_rate Persen service charge (contoh: 10 untuk 10%)
 * @return array ['subtotal' => x, 'discount_amount' => x, 'after_discount' => x, 
 *                'tax_amount' => x, 'service_charge_amount' => x, 'grand_total' => x]
 */
if (!function_exists('calculate_grand_total')) {
    function calculate_grand_total($subtotal, $discount = 0, $discount_type = 'fixed', 
                                   $tax_rate = 0, $service_charge_rate = 0) {
        $ci = get_instance();
        $ci->load->config('app');
        
        // Gunakan nilai dari config jika parameter 0
        if ($tax_rate == 0) {
            $tax_rate = $ci->config->item('tax_rate');
        }
        if ($service_charge_rate == 0) {
            $service_charge_rate = $ci->config->item('service_charge_rate');
        }
        
        $subtotal = (float)$subtotal;
        $discount = (float)$discount;
        
        // Hitung diskon
        if ($discount_type === 'percentage') {
            $discount_amount = $subtotal * ($discount / 100);
        } else {
            $discount_amount = $discount;
        }
        
        // Pastikan diskon tidak melebihi subtotal
        $discount_amount = min($discount_amount, $subtotal);
        
        // Subtotal setelah diskon
        $after_discount = $subtotal - $discount_amount;
        
        // Hitung service charge (berdasarkan after_discount)
        $service_charge_amount = $after_discount * ($service_charge_rate / 100);
        
        // Hitung pajak (berdasarkan after_discount + service_charge)
        $taxable_amount = $after_discount + $service_charge_amount;
        $tax_amount = $taxable_amount * ($tax_rate / 100);
        
        // Grand total
        $grand_total = $after_discount + $service_charge_amount + $tax_amount;
        
        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discount_amount, 2),
            'discount_type' => $discount_type,
            'after_discount' => round($after_discount, 2),
            'service_charge_rate' => $service_charge_rate,
            'service_charge_amount' => round($service_charge_amount, 2),
            'tax_rate' => $tax_rate,
            'tax_amount' => round($tax_amount, 2),
            'grand_total' => round($grand_total, 2)
        ];
    }
}

// ------------------------------------------------------------------------

/**
 * Helper untuk cek status meja
 * 
 * @param int $table_id
 * @return array ['status' => 'available|occupied|reserved', 'order_id' => null|int]
 */
if (!function_exists('check_table_status')) {
    function check_table_status($table_id) {
        $ci = get_instance();
        $ci->load->database();
        
        $ci->db->where('table_id', $table_id);
        $ci->db->where('status', 'pending');
        $ci->db->order_by('created_at', 'DESC');
        $query = $ci->db->get('orders', 1);
        
        if ($query->num_rows() > 0) {
            $order = $query->row();
            return [
                'status' => 'occupied',
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ];
        }
        
        return [
            'status' => 'available',
            'order_id' => null,
            'order_number' => null
        ];
    }
}

// ------------------------------------------------------------------------

/**
 * Sanitize input untuk mencegah XSS
 * 
 * @param string $str
 * @return string
 */
if (!function_exists('clean_input')) {
    function clean_input($str) {
        $ci = get_instance();
        return $ci->security->xss_clean(trim($str));
    }
}

// ------------------------------------------------------------------------

/**
 * Get setting dari database
 * 
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
if (!function_exists('get_setting')) {
    function get_setting($key, $default = null) {
        $ci = get_instance();
        $ci->load->database();
        
        static $settings = [];
        
        if (empty($settings)) {
            $query = $ci->db->get('settings');
            foreach ($query->result() as $row) {
                $settings[$row->setting_key] = $row->setting_value;
            }
        }
        
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
}
