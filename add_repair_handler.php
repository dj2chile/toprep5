<?php
function generateSecureString($length = 16) {
    return bin2hex(random_bytes(ceil($length / 2)));
}

session_start();
require_once 'config.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Log function
function logAction($message) {
    $logFile = __DIR__ . '/logs/repair_actions.log';
    $timestamp = date('Y-m-d H:i:s');
    $username = $_SESSION['username'] ?? 'unknown';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $logEntry = sprintf(
        "[%s] User: %s | IP: %s | %s\n",
        $timestamp,
        $username,
        $ip,
        $message
    );
    
    // Make sure the logs directory exists
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['device_model'])) {
    try {
        // Generate secure status URL
        $statusUrl = generateSecureString();
        
        // Extract form data
        $device_model = $_POST['device_model'];
        $serial_number = $_POST['serial_number'];
        $phone_number = $_POST['phone_number'];
        $nip = !empty($_POST['nip']) ? $_POST['nip'] : null;
        $prefer_phone_contact = isset($_POST['prefer_phone_contact']) ? 1 : 0;
        $prefer_sms_contact = isset($_POST['prefer_sms_contact']) ? 1 : 0;
        $usb_cable = isset($_POST['usb_cable']) ? 1 : 0;
        $power_cable = isset($_POST['power_cable']) ? 1 : 0;
        $option_expertise = isset($_POST['option_expertise']) ? 1 : 0;
        $option_repair = isset($_POST['option_repair']) ? 1 : 0;
        $option_supplies = isset($_POST['option_supplies']) ? 1 : 0;
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Prepare statement with added contact preference columns
        $stmt = $pdo->prepare("
            INSERT INTO repairs (
                device_model, serial_number, phone_number, 
                prefer_phone_contact, prefer_sms_contact,
                usb_cable, power_cable, nip, status_url, status,
                option_expertise, option_repair, option_supplies,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'W trakcie', ?, ?, ?, NOW(), NOW())
        ");
        
        // Execute with form data
        $stmt->execute([
            $device_model,
            $serial_number,
            $phone_number,
            $prefer_phone_contact,
            $prefer_sms_contact,
            $usb_cable,
            $power_cable,
            $nip,
            $statusUrl,
            $option_expertise,
            $option_repair,
            $option_supplies
        ]);
        
        $repairId = $pdo->lastInsertId();
        
        // Add initial history entry
        $stmt = $pdo->prepare("
            INSERT INTO repair_history (repair_id, status, notes, created_at)
            VALUES (?, 'W trakcie', ?, NOW())
        ");
        
        // Build notes message including contact preferences
        $notes = "Przyjęto do naprawy";
        $contact_preferences = [];
        
        if ($prefer_phone_contact) {
            $contact_preferences[] = "telefonicznie";
        }
        if ($prefer_sms_contact) {
            $contact_preferences[] = "SMS";
        }
        
        if (!empty($contact_preferences)) {
            $notes .= ". Preferowany kontakt: " . implode(", ", $contact_preferences);
        }
        
        $stmt->execute([$repairId, $notes]);
        
        // Log the action
        logAction("Added new repair ID: $repairId | Model: $device_model | Serial: $serial_number | Phone: $phone_number | Preferences: " . implode(", ", $contact_preferences));
        
        // Commit transaction
        $pdo->commit();
        
        // Redirect to repair details page with the repair ID
        header("Location: repair_details.php?id=" . $repairId);
        exit;
        
    } catch(PDOException $e) {
        // Rollback transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Log error
        logAction("Error adding repair: " . $e->getMessage());
        
        $_SESSION['error_message'] = "Błąd podczas dodawania naprawy: " . $e->getMessage();
        header("Location: add_repair.php");
        exit;
    }
} else {
    header("Location: add_repair.php");
    exit;
}
?>