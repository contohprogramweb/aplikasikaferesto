<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Restaurant Configuration
|--------------------------------------------------------------------------
*/
$config['restaurant_name'] = 'Smart Restaurant POS';
$config['restaurant_address'] = 'Jl. Contoh No. 123, Jakarta';
$config['restaurant_phone'] = '+62 21 12345678';
$config['restaurant_email'] = 'info@smartrestaurant.com';
$config['restaurant_logo'] = 'assets/images/logo.png';

/*
|--------------------------------------------------------------------------
| Tax and Service Charge Configuration
|--------------------------------------------------------------------------
*/
$config['tax_enabled'] = TRUE;
$config['tax_rate'] = 10.0; // 10% tax
$config['tax_label'] = 'PB1 (10%)';

$config['service_charge_enabled'] = TRUE;
$config['service_charge_rate'] = 5.0; // 5% service charge
$config['service_charge_label'] = 'Service Charge (5%)';

/*
|--------------------------------------------------------------------------
| Currency Configuration
|--------------------------------------------------------------------------
*/
$config['currency_symbol'] = 'Rp ';
$config['currency_code'] = 'IDR';
$config['currency_decimal_places'] = 0;
$config['currency_thousand_separator'] = '.';
$config['currency_decimal_separator'] = ',';

/*
|--------------------------------------------------------------------------
| Order Configuration
|--------------------------------------------------------------------------
*/
$config['order_prefix'] = 'ORD';
$config['order_date_format'] = 'Y-m-d H:i:s';
$config['order_number_format'] = 'YmdHis'; // e.g., ORD20240115120530
$config['default_order_status'] = 'pending'; // pending, confirmed, preparing, ready, completed, cancelled

/*
|--------------------------------------------------------------------------
| Table Configuration
|--------------------------------------------------------------------------
*/
$config['table_prefix'] = 'T';
$config['max_guests_per_table'] = 10;

/*
|--------------------------------------------------------------------------
| Session Configuration
|--------------------------------------------------------------------------
*/
$config['session_timeout_minutes'] = 120; // Customer session timeout in minutes
$config['customer_session_enabled'] = TRUE;

/*
|--------------------------------------------------------------------------
| QR Code Configuration
|--------------------------------------------------------------------------
$config['qr_code_enabled'] = TRUE;
$config['qr_code_size'] = 200;
$config['qr_code_error_correction'] = 'M'; // L, M, Q, H

/*
|--------------------------------------------------------------------------
| PDF Configuration (for invoices)
|--------------------------------------------------------------------------
*/
$config['pdf_enabled'] = TRUE;
$config['pdf_paper_size'] = 'A4';
$config['pdf_orientation'] = 'portrait';

/*
|--------------------------------------------------------------------------
| Business Hours
|--------------------------------------------------------------------------
*/
$config['business_hours'] = [
    'monday' => ['open' => '09:00', 'close' => '22:00'],
    'tuesday' => ['open' => '09:00', 'close' => '22:00'],
    'wednesday' => ['open' => '09:00', 'close' => '22:00'],
    'thursday' => ['open' => '09:00', 'close' => '22:00'],
    'friday' => ['open' => '09:00', 'close' => '23:00'],
    'saturday' => ['open' => '09:00', 'close' => '23:00'],
    'sunday' => ['open' => '10:00', 'close' => '22:00']
];

/*
|--------------------------------------------------------------------------
| Miscellaneous Settings
|--------------------------------------------------------------------------
*/
$config['enable_reviews'] = TRUE;
$config['enable_loyalty_program'] = FALSE;
$config['min_order_amount'] = 0;
$config['max_items_per_order'] = 50;
$config['allow_split_bill'] = TRUE;
$config['allow_merge_orders'] = TRUE;
