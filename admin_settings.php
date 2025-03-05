<?php
/**
 * Admin Settings Panel
 * Central administration for system configuration
 * Last updated: 2025-03-05 14:36:53
 * By: dj2chile
 */

// Start session before any output
session_start();

// Basic configuration
require_once 'config.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Strict error reporting but hide from output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/admin.log');

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// Include functions
if (file_exists('settings_functions.php')) {
    require_once 'settings_functions.php';
} else {
    // Define basic functions if file doesn't exist
    function getCompanySettings($pdo) {
        try {
            $settings = [];
            $stmt = $pdo->query("SELECT setting_name, setting_value FROM settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_name']] = $row['setting_value'];
            }
            return $settings;
        } catch (PDOException $e) {
            error_log("Error fetching settings: " . $e->getMessage());
            return [];
        }
    }
    
    function updateSetting($pdo, $setting_name, $setting_value) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_name = ?");
            $stmt->execute([$setting_name]);
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_name = ?");
                $stmt->execute([$setting_value, $setting_name]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_name, setting_value) VALUES (?, ?)");
                $stmt->execute([$setting_name, $setting_value]);
            }
            return true;
        } catch (PDOException $e) {
            error_log("Error updating setting: " . $e->getMessage());
            return false;
        }
    }
}

// Check if user has admin privileges
$isAdmin = isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true;

// Initialize variables
$success_message = '';
$error_message = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Create the logs directory if it doesn't exist
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Ensure settings table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
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

