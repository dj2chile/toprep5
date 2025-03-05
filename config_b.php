<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definicja środowiska
define('ENVIRONMENT', 'development'); // Zmień na 'production' na serwerze
// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'topserwis_repairs');
define('DB_PASSWORD', 'DaytWNL4jU8uCMtYZEfx');
define('DB_NAME', 'topserwis_repairs');

// Attempt to connect to MySQL database
try {
    // Check MySQL server connectivity
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);
    if ($conn->connect_error) {
        throw new Exception("MySQL server connection failed: " . $conn->connect_error);
    }

    // Check database existence
    $db_check = $conn->select_db(DB_NAME);
    if (!$db_check) {
        throw new Exception("Database does not exist or access denied: " . $conn->error);
    }

    // PDO Connection with enhanced error reporting
    $pdo = new PDO(
        "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USERNAME,
        DB_PASSWORD,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );

    // Verify table structure
    $stmt = $pdo->query("SHOW TABLES LIKE 'repairs'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("'repairs' table does not exist");
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'repair_history'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("'repair_history' table does not exist");
    }

    // Table structure check
    $columns = $pdo->query("DESCRIBE repairs")->fetchAll();
    $required_columns = [
        'device_model', 'serial_number', 'phone_number', 
        'usb_cable', 'power_cable', 'nip', 'status_url', 'status'
    ];

    $existing_columns = array_column($columns, 'Field');
    $missing_columns = array_diff($required_columns, $existing_columns);
    
    if (!empty($missing_columns)) {
        throw new Exception("Missing columns in 'repairs' table: " . implode(', ', $missing_columns));
    }

} catch(Exception $e) {
    // Log the error
    error_log("Database Error: " . $e->getMessage());
    
    // Display error (only in development)
    die("Błąd konfiguracji bazy danych: " . $e->getMessage());
}
?>