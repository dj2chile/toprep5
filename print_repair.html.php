<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOP-SERWIS.org</title>
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

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            width: 90%;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Print preview -->
    <div class="print-only">
        <!-- Original -->
        <div class="page">
            <div class="header text-center">
                <h1 class="text-2xl font-bold">TOP-SERWIS</h1>
                <p class="mt-4">ul. Szczecińska 8-10/15, 75-135 Koszalin<br>
                   tel. 94 341-82-72, kom. 696123891</p>
                <h2 class="text-xl font-bold mt-4">PROTOKÓŁ PRZYJĘCIA DO NAPRAWY</h2>
                <p class="mt-2 text-gray-600">
                    Firma TOP-SERWIS w Koszalinie zabiera dnia <?= htmlspecialchars($created_date ?? 'brak danych') ?> 
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
                    <p class="font-bold">**W przypadku nie odebrania sprzętu w przeciągu 3 miesięcy, sprzęt zostaje oddany do utylizacji.**</p>
                    
                    <p class="font-bold mt-4">**Wyrażam zgodę na pobranie opłaty 40 zł za diagnozę urządzenia. Opłata nie jest pobierana w przypadku naprawy w naszym serwisie.**</p>

                    <p class="mt-4">W związku z rozpoczęciem stosowania z dniem 25 maja 2018 r. Rozporządzenia Parlamentu Europejskiego i Rady (UE) 2016/679 z 27 kwietnia 2016 r. w sprawie ochrony osób fizycznych w związku z przetwarzaniem danych osobowych i w sprawie swobodnego przepływu takich danych oraz uchylenia dyrektywy 95/46/WE (ogólne rozporządzenie o ochronie danych, dalej: RODO) informujemy, iż na podstawie art. 13 RODO od dnia 25 maja 2018 r. będą Pani/Panu przysługiwały określone poniżej prawa związane z przetwarzaniem Pani/Pana danych osobowych Jakie dane wykorzystujemy? Chodzi o Twoje dane osobowe, które przekazywane są na potrzeby wykonywania usługi którą świadczymy dla Ciebie Przechowywanie danych osobowych tylko do zakończenia usługi Administrator danych osobowych. Twoje dane przekazywane nam i to my będziemy Administratorami to znaczy: TOP-SERWIS Sp. z o.o. ul Szczecińska 8-10/15/75-135 Koszalin Kontakt do inspektora danych osobowych info@top-serwis.org</p>
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
                <h2 class="text-xl font-bold mt-4">TOP-SERWIS</h2>
                <p class="mt-4">ul. Szczecińska 8-10/15, 75-135 Koszalin<br>
                   tel. 94 341-82-72, kom. 696123891</p>
                <h2 class="text-xl font-bold mt-4">PROTOKÓŁ PRZYJĘCIA DO NAPRAWY</h2>
                <p class="mt-2 text-gray-600">
                    Firma TOP-SERWIS w Koszalinie zabiera dnia <?= htmlspecialchars($created_date ?? 'brak danych') ?> 
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
                        <div id="qrcode-service"></div>
                        <p class="text-sm mt-2">Zeskanuj aby sprawdzić status naprawy</p>
                    </div>
                </div>

                <div class="mt-8 legal-text">
                    <p class="font-bold">**W przypadku nie odebrania sprzętu w przeciągu 3 miesięcy, sprzęt zostaje oddany do utylizacji.**</p>
                    
                    <p class="font-bold mt-4">**Wyrażam zgodę na pobranie opłaty 40 zł za diagnozę urządzenia. Opłata nie jest pobierana w przypadku naprawy w naszym serwisie.**</p>

                    <p class="mt-4">W związku z rozpoczęciem stosowania z dniem 25 maja 2018 r. Rozporządzenia Parlamentu Europejskiego i Rady (UE) 2016/679 z 27 kwietnia 2016 r. w sprawie ochrony osób fizycznych w związku z przetwarzaniem danych osobowych i w sprawie swobodnego przepływu takich danych oraz uchylenia dyrektywy 95/46/WE (ogólne rozporządzenie o ochronie danych, dalej: RODO) informujemy, iż na podstawie art. 13 RODO od dnia 25 maja 2018 r. będą Pani/Panu przysługiwały określone poniżej prawa związane z przetwarzaniem Pani/Pana danych osobowych Jakie dane wykorzystujemy? Chodzi o Twoje dane osobowe, które przekazywane są na potrzeby wykonywania usługi którą świadczymy dla Ciebie Przechowywanie danych osobowych tylko do zakończenia usługi Administrator danych osobowych. Twoje dane przekazywane nam i to my będziemy Administratorami to znaczy: TOP-SERWIS Sp. z o.o. ul Szczecińska 8-10/15/75-135 Koszalin Kontakt do inspektora danych osobowych info@top-serwis.org</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Print and SMS modal -->
    <div id="printModal" class="modal">
        <div class="modal-content">
            <h3 class="text-lg font-bold mb-4 text-center">Wybierz opcje</h3>
            <div class="flex flex-col gap-4">
                <button onclick="sendSMS()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Wyślij SMS do klienta
                </button>
                <button onclick="printForm(1)" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Drukuj tylko oryginał
                </button>
                <button onclick="printForm(2)" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Drukuj oryginał + kopia dla klienta
                </button>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            try {
                // URL for client (status only)
                const clientUrl = 'https://top-serwis.org/repairs/status.php?code=<?= htmlspecialchars($status_url ?? '', ENT_QUOTES, 'UTF-8') ?>';
                
                // URL for service (admin panel)
                const serviceUrl = 'https://top-serwis.org/repairs/repair_details.php?id=<?= (int)($repair_id ?? 0) ?>';
                
                // Get QR code containers
                const originalContainer = document.getElementById("qrcode-original");
                const serviceContainer = document.getElementById("qrcode-service");
                
                if (!originalContainer || !serviceContainer) {
                    console.error('QR code containers not found');
                    return;
                }
                
                // Generate QR codes
                new QRCode(originalContainer, {
                    text: serviceUrl,
                    width: 200,
                    height: 200,
                    correctLevel: QRCode.CorrectLevel.H
                });
                
                new QRCode(serviceContainer, {
                    text: clientUrl,
                    width: 200,
                    height: 200,
                    correctLevel: QRCode.CorrectLevel.H
                });

            // Show print modal
            document.getElementById('printModal').style.display = 'flex';
            } catch (error) {
                console.error('Error generating QR codes:', error);
                alert('Wystąpił błąd podczas generowania kodów QR. Spróbuj odświeżyć stronę.');
            }
        };

        function sendSMS() {
            const phoneNumber = '<?= htmlspecialchars($phone_number ?? '', ENT_QUOTES, 'UTF-8') ?>';
            const message = 'Dziękujemy za zostawienie urządzenia w TOP-SERWIS. Status naprawy możesz sprawdzić pod adresem: https://top-serwis.org/repairs/status.php?code=<?= htmlspecialchars($status_url ?? '', ENT_QUOTES, 'UTF-8') ?>';
            window.location.href = `sms:${phoneNumber}?body=${encodeURIComponent(message)}`;
        }

        function printForm(copies) {
            document.getElementById('printModal').style.display = 'none';
            if(copies === 1) {
                document.querySelector('.page-break').style.display = 'none';
            } else {
                document.querySelector('.page-break').style.display = 'block';
            }
            window.print();
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 1000);
        }
    </script>
</body>
</html>