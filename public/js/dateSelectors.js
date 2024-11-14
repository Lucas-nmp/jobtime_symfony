// Función para actualizar los días en función del mes seleccionado
function updateDays() {
    const monthSelect = document.getElementById('monthSelect');
    const daySelect = document.getElementById('daySelect');
    const selectedMonth = monthSelect.value;
    
    daySelect.innerHTML = '<option value="">Seleccione día</option>';
    
    let daysInMonth;
    if (selectedMonth === '2') {
        const selectedYear = parseInt(document.getElementById('yearSelect').value);
        daysInMonth = (selectedYear % 4 === 0 && selectedYear % 100 !== 0) || selectedYear % 400 === 0 ? 29 : 28;
    } else if (['4', '6', '9', '11'].includes(selectedMonth)) {
        daysInMonth = 30;
    } else {
        daysInMonth = 31;
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = i;
        daySelect.appendChild(option);
    }
}

function populateYearSelector() {
    const yearSelect = document.getElementById('yearSelect');
    
    // Limpiar opciones duplicadas en cada llamada
    yearSelect.innerHTML = '<option value="">Seleccione año</option>';
    
    const currentYear = new Date().getFullYear();
    for (let i = currentYear; i >= currentYear - 4; i--) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = i;
        yearSelect.appendChild(option);
    }
}