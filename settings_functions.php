<?php
/**
 * Funkcje zarządzania ustawieniami dla systemu TOP-SERWIS
 * 
 * Ten plik zawiera funkcje do pobierania, zapisywania i zarządzania
 * ustawieniami aplikacji, przechowywanymi w bazie danych.
 */

/**
 * Sprawdza czy tabela settings istnieje w bazie danych
 * 
 * @param PDO $pdo Połączenie z bazą danych
 * @param string $table Nazwa tabeli do sprawdzenia
 * @return bool Czy tabela istnieje
 */
function tableExists($pdo, $table) {
    try {
        // Bezpieczniejsza metoda sprawdzania istnienia tabeli
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error checking if table exists: " . $e->getMessage());
        return false;
    }
}

/**
 * Tworzy tabelę ustawień jeśli nie istnieje
 * 
 * @param PDO $pdo Połączenie z bazą danych
 * @return bool Czy operacja się powiodła
 */
function createSettingsTable($pdo) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(255) NOT NULL,
            `setting_value` text NOT NULL,
            `description` varchar(255) DEFAULT NULL,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        // Sprawdź czy tabela została utworzona
        if (tableExists($pdo, 'settings')) {
            error_log("Settings table created successfully");
            return true;
        } else {
            error_log("Failed to create settings table - table does not exist after creation attempt");
            return false;
        }
    } catch (PDOException $e) {
        error_log("Failed to create settings table: " . $e->getMessage());
        return false;
    }
}

/**
 * Pobiera pojedyncze ustawienie z bazy danych
 * 
 * @param PDO $pdo Połączenie z bazą danych
 * @param string $key Klucz ustawienia
 * @param mixed $default Wartość domyślna, jeśli ustawienie nie istnieje
 * @return mixed Wartość ustawienia lub wartość domyślna
 */
function getSetting($pdo, $key, $default = null) {
    try {
        // Sprawdź czy tabela istnieje
        if (!tableExists($pdo, 'settings')) {
            // Spróbuj utworzyć tabelę
            if (!createSettingsTable($pdo)) {
                return $default;
            }
        }
        
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log("Błąd podczas pobierania ustawienia '{$key}': " . $e->getMessage());
        return $default;
    }
}

/**
 * Pobiera wiele ustawień na raz, zwraca tablicę asocjacyjną
 * 
 * @param PDO $pdo Połączenie z bazą danych
 * @param array $keys Tablica kluczy ustawień
 * @param array $defaults Tablica domyślnych wartości (opcjonalna)
 * @return array Tablica ustawień
 */
function getSettings($pdo, array $keys, array $defaults = []) {
    $result = [];
    
    // Najpierw ustawiamy wartości domyślne
    foreach ($keys as $index => $key) {
        $result[$key] = isset($defaults[$key]) ? $defaults[$key] : null;
    }
    
    // Sprawdź czy tabela istnieje
    if (!tableExists($pdo, 'settings')) {
        // Spróbuj utworzyć tabelę
        if (!createSettingsTable($pdo)) {
            error_log("Could not create settings table. Returning default values.");
            return $result;
        }
    }
    
    // Pobieramy rzeczywiste wartości z bazy danych
    try {
        // Jeśli nie ma kluczy do pobrania, zwróć domyślne wartości
        if (empty($keys)) {
            return $result;
        }
        
        $placeholders = rtrim(str_repeat('?,', count($keys)), ',');
        $query = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)";
        $stmt = $pdo->prepare($query);
        $stmt->execute($keys);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        error_log("Błąd podczas pobierania ustawień: " . $e->getMessage());
    }
    
    return $result;
}

/**
 * Aktualizuje lub dodaje ustawienie w bazie danych
 * 
 * @param PDO $pdo Połączenie z bazą danych
 * @param string $key Klucz ustawienia
 * @param string $value Wartość ustawienia
 * @param string $description Opis ustawienia (opcjonalny)
 * @return bool Sukces aktualizacji
 */
function updateSetting($pdo, $key, $value, $description = null) {
    try {
        // Sprawdź czy tabela istnieje
        if (!tableExists($pdo, 'settings')) {
            // Utwórz tabelę jeśli nie istnieje
            if (!createSettingsTable($pdo)) {
                return false;
            }
        }
        
        // Najpierw sprawdzamy, czy ustawienie już istnieje
        $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            // Aktualizuj istniejące ustawienie
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            return $stmt->execute([$value, $key]);
        } else {
            // Dodaj nowe ustawienie
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
            return $stmt->execute([$key, $value, $description]);
        }
    } catch (PDOException $e) {
        error_log("Błąd podczas aktualizacji ustawienia '{$key}': " . $e->getMessage());
        return false;
    }
}

/**
 * Usuwa ustawienie z bazy danych
 * 
 * @param PDO $pdo Połączenie z bazą danych
 * @param string $key Klucz ustawienia
 * @return bool Sukces usunięcia
 */
