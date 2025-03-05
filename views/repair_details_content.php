<?php if(empty($repair)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
        Nie znaleziono naprawy
    </div>
<?php else: ?>
    <div class="mb-6">
        <a href="print_repair.php?id=<?= $repair_id ?>" 
           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 0[...]
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
                        <!-- Device model -->
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Model urządzenia</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?= htmlspecialchars($repair['device_model'] ?? 'Brak danych') ?>
                            </dd>
                        </div>

                        <!-- Serial number with edit option -->
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Numer seryjny</dt>
                            <dd class="mt-1 text-sm text-gray-900 flex items-center">
                                <span><?= htmlspecialchars($repair['serial_number'] ?? 'Brak danych') ?></span>
                                <button onclick="toggleSerialEdit()" class="ml-2 text-sm text-blue-600 hover:text-blue-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.7[...]
                                    </svg>
                                </button>
                            </dd>
                            <div id="serial-edit-form" class="mt-2 hidden">
                                <form onsubmit="return updateSerial(event)">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <div class="flex items-center">
                                        <input type="text" name="new_serial_number" value="<?= htmlspecialchars($repair['serial_number'] ?? '') ?>" 
                                               class="shadow-sm focus:ring-pink-500 focus:border-pink-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        <button type="submit" class="ml-2 inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-[...]
                                            Zapisz
                                        </button>
                                        <button type="button" onclick="toggleSerialEdit()" class="ml-1 inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-g[...]
                                            Anuluj
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Contact information with clickable phone number -->
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Kontakt</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?php if(!empty($repair['phone_number'])): ?>
                                    <a href="tel:<?= htmlspecialchars($repair['phone_number']) ?>" class="phone-link text-blue-600 hover:underline">
                                        <?= htmlspecialchars($repair['phone_number']) ?>
                                    </a>
                                <?php else: ?>
                                    Brak numeru telefonu
                                <?php endif; ?>
                                
                                <?php if(!empty($repair['additional_phone'])): ?>
                                    <br>
                                    <a href="tel:<?= htmlspecialchars($repair['additional_phone']) ?>" class="phone-link text-blue-600 hover:underline">
                                        <?= htmlspecialchars($repair['additional_phone']) ?>
                                    </a>
                                <?php endif; ?>
                            </dd>
                        </div>

                        <!-- NIP (Tax ID) -->
                        <div>
                            <dt class="text-sm font-medium text-gray-500">NIP</dt>
                            <dd class="mt-1 text-sm text-gray-900 flex items-center">
                                <span id="nip-display"><?= htmlspecialchars($repair['nip'] ?? 'Brak danych') ?></span>
                                <button onclick="toggleNipEdit()" class="ml-2 text-sm text-blue-600 hover:text-blue-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.7[...]
                                    </svg>
                                </button>
                            </dd>
                            <div id="nip-edit-form" class="mt-2 hidden">
                                <form onsubmit="return updateNip(event)">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                    <div class="flex items-center">
                                        <input type="text" name="new_nip" value="<?= htmlspecialchars($repair['nip'] ?? '') ?>" 
                                               placeholder="Wprowadź NIP"
                                               class="shadow-sm focus:ring-pink-500 focus:border-pink-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                        <button type="submit" class="ml-2 inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                                            Zapisz
                                        </button>
                                        <button type="button" onclick="toggleNipEdit()" class="ml-1 inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 hover:bg-gray-50">
                                            Anuluj
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Additional content would go here -->
                    </dl>

                    <!-- Internal notes display (only visible to service staff) -->
                    <?php if(!empty($repair['internal_notes'])): ?>
                    <div class="mt-6">
                        <h4 class="text-sm font-medium text-gray-700">Notatki wewnętrzne:</h4>
                        <div class="mt-1 p-3 bg-gray-50 border border-gray-200 rounded-md">
                            <pre class="text-xs text-gray-600 whitespace-pre-wrap"><?= htmlspecialchars($repair['internal_notes']) ?></pre>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Right column -->
                <div>
                    <!-- Status form with renamed label and internal notes -->
                    <h3 class="text-lg font-medium text-gray-900">Aktualizacja statusu</h3>
                    <form method="post" class="mt-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        
                        <div class="mb-4">
                            <label for="new_status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="new_status" name="new_status" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-pink-500 foc[...]
                                <?php foreach(VALID_STATUSES as $status): ?>
                                    <option value="<?= $status ?>" <?= $repair['status'] === $status ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="internal_notes" class="block text-sm font-medium text-gray-700">Notatki wewnętrzne (tylko dla serwisu)</label>
                            <textarea id="internal_notes" name="internal_notes" rows="3" 
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"
                                    placeholder="Notatki widoczne tylko dla pracowników serwisu"></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium text-gray-700">Notatki dla klienta (opcjonalnie)</label>
                            <textarea id="notes" name="notes" rows="3" 
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 sm:text-sm"
                                    placeholder="Te notatki będą widoczne dla klienta"></textarea>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="send_sms" name="send_sms" value="1" 
                                   class="h-4 w-4 text-pink-600 focus:ring-pink-500 border-gray-300 rounded">
                            <label for="send_sms" class="ml-2 block text-sm text-gray-900">
                                Wyślij SMS z powiadomieniem
                            </label>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" name="update_status_dropdown" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                                Aktualizuj status
                            </button>
                        </div>
                    </form>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Repair history -->
    <div class="mt-6 bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900">Historia naprawy</h3>
            <div class="mt-4">
                <?php if(empty($history)): ?>
                    <p class="text-sm text-gray-500">Brak historii dla tej naprawy.</p>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach($history as $entry): ?>
                            <li class="py-4">
                                <div class="flex space-x-3">
                                    <div class="flex-1 space-y-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-medium">
                                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold 
                                                    <?= $statusClasses[$entry['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                                    <?= htmlspecialchars($entry['status']) ?>
                                                </span>
                                            </h3>
                                            <p class="text-sm text-gray-500"><?= htmlspecialchars(date('d.m.Y H:i', strtotime($entry['created_at']))) ?></p>
                                        </div>
                                        <?php if(!empty($entry['notes'])): ?>
                                            <p class="text-sm text-gray-500"><?= nl2br(htmlspecialchars($entry['notes'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Related repairs -->
    <?php if(!empty($related_repairs)): ?>
    <div class="mt-6 bg-white shadow rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900">Powiązane naprawy</h3>
            <div class="mt-4">
                <ul class="divide-y divide-gray-200">
                    <?php foreach($related_repairs as $related): ?>
                        <li class="py-3">
                            <a href="repair_details.php?id=<?= $related['id'] ?>" class="block hover:bg-gray-50">
                                <div class="flex justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            #<?= $related['id'] ?> - <?= htmlspecialchars($related['device_model']) ?>
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold 
                                                <?= $statusClasses[$related['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                                <?= htmlspecialchars($related['status']) ?>
                                            </span>
                                        </p>
                                        <?php if(isset($related['phone_number']) && $related['phone_number'] != $repair['phone_number']): ?>
                                            <p class="text-sm text-gray-500">
                                                <a href="tel:<?= htmlspecialchars($related['phone_number']) ?>" class="phone-link text-blue-600 hover:underline">
                                                    <?= htmlspecialchars($related['phone_number']) ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <!-- Display NIP if available -->
                                        <?php if(!empty($related['nip'])): ?>
                                            <p class="text-sm text-gray-500">
                                                NIP: <?= htmlspecialchars($related['nip']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars(date('d.m.Y', strtotime($related['created_at']))) ?></p>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

</div>

<!-- JavaScript for functionality -->
<script>
// Toggle serial number edit form
function toggleSerialEdit() {
    const form = document.getElementById('serial-edit-form');
    form.classList.toggle('hidden');
}

// Toggle NIP edit form
function toggleNipEdit() {
    const form = document.getElementById('nip-edit-form');
    form.classList.toggle('hidden');
}

// Update serial number via AJAX
function updateSerial(event) {
    event.preventDefault();
    
    const form = event.target;
    const serialNumber = form.querySelector('input[name="new_serial_number"]').value;
    const csrfToken = form.querySelector('input[name="csrf_token"]').value;
    
    if (!serialNumber) {
        alert('Numer seryjny nie może być pusty');
        return false;
    }
    
    fetch('repair_details.php?id=<?= $repair_id ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `update_serial=1&new_serial_number=${encodeURIComponent(serialNumber)}&csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Wystąpił błąd podczas aktualizacji numeru seryjnego');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas aktualizacji numeru seryjnego');
    });
    
    return false;
}

// Update NIP via AJAX
function updateNip(event) {
    event.preventDefault();
    
    const form = event.target;
    const nip = form.querySelector('input[name="new_nip"]').value;
    const csrfToken = form.querySelector('input[name="csrf_token"]').value;
    
    fetch('repair_details.php?id=<?= $repair_id ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `update_nip=1&new_nip=${encodeURIComponent(nip)}&csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Wystąpił błąd podczas aktualizacji numeru NIP');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas aktualizacji numeru NIP');
    });
    
    return false;
}

// Handle phone number clicks for calling
document.addEventListener('DOMContentLoaded', function() {
    const phoneLinks = document.querySelectorAll('.phone-link');
    
    phoneLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const phoneNumber = this.textContent.trim();
            console.log('Próba połączenia z numerem:', phoneNumber);
            
            // Use the tel: protocol to initiate a call
            // The browser or device will handle this based on available capabilities
            // No need to preventDefault as the href="tel:" is already set
        });
    });
});
</script>
</body>
</html>