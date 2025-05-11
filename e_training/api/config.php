<?php
/**
 * API Configuration
 */

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'e_training';

// Try to connect to the database
$con = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$con) {
    // Log the error
    error_log('Database connection failed: ' . mysqli_connect_error());
    
    // Return a JSON error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status' => [
            'code' => 500,
            'message' => 'Internal Server Error'
        ],
        'data' => [
            'error' => 'Database connection failed'
        ]
    ]);
    exit;
}

// Set character set
mysqli_set_charset($con, 'utf8mb4');

// API Settings
define('API_KEY', 'ab451737ef49bdf783cce0556a3e75edc7dd7feafb6ae6a92fc3792387676fa1');

// Logging
define('LOG_ENABLED', true);
define('LOG_FILE', __DIR__ . '/../api_log.txt');

// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('UTC');
