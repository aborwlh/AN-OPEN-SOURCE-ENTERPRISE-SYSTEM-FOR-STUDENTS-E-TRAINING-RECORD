<?php
/**
 * API Entry Point
 * 
 * This file serves as the entry point for the API.
 * It includes the secure API implementation.
 */

// Define API_ACCESS constant to prevent direct access to secure.php
define('API_ACCESS', true);

// Include the secure API implementation
require_once __DIR__ . '/secure.php';
?>
