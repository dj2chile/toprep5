<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel zarządzania naprawami - TOP SERWIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <style>
        .keypad {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            transform: translateY(100%);
            transition: transform 0.3s ease-in-out;
            z-index: 50;
        }

        .keypad.active {
            transform: translateY(0);
        }

        .scanner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 50;
        }

        .scanner-overlay.active {
            display: flex;
        }

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
        }

        .status-dropdown.active {
            display: block;
        }

        .repair-options span {
            display: inline-block;
            margin: 2px 0;
        }

        /* New styles for full-width layout */
        .container-full {
            width: 100%;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .table-wrapper {
            margin: 0 -1rem;
            width: calc(100% + 2rem);
        }

        @media (max-width: 768px) {
            .desktop-view { display: none; }
            .mobile-view { display: block; }
        }

        @media (min-width: 769px) {
            .desktop-view { display: block; }
            .mobile-view { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="container-full mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-bold text-pink-500">TOP SERWIS</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="add_repair.php" class="bg-pink-500 text-white px-4 py-2 rounded-md hover:bg-pink-600">
                        Dodaj naprawę
                    </a>
                    <a href="logout.php" class="text-gray-700 hover:text-gray-900">
                        Wyloguj się
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-full mx-auto py-6">
        <?php if($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Search controls -->
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
            <button onclick="toggleKeypad()" 
                    class="flex-1 bg-pink-500 text-white py-3 px-4 rounded-lg flex items-center justify-center gap-2 hover:bg-pink-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                </svg>
                <span>Szukaj po nr. tel</span>
            </button>
            <button onclick="toggleScanner()" 
                    class="flex-1 bg-blue-500 text-white py-3 px-4 rounded-lg flex items-center justify-center gap-2 hover:bg-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd"/>
                </svg>
                <span>Skanuj kod</span>
            </button>
        </div>

        <!-- Results display -->
        <div class="bg-white shadow rounded-lg overflow-hidden table-wrapper">
            <!-- Desktop view -->
            <div class="desktop-view overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Model
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Opcje naprawy
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Numer seryjny
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Telefon
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Data przyjęcia
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Akcje
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if(empty($repairs)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    Brak aktywnych napraw
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($repairs as $repair): ?>
                                <tr class="hover:bg-gray-50" onclick="window.location.href='repair_details.php?id=<?php echo $repair['id']; ?>'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($repair['device_model']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="repair-options flex flex-col space-y-1">
                                            <?php foreach(getRepairOptions($repair) as $option): ?>
                                                <span class="inline-flex px-2 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    <?php echo htmlspecialchars($option); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($repair['serial_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="#" onclick="event.stopPropagation(); composeSms('<?php echo htmlspecialchars($repair['phone_number']); ?>')" 
                                        class="text-blue-600 hover:text-blue-900">
                                            <?php echo htmlspecialchars($repair['phone_number']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap status-cell">
                                        <span class="status-label px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $STATUS_COLORS[$repair['status']] ?? 'bg-gray-100 text-gray-800'; ?>"
                                            onclick="event.stopPropagation(); toggleStatusDropdown(event, <?php echo $repair['id']; ?>)">
                                            <?php echo htmlspecialchars($repair['status']); ?>
                                        </span>
                                        <div id="status-dropdown-<?php echo $repair['id']; ?>" class="status-dropdown">
                                            <?php foreach($STATUS_COLORS as $status => $color): 
                                                if($status !== $repair['status']): ?>
                                                <button type="button"
                                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                        onclick="event.stopPropagation(); updateStatus(<?php echo $repair['id']; ?>, '<?php echo htmlspecialchars($status); ?>')">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </button>
                                            <?php endif; endforeach; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d.m.Y H:i', strtotime($repair['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick="event.stopPropagation(); handleDelete(<?php echo $repair['id']; ?>)" 
                                                class="text-red-600 hover:text-red-900">
                                            Usuń
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile view -->
            <div class="mobile-view">
                <?php if(empty($repairs)): ?>
                    <div class="p-4 text-center text-gray-500">
                        Brak aktywnych napraw
                    </div>
                <?php else: ?>
                    <?php foreach($repairs as $repair): ?>
                        <div class="p-4 border-b border-gray-200" onclick="window.location.href='repair_details.php?id=<?php echo $repair['id']; ?>'">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <?php echo htmlspecialchars($repair['device_model']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($repair['serial_number']); ?>
                                    </p>
                                </div>
                                <div class="status-cell">
                                    <span class="status-label px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $STATUS_COLORS[$repair['status']] ?? 'bg-gray-100 text-gray-800'; ?>"
                                        onclick="event.stopPropagation(); toggleStatusDropdown(event, <?php echo $repair['id']; ?>)">
                                        <?php echo htmlspecialchars($repair['status']); ?>
                                    </span>
                                    <div id="status-dropdown-mobile-<?php echo $repair['id']; ?>" class="status-dropdown">
                                        <?php foreach($STATUS_COLORS as $status => $color): 
                                            if($status !== $repair['status']): ?>
                                            <button type="button"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                    onclick="event.stopPropagation(); updateStatus(<?php echo $repair['id']; ?>, '<?php echo htmlspecialchars($status); ?>')">
                                                <?php echo htmlspecialchars($status); ?>
                                            </button>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-between items-center">
                                <a href="#" onclick="event.stopPropagation(); composeSms('<?php echo htmlspecialchars($repair['phone_number']); ?>')" 
                                class="text-blue-600 hover:text-blue-900">
                                    <?php echo htmlspecialchars($repair['phone_number']); ?>
                                </a>
                                <button onclick="event.stopPropagation(); handleDelete(<?php echo $repair['id']; ?>)" 
                                        class="text-red-600 hover:text-red-900">
                                    Usuń
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Link to archived repairs -->
        <div class="mt-8 text-center">
            <a href="archived_repairs.php" class="inline-flex items-center px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                Zobacz naprawy odebrane i archiwalne 
                <?php if(isset($archived_count) && $archived_count > 0): ?>
                    <span class="ml-2 bg-gray-200 text-gray-800 py-0.5 px-2 rounded-full text-xs">
                        <?php echo $archived_count; ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <!-- Numeric Keypad -->
    <div class="keypad" id="keypad">
        <div class="max-w-sm mx-auto">
            <div class="mb-4 relative">
                <input type="text" id="phoneInput" readonly 
                       class="w-full text-2xl p-4 text-center border rounded-lg" 
                       placeholder="Wpisz numer telefonu" />
                <button onclick="toggleKeypad()" 
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-3 gap-2" id="keypadButtons"></div>
        </div>
    </div>

    <!-- Scanner Overlay -->
    <div class="scanner-overlay" id="scannerOverlay">
        <div class="bg-white p-6 rounded-lg max-w-sm w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Skanuj kod kreskowy</h3>
                <button onclick="toggleScanner()" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="space-y-4">
                <input type="file" id="scanner" accept="image/*" capture="environment" class="hidden" 
                       onchange="handleBarcodeScanning(event)" />
                <label for="scanner" 
                       class="block w-full bg-blue-500 text-white text-center px-4 py-3 rounded-lg hover:bg-blue-600 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-1.586a1 1 0 01-.707-.293l-1.121-1.121A2 2 0 0011.172 3H8.828a2 2 0 00-1.414.586L6.293 4.707A1 1 0 015.586 5H4zm6 9a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                    </svg>
                    Zrób zdjęcie kodu
                </label>
                <div id="scannerPreview" class="mt-4 hidden">
                    <img id="previewImage" class="max-w-full rounded-lg" />
                </div>
            </div>
        </div>
    </div>

    <script>
        // Keypad functionality
        let phoneNumber = '';
        
        function initKeypad() {
            const keypadButtons = [
                ['1', '2', '3'],
                ['4', '5', '6'],
                ['7', '8', '9'],
                ['', '0', 'del']
            ];
            
            const keypadGrid = document.getElementById('keypadButtons');
            keypadGrid.innerHTML = '';
            
            keypadButtons.forEach(row => {
                row.forEach(key => {
                    if (key === '') {
                        const emptyDiv = document.createElement('div');
                        keypadGrid.appendChild(emptyDiv);
                        return;
                    }
                    
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'p-4 text-2xl rounded-lg bg-gray-100 hover:bg-gray-200 active:bg-gray-300';
                    button.textContent = key === 'del' ? '←' : key;
                    button.onclick = () => handleKeyPress(key);
                    keypadGrid.appendChild(button);
                });
            });
        }

        function handleKeyPress(key) {
            const input = document.getElementById('phoneInput');
            
            if (key === 'del') {
                phoneNumber = phoneNumber.slice(0, -1);
            } else if (phoneNumber.length < 9) {
                phoneNumber += key;
            }
            
            input.value = phoneNumber;
            
            if (phoneNumber.length === 9) {
                window.location.href = `?search_type=phone&search_term=${phoneNumber}`;
            }
        }

        function toggleKeypad() {
            const keypad = document.getElementById('keypad');
            keypad.classList.toggle('active');
            if (keypad.classList.contains('active')) {
                phoneNumber = '';
                document.getElementById('phoneInput').value = '';
            }
        }

        // Scanner functionality
        function toggleScanner() {
            const overlay = document.getElementById('scannerOverlay');
            overlay.classList.toggle('active');
            document.getElementById('scannerPreview').classList.add('hidden');
            document.getElementById('previewImage').src = '';
        }

        async function handleBarcodeScanning(event) {
            const file = event.target.files[0];
            if (!file) return;

            try {
                const imageUrl = URL.createObjectURL(file);
                const previewImage = document.getElementById('previewImage');
                const preview = document.getElementById('scannerPreview');
                
                previewImage.src = imageUrl;
                preview.classList.remove('hidden');

                if ('BarcodeDetector' in window) {
                    const barcodeDetector = new BarcodeDetector();
                    const img = new Image();
                    img.src = imageUrl;
                    await img.decode();
                    
                    const barcodes = await barcodeDetector.detect(img);
                    if (barcodes.length > 0) {
                        window.location.href = `?search_type=serial&search_term=${barcodes[0].rawValue}`;
                    } else {
                        alert('Nie znaleziono kodu kreskowego. Spróbuj ponownie.');
                    }
                } else {
                    alert('Przepraszamy, twoja przeglądarka nie obsługuje skanowania kodów kreskowych.');
                }
            } catch (error) {
                console.error('Error scanning barcode:', error);
                alert('Wystąpił błąd podczas skanowania. Spróbuj ponownie.');
            }
        }

        // Status management
        function toggleStatusDropdown(event, repairId) {
            event.stopPropagation();
            
            document.querySelectorAll('.status-dropdown').forEach(dropdown => {
                if (dropdown.id !== `status-dropdown-${repairId}` && 
                    dropdown.id !== `status-dropdown-mobile-${repairId}`) {
                    dropdown.classList.remove('active');
                }
            });
            
            const dropdowns = [
                document.getElementById(`status-dropdown-${repairId}`),
                document.getElementById(`status-dropdown-mobile-${repairId}`)
            ];
            
dropdowns.forEach(dropdown => {
                if (dropdown) {
                    dropdown.classList.toggle('active');
                }
            });
        }

        function updateStatus(repairId, newStatus) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `update_status=1&repair_id=${repairId}&new_status=${encodeURIComponent(newStatus)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Błąd podczas aktualizacji statusu: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas aktualizacji statusu.');
            });
        }

        // Utility functions
        function composeSms(phoneNumber) {
            window.location.href = `sms:${phoneNumber}`;
        }

        function handleDelete(repairId) {
            if (confirm('Czy na pewno chcesz usunąć tę naprawę?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="repair_id" value="${repairId}">
                    <input type="hidden" name="delete_repair" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initKeypad();
            
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.status-cell')) {
                    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
                        dropdown.classList.remove('active');
                    });
                }
            });
        });
    </script>
</body>
</html>