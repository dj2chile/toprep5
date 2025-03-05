<?php
// Disable error display for production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'errors.log');

// Funkcja do logowania błędów
function logError($message) {
    error_log(date('Y-m-d H:i:s') . " - Print Error: " . $message);
}

session_start();
require_once 'config.php';

// Upewnij się, że plik settings_functions.php istnieje
if (!file_exists('settings_functions.php')) {
    logError("Brak pliku settings_functions.php");
    
    // Tymczasowe funkcje zastępcze, aby strona działała nawet bez pliku settings_functions.php
    function getCompanySettings($pdo) {
        return [
            'company_name' => 'TOP-SERWIS Sp. z o.o.',
            'company_address' => 'ul. Szczecińska 8-10/15',
            'company_city' => '75-135 Koszalin',
            'company_phone' => '94 341-82-72',
            'company_mobile' => '696123891',
            'company_email' => 'info@top-serwis.org',
            'domain_url' => 'https://top-serwis.org/repairs/',
            'diagnosis_fee_text' => 'Wyrażam zgodę na pobranie opłaty 40 zł za diagnozę urządzenia. Opłata nie jest pobierana w przypadku naprawy w naszym serwisie.',
            'unclaimed_equipment_text' => 'W przypadku nie odebrania sprzętu w przeciągu 3 miesięcy, sprzęt zostaje oddany do utylizacji.',
            'rodo_text' => 'W związku z rozpoczęciem stosowania z dniem 25 maja 2018 r. Rozporządzenia Parlamentu Europejskiego i Rady (UE) 2016/679 z 27 kwietnia 2016 r. w sprawie ochrony osób fizycznych w związku z przetwarzaniem danych osobowych i w sprawie swobodnego przepływu takich danych oraz uchylenia dyrektywy 95/46/WE (ogólne rozporządzenie o ochronie danych) informujemy, że administratorem danych osobowych jest TOP-SERWIS Sp. z o.o.'
        ];
    }
} else {
    require_once 'settings_functions.php';
}

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Check if repair ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

// Bezpieczne pobranie ID naprawy
$repair_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($repair_id === false || $repair_id === null) {
    $_SESSION['error_message'] = "Nieprawidłowe ID naprawy";
    header("Location: dashboard.php");
    exit;
}

// Pobierz ustawienia firmy
$settings = getCompanySettings($pdo);

// Helper function to get cables display text
function getCablesDisplayText($repair) {
    $cables = [];
    if (!empty($repair['usb_cable'])) $cables[] = 'Kabel USB';
    if (!empty($repair['power_cable'])) $cables[] = 'Kabel zasilający';
    return empty($cables) ? 'brak' : implode(', ', $cables);
}

// Helper function to get repair options
function getRepairOptionsText($repair) {
    $options = [];
    if (!empty($repair['option_expertise'])) $options[] = 'Ekspertyza';
    if (!empty($repair['option_repair'])) $options[] = 'Naprawa';
    if (!empty($repair['option_supplies'])) $options[] = 'Mat. eksploatacyjne';
    return $options;
}

