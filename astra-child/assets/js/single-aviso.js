document.addEventListener('DOMContentLoaded', function() {
    
    /**
     * Lógica para la galería de medios interactiva.
     */
    const mainMediaContainer = document.getElementById('ap-main-media-viewer');
    const thumbnails = document.querySelectorAll('.ap-media-thumbnail');

    if (mainMediaContainer && thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function(e) {
                e.preventDefault(); // Evita que la página salte
                
                // Obtiene la URL de la imagen en alta resolución del atributo 'data-full-url'
                const fullImageUrl = this.getAttribute('data-full-url');
                
                // Crea un nuevo elemento de imagen
                const newImage = document.createElement('img');
                newImage.src = fullImageUrl;
                newImage.alt = "Vista principal";

                // Vacía el contenedor principal y añade la nueva imagen
                mainMediaContainer.innerHTML = '';
                mainMediaContainer.appendChild(newImage);
            });
        });
    }

    /**
     * Lógica para auto-seleccionar el enlace para compartir.
     */
    const shareLinkInput = document.getElementById('ap-share-link-input');
    if (shareLinkInput) {
        shareLinkInput.addEventListener('click', function() {
            this.select();
        });
    }
});