<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Admin_Controller.php';

/**
 * Admin Refund Controller
 * Mengelola refund dan void transaksi.
 * 
 * Implements: UC-ADM-07
 */
class Admin_refund extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('order_model');
        $this->load->model('transaction_model', 'transaction');
        
        // Ensure admin role only
        if ($this->session->userdata('role') !== 'admin') {
            show_error('Akses ditolak. Hanya administrator yang dapat melakukan refund/void.', 403);
        }
    }

    /**
     * Daftar Transaksi untuk Refund
     * Menampilkan transaksi yang sudah dibayar dalam periode tertentu
     */
    public function index()
    {
        $start_date = $this->input->get('start_date') ?? date('Y-m-d', strtotime('-7 days'));
        $end_date = $this->input->get('end_date') ?? date('Y-m-d');
        
        $this->db->select('t.*, o.order_number, u.username as cashier_name, tb.code as table_code, tb.id as table_id');
        $this->db->from('transactions t');
        $this->db->join('orders o', 't.order_id = o.id');
        $this->db->join('users u', 't.user_id = u.id');
        $this->db->join('tables tb', 'o.table_id = tb.id');
        $this->db->where('DATE(t.created_at) >=', $start_date);
        $this->db->where('DATE(t.created_at) <=', $end_date);
        $this->db->where('t.status', 'paid');
        $this->db->where('t.is_refunded', 0); // Belum di-refund
        $this->db->order_by('t.created_at', 'DESC');
        
        $data['transactions'] = $this->db->get()->result_array();
        $data['page_title'] = 'Refund / Void Transaksi';
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        
        $this->load->view('admin/refund', $data);
    }

    /**
     * Detail Transaksi untuk Refund
     * Menampilkan detail item order sebelum refund
     */
    public function detail($transaction_id)
    {
        $transaction = $this->db->get_where('transactions', ['id' => $transaction_id])->row_array();
        
        if (!$transaction) {
            show_error('Transaksi tidak ditemukan.');
        }
        
        if ($transaction['status'] !== 'paid') {
            show_error('Hanya transaksi yang sudah dibayar yang dapat di-refund.');
        }
        
        if ($transaction['is_refunded'] == 1) {
            show_error('Transaksi ini sudah di-refund sebelumnya.');
        }
        
        // Get order details
        $this->db->select('oi.*, mi.name as item_name');
        $this->db->from('order_items oi');
        $this->db->join('menu_items mi', 'oi.menu_item_id = mi.id');
        $this->db->where('oi.order_id', $transaction['order_id']);
        $data['items'] = $this->db->get()->result_array();
        
        $data['transaction'] = $transaction;
        $data['page_title'] = 'Detail Refund - ' . $transaction['order_number'];
        
        $this->load->view('admin/refund_detail', $data);
    }

    /**
     * Proses Refund
     * @param int $transaction_id
     */
    public function process($transaction_id)
    {
        $transaction = $this->db->get_where('transactions', ['id' => $transaction_id])->row_array();
        
        if (!$transaction) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']));
        }
        
        if ($transaction['is_refunded'] == 1) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Transaksi sudah di-refund']));
        }
        
        // Get POST data
        $reason = $this->input->post('reason');
        $refund_amount = $this->input->post('refund_amount');
        $refund_type = $this->input->post('refund_type'); // full/partial
        
        // Validation
        if (empty($reason)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Alasan refund wajib diisi']));
        }
        
        if ($refund_type === 'full') {
            $refund_amount = $transaction['amount'];
        } else {
            $refund_amount = floatval($refund_amount);
            if ($refund_amount <= 0 || $refund_amount > $transaction['amount']) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['success' => false, 'message' => 'Jumlah refund tidak valid']));
            }
        }
        
        // Start transaction
        $this->db->trans_start();
        
        // Update transaction
        $update_data = [
            'is_refunded' => 1,
            'refunded_at' => date('Y-m-d H:i:s'),
            'refund_reason' => $reason,
            'refund_amount' => $refund_amount,
            'refund_by' => $this->session->userdata('user_id')
        ];
        
        if ($refund_type === 'full') {
            $update_data['status'] = 'refunded';
        } else {
            $update_data['status'] = 'partial_refunded';
        }
        
        $this->db->where('id', $transaction_id);
        $this->db->update('transactions', $update_data);
        
        // Create refund record in refund_logs table
        $refund_log = [
            'transaction_id' => $transaction_id,
            'order_id' => $transaction['order_id'],
            'refund_amount' => $refund_amount,
            'refund_type' => $refund_type,
            'reason' => $reason,
            'processed_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('refund_logs', $refund_log);
        
        // If full refund, update order status
        if ($refund_type === 'full') {
            $this->db->where('id', $transaction['order_id']);
            $this->db->update('orders', ['status' => 'refunded']);
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Gagal memproses refund']));
        }
        
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'message' => 'Refund berhasil diproses',
                'refund_amount' => $refund_amount
            ]));
    }

    /**
     * Void Order (Batalkan pesanan sebelum bayar)
     * Ini berbeda dengan refund - void untuk order yang belum dibayar
     */
    public function void($order_id)
    {
        $order = $this->db->get_where('orders', ['id' => $order_id])->row_array();
        
        if (!$order) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Order tidak ditemukan']));
        }
        
        if ($order['status'] === 'completed' || $order['status'] === 'paid') {
            return $this->output
                ->set_content_type('application_json')
                ->set_output(json_encode(['success' => false, 'message' => 'Order sudah dibayar, gunakan refund bukan void']));
        }
        
        $reason = $this->input->post('reason');
        
        if (empty($reason)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Alasan void wajib diisi']));
        }
        
        $this->db->trans_start();
        
        // Update order status
        $this->db->where('id', $order_id);
        $this->db->update('orders', [
            'status' => 'voided',
            'void_reason' => $reason,
            'voided_at' => date('Y-m-d H:i:s'),
            'voided_by' => $this->session->userdata('user_id')
        ]);
        
        // Update order items
        $this->db->where('order_id', $order_id);
        $this->db->update('order_items', ['status' => 'voided']);
        
        // Create void log
        $void_log = [
            'order_id' => $order_id,
            'reason' => $reason,
            'processed_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('void_logs', $void_log);
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Gagal memproses void']));
        }
        
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['success' => true, 'message' => 'Order berhasil di-void']));
    }

    /**
     * Riwayat Refund
     */
    public function history()
    {
        $start_date = $this->input->get('start_date') ?? date('Y-m-d', strtotime('-30 days'));
        $end_date = $this->input->get('end_date') ?? date('Y-m-d');
        
        $this->db->select('rl.*, t.amount as original_amount, o.order_number, u.username as processed_by_name');
        $this->db->from('refund_logs rl');
        $this->db->join('transactions t', 'rl.transaction_id = t.id');
        $this->db->join('orders o', 'rl.order_id = o.id');
        $this->db->join('users u', 'rl.processed_by = u.id');
        $this->db->where('DATE(rl.created_at) >=', $start_date);
        $this->db->where('DATE(rl.created_at) <=', $end_date);
        $this->db->order_by('rl.created_at', 'DESC');
        
        $data['refunds'] = $this->db->get()->result_array();
        $data['page_title'] = 'Riwayat Refund';
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;
        
        $this->load->view('admin/refund_history', $data);
    }
}
