// Status management functions

// Function to toggle status dropdown
function toggleStatusDropdown(event, repairId) {
    event.stopPropagation(); // Zatrzymanie propagacji zdarzenia
    
    // Zamknięcie wszystkich innych rozwijanych list
    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
        if (dropdown.id !== `status-dropdown-${repairId}`) {
            dropdown.classList.remove('active');
        }
    });
    
    // Przełączenie aktualnej rozwijanej listy
    const dropdown = document.getElementById(`status-dropdown-${repairId}`);
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

// Variable to store selected status
let selectedNewStatus = null;

// Function to show status update panel
function showStatusUpdatePanel(newStatus) {
    // Zapisanie wybranego statusu
    selectedNewStatus = newStatus;
    
    // Aktualizacja tytułu panelu
    const title = document.getElementById('status-update-title');
    if (title) {
        title.textContent = `Zmiana statusu na: ${newStatus}`;
    }
    
    // Zamknięcie rozwijanej listy
    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
        dropdown.classList.remove('active');
    });
    
    // Pokazanie panelu
    const panel = document.getElementById('status-update-panel');
    if (panel) {
        panel.classList.remove('hidden');
        // Przewinięcie do panelu
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Ustawienie fokusu na polu notatki
        document.getElementById('status-notes').focus();
    }
}

// Function to hide status update panel
function hideStatusUpdatePanel() {
    const panel = document.getElementById('status-update-panel');
    if (panel) {
        panel.classList.add('hidden');
    }
    
    // Wyczyszczenie pól
    document.getElementById('status-notes').value = '';
    document.getElementById('send-sms-checkbox').checked = false;
    document.getElementById('char-counter').textContent = '0';
    selectedNewStatus = null;
}

// Function to confirm status update
function confirmStatusUpdate() {
    if (!selectedNewStatus) {
        return;
    }
    
    const notes = document.getElementById('status-notes').value.trim();
    const sendSms = document.getElementById('send-sms-checkbox').checked;
    const csrfToken = document.getElementById('csrf_token').value;
    
    // Pokazanie wskaźnika ładowania
    showLoader();
    
    // Przygotowanie danych formularza
    const formData = new FormData();
    formData.append('update_status_dropdown', '1');
    formData.append('new_status', selectedNewStatus);
    formData.append('notes', notes);
    formData.append('send_sms', sendSms ? '1' : '0');
    formData.append('csrf_token', csrfToken);
    
    // Wysłanie żądania AJAX
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Błąd serwera: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        hideLoader();
        
        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else if (sendSms) {
                window.location.href = `send_status_sms.php?repair_id=${getCurrentRepairId()}&status=${encodeURIComponent(selectedNewStatus)}`;
            } else {
                // Pokaż komunikat sukcesu i odśwież stronę
                showMessage('Status został zaktualizowany pomyślnie.', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
        } else {
            throw new Error(data.error || 'Wystąpił nieznany błąd podczas aktualizacji statusu.');
        }
    })
    .catch(error => {
        hideLoader();
        showMessage(error.message, 'error');
        console.error('Error:', error);
    });
}

// Helper function to get current repair ID from URL
function getCurrentRepairId() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}

// Document ready event listener for status-related functionality
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for notes
    const notesField = document.getElementById('status-notes');
    const charCount = document.getElementById('char-counter');
    
    if (notesField && charCount) {
        notesField.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            
            if (count > 500) {
                this.value = this.value.substring(0, 500);
                charCount.textContent = 500;
            }
        });
    }

    // Close status dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.status-cell')) {
            document.querySelectorAll('.status-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
});