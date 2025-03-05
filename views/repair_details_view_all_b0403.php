<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły naprawy - TOP SERWIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        /* Style dla rozwijanej listy statusów */
        .status-dropdown {
            display: none;
            position: absolute;
            z-index: 1000;
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            min-width: 200px;
            left: 0;
            top: 100%;
            margin-top: 4px;
        }
        
        .status-cell {
            position: relative;
        }
        
        .status-label {
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-dropdown.active {
            display: block;
        }
        
        /* Animacja dla wskaźnika rozwijania */
        .chevron-icon {
            transition: transform 0.2s ease;
        }
        
        .status-dropdown.active + .chevron-icon {
            transform: rotate(180deg);
        }
        
        /* Loader */
        .loading-indicator {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            border: 0.25rem solid #f3f3f3;
            border-top: 0.25rem solid #ec4899;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-pink-500">TOP SERWIS</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-gray-900">
                        Powrót do listy
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Komunikaty błędów i powodzeń -->
        <?php if($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if(empty($repair)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                Nie znaleziono naprawy
            </div>
        <?php else: ?>
            <div class="mb-6">
                <a href="print_repair.php?id=<?= $repair_id ?>" 
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z" />
                    </svg>
                    Drukuj potwierdzenie
                </a>
            </div>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Lewa kolumna -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Informacje o naprawie</h3>
                            <dl class="mt-4 space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Model urządzenia</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= htmlspecialchars($repair['device_model'] ?? 'Brak danych') ?>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Numer seryjny</dt>
                                    <dd class="mt-1 text-sm text-gray-900 flex items-center">
                                        <span><?= htmlspecialchars($repair['serial_number'] ?? 'Brak danych') ?></span>
                                        <button onclick="toggleSerialEdit()" class="ml-2 text-sm text-blue-600 hover:text-blue-800">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                    </dd>
                                    <div id="serial-edit-form" class="mt-2 hidden">
                                        <form onsubmit="return updateSerial(event)">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <div class="flex items-center">
                                                <input type="text" name="new_serial_number" value="<?= htmlspecialchars($repair['serial_number'] ?? '') ?>" 
                                                       class="shadow-sm focus:ring-pink-500 focus:border-pink-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                                <button type="submit" class="ml-2 inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                                                    Zapisz
                                                </button>
                                                <button type="button" onclick="toggleSerialEdit()" class="ml-1 inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                    Anuluj
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Kontakt</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <a href="tel:<?= htmlspecialchars($repair['phone_number'] ?? '') ?>" 
                                               class="text-pink-600 hover:text-pink-800">
                                                <?= htmlspecialchars($repair['phone_number'] ?? 'Brak numeru') ?>
                                            </a>
                                            <button onclick="composeSms('<?= htmlspecialchars($repair['phone_number'] ?? '') ?>')" 
                                                    class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm hover:bg-blue-200">
                                                SMS
                                            </button>
                                            <?php if(empty($repair['additional_phone'])): ?>
                                                <button onclick="toggleAdditionalPhone(true)" 
                                                        class="bg-pink-100 text-pink-800 px-2 py-1 rounded-full text-sm hover:bg-pink-200">
                                                    +
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Formularz dodatkowego numeru (domyślnie ukryty) -->
                                        <div id="additionalPhoneForm" class="mt-4 <?= empty($repair['additional_phone']) ? 'hidden' : '' ?>">
                                            <form method="POST" onsubmit="return handleAdditionalPhone(event)">
                                                <input type="hidden" name="update_phone" value="1">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                <div class="flex items-center space-x-2">
                                                    <input type="tel" 
                                                           name="additional_phone" 
                                                           id="additional_phone"
                                                           pattern="\d{9}"
                                                           placeholder="Dodatkowy numer (9 cyfr)"
                                                           value="<?= htmlspecialchars($repair['additional_phone'] ?? '') ?>"
                                                           class="flex-1 shadow-sm focus:ring-pink-500 focus:border-pink-500 sm:text-sm border-gray-300 rounded-md p-2"/>
                                                    <button type="submit" 
                                                            class="bg-pink-100 text-pink-800 px-3 py-2 rounded-md text-sm hover:bg-pink-200">
                                                        Zapisz
                                                    </button>
                                                    <button type="button" 
                                                            onclick="toggleAdditionalPhone(false)"
                                                            class="bg-gray-100 text-gray-800 px-3 py-2 rounded-md text-sm hover:bg-gray-200">
                                                        Anuluj
                                                    </button>
                                                </div>
                                            </form>
                                        </div>

                                        <?php if(!empty($repair['additional_phone'])): ?>
                                            <div id="additionalPhoneDisplay" class="mt-2 flex items-center space-x-2">
                                                <a href="tel:<?= htmlspecialchars($repair['additional_phone']) ?>" 
                                                   class="text-pink-600 hover:text-pink-800">
                                                    <?= htmlspecialchars($repair['additional_phone']) ?>
                                                </a>
                                                <button onclick="composeSms('<?= htmlspecialchars($repair['additional_phone']) ?>')" 
                                                        class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm hover:bg-blue-200">
                                                    SMS
                                                </button>
                                                <button onclick="toggleAdditionalPhone(true)" 
                                                        class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-sm hover:bg-gray-200">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </dd>
                                </div>

                                <!-- Status naprawy z rozwijaną listą -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Aktualny status</dt>
                                    <dd class="mt-1 status-cell">
                                        <span class="status-label px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                              <?= $statusClasses[$repair['status'] ?? 'W trakcie'] ?>"
                                              onclick="toggleStatusDropdown(event, <?= $repair_id ?>)">
                                            <?= htmlspecialchars($repair['status'] ?? 'W trakcie') ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-4 w-4 chevron-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </span>
                                        <div id="status-dropdown-<?= $repair_id ?>" class="status-dropdown">
                                            <?php foreach(VALID_STATUSES as $status): 
                                                if($status !== ($repair['status'] ?? '')): ?>
                                                <button type="button"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        onclick="showStatusUpdatePanel('<?= htmlspecialchars($status) ?>')">
                                                    <?= htmlspecialchars($status) ?>
                                                </button>
                                            <?php endif; endforeach; ?>
                                        </div>
                                    </dd>
                                </div>

                                <!-- Panel zmiany statusu (domyślnie ukryty) -->
                                <div id="status-update-panel" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2" id="status-update-title">Zmień status</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="status-notes" class="block text-sm font-medium text-gray-700">
                                                Dodatkowe informacje (opcjonalnie)
                                            </label>
                                            <div class="mt-1 relative">
                                                <textarea id="status-notes" rows="3" 
                                                        class="shadow-sm focus:ring-pink-500 focus:border-pink-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
                                                <div class="absolute right-2 bottom-2 text-xs text-gray-400">
                                                    <span id="char-counter">0</span>/500
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center">
                                            <input id="send-sms-checkbox" type="checkbox" class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                                            <label for="send-sms-checkbox" class="ml-2 block text-sm text-gray-700">
                                                Wyślij SMS z powiadomieniem
                                            </label>
                                        </div>
                                        
                                        <div class="flex justify-end space-x-2">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none"
                                                   onclick="hideStatusUpdatePanel()">
                                                Anuluj
                                            </button>
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm leading-4 font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700 focus:outline-none"
                                                   onclick="confirmStatusUpdate()">
                                                Zapisz zmiany
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Przewody i opcje naprawy -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 mb-2">Przewody</dt>
                                        <dd class="space-y-2">
                                            <button type="button"
                                                    onclick="toggleOption('cable', 'usb_cable', this)"
                                                    class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= $repair['usb_cable'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-500' ?>"
                                                    data-active="<?= $repair['usb_cable'] ? 'true' : 'false' ?>">
                                                Kabel USB
                                            </button>
                                            <button type="button"
                                                    onclick="toggleOption('cable', 'power_cable', this)"
                                                    class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= $repair['power_cable'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-500' ?>"
                                                    data-active="<?= $repair['power_cable'] ? 'true' : 'false' ?>">
                                                Kabel zasilający
                                            </button>
                                        </dd>
                                    </div>

                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 mb-2">Opcje naprawy</dt>
                                        <dd class="space-y-2">
                                            <button type="button"
                                                    onclick="toggleOption('repair', 'option_expertise', this)"
                                                    class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= $repair['option_expertise'] ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-500' ?>"
                                                    data-active="<?= $repair['option_expertise'] ? 'true' : 'false' ?>">
                                                Ekspertyza
                                            </button>
                                            <button type="button"
                                                    onclick="toggleOption('repair', 'option_repair', this)"
                                                    class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= $repair['option_repair'] ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-500' ?>"
                                                    data-active="<?= $repair['option_repair'] ? 'true' : 'false' ?>">
                                                Naprawa
                                            </button>
                                            <button type="button"
                                                    onclick="toggleOption('repair', 'option_supplies', this)"
                                                    class="w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= $repair['option_supplies'] ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-500' ?>"
                                                    data-active="<?= $repair['option_supplies'] ? 'true' : 'false' ?>">
                                                Mat. eksploatacyjne
                                            </button>
                                        </dd>
                                    </div>
                                </div>

                                <?php if(!empty($repair['nip'])): ?>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">NIP</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <?= htmlspecialchars($repair['nip']) ?>
                                        </dd>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(!empty($repair['status_url'])): ?>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Kod QR</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <button onclick="showQRCode()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd" />
                                                    <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z" />
                                                </svg>
                                                Pokaż kod QR
                                            </button>
                                            <button onclick="regenerateQRCode()" class="ml-2 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                                                Wygeneruj nowy
                                            </button>
                                        </dd>
                                    </div>
                                <?php endif; ?>
                            </dl>
                        </div>

                        <!-- Prawa kolumna -->
                        <div>
                            <!-- Historia naprawy -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Historia naprawy</h3>
                                <div class="mt-4 space-y-4 max-h-96 overflow-y-auto pr-2">
                                    <?php if(empty($history)): ?>
                                        <p class="text-sm text-gray-500">Brak historii naprawy</p>
                                    <?php else: ?>
                                        <?php foreach($history as $entry): ?>
                                            <div class="border-l-4 border-pink-400 pl-4">
                                                <div class="flex justify-between">
                                                    <span class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($entry['status'] ?? '') ?>
                                                    </span>
                                                    <span class="text-sm text-gray-500">
                                                        <?= date('d.m.Y H:i', strtotime($entry['created_at'] ?? 'now')) ?>
                                                    </span>
                                                </div>
                                                <?php if(!empty($entry['notes'])): ?>
                                                    <p class="mt-1 text-sm text-gray-600">
                                                        <?= htmlspecialchars($entry['notes']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Archiwalne naprawy -->
                            <?php if(!empty($related_repairs)): ?>
                                <div class="mt-8">
                                    <h3 class="text-lg font-medium text-gray-900">Poprzednie naprawy</h3>
                                    <div class="mt-4 space-y-4">
                                        <?php foreach($related_repairs as $entry): ?>
                                            <div class="border-l-4 border-gray-400 pl-4 hover:bg-gray-50">
                                                <div class="flex justify-between">
                                                    <span class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($entry['device_model'] ?? '') ?>
                                                    </span>
                                                    <span class="text-sm text-gray-500">
                                                        <?= date('d.m.Y H:i', strtotime($entry['created_at'] ?? 'now')) ?>
                                                    </span>
                                                </div>
                                                <div class="flex justify-between mt-1">
                                                    <span class="text-sm text-gray-700">
                                                        Status: <?= htmlspecialchars($entry['history_status'] ?? '') ?>
                                                    </span>
                                                    <a href="repair_details.php?id=<?= htmlspecialchars($entry['id'] ?? '') ?>" 
                                                       class="text-sm text-pink-600 hover:text-pink-800">
                                                        Szczegóły
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Informacje o ostatniej aktualizacji -->
                            <div class="mt-8 text-xs text-gray-500">
                                <p>Ostatnia aktualizacja: <?= $lastUpdate ?> przez <?= $lastUser ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal kodu QR -->
    <?php if(!empty($repair['status_url'])): ?>
        <div id="qrcode-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
            <div class="bg-white p-6 rounded-lg shadow-xl relative">
                <button onclick="document.getElementById('qrcode-modal').classList.add('hidden')" 
                        class="absolute top-2 right-2 text-gray-600 hover:text-gray-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Kod QR statusu naprawy</h3>
                <div id="qrcode" class="mx-auto"></div>
                <p class="mt-4 text-sm text-gray-600 text-center">
                    Zeskanuj kod, aby sprawdzić status naprawy online
                </p>
                <div class="mt-4 text-center">
                    <a href="#" onclick="copyStatusUrl()" class="text-pink-600 hover:text-pink-800">
                        Kopiuj link
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hidden input do przechowywania CSRF token -->
    <input type="hidden" id="csrf_token" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    
    <!-- Zmienna do przechowywania aktualnie wybranego statusu -->
    <script>
        let selectedNewStatus = null;
    </script>

    <!-- JavaScript dla interakcji -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // QR Code generation
            <?php if(!empty($repair['status_url'])): ?>
                if (document.getElementById("qrcode")) {
                    new QRCode(document.getElementById("qrcode"), {
                        text: '<?= htmlspecialchars("https://top-serwis.org/status.php?code={$repair['status_url']}") ?>',
                        width: 256,
                        height: 256
                    });
                }
            <?php endif; ?>

            // Character counter for notes
            const notesField = document.getElementById('status-notes');
            const charCount = document.getElementById('char-counter');
            
            if (notesField && charCount) {
                notesField.addEventListener('input', function() {
                    const count = this.value.length;
                    charCount.textContent = count;
                    
                    if (count > 500) {
                        this.value = this.value.substring(0, 500);
                        charCount.textContent = 500;
                    }
                });
            }

            // Phone number validation
            document.querySelector('input[name="additional_phone"]')?.addEventListener('input', function(e) {
                // Allow only digits
                let value = e.target.value.replace(/\D/g, '');
                
                // Limit to 9 digits
                if (value.length > 9) {
                    value = value.slice(0, 9);
                }
                
                // Update the input value
                e.target.value = value;
            });

            // Zamykanie rozwijanej listy statusów po kliknięciu poza nią
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.status-cell')) {
                    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
                        dropdown.classList.remove('active');
                    });
                }
            });
        });

        // Funkcja do pokazywania/ukrywania rozwijanej listy statusów
        function toggleStatusDropdown(event, repairId) {
            event.stopPropagation(); // Zatrzymanie propagacji zdarzenia
            
            // Zamknięcie wszystkich innych rozwijanych list
            document.querySelectorAll('.status-dropdown').forEach(dropdown => {
                if (dropdown.id !== `status-dropdown-${repairId}`) {
                    dropdown.classList.remove('active');
                }
            });
            
            // Przełączenie aktualnej rozwijanej listy
            const dropdown = document.getElementById(`status-dropdown-${repairId}`);
            if (dropdown) {
                dropdown.classList.toggle('active');
            }
        }

        // Funkcja do pokazywania panelu zmiany statusu
        function showStatusUpdatePanel(newStatus) {
            // Zapisanie wybranego statusu
            selectedNewStatus = newStatus;
            
            // Aktualizacja tytułu panelu
            const title = document.getElementById('status-update-title');
            if (title) {
                title.textContent = `Zmiana statusu na: ${newStatus}`;
            }
            
            // Zamknięcie rozwijanej listy
            document.querySelectorAll('.status-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
            
            // Pokazanie panelu
            const panel = document.getElementById('status-update-panel');
            if (panel) {
                panel.classList.remove('hidden');
                // Przewinięcie do panelu
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                
                // Ustawienie fokusu na polu notatki
                document.getElementById('status-notes').focus();
            }
        }

        // Funkcja do ukrywania panelu zmiany statusu
        function hideStatusUpdatePanel() {
            const panel = document.getElementById('status-update-panel');
            if (panel) {
                panel.classList.add('hidden');
            }
            
            // Wyczyszczenie pól
            document.getElementById('status-notes').value = '';
            document.getElementById('send-sms-checkbox').checked = false;
            document.getElementById('char-counter').textContent = '0';
            selectedNewStatus = null;
        }
        
        // Funkcja zatwierdzająca zmianę statusu
        function confirmStatusUpdate() {
            if (!selectedNewStatus) {
                return;
            }
            
            const notes = document.getElementById('status-notes').value.trim();
            const sendSms = document.getElementById('send-sms-checkbox').checked;
            const csrfToken = document.getElementById('csrf_token').value;
            
            // Pokazanie wskaźnika ładowania
            showLoader();
            
            // Przygotowanie danych formularza
            const formData = new FormData();
            formData.append('update_status_dropdown', '1');
            formData.append('new_status', selectedNewStatus);
            formData.append('notes', notes);
            formData.append('send_sms', sendSms ? '1' : '0');
            formData.append('csrf_token', csrfToken);
            
            // Wysłanie żądania AJAX
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Błąd serwera: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                hideLoader();
                
                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else if (sendSms) {
                        window.location.href = `send_status_sms.php?repair_id=<?= $repair_id ?>&status=${encodeURIComponent(selectedNewStatus)}`;
                    } else {
                        // Pokaż komunikat sukcesu i odśwież stronę
                        showMessage('Status został zaktualizowany pomyślnie.', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else {
                    throw new Error(data.error || 'Wystąpił nieznany błąd podczas aktualizacji statusu.');
                }
            })
            .catch(error => {
                hideLoader();
                showMessage(error.message, 'error');
                console.error('Error:', error);
            });
        }
        
        // Funkcje dla dodatkowego numeru telefonu
        function toggleAdditionalPhone(show) {
            const form = document.getElementById('additionalPhoneForm');
            if (form) {
                if (show) {
                    form.classList.remove('hidden');
                    document.getElementById('additional_phone')?.focus();
                } else {
                    form.classList.add('hidden');
                }
            }
        }

        async function handleAdditionalPhone(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);

            showLoader();
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                // Check if the response is OK
                if (!response.ok) {
                    throw new Error(`Błąd serwera: ${response.status} ${response.statusText}`);
                }

                const data = await response.json();
                hideLoader();
                
                if (data.success) {
                    // Reload to update the display
                    showMessage('Numer telefonu został zaktualizowany.', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.error || 'Błąd podczas aktualizacji numeru');
                }
            } catch (error) {
                hideLoader();
                showMessage(error.message, 'error');
                console.error('Phone update error:', error);
            }

            return false;
        }
        
        // Funkcje dla edycji numeru seryjnego
        function toggleSerialEdit() {
            const form = document.getElementById('serial-edit-form');
            if (form) {
                form.classList.toggle('hidden');
                if (!form.classList.contains('hidden')) {
                    form.querySelector('input[name="new_serial_number"]').focus();
                }
            }
        }
        
        async function updateSerial(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            formData.append('update_serial', '1');
            
            const serialNumber = formData.get('new_serial_number');
            if (!serialNumber.trim()) {
                showMessage('Numer seryjny nie może być pusty', 'error');
                return false;
            }
            
            showLoader();
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`Błąd serwera: ${response.status} ${response.statusText}`);
                }
                
                const data = await response.json();
                hideLoader();
                
                if (data.success) {
                    showMessage('Numer seryjny został zaktualizowany.', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.error || 'Błąd podczas aktualizacji numeru seryjnego');
                }
            } catch (error) {
                hideLoader();
                showMessage(error.message, 'error');
                console.error('Serial update error:', error);
            }
            
            return false;
        }
        
        // Funkcje dla opcji naprawy
        function toggleOption(optionType, optionName, button) {
            const currentValue = button.dataset.active === 'true';
            const newValue = !currentValue;
            
            // Optymistyczna aktualizacja UI
            button.dataset.active = String(newValue);
            updateButtonStyle(button, optionType, newValue);

            // Wysłanie aktualizacji do serwera
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `update_option=1&option_type=${encodeURIComponent(optionType)}&option_name=${encodeURIComponent(optionName)}&value=${newValue}&csrf_token=${encodeURIComponent(document.getElementById('csrf_token').value)}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Cofnij zmiany w UI jeśli wystąpił błąd
                    button.dataset.active = String(currentValue);
                    updateButtonStyle(button, optionType, currentValue);
                    showMessage('Nie udało się zaktualizować opcji.', 'error');
                }
            })
            .catch(error => {
                // Cofnij zmiany w UI w przypadku błędu
                button.dataset.active = String(currentValue);
                updateButtonStyle(button, optionType, currentValue);
                console.error('Error:', error);
                showMessage('Wystąpił błąd podczas aktualizacji opcji.', 'error');
            });
        }

        function updateButtonStyle(button, optionType, isActive) {
            const baseClasses = "w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200";
            if (isActive) {
                button.className = `${baseClasses} ${
                    optionType === 'repair' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'
                }`;
            } else {
                button.className = `${baseClasses} bg-gray-100 text-gray-500`;
            }
        }
        
        // Funkcje dla kodu QR
        function showQRCode() {
            const modal = document.getElementById('qrcode-modal');
            if (modal) {
                modal.classList.remove('hidden');
            }
        }
        
        function copyStatusUrl() {
            const url = '<?= isset($repair['status_url']) ? "https://top-serwis.org/status.php?code=" . htmlspecialchars($repair['status_url']) : "" ?>';
            
            if (url) {
                try {
                    // Tworzy tymczasowy element input
                    const tempInput = document.createElement('input');
                    tempInput.value = url;
                    document.body.appendChild(tempInput);
                    
                    // Zaznacza i kopiuje zawartość
                    tempInput.select();
                    document.execCommand('copy');
                    
                    // Usuwa tymczasowy element
                    document.body.removeChild(tempInput);
                    
                    showMessage('Link został skopiowany do schowka', 'success');
                } catch (err) {
                    showMessage('Nie udało się skopiować linku', 'error');
                }
            }
        }
        
        async function regenerateQRCode() {
            if (!confirm('Czy na pewno chcesz wygenerować nowy kod QR? Stary kod przestanie działać.')) {
                return;
            }
            
            showLoader();
            
            try {
                const formData = new FormData();
                formData.append('generate_new_status_url', '1');
                formData.append('csrf_token', document.getElementById('csrf_token').value);
                
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`Błąd serwera: ${response.status} ${response.statusText}`);
                }
                
                const data = await response.json();
                hideLoader();
                
                if (data.success) {
                    showMessage('Nowy kod QR został wygenerowany.', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    throw new Error(data.error || 'Błąd podczas generowania kodu QR');
                }
            } catch (error) {
                hideLoader();
                showMessage(error.message, 'error');
                console.error('QR code regeneration error:', error);
            }
        }

        // Funkcje pomocnicze
        function composeSms(phoneNumber, status) {
            if (!phoneNumber) return;
            
            let message;
            
            if (status) {
                switch (status) {
                    case 'Gotowe':
                    case 'Gotowa do odbioru':
                        message = 'Twoje urządzenie jest gotowe do odbioru. Zapraszamy!';
                        break;
                    case 'Oczekiwanie na części':
                        message = 'Oczekujemy na części do Twojego urządzenia.';
                        break;
                    default:
                        message = 'Status Twojego urządzenia: ' + status;
                }
            } else {
                // Jeśli status nie został podany, sprawdź aktualny status
                const currentStatus = '<?= htmlspecialchars($repair['status'] ?? '') ?>';
                if (['Gotowe', 'Gotowa do odbioru'].includes(currentStatus)) {
                    message = 'Twoje urządzenie jest gotowe do odbioru. Zapraszamy!';
                } else {
                    message = 'Status Twojego urządzenia: ' + currentStatus;
                }
            }
            
            const cleanPhone = phoneNumber.replace(/[^0-9+]/g, '');
            window.location.href = `sms:${cleanPhone}?body=${encodeURIComponent(message)}`;
        }
        
        function showLoader() {
            // Usuń istniejący loader jeśli istnieje
            hideLoader();
            
            // Stwórz nowy element loadera
            const loader = document.createElement('div');
            loader.id = 'global-loader';
            loader.className = 'loading-indicator';
            loader.innerHTML = `
                <div class="bg-white p-5 rounded-lg">
                    <div class="loading-spinner mx-auto"></div>
                    <p class="mt-3 text-center">Przetwarzanie...</p>
                </div>
            `;
            
            document.body.appendChild(loader);
        }
        
        function hideLoader() {
            const loader = document.getElementById('global-loader');
            if (loader) {
                loader.remove();
            }
        }
        
        function showMessage(message, type = 'success') {
            // Usuń wcześniejsze komunikaty
            const existingMessages = document.querySelectorAll('.message-notification');
            existingMessages.forEach(msg => {
                msg.remove();
            });
            
            // Stwórz nowy komunikat
            const notification = document.createElement('div');
            notification.className = `message-notification fixed top-4 right-4 px-4 py-3 rounded z-50 ${
                type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Usuń komunikat po czasie
            setTimeout(() => {
                notification.remove();
            }, type === 'success' ? 3000 : 5000);
        }
    </script>
</body>
</html>