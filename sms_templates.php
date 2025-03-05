<?php
/**
 * SMS Templates Management
 * Last updated: 2025-03-05 14:18:02
 * By: dj2chile
 */

// Strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

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
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Initialize variables
$success_message = '';
$error_message = '';
$debug_mode = false; // Set to true to enable debug messages

// Ensure settings table exists
try {
    $pdo->query("CREATE TABLE IF NOT EXISTS `settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `setting_name` varchar(50) NOT NULL,
        `setting_value` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `setting_name` (`setting_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    error_log("Error ensuring settings table exists: " . $e->getMessage());
}

// Ensure SMS messages table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'sms_messages'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the table
        $pdo->exec("
            CREATE TABLE `sms_messages` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `status_code` varchar(50) NOT NULL,
              `message_template` text NOT NULL,
              `active` tinyint(1) NOT NULL DEFAULT 1,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `status_code` (`status_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Insert default templates
        $templates = [
            ['ready', 'Twoje urządzenie jest gotowe do odbioru. Zapraszamy do {company_name}! Status można sprawdzić tutaj: {status_page_url}'],
            ['waiting_for_parts', 'Oczekujemy na części do Twojego urządzenia. Status możesz sprawdzić tutaj: {status_page_url}'],
            ['in_progress', 'Twoje urządzenie jest w trakcie naprawy. Status możesz sprawdzić tutaj: {status_page_url}'],
            ['completed', 'Dziękujemy za skorzystanie z usług {company_name}. Status możesz sprawdzić tutaj: {status_page_url}'],
            ['default', 'Status Twojego urządzenia został zaktualizowany: {status}. Szczegóły: {status_page_url}'],
            ['new_repair', 'Przyjęliśmy Twoje urządzenie do serwisu {company_name}. Numer naprawy: {repair_id}. Status możesz sprawdzić tutaj: {status_page_url}'],
            ['welcome', 'Witamy w {company_name}. Bedzie nam milo pomoc w naprawie Twojego urzadzenia. Twoj sprzet jest w dobrych rekach.']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO sms_messages (status_code, message_template) VALUES (?, ?)");
        
        foreach ($templates as $template) {
            $stmt->execute($template);
        }
        
        $success_message = "Utworzono tabelę szablonów SMS z domyślnymi szablonami.";
    }
} catch (PDOException $e) {
    error_log("Error creating SMS templates table: " . $e->getMessage());
    $error_message = "Błąd podczas tworzenia tabeli szablonów SMS.";
}

// Load current sms_new_repair_enabled setting
$sms_new_repair_enabled = 0;
try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_name = 'sms_new_repair_enabled'");
    if ($stmt->rowCount() > 0) {
        $sms_new_repair_enabled = (int)$stmt->fetchColumn();
    } else {
        // Insert default value if not exists
        $stmt = $pdo->prepare("INSERT INTO settings (setting_name, setting_value) VALUES (?, ?)");
        $stmt->execute(['sms_new_repair_enabled', '0']);
    }
    if ($debug_mode) error_log("Initial sms_new_repair_enabled value: " . $sms_new_repair_enabled);
} catch (PDOException $e) {
    error_log("Error fetching sms_new_repair_enabled: " . $e->getMessage());
}

// Handle form submission for SMS templates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_templates'])) {
    try {
        // Process the sms_new_repair_enabled checkbox first
        $new_sms_new_repair_enabled = isset($_POST['sms_new_repair_enabled']) ? 1 : 0;
        if ($debug_mode) error_log("Form submitted. New sms_new_repair_enabled value: " . $new_sms_new_repair_enabled);
        
        try {
            // Direct SQL approach
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_name = 'sms_new_repair_enabled'");
            $result = $stmt->execute([$new_sms_new_repair_enabled]);
            if ($stmt->rowCount() == 0) {
                // If no rows updated, insert the setting
                $stmt = $pdo->prepare("INSERT INTO settings (setting_name, setting_value) VALUES ('sms_new_repair_enabled', ?)");
                $result = $stmt->execute([$new_sms_new_repair_enabled]);
            }
            $sms_new_repair_enabled = $new_sms_new_repair_enabled; // Update the value for display
            if ($debug_mode) error_log("Updated sms_new_repair_enabled result: " . ($result ? "Success" : "Failed"));
        } catch (PDOException $e) {
            error_log("Error setting sms_new_repair_enabled: " . $e->getMessage());
        }

        // Update SMS templates
        foreach ($_POST['templates'] as $status_code => $message_template) {
            $active = isset($_POST['active'][$status_code]) ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE sms_messages SET message_template = ?, active = ? WHERE status_code = ?");
            $stmt->execute([$message_template, $active, $status_code]);
        }
        
        // Log the changes
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/sms_templates_changes.log';
        $timestamp = date('Y-m-d H:i:s');
        $username = $_SESSION['username'] ?? 'unknown';
        $logMessage = "[{$timestamp}] User {$username} updated SMS templates, sms_new_repair_enabled={$new_sms_new_repair_enabled}\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        $success_message = "Szablony SMS zostały zaktualizowane pomyślnie.";
        
    } catch (PDOException $e) {
        error_log("Error updating SMS templates: " . $e->getMessage());
        $error_message = "Wystąpił błąd podczas aktualizacji szablonów SMS: " . $e->getMessage();
    }
}

