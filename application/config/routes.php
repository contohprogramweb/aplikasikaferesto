<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Router Configuration
|--------------------------------------------------------------------------
| This file lets you remap URI requests to specific controllers and methods.
| Following CodeIgniter 3.1.13 pattern
*/

/*
|--------------------------------------------------------------------------
| Default Controller
|--------------------------------------------------------------------------
| Loaded when no URI segments are present (homepage)
*/
$route['default_controller'] = 'welcome';

/*
|--------------------------------------------------------------------------
| 404 Override
|--------------------------------------------------------------------------
| Controller/method to handle 404 errors
*/
$route['404_override'] = 'errors/page_missing';

/*
|--------------------------------------------------------------------------
| Error Pages Override
|--------------------------------------------------------------------------
| Custom error pages for different HTTP status codes
*/
$route['error_403'] = 'errors/error_403';
$route['error_500'] = 'errors/error_500';

/*
|--------------------------------------------------------------------------
| Translate Dashes
|--------------------------------------------------------------------------
| Automatically convert dashes in URIs to underscores
*/
$route['translate_uri_dashes'] = FALSE;

/*
|--------------------------------------------------------------------------
| Reserved Routes
|--------------------------------------------------------------------------
| System routes that should not be overridden
*/
$route['default_controller'] = 'welcome';

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
$route['login'] = 'auth/login';
$route['logout'] = 'auth/logout';
$route['register'] = 'auth/register';
$route['forgot-password'] = 'auth/forgot_password';
$route['reset-password'] = 'auth/reset_password';
$route['auth/login'] = 'auth/login';
$route['auth/do_login'] = 'auth/do_login';
$route['auth/logout'] = 'auth/logout';
$route['auth/forgot_password'] = 'auth/forgot_password';
$route['auth/do_forgot_password'] = 'auth/do_forgot_password';

/*
|--------------------------------------------------------------------------
| Admin Routes - Category & Table Management
|--------------------------------------------------------------------------
*/
$route['admin'] = 'admin/dashboard';
$route['admin/dashboard'] = 'admin/dashboard';
$route['admin/users'] = 'admin/users';
$route['admin/users/(:num)'] = 'admin/users/edit/$1';

// Category Management (UC-ADM-02)
$route['admin/categories'] = 'admin_category/index';
$route['admin_category'] = 'admin_category';
$route['admin_category/datatable'] = 'admin_category/datatable';
$route['admin_category/save'] = 'admin_category/save';
$route['admin_category/edit/(:num)'] = 'admin_category/edit/$1';
$route['admin_category/delete/(:num)'] = 'admin_category/delete/$1';
$route['admin_category/toggle_status/(:num)'] = 'admin_category/toggle_status/$1';

// Menu Item Management (UC-ADM-01)
$route['admin/menu-items'] = 'admin_menu/index';
$route['admin_menu'] = 'admin_menu';
$route['admin_menu/datatable'] = 'admin_menu/datatable';
$route['admin_menu/save'] = 'admin_menu/save';
$route['admin_menu/edit/(:num)'] = 'admin_menu/edit/$1';
$route['admin_menu/delete/(:num)'] = 'admin_menu/delete/$1';
$route['admin_menu/toggle_available/(:num)'] = 'admin_menu/toggle_available/$1';
$route['admin_menu/check_name_unique_ajax'] = 'admin_menu/check_name_unique_ajax';

// Table Management (UC-ADM-03)
$route['admin/tables'] = 'admin_table/index';
$route['admin_table'] = 'admin_table';
$route['admin_table/datatable'] = 'admin_table/datatable';
$route['admin_table/save'] = 'admin_table/save';
$route['admin_table/edit/(:num)'] = 'admin_table/edit/$1';
$route['admin_table/delete/(:num)'] = 'admin_table/delete/$1';
$route['admin_table/toggle_status/(:num)'] = 'admin_table/toggle_status/$1';
$route['admin_table/print_qr/(:num)'] = 'admin_table/print_qr/$1';
$route['admin_table/regenerate_all_qr'] = 'admin_table/regenerate_all_qr';

// User Management (UC-ADM-04)
$route['admin/users'] = 'admin_user/index';
$route['admin_user'] = 'admin_user';
$route['admin_user/datatable'] = 'admin_user/datatable';
$route['admin_user/save'] = 'admin_user/save';
$route['admin_user/edit/(:num)'] = 'admin_user/edit/$1';
$route['admin_user/delete/(:num)'] = 'admin_user/delete/$1';
$route['admin_user/toggle_status/(:num)'] = 'admin_user/toggle_status/$1';
$route['admin_user/check_username_unique_ajax'] = 'admin_user/check_username_unique_ajax';
$route['admin_user/check_email_unique_ajax'] = 'admin_user/check_email_unique_ajax';