// Handle form submissions based on tab
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // General settings form
    if (isset($_POST['update_general'])) {
        try {
            $company_name = trim($_POST['company_name']);
            $domain_url = trim($_POST['domain_url']);
            $logo_path = trim($_POST['logo_path']);
            $theme_color = trim($_POST['theme_color']);
            
            // Update settings
            updateSetting($pdo, 'company_name', $company_name);
            updateSetting($pdo, 'domain_url', $domain_url);
            updateSetting($pdo, 'logo_path', $logo_path);
            updateSetting($pdo, 'theme_color', $theme_color);
            
            $success_message = "Ustawienia ogólne zostały zaktualizowane.";
        } catch (PDOException $e) {
            error_log("Error updating general settings: " . $e->getMessage());
            $error_message = "Wystąpił błąd podczas aktualizacji ustawień ogólnych.";
        }
    }
    
    // Repair settings form
    else if (isset($_POST['update_repair'])) {
        try {
            $default_status = trim($_POST['default_status']);
            $auto_assign = isset($_POST['auto_assign']) ? 1 : 0;
            $notify_users = isset($_POST['notify_users']) ? 1 : 0;
            
            // Update settings
            updateSetting($pdo, 'default_status', $default_status);
            updateSetting($pdo, 'auto_assign', $auto_assign);
            updateSetting($pdo, 'notify_users', $notify_users);
            
            $success_message = "Ustawienia napraw zostały zaktualizowane.";
        } catch (PDOException $e) {
            error_log("Error updating repair settings: " . $e->getMessage());
            $error_message = "Wystąpił błąd podczas aktualizacji ustawień napraw.";
        }
    }
    
    // SMS Templates form
    else if (isset($_POST['update_templates'])) {
        try {
            // Ensure SMS table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_messages` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `status_code` varchar(50) NOT NULL,
                `message_template` text NOT NULL,
                `active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `status_code` (`status_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Update SMS templates
            foreach ($_POST['templates'] as $status_code => $message_template) {
                $active = isset($_POST['active'][$status_code]) ? 1 : 0;
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_messages WHERE status_code = ?");
                $stmt->execute([$status_code]);
                $exists = $stmt->fetchColumn();
                
                if ($exists) {
                    $stmt = $pdo->prepare("UPDATE sms_messages SET message_template = ?, active = ? WHERE status_code = ?");
                    $stmt->execute([$message_template, $active, $status_code]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO sms_messages (status_code, message_template, active) VALUES (?, ?, ?)");
                    $stmt->execute([$status_code, $message_template, $active]);
                }
            }
            
            $logFile = $logDir . '/sms_templates_changes.log';
            $timestamp = date('Y-m-d H:i:s');
            $username = $_SESSION['username'] ?? 'unknown';
            $logMessage = "[{$timestamp}] User {$username} updated SMS templates\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);
            
            $success_message = "Szablony SMS zostały zaktualizowane pomyślnie.";
        } catch (PDOException $e) {
            error_log("Error updating SMS templates: " . $e->getMessage());
            $error_message = "Wystąpił błąd podczas aktualizacji szablonów SMS.";
        }
    }
    
    // Contact information form
    else if (isset($_POST['update_contact'])) {
        try {
            $name = trim($_POST['name']);
            $phone_number = trim($_POST['phone_number']);
            $mobile_phone_number = trim($_POST['mobile_phone_number']);
            $email = trim($_POST['email']);
            $address = trim($_POST['address']);
            $opening_hours = trim($_POST['opening_hours']);
            
            // Ensure contacts table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS `contacts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) NOT NULL,
                `phone_number` varchar(20) DEFAULT NULL,
                `mobile_phone_number` varchar(20) DEFAULT NULL,
                `email` varchar(100) DEFAULT NULL,
                `address` text DEFAULT NULL,
                `opening_hours` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Check if any contacts exist
            $stmt = $pdo->query("SELECT COUNT(*) FROM contacts");
            $contactsCount = $stmt->fetchColumn();
            
            if ($contactsCount > 0) {
                // Update existing contact (first one)
                $stmt = $pdo->prepare("
                    UPDATE contacts 
                    SET name = ?, phone_number = ?, mobile_phone_number = ?, email = ?, address = ?, opening_hours = ?
                    ORDER BY id LIMIT 1
                ");
                $stmt->execute([
                    $name, $phone_number, $mobile_phone_number, $email, $address, $opening_hours
                ]);
            } else {
                // Insert new contact
                $stmt = $pdo->prepare("
                    INSERT INTO contacts (name, phone_number, mobile_phone_number, email, address, opening_hours)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name, $phone_number, $mobile_phone_number, $email, $address, $opening_hours
                ]);
            }
            
            $success_message = "Dane kontaktowe zostały zaktualizowane pomyślnie.";
        } catch (PDOException $e) {
            error_log("Error updating contact info: " . $e->getMessage());
            $error_message = "Wystąpił błąd podczas aktualizacji danych kontaktowych.";
        }
    }
}

// Load data based on active tab
$settings = getCompanySettings($pdo);

