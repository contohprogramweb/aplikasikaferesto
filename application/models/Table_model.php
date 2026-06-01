<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Table Model
 * Mengelola CRUD meja restoran dengan QR code
 */
class Table_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->table = 'tables';
        $this->load->library('qrcode_lib');
    }

    /**
     * Get all tables dengan pagination server-side
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @param string $order_field
     * @param string $order_dir
     * @return array ['data' => [], 'total' => int, 'filtered' => int]
     */
    public function get_datatable($filters = [], $limit = 10, $offset = 0, $order_field = 'table_number', $order_dir = 'ASC')
    {
        // Build query
        $this->db->from($this->table);
        
        // Search filter
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('table_number', $filters['search'], 'both');
            $this->db->or_like('table_name', $filters['search'], 'both');
            $this->db->or_like('location', $filters['search'], 'both');
            $this->db->group_end();
        }
        
        // Status filter
        if (isset($filters['status']) && $filters['status'] !== '') {
            $this->db->where('is_active', $filters['status']);
        }
        
        // Location filter
        if (!empty($filters['location'])) {
            $this->db->where('location', $filters['location']);
        }
        
        // Get total filtered
        $filtered_total = $this->db->count_all_results('', FALSE);
        
        // Get total all
        $this->db->reset_query();
        $total = $this->db->count_all($this->table);
        
        // Ordering
        $allowed_fields = ['id', 'table_number', 'table_name', 'capacity', 'location', 'status', 'is_active', 'created_at'];
        if (!in_array($order_field, $allowed_fields)) {
            $order_field = 'table_number';
        }
        $order_dir = strtoupper($order_dir) === 'DESC' ? 'DESC' : 'ASC';
        $this->db->order_by($order_field, $order_dir);
        
        // Pagination
        $this->db->limit($limit, $offset);
        
        // Execute
        $query = $this->db->get();
        
        return [
            'data' => $query->result_array(),
            'total' => $total,
            'filtered' => $filtered_total
        ];
    }

    /**
     * Get table by ID
     * @param int $id
     * @return array|null
     */
    public function get_by_id($id)
    {
        $query = $this->db->get_where($this->table, ['id' => $id]);
        return $query->row_array();
    }

    /**
     * Check if table number/code exists (untuk validasi unique)
     * @param string $table_number
     * @param int|null $exclude_id Exclude ID untuk update
     * @return bool
     */
    public function table_number_exists($table_number, $exclude_id = null)
    {
        $this->db->where('table_number', $table_number);
        if ($exclude_id !== null) {
            $this->db->where('id !=', $exclude_id);
        }
        $query = $this->db->get($this->table, 1);
        return $query->num_rows() > 0;
    }

    /**
     * Create new table dengan auto-generate QR code
     * @param array $data
     * @return int|bool Insert ID atau FALSE jika gagal
     */
    public function create($data)
    {
        $this->db->trans_start();
        
        // Generate QR code path
        $qr_path = $this->_generate_qr_code($data['table_number']);
        $data['qr_code'] = $qr_path;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->db->insert($this->table, $data)) {
            $insert_id = $this->db->insert_id();
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                return FALSE;
            }
            
            return $insert_id;
        }
        
        $this->db->trans_complete();
        return FALSE;
    }

    /**
     * Update table dengan regenerate QR jika table_number berubah
     * @param int $id
     * @param array $data
     * @param bool $regenerate_qr Force regenerate QR
     * @return bool
     */
    public function update($id, $data, $regenerate_qr = FALSE)
    {
        $this->db->trans_start();
        
        // Get old data
        $old_data = $this->get_by_id($id);
        
        // Regenerate QR jika table_number berubah atau force regenerate
        if ($regenerate_qr || (isset($data['table_number']) && $data['table_number'] !== $old_data['table_number'])) {
            $qr_path = $this->_generate_qr_code($data['table_number'] ?? $old_data['table_number']);
            $data['qr_code'] = $qr_path;
            
            // Delete old QR file
            if (!empty($old_data['qr_code']) && file_exists(FCPATH . $old_data['qr_code'])) {
                @unlink(FCPATH . $old_data['qr_code']);
            }
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        
        if ($this->db->update($this->table, $data)) {
            $this->db->trans_complete();
            return $this->db->trans_status() !== FALSE;
        }
        
        $this->db->trans_complete();
        return FALSE;
    }

    /**
     * Soft delete table (set is_active = 0)
     * @param int $id
     * @return bool
     */
    public function soft_delete($id)
    {
        $data = [
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Hard delete table dengan validasi status
     * Tidak bisa hapus jika meja terisi/menunggu bayar/dibersihkan
     * @param int $id
     * @return array ['success' => bool, 'message' => string]
     */
    public function hard_delete($id)
    {
        // Get table data
        $table = $this->get_by_id($id);
        if (!$table) {
            return ['success' => FALSE, 'message' => 'Meja tidak ditemukan'];
        }
        
        // Cek apakah ada order aktif di meja ini
        $this->db->where('table_id', $id);
        $this->db->where_in('status', ['pending', 'confirmed', 'preparing', 'ready']);
        $this->db->where('payment_status !=', 'paid');
        $active_orders = $this->db->count_all_results('orders');
        
        if ($active_orders > 0) {
            return [
                'success' => FALSE, 
                'message' => 'Tidak dapat menghapus meja karena masih ada order aktif (' . $active_orders . ' order)'
            ];
        }
        
        // Cek apakah meja sedang dibersihkan
        if ($table['status'] === 'occupied' || $table['status'] === 'reserved') {
            return [
                'success' => FALSE, 
                'message' => 'Tidak dapat menghapus meja karena status masih: ' . $table['status']
            ];
        }
        
        // Delete QR file
        if (!empty($table['qr_code']) && file_exists(FCPATH . $table['qr_code'])) {
            @unlink(FCPATH . $table['qr_code']);
        }
        
        // Hard delete
        $this->db->where('id', $id);
        if ($this->db->delete($this->table)) {
            return ['success' => TRUE, 'message' => 'Meja berhasil dihapus'];
        }
        
        return ['success' => FALSE, 'message' => 'Gagal menghapus meja'];
    }

    /**
     * Toggle status active/inactive
     * @param int $id
     * @return bool|null New status atau FALSE jika gagal
     */
    public function toggle_status($id)
    {
        $table = $this->get_by_id($id);
        if (!$table) {
            return FALSE;
        }
        
        $new_status = $table['is_active'] == 1 ? 0 : 1;
        $this->update($id, ['is_active' => $new_status]);
        
        return $new_status;
    }

    /**
     * Update table status (available, occupied, reserved, maintenance)
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function update_status($id, $status)
    {
        $allowed_status = ['available', 'occupied', 'reserved', 'maintenance'];
        if (!in_array($status, $allowed_status)) {
            return FALSE;
        }
        
        $this->db->where('id', $id);
        return $this->db->update($this->table, ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Generate QR code untuk meja
     * @param string $table_number
     * @return string Path QR code relatif
     */
    private function _generate_qr_code($table_number)
    {
        $base_url = base_url();
        return $this->qrcode_lib->generate_table_qr($table_number, $base_url);
    }

    /**
     * Regenerate semua QR code (jika BASE_URL berubah)
     * @return array ['success' => int, 'failed' => int]
     */
    public function regenerate_all_qr()
    {
        $base_url = base_url();
        $success = 0;
        $failed = 0;
        
        $this->db->where('deleted_at IS NULL');
        $query = $this->db->get($this->table);
        
        foreach ($query->result_array() as $table) {
            try {
                // Delete old QR
                if (!empty($table['qr_code']) && file_exists(FCPATH . $table['qr_code'])) {
                    @unlink(FCPATH . $table['qr_code']);
                }
                
                // Generate new QR
                $qr_path = $this->qrcode_lib->generate_table_qr($table['table_number'], $base_url);
                
                if ($qr_path) {
                    $this->db->where('id', $table['id'])
                             ->update($this->table, ['qr_code' => $qr_path, 'updated_at' => date('Y-m-d H:i:s')]);
                    $success++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                log_message('error', 'Failed to regenerate QR for table ' . $table['table_number'] . ': ' . $e->getMessage());
                $failed++;
            }
        }
        
        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Count tables by status
     * @param string $status
     * @return int
     */
    public function count_by_status($status)
    {
        $this->db->where('status', $status);
        $this->db->where('is_active', 1);
        return $this->db->count_all_results($this->table);
    }

    /**
     * Get available tables
     * @return array
     */
    public function get_available_tables()
    {
        $this->db->where('status', 'available');
        $this->db->where('is_active', 1);
        $this->db->order_by('table_number', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result_array();
    }

    /**
     * Check if table can be deleted (no active orders)
     * @param int $id
     * @return bool
     */
    public function can_delete($id)
    {
        $table = $this->get_by_id($id);
        if (!$table) {
            return FALSE;
        }
        
        // Check active orders
        $this->db->where('table_id', $id);
        $this->db->where_in('status', ['pending', 'confirmed', 'preparing', 'ready']);
        $this->db->where('payment_status !=', 'paid');
        $active_orders = $this->db->count_all_results('orders');
        
        return $active_orders == 0;
    }
}