// Handle form submission for contact information
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_contact'])) {
    try {
        $name = trim($_POST['name']);
        $phone_number = trim($_POST['phone_number']);
        $mobile_phone_number = trim($_POST['mobile_phone_number']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $opening_hours = trim($_POST['opening_hours']);
        
        // Check if any contacts exist
        $stmt = $pdo->query("SELECT COUNT(*) FROM contacts");
        $contactsCount = $stmt->fetchColumn();
        
        if ($contactsCount > 0) {
            // Update existing contact (first one)
            $stmt = $pdo->prepare("
                UPDATE contacts 
                SET name = ?, phone_number = ?, mobile_phone_number = ?, 
                    email = ?, address = ?, opening_hours = ?
                ORDER BY id LIMIT 1
            ");
            $stmt->execute([
                $name, $phone_number, $mobile_phone_number, 
                $email, $address, $opening_hours
            ]);
        } else {
            // Insert new contact
            $stmt = $pdo->prepare("
                INSERT INTO contacts (name, phone_number, mobile_phone_number, email, address, opening_hours)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name, $phone_number, $mobile_phone_number, 
                $email, $address, $opening_hours
            ]);
        }
        
        $success_message = "Dane kontaktowe zostały zaktualizowane pomyślnie.";
        
    } catch (PDOException $e) {
        error_log("Error updating contact info: " . $e->getMessage());
        $error_message = "Wystąpił błąd podczas aktualizacji danych kontaktowych: " . $e->getMessage();
    }
}

// Get SMS templates from database
try {
    $stmt = $pdo->query("SELECT * FROM sms_messages ORDER BY FIELD(status_code, 'welcome', 'new_repair', 'in_progress', 'waiting_for_parts', 'ready', 'completed', 'default')");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching SMS templates: " . $e->getMessage());
    $error_message = "Wystąpił błąd podczas pobierania szablonów SMS.";
    $templates = [];
}

// Get contact info
try {
    $stmt = $pdo->query("SELECT * FROM contacts ORDER BY id LIMIT 1");
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching contact info: " . $e->getMessage());
    $contact = [
        'name' => 'TOP-SERWISs',
        'phone_number' => '696123891',
        'mobile_phone_number' => '696123891',
        'email' => 'info@top-serwis.org',
        'address' => 'ul. Szczecińska 8-10/15, 75-135 Koszalin',
        'opening_hours' => 'od poniedziałku do piątku 8:00 do 16:00'
    ];
}

