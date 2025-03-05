<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły naprawy - TOP SERWIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Fixed CSS path -->
<link rel="stylesheet" href="css/repair_details.css">
    <!-- ... rest of the head section ... -->
    <style>
        .option-selected {
            border: 2px solid #EC4899 !important; /* Pink-500 from tailwind */
            box-shadow: 0 0 0 2px rgba(236, 72, 153, 0.25);
        }
        .option-active {
            background-color: #F9A8D4 !important; /* Pink-300 from tailwind */
            color: #831843 !important; /* Pink-900 from tailwind */
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2h6z" />
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
                                <!-- Device model with edit option -->
                                <div>
                                                                        <dt class="text-sm font-medium text-gray-500">Model urządzenia</dt>
                                    <dd class="mt-1 text-sm text-gray-900 flex items-center">
                                        <span id="device-model-display"><?= htmlspecialchars($repair['device_model'] ?? 'Brak danych') ?></span>
                                        <button onclick="toggleDeviceModelEdit()" class="ml-2 text-sm text-blue-600 hover:text-blue-800">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.7 6.768l2.766 3.536z" />
                                            </svg>
                                        </button>
                                    </dd>
                                    <div id="device-model-edit-form" class="mt-2 hidden">
                                        <form onsubmit="return updateDeviceModel(event)">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <div class="flex items-center">
                                                <input type="text" name="new_device_model" value="<?= htmlspecialchars($repair['device_model'] ?? '') ?>" 
                                                       class="shadow-sm focus:ring-pink-500 focus:border-pink-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                                <button type="submit" class="ml-2 inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                                                    Zapisz
                                                </button>
                                                <button type="button" onclick="toggleDeviceModelEdit()" class="ml-1 inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                                                    Anuluj
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Serial number with edit option -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Numer seryjny</dt>
                                    <dd class="mt-1 text-sm text-gray-900 flex items-center">
                                        <span id="serial-number-display"><?= htmlspecialchars($repair['serial_number'] ?? 'Brak danych') ?></span>
                                        <button onclick="toggleSerialEdit()" class="ml-2 text-sm text-blue-600 hover:text-blue-800">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.7 6.768l2.766 3.536z" />
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
                                                <button type="button" onclick="toggleSerialEdit()" class="ml-1 inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                                                    Anuluj
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Contact information with edit option -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Kontakt</dt>
                                    <dd class="mt-1 text-sm text-gray-900 flex items-center">
                                        <span id="phone-number-display"><?= htmlspecialchars($repair['phone_number'] ?? 'Brak danych') ?></span>
                                        <button onclick="togglePhoneEdit()" class="ml-2 text-sm text-blue-600 hover:text-blue-800">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.7 6.768l2.766 3.536z" />
                                            </svg>
                                        </button>
                                    </dd>
                                    <div id="phone-edit-form" class="mt-2 hidden">
                                        <form onsubmit="return updatePhoneNumber(event)">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <div class="flex items-center">
                                                <input type="text" name="new_phone_number" value="<?= htmlspecialchars($repair['phone_number'] ?? '') ?>" 
                                                       class="shadow-sm focus:ring-pink-500 focus:border-pink-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                                <button type="submit" class="ml-2 inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                                                    Zapisz
                                                </button>
                                                <button type="button" onclick="togglePhoneEdit()" class="ml-1 inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                                                    Anuluj
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Preferred Contact Method -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Preferowany rodzaj kontaktu</dt>
                                    <dd class="mt-1 flex space-x-2">
                                        <form id="contactPreferencesForm" onsubmit="return updateContactPreferences(event)">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <div class="flex space-x-3 items-center">
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" name="prefer_phone_contact" value="1" 
                                                           class="option-button rounded border-gray-300 text-pink-600" 
                                                           data-type="contact" data-name="prefer_phone_contact" 
                                                           <?= $repair['prefer_phone_contact'] ? 'checked' : '' ?>
                                                           onchange="toggleContactPreference(this)">
                                                    <span class="ml-2 flex items-center text-sm text-gray-800">
                                                        <i class="fas fa-phone <?= $repair['prefer_phone_contact'] ? 'text-pink-600' : 'text-gray-400' ?> mr-1"></i> Telefon
                                                    </span>
                                                </label>
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" name="prefer_sms_contact" value="1" 
                                                           class="option-button rounded border-gray-300 text-pink-600" 
                                                           data-type="contact" data-name="prefer_sms_contact" 
                                                           <?= $repair['prefer_sms_contact'] ? 'checked' : '' ?>
                                                           onchange="toggleContactPreference(this)">
                                                    <span class="ml-2 flex items-center text-sm text-gray-800">
                                                        <i class="fas fa-envelope <?= $repair['prefer_sms_contact'] ? 'text-pink-600' : 'text-gray-400' ?> mr-1"></i> SMS
                                                    </span>
                                                </label>
                                                <button type="submit" class="ml-2 inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                                                    Aktualizuj
                                                </button>
                                            </div>
                                        </form>
                                    </dd>
                                </div>

                                <!-- Status -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium <?= isset($statusClasses[$repair['status']]) ? $statusClasses[$repair['status']] : 'bg-gray-100 text-gray-800' ?>">
                                            <?= htmlspecialchars($repair['status'] ?? 'Nieznany') ?>
                                        </span>
                                    </dd>
                                </div>

                                <!-- Date -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Data przyjęcia</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <?= htmlspecialchars((new DateTime($repair['created_at']))->format('d.m.Y H:i')) ?>
                                    </dd>
                                </div>

                                <!-- Additional phone with edit option -->
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Dodatkowy numer telefonu</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <div class="flex items-center">
                                            <input type="text" id="additional-phone-input" 
                                                   value="<?= htmlspecialchars($repair['additional_phone'] ?? '') ?>" 
                                                   placeholder="Brak dodatkowego numeru"
                                                   class="shadow-sm focus:ring-pink-500 focus:border-pink-500 block sm:text-sm border-gray-300 rounded-md" />
                                            <button onclick="updateAdditionalPhone()" class="ml-2 inline-flex items-center px-2 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                                                Zapisz
                                            </button>
                                        </div>
                                    </dd>
                                </div>
                            </dl>

                            <!-- Repair options -->
                            <div class="mt-8">
                                <h4 class="text-md font-medium text-gray-900">Opcje naprawy</h4>
                                <div class="mt-2 grid grid-cols-3 gap-2">
                                    <button type="button" 
                                            class="option-button <?= $initial_options['option_expertise'] ? 'option-selected' : '' ?> <?= $repair['option_expertise'] ? 'option-active' : '' ?> inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" 
                                            data-type="repair" data-name="option_expertise" data-value="<?= $repair['option_expertise'] ? 'true' : 'false' ?>">
                                        Ekspertyza
                                    </button>
                                    <button type="button" 
                                            class="option-button <?= $initial_options['option_repair'] ? 'option-selected' : '' ?> <?= $repair['option_repair'] ? 'option-active' : '' ?> inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" 
                                            data-type="repair" data-name="option_repair" data-value="<?= $repair['option_repair'] ? 'true' : 'false' ?>">
                                        Naprawa
                                    </button>
                                    <button type="button" 
                                            class="option-button <?= $initial_options['option_supplies'] ? 'option-selected' : '' ?> <?= $repair['option_supplies'] ? 'option-active' : '' ?> inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" 
                                            data-type="repair" data-name="option_supplies" data-value="<?= $repair['option_supplies'] ? 'true' : 'false' ?>">
                                        Materiały
                                    </button>
                                </div>
                            </div>

                            <!-- Cable options -->
                            <div class="mt-6">
                                <h4 class="text-md font-medium text-gray-900">Przewody</h4>
                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    <button type="button" 
                                            class="cable-button <?= $initial_options['usb_cable'] ? 'option-selected' : '' ?> <?= $repair['usb_cable'] ? 'option-active' : '' ?> inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" 
                                            data-type="cable" data-name="usb_cable" data-value="<?= $repair['usb_cable'] ? 'true' : 'false' ?>">
                                        USB
                                    </button>
                                    <button type="button" 
                                            class="cable-button <?= $initial_options['power_cable'] ? 'option-selected' : '' ?> <?= $repair['power_cable'] ? 'option-active' : '' ?> inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" 
                                            data-type="cable" data-name="power_cable" data-value="<?= $repair['power_cable'] ? 'true' : 'false' ?>">
                                        Zasilający
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Prawa kolumna -->
                        <div>
                            <!-- Status update -->
                            <h3 class="text-lg font-medium text-gray-900">Aktualizacja statusu</h3>
                            <form method="post" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <input type="hidden" name="update_status_dropdown" value="1">
                                
                                <div class="mb-4">
                                    <label for="new_status" class="block text-sm font-medium text-gray-700">Nowy status</label>
                                    <select id="new_status" name="new_status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm rounded-md">
                                        <?php foreach(RepairDetailsModel::VALID_STATUSES as $status): ?>
                                            <option value="<?= htmlspecialchars($status) ?>" <?= $repair['status'] === $status ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="notes" class="block text-sm font-medium text-gray-700">Notatki (opcjonalnie)</label>
                                    <textarea id="notes" name="notes" rows="3" class="shadow-sm focus:ring-pink-500 focus:border-pink-500 mt-1 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
                                </div>

                                <div class="mb-4 flex items-center">
                                    <input id="send_sms" name="send_sms" type="checkbox" value="1" class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                                    <label for="send_sms" class="ml-2 block text-sm text-gray-900">
                                        Wyślij SMS z nowym statusem
                                    </label>
                                </div>
                                
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                                    Aktualizuj status
                                </button>
                            </form>

                            <!-- History -->
                            <h3 class="text-lg font-medium text-gray-900 mt-8">Historia naprawy</h3>
                            <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md max-h-96 overflow-y-auto">
                                <ul class="divide-y divide-gray-200">
                                    <?php foreach($history as $entry): ?>
                                    <li>
                                        <div class="px-4 py-4 sm:px-6">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium 
                                                                <?= isset($statusClasses[$entry['status']]) ? $statusClasses[$entry['status']] : 'bg-gray-100 text-gray-800' ?>">
                                                        <?= htmlspecialchars($entry['status']) ?>
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?= htmlspecialchars((new DateTime($entry['created_at']))->format('d.m.Y H:i')) ?>
                                                </div>
                                            </div>
                                            <?php if(!empty($entry['notes'])): ?>
                                            <div class="mt-2 text-sm text-gray-700">
                                                <?= htmlspecialchars($entry['notes']) ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                    <?php if(empty($history)): ?>
                                    <li>
                                        <div class="px-4 py-4 sm:px-6 text-sm text-gray-500">
                                            Brak wpisów w historii
                                        </div>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <!-- Related repairs -->
                            <?php if(!empty($related_repairs)): ?>
                            <h3 class="text-lg font-medium text-gray-900 mt-8">Powiązane naprawy</h3>
                            <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md">
                                <ul class="divide-y divide-gray-200">
                                    <?php foreach($related_repairs as $related): ?>
                                    <li>
                                        <a href="repair_details.php?id=<?= $related['id'] ?>" class="block hover:bg-gray-50">
                                            <div class="px-4 py-4 sm:px-6">
                                                <div class="flex items-center justify-between">
                                                    <p class="text-sm font-medium text-pink-600 truncate">
                                                        <?= htmlspecialchars($related['device_model']) ?>
                                                    </p>
                                                    <div class="text-sm text-gray-500">
                                                        <?= htmlspecialchars((new DateTime($related['created_at']))->format('d.m.Y')) ?>
                                                    </div>
                                                </div>
                                                <div class="mt-2 flex justify-between">
                                                    <div class="sm:flex">
                                                        <p class="flex items-center text-sm text-gray-500">
                                                            <?= htmlspecialchars($related['serial_number'] ?? 'Brak numeru seryjnego') ?>
                                                            
                                                            <!-- Show contact preference icons if any -->
                                                            <?php if(!empty($related['prefer_phone_contact']) || !empty($related['prefer_sms_contact'])): ?>
                                                                <span class="ml-2">
                                                                    <?php if(!empty($related['prefer_phone_contact'])): ?>
                                                                        <i class="fas fa-phone text-pink-600 mr-1" title="Preferuje kontakt telefoniczny"></i>
                                                                    <?php endif; ?>
                                                                    <?php if(!empty($related['prefer_sms_contact'])): ?>
                                                                        <i class="fas fa-envelope text-pink-600" title="Preferuje kontakt SMS"></i>
                                                                    <?php endif; ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium 
                                                                    <?= isset($statusClasses[$related['status']]) ? $statusClasses[$related['status']] : 'bg-gray-100 text-gray-800' ?>">
                                                            <?= htmlspecialchars($related['status']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Document ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize option buttons
            initOptionButtons();
        });

        // Option buttons toggle
        function initOptionButtons() {
            document.querySelectorAll('.option-button, .cable-button').forEach(button => {
                button.addEventListener('click', function() {
                    const type = this.dataset.type;
                    const name = this.dataset.name;
                    const currentValue = this.dataset.value === 'true';
                    const newValue = !currentValue;
                    
                    // Update button appearance (only the background/active state, not the border)
                    this.classList.toggle('option-active', newValue);
                    this.dataset.value = newValue ? 'true' : 'false';
                    
                    // Send update to server
                    updateOption(type, name, newValue);
                });
            });
        }
        
        // Update option via AJAX
        function updateOption(type, name, value) {
            const formData = new FormData();
            formData.append('csrf_token', '<?= htmlspecialchars($csrf_token) ?>');
            formData.append('update_option', '1');
            formData.append('option_type', type);
            formData.append('option_name', name);
            formData.append('value', value ? 'true' : 'false');
            
            fetch('repair_details.php?id=<?= $repair_id ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.error || 'Wystąpił błąd podczas aktualizacji opcji');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas komunikacji z serwerem');
            });
        }

        // Toggle contact preference checkboxes visual state
        function toggleContactPreference(checkbox) {
            const iconElement = checkbox.nextElementSibling.querySelector('i');
            if (checkbox.checked) {
                iconElement.classList.remove('text-gray-400');
                iconElement.classList.add('text-pink-600');
            } else {
                iconElement.classList.remove('text-pink-600');
                iconElement.classList.add('text-gray-400');
            }
        }

