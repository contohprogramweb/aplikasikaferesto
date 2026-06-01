<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Database Configuration
|--------------------------------------------------------------------------
| Using MySQLi driver as specified in SRS v4.0
*/

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = [
    'dsn'      => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'smart_restaurant_pos',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    'encrypt'  => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => [],
    'port'     => 3306,
    
    // Connection persistence
    'persistent' => FALSE,
    
    // Query timeout in seconds
    'query_timeout' => 30,
    
    // Socket for local connections
    'socket' => '',
];

/*
|--------------------------------------------------------------------------
| Database Grouping
|--------------------------------------------------------------------------
| You can have multiple database groups for different environments
*/

// Development environment
$db['development'] = $db['default'];

// Testing environment
$db['test'] = [
    'dsn'      => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'smart_restaurant_pos_test',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => TRUE,
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    'encrypt'  => FALSE,
    'compress' => FALSE,
    'stricton' => TRUE,
    'failover' => [],
    'port'     => 3306,
];

// Production environment (example - customize for your production server)
$db['production'] = [
    'dsn'      => '',
    'hostname' => 'your-production-host.com',
    'username' => 'your-production-username',
    'password' => 'your-production-password',
    'database' => 'smart_restaurant_pos_prod',
    'dbdriver' => 'mysqli',
    'dbprefix' => 'sr_',
    'pconnect' => FALSE,
    'db_debug' => FALSE,
    'cache_on' => TRUE,
    'cachedir' => APPPATH . 'cache/db/',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_unicode_ci',
    'swap_pre' => '',
    'encrypt'  => TRUE,
    'compress' => TRUE,
    'stricton' => TRUE,
    'failover' => [],
    'port'     => 3306,
];
