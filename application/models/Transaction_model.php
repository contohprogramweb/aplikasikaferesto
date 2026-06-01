<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Transaction Model
 * 
 * Handles all transaction-related operations including payments, refunds, and financial records
 * Provides atomic transaction support for payment processing
 */
class Transaction_model extends CI_Model {

    private $table_name = 'transactions';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Create a new transaction record
     * 
     * @param array $data Transaction data:
     *   - order_id: int (required) - Associated order ID
     *   - transaction_type: string (required) - payment, refund, adjustment
     *   - payment_method: string (required) - tunai, debit, credit, qris, transfer
     *   - amount: decimal (required) - Transaction amount
     *   - change_amount: decimal (optional) - Change given (for cash payments)
     *   - discount_amount: decimal (optional) - Discount applied
     *   - discount_type: string (optional) - percentage, nominal
     *   - notes: string (optional) - Additional notes
     *   - cashier_id: int (optional) - User who processed the transaction
     *   - reference_number: string (optional) - External reference (e.g., QRIS transaction ID)
     * 
     * @return int|bool Transaction ID on success, FALSE on failure
     */
    public function create($data = [])
    {
        // Validate required fields
        $required_fields = ['order_id', 'transaction_type', 'payment_method', 'amount'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                log_message('error', 'Transaction_model::create - Missing required field: ' . $field);
                return FALSE;
            }
        }

        $ci =& get_instance();
        
