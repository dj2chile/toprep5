// Utility functions for repair details page

// QR Code functions
function showQRCode() {
    const modal = document.getElementById('qrcode-modal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function copyStatusUrl() {
    // Get status URL from data attribute
    const statusUrlElement = document.getElementById('qrcode');
    const url = statusUrlElement?.getAttribute('data-url');
    
    if (url) {
        try {
            // Create temporary input element
            const tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);
            
            // Select and copy content
            tempInput.select();
            document.execCommand('copy');
            
            // Remove temporary element
            document.body.removeChild(tempInput);
            
            showMessage('Link został skopiowany do schowka', 'success');
        } catch (err) {
            showMessage('Nie udało się skopiować linku', 'error');
        }
    }
}

async function regenerateQRCode() {
    if (!confirm('Czy na pewno chcesz wygenerować nowy kod QR? Stary kod przestanie działać.')) {
        return;
    }
    
    showLoader();
    
    try {
        const formData = new FormData();
        formData.append('generate_new_status_url', '1');
        formData.append('csrf_token', document.getElementById('csrf_token').value);
        
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
            showMessage('Nowy kod QR został wygenerowany.', 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            throw new Error(data.error || 'Błąd podczas generowania kodu QR');
        }
    } catch (error) {
        hideLoader();
        showMessage(error.message, 'error');
        console.error('QR code regeneration error:', error);
    }
}

// Helper functions
function composeSms(phoneNumber, status) {
    if (!phoneNumber) return;
    
    // Get current status from data attribute if available, otherwise use parameter
    const statusElement = document.querySelector('.status-label');
    const currentStatus = status || statusElement?.textContent?.trim() || '';
    
    let message;
    
    if (currentStatus) {
        switch (currentStatus) {
            case 'Gotowe':
            case 'Gotowa do odbioru':
                message = 'Twoje urządzenie jest gotowe do odbioru. Zapraszamy!';
                break;
            case 'Oczekiwanie na części':
                message = 'Oczekujemy na części do Twojego urządzenia.';
                break;
            default:
                message = 'Status Twojego urządzenia: ' + currentStatus;
        }
    } else {
        message = 'Informacja o naprawie urządzenia.';
    }
    
    const cleanPhone = phoneNumber.replace(/[^0-9+]/g, '');
    window.location.href = `sms:${cleanPhone}?body=${encodeURIComponent(message)}`;
}

// UI feedback functions
function showLoader() {
    // Remove existing loader if exists
    hideLoader();
    
    // Create new loader element
    const loader = document.createElement('div');
    loader.id = 'global-loader';
    loader.className = 'loading-indicator';
    loader.innerHTML = `
        <div class="bg-white p-5 rounded-lg">
            <div class="loading-spinner mx-auto"></div>
            <p class="mt-3 text-center">Przetwarzanie...</p>
        </div>
    `;
    
    document.body.appendChild(loader);
}

function hideLoader() {
    const loader = document.getElementById('global-loader');
    if (loader) {
        loader.remove();
    }
}

function showMessage(message, type = 'success') {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.message-notification');
    existingMessages.forEach(msg => {
        msg.remove();
    });
    
    // Create new message notification
    const notification = document.createElement('div');
    notification.className = `message-notification fixed top-4 right-4 px-4 py-3 rounded z-50 ${
        type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove message after timeout
    setTimeout(() => {
        notification.remove();
    }, type === 'success' ? 3000 : 5000);
}

// Initialize QR code on page load
document.addEventListener('DOMContentLoaded', function() {
    // QR Code generation
    const qrcodeElement = document.getElementById("qrcode");
    if (qrcodeElement && window.QRCode && qrcodeElement.getAttribute('data-url')) {
        new QRCode(qrcodeElement, {
            text: qrcodeElement.getAttribute('data-url'),
            width: 256,
            height: 256
        });
    }
});