try {
    // Get repair data
    $stmt = $pdo->prepare("SELECT * FROM repairs WHERE id = ?");
    $stmt->execute([$repair_id]);
    $repair = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repair) {
        $_SESSION['error_message'] = "Nie znaleziono naprawy o ID: " . $repair_id;
        header("Location: dashboard.php");
        exit;
    }
    
    // Sprawdź czy kod statusu istnieje
    if (empty($repair['status_url'])) {
        // Jeśli nie ma kodu statusu, wygeneruj go
        $status_url = bin2hex(random_bytes(16)); // 32 znaki
        
        // Zapisz kod statusu w bazie
        $stmt = $pdo->prepare("UPDATE repairs SET status_url = ? WHERE id = ?");
        $stmt->execute([$status_url, $repair_id]);
        
        // Aktualizuj dane naprawy
        $repair['status_url'] = $status_url;
    }
    
    // Prepare data for the view
    $created_date = !empty($repair['created_at']) ? date('d.m.Y', strtotime($repair['created_at'])) : date('d.m.Y');
    $created_time = !empty($repair['created_at']) ? date('H:i', strtotime($repair['created_at'])) : date('H:i');
    $device_model = !empty($repair['device_model']) ? $repair['device_model'] : '';
    $serial_number = !empty($repair['serial_number']) ? $repair['serial_number'] : '';
    $phone_number = !empty($repair['phone_number']) ? $repair['phone_number'] : '';
    $nip = !empty($repair['nip']) ? $repair['nip'] : '';
    $options = getRepairOptionsText($repair);
    $cables_display = getCablesDisplayText($repair);
    $status_url = !empty($repair['status_url']) ? $repair['status_url'] : '';
    
} catch (Exception $e) {
    // Log error and redirect with message
    logError("Error in print_repair.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Wystąpił błąd podczas pobierania danych naprawy";
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wydruk protokołu - <?= htmlspecialchars($settings['company_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        @page {
            size: A4;
            margin: 20mm 20mm;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .print-only {
                display: block !important;
            }
            
            .page-break {
                page-break-before: always;
            }
            
            body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 20mm;
                background: white;
            }

            .legal-text {
                font-size: 8pt;
                line-height: 1.2;
                text-align: justify;
            }

            .qr-section {
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100%;
            }

            .qr-section canvas {
                width: 200px !important;
                height: 200px !important;
                margin: 0 auto;
            }

            .page {
                page-break-after: always;
            }
        }

        @media screen {
            .print-only {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Print preview -->
    <div class="print-only">
        <!-- Original -->
        <div class="page">
            <div class="header text-center">
                <img src="logo.svg" alt="<?= htmlspecialchars($settings['company_name']) ?>" class="w-64 h-auto mx-auto">
                <p class="mt-4"><?= htmlspecialchars($settings['company_address']) ?>, <?= htmlspecialchars($settings['company_city']) ?><br>
                   tel. <?= htmlspecialchars($settings['company_phone']) ?>, kom. <?= htmlspecialchars($settings['company_mobile']) ?></p>
                <h2 class="text-xl font-bold mt-4">PROTOKÓŁ PRZYJĘCIA DO NAPRAWY</h2>
                <p class="mt-2 text-gray-600">
                    Firma <?= htmlspecialchars($settings['company_name']) ?> przyjmuje dnia <?= htmlspecialchars($created_date ?? 'brak danych') ?> 
                    o godzinie <?= htmlspecialchars($created_time ?? 'brak danych') ?>
                </p>
            </div>

            <div class="content mt-8">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="mb-4">
                            <span class="font-bold">Model urządzenia:</span>
                            <span><?= !empty($device_model) ? htmlspecialchars($device_model) : 'brak danych' ?></span>
                        </div>
                        <div class="mb-4">
                            <span class="font-bold">Numer seryjny:</span>
                            <span><?= !empty($serial_number) ? htmlspecialchars($serial_number) : 'brak danych' ?></span>
                        </div>
                        <div class="mb-4">
                            <span class="font-bold">Numer telefonu:</span>
                            <span><?= !empty($phone_number) ? htmlspecialchars($phone_number) : 'brak danych' ?></span>
                        </div>
                        <?php if(!empty($nip)): ?>
                        <div class="mb-4">
                            <span class="font-bold">NIP:</span>
                            <span><?= htmlspecialchars($nip) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="mb-4">
                            <span class="font-bold">Opcje naprawy:</span>
                            <span><?= !empty($options) ? htmlspecialchars(implode(', ', $options)) : 'brak wybranych opcji' ?></span>
                        </div>
                        <div class="mb-4">
                            <span class="font-bold">Przewody:</span>
                            <span><?= htmlspecialchars($cables_display ?? 'brak') ?></span>
                        </div>
                    </div>
                    <div class="qr-section">
                        <div id="qrcode-original"></div>
                        <p class="text-sm mt-2">Zeskanuj aby przejść do szczegółów naprawy</p>
                    </div>
                </div>

                <div class="mt-8 legal-text">
                    <p class="font-bold"><?= htmlspecialchars($settings['unclaimed_equipment_text']) ?></p>
                    
                    <p class="font-bold mt-4"><?= htmlspecialchars($settings['diagnosis_fee_text']) ?></p>

                    <p class="mt-4"><?= htmlspecialchars($settings['rodo_text']) ?></p>
                </div>

                <div class="mt-8 flex justify-between">
                    <div>
                        <p>Przekazujący</p>
                        <p>.........................</p>
                    </div>
                    <div>
                        <p>Odbierający</p>
                        <p>.........................</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client copy -->
        <div class="page-break">
            <div class="header text-center">
                <h1 class="text-2xl font-bold">KOPIA DLA KLIENTA</h1>
                <img src="logo.svg" alt="<?= htmlspecialchars($settings['company_name']) ?>" class="w-64 h-auto mx-auto mt-4">
                <p class="mt-4"><?= htmlspecialchars($settings['company_address']) ?>, <?= htmlspecialchars($settings['company_city']) ?><br>
                   tel. <?= htmlspecialchars($settings['company_phone']) ?>, kom. <?= htmlspecialchars($settings['company_mobile']) ?></p>
                <h2 class="text-xl font-bold mt-4">PROTOKÓŁ PRZYJĘCIA DO NAPRAWY</h2>
                <p class="mt-2 text-gray-600">
                    Firma <?= htmlspecialchars($settings['company_name']) ?> przyjmuje dnia <?= htmlspecialchars($created_date ?? 'brak danych') ?> 
                    o godzinie <?= htmlspecialchars($created_time ?? 'brak danych') ?>
                </p>
            </div>

            <div class="content mt-8">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="mb-4">
                            <span class="font-bold">Model urządzenia:</span>
                            <span><?= !empty($device_model) ? htmlspecialchars($device_model) : 'brak danych' ?></span>
                        </div>
                        <div class="mb-4">
                            <span class="font-bold">Numer seryjny:</span>
                            <span><?= !empty($serial_number) ? htmlspecialchars($serial_number) : 'brak danych' ?></span>
                        </div>
                        <div class="mb-4">
                            <span class="font-bold">Numer telefonu:</span>
                            <span><?= !empty($phone_number) ? htmlspecialchars($phone_number) : 'brak danych' ?></span>
                        </div>
                        <?php if(!empty($nip)): ?>
                        <div class="mb-4">
                            <span class="font-bold">NIP:</span>
                            <span><?= htmlspecialchars($nip) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="mb-4">
                            <span class="font-bold">Opcje naprawy:</span>
                            <span><?= !empty($options) ? htmlspecialchars(implode(', ', $options)) : 'brak wybranych opcji' ?></span>
                        </div>
                        <div class="mb-4">
                            <span class="font-bold">Przewody:</span>
                            <span><?= htmlspecialchars($cables_display ?? 'brak') ?></span>
                        </div>
                    </div>
                    <div class="qr-section">
                        <div id="qrcode-client"></div>
                        <p class="text-sm mt-2">Zeskanuj aby sprawdzić status naprawy</p>
                    </div>
                </div>

                <div class="mt-8 legal-text">
                    <p class="font-bold"><?= htmlspecialchars($settings['unclaimed_equipment_text']) ?></p>
                    
                    <p class="font-bold mt-4"><?= htmlspecialchars($settings['diagnosis_fee_text']) ?></p>

                    <p class="mt-4"><?= htmlspecialchars($settings['rodo_text']) ?></p>
                </div>

                <div class="mt-8 flex justify-between">
                    <div>
                        <p>Przekazujący</p>
                        <p>.........................</p>
                    </div>
                    <div>
                        <p>Odbierający</p>
                        <p>.........................</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            try {
                // Pobierz bazowy URL z ustawień
                const baseUrl = '<?= htmlspecialchars($settings['domain_url']) ?>';
                
                // URL statusu dla klienta
                const clientUrl = baseUrl + 'status.php?code=<?= htmlspecialchars($status_url ?? '') ?>';
                
                // URL dla serwisu
                const serviceUrl = baseUrl + 'repair_details.php?id=<?= (int)($repair_id ?? 0) ?>';
                
                // Generowanie kodów QR
                generateQRCode('qrcode-original', serviceUrl, 200);
                generateQRCode('qrcode-client', clientUrl, 200);

                // Wyświetlenie podglądu dla druku
                window.print();
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1000);
            } catch (error) {
                console.error('Błąd podczas generowania kodów QR:', error);
                alert('Wystąpił błąd podczas generowania kodów QR. Spróbuj odświeżyć stronę.');
            }
        };

        // Funkcja pomocnicza do generowania kodów QR
        function generateQRCode(elementId, url, size) {
            const element = document.getElementById(elementId);
            if (!element) {
                console.error('Element o ID ' + elementId + ' nie istnieje');
                return;
            }

            // Czyszczenie elementu
            element.innerHTML = '';

            // Generowanie kodu QR
            new QRCode(element, {
                text: url,
                width: size,
                height: size,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }

        function sendSMS() {
            const phoneNumber = '<?= htmlspecialchars($phone_number ?? '') ?>';
            const message = 'Dziękujemy za zostawienie urządzenia w <?= htmlspecialchars($settings['company_name']) ?>. Status naprawy możesz sprawdzić pod adresem: ' + 
                            '<?= htmlspecialchars($settings['domain_url']) ?>' + 
                            'status.php?code=<?= htmlspecialchars($status_url ?? '') ?>';
            
            window.location.href = `sms:${phoneNumber}?body=${encodeURIComponent(message)}`;
        }
    </script>
</body>
</html>