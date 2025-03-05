    <div class="keypad" id="keypad">
        <div class="max-w-sm mx-auto">
            <div class="mb-4 relative">
                <input type="text" id="phoneInput" readonly 
                       class="w-full text-2xl p-4 text-center border rounded-lg" 
                       placeholder="Wpisz numer telefonu">
                <button onclick="toggleKeypad()" 
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-3 gap-2" id="keypadButtons"></div>
        </div>
    </div>

    <div class="scanner-overlay" id="scannerOverlay">
        <div class="bg-white p-6 rounded-lg max-w-sm w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Skanuj kod kreskowy</h3>
                <button onclick="toggleScanner()" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="space-y-4">
                <input type="file" id="scanner" accept="image/*" capture="environment" class="hidden" 
                       onchange="handleBarcodeScanning(event)">
                <label for="scanner" 
                       class="block w-full bg-blue-500 text-white text-center px-4 py-3 rounded-lg hover:bg-blue-600 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 5a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2h-1.586a1 1 0 01-.707-.293l-1.121-1.121A2 2 0 0011.172 3H8.828a2 2 0 00-1.414.586L6.293 4.707A1 1 0 015.586 5H4zm6 9a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                    </svg>
                    Zrób zdjęcie kodu
                </label>
                <div id="scannerPreview" class="mt-4 hidden">
                    <img id="previewImage" class="max-w-full rounded-lg">
                </div>
            </div>
        </div>
    </div>