// Get company settings
$settings = getCompanySettings($pdo);
// Ensure the sms_new_repair_enabled setting is included
$settings['sms_new_repair_enabled'] = $sms_new_repair_enabled;

// Define status code labels for better UI
$status_labels = [
    'welcome' => 'Wiadomość powitalna',
    'ready' => 'Gotowe do odbioru',
    'waiting_for_parts' => 'Oczekiwanie na części',
    'in_progress' => 'W trakcie naprawy',
    'completed' => 'Zakończone/Odebrane',
    'new_repair' => 'Nowa naprawa',
    'default' => 'Domyślny szablon'
];

// Define available placeholders for templates
$placeholders = [
    '{company_name}' => 'Nazwa firmy',
    '{status_page_url}' => 'Link do strony statusu',
    '{repair_id}' => 'Numer naprawy',
    '{status}' => 'Status naprawy',
    '{name}' => 'Nazwa firmy (z kontaktów)',
    '{phone_number}' => 'Numer telefonu (z kontaktów)',
    '{mobile_phone_number}' => 'Numer telefonu komórkowego (z kontaktów)',
    '{email}' => 'Email (z kontaktów)',
    '{address}' => 'Adres (z kontaktów)',
    '{opening_hours}' => 'Godziny otwarcia (z kontaktów)'
];

// Format domain URL properly (remove protocol if present)
$domain = $settings['domain_url'] ?? 'top-serwis.org/repairs/';
$domain = preg_replace('#^https?://#', '', $domain); // Remove http:// or https:// if present

