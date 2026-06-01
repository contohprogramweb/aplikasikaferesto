<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Restaurant Application Configuration
|--------------------------------------------------------------------------
| Based on SRS v4.0 NFR-MAI-03 and NFR-MAI-08
*/

// Basic Restaurant Information
$config['app_name'] = 'Nama Restoran';
$config['app_address'] = 'Alamat Restoran';
$config['app_phone'] = '081234567890';

// Tax and Service Charge
$config['tax_rate'] = 10; // persen
$config['service_charge'] = 5; // persen

// Currency Settings
$config['currency'] = 'IDR';
$config['currency_symbol'] = 'Rp';

// Receipt Configuration
$config['receipt_width'] = 80; // mm (thermal)
$config['receipt_paper_size'] = 'thermal'; // thermal atau A4

// Session Timeout Settings
$config['session_timeout_customer'] = 30; // menit
$config['session_timeout_staff'] = 8; // jam

// System Settings
$config['polling_interval'] = 5000; // ms
$config['items_per_page'] = 10;
$config['max_discount'] = 100; // persen

// QR Code Configuration
$config['qr_base_url'] = 'https://yourdomain.com';

// Receipt Settings
$config['auto_print_receipt'] = false;
$config['save_receipt_pdf'] = true;
$config['generate_void_receipt'] = true;

// Order Settings
$config['require_cancel_reason'] = true;
$config['bill_after_all_delivered'] = true; // false = bisa minta bill kapan saja
$config['order_prefix'] = 'T';
$config['table_reset_delay'] = 0; // menit

// Category Settings
$config['default_category_id'] = 1;
$config['show_inactive_category_label'] = true;
