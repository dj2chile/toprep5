<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check for error message
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['error_message']); // Clear the message after displaying

// Check if we're in serial number search mode
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'selection';

// Variables for storing found repair data
$found_repair = null;
$serial_number = '';

// If searching for serial number
if ($mode === 'search_serial' && isset($_GET['serial_number'])) {
    $serial_number = $_GET['serial_number'];
    
    // Search for the serial number in the database
    try {
        $stmt = $pdo->prepare("SELECT * FROM repairs WHERE serial_number = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$serial_number]);
        $found_repair = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Błąd podczas wyszukiwania: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj naprawę - TOP SERWIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
</head>
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <?php if($mode === 'selection'): ?>
                    <!-- Selection Screen -->
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Wybierz sposób dodania naprawy</h3>
                    <div class="space-y-4">
                        <a href="?mode=standard" class="block w-full bg-pink-600 hover:bg-pink-700 text-white font-bold py-4 px-4 rounded text-center">
                            1. Dodaj po staremu
                        </a>
                        <a href="?mode=serial_scan" class="block w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-4 px-4 rounded text-center">
                            2. TESTUJ dodaj po numerze seryjnym
                        </a>
                    </div>
                
                <?php elseif($mode === 'serial_scan'): ?>
                    <!-- Serial Scanner Mode -->
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Skanuj numer seryjny</h3>
                    <div class="mb-6">
                        <button id="startScanButton" class="w-full py-3 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                            Uruchom skaner
                        </button>
                        
                        <div class="mt-4 relative">
                            <input type="text" id="serialNumberScan" class="block w-full border border-gray-300 rounded-md shadow-sm p-2" 
                                   placeholder="Numer seryjny">
                            <button id="searchSerialButton" class="mt-2 px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                                Szukaj
                            </button>
                        </div>
                        
                        <input type="file" id="scannerFileInput" accept="image/*" capture="environment" class="hidden">
                    </div>
                    
                <?php elseif($mode === 'search_serial'): ?>
                    <!-- Serial Search Results -->
                    <h3 class="text-lg font-medium text-gray-900 mb-6">
                        <?php if($found_repair): ?>
                            Znaleziono poprzednią naprawę
                        <?php else: ?>
                            Nie znaleziono naprawy o podanym numerze seryjnym
                        <?php endif; ?>
                    </h3>
                    
                    <?php if($found_repair): ?>
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <h4 class="font-semibold">Szczegóły poprzedniej naprawy:</h4>
                        <p><strong>Model urządzenia:</strong> <?php echo htmlspecialchars($found_repair['device_model']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($found_repair['status']); ?></p>
                        <p><strong>Data:</strong> <?php echo htmlspecialchars($found_repair['created_at']); ?></p>
                        
                        <?php 
                        // Try to get repair history
                        try {
                            $stmt = $pdo->prepare("SELECT * FROM repair_history WHERE repair_id = ? ORDER BY created_at DESC");
                            $stmt->execute([$found_repair['id']]);
                            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if ($history): 
                        ?>
                            <h5 class="font-semibold mt-2">Historia naprawy:</h5>
                            <ul class="list-disc list-inside pl-2">
                                <?php foreach ($history as $entry): ?>
                                <li>
                                    <?php echo htmlspecialchars($entry['status']); ?>: 
                                    <?php echo htmlspecialchars($entry['notes']); ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php 
                            endif;
                        } catch (PDOException $e) {
                            // Silently handle error
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Continue to form -->
                    <a href="?mode=standard&serial_number=<?php echo urlencode($serial_number); ?>&phone_number=<?php echo $found_repair ? urlencode($found_repair['phone_number']) : ''; ?>&nip=<?php echo ($found_repair && $found_repair['nip']) ? urlencode($found_repair['nip']) : ''; ?>&device_model=<?php echo $found_repair ? urlencode($found_repair['device_model']) : ''; ?>" 
                       class="block w-full bg-pink-600 hover:bg-pink-700 text-white font-bold py-2 px-4 rounded text-center mb-4">
                        Kontynuuj dodawanie naprawy
                    </a>
                    
                    <a href="?mode=selection" class="block text-center text-blue-500 hover:underline">
                        Wróć do wyboru
                    </a>

                <?php elseif($mode === 'standard'): ?>
                    <!-- Standard Form Mode -->
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Dodaj nową naprawę</h3>
                    
                    <form action="add_repair_handler.php" method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Model urządzenia</label>
                            <input type="text" name="device_model" required
                                   value="<?php echo isset($_GET['device_model']) ? htmlspecialchars($_GET['device_model']) : ''; ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Numer seryjny</label>
                            <div class="mt-1 flex space-x-2">
                                <input type="text" name="serial_number" id="serialNumber" required
                                       value="<?php echo isset($_GET['serial_number']) ? htmlspecialchars($_GET['serial_number']) : ''; ?>"
                                       class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                                <button type="button" 
                                        onclick="document.getElementById('fileInput').click()"
                                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                                    Skanuj
                                </button>
                            </div>
                            <input type="file" id="fileInput" accept="image/*" capture="environment" class="hidden">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Numer telefonu</label>
                            <div class="mt-1 relative">
                                <input type="tel" name="phone_number" id="phoneNumber" required pattern="[0-9]{9}"
                                       value="<?php echo isset($_GET['phone_number']) ? htmlspecialchars($_GET['phone_number']) : ''; ?>"
                                       class="block w-full border border-gray-300 rounded-md shadow-sm p-2">
                                <div id="smsButtonContainer" class="hidden mt-2">
                                    <a href="#" id="sendSmsBtn" class="inline-block px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                                        Wyślij SMS powitalny
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">NIP (opcjonalnie)</label>
                            <input type="text" name="nip" pattern="[0-9]{10}"
                                   value="<?php echo isset($_GET['nip']) ? htmlspecialchars($_GET['nip']) : ''; ?>"
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Opcje naprawy</label>
                            <div class="space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="option_expertise" class="rounded border-gray-300 text-pink-600">
                                    <span class="ml-2">Ekspertyza</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="option_repair" class="rounded border-gray-300 text-pink-600">
                                    <span class="ml-2">Naprawa</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="option_supplies" class="rounded border-gray-300 text-pink-600">
                                    <span class="ml-2">Mat. eksploatacyjne</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Przewody</label>
                            <div class="space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="usb_cable" class="rounded border-gray-300 text-pink-600">
                                    <span class="ml-2">Kabel USB</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="power_cable" class="rounded border-gray-300 text-pink-600">
                                    <span class="ml-2">Kabel zasilający</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-pink-600 hover:bg-pink-700">
                                Dodaj naprawę
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4">
                        <a href="?mode=selection" class="text-blue-500 hover:underline">
                            Wróć do wyboru
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Original scanner code for standard form
        const fileInput = document.getElementById('fileInput');
        const serialNumber = document.getElementById('serialNumber');

        if (fileInput && serialNumber) {
            fileInput.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                try {
                    if ('BarcodeDetector' in window) {
                        const imageUrl = URL.createObjectURL(file);
                        const img = new Image();
                        img.src = imageUrl;
                        
                        await img.decode();
                        
                        const barcodeDetector = new BarcodeDetector();
                        const barcodes = await barcodeDetector.detect(img);
                        
                        if (barcodes.length > 0) {
                            serialNumber.value = barcodes[0].rawValue;
                        } else {
                            alert('Nie wykryto kodu kreskowego. Spróbuj ponownie.');
                        }
                    } else {
                        alert('Twoja przeglądarka nie obsługuje skanowania kodów kreskowych.');
                    }
                } catch (error) {
                    console.error('Błąd podczas skanowania:', error);
                    alert('Wystąpił błąd podczas skanowania. Spróbuj ponownie.');
                }
            });
        }
        
        // Scanner code for serial_scan mode
        const scannerFileInput = document.getElementById('scannerFileInput');
        const serialNumberScan = document.getElementById('serialNumberScan');
        const startScanButton = document.getElementById('startScanButton');
        const searchSerialButton = document.getElementById('searchSerialButton');
        
        if (startScanButton && scannerFileInput && serialNumberScan) {
            startScanButton.addEventListener('click', () => {
                scannerFileInput.click();
            });
            
            scannerFileInput.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                try {
                    if ('BarcodeDetector' in window) {
                        const imageUrl = URL.createObjectURL(file);
                        const img = new Image();
                        img.src = imageUrl;
                        
                        await img.decode();
                        
                        const barcodeDetector = new BarcodeDetector();
                        const barcodes = await barcodeDetector.detect(img);
                        
                        if (barcodes.length > 0) {
                            serialNumberScan.value = barcodes[0].rawValue;
                            // Auto search after successful scan
                            window.location.href = '?mode=search_serial&serial_number=' + encodeURIComponent(barcodes[0].rawValue);
                        } else {
                            alert('Nie wykryto kodu kreskowego. Spróbuj ponownie.');
                        }
                    } else {
                        alert('Twoja przeglądarka nie obsługuje skanowania kodów kreskowych.');
                    }
                } catch (error) {
                    console.error('Błąd podczas skanowania:', error);
                    alert('Wystąpił błąd podczas skanowania. Spróbuj ponownie.');
                }
            });
            
            // Search button handler
            if (searchSerialButton) {
                searchSerialButton.addEventListener('click', () => {
                    const serialValue = serialNumberScan.value.trim();
                    if (serialValue) {
                        window.location.href = '?mode=search_serial&serial_number=' + encodeURIComponent(serialValue);
                    } else {
                        alert('Wprowadź numer seryjny przed wyszukiwaniem.');
                    }
                });
            }
        }

        // Phone number validation and SMS functionality
        const phoneNumberInput = document.getElementById('phoneNumber');
        const smsButtonContainer = document.getElementById('smsButtonContainer');
        const sendSmsBtn = document.getElementById('sendSmsBtn');
        
        if (phoneNumberInput && smsButtonContainer && sendSmsBtn) {
            // Check if phone number is already filled with 9 digits
            if (phoneNumberInput.value && phoneNumberInput.value.match(/^\d{9}$/)) {
                showSmsButton(phoneNumberInput.value);
            }
            
            // Listen for changes in the phone number field
            phoneNumberInput.addEventListener('input', function() {
                const phoneNumber = this.value.replace(/\D/g, ''); // Remove non-digits
                
                if (phoneNumber.length === 9) {
                    showSmsButton(phoneNumber);
                } else {
                    smsButtonContainer.classList.add('hidden');
                }
            });
            
            function showSmsButton(phoneNumber) {
                // Format phone number with country code for Poland (+48)
                const formattedNumber = '+48' + phoneNumber;
                
                // Set up SMS message
                const smsMessage = "Dziekujemy za skorzystanie z uslug TOP SERWIS. Bedzie nam milo pomoc w naprawie Twojego urzadzenia. Twoj sprzet jest w dobrych rekach.";
                
                // Update SMS button href
                sendSmsBtn.href = `sms:${formattedNumber}?body=${encodeURIComponent(smsMessage)}`;
                
                // Show the SMS button
                smsButtonContainer.classList.remove('hidden');
                
                // Try to auto-launch SMS (this may be blocked by browsers without user interaction)
                if (phoneNumberInput.dataset.autoSent !== 'true') {
                    // Set a flag to prevent multiple attempts
                    phoneNumberInput.dataset.autoSent = 'true';
                    
                    // Attempt to trigger the SMS link
                    setTimeout(() => {
                        // This will work on mobile devices but may be blocked by browsers
                        // without explicit user interaction
                        window.open(sendSmsBtn.href, '_blank');
                    }, 500);
                }
            }
        }
    </script>
</body>
</html>