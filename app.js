// Keypad functionality
let phoneNumber = '';

function initKeypad() {
    const keypadButtons = [
        ['1', '2', '3'],
        ['4', '5', '6'],
        ['7', '8', '9'],
        ['', '0', 'del']
    ];
    
    const keypadGrid = document.getElementById('keypadButtons');
    keypadGrid.innerHTML = '';
    
    keypadButtons.forEach(row =>