<?php
declare(strict_types=1);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session configuration (before session starts)
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters before starting session
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    // Removed session.cookie_secure for local development
    
    // Now start the session
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'perfume_shop');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_URL', 'http://localhost/act7');
define('SITE_NAME', 'Elixia Parfumerie');

// Time Zone
date_default_timezone_set('Asia/Manila');