<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Autoload Configuration File
|--------------------------------------------------------------------------
| This file lets the system know what should be auto-loaded when
| the application starts, providing quick access to commonly used resources.
*/

/*
|--------------------------------------------------------------------------
| Auto-load Packages
|--------------------------------------------------------------------------
| Automatically load any package located in the application/packages/ folder
*/
$autoload['packages'] = [];

/*
|--------------------------------------------------------------------------
| Auto-load Libraries
|--------------------------------------------------------------------------
| Load common libraries automatically
| These are typically loaded by controllers as needed, but some can be autoloaded
*/
$autoload['libraries'] = [
    'database',
    'session',
    'form_validation',
    'upload',
    'image_lib'
];

/*
|--------------------------------------------------------------------------
| Auto-load Drivers
|--------------------------------------------------------------------------
| Auto-load specific drivers (e.g., cache, session)
*/
$autoload['drivers'] = [
    'cache'
];

/*
|--------------------------------------------------------------------------
| Auto-load Helper Files
|--------------------------------------------------------------------------
| Load commonly used helper files
*/
$autoload['helper'] = [
    'url',
    'form',
    'file',
    'custom'
];

/*
|--------------------------------------------------------------------------
| Auto-load Models
|--------------------------------------------------------------------------
| Models are typically loaded on-demand by controllers for better performance
| Leave empty unless you have models that are used across all controllers
*/
$autoload['model'] = [];

/*
|--------------------------------------------------------------------------
| Auto-load Config Files
|--------------------------------------------------------------------------
| Additional config files to auto-load beyond the main config.php
*/
$autoload['config'] = [
    'app'  // Custom restaurant configuration
];

/*
|--------------------------------------------------------------------------
| Auto-load Language Files
|--------------------------------------------------------------------------
| Load language files if needed globally
*/
$autoload['language'] = [];
