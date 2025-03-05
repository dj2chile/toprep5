<?php
if (!function_exists('getRepairs')) {
    /**
     * Pobiera listę napraw z bazy danych
     * @return array Tablica napraw
     */
    function getRepairs() {
        global $pdo;
        
        try {
            $stmt = $pdo->query("
                SELECT id, device_model, serial_number, repair_options, 
                       phone_number, status, created_at 
                FROM repairs 
                ORDER BY created_at DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("[".date('Y-m-d H:i:s')."] Błąd bazy danych: ".$e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getRepairOptions')) {
    /**
     * Przetwarza opcje naprawy na tablicę
     * @param array $repair
     * @return array
     */
    function getRepairOptions($repair) {
        return array_filter(
            explode(',', $repair['repair_options'] ?? ''),
            function($option) {
                return !empty(trim($option));
            }
        );
    }
}

if (!function_exists('updateRepairStatus')) {
    /**
     * Aktualizuje status naprawy
     * @param int $repairId
     * @param string $newStatus
     * @return bool
     */
    function updateRepairStatus($repairId, $newStatus) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE repairs 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            return $stmt->execute([$newStatus, (int)$repairId]);
        } catch (PDOException $e) {
            error_log("[".date('Y-m-d H:i:s')."] Błąd aktualizacji statusu: ".$e->getMessage());
            return false;
        }
    }
}

if (!function_exists('generateSecureString')) {
    /**
     * Generuje bezpieczny losowy ciąg znaków
     * @param int $length Długość ciągu
     * @return string
     */
    function generateSecureString($length = 32) {
        try {
            $bytes = random_bytes(ceil($length / 2));
            return substr(bin2hex($bytes), 0, $length);
        } catch (Exception $e) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[random_int(0, $charactersLength - 1)];
            }
            return $randomString;
        }
    }
}

if (!function_exists('generateCsrfToken')) {
    /**
     * Generuje i zwraca token CSRF
     * @return string
     */
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = generateSecureString(32);
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCsrfToken')) {
    /**
     * Weryfikuje token CSRF
     * @param string $token
     * @return bool
     */
    function validateCsrfToken($token) {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}

if (!function_exists('sanitizeInput')) {
    /**
     * Sanityzuje dane wejściowe
     * @param mixed $data
     * @return string
     */
    function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('handleError')) {
    /**
     * Globalna obsługa błędów
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     */
    function handleError($errno, $errstr, $errfile, $errline) {
        error_log("[".date('Y-m-d H:i:s')."] Error: $errstr in $errfile on line $errline");
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            die("Wystąpił błąd systemu. Prosimy spróbować później.");
        }
    }
}

set_error_handler("handleError");