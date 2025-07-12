/**
 * Mejoras Globales para el Tema Hijo de Avisos Perú
 * v1.1.0
 * - Previene búsquedas vacías en todos los formularios.
 * - Corrige el renderizado de videos verticales al cargar y al redimensionar/hacer zoom.
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
     * TAREA 2: Corregir renderizado del video en la página de detalle.
     */
    const mediaViewer = document.getElementById('ap-main-media-viewer');
    if (mediaViewer) {
        const videoElement = mediaViewer.querySelector('video');
        if (videoElement) {
            
            // Función para forzar el ajuste del video
            const fixVideoRender = () => {
                // Truco para forzar al navegador a re-calcular las dimensiones
                videoElement.style.height = 'auto'; // Resetea la altura
                setTimeout(() => {
                    // Y la vuelve a aplicar para que se ajuste al contenedor
                    videoElement.style.height = '100%'; 
                }, 10);
            };

            // Ejecuta la corrección al cargar la página
            fixVideoRender();
            
            // Y vuelve a ejecutarla cada vez que la ventana cambia de tamaño (o se hace zoom)
            window.addEventListener('resize', fixVideoRender);
        }
    }
});