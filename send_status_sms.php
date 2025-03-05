<?php
/**
 * Send status notification SMS
 * This script sends SMS notifications about repair status changes
 * Last updated: 2025-03-05 14:09:39 by dj2chile
 */

// Strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/sms_errors.log');

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// Start secure session
session_start([
    'use_strict_mode' => 1,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

// Require configuration
require_once 'config.php';
require_once 'settings_functions.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

// Get company settings
$settings = getCompanySettings($pdo);

// Directly get the sms_new_repair_enabled setting for debugging
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_name = 'sms_new_repair_enabled'");
    $stmt->execute();
    $sms_new_repair_enabled = $stmt->fetchColumn();
    error_log("Retrieved sms_new_repair_enabled = " . ($sms_new_repair_enabled ? $sms_new_repair_enabled : "not found"));
} catch (PDOException $e) {
    error_log("Error fetching sms_new_repair_enabled: " . $e->getMessage());
}

// Get contact information
try {
    $stmt = $pdo->query("SELECT * FROM contacts ORDER BY id LIMIT 1");
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contact = null;
}

// Validate parameters
$repair_id = filter_input(INPUT_GET, 'repair_id', FILTER_VALIDATE_INT);
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

if (!$repair_id || empty($status)) {
    $_SESSION['error_message'] = "Brak wymaganych parametrów";
    header("Location: dashboard.php");
    exit();
}

// Get repair details
try {
    $stmt = $pdo->prepare("
        SELECT * FROM repairs 
        WHERE id = ? AND status != 'Usunięty'
    ");
    $stmt->execute([$repair_id]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        $_SESSION['error_message'] = "Nie znaleziono naprawy o ID: " . $repair_id;
        header("Location: dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching repair data: " . $e->getMessage());
    $_SESSION['error_message'] = "Błąd podczas pobierania danych naprawy";
    header("Location: dashboard.php");
    exit();
}

// Log SMS attempt
function logSmsAttempt($repair_id, $phone, $message, $success = true) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/sms_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $status = $success ? 'SUCCESS' : 'FAILED';
    $log_message = "[{$timestamp}] [{$status}] Repair ID: {$repair_id}, Phone: {$phone}, Message: {$message}\n";
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Create appropriate message based on status
function createStatusMessage($status, $settings, $statusUrl, $repair_id, $pdo, $contact) {
    // Format domain URL properly (remove protocol if present)
    $domain = isset($settings['domain_url']) ? $settings['domain_url'] : 'top-serwis.org/repairs/';
    $domain = preg_replace('#^https?://#', '', $domain); // Remove http:// or https:// if present
    
    // Make sure the domain ends with a trailing slash for path concatenation
    if (substr($domain, -1) != '/') {
        $domain .= '/';
    }
    
    $status_page_url = "https://{$domain}status.php?code={$statusUrl}";
    
    // Map status to status_code
    $status_code = 'default';
    switch($status) {
        case 'Gotowe':
        case 'Gotowa do odbioru':
            $status_code = 'ready';
            break;
        
        case 'Oczekiwanie na części':
            $status_code = 'waiting_for_parts';
            break;
            
        case 'W trakcie':
            $status_code = 'in_progress';
            break;
            
        case 'Odebrane':
        case 'Archiwalne':
            $status_code = 'completed';
            break;
    }
    
    // Check if sms_messages table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'sms_messages'");
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            // Get message template from database
            $stmt = $pdo->prepare("SELECT message_template FROM sms_messages WHERE status_code = ? AND active = 1");
            $stmt->execute([$status_code]);
            $template = $stmt->fetchColumn();
            
            if (!$template) {
                // Fall back to default template
                $stmt = $pdo->prepare("SELECT message_template FROM sms_messages WHERE status_code = 'default' AND active = 1");
                $stmt->execute();
                $template = $stmt->fetchColumn();
            }
        } else {
            // Table doesn't exist, use hardcoded templates
            $template = null; // Will be set below
        }
    } catch (PDOException $e) {
        error_log("Error checking SMS templates table: " . $e->getMessage());
        $template = null; // Will use hardcoded templates
    }
    
    // If no template found in database, use hardcoded ones
    if (empty($template)) {
        switch($status_code) {
            case 'ready':
                $template = "Twoje urządzenie jest gotowe do odbioru. Zapraszamy do {company_name}! Status można sprawdzić tutaj: {status_page_url}";
                break;
            case 'waiting_for_parts':
                $template = "Oczekujemy na części do Twojego urządzenia. Status możesz sprawdzić tutaj: {status_page_url}";
                break;
            case 'in_progress':
                $template = "Twoje urządzenie jest w trakcie naprawy. Status możesz sprawdzić tutaj: {status_page_url}";
                break;
            case 'completed':
                $template = "Dziękujemy za skorzystanie z usług {company_name}. Status możesz sprawdzić tutaj: {status_page_url}";
                break;
            default:
                $template = "Status Twojego urządzenia został zaktualizowany: {status}. Szczegóły: {status_page_url}";
                break;
        }
    }
    
    // Prepare replacement values
    $company_name = $settings['company_name'] ?? 'TOP SERWIS';
    
    // Create replacements array
    $replacements = [
        '{company_name}' => $company_name,
        '{status_page_url}' => $status_page_url,
        '{status}' => $status,
        '{repair_id}' => $repair_id
    ];
    
    // Add contact information placeholders if available
    if ($contact) {
        $replacements['{name}'] = $contact['name'] ?? '';
        $replacements['{phone_number}'] = $contact['phone_number'] ?? '';
        $replacements['{mobile_phone_number}'] = $contact['mobile_phone_number'] ?? '';
        $replacements['{email}'] = $contact['email'] ?? '';
        $replacements['{address}'] = $contact['address'] ?? '';
        $replacements['{opening_hours}'] = $contact['opening_hours'] ?? '';
    }
    
    // Replace placeholders with actual values
    foreach ($replacements as $key => $value) {
        $template = str_replace($key, $value, $template);
    }
    
    return $template;
}

// Build the SMS message
$message = createStatusMessage($status, $settings, $repair['status_url'], $repair_id, $pdo, $contact);

// Determine which phone number to use
$phone_number = !empty($repair['phone_number']) ? $repair['phone_number'] : 
               (!empty($repair['additional_phone']) ? $repair['additional_phone'] : '');

if (empty($phone_number)) {
    $_SESSION['error_message'] = "Brak numeru telefonu do wysłania SMS";
    header("Location: repair_details.php?id=" . $repair_id);
    exit();
}

// Log the attempt
logSmsAttempt($repair_id, $phone_number, $message);

// Redirect to SMS app
$sms_url = "sms:" . $phone_number . "?body=" . urlencode($message);
header("Location: " . $sms_url);
exit();