// Get SMS templates if on messaging tab
$templates = [];
if ($activeTab == 'messaging') {
    try {
        // Ensure SMS table exists before querying
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_messages` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `status_code` varchar(50) NOT NULL,
            `message_template` text NOT NULL,
            `active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `status_code` (`status_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Check if we need to insert default templates
        $stmt = $pdo->query("SELECT COUNT(*) FROM sms_messages");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            // Insert default templates
            $defaultTemplates = [
                ['ready', 'Twoje urządzenie jest gotowe do odbioru. Zapraszamy do {company_name}! Status można sprawdzić tutaj: {status_page_url}'],
                ['waiting_for_parts', 'Oczekujemy na części do Twojego urządzenia. Status możesz sprawdzić tutaj: {status_page_url}'],
                ['in_progress', 'Twoje urządzenie jest w trakcie naprawy. Status możesz sprawdzić tutaj: {status_page_url}'],
                ['completed', 'Dziękujemy za skorzystanie z usług {company_name}. Status możesz sprawdzić tutaj: {status_page_url}'],
                ['default', 'Status Twojego urządzenia został zaktualizowany: {status}. Szczegóły: {status_page_url}'],
                ['new_repair', 'Przyjęliśmy Twoje urządzenie do serwisu {company_name}. Numer naprawy: {repair_id}. Status możesz sprawdzić tutaj: {status_page_url}'],
                ['welcome', 'Witamy w {company_name}. Bedzie nam milo pomoc w naprawie Twojego urzadzenia. Twoj sprzet jest w dobrych rekach.']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO sms_messages (status_code, message_template) VALUES (?, ?)");
            
            foreach ($defaultTemplates as $template) {
                $stmt->execute($template);
            }
        }
        
        // Get the templates
        $stmt = $pdo->query("SELECT * FROM sms_messages ORDER BY FIELD(status_code, 'welcome', 'new_repair', 'in_progress', 'waiting_for_parts', 'ready', 'completed', 'default')");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error handling SMS templates: " . $e->getMessage());
    }
}

// Get contact info if on contact tab
$contact = [];
if ($activeTab == 'contact') {
    try {
        // Ensure contacts table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS `contacts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `phone_number` varchar(20) DEFAULT NULL,
            `mobile_phone_number` varchar(20) DEFAULT NULL,
            `email` varchar(100) DEFAULT NULL,
            `address` text DEFAULT NULL,
            `opening_hours` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $pdo->query("SELECT * FROM contacts ORDER BY id LIMIT 1");
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Default values if no contact record exists
        if (!$contact) {
            $contact = [
                'name' => $settings['company_name'] ?? 'TOP-SERWISs',
                'phone_number' => '696123891',
                'mobile_phone_number' => '696123891',
                'email' => 'info@top-serwis.org',
                'address' => 'ul. Szczecińska 8-10/15, 75-135 Koszalin',
                'opening_hours' => 'od poniedziałku do piątku 8:00 do 16:00'
            ];
        }
    } catch (PDOException $e) {
        error_log("Error fetching contact info: " . $e->getMessage());
    }
}

// Get users list if on users tab
$users = [];
if ($activeTab == 'users') {
    try {
        // Ensure users table exists
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `name` varchar(100) DEFAULT NULL,
            `email` varchar(100) DEFAULT NULL,
            `role` varchar(20) DEFAULT 'user',
            `active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $pdo->query("SELECT * FROM users ORDER BY username");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching users: " . $e->getMessage());
    }
}

// Define status code labels for SMS templates
$status_labels = [
    'welcome' => 'Wiadomość powitalna',
    'ready' => 'Gotowe do odbioru',
    'waiting_for_parts' => 'Oczekiwanie na części',
    'in_progress' => 'W trakcie naprawy',
    'completed' => 'Zakończone/Odebrane',
    'new_repair' => 'Nowa naprawa',
    'default' => 'Domyślny szablon'
];

// Define available placeholders for SMS templates
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
$domain = preg_replace('#^https?://#', '', $domain);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administracyjny - TOP SERWIS</title>
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
                Panel Administracyjny
            </h1>
            <div class="text-sm text-gray-500">
                Zalogowany jako: <?php echo htmlspecialchars($_SESSION['username']); ?> 
                <?php echo $isAdmin ? '(Administrator)' : ''; ?>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if(!$isAdmin): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            Tylko administratorzy mają dostęp do tych ustawień. Skontaktuj się z administratorem systemu.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center">
                <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    Powrót do strony głównej
                </a>
            </div>
        <?php else: ?>
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
                <div class="flex flex-wrap -mb-px">
                    <a href="?tab=general" class="py-2 px-4 text-center text-gray-700 hover:text-gray-900 <?php echo ($activeTab == 'general') ? 'tab-active' : ''; ?>">
                        Ustawienia Ogólne
                    </a>
                    <a href="?tab=repair" class="py-2 px-4 text-center text-gray-700 hover:text-gray-900 <?php echo ($activeTab == 'repair') ? 'tab-active' : ''; ?>">
                        Ustawienia Napraw
                    </a>
                    <a href="?tab=messaging" class="py-2 px-4 text-center text-gray-700 hover:text-gray-900 <?php echo ($activeTab == 'messaging') ? 'tab-active' : ''; ?>">
                        Szablony SMS
                    </a>
                    <a href="?tab=contact" class="py-2 px-4 text-center text-gray-700 hover:text-gray-900 <?php echo ($activeTab == 'contact') ? 'tab-active' : ''; ?>">
                        Dane Kontaktowe
                    </a>
                    <a href="?tab=users" class="py-2 px-4 text-center text-gray-700 hover:text-gray-900 <?php echo ($activeTab == 'users') ? 'tab-active' : ''; ?>">
                        Użytkownicy
                    </a>
                    <a href="?tab=backup" class="py-2 px-4 text-center text-gray-700 hover:text-gray-900 <?php echo ($activeTab == 'backup') ? 'tab-active' : ''; ?>">
                        Kopie Zapasowe
                    </a>
                </div>
            </div>
            
            <!-- Tab Content -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    
                    <?php if($activeTab == 'general'): ?>
                        <!-- General Settings Tab -->
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-5">Ustawienia ogólne systemu</h3>
                        
                        <form action="?tab=general" method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="company_name" class="block text-sm font-medium text-gray-700">Nazwa firmy</label>
                                    <input type="text" name="company_name" id="company_name" 
                                           value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                                </div>
                                
                                <div>
                                    <label for="domain_url" class="block text-sm font-medium text-gray-700">Adres domeny</label>
                                    <input type="text" name="domain_url" id="domain_url" 
                                           value="<?php echo htmlspecialchars($settings['domain_url'] ?? ''); ?>"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"
                                           placeholder="top-serwis.org">
                                </div>
                                
                                <div>
                                    <label for="logo_path" class="block text-sm font-medium text-gray-700">Ścieżka do logo</label>
                                    <input type="text" name="logo_path" id="logo_path" 
                                           value="<?php echo htmlspecialchars($settings['logo_path'] ?? ''); ?>"
                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"
                                           placeholder="/images/logo.png">
                                </div>
                                
                                <div>
                                    <label for="theme_color" class="block text-sm font-medium text-gray-700">Kolor motywu</label>
                                    <div class="mt-1 flex rounded-md shadow-sm">
                                        <input type="color" name="theme_color" id="theme_color" 
                                               value="<?php echo htmlspecialchars($settings['theme_color'] ?? '#db2777'); ?>"
                                               class="h-8 w-8 rounded mr-3">
                                        <input type="text" name="theme_color_hex" id="theme_color_hex" 
                                               value="<?php echo htmlspecialchars($settings['theme_color'] ?? '#db2777'); ?>"
                                               class="flex-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pt-5">
                                <div class="flex justify-end">
                                                                    <button type="submit" name="update_general" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                        Zapisz ustawienia
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                    <?php elseif($activeTab == 'repair'): ?>
                        <!-- Repair Settings Tab -->
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-5">Ustawienia napraw</h3>
                        
                        <form action="?tab=repair" method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label for="default_status" class="block text-sm font-medium text-gray-700">Domyślny status naprawy</label>
                                    <select name="default_status" id="default_status" 
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                                        <option value="Przyjęte" <?php echo ($settings['default_status'] ?? '') == 'Przyjęte' ? 'selected' : ''; ?>>Przyjęte</option>
                                        <option value="W trakcie" <?php echo ($settings['default_status'] ?? '') == 'W trakcie' ? 'selected' : ''; ?>>W trakcie</option>
                                        <option value="Oczekiwanie na części" <?php echo ($settings['default_status'] ?? '') == 'Oczekiwanie na części' ? 'selected' : ''; ?>>Oczekiwanie na części</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="auto_assign" name="auto_assign" value="1"
                                               <?php echo (isset($settings['auto_assign']) && $settings['auto_assign'] == 1) ? 'checked' : ''; ?> 
                                               class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                                        <label for="auto_assign" class="ml-2 block text-sm text-gray-900">
                                            Automatycznie przydzielaj naprawy do techników
                                        </label>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="notify_users" name="notify_users" value="1"
                                               <?php echo (isset($settings['notify_users']) && $settings['notify_users'] == 1) ? 'checked' : ''; ?> 
                                               class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                                        <label for="notify_users" class="ml-2 block text-sm text-gray-900">
                                            Powiadamiaj techników o nowych przypisaniach napraw
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="bg-yellow-50 p-4 rounded">
                                    <h4 class="text-sm font-medium text-yellow-800">Statusy napraw</h4>
                                    <p class="text-sm text-yellow-700 mt-1">Aby zarządzać statusami napraw, kategoriami i typami urządzeń, przejdź do <a href="admin_taxonomies.php" class="underline">Zarządzania taksonomią</a>.</p>
                                </div>
                            </div>
                            
                            <div class="pt-5">
                                <div class="flex justify-end">
                                    <button type="submit" name="update_repair" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                        Zapisz ustawienia
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                    <?php elseif($activeTab == 'messaging'): ?>
                        <!-- SMS Templates Tab -->
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-5">Szablony wiadomości SMS</h3>
                        
                        <form action="?tab=messaging" method="POST" class="space-y-6" id="smsTemplatesForm">
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
                                    <button type="submit" name="update_templates"
                                            class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                        Zapisz szablony
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                    <?php elseif($activeTab == 'contact'): ?>
                        <!-- Contact Information Tab -->
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-5">Dane kontaktowe firmy</h3>
                        
                        <form action="?tab=contact" method="POST" class="space-y-6" id="contactForm">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Nazwa firmy</label>
                                <input type="text" name="name" id="name" 
                                       value="<?php echo htmlspecialchars($contact['name'] ?? ''); ?>"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                            </div>
                        
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700">Numer telefonu stacjonarnego</label>
                                <input type="text" name="phone_number" id="phone_number" 
                                       value="<?php echo htmlspecialchars($contact['phone_number'] ?? ''); ?>"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                            </div>
                            
                            <div>
                                <label for="mobile_phone_number" class="block text-sm font-medium text-gray-700">Numer telefonu komórkowego</label>
                                <input type="text" name="mobile_phone_number" id="mobile_phone_number" 
                                       value="<?php echo htmlspecialchars($contact['mobile_phone_number'] ?? ''); ?>"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" id="email" 
                                       value="<?php echo htmlspecialchars($contact['email'] ?? ''); ?>"
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm">
                            </div>
                            
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700">Adres</label>
                                <textarea name="address" id="address" rows="2"
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"><?php echo htmlspecialchars($contact['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label for="opening_hours" class="block text-sm font-medium text-gray-700">Godziny otwarcia</label>
                                <textarea name="opening_hours" id="opening_hours" rows="2"
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"><?php echo htmlspecialchars($contact['opening_hours'] ?? ''); ?></textarea>
                                <p class="mt-1 text-xs text-gray-500">Np. Poniedziałek-Piątek: 9:00-17:00, Sobota: 9:00-13:00</p>
                            </div>
                            
                            <div class="pt-5">
                                <div class="flex justify-end">
                                    <button type="submit" name="update_contact"
                                            class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                        Zapisz dane kontaktowe
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                    <?php elseif($activeTab == 'users'): ?>
                        <!-- Users Management Tab -->
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-5">Zarządzanie użytkownikami</h3>
                        
                        <div class="mb-6 flex justify-end">
                            <a href="add_user.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-pink-600 hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Dodaj nowego użytkownika
                            </a>
                        </div>
                        
                        <div class="flex flex-col">
                            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Login
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Imię i Nazwisko
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Email
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Rola
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Status
                                                    </th>
                                                    <th scope="col" class="relative px-6 py-3">
                                                        <span class="sr-only">Akcje</span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php if (empty($users)): ?>
                                                    <tr>
                                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                                            Brak użytkowników w systemie.
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($users as $user): ?>
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                <?php echo htmlspecialchars($user['username']); ?>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                <?php echo htmlspecialchars($user['name'] ?? 'Nie podano'); ?>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                <?php echo htmlspecialchars($user['email'] ?? 'Nie podano'); ?>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                <?php 
                                                                $role = $user['role'] ?? 'user';
                                                                $roleLabels = [
                                                                    'admin' => 'Administrator',
                                                                    'manager' => 'Kierownik',
                                                                    'technician' => 'Technik',
                                                                    'user' => 'Użytkownik'
                                                                ];
                                                                echo htmlspecialchars($roleLabels[$role] ?? $role);
                                                                ?>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo ($user['active'] ?? 1) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                                    <?php echo ($user['active'] ?? 1) ? 'Aktywny' : 'Nieaktywny'; ?>
                                                                </span>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edytuj</a>
                                                                <?php if($user['username'] != $_SESSION['username']): ?>
                                                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Czy na pewno chcesz usunąć tego użytkownika?')">Usuń</a>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    <?php elseif($activeTab == 'backup'): ?>
                        <!-- Backup Tab -->
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-5">Kopie zapasowe bazy danych</h3>
                        
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        Regularne wykonywanie kopii zapasowych jest ważne dla bezpieczeństwa danych. Zaleca się przechowywanie kopii zapasowych w bezpiecznym miejscu.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row sm:space-x-4 mb-6">
                            <a href="backup.php?action=create" class="mb-3 sm:mb-0 inline-flex justify-center items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Utwórz kopię zapasową
                            </a>
                            
                            <a href="backup.php?action=download" class="mb-3 sm:mb-0 inline-flex justify-center items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Pobierz ostatnią kopię zapasową
                            </a>
                            
                            <label for="restore-file" class="inline-flex justify-center items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Przywróć z kopii zapasowej
                                <input type="file" id="restore-file" name="restore_file" class="hidden" onchange="document.getElementById('restore-form').submit();">
                            </label>
                        </div>
                        
                        <form id="restore-form" action="backup.php?action=restore" method="post" enctype="multipart/form-data"></form>
                        
                        <h4 class="font-medium text-gray-800 mb-3">Historia kopii zapasowych</h4>
                        
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nazwa pliku
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Data utworzenia
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Rozmiar
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Akcje</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                            Brak zapisanych kopii zapasowych.
                                        </td>
                                    </tr>
                                    <!-- Tutaj w rzeczywistej implementacji byłaby pętla wyświetlająca kopie zapasowe -->
                                </tbody>
                            </table>
                        </div>
                        
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <!-- Preview Modal for SMS Templates -->
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
        <?php endif; ?>
    </div>

    <script>
        // Show toast messages for 5 seconds then auto-hide
        setTimeout(() => {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                toast.style.display = 'none';
            });
        }, 5000);

        // Theme color input sync
        document.addEventListener('DOMContentLoaded', function() {
            const colorPicker = document.getElementById('theme_color');
            const colorHexInput = document.getElementById('theme_color_hex');
            
            if(colorPicker && colorHexInput) {
                colorPicker.addEventListener('input', (e) => {
                    colorHexInput.value = e.target.value;
                });
                
                colorHexInput.addEventListener('input', (e) => {
                    const hexColor = e.target.value;
                    if(/^#([0-9A-F]{3}){1,2}$/i.test(hexColor)) {
                        colorPicker.value = hexColor;
                    }
                });
            }
            
            // SMS Template preview functionality
            const previewBtns = document.querySelectorAll('.preview-btn');
            const previewModal = document.getElementById('previewModal');
            const previewTitle = document.getElementById('previewTitle');
            const previewContent = document.getElementById('previewContent');
            const closePreviewBtn = document.getElementById('closePreviewBtn');
            
            if(previewBtns.length && previewModal && previewTitle && previewContent && closePreviewBtn) {
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
            }
        });
    </script>

    <!-- Footer -->
    <footer class="bg-white mt-8 py-4 shadow-inner">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 text-sm">
                TOP SERWIS - System zarządzania naprawami<br>
                Ostatnia aktualizacja: 2025-03-05 14:41:46 (dj2chile)
            </p>
        </div>
    </footer>
</body>
</html>