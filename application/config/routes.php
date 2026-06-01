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
