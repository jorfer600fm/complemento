document.addEventListener('DOMContentLoaded', function() {
    
    /**
     * Lógica para la galería de medios interactiva.
     * v2.0 - Añadido soporte para alternar entre video y fotos.
     */
    const mainMediaContainer = document.getElementById('ap-main-media-viewer');
    const thumbnails = document.querySelectorAll('.ap-media-thumbnail');

    if (mainMediaContainer && thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function(e) {
                e.preventDefault();
                
                const mediaType = this.getAttribute('data-media-type');

                if (mediaType === 'image') {
                    // Si es una imagen, crea un elemento <img>
                    const fullImageUrl = this.getAttribute('data-full-url');
                    const newImage = document.createElement('img');
                    newImage.src = fullImageUrl;
                    newImage.alt = "Vista principal";
                    
                    mainMediaContainer.innerHTML = ''; // Limpia el contenedor
                    mainMediaContainer.appendChild(newImage);

                } else if (mediaType === 'video') {
                    // Si es el video, usa el HTML que pasamos desde PHP
                    // La variable `ap_video_player_html` es creada en single-aviso.php
                    if (typeof ap_video_player_html !== 'undefined') {
                        mainMediaContainer.innerHTML = ap_video_player_html;
                    }
                }
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