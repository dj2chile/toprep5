<?php
// Main entry point for repair details
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Start session
session_start([
    'use_strict_mode' => 1,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

// Set security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// Require configuration and dependencies
require_once 'config.php';
require_once 'settings_functions.php';
require_once 'models/repair_details_model.php';
require_once 'controllers/repair_details_controller.php';

// Main execution
try {
    // Determine if this is an AJAX request
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    // Initialize variables
    $error_message = '';
    $success_message = '';
    
    // Handle the request
    $controller = new RepairDetailsController($pdo);
    $result = $controller->handleRequest();
    
    // Process the results
    if ($result['status'] === 'success') {
        if (isset($result['redirect'])) {
            header("Location: " . $result['redirect']);
            exit();
        }
        
        if (isset($result['success_message'])) {
            $success_message = $result['success_message'];
        }
        
        // Load data for view
        $repair = $result['data']['repair'] ?? null;
        $repair_id = $result['data']['repair_id'] ?? 0;
        $history = $result['data']['history'] ?? [];
        $related_repairs = $result['data']['related_repairs'] ?? [];
        $initial_options = $result['data']['initial_options'] ?? [];
        $csrf_token = $result['data']['csrf_token'] ?? '';
        $statusClasses = $result['data']['statusClasses'] ?? [];
        $settings = $result['data']['settings'] ?? [];
        
        // Meta information
        $lastUpdate = "2025-03-05 00:41:20";
        $lastUser = "dj2chile";
        
        // Render view
        if (!$is_ajax) {
            include 'views/repair_details_view.php';
        }
    } else {
        $error_message = $result['error_message'] ?? "Wystąpił błąd podczas przetwarzania żądania";
        
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            exit(json_encode(['error' => $error_message]));
        } else {
            include 'views/repair_details_view.php';
        }
    }
    
} catch (Exception $e) {
    // Log and handle errors
    error_log("Repair details error: " . $e->getMessage());
    
    if ($is_ajax) {
        // Send JSON error for AJAX requests
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        exit(json_encode(['error' => $e->getMessage()]));
    } else {
        // Display error for non-AJAX requests
        $error_message = $e->getMessage() ?: "Wystąpił błąd podczas przetwarzania żądania";
        include 'views/repair_details_view.php';
    }
}