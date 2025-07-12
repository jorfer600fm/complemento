/**
 * Mejoras Globales para el Tema Hijo de Avisos Perú
 * v1.1.0
 * - Previene búsquedas vacías en todos los formularios.
 * - Corrección de video eliminada, ahora se gestiona por CSS.
 */
document.addEventListener('DOMContentLoaded', function() {

    /**
     * TAREA 1: Prevenir búsquedas vacías.
     */
    const searchForms = document.querySelectorAll('.ap-search-form');
    searchForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const searchInput = form.querySelector('.ap-search-field');
            if (searchInput && searchInput.value.trim() === '') {
                e.preventDefault();

                searchInput.classList.add('ap-input-error');
                
                const originalPlaceholder = searchInput.placeholder;
                searchInput.placeholder = 'Por favor, escribe algo para buscar...';

                setTimeout(() => {
                    searchInput.classList.remove('ap-input-error');
                    searchInput.placeholder = originalPlaceholder;
                }, 2500);
            }
        });
    });

    /**
     * TAREA 2: Corrección de video eliminada.
     * La lógica para el renderizado del video ha sido movida a una solución
     * más robusta y eficiente en single-aviso.css
     */
});