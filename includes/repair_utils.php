<?php
/**
 * General repair utility functions
 */

/**
 * Generate a new secure status URL
 * 
 * @return string The generated status URL
 */
function generateNewStatusUrl() {
    return bin2hex(random_bytes(32));
}

/**
 * Get repair details along with history and related repairs
 * 
 * @param PDO $pdo Database connection
 * @param int $repair_id The repair ID
 * @return array An array containing repair details, history, and related repairs
 */
function getRepairDetailsWithHistory($pdo, $repair_id) {
    // Fetch repair details
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

    // Fetch repair history
    $stmt = $pdo->prepare("
        SELECT * FROM repair_history 
        WHERE repair_id = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$repair_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize related repairs array
    $related_repairs = [];
    $archived_history = [];

    // Fetch archived repairs with same phone number
    if (!empty($repair['phone_number'])) {
        $stmt = $pdo->prepare("
            SELECT r.id, r.device_model, r.serial_number, r.status, 
                   rh.status AS history_status, rh.notes, MAX(rh.created_at) AS created_at
            FROM repairs r
            JOIN repair_history rh ON r.id = rh.repair_id
            WHERE r.phone_number = ? 
            AND r.id != ?
            GROUP BY r.id
            ORDER BY rh.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$repair['phone_number'], $repair_id]);
        $archived_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Also fetch repairs with the same serial number if it's not empty
        if (!empty($repair['serial_number'])) {
            $stmt = $pdo->prepare("
                SELECT r.id, r.device_model, r.serial_number, r.status, r.phone_number,
                       rh.status AS history_status, rh.notes, MAX(rh.created_at) AS created_at
                FROM repairs r
                JOIN repair_history rh ON r.id = rh.repair_id
                WHERE r.serial_number = ? 
                AND r.id != ?
                GROUP BY r.id
                ORDER BY rh.created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$repair['serial_number'], $repair_id]);
            $serial_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Merge with archived history to create related repairs
            $related_repairs = array_merge($archived_history, $serial_history);
            
            // Remove duplicates
            $related_repairs = array_values(array_filter($related_repairs, function($item, $key) use ($related_repairs) {
                for ($i = 0; $i < $key; $i++) {
                    if ($related_repairs[$i]['id'] === $item['id']) {
                        return false;
                    }
                }
                return true;
            }, ARRAY_FILTER_USE_BOTH));
        } else {
            $related_repairs = $archived_history;
        }
    }

    return [
        'repair' => $repair,
        'history' => $history,
        'related_repairs' => $related_repairs
    ];
}