        $transaction_data = [
            'order_id' => $data['order_id'],
            'transaction_type' => $data['transaction_type'],
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'change_amount' => isset($data['change_amount']) ? $data['change_amount'] : 0,
            'discount_amount' => isset($data['discount_amount']) ? $data['discount_amount'] : 0,
            'discount_type' => isset($data['discount_type']) ? $data['discount_type'] : NULL,
            'notes' => isset($data['notes']) ? $data['notes'] : NULL,
            'cashier_id' => isset($data['cashier_id']) ? $data['cashier_id'] : ($ci->session->userdata('user_id') ?? NULL),
            'reference_number' => isset($data['reference_number']) ? $data['reference_number'] : NULL,
            'status' => 'completed',
            'processed_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $this->db->trans_start();
            
            $this->db->insert($this->table_name, $transaction_data);
            
            if ($this->db->affected_rows() === 0) {
                throw new Exception('Failed to insert transaction record');
            }
            
            $transaction_id = $this->db->insert_id();
            
            // Update order status if this is a payment
            if ($data['transaction_type'] === 'payment') {
                $this->load->model('Order_model');
                $this->Order_model->update_status($data['order_id'], 'lunas');
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed during commit');
            }
            
            log_message('info', 'Transaction created: ID=' . $transaction_id . ', Order=' . $data['order_id']);
            return $transaction_id;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Transaction_model::create - Error: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Process a refund for an order
     * 
     * @param int $order_id Order ID to refund
     * @param decimal $refund_amount Amount to refund
     * @param string $reason Reason for refund
     * @param int $processed_by User ID processing the refund
     * @return int|bool Refund transaction ID on success, FALSE on failure
     */
    public function process_refund($order_id, $refund_amount, $reason = '', $processed_by = NULL)
    {
        $ci =& get_instance();
        
        if ($processed_by === NULL) {
            $processed_by = $ci->session->userdata('user_id');
        }

        try {
            $this->db->trans_start();
            
            // Verify order exists and is paid
            $this->db->where('id', $order_id);
            $this->db->where('payment_status', 'lunas');
            $order = $this->db->get('orders')->row_array();
            
            if (empty($order)) {
                throw new Exception('Order not found or not paid');
            }
            
            // Verify refund amount doesn't exceed original
            if ($refund_amount > $order['grand_total']) {
                throw new Exception('Refund amount exceeds original order total');
            }
            
            // Create refund transaction
            $refund_data = [
                'order_id' => $order_id,
                'transaction_type' => 'refund',
                'payment_method' => $order['payment_method'],
                'amount' => -$refund_amount, // Negative for refund
                'change_amount' => 0,
                'discount_amount' => 0,
                'notes' => $reason,
                'cashier_id' => $processed_by,
                'status' => 'completed',
                'processed_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert($this->table_name, $refund_data);
            
            if ($this->db->affected_rows() === 0) {
                throw new Exception('Failed to create refund record');
            }
            
            $refund_id = $this->db->insert_id();
            
            // Update order status if full refund
            if ($refund_amount >= $order['grand_total']) {
                $this->db->where('id', $order_id)
                         ->update('orders', ['payment_status' => 'refunded']);
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Refund transaction failed');
            }
            
            // Log the refund activity
            $this->load->model('Activity_log_model');
            $this->Activity_log_model->create([
                'action' => 'refund_processed',
                'table' => 'orders',
                'record_id' => $order_id,
                'old_value' => ['payment_status' => 'lunas', 'grand_total' => $order['grand_total']],
                'new_value' => ['refund_amount' => $refund_amount, 'reason' => $reason],
                'user_id' => $processed_by,
                'description' => 'Refund processed: ' . $reason
            ]);
            
            log_message('info', 'Refund processed: ID=' . $refund_id . ', Order=' . $order_id . ', Amount=' . $refund_amount);
            return $refund_id;
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Transaction_model::process_refund - Error: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Get transactions by order ID
     * 
     * @param int $order_id Order ID
     * @return array Transaction records
     */
    public function get_by_order($order_id)
    {
        $this->db->where('order_id', $order_id);
        $this->db->order_by('created_at', 'DESC');
        $query = $this->db->get($this->table_name);
        return $query->result_array();
    }

    /**
     * Get transactions with filtering
     * 
     * @param array $filters Filtering options
     * @param int $limit Number of records
     * @param int $offset Offset for pagination
     * @return array Query results
     */
    public function get_transactions($filters = [], $limit = 50, $offset = 0)
    {
        $this->db->select('t.*, o.order_number, u.username as cashier_name');
        $this->db->from($this->table_name . ' t');
        $this->db->join('orders o', 't.order_id = o.id', 'left');
        $this->db->join('users u', 't.cashier_id = u.id', 'left');
        
        if (!empty($filters['transaction_type'])) {
            $this->db->where('t.transaction_type', $filters['transaction_type']);
        }
        
        if (!empty($filters['payment_method'])) {
            $this->db->where('t.payment_method', $filters['payment_method']);
        }
        
        if (!empty($filters['status'])) {
            $this->db->where('t.status', $filters['status']);
        }
        
        if (!empty($filters['date_from'])) {
            $this->db->where('t.processed_at >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $this->db->where('t.processed_at <=', $filters['date_to']);
        }
        
        if (!empty($filters['cashier_id'])) {
            $this->db->where('t.cashier_id', $filters['cashier_id']);
        }
        
        $this->db->order_by('t.processed_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Get summary statistics for transactions
     * 
     * @param string $date_from Start date
     * @param string $date_to End date
     * @return array Summary statistics
     */
    public function get_summary($date_from, $date_to)
    {
        $this->db->select('
            COUNT(*) as total_transactions,
            SUM(CASE WHEN transaction_type = "payment" THEN amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN transaction_type = "refund" THEN ABS(amount) ELSE 0 END) as total_refunds,
            SUM(CASE WHEN transaction_type = "payment" THEN amount ELSE 0 END) - 
            SUM(CASE WHEN transaction_type = "refund" THEN ABS(amount) ELSE 0 END) as net_revenue,
            COUNT(DISTINCT order_id) as total_orders,
            AVG(CASE WHEN transaction_type = "payment" THEN amount END) as avg_transaction
        ');
        $this->db->from($this->table_name);
        $this->db->where('processed_at >=', $date_from);
        $this->db->where('processed_at <=', $date_to);
        
        $query = $this->db->get();
        return $query->row_array();
    }

    /**
     * Get payment method breakdown
     * 
     * @param string $date_from Start date
     * @param string $date_to End date
     * @return array Payment method statistics
     */
    public function get_payment_method_breakdown($date_from, $date_to)
    {
        $this->db->select('
            payment_method,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount
        ');
        $this->db->from($this->table_name);
        $this->db->where('transaction_type', 'payment');
        $this->db->where('processed_at >=', $date_from);
        $this->db->where('processed_at <=', $date_to);
        $this->db->group_by('payment_method');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Create the transactions table if it doesn't exist
     */
    private function _create_table_if_not_exists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            transaction_type ENUM('payment', 'refund', 'adjustment') NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            change_amount DECIMAL(12,2) DEFAULT 0,
            discount_amount DECIMAL(12,2) DEFAULT 0,
            discount_type VARCHAR(20) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            cashier_id INT DEFAULT NULL,
            reference_number VARCHAR(100) DEFAULT NULL,
            status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'completed',
            processed_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_order (order_id),
            INDEX idx_type (transaction_type),
            INDEX idx_method (payment_method),
            INDEX idx_cashier (cashier_id),
            INDEX idx_status (status),
            INDEX idx_processed (processed_at),
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->query($sql);
        log_message('info', 'Transaction_model: Created transactions table');
    }
}
