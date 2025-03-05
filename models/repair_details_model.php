<?php
// Model class for repair details - handles data operations

class RepairDetailsModel {
    private $pdo;
    
    const VALID_STATUSES = [
        'W trakcie', 
        'Oczekiwanie na części', 
        'Gotowe', 
        'Gotowa do odbioru', 
        'Odebrane',
        'Archiwalne'
    ];

    const VALID_OPTIONS = [
        'repair' => ['option_expertise', 'option_repair', 'option_supplies'],
        'cable' => ['usb_cable', 'power_cable'],
        'contact' => ['prefer_phone_contact', 'prefer_sms_contact'] // Added new option type
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Validate repair ID
    public function validateRepairId($repair_id) {
        if (!$repair_id) {
            throw new Exception('Missing repair ID');
        }
        
        // Split the ID if it contains additional data (e.g., "679:369")
        $id_parts = explode(':', $repair_id);
        $id = $id_parts[0];
        
        if (!is_numeric($id)) {
            throw new Exception('Invalid repair ID');
        }
        
        return (int)$id;
    }
    
    // Get repair details
    public function getRepair($repair_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM repairs WHERE id = ? AND status != 'Usunięty'");
        $stmt->execute([$repair_id]);
        $repair = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$repair) {
            throw new Exception("Nie znaleziono naprawy o ID: " . $repair_id);
        }
        
        return $repair;
    }
    
