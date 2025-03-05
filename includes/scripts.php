<?php
// scripts.php
?>
<script>
// Zmienne globalne
const csrfToken = '<?= $csrfToken ?>';
let phoneNumber = '';

// Inicjalizacja klawiatury
function initKeypad() {
    const keypadGrid = document.getElementById('keypadButtons');
    const keypadLayout = [
        ['1', '2', '3'],
        ['4', '5', '6'],
        ['7', '8', '9'],
        ['', '0', 'del']
    ];

    keypadGrid.innerHTML = '';
    
    keypadLayout.forEach(row => {
        row.forEach(key => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'p-4 text-2xl rounded-lg bg-gray-100 hover:bg-gray-200 active:bg-gray-300';
            
            if (key === 'del') {
                button.innerHTML = '&#x232B;';
                button.onclick = () => handleKeyPress('del');
            } else if (key !== '') {
                button.textContent = key;
                button.onclick = () => handleKeyPress(key);
            } else {
                button.disabled = true;
            }
            
            keypadGrid.appendChild(button);
        });
    });
}

// Obsługa klawiatury
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

// Przełączanie klawiatury
function toggleKeypad() {
    const keypad = document.getElementById('keypad');
    keypad.classList.toggle('active');
    if (keypad.classList.contains('active')) {
        phoneNumber = '';
        document.getElementById('phoneInput').value = '';
    }
}

// Obsługa skanera
async function handleBarcodeScanning(event) {
    const file = event.target.files[0];
    if (!file) return;

    try {
        const preview = document.getElementById('scannerPreview');
        const img = document.getElementById('previewImage');
        img.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');

        if (window.BarcodeDetector) {
            const barcodeDetector = new BarcodeDetector();
            const detected = await barcodeDetector.detect(file);
            
            if (detected.length > 0) {
                window.location.href = `?search_type=serial&search_term=${detected[0].rawValue}`;
            } else {
                throw new Error('Nie znaleziono kodu');
            }
        } else {
            throw new Error('Brak obsługi skanera w przeglądarce');
        }
    } catch (error) {
        alert(`Błąd skanowania: ${error.message}`);
        toggleScanner();
    }
}

function toggleScanner() {
    document.getElementById('scannerOverlay').classList.toggle('active');
    document.getElementById('scannerPreview').classList.add('hidden');
}

// Zarządzanie statusami
function toggleStatusDropdown(event, repairId) {
    event.stopPropagation();
    const dropdowns = document.querySelectorAll(`[id^="status-dropdown-${repairId}"]`);
    
    document.querySelectorAll('.status-dropdown').forEach(d => {
        if (!d.id.startsWith(`status-dropdown-${repairId}`)) d.classList.remove('active');
    });
    
    dropdowns.forEach(d => d.classList.toggle('active'));
}

async function updateStatus(repairId, newStatus) {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken
            },
            body: new URLSearchParams({
                update_status: 1,
                repair_id: repairId,
                new_status: newStatus,
                csrf_token: csrfToken
            })
        });

        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            throw new Error(data.error || 'Nieznany błąd');
        }
    } catch (error) {
        console.error('Błąd aktualizacji:', error);
        alert(`Błąd aktualizacji statusu: ${error.message}`);
    }
}

// Funkcje pomocnicze
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
            <input type="hidden" name="csrf_token" value="${csrfToken}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Inicjalizacja strony
document.addEventListener('DOMContentLoaded', () => {
    initKeypad();
    
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.status-cell')) {
            document.querySelectorAll('.status-dropdown').forEach(d => d.classList.remove('active'));
        }
    });

    // Automatyczne zamknięcie modali przy ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            toggleKeypad();
            toggleScanner();
        }
    });
});
</script>