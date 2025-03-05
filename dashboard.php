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
    // Base query - get only active repairs (non-archived, non-picked up)
    $query = "SELECT * FROM repairs WHERE status NOT IN ('Odebrane', 'Archiwalne', 'Usunięty')";
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
            WHEN 'Gotowa do odbioru' THEN 1
            WHEN 'Gotowe' THEN 2
            WHEN 'Oczekiwanie na części' THEN 3
            WHEN 'W trakcie' THEN 4
            ELSE 5
        END, created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $repairs = $stmt->fetchAll();
} catch(PDOException $e) {
    $error_message = "Błąd podczas pobierania danych: " . $e->getMessage();
}

// Delete repair handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_repair'])) {
    try {
        $repair_id = $_POST['repair_id'];
        
        // Mark as deleted instead of actually deleting
        $stmt = $pdo->prepare("UPDATE repairs SET status = 'Usunięty' WHERE id = ?");
        $stmt->execute([$repair_id]);
        
        $success_message = "Naprawa została usunięta.";
        
        // Redirect to avoid re-posting on refresh
        header("Location: dashboard.php");
        exit;
    } catch(PDOException $e) {
        $error_message = "Błąd podczas usuwania naprawy: " . $e->getMessage();
    }
}

// Helper function to get repair options as array
function getRepairOptions($repair) {
    $options = [];
    if (!empty($repair['option_expertise'])) $options[] = 'Ekspertyza';
    if (!empty($repair['option_repair'])) $options[] = 'Naprawa';
    if (!empty($repair['option_supplies'])) $options[] = 'Mat. eksploatacyjne';
    return $options;
}

// Helper function to update status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    try {
        $repair_id = $_POST['repair_id'];
        $new_status = $_POST['new_status'];
        
        if (!array_key_exists($new_status, $STATUS_COLORS)) {
            throw new Exception("Nieprawidłowy status");
        }
        
        // Update status
        $stmt = $pdo->prepare("UPDATE repairs SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $repair_id]);
        
        // Add history entry
        $stmt = $pdo->prepare("INSERT INTO repair_history (repair_id, status, notes) VALUES (?, ?, ?)");
        $stmt->execute([
            $repair_id,
            $new_status,
            "Zmieniono status na: $new_status"
        ]);
        
        $success_message = "Status naprawy został zaktualizowany.";
        
        // Return JSON response for AJAX calls
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => true]);
            exit;
        }
        
        // Redirect to avoid re-posting on refresh
        header("Location: dashboard.php");
        exit;
    } catch(Exception $e) {
        $error_message = "Błąd podczas aktualizacji statusu: " . $e->getMessage();
        
        // Return JSON response for AJAX calls
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => false, 'error' => $error_message]);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TOP-SERWIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

        .status-dropdown.active {
            display: block;
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
                    <a href="archived_repairs.php" class="text-gray-700 hover:text-gray-900">
                        Archiwum
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
            <h1 class="text-2xl font-bold text-gray-800 mb-4 sm:mb-0">Aktywne naprawy</h1>
            
            <!-- Searchform -->
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

        <?php include 'repairs_list.php'; ?>
        
    </div>

    <!-- Delete confirmation modal -->
    <div id="delete-modal" class="hidden fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <!-- Modal dialog -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Potwierdź usunięcie
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Czy na pewno chcesz usunąć tę naprawę? Tej operacji nie można cofnąć.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form id="delete-form" method="POST">
                        <input type="hidden" name="delete_repair" value="1">
                        <input type="hidden" name="repair_id" id="repair-id-input">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Usuń
                        </button>
                    </form>
                    <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Anuluj
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Delete modal functions
        function handleDelete(repairId) {
            document.getElementById('repair-id-input').value = repairId;
            document.getElementById('delete-modal').classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
        }
        
        // Status dropdown toggle
        function toggleStatusDropdown(event, repairId) {
            event.stopPropagation();
            
            // Close all other dropdowns
            document.querySelectorAll('.status-dropdown.active').forEach(dropdown => {
                if (dropdown.id !== `status-dropdown-${repairId}` && dropdown.id !== `status-dropdown-mobile-${repairId}`) {
                    dropdown.classList.remove('active');
                }
            });
            
            // Toggle this dropdown
            const dropdown = document.getElementById(`status-dropdown-${repairId}`);
            const mobileDropdown = document.getElementById(`status-dropdown-mobile-${repairId}`);
            
            if (dropdown) dropdown.classList.toggle('active');
            if (mobileDropdown) mobileDropdown.classList.toggle('active');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            document.querySelectorAll('.status-dropdown.active').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        });
        
        // Update status via AJAX
        function updateStatus(repairId, newStatus) {
            const formData = new FormData();
            formData.append('update_status', '1');
            formData.append('repair_id', repairId);
            formData.append('new_status', newStatus);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to reflect changes
                    location.reload();
                } else {
                    alert(data.error || 'Wystąpił błąd podczas aktualizacji statusu');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas komunikacji z serwerem');
            });
        }
        
        // SMS composition function
        function composeSms(phoneNumber) {
            window.location.href = `sms:${phoneNumber}`;
        }
    </script>
</body>
</html>