$route['admin/menu'] = 'admin/menu';
$route['admin/menu/(:num)'] = 'admin/menu/edit/$1';
$route['admin/orders'] = 'admin/orders';
$route['admin/orders/(:num)'] = 'admin/orders/view/$1';
$route['admin/transactions'] = 'admin/transactions';
$route['admin/reports'] = 'admin/reports';
$route['admin/settings'] = 'admin/settings';

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
*/
$route['staff'] = 'staff/dashboard';
$route['staff/dashboard'] = 'staff/dashboard';
$route['staff/orders'] = 'staff/orders';
$route['staff/orders/create'] = 'staff/orders/create';
$route['staff/orders/(:num)'] = 'staff/orders/view/$1';
$route['staff/tables'] = 'staff/tables';
$route['staff/menu'] = 'staff/menu';
$route['staff/kitchen'] = 'staff/kitchen';

/*
|--------------------------------------------------------------------------
| Staff Routes (Kitchen, Waiter, Cashier)
|--------------------------------------------------------------------------
*/
// Kitchen Display System (UC-KIT-01, UC-KIT-02, UC-KIT-03)
$route['kitchen'] = 'kitchen/index';
$route['kds'] = 'kitchen/index';
$route['api/kitchen/orders'] = 'kitchen/orders';
$route['kitchen/accept'] = 'kitchen/accept';
$route['kitchen/update_status'] = 'kitchen/update_status';
$route['kitchen/cancel_item'] = 'kitchen/cancel_item';
$route['kitchen/undo_status'] = 'kitchen/undo_status';

// Waiter Dashboard (UC-WAIT-01, UC-WAIT-02, UC-WAIT-03)
$route['waiter'] = 'waiter/index';
$route['api/waiter/ready'] = 'waiter/ready';
$route['waiter/deliver'] = 'waiter/deliver';
$route['waiter/tables'] = 'waiter/tables';
$route['waiter/clean_table'] = 'waiter/clean_table';

// Cashier Dashboard (UC-CASH-01 to UC-CASH-05)
$route['cashier'] = 'cashier/index';
$route['api/cashier/tables'] = 'cashier/tables';
$route['cashier/detail/(:num)'] = 'cashier/detail/$1';
$route['cashier/apply_discount'] = 'cashier/apply_discount';
$route['cashier/pay'] = 'cashier/pay';
$route['cashier/print_receipt/(:num)'] = 'cashier/print_receipt/$1';
$route['cashier/clean_table'] = 'cashier/clean_table';

/*
|--------------------------------------------------------------------------
| Customer Routes (QR Ordering)
|--------------------------------------------------------------------------
*/
$route['customer'] = 'customer/home';
$route['customer/tables'] = 'customer/tables';
$route['customer/table/(:num)'] = 'customer/tables/select/$1';
$route['customer/menu'] = 'customer/menu';
$route['customer/menu/(:num)'] = 'customer/menu/category/$1';
$route['customer/order'] = 'customer/orders';
$route['customer/order/create'] = 'customer/orders/create';
$route['customer/order/(:num)'] = 'customer/orders/view/$1';
$route['customer/payment'] = 'customer/payment';
$route['customer/receipt/(:num)'] = 'customer/orders/receipt/$1';

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
$route['api'] = 'api/home';
$route['api/menu'] = 'api/menu';
$route['api/menu/(:num)'] = 'api/menu/detail/$1';
$route['api/orders'] = 'api/orders';
$route['api/orders/(:num)'] = 'api/orders/detail/$1';
$route['api/tables'] = 'api/tables';
$route['api/tables/(:num)/status'] = 'api/tables/status/$1';

// Kitchen API endpoints
$route['api/kitchen/orders'] = 'kitchen/orders';
$route['api/kitchen/accept'] = 'kitchen/accept';
$route['api/kitchen/update_status'] = 'kitchen/update_status';
$route['api/kitchen/cancel_item'] = 'kitchen/cancel_item';

// Waiter API endpoints
$route['api/waiter/ready'] = 'waiter/ready';
$route['api/waiter/deliver'] = 'waiter/deliver';
$route['api/waiter/tables'] = 'waiter/tables';
$route['api/waiter/clean_table'] = 'waiter/clean_table';

// Cashier API endpoints
$route['api/cashier/tables'] = 'cashier/tables';
$route['api/cashier/detail'] = 'cashier/detail';
$route['api/cashier/apply_discount'] = 'cashier/apply_discount';
$route['api/cashier/pay'] = 'cashier/pay';
$route['api/cashier/print_receipt'] = 'cashier/print_receipt';

// Customer API endpoints
$route['api/customer/session'] = 'customer/session';
$route['api/customer/cart'] = 'customer/cart';
$route['api/customer/order'] = 'customer/order';
$route['api/customer/payment'] = 'customer/payment';

/*
|--------------------------------------------------------------------------
| Utility Routes
|--------------------------------------------------------------------------
*/
$route['qr/(:num)'] = 'utils/qr_code/$1';
$route['print/invoice/(:num)'] = 'utils/print_invoice/$1';
$route['print/receipt/(:num)'] = 'utils/print_receipt/$1';
$route['print/kitchen/(:num)'] = 'utils/print_kitchen_order/$1';

/*
|--------------------------------------------------------------------------
| Catch-all Route (must be last)
|--------------------------------------------------------------------------
*/
$route['(:any)'] = '$1';