    // Get repair history
    public function getHistory($repair_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM repair_history 
            WHERE repair_id = ? 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute([$repair_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get related repairs
    public function getRelatedRepairs($phone_number, $serial_number, $repair_id) {
        $related_repairs = [];
        
        if (!empty($phone_number)) {
            $stmt = $this->pdo->prepare("
                SELECT r.id, r.device_model, r.serial_number, r.status, 
                       r.prefer_phone_contact, r.prefer_sms_contact,
                       rh.status AS history_status, rh.notes, MAX(rh.created_at) AS created_at
                FROM repairs r
                JOIN repair_history rh ON r.id = rh.repair_id
                WHERE r.phone_number = ? 
                AND r.id != ?
                GROUP BY r.id
                ORDER BY rh.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$phone_number, $repair_id]);
            $related_repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Also fetch repairs with the same serial number if it's not empty
            if (!empty($serial_number)) {
                $stmt = $this->pdo->prepare("
                    SELECT r.id, r.device_model, r.serial_number, r.status, r.phone_number,
                           r.prefer_phone_contact, r.prefer_sms_contact,
                           rh.status AS history_status, rh.notes, MAX(rh.created_at) AS created_at
                    FROM repairs r
                    JOIN repair_history rh ON r.id = rh.repair_id
                    WHERE r.serial_number = ? 
                    AND r.id != ?
                    GROUP BY r.id
                    ORDER BY rh.created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$serial_number, $repair_id]);
                $serial_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Merge with related repairs
                $related_repairs = array_merge($related_repairs, $serial_history);
                
                // Remove duplicates
                $related_repairs = array_values(array_filter($related_repairs, function($item, $key) use ($related_repairs) {
                    for ($i = 0; $i < $key; $i++) {
                        if ($related_repairs[$i]['id'] === $item['id']) {
                            return false;
                        }
                    }
                    return true;
                }, ARRAY_FILTER_USE_BOTH));
            }
        }
        
        return $related_repairs;
    }
    
    // Update option 
    public function updateOption($repair_id, $option_name, $value) {
        $stmt = $this->pdo->prepare("UPDATE repairs SET {$option_name} = ? WHERE id = ?");
        return $stmt->execute([$value, $repair_id]);
    }
    
    // Update additional phone number
    public function updateAdditionalPhone($repair_id, $phone) {
        $stmt = $this->pdo->prepare("UPDATE repairs SET additional_phone = ? WHERE id = ?");
        return $stmt->execute([$phone, $repair_id]);
    }
    
    // Update serial number
    public function updateSerialNumber($repair_id, $serial_number, $current_status) {
        // Begin transaction
        $this->pdo->beginTransaction();
        
        try {
            // Update serial number
            $stmt = $this->pdo->prepare("UPDATE repairs SET serial_number = ? WHERE id = ?");
            $stmt->execute([$serial_number, $repair_id]);
            
            // Add history entry
            $stmt = $this->pdo->prepare("INSERT INTO repair_history (repair_id, status, notes, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                $repair_id, 
                $current_status, 
                "Zaktualizowano numer seryjny na: {$serial_number}"
            ]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    // Update device model
    public function updateDeviceModel($repair_id, $device_model, $current_status) {
        // Begin transaction
        $this->pdo->beginTransaction();
        
        try {
            // Update device model
            $stmt = $this->pdo->prepare("UPDATE repairs SET device_model = ? WHERE id = ?");
            $stmt->execute([$device_model, $repair_id]);
            
            // Add history entry
            $stmt = $this->pdo->prepare("INSERT INTO repair_history (repair_id, status, notes, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                $repair_id, 
                $current_status, 
                "Zaktualizowano model urządzenia na: {$device_model}"
            ]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    // Update main phone number
    public function updatePhoneNumber($repair_id, $phone_number, $current_status) {
        // Begin transaction
        $this->pdo->beginTransaction();
        
        try {
            // Update phone number
            $stmt = $this->pdo->prepare("UPDATE repairs SET phone_number = ? WHERE id = ?");
            $stmt->execute([$phone_number, $repair_id]);
            
            // Add history entry
            $stmt = $this->pdo->prepare("INSERT INTO repair_history (repair_id, status, notes, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                $repair_id, 
                $current_status, 
                "Zaktualizowano numer telefonu na: {$phone_number}"
            ]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    // Update contact preferences
    public function updateContactPreferences($repair_id, $prefer_phone_contact, $prefer_sms_contact, $current_status) {
        // Begin transaction
        $this->pdo->beginTransaction();
        
        try {
            // Update contact preferences
            $stmt = $this->pdo->prepare("UPDATE repairs SET prefer_phone_contact = ?, prefer_sms_contact = ? WHERE id = ?");
            $stmt->execute([$prefer_phone_contact, $prefer_sms_contact, $repair_id]);
            
            // Prepare note for history
            $contact_methods = [];
            if ($prefer_phone_contact) $contact_methods[] = "telefonicznie";
            if ($prefer_sms_contact) $contact_methods[] = "SMS";
            
            $note = empty($contact_methods) 
                  ? "Usunięto preferencje kontaktu" 
                  : "Ustawiono preferowane metody kontaktu: " . implode(", ", $contact_methods);
            
            // Add history entry
            $stmt = $this->pdo->prepare("INSERT INTO repair_history (repair_id, status, notes, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                $repair_id, 
                $current_status, 
                $note
            ]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    // Update repair status
    public function updateStatus($repair_id, $status, $notes) {
        // Validate status
        if (!in_array($status, self::VALID_STATUSES)) {
            throw new Exception("Nieprawidłowy status");
        }
        
        // Begin transaction
        $this->pdo->beginTransaction();
        
        try {
            // Update repair status
            $stmt = $this->pdo->prepare("UPDATE repairs SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $repair_id]);
            
            // Add history entry
            $stmt = $this->pdo->prepare("INSERT INTO repair_history (repair_id, status, notes, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$repair_id, $status, $notes]);
            
            // Commit transaction
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    // Get status classes for styling
    public function getStatusClasses() {
        return [
            'Gotowe' => 'bg-green-100 text-green-800',
            'Gotowa do odbioru' => 'bg-green-100 text-green-800',
            'Oczekiwanie na części' => 'bg-orange-100 text-orange-800',
            'Archiwalne' => 'bg-gray-100 text-gray-800',
            'Odebrane' => 'bg-purple-100 text-purple-800',
            'W trakcie' => 'bg-yellow-100 text-yellow-800'
        ];
    }
    
    // Validate option
    public function validateOption($option_type, $option_name) {
        if (!isset(self::VALID_OPTIONS[$option_type]) || !in_array($option_name, self::VALID_OPTIONS[$option_type])) {
            throw new Exception("Nieprawidłowa opcja");
        }
        return true;
    }
    
    // Validate phone number
    public function validatePhoneNumber($phone) {
        $cleaned_phone = preg_replace('/\D/', '', $phone);
        
        if (!empty($cleaned_phone) && strlen($cleaned_phone) !== 9) {
            throw new Exception("Numer telefonu musi zawierać dokładnie 9 cyfr");
        }
        
        return $cleaned_phone ?: null;
    }
}