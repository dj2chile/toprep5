<?php
require_once 'config.php';

// Wyłącz wyświetlanie błędów na output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/api_errors.log');
header('Content-Type: application/json');

try {
    if (!isset($_GET['serial']) || empty(trim($_GET['serial']))) {
        throw new Exception('Brak numeru seryjnego');
    }

    $serial = trim($_GET['serial']);
    
    // Use PDO instead of mysqli
    if (!isset($pdo)) {
        throw new Exception('Błąd połączenia z bazą danych');
    }

    $stmt = $pdo->prepare("
        SELECT phone_number, prefer_phone_contact, prefer_sms_contact, device_model
        FROM repairs 
        WHERE serial_number = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$serial]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response = ['exists' => false];

    if ($result) {
        $response = [
            'exists' => true,
            'phone_number' => $result['phone_number'],
            'prefer_phone_contact' => (bool)$result['prefer_phone_contact'],
            'prefer_sms_contact' => (bool)$result['prefer_sms_contact'],
            'device_model' => $result['device_model']
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => DEBUG_MODE ? $e->getTrace() : null // Only include trace in debug mode
    ]);
    
    // Log the error
    $logFile = __DIR__ . '/logs/api_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = sprintf("[%s] Error: %s\n", $timestamp, $e->getMessage());
    file_put_contents($logFile, $message, FILE_APPEND);
}
?>