document.addEventListener('DOMContentLoaded', function() {
    
    const mainMediaContainer = document.getElementById('ap-main-media-viewer');
    const thumbnails = document.querySelectorAll('.ap-media-thumbnail');

    if (mainMediaContainer && thumbnails.length > 0) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function(e) {
                e.preventDefault();
                
                const mediaType = this.getAttribute('data-media-type');
                mainMediaContainer.innerHTML = ''; // Limpiar el visor principal

                if (mediaType === 'image') {
                    const fullImageUrl = this.getAttribute('data-full-url');
                    const newImage = document.createElement('img');
                    newImage.src = fullImageUrl;
                    newImage.alt = "Vista principal";
                    mainMediaContainer.appendChild(newImage);

                } else if (mediaType === 'video') {
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