function deleteSetting($pdo, $key) {
    try {
        // Sprawdź czy tabela istnieje
        if (!tableExists($pdo, 'settings')) {
            return false;
        }
        
        $stmt = $pdo->prepare("DELETE FROM settings WHERE setting_key = ?");
        return $stmt->execute([$key]);
    } catch (PDOException $e) {
        error_log("Błąd podczas usuwania ustawienia '{$key}': " . $e->getMessage());
        return false;
    }
}

/**
 * Pobiera wszystkie ustawienia z bazy danych
 * 
 * @param PDO $pdo Połączenie z bazą danych
 * @return array Tablica wszystkich ustawień
 */
function getAllSettings($pdo) {
    try {
        // Sprawdź czy tabela istnieje
        if (!tableExists($pdo, 'settings')) {
            // Utwórz tabelę jeśli nie istnieje
            if (!createSettingsTable($pdo)) {
                return [];
            }
        }
        
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    } catch (PDOException $e) {
        error_log("Błąd podczas pobierania wszystkich ustawień: " . $e->getMessage());
        return [];
    }
}

/**
 * Pobiera wszystkie ustawienia firmowe
 * 
 * @param PDO $pdo Połączenie z bazą danych
 * @return array Tablica z ustawieniami firmowymi
 */
function getCompanySettings($pdo) {
    $keys = [
        'company_name',
        'company_address',
        'company_city',
        'company_phone',
        'company_mobile',
        'company_email',
        'domain_url',
        'diagnosis_fee_text',
        'unclaimed_equipment_text',
        'rodo_text'
    ];
    
    $defaults = [
        'company_name' => 'TOP-SERWIS Sp. z o.o.',
        'company_address' => 'ul. Szczecińska 8-10/15',
        'company_city' => '75-135 Koszalin',
        'company_phone' => '94 341-82-72',
        'company_mobile' => '696123891',
        'company_email' => 'info@top-serwis.org',
        'domain_url' => 'https://top-serwis.org/repairs/',
        'diagnosis_fee_text' => 'Wyrażam zgodę na pobranie opłaty 40 zł za diagnozę urządzenia. Opłata nie jest pobierana w przypadku naprawy w naszym serwisie.',
        'unclaimed_equipment_text' => 'W przypadku nie odebrania sprzętu w przeciągu 3 miesięcy, sprzęt zostaje oddany do utylizacji.',
        'rodo_text' => 'W związku z rozpoczęciem stosowania z dniem 25 maja 2018 r. Rozporządzenia Parlamentu Europejskiego i Rady (UE) 2016/679 z 27 kwietnia 2016 r. w sprawie ochrony osób fizycznych w związku z przetwarzaniem danych osobowych i w sprawie swobodnego przepływu takich danych oraz uchylenia dyrektywy 95/46/WE (ogólne rozporządzenie o ochronie danych, dalej: RODO) informujemy, iż na podstawie art. 13 RODO od dnia 25 maja 2018 r. będą Pani/Panu przysługiwały określone poniżej prawa związane z przetwarzaniem Pani/Pana danych osobowych.'
    ];
    
    // Pobierz ustawienia lub wartości domyślne
    $settings = getSettings($pdo, $keys, $defaults);
    
    // Jeśli to pierwsze uruchomienie aplikacji, zapisz wartości domyślne do bazy
    foreach ($keys as $key) {
        if (isset($defaults[$key]) && !empty($defaults[$key])) {
            $description = "Ustawienie firmowe: " . $key;
            
            // Aktualizuj lub dodaj ustawienie w bazie danych
            updateSetting($pdo, $key, $settings[$key], $description);
        }
    }
    
    return $settings;
}

/**
 * Formatuje pełny adres z elementów
 * 
 * @param array $settings Tablica ustawień
 * @return string Sformatowany adres
 */
function getFormattedAddress($settings) {
    return $settings['company_address'] . ', ' . $settings['company_city'];
}

/**
 * Formatuje pełny numer telefonu z kodem kraju
 * 
 * @param string $phoneNumber Numer telefonu
 * @param string $countryCode Kod kraju (domyślnie +48 dla Polski)
 * @return string Sformatowany numer telefonu
 */
function formatPhoneNumber($phoneNumber, $countryCode = '+48') {
    // Usuwamy wszystkie nie-cyfry
    $digits = preg_replace('/\D/', '', $phoneNumber);
    
    // Jeśli numer zaczyna się od 0, usuwamy je
    if (substr($digits, 0, 1) === '0') {
        $digits = substr($digits, 1);
    }
    
    // Jeśli numer ma 9 cyfr (standardowy polski numer), dodajemy kod kraju
    if (strlen($digits) === 9) {
        return $countryCode . ' ' . $digits;
    }
    
    return $phoneNumber;
}

/**
 * Formatuje numer telefonu do wyświetlenia, dodając spacje po każdych 3 cyfrach
 * 
 * @param string $phone Numer telefonu
 * @return string Sformatowany numer telefonu
 */
function formatPhoneDisplay($phone) {
    // Usuwamy wszystkie znaki niebędące cyframi
    $digits = preg_replace('/\D/', '', $phone);
    
    // Jeśli numer jest pusty, zwracamy pusty ciąg
    if (empty($digits)) return '';
    
    // Dzielimy numer na chunki po 3 cyfry
    return wordwrap($digits, 3, ' ', true);
}
?>