// Get active tab
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'templates';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szablony SMS - TOP SERWIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <!-- Additional styles -->
    <style>
        .toast {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            z-index: 50;
            animation: toast-in-right 0.7s;
        }
        @keyframes toast-in-right {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        .tab-active {
            border-bottom: 2px solid #db2777;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-900">
                Szablony wiadomości SMS
            </h1>
            <div class="text-sm text-gray-500">
                Ostatnia aktualizacja: 2025-03-05 14:18:02 (dj2chile)
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if($success_message): ?>
            <div id="success-toast" class="toast bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
                <button onclick="document.getElementById('success-toast').style.display = 'none'" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Zamknij</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div id="error-toast" class="toast bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                <button onclick="document.getElementById('error-toast').style.display = 'none'" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Zamknij</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <div class="flex -mb-px">
                <a href="?tab=templates" class="py-2 px-4 text-center text-gray-700 hover:text-gray-900 <?php echo ($activeTab == 'templates') ? 'tab-active' : ''; ?>">
                    Szablony SMS
                </a>
                <a href="?tab=contacts" class="py-2 px-4 text-center text-gray-700 hover:text-gray-900 <?php echo ($activeTab == 'contacts') ? 'tab-active' : ''; ?>">
                    Dane kontaktowe
                </a>
            </div>
        </div>
        
        <!-- Tab Content -->
        <?php if($activeTab == 'templates'): ?>
            <!-- SMS Templates Tab -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <form action="?tab=templates" method="POST" class="space-y-6" id="smsTemplatesForm">
                        <!-- General SMS Settings -->
                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="text-md font-medium text-gray-700 mb-4">Ustawienia ogólne</h4>
                            
                            <div class="flex items-center">
                                <input type="checkbox" id="sms_new_repair_enabled" name="sms_new_repair_enabled" value="1"
                                       <?php echo ($sms_new_repair_enabled == 1) ? 'checked' : ''; ?> 
                                       class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                                <label for="sms_new_repair_enabled" class="ml-2 block text-sm text-gray-900">
                                    Wysyłaj SMS przy dodaniu nowej naprawy
                                </label>
                            </div>
                            
                            <!-- Debug info - remove in production -->
                            <?php if($debug_mode): ?>
                            <div class="mt-2 text-xs text-gray-500">
                                Current value: <?php echo $sms_new_repair_enabled; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Available placeholders info -->
                        <div class="bg-blue-50 p-4 rounded-md">
                            <h4 class="text-sm font-medium text-blue-700 mb-2">Dostępne znaczniki do użycia w szablonach:</h4>
                            <ul class="list-disc pl-5 text-sm text-blue-600 grid grid-cols-1 md:grid-cols-2">
                                <?php foreach ($placeholders as $placeholder => $description): ?>
                                    <li><code><?php echo htmlspecialchars($placeholder); ?></code> - <?php echo htmlspecialchars($description); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <!-- SMS Templates -->
                        <?php foreach ($templates as $template): ?>
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="active_<?php echo htmlspecialchars($template['status_code']); ?>" 
                                               name="active[<?php echo htmlspecialchars($template['status_code']); ?>]"
                                               <?php echo ($template['active'] == 1) ? 'checked' : ''; ?> 
                                               class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                                        <label for="active_<?php echo htmlspecialchars($template['status_code']); ?>" class="ml-2 block text-sm font-medium text-gray-700">
                                            <?php echo htmlspecialchars($status_labels[$template['status_code']] ?? $template['status_code']); ?>
                                        </label>
                                    </div>
                                    <span class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($template['status_code']); ?>
                                    </span>
                                </div>
                                
                                <div class="mt-2">
                                    <textarea name="templates[<?php echo htmlspecialchars($template['status_code']); ?>]" rows="3"
                                              class="shadow-sm block w-full focus:ring-pink-500 focus:border-pink-500 sm:text-sm border border-gray-300 rounded-md"
                                              placeholder="Treść szablonu wiadomości"><?php echo htmlspecialchars($template['message_template']); ?></textarea>
                                </div>
                                
                                <!-- Template preview button -->
                                <div class="mt-1">
                                    <button type="button" class="preview-btn text-sm text-blue-600 hover:text-blue-800" 
                                            data-template="<?php echo htmlspecialchars($template['status_code']); ?>">
                                        Podgląd z danymi testowymi
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="pt-5">
                            <div class="flex justify-end">
                                <a href="index.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Anuluj
                                </a>
                                <button type="submit" name="update_templates"
                                        class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                    Zapisz szablony
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Contact Information Tab -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Dane kontaktowe do szablonów SMS</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Te dane będą używane jako zmienne w szablonach SMS. Można użyć znaczników {name}, {phone_number}, {mobile_phone_number}, {email}, {address} oraz {opening_hours}.
                    </p>
                    
                    <form action="?tab=contacts" method="POST" class="space-y-6" id="contactForm">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Nazwa firmy</label>
                            <input type="text" name="name" id="name" 
                                   value="<?php echo htmlspecialchars($contact['name'] ?? 'TOP-SERWISs'); ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                        </div>
                    
                        <div>
                            <label for="phone_number" class="block text-sm font-medium text-gray-700">Numer telefonu stacjonarnego</label>
                            <input type="text" name="phone_number" id="phone_number" 
                                   value="<?php echo htmlspecialchars($contact['phone_number'] ?? '696123891'); ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="mobile_phone_number" class="block text-sm font-medium text-gray-700">Numer telefonu komórkowego</label>
                            <input type="text" name="mobile_phone_number" id="mobile_phone_number" 
                                   value="<?php echo htmlspecialchars($contact['mobile_phone_number'] ?? '696123891'); ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="email" 
                                   value="<?php echo htmlspecialchars($contact['email'] ?? 'info@top-serwis.org'); ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700">Adres</label>
                            <textarea name="address" id="address" rows="2"
                                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"><?php echo htmlspecialchars($contact['address'] ?? 'ul. Szczecińska 8-10/15, 75-135 Koszalin'); ?></textarea>
                        </div>
                        
                        <div>
                            <label for="opening_hours" class="block text-sm font-medium text-gray-700">Godziny otwarcia</label>
                            <textarea name="opening_hours" id="opening_hours" rows="2"
                                      class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"><?php echo htmlspecialchars($contact['opening_hours'] ?? 'od poniedziałku do piątku 8:00 do 16:00'); ?></textarea>
                            <p class="mt-1 text-xs text-gray-500">Np. Poniedziałek-Piątek: 9:00-17:00, Sobota: 9:00-13:00</p>
                        </div>
                        
                        <div class="pt-5">
                            <div class="flex justify-end">
                                <a href="?tab=templates" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Wróć do szablonów
                                </a>
                                <button type="submit" name="update_contact"
                                        class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                    Zapisz dane kontaktowe
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Preview Modal -->
        <div id="previewModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="previewTitle">
                                    Podgląd wiadomości SMS
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Tak będzie wyglądać wiadomość z przykładowymi danymi:
                                    </p>
                                    <div class="mt-3 p-3 bg-gray-100 rounded text-sm" id="previewContent">
                                        <!-- Preview content will be inserted here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" id="closePreviewBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Zamknij
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show toast messages for 5 seconds then auto-hide
        setTimeout(() => {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                toast.style.display = 'none';
            });
        }, 5000);

        // Template preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            const previewBtns = document.querySelectorAll('.preview-btn');
            const previewModal = document.getElementById('previewModal');
            const previewTitle = document.getElementById('previewTitle');
            const previewContent = document.getElementById('previewContent');
            const closePreviewBtn = document.getElementById('closePreviewBtn');
            const form = document.getElementById('smsTemplatesForm');
            
            // Format domain correctly for status URL
            let domain = '<?php echo htmlspecialchars($domain); ?>';
            // Make sure domain doesn't have http:// or https:// prefix
            domain = domain.replace(/^https?:\/\//, '');
            
            // Add trailing slash if needed for proper URL formatting
            if (domain && !domain.endsWith('/')) {
                domain += '/';
            }
            
            // Sample data for preview
            const sampleData = {
                company_name: '<?php echo htmlspecialchars($settings['company_name'] ?? 'TOP-SERWIS'); ?>',
                status_page_url: 'https://' + domain + 'status.php?code=abc123def456',
                repair_id: '20250305-001',
                status: 'W trakcie naprawy',
                name: '<?php echo htmlspecialchars($contact['name'] ?? "TOP-SERWISs"); ?>',
                phone_number: '<?php echo htmlspecialchars($contact['phone_number'] ?? "696123891"); ?>',
                mobile_phone_number: '<?php echo htmlspecialchars($contact['mobile_phone_number'] ?? "696123891"); ?>',
                email: '<?php echo htmlspecialchars($contact['email'] ?? "info@top-serwis.org"); ?>',
                address: '<?php echo htmlspecialchars($contact['address'] ?? "ul. Szczecińska 8-10/15, 75-135 Koszalin"); ?>',
                opening_hours: '<?php echo htmlspecialchars($contact['opening_hours'] ?? "od poniedziałku do piątku 8:00 do 16:00"); ?>'
            };
            
            // Status code to label mapping for preview title
            const statusLabels = <?php echo json_encode($status_labels); ?>;
            
            previewBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const statusCode = this.getAttribute('data-template');
                    const textareaSelector = `textarea[name="templates[${statusCode}]"]`;
                    const textarea = document.querySelector(textareaSelector);
                    
                    if (textarea) {
                        let templateText = textarea.value;
                        
                        // Replace placeholders with sample data
                        Object.keys(sampleData).forEach(key => {
                            const placeholder = `{${key}}`;
                            templateText = templateText.replace(
                                new RegExp(placeholder, 'g'), 
                                sampleData[key]
                            );
                        });
                        
                        // Set modal content
                        previewTitle.textContent = `Podgląd: ${statusLabels[statusCode] || statusCode}`;
                        previewContent.textContent = templateText;
                        
                        // Show modal
                        previewModal.classList.remove('hidden');
                    }
                });
            });
            
            // Close modal
            closePreviewBtn.addEventListener('click', function() {
                previewModal.classList.add('hidden');
            });
            
            // Also close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === previewModal) {
                    previewModal.classList.add('hidden');
                }
            });
            
            // Form validation for SMS templates
            if (form) {
                form.addEventListener('submit', function(event) {
                    let hasEmptyTemplate = false;
                    
                    const textareas = document.querySelectorAll('textarea[name^="templates["]');
                    textareas.forEach(textarea => {
                        const statusCode = textarea.name.match(/templates\[(.*?)\]/)[1];
                        const isActive = document.querySelector(`input[name="active[${statusCode}]"]`).checked;
                        
                        if (isActive && textarea.value.trim() === '') {
                            hasEmptyTemplate = true;
                            textarea.classList.add('border-red-500');
                        } else {
                            textarea.classList.remove('border-red-500');
                        }
                    });
                    
                    if (hasEmptyTemplate) {
                        event.preventDefault();
                        alert('Aktywne szablony SMS nie mogą być puste. Proszę uzupełnić treść lub dezaktywować szablon.');
                    }
                });
            }
            
            // Form validation for contact form
            const contactForm = document.getElementById('contactForm');
            if (contactForm) {
                contactForm.addEventListener('submit', function(event) {
                    const name = document.getElementById('name').value.trim();
                    const phoneNumber = document.getElementById('phone_number').value.trim();
                    const mobilePhoneNumber = document.getElementById('mobile_phone_number').value.trim();
                    const email = document.getElementById('email').value.trim();
                    
                    // Basic validation
                    if (!name) {
                        event.preventDefault();
                        alert('Proszę podać nazwę firmy.');
                        document.getElementById('name').focus();
                    } else if (!phoneNumber && !mobilePhoneNumber) {
                        event.preventDefault();
                        alert('Proszę podać przynajmniej jeden numer telefonu.');
                        document.getElementById('phone_number').focus();
                    } else if (email && !isValidEmail(email)) {
                        event.preventDefault();
                        alert('Proszę podać prawidłowy adres email.');
                        document.getElementById('email').focus();
                    }
                });
                
                // Helper function to validate email
                function isValidEmail(email) {
                    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return re.test(email);
                }
            }
        });
        
        // Function to generate SMS templates links in the dashboard
        function createSmsTemplateLinks() {
            // Add links to SMS templates in appropriate places
            const statusSelects = document.querySelectorAll('select[name="status"]');
            if (statusSelects.length) {
                statusSelects.forEach(select => {
                    // Add event listener to status changes
                    select.addEventListener('change', function() {
                        const repairId = this.closest('form').querySelector('input[name="repair_id"]').value;
                        const status = this.value;
                        // Create SMS link if appropriate status
                        if (['Gotowe', 'Gotowa do odbioru', 'W trakcie', 'Oczekiwanie na części', 'Odebrane'].includes(status)) {
                            const smsLink = document.createElement('a');
                            smsLink.href = `send_status_sms.php?repair_id=${repairId}&status=${encodeURIComponent(status)}`;
                            smsLink.className = 'ml-2 text-sm text-blue-600 hover:text-blue-800';
                            smsLink.textContent = 'Wyślij SMS';
                            // Add link after the select element
                            const parentDiv = this.parentNode;
                            if (parentDiv.querySelector('.sms-link')) {
                                parentDiv.querySelector('.sms-link').remove();
                            }
                            smsLink.classList.add('sms-link');
                            parentDiv.appendChild(smsLink);
                        }
                    });
                });
            }
        }
        
        // Initialize dashboard SMS links if on dashboard page
        if (window.location.pathname.includes('dashboard') || window.location.pathname.includes('repair_details')) {
            document.addEventListener('DOMContentLoaded', createSmsTemplateLinks);
        }
    </script>

    <!-- Footer -->
    <footer class="bg-white mt-8 py-4 shadow-inner">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 text-sm">
                TOP SERWIS - System zarządzania naprawami<br>
                Ostatnia aktualizacja: 2025-03-05 14:20:14 (dj2chile)
            </p>
        </div>
    </footer>
</body>
</html>