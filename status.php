<?php
// Ustawienia błędów - w produkcji wyłącz wyświetlanie błędów
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'errors.log');

// Funkcja do logowania błędów
function logError($message) {
    error_log(date('Y-m-d H:i:s') . " - Status Error: " . $message);
}

// Zabezpieczenia
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// Połączenie z bazą danych
try {
    require_once 'config.php';
    require_once 'settings_functions.php';
} catch (Exception $e) {
    logError("Nie można załadować pliku konfiguracyjnego: " . $e->getMessage());
    displayErrorPage("Błąd konfiguracji systemu");
    exit;
}

// Pobierz ustawienia firmowe
$settings = getCompanySettings($pdo);

// Funkcja wyświetlająca stronę błędu
function displayErrorPage($message, $settings = []) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Błąd - <?= htmlspecialchars($settings['company_name'] ?? 'TOP-SERWIS') ?></title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="bg-gray-100">
        <div class="max-w-2xl mx-auto p-4 mt-10">
            <div class="text-center mb-8">
                <img src="logo.svg" alt="<?= htmlspecialchars($settings['company_name'] ?? 'TOP-SERWIS') ?>" class="h-24 mx-auto mb-2">
                <p class="text-gray-600">Status naprawy</p>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-red-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h2 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($message); ?></h2>
                <p class="text-gray-600 mb-4">Przepraszamy, nie możemy wyświetlić informacji o naprawie.</p>
                
                <!-- Przyciski do kontaktu -->
                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                    <a href="tel:<?= htmlspecialchars($settings['company_mobile'] ?? '+48696123891') ?>" 
                       class="flex items-center px-4 py-2 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition-colors duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span>Komórka</span>
                    </a>
                    
                    <a href="tel:<?= htmlspecialchars($settings['company_phone'] ?? '+48943418272') ?>" 
                       class="flex items-center px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition-colors duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span>Stacjonarny</span>
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

// Pobierz kod statusu z URL
$statusCode = isset($_GET['code']) ? trim($_GET['code']) : '';

// Sprawdź czy kod jest poprawny - tylko alfanumeryczny i ma odpowiednią długość
if (empty($statusCode) || !preg_match('/^[a-zA-Z0-9]{16,64}$/', $statusCode)) {
    logError("Nieprawidłowy kod statusu: " . $statusCode);
    displayErrorPage("Nieprawidłowy kod statusu", $settings);
    exit;
}

