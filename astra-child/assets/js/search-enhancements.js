document.addEventListener('DOMContentLoaded', function() {
    
    // --- Punto 1: Lógica para la "X" en la barra de búsqueda ---
    const searchForm = document.querySelector('.ap-search-form');
    if (searchForm) {
        const searchInput = searchForm.querySelector('.ap-search-field');
        const inputGroup = searchInput.parentElement; // El div .ap-search-input-group
        
        // Crear el botón de limpiar
        const clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = 'ap-search-clear';
        clearButton.innerHTML = '&times;'; // Símbolo de "X"
        clearButton.style.display = 'none'; // Oculto por defecto
        inputGroup.style.position = 'relative'; // Necesario para posicionar el botón
        inputGroup.appendChild(clearButton);

        // Función para mostrar u ocultar la "X"
        const toggleClearButton = () => {
            if (searchInput.value.length > 0) {
                clearButton.style.display = 'block';
            } else {
                clearButton.style.display = 'none';
            }
        };

        // Mostrar el botón si ya hay texto al cargar la página
        toggleClearButton();

        // Escuchar cambios en el input
        searchInput.addEventListener('input', toggleClearButton);

        // Limpiar el campo al hacer clic en la "X"
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            toggleClearButton();
            searchInput.focus();
        });
    }

    // --- Punto 3: Lógica para el formato de miles en los filtros de precio ---
    const formatNumber = (input) => {
        // No hacer nada si el input está vacío
        if(input.value === '') return;

        // Quitar cualquier caracter que no sea un dígito
        let num = input.value.replace(/\D/g, '');
        
        // Formatear con comas
        input.value = new Intl.NumberFormat('en-US').format(num);
    };

    const minPriceInput = document.getElementById('min_price_filter');
    const maxPriceInput = document.getElementById('max_price_filter');

    if(minPriceInput) {
        minPriceInput.addEventListener('keyup', () => formatNumber(minPriceInput));
        // Formatear al cargar la página si ya tiene un valor
        formatNumber(minPriceInput); 
    }
    if(maxPriceInput) {
        maxPriceInput.addEventListener('keyup', () => formatNumber(maxPriceInput));
        // Formatear al cargar la página si ya tiene un valor
        formatNumber(maxPriceInput);
    }
});