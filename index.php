<?php
/**
 * CodeIgniter Application Entry Point
 * 
 * This file is the main entry point for the Smart Restaurant POS application.
 * It sets up the environment and loads the CodeIgniter framework.
 */

/*
|---------------------------------------------------------------
| ENVIRONMENT CONFIGURATION
|---------------------------------------------------------------
| Set the application environment:
| - 'development': Shows all errors, debug mode enabled
| - 'production': Hides errors, optimized for live server
*/
define('ENVIRONMENT', 'development');

/*
|---------------------------------------------------------------
| ERROR REPORTING
|---------------------------------------------------------------
| Configure error reporting based on environment
*/
switch (ENVIRONMENT) {
    case 'development':
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        break;
    
    case 'production':
        error_reporting(0);
        ini_set('display_errors', 0);
        break;
    
    default:
        exit('The application environment is not set correctly.');
}

/*
|---------------------------------------------------------------
| LOG LEVEL CONFIGURATION
|---------------------------------------------------------------
| Set log level based on environment:
| - Development: DEBUG (show all logs)
| - Production: ERROR (only show errors)
*/
if (ENVIRONMENT === 'development') {
    // Debug logging enabled
    ini_set('log_errors', 1);
    ini_set('error_log', APPPATH . 'logs/php_error.log');
} else {
    // Only log errors in production
    ini_set('log_errors', 1);
    ini_set('error_log', APPPATH . 'logs/php_error.log');
}

/*
|---------------------------------------------------------------
| APPLICATION PATH CONSTANTS
|---------------------------------------------------------------
*/
// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Name of the "system folder"
define('SYSDIR', basename(APPPATH . '../system'));

// The path to the "application" folder
define('APPPATH', __DIR__ . '/application/');

// The path to the "system" folder
define('BASEPATH', __DIR__ . '/system/');

// The path to the "public" folder (assets, uploads, etc.)
define('PUBLICPATH', __DIR__ . '/public/');

/*
|---------------------------------------------------------------
| LOAD CODEIGNITER
|---------------------------------------------------------------
| Include the CodeIgniter core file to bootstrap the framework
*/
require_once BASEPATH . 'core/CodeIgniter.php';
