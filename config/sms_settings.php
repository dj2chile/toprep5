<?php
session_start();
require_once 'config.php';
require_once 'settings_functions.php';

// Check if user is logged in and admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_templates'])) {
        try {
            $pdo->beginTransaction();
            
            // Update SMS templates
            foreach ($_POST['templates'] as $status_code => $message_template) {
                $active = isset($_POST['active'][$status_code]) ? 1 : 0;
                
                $stmt = $pdo->prepare("
                    UPDATE sms_messages 
                    SET message_template = ?, active = ?
                    WHERE status_code = ?
                ");
                
                $stmt->execute([$message_template, $active, $status_code]);
            }
            
            // Update SMS settings
            $sms_new_repair_enabled = isset($_POST['sms_new_repair_enabled']) ? 1 : 0;
            
            // Update company settings
            updateSetting($pdo, 'sms_new_repair_enabled', $sms_new_repair_enabled);
            
            $pdo->commit();
            $success_message = "Ustawienia SMS zostały zaktualizowane pomyślnie.";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error updating SMS settings: " . $e->getMessage());
            $error_message = "Wystąpił błąd podczas aktualizacji ustawień SMS.";
        }
    }
}

// Get SMS templates from database
try {
    $stmt = $pdo->query("SELECT * FROM sms_messages ORDER BY id");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching SMS templates: " . $e->getMessage());
    $error_message = "Wystąpił błąd podczas pobierania szablonów SMS.";
    $templates = [];
}

// Get company settings
$settings = getCompanySettings($pdo);

// Define status code labels for better UI
$status_labels = [
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
    '{status}' => 'Status naprawy'
];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia SMS - TOP SERWIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
</head>
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Szablony wiadomości SMS</h3>
                
                <form action="" method="POST" class="space-y-6">
                    <!-- General SMS Settings -->
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-md font-medium text-gray-700 mb-4">Ustawienia ogólne</h4>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="sms_new_repair_enabled" name="sms_new_repair_enabled"
                                   <?php echo (isset($settings['sms_new_repair_enabled']) && $settings['sms_new_repair_enabled'] == 1) ? 'checked' : ''; ?> 
                                   class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                            <label for="sms_new_repair_enabled" class="ml-2 block text-sm text-gray-900">
                                Wysyłaj SMS przy dodaniu nowej naprawy
                            </label>
                        </div>
                    </div>
                    
                    <!-- Available placeholders info -->
                    <div class="bg-blue-50 p-4 rounded-md">
                        <h4 class="text-sm font-medium text-blue-700 mb-2">Dostępne znaczniki do użycia w szablonach:</h4>
                        <ul class="list-disc pl-5 text-sm text-blue-600">
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
                                <textarea name="templates[<?php echo htmlspecialchars($template['status_code']);