// Update contact preferences via AJAX - add better error handling
function updateContactPreferences(event) {
    event.preventDefault();
    const form = document.getElementById('contactPreferencesForm');
    const formData = new FormData(form);
    formData.append('update_contact_preferences', '1');
    
    fetch('repair_details.php?id=<?= $repair_id ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            // Show success notification
            const successMsg = document.createElement('div');
            successMsg.className = 'fixed bottom-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded';
            successMsg.innerHTML = 'Preferencje kontaktu zostały zaktualizowane';
            document.body.appendChild(successMsg);
            
            // Remove the notification after 3 seconds
            setTimeout(() => successMsg.remove(), 3000);
        } else {
            alert(data && data.error ? data.error : 'Wystąpił błąd podczas aktualizacji preferencji kontaktu');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas komunikacji z serwerem. Spróbuj ponownie.');
    });
    
    return false;
}

        // Toggle serial number edit form
        function toggleSerialEdit() {
            const displayEl = document.getElementById('serial-number-display');
            const formEl = document.getElementById('serial-edit-form');
            
            if (formEl.classList.contains('hidden')) {
                displayEl.classList.add('hidden');
                formEl.classList.remove('hidden');
            } else {
                displayEl.classList.remove('hidden');
                formEl.classList.add('hidden');
            }
        }

        // Toggle device model edit form
        function toggleDeviceModelEdit() {
            const displayEl = document.getElementById('device-model-display');
            const formEl = document.getElementById('device-model-edit-form');
            
            if (formEl.classList.contains('hidden')) {
                displayEl.classList.add('hidden');
                formEl.classList.remove('hidden');
            } else {
                displayEl.classList.remove('hidden');
                formEl.classList.add('hidden');
            }
        }

        // Toggle phone number edit form
        function togglePhoneEdit() {
            const displayEl = document.getElementById('phone-number-display');
            const formEl = document.getElementById('phone-edit-form');
            
            if (formEl.classList.contains('hidden')) {
                displayEl.classList.add('hidden');
                formEl.classList.remove('hidden');
            } else {
                displayEl.classList.remove('hidden');
                formEl.classList.add('hidden');
            }
        }

        // Update serial number via AJAX
        function updateSerial(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('update_serial', '1');
            
            fetch('repair_details.php?id=<?= $repair_id ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const newSerial = form.elements.new_serial_number.value;
                    document.getElementById('serial-number-display').textContent = newSerial;
                    toggleSerialEdit();
                } else {
                    alert(data.error || 'Wystąpił błąd podczas aktualizacji numeru seryjnego');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas komunikacji z serwerem');
            });
            
            return false;
        }

        // Update device model via AJAX
        function updateDeviceModel(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('update_device_model', '1');
            
            fetch('repair_details.php?id=<?= $repair_id ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const newModel = form.elements.new_device_model.value;
                    document.getElementById('device-model-display').textContent = newModel;
                    toggleDeviceModelEdit();
                } else {
                    alert(data.error || 'Wystąpił błąd podczas aktualizacji modelu urządzenia');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas komunikacji z serwerem');
            });
            
            return false;
        }

        // Update main phone number via AJAX
        function updatePhoneNumber(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('update_main_phone', '1');
            
            fetch('repair_details.php?id=<?= $repair_id ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const newPhone = form.elements.new_phone_number.value;
                    document.getElementById('phone-number-display').textContent = newPhone;
                    togglePhoneEdit();
                } else {
                    alert(data.error || 'Wystąpił błąd podczas aktualizacji numeru telefonu');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas komunikacji z serwerem');
            });
            
            return false;
        }

        // Update additional phone number via AJAX
        function updateAdditionalPhone() {
            const phoneInput = document.getElementById('additional-phone-input');
            const additionalPhone = phoneInput.value;
            
            const formData = new FormData();
            formData.append('csrf_token', '<?= htmlspecialchars($csrf_token) ?>');
            formData.append('update_phone', '1');
            formData.append('additional_phone', additionalPhone);
            
            fetch('repair_details.php?id=<?= $repair_id ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Dodatkowy numer telefonu został zaktualizowany');
                } else {
                    alert(data.error || 'Wystąpił błąd podczas aktualizacji dodatkowego numeru telefonu');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas komunikacji z serwerem');
            });
        }
    </script>
</body>
</html>