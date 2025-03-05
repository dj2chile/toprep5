<?php
// Controller class for handling repair details requests

class RepairDetailsController {
    private $pdo;
    private $model;
    private $repair_id;
    private $csrf_token;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->model = new RepairDetailsModel($pdo);
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->csrf_token = $_SESSION['csrf_token'];
    }
    
    public function handleRequest() {
        // Check if user is logged in for non-ajax requests
        if(!$this->isAjaxRequest() && (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true)) {
            return [
                'status' => 'error',
                'error_message' => 'Unauthorized access',
                'redirect' => 'login.php'
            ];
        }
        
        // Get repair ID from request
        $repair_id = isset($_GET['id']) ? $_GET['id'] : null;
        
        try {
            // Validate repair ID
            $this->repair_id = $this->model->validateRepairId($repair_id);
            
            // CSRF token validation for POST requests
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $this->csrf_token) {
                    throw new Exception('Invalid CSRF token');
                }
                
                return $this->handlePostRequest();
            }
            
            // Default: Display repair details (GET request)
            return $this->getRepairDetails();
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error_message' => $e->getMessage()
            ];
        }
    }
    
    private function handlePostRequest() {
        // Handle various POST actions
        if (isset($_POST['update_status_dropdown'])) {
            return $this->updateStatus();
        }
        
        if (isset($_POST['update_option'])) {
            return $this->updateOption();
        }
        
        if (isset($_POST['update_phone'])) {
            return $this->updateAdditionalPhone();
        }
        
        if (isset($_POST['update_serial'])) {
            return $this->updateSerialNumber();
        }
        
        if (isset($_POST['update_device_model'])) {
            return $this->updateDeviceModel();
        }
        
        if (isset($_POST['update_main_phone'])) {
            return $this->updateMainPhone();
        }
        
        if (isset($_POST['update_contact_preferences'])) {
            return $this->updateContactPreferences();
        }
        
        // If no valid action is found
        return [
            'status' => 'error',
            'error_message' => 'Invalid action'
        ];
    }
    
    private function getRepairDetails() {
        try {
            // Get repair details
            $repair = $this->model->getRepair($this->repair_id);
            
            // Get repair history
            $history = $this->model->getHistory($this->repair_id);
            
            // Get related repairs
            $related_repairs = $this->model->getRelatedRepairs(
                $repair['phone_number'],
                $repair['serial_number'],
                $this->repair_id
            );
            
            // Store initial options for highlighting on UI
            $initial_options = [
                'option_expertise' => (bool) $repair['option_expertise'],
                'option_repair' => (bool) $repair['option_repair'],
                'option_supplies' => (bool) $repair['option_supplies'],
                'usb_cable' => (bool) $repair['usb_cable'],
                'power_cable' => (bool) $repair['power_cable'],
                'prefer_phone_contact' => (bool) $repair['prefer_phone_contact'],
                'prefer_sms_contact' => (bool) $repair['prefer_sms_contact']
            ];
            
            // Get status classes for styling
            $statusClasses = $this->model->getStatusClasses();
            
            // Return success response
            return [
                'status' => 'success',
                'data' => [
                    'repair' => $repair,
                    'repair_id' => $this->repair_id,
                    'history' => $history,
                    'related_repairs' => $related_repairs,
                    'initial_options' => $initial_options,
                    'csrf_token' => $this->csrf_token,
                    'statusClasses' => $statusClasses
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error_message' => $e->getMessage()
            ];
        }
    }
    
    private function updateStatus() {
        try {
            // Get status and notes from request
            $status = $_POST['new_status'] ?? '';
            $notes = $_POST['notes'] ?? '';
            $send_sms = isset($_POST['send_sms']) && $_POST['send_sms'] == 1;
            
            // Update repair status
            $this->model->updateStatus($this->repair_id, $status, $notes);
            
            // Handle SMS sending logic if needed
            if ($send_sms) {
                $this->sendStatusUpdateSMS($this->repair_id, $status);
            }
            
            // Redirect to the same page with success message
            return [
                'status' => 'success',
                'success_message' => 'Status został zaktualizowany',
                'redirect' => 'repair_details.php?id=' . $this->repair_id
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'error_message' => $e->getMessage()
            ];
        }
    }
    
    private function updateOption() {
        try {
            // Get option details from request
            $option_type = $_POST['option_type'] ?? '';
            $option_name = $_POST['option_name'] ?? '';
            $value = $_POST['value'] === 'true' ? 1 : 0;
            
            // Validate option
            $this->model->validateOption($option_type, $option_name);
            
            // Update option
            $this->model->updateOption($this->repair_id, $option_name, $value);
            
            // Log the action
            $this->logAction('Option update', $option_name);
            
            // Return success response
            return [
                'status' => 'success',
                'success' => true
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
// In the updateContactPreferences method in controllers/repair_details_controller.php
// Make sure the response is properly formatted

private function updateContactPreferences() {
    try {
        // Get contact preferences from request
        $prefer_phone_contact = isset($_POST['prefer_phone_contact']) ? 1 : 0;
        $prefer_sms_contact = isset($_POST['prefer_sms_contact']) ? 1 : 0;
        
        // Get current repair status
        $repair = $this->model->getRepair($this->repair_id);
        $current_status = $repair['status'];
        
        // Update contact preferences
        $this->model->updateContactPreferences(
            $this->repair_id, 
            $prefer_phone_contact, 
            $prefer_sms_contact,
            $current_status
        );
        
        // Log the action
        $this->logAction('Contact preferences update', 'Zaktualizowano preferencje kontaktu');
        
        // Return success response for AJAX
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'success' => true]);
            exit;
        }
        
        // Redirect with success message for non-AJAX
        return [
            'status' => 'success',
            'success_message' => 'Preferencje kontaktu zostały zaktualizowane',
            'redirect' => 'repair_details.php?id=' . $this->repair_id
        ];
        
    } catch (Exception $e) {
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        
        return [
            'status' => 'error',
            'error_message' => $e->getMessage()
        ];
    }
}
    
    private function updateAdditionalPhone() {
        try {
            // Get additional phone from request
            $additional_phone = $_POST['additional_phone'] ?? '';
            
            // Update additional phone
            $this->model->updateAdditionalPhone($this->repair_id, $additional_phone);
            
            // Return success response
            return [
                'status' => 'success',
                'success' => true
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function updateSerialNumber() {
        try {
            // Get new serial number from request
            $new_serial_number = $_POST['new_serial_number'] ?? '';
            
            // Get current repair status
            $repair = $this->model->getRepair($this->repair_id);
            $current_status = $repair['status'];
            
            // Update serial number
            $this->model->updateSerialNumber($this->repair_id, $new_serial_number, $current_status);
            
            // Return success response
            return [
                'status' => 'success',
                'success' => true
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function updateDeviceModel() {
        try {
            // Get new device model from request
            $new_device_model = $_POST['new_device_model'] ?? '';
            
            // Get current repair status
            $repair = $this->model->getRepair($this->repair_id);
            $current_status = $repair['status'];
            
            // Update device model
            $this->model->updateDeviceModel($this->repair_id, $new_device_model, $current_status);
            
            // Return success response
            return [
                'status' => 'success',
                'success' => true
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function updateMainPhone() {
        try {
            // Get new phone number from request
            $new_phone_number = $_POST['new_phone_number'] ?? '';
            
            // Validate phone number
            $phone_number = $this->model->validatePhoneNumber($new_phone_number);
            
            // Get current repair status
            $repair = $this->model->getRepair($this->repair_id);
            $current_status = $repair['status'];
            
            // Update phone number
            $this->model->updatePhoneNumber($this->repair_id, $phone_number, $current_status);
            
            // Return success response
            return [
                'status' => 'success',
                'success' => true
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function sendStatusUpdateSMS($repair_id, $status) {
        // This is a placeholder for SMS sending logic
        // Implementation would depend on the SMS service being used
        try {
            // Get repair details
            $repair = $this->model->getRepair($repair_id);
            
            // Log the SMS sending attempt
            file_put_contents(
                'logs/sms.log', 
                date('Y-m-d H:i:s') . " | Attempted to send SMS to {$repair['phone_number']} about status change to {$status}\n", 
                FILE_APPEND
            );
            
            // Return true as if SMS was sent successfully
            return true;
        } catch (Exception $e) {
            // Log the error
            file_put_contents(
                'logs/error.log', 
                date('Y-m-d H:i:s') . " | Error sending SMS: {$e->getMessage()}\n", 
                FILE_APPEND
            );
            
            // Return false to indicate failure
            return false;
        }
    }
    
    private function logAction($action, $details) {
        try {
            $username = $_SESSION['username'] ?? 'unknown';
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            file_put_contents(
                'actions.log', 
                date('Y-m-d H:i:s') . " | User: {$username} | IP: {$ip} | Action: {$action} | Repair ID: {$this->repair_id} | Details: {$details}\n", 
                FILE_APPEND
            );
        } catch (Exception $e) {
            // Silently fail logging
        }
    }
    
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}