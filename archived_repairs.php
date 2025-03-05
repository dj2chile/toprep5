<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Initialize variables
$repairs = [];
$error_message = '';
$success_message = '';

// Status configurations
$STATUS_COLORS = [
    'W trakcie' => 'bg-yellow-100 text-yellow-800',
    'Oczekiwanie na części' => 'bg-orange-100 text-orange-800',
    'Gotowe' => 'bg-green-100 text-green-800',
    'Gotowa do odbioru' => 'bg-blue-100 text-blue-800',
    'Odebrane' => 'bg-purple-100 text-purple-800',
    'Archiwalne' => 'bg-gray-100 text-gray-800'
];

// Search parameters
$search_term = $_GET['search_term'] ?? '';
$search_type = $_GET['search_type'] ?? 'phone';

try {
    // Base query - get only archived and completed repairs
    $query = "SELECT * FROM repairs WHERE status IN ('Odebrane', 'Archiwalne') AND status != 'Usunięty'";
    $params = [];

    // Add search filter if provided
    if (!empty($search_term)) {
        if ($search_type == "phone") {
            $query .= " AND phone_number LIKE ?";
            $params[] = "%$search_term%";
        } else if ($search_type == "serial") {
            $query .= " AND serial_number LIKE ?";
            $params[] = "%$search_term%";
        }
    }

    // Add ordering by status and date
    $query .= " ORDER BY 
        CASE status
            WHEN 'Odebrane' THEN 1
            WHEN 'Archiwalne' THEN 2
            ELSE 3
        END, created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $repairs = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Błąd podczas pobierania danych: " . $e->getMessage();
}

// Helper function to get repair options as array
function getRepairOptions($repair) {
    $options = [];
    if (!empty($repair['option_expertise'])) $options[] = 'Ekspertyza';
    if (!empty($repair['option_repair'])) $options[] = 'Naprawa';
    if (!empty($repair['option_supplies'])) $options[] = 'Mat. eksploatacyjne';
    return $options;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archiwum napraw - TOP-SERWIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <style>
        .container-full {
            width: 100%;
            padding-left: 1rem;
            padding-right: 1rem;
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
                        <span class="text-xl font-bold text-pink-500">TOP-SERWIS</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-gray-900">
                        Aktywne naprawy
                    </a>
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

    <div class="container-full mx-auto py-6 px-4">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">Archiwum napraw</h1>
            
            <!-- Search form -->
            <form method="GET" class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
                <input type="text" name="search_term" 
                       value="<?= htmlspecialchars($search_term ?? '') ?>"
                       placeholder="Szukaj..." 
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full">
                <select name="search_type" class="border border-gray-300 rounded-md px-3 py-2 text-sm w-full sm:w-auto">
                    <option value="phone" <?= ($search_type == 'phone') ? 'selected' : '' ?>>Telefon</option>
                    <option value="serial" <?= ($search_type == 'serial') ? 'selected' : '' ?>>Numer seryjny</option>
                </select>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 w-full sm:w-auto">
                    Szukaj
                </button>
            </form>
        </div>

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

        <!-- Results display -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <?php if(empty($repairs)): ?>
                <div class="p-6 text-center text-gray-500">
                    <p>Brak napraw archiwalnych.</p>
                </div>
            <?php else: ?>
                <!-- Desktop view -->
                <div class="desktop-view overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Model
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
                                    Data
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Akcje
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($repairs as $repair): ?>
                                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location.href='repair_details.php?id=<?php echo $repair['id']; ?>'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($repair['device_model']); ?>
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
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $STATUS_COLORS[$repair['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo htmlspecialchars($repair['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d.m.Y H:i', strtotime($repair['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="repair_details.php?id=<?php echo $repair['id']; ?>" 
                                           class="text-indigo-600 hover:text-indigo-900"
                                           onclick="event.stopPropagation();">
                                            Szczegóły
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile view -->
                <div class="mobile-view">
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
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $STATUS_COLORS[$repair['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo htmlspecialchars($repair['status']); ?>
                                </span>
                            </div>
                            <div class="mt-4 flex justify-between items-center">
                                <a href="#" onclick="event.stopPropagation(); composeSms('<?php echo htmlspecialchars($repair['phone_number']); ?>')" 
                                   class="text-blue-600 hover:text-blue-900">
                                    <?php echo htmlspecialchars($repair['phone_number']); ?>
                                </a>
                                <span class="text-sm text-gray-500">
                                    <?php echo date('d.m.Y', strtotime($repair['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // SMS composition function
        function composeSms(phoneNumber) {
            window.location.href = `sms:${phoneNumber}`;
        }
    </script>
</body>
</html>