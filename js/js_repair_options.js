// Functions for repair options management

// Toggle option (cable, repair options)
function toggleOption(optionType, optionName, button) {
    const currentValue = button.dataset.active === 'true';
    const newValue = !currentValue;
    
    // Optimistic UI update
    button.dataset.active = String(newValue);
    updateButtonStyle(button, optionType, newValue);

    // Send update to server
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `update_option=1&option_type=${encodeURIComponent(optionType)}&option_name=${encodeURIComponent(optionName)}&value=${newValue}&csrf_token=${encodeURIComponent(document.getElementById('csrf_token').value)}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Roll back UI changes if error occurred
            button.dataset.active = String(currentValue);
            updateButtonStyle(button, optionType, currentValue);
            showMessage('Nie udało się zaktualizować opcji.', 'error');
        }
    })
    .catch(error => {
        // Roll back UI changes on error
        button.dataset.active = String(currentValue);
        updateButtonStyle(button, optionType, currentValue);
        console.error('Error:', error);
        showMessage('Wystąpił błąd podczas aktualizacji opcji.', 'error');
    });
}

// Update button style based on active state
function updateButtonStyle(button, optionType, isActive) {
    const baseClasses = "w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200";
    if (isActive) {
        button.className = `${baseClasses} ${
            optionType === 'repair' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'
        }`;
    } else {
        button.className = `${baseClasses} bg-gray-100 text-gray-500`;
    }
}

// Functions for additional phone number
function toggleAdditionalPhone(show) {
    const form = document.getElementById('additionalPhoneForm');
    if (form) {
        if (show) {
            form.classList.remove('hidden');
            document.getElementById('additional_phone')?.focus();
        } else {
            form.classList.add('hidden');
        }
    }
}

// Handle additional phone form submission
async function handleAdditionalPhone(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);

    showLoader();
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        // Check if the response is OK
        if (!response.ok) {
            throw new Error(`Błąd serwera: ${response.status} ${response.statusText}`);
        }

        const data = await response.json();
        hideLoader();
        
        if (data.success) {
            // Reload to update the display
            showMessage('Numer telefonu został zaktualizowany.', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.error || 'Błąd podczas aktualizacji numeru');
        }
    } catch (error) {
        hideLoader();
        showMessage(error.message, 'error');
        console.error('Phone update error:', error);
    }

    return false;
}

// Functions for serial number editing
function toggleSerialEdit() {
    const form = document.getElementById('serial-edit-form');
    if (form) {
        form.classList.toggle('hidden');
        if (!form.classList.contains('hidden')) {
            form.querySelector('input[name="new_serial_number"]').focus();
        }
    }
}

// Update serial number
async function updateSerial(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('update_serial', '1');
    
    const serialNumber = formData.get('new_serial_number');
    if (!serialNumber.trim()) {
        showMessage('Numer seryjny nie może być pusty', 'error');
        return false;
    }
    
    showLoader();
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`Błąd serwera: ${response.status} ${response.statusText}`);
        }
        
        const data = await response.json();
        hideLoader();
        
        if (data.success) {
            showMessage('Numer seryjny został zaktualizowany.', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.error || 'Błąd podczas aktualizacji numeru seryjnego');
        }
    } catch (error) {
        hideLoader();
        showMessage(error.message, 'error');
        console.error('Serial update error:', error);
    }
    
    return false;
}

// Document ready handler for phone input validation
document.addEventListener('DOMContentLoaded', function() {
    // Phone number validation
    document.querySelector('input[name="additional_phone"]')?.addEventListener('input', function(e) {
        // Allow only digits
        let value = e.target.value.replace(/\D/g, '');
        
        // Limit to 9 digits
        if (value.length > 9) {
            value = value.slice(0, 9);
        }
        
        // Update the input value
        e.target.value = value;
    });
});