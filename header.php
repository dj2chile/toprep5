<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel zarządzania naprawami - TOP-SERWIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <style>
        /* Style dla całej strony */
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
        .keypad.active { transform: translateY(0); }
        .scanner-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 50;
        }
        .scanner-overlay.active { display: flex; }
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
        .status-cell { position: relative; }
        .status-label { cursor: pointer; }
        .status-dropdown.active { display: block; }
        .repair-options span { display: inline-block; margin: 2px 0; }
        .container-full { width: 100%; padding-left: 1rem; padding-right: 1rem; }
        .table-wrapper { margin: 0 -1rem; width: calc(100% + 2rem); }
        @media (max-width: 768px) { .desktop-view { display: none; } .mobile-view { display: block; } }
        @media (min-width: 769px) { .desktop-view { display: block; } .mobile-view { display: none; } }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Pasek nawigacyjny -->
    <nav class="bg-white shadow-lg">
        <div class="container-full mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-bold text-pink-500">TOP-SERWIS</span>
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