try {
    // Zapytanie do bazy danych
    $stmt = $pdo->prepare("
        SELECT * FROM repairs 
        WHERE status_url = ? AND status != 'Usunięty'
        LIMIT 1
    ");
    $stmt->execute([$statusCode]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        logError("Nie znaleziono naprawy dla kodu: " . $statusCode);
        displayErrorPage("Nie znaleziono naprawy", $settings);
        exit;
    }
    
    // Pobierz historię naprawy
    $stmt = $pdo->prepare("
        SELECT * FROM repair_history 
        WHERE repair_id = ? 
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$repair['id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    logError("Błąd bazy danych: " . $e->getMessage());
    displayErrorPage("Błąd serwera", $settings);
    exit;
}

// Pomocnicza funkcja do generowania klasy koloru statusu
function getStatusColorClass($status) {
    switch($status) {
        case 'Gotowe':
        case 'Gotowa do odbioru':
            return 'bg-green-100 text-green-800';
        case 'Oczekiwanie na części':
            return 'bg-orange-100 text-orange-800';
        case 'Archiwalne':
        case 'Odebrane':
            return 'bg-gray-100 text-gray-800';
        case 'W trakcie':
        default:
            return 'bg-yellow-100 text-yellow-800';
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status naprawy - <?= htmlspecialchars($settings['company_name']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        /* Custom styles to fix text truncation issues */
        .no-truncate {
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
        }
        
        /* Ensure SVG icons display correctly */
        svg {
            display: inline-block;
            vertical-align: middle;
        }
        
        /* Fix for history timeline display */
        .history-item {
            display: flex;
            align-items: flex-start;
        }
        
        /* Fix for long text in legal section */
        .legal-text {
            white-space: normal;
            word-wrap: break-word;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-2xl mx-auto p-4">
        <div class="text-center mb-8">
            <img src="logo.svg" alt="<?= htmlspecialchars($settings['company_name']) ?>" class="h-24 mx-auto mb-2">
            <p class="text-gray-600">Status naprawy</p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600">Model urządzenia</h3>
                    <p class="text-lg no-truncate"><?= htmlspecialchars($repair['device_model'] ?? 'Brak informacji') ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-600">Numer seryjny</h3>
                    <p class="text-lg no-truncate"><?= htmlspecialchars($repair['serial_number'] ?? 'Brak informacji') ?></p>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Aktualny status</h3>
                <div class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                    <?= getStatusColorClass($repair['status'] ?? '') ?>">
                    <?= htmlspecialchars($repair['status'] ?? 'Brak statusu') ?>
                </div>
            </div>

            <?php if(!empty($history)): ?>
            <div>
                <h3 class="text-sm font-semibold text-gray-600 mb-2">Historia naprawy</h3>
                <div class="space-y-3">
                    <?php foreach($history as $entry): ?>
                    <div class="history-item">
                        <div class="flex-shrink-0">
                            <div class="w-2 h-2 mt-2 rounded-full bg-blue-500"></div>
                        </div>
                        <div class="ml-4 flex-grow">
                            <p class="text-sm font-medium no-truncate">
                                <?= htmlspecialchars($entry['status'] ?? '') ?>
                            </p>
                            <?php if(!empty($entry['notes'])): ?>
                            <p class="text-sm text-gray-600 no-truncate">
                                <?= nl2br(htmlspecialchars($entry['notes'])) ?>
                            </p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500">
                                <?= !empty($entry['created_at']) ? date('d.m.Y H:i', strtotime($entry['created_at'])) : '' ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="text-center">
            <!-- Przyciski do kontaktu - nowa wersja z dwoma oddzielnymi przyciskami -->
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <!-- Przycisk do numeru komórkowego -->
                <a href="tel:<?= htmlspecialchars(formatPhoneNumber($settings['company_mobile'])) ?>" 
                   class="flex items-center px-5 py-2 bg-green-500 text-white font-semibold rounded-lg hover:bg-green-600 transition-colors duration-200 w-full sm:w-auto justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <span>Komórka<br><?= htmlspecialchars(formatPhoneDisplay($settings['company_mobile'])) ?></span>
                </a>
                
                <!-- Przycisk do numeru stacjonarnego -->
                <a href="tel:<?= htmlspecialchars(formatPhoneNumber($settings['company_phone'])) ?>" 
                   class="flex items-center px-5 py-2 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition-colors duration-200 w-full sm:w-auto justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <span>Stacjonarny<br><?= htmlspecialchars(formatPhoneDisplay($settings['company_phone'])) ?></span>
                </a>
            </div>
            
            <div class="mt-4 text-sm text-gray-600 flex flex-col items-center">
                <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($settings['company_address'] . ', ' . $settings['company_city']) ?>" 
                   target="_blank" 
                   class="flex items-center space-x-2 px-4 py-2 rounded-lg hover:bg-blue-50 transition-colors duration-200 group text-center"
                   title="Otwórz nawigację">
                    <div>
                        <p class="font-semibold group-hover:text-blue-700 transition-colors"><?= htmlspecialchars($settings['company_name']) ?></p>
                        <p class="group-hover:text-blue-600 transition-colors"><?= htmlspecialchars($settings['company_address'] . ', ' . $settings['company_city']) ?></p>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500 group-hover:text-blue-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                </a>
                <p class="text-xs text-gray-500 text-center mt-1">
                    Kliknij, aby uruchomić nawigację do serwisu
                </p>
            </div>
        </div>

        <!-- Legal information and RODO -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
            <div class="space-y-4 text-sm text-gray-700">
                <p class="font-semibold legal-text">
                    <?= htmlspecialchars($settings['diagnosis_fee_text']) ?>
                </p>
                <p class="font-semibold legal-text">
                    <?= htmlspecialchars($settings['unclaimed_equipment_text']) ?>
                </p>
                
                <div class="mt-4 text-xs">
                    <p class="mb-2 text-justify text-xs leading-tight legal-text">
                        <?= nl2br(htmlspecialchars($settings['rodo_text'])) ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Footer with version information -->
        <div class="mt-4 text-center text-xs text-gray-500">
            <p>TOP-SERWIS System v1.1 &copy; 2024-2025</p>
        </div>
    </div>